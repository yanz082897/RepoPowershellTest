<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');
use Restserver\Libraries\REST_Controller;

class Redemptions extends REST_Controller {

    public function __construct() {
      parent::__construct();
        $this->load->model('db_model');
        $this->load->library('Authorization_Token');
        $this->lang->load('response');
        // $this->load->helper('cookie');
    }

    public function index_post($payload)
    {
        $date           = new DateTime();
        $input          = json_decode(file_get_contents('php://input'), true);
        $headers        = $this->input->request_headers(); 
        $validateToken  = validateTermID($input, $headers);
        $txn_id         = gen_txn_id();
        $pl_url         = $this->db_model->selectQuery('mt_sys_properties',array('FLAG' => 1, 'PROPERTY' => 'PINELABS_APISERVER'));
        $pl_endpoints   = $this->db_model->selectQuery('mt_sys_properties',array('FLAG' => 1),'PROPERTY, VALUE');

        if ($validateToken) {
            $http = REST_Controller::HTTP_OK;

            if (!is_array($input)) {
                $code = '90405';
                $msg = 'Method Not Allowed';
                $http = REST_Controller::HTTP_METHOD_NOT_ALLOWED;
            } elseif ( ! in_array($payload, array('redeem','void','reverse','rev_void'))) {
                $code = '90400';
                $msg = 'Bad Request';
                $http = REST_Controller::HTTP_BAD_REQUEST; 
            } else {
                foreach ($input as $key => $value) { $_POST[$key] = $value; }

                $this->config->load('form_validation_rules', TRUE);
                $this->form_validation->set_rules($this->config->item($payload, 'form_validation_rules'));
                $this->form_validation->set_error_delimiters('(',')');

                if($this->form_validation->run()) {
                    $busRefNum = $input['Stan'].$input['InvoiceNumber'];
                    $request_header = json_encode(array("Content-Type:application/json","DateAtClient:".date('Y-m-d'),"TransactionId:".$txn_id,"Authorization: Bearer ".$validateToken));
                    $transactionMode= getTranMode(@$input['PosCode']);

                    switch ($payload) {
                        case 'redeem':
                            // $url     = PINELABS_URL.'/gc/transactions';
                            foreach ($pl_endpoints as $key => $field) { if($pl_endpoints[$key]->PROPERTY == 'PL_redemption') { $url = $pl_url[0]->VALUE.$pl_endpoints[$key]->VALUE; } }

                            $post_array = array(
                                    "transactionTypeId" => "302",
                                    "inputType" => "1",
                                    "invoiceNumber" => $input['InvoiceNumber'],
                                    "invoiceDate" => date('Y-m-d'),
                                    "invoiceAmount" => (isDE4Format($input['Amount']) ? iso8583Amount($input['Amount']) : $input['Amount']),
                                    "BusinessReferenceNumber" => $busRefNum,
                                    "IdempotencyKey" => "0200-".$input['ProcessingCode']."-".date('Ymd')."-".$input['Stan']."-".$input['MerchantID']."-".$input['TerminalID'],
                                    "cards" => [array(
                                        "CardNumber" => $input['Track2Data'],
                                        "CardPin" => $input['CardPin'],
                                        "Amount" => (isDE4Format($input['Amount']) ? iso8583Amount($input['Amount']) : $input['Amount'])
                                    )],
                                    "NetworkTransactionInfo" => array(
                                        "mid" => $input['MerchantID'],
                                        "tid" => $input['TerminalID'],
                                        "TransactionMode" => $transactionMode,
                                    )
                                );
                            $errorWhereClause = array("IdempotencyKey"=>$post_array['IdempotencyKey'],"InvoiceNumber"=>$input['InvoiceNumber']);

                            @$this->db_insert('redeem','in',$input,$post_array,$request_header);
                            
                            //check IdempotencyKey uniqueness
                            $record = $this->db_model->selectQuery('TT_TRANSACTIONS_GATEWAY',array('IDEMPOTENCYKEY' => $post_array['IdempotencyKey'], 'MSGTYPE' => '0210'));
                            if ($record) {
                                $response = array("ResponseCode"=>"90403","ResponseMessage"=>"Invalid Idempotency Key.");
                                $this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }
                            if ($input['ProcessingCode'] <> '069000') {
                                $response = array("ResponseCode"=>"90401","ResponseMessage"=>"Invalid Processing Code.");
                                $this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }
                            if ($this->db_model->selectQuery('TT_TRANSACTIONS_GATEWAY',array('INVOICENUM' => $input['InvoiceNumber'], 'MSGTYPE' => '0210'))) {
                                $response = array("ResponseCode"=>"90403","ResponseMessage"=>"Invalid Invoice Number.");
                                $this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            } //added 20231107

                            $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);

                           /// $this->log_trail('redeem',json_encode($input),($result?$result:NULL),($result?json_decode($result)->ResponseMessage:NULL));

                            if (!$result) { 
                                $response = array(
                                            "ResponseCode" => '99204',
                                            "ResponseMessage" => 'No Response from PL'
                                        );
                                $this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response([], REST_Controller::HTTP_NO_CONTENT);
                            // elseif (json_decode($result)->ResponseCode <> 0) { $this->response(json_decode($result, TRUE), $http); } 
                            } elseif (empty(json_decode($result)->ResponseMessage)) {
                                $response = array("ResponseCode"=>'90400',"ResponseMessage"=>'Bad Request');
                                @$this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                            } elseif (json_decode($result)->ResponseCode <> 0 || is_null(json_decode($result)->ResponseCode)) { 
                                $response = json_decode($result,1);
                                $code = (empty($response['Cards']) ? $response['ResponseCode'] : $response['Cards'][0]['ResponseCode']);
                                $msg = (empty($response['Cards']) ? $response['ResponseMessage'] : $response['Cards'][0]['ResponseMessage']);
                                $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg);
                                @$this->db_insert('redeem','out',$response,($errorWhereClause+$message),json_encode($headers));

                                $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
                            } else {
                                $response = json_decode($result);
                                $return_array = array(
                                                "02^PAN" => $response->Cards[0]->CardNumber,
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $response->Cards[0]->TransactionAmount,
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => date('H:iA', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "13^Date" => date('Y-m-d', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => $response->BusinessReferenceNumber ? $response->BusinessReferenceNumber : str_pad('',12,'0'),
                                                "38^ApprovalCode" => $response->Cards[0]->ApprovalCode.'-'.$response->CurrentBatchNumber,
                                                "39^ResponseCode" => $response->Cards[0]->ResponseCode,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "62^InvoiceNumber" => $input['InvoiceNumber'],
                                                "63^Balance" => $response->Cards[0]->Balance
                                            );

                                @$this->db_insert('redeem','out',json_decode($result,1),$return_array,json_encode($headers));
                                $this->response($return_array, $http);
                            }
                            break;
                        case 'void':
                            // $url     = PINELABS_URL.'/gc/transactions/cancel';
                            foreach ($pl_endpoints as $key => $field) { if($pl_endpoints[$key]->PROPERTY == 'PL_voidRed') { $url = $pl_url[0]->VALUE.$pl_endpoints[$key]->VALUE; } }

                            $records = $this->db_model->selectQuery('tt_transactions_gateway',array('TRANSACTIONTYPEID' => '302', 'INVOICENUM' => $input['InvoiceNumber'], 'RESPONSECODE' => '0', 'CARDNUMBER' => $input['PAN'], 'MIDTID' => $input['MerchantID'].'-'.$input['TerminalID']));
                            $errorWhereClause = array("ProcessingCode"=>$input['ProcessingCode'],"InvoiceNumber"=>$input['InvoiceNumber']);
                            $post_array = array(
                                    "transactionTypeId" => "312",
                                    "TransactionModeID" => 0,
                                    "inputType" => "1",
                                    "numberOfCards" => 1,
                                    "BusinessReferenceNumber" => $input['ReferenceNumber'],
                                    "cards" => [array(
                                        "CardNumber" => @$records[0]->CARDNUMBER,
                                        "OriginalRequest" => array(
                                            "OriginalBatchNumber" => @$records[0]->BATCHNUMBER,
                                            "OriginalTransactionId" => @$records[0]->TRANSACTIONID,
                                            "OriginalApprovalCode" => @$records[0]->APPROVAL_CODE,
                                            "OriginalAmount" => @$records[0]->AMOUNT,
                                            "OriginalInvoiceNumber" => @$records[0]->INVOICENUM
                                        ),
                                        "Reason" => "incorrectRedemption"
                                    )],
                                    "NetworkTransactionInfo" => array(
                                        "mid" => $input['MerchantID'],
                                        "tid" => $input['TerminalID'],
                                        "TransactionMode" => $transactionMode,
                                    )
                                );

                            @$this->db_insert('void','in',$input,$post_array,$request_header);

                            if (!$records) {
                                $response = array("ResponseCode"=>"99402","ResponseMessage"=>"Transaction Not Found");
                                $this->db_insert('void','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_NOT_FOUND);
                            }
                            if ($input['ProcessingCode'] <> '020000') {
                                $response = array("ResponseCode"=>"90401","ResponseMessage"=>"Invalid Processing Code.");
                                $this->db_insert('void','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }

                            $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);

                            //$this->log_trail('void',json_encode($input),($result?$result:NULL),($result?json_decode($result)->ResponseMessage:NULL));

                            if (!$result) { 
                                $response = array(
                                            "ResponseCode" => '99204',
                                            "ResponseMessage" => 'No Response from PL'
                                        );
                                $this->db_insert('void','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response([], REST_Controller::HTTP_NO_CONTENT);
                            // elseif (json_decode($result)->ResponseCode <> 0) { $this->response(json_decode($result, TRUE), $http); } 
                            } elseif (empty(json_decode($result)->ResponseMessage)) {
                                $response = array("ResponseCode"=>'90400',"ResponseMessage"=>'Bad Request');
                                @$this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                            } elseif (json_decode($result)->ResponseCode <> 0 || is_null(json_decode($result)->ResponseCode)) { 
                                $response = json_decode($result,1);
                                $code = (empty($response['Cards']) ? $response['ResponseCode'] : $response['Cards'][0]['ResponseCode']);
                                $msg = (empty($response['Cards']) ? $response['ResponseMessage'] : $response['Cards'][0]['ResponseMessage']);
                                $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg);
                                @$this->db_insert('void','out',$response,($errorWhereClause+$message),json_encode($headers));
                               
                                $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
                            } else {
                                $response = json_decode($result);
                                $return_array = array(
                                                "02^PAN" => $response->Cards[0]->CardNumber,
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $response->Cards[0]->TransactionAmount,
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => date('H:iA', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "13^Date" => date('Y-m-d', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => $response->BusinessReferenceNumber ? $response->BusinessReferenceNumber : str_pad('',12,'0'),
                                                "38^ApprovalCode" => $response->Cards[0]->ApprovalCode.'-'.$response->CurrentBatchNumber,
                                                "39^ResponseCode" => $response->Cards[0]->ResponseCode,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => $response->Cards[0]->Balance
                                            );

                                @$this->db_insert('void','out',json_decode($result,1),$return_array,json_encode($headers));
                                $this->response($return_array, $http);
                            }
                            break;
                        case 'reverse':
                            // $url     = PINELABS_URL.'/gc/transactions/reverse';
                            foreach ($pl_endpoints as $key => $field) { if($pl_endpoints[$key]->PROPERTY == 'PL_reversal') { $url = $pl_url[0]->VALUE.$pl_endpoints[$key]->VALUE; } }

                            $post_array = array(
                                    "transactionTypeId" => "302",
                                    "inputType" => "1",
                                    "invoiceNumber" => $input['InvoiceNumber'],
                                    "invoiceDate" => date('Y-m-d'),
                                    "invoiceAmount" => (isDE4Format($input['Amount']) ? iso8583Amount($input['Amount']) : $input['Amount']),
                                    "BusinessReferenceNumber" => $busRefNum,
                                    "IdempotencyKey" => "0400-".$input['ProcessingCode']."-".date('Ymd')."-".$input['Stan']."-".$input['MerchantID']."-".$input['TerminalID'],
                                    "cards" => [array(
                                        "CardNumber" => $input['Track2Data'],
                                        //"CardPin" => $input['CardPin'], removed 08/09/2023 by Bryan as per sir Alvin and Klint
                                        "Amount" => (isDE4Format($input['Amount']) ? iso8583Amount($input['Amount']) : $input['Amount'])
                                    )],
                                    "NetworkTransactionInfo" => array(
                                        "mid" => $input['MerchantID'],
                                        "tid" => $input['TerminalID'],
                                        "TransactionMode" => $transactionMode,
                                    )
                                );
                            $errorWhereClause = array("IdempotencyKey"=>$post_array['IdempotencyKey'],"InvoiceNumber"=>$input['InvoiceNumber']);

                            @$this->db_insert('reverse','in',$input,$post_array,$request_header);
                            
                            $record = $this->db_model->selectQuery('tt_transactions_gateway',array('CARDNUMBER' => $input['Track2Data'], 'INVOICENUM' => $input['InvoiceNumber'], 'MSGTYPE' => '0210'));
                            if (!$record) {
                                $response = array("ResponseCode"=>"99402","ResponseMessage"=>"Transaction Not Found");
                                $this->db_insert('reverse','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_NOT_FOUND);
                            }
                            if ($input['ProcessingCode'] <> '069000') {
                                $response = array("ResponseCode"=>"90401","ResponseMessage"=>"Invalid Processing Code.");
                                $this->db_insert('reverse','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }

                            $txn_id = $record[0]->TRANSACTIONID;
                            $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);

                            //$this->log_trail('reverse',json_encode($input),($result?$result:NULL),($result?json_decode($result)->ResponseMessage:NULL));

                            if (!$result) { 
                                $response = array(
                                            "ResponseCode" => '99204',
                                            "ResponseMessage" => 'No Response from PL'
                                        );
                                $this->db_insert('reverse','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response([], REST_Controller::HTTP_NO_CONTENT);
                            // elseif (json_decode($result)->ResponseCode <> 0) { $this->response(json_decode($result, TRUE), $http); } 
                            } elseif (empty(json_decode($result)->ResponseMessage)) {
                                $response = array("ResponseCode"=>'90400',"ResponseMessage"=>'Bad Request');
                                @$this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                            } elseif (json_decode($result)->ResponseCode <> 0 || strpos(json_decode($result)->ResponseMessage, 'Cannot') !== false || strpos(json_decode($result)->ResponseMessage, 'Failed') !== false || (!empty(json_decode($result)->Cards) && strpos(json_decode($result)->Cards[0]->ResponseMessage, 'Cannot') !== false) || is_null(json_decode($result)->ResponseCode)) { 
                                $response = json_decode($result,1);
                                $code = (empty($response['Cards']) ? $response['ResponseCode'] : $response['Cards'][0]['ResponseCode']);
                                $msg = (empty($response['Cards']) ? $response['ResponseMessage'] : $response['Cards'][0]['ResponseMessage']);
                                $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg);
                                @$this->db_insert('reverse','out',$response,($errorWhereClause+$message),json_encode($headers));
                                
                                $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
                            } else {
                                $response = json_decode($result);
                                $return_array = array(
                                                "02^PAN" => $response->Cards[0]->CardNumber,
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $response->Cards[0]->TransactionAmount,
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => date('H:iA', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "13^Date" => date('Y-m-d', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => $response->BusinessReferenceNumber ? $response->BusinessReferenceNumber : str_pad('',12,'0'), //$input['Stan']."-".$response->TransactionId."-".$input['ProcessingCode'],
                                                "38^ApprovalCode" => $response->Cards[0]->ApprovalCode.'-'.$response->CurrentBatchNumber,
                                                "39^ResponseCode" => $response->Cards[0]->ResponseCode,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => $response->Cards[0]->Balance
                                            );

                                @$this->db_insert('reverse','out',json_decode($result,1),($return_array+array('InvoiceNumber'=>$records[0]->INVOICENUM)),json_encode($headers));
                                $this->response($return_array, $http);
                            }
                            break;
                        case 'rev_void':
                            // $url     = PINELABS_URL.'/gc/transactions/reverse';
                            foreach ($pl_endpoints as $key => $field) { if($pl_endpoints[$key]->PROPERTY == 'PL_voidRev') { $url = $pl_url[0]->VALUE.$pl_endpoints[$key]->VALUE; } }

                            $records = $this->db_model->selectQuery('TT_TRANSACTIONS_GATEWAY',array('TRANSACTIONTYPEID' => '312', 'INVOICENUM' => $input['InvoiceNumber'], 'RESPONSECODE' => '0', 'CARDNUMBER' => $input['PAN'], 'MIDTID' => $input['MerchantID'].'-'.$input['TerminalID']));
                            $errorWhereClause = array("ProcessingCode"=>$input['ProcessingCode'],"InvoiceNumber"=>$input['InvoiceNumber']);
                            $post_array = array(
                                    "transactionTypeId" => "312",
                                    "TransactionModeID" => 0,
                                    "inputType" => "1",
                                    "numberOfCards" => 1,
                                    "BusinessReferenceNumber" => $busRefNum,
                                    "cards" => [array(
                                        "CardNumber" => @$records[0]->CARDNUMBER,
                                        "OriginalRequest" => array(
                                            "OriginalBatchNumber" => @$records[0]->BATCHNUMBER,
                                            "OriginalTransactionId" => @$records[0]->TRANSACTIONID,
                                            "OriginalApprovalCode" => @$records[0]->APPROVAL_CODE,
                                            "OriginalAmount" => @$records[0]->AMOUNT,
                                            "OriginalInvoiceNumber" => @$records[0]->INVOICENUM
                                        ),
                                        "Reason" => "incorrectRedemption",
                                        "NetworkTransactionInfo" => array(
                                            "mid" => $input['MerchantID'],
                                            "tid" => $input['TerminalID'],
                                            "TransactionMode" => $transactionMode
                                        )
                                    )]
                                );

                            @$this->db_insert('rev_void','in',$input,$post_array,$request_header);

                            if (!$records) {
                                $response = array("ResponseCode"=>"99402","ResponseMessage"=>"Transaction Not Found");
                                $this->db_insert('rev_void','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_NOT_FOUND);
                            }
                            if ($input['ProcessingCode'] <> '020000') {
                                $response = array("ResponseCode"=>"90401","ResponseMessage"=>"Invalid Processing Code.");
                                $this->db_insert('rev_void','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }
                            
                            $txn_id = $records[0]->TRANSACTIONID;
                            $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);

                            //$this->log_trail('reverse_void',json_encode($input),($result?$result:NULL),($result?json_decode($result)->ResponseMessage:NULL));

                            if (!$result) { 
                                $response = array(
                                            "ResponseCode" => '99204',
                                            "ResponseMessage" => 'No Response from PL'
                                        );
                                $this->db_insert('rev_void','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response([], REST_Controller::HTTP_NO_CONTENT);
                            // elseif (json_decode($result)->ResponseCode <> 0) { $this->response(json_decode($result, TRUE), $http); } 
                            } elseif (empty(json_decode($result)->ResponseMessage)) {
                                $response = array("ResponseCode"=>'90400',"ResponseMessage"=>'Bad Request');
                                @$this->db_insert('redeem','out',$response,($errorWhereClause+$response),json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                            } elseif (json_decode($result)->ResponseCode <> 0 || strpos(json_decode($result)->ResponseMessage, 'Cannot') !== false || strpos(json_decode($result)->ResponseMessage, 'Failed') !== false || (!empty(json_decode($result)->Cards) && strpos(json_decode($result)->Cards[0]->ResponseMessage, 'Cannot') !== false) || is_null(json_decode($result)->ResponseCode)) { 
                                $response = json_decode($result,1);
                                $code = (empty($response['Cards']) ? $response['ResponseCode'] : $response['Cards'][0]['ResponseCode']);
                                $msg = (empty($response['Cards']) ? $response['ResponseMessage'] : $response['Cards'][0]['ResponseMessage']);
                                $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg);
                                @$this->db_insert('rev_void','out',$response,($errorWhereClause+$message),json_encode($headers));
                               
                                $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
                            } else {
                                $response = json_decode($result);
                                $return_array = array(
                                                "02^PAN" => $response->Cards[0]->CardNumber,
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $response->Cards[0]->TransactionAmount,
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => date('H:iA', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "13^Date" => date('Y-m-d', strtotime($response->Cards[0]->TransactionDateTime)),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => $response->BusinessReferenceNumber ? $response->BusinessReferenceNumber : str_pad('',12,'0'), //$input['Stan']."-".$response->TransactionId."-".$input['ProcessingCode'];
                                                "38^ApprovalCode" => $response->Cards[0]->ApprovalCode.'-'.$response->CurrentBatchNumber,
                                                "39^ResponseCode" => $response->Cards[0]->ResponseCode,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => $response->Cards[0]->Balance
                                            );

                                @$this->db_insert('rev_void','out',json_decode($result,1),($return_array+array('InvoiceNumber'=>$records[0]->INVOICENUM)),json_encode($headers));
                                $this->response($return_array, $http);
                            }
                            break;
                    }
                } else {
                    $code = '90400';
                    $msg = str_replace("\n", ', ',trim(validation_errors()));
                    $http = REST_Controller::HTTP_BAD_REQUEST; 
                }
            }
            
            //$this->log_trail($payload, file_get_contents('php://input'), (empty($result)?NULL:$result), $msg);
            $this->response(array("ResponseCode" => $code, "ResponseMessage" => $msg), $http);
        }
    }

    private function log_trail($payload, $input, $response, $msg)
    {
        $log_details = array(
           'IP_ADDRESS'         => $_SERVER['REMOTE_ADDR'],
           'USER_AGENT'         => $_SERVER['HTTP_USER_AGENT'],
           'MODULE'             => $payload,
           'REQUEST'            => $input,
           'RESPONSE'           => $response, 
           'MESSAGE'            => $msg
          );
        @$this->db->insert('MT_API_LOGS',$log_details);
    }

    private function db_insert($payload, $type, $input1, $input2 = '', $headers = FALSE)
    {
        $date = new DateTime();
        if ($input1 && is_array($input1) && in_array($payload, array('redeem','void','reverse','rev_void'))) {
            switch ($payload) {
                case 'redeem':
                case 'reverse':
                    if ($type == 'in') {
                        $data = array(
                           'TRANSACTIONTYPEID'  => '302',
                           'TRANDATETIME'       => $date->format('Y-m-d H:i:s'),
                           'IDEMPOTENCYKEY'     => ($input2 ? $input2['IdempotencyKey'] : NULL),
                           'STAN'               => $input1['Stan'],
                           'MIDTID'             => $input1['MerchantID'].'-'.$input1['TerminalID'],
                           'CARDNUMBER'         => $input1['Track2Data'],
                           'AMOUNT'             => (string)$input1['Amount'], 
                           'REQUESTCODE'        => ($payload=='redeem'?'0200':'0400'),
                           'MSGTYPE'            => ($payload=='redeem'?'0200':'0400'),
                           'PROCESSINGCODE'     => $input1['ProcessingCode'],
                           'INVOICENUM'         => $input1['InvoiceNumber'],
                           'INVOICEDATE'        => $date->format('Y-m-d H:i:s'),
                           'ACTUALMSGREQ'       => ($input2 ? json_encode($input2) : NULL),
                           'ACTUALMSGBYGCREQ'   => json_encode($input1),
                           'REQUESTHEADER'      => ($headers ? $headers : NULL)
                          );
                        @$this->db->insert('tt_transactions_gateway',$data);
                    } elseif ($type == 'out') {
                        $data = array(
                           'TRANSACTIONID'      => @($input1['TransactionId']  ? $input1['TransactionId']  : 0),
                           // 'TRANSACTIONTYPEID'  => '302',
                           'BATCHNUMBER'        => @$input1['CurrentBatchNumber'],
                           'APPROVAL_CODE'      => @$input1['Cards'][0]['ApprovalCode'],
                           'TRANDATETIMERSP'    => (empty($input1['Cards'][0]['TransactionDateTime']) ? $date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s',strtotime($input1['Cards'][0]['TransactionDateTime']))),
                           // 'IDEMPOTENCYKEY'     => @$input1['IdempotencyKey'],
                           // 'STAN'               => ($input2 ? $input2['11^Stan'] : NULL),
                           // 'CARDNUMBER'         => @$input1['Cards'][0]['CardNumber'],
                           'AMOUNT'             => @$input1['Cards'][0]['TransactionAmount'], 
                           'RESPONSECODE'       => (empty($input1['Cards']) ? $input1['ResponseCode'] : $input1['Cards'][0]['ResponseCode']),
                           'MSGTYPE'            => ($payload=='redeem'?'0210':'0410'),
                           // 'PROCESSINGCODE'     => ($input2 ? $input2['62^ProcessingCode'] : NULL),
                           // 'INVOICENUM'         => @$input1['Cards'][0]['InvoiceNumber'],
                           'INVOICEDATE'        => $date->format('Y-m-d H:i:s'),
                           'ACTUALMSGRESP'      => json_encode($input1),
                           'ACTUALMSGBYGCRESP'  => (($input2 && !empty($input2['ResponseCode'])) ? json_encode(array("ResponseCode"=>$input2['ResponseCode'],"ResponseMessage"=>$input2['ResponseMessage'])) : json_encode($input2)),
                           'RESPONSEHEADER'     => ($headers ? $headers : NULL)
                          );
                        $where = array(
                                'IDEMPOTENCYKEY'=> (!empty($input2['IdempotencyKey']) ? $input2['IdempotencyKey'] : $input1['IdempotencyKey']),
                                'INVOICENUM'    => (!empty($input2['InvoiceNumber']) ? $input2['InvoiceNumber'] : $input1['Cards'][0]['InvoiceNumber']),
                                'MSGTYPE'       => ($payload=='redeem'?'0200':'0400'),
                                'TRANSACTIONTYPEID' => '302'
                            );
                        @$this->db_model->updateRecord('tt_transactions_gateway', $data, $where);
                    }
                    break;
                case 'void':
                case 'rev_void':
                    if ($type == 'in') {
                        $data = array(
                           'TRANSACTIONTYPEID'  => '312',
                           'TRANDATETIME'       => $date->format('Y-m-d H:i:s'),
                           'CARDNUMBER'         => $input1['PAN'],
                           'AMOUNT'             => (!empty($input2['Cards']) ? $input2['Cards'][0]['OriginalAmount'] : (isDE4Format($input1['Amount']) ? iso8583Amount($input1['Amount']) : $input1['Amount'])), 
                           'STAN'               => $input1['Stan'],
                           'MIDTID'             => $input1['MerchantID'].'-'.$input1['TerminalID'],
                           'REQUESTCODE'        => ($payload=='void'?'0200':'0400'),
                           'MSGTYPE'            => ($payload=='void'?'0200':'0400'),
                           'PROCESSINGCODE'     => $input1['ProcessingCode'],
                           'INVOICENUM'         => $input1['InvoiceNumber'],
                           'INVOICEDATE'        => $date->format('Y-m-d H:i:s'),
                           'ACTUALMSGREQ'       => ($input2 ? json_encode($input2) : NULL),
                           'ACTUALMSGBYGCREQ'   => json_encode($input1),
                           'REQUESTHEADER'      => ($headers ? $headers : NULL)
                          );
                        @$this->db->insert('tt_transactions_gateway',$data);
                    } elseif ($type == 'out') {
                        $data = array(
                           'TRANSACTIONID'      => @($input1['TransactionId']  ? $input1['TransactionId']  : 0),
                           // 'TRANSACTIONTYPEID'  => '312',
                           'BATCHNUMBER'        => @$input1['CurrentBatchNumber'],
                           'APPROVAL_CODE'      => @$input1['Cards'][0]['ApprovalCode'],
                           'TRANDATETIMERSP'    => (empty($input1['Cards'][0]['TransactionDateTime']) ? $date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s',strtotime($input1['Cards'][0]['TransactionDateTime']))),
                           'IDEMPOTENCYKEY'     => @($input1['IdempotencyKey'] ? $input1['IdempotencyKey'] : NULL),
                           // 'STAN'               => ($input2 ? $input2['11^Stan'] : NULL),
                           // 'CARDNUMBER'         => @$input1['Cards'][0]['CardNumber'],
                           // 'AMOUNT'             => @$input1['Cards'][0]['TransactionAmount'], 
                           'RESPONSECODE'       => (empty($input1['Cards']) ? $input1['ResponseCode'] : $input1['Cards'][0]['ResponseCode']),
                           'MSGTYPE'            => ($payload=='void'?'0210':'0410'),
                           // 'PROCESSINGCODE'     => ($input2 ? $input2['03^ProcessingCode'] : NULL),
                           // 'INVOICENUM'         => @$input1['Cards'][0]['InvoiceNumber'],
                           'INVOICEDATE'        => $date->format('Y-m-d H:i:s'),
                           'ACTUALMSGRESP'      => json_encode($input1),
                           'ACTUALMSGBYGCRESP'  => (($input2 && !empty($input2['ResponseCode'])) ? json_encode(array("ResponseCode"=>$input2['ResponseCode'],"ResponseMessage"=>$input2['ResponseMessage'])) : json_encode($input2)),
                           'RESPONSEHEADER'     => ($headers ? $headers : NULL)
                          );
                        $where = array(
                            'PROCESSINGCODE'    => (!empty($input2['ProcessingCode']) ? $input2['ProcessingCode'] : '020000'),
                            'INVOICENUM'        => (!empty($input2['InvoiceNumber']) ? $input2['InvoiceNumber'] : $input1['Cards'][0]['InvoiceNumber']),
                            'MSGTYPE'           => ($payload=='void'?'0200':'0400'),
                            'TRANSACTIONTYPEID' => '312'
                        );
                        @$this->db_model->updateRecord('tt_transactions_gateway',$data,$where);
                    }
                    break;
            }
            // if(!empty($data)) @$this->db->insert('TT_TRANSACTIONS_GATEWAY',$data);
        }
    }

}