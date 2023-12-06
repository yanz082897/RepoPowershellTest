<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');
use Restserver\Libraries\REST_Controller;

class Settlement extends REST_Controller {

    public function __construct() {
      parent::__construct();
        $this->load->model('db_model');
        $this->load->library('Authorization_Token');

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
            } elseif ( ! in_array($payload, array('balance','settlement'))) {
                $code = '90400';
                $msg = 'Bad Request';
                $http = REST_Controller::HTTP_BAD_REQUEST; 
            } else {
                foreach ($input as $key => $value) { $_POST[$key] = $value; }

                $this->config->load('form_validation_rules', TRUE);
                $this->form_validation->set_rules($this->config->item($payload, 'form_validation_rules'));
                $this->form_validation->set_error_delimiters('(',')');
                $this->form_validation->error_array();

                if($this->form_validation->run()) {
                    $busRefNum = str_pad($input['Stan'].$txn_id, 12,"0",STR_PAD_LEFT);
                    $request_header = json_encode(array("Content-Type:application/json","DateAtClient:".date('Y-m-d'),"TransactionId:".$txn_id,"Authorization: Bearer ".$validateToken));

                    $transactionMode= getTranMode(@$input['PosCode']);

                    switch ($payload) {
                        case 'balance':
                            // $url     = PINELABS_URL.'/gc/transactions';
                            foreach ($pl_endpoints as $key => $field) { if($pl_endpoints[$key]->PROPERTY == 'PL_balInquiry') { $url = $pl_url[0]->VALUE.$pl_endpoints[$key]->VALUE; } }
                            
                            $post_array = array(
                                    "transactionTypeId" => "306",
                                    "inputType" => "1",
                                    "BusinessReferenceNumber" => $busRefNum,
                                    "cards" => [array(
                                        "CardNumber" => $input['Track2Data'],
                                    )],
                                    "NetworkTransactionInfo" => array(
                                        "mid" => $input['MerchantID'],
                                        "tid" => $input['TerminalID'],
                                        "TransactionMode" => $transactionMode,
                                    )
                                );

                            if ($input['ProcessingCode'] <> '310000') {
                                $code = '90401';
                                $msg = 'Invalid Processing Code.';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        ); 
                                $this->db_insert_failed_validation('balance',$input,$response, $request_header);
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }

                            if ( $record = $this->db_model->selectQuery('TT_TRANSACTIONS_GATEWAY',array('STAN' => $input['Stan']))) {
                                $code = '90400';
                                $msg = 'Invalid Stan';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        ); 
                                $this->db_insert_failed_validation('balance',$input,$response, $request_header);
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            } 


                            @$this->db_insert('balance','in',$input,$post_array, NULL, $request_header);
                            $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);
                            //$this->log_trail('balance',json_encode($input),($result?$result:NULL),($result?json_decode($result)->ResponseMessage:NULL));

                            if (!$result) { 
                                $code = '99204';
                                $msg = 'No Response from PL';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        );
                                $this->db_insert('balance','out',$response,NULL,$input['Stan'],json_encode($headers));
                                $this->response(NULL, REST_Controller::HTTP_NO_CONTENT); 
                            // elseif (json_decode($result)->ResponseCode <> 0) { $this->response(json_decode($result, TRUE), $http); } 
                            } elseif (empty(json_decode($result)->ResponseMessage)) {
                                $code = '90400';
                                $msg = 'Bad Request';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        );
                                @$this->db_insert('balance','out',$response,NULL,$input['Stan'],json_encode($headers));
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                            } elseif (json_decode($result)->ResponseCode <> 0 || is_null(json_decode($result)->ResponseCode)) { 
                                $response = json_decode($result,1);
                                $code = (empty($response['Cards']) ? $response['ResponseCode'] : $response['Cards'][0]['ResponseCode']);
                                $msg = (empty($response['Cards']) ? $response['ResponseMessage'] : $response['Cards'][0]['ResponseMessage']);
                                $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg);
                                $this->db_insert('balance','out',$response,$message,$input['Stan'],json_encode($headers));
                                $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
                            } else {
                                $response = json_decode($result);
                                $timestamp = strtotime($response->Cards[0]->TransactionDateTime);
                                $return_array = array(
                                                "02^PAN" => $response->Cards[0]->CardNumber,
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $response->Cards[0]->TransactionAmount,
                                                "11^Stan" => @$input['Stan'] ? $input['Stan'] : NULL,
                                                "12^Time" => date('H:iA',$timestamp),
                                                "13^Date" => date('Y-m-d',$timestamp),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => $response->BusinessReferenceNumber ? $response->BusinessReferenceNumber : str_pad('',12,'0'),
                                                "38^ApprovalCode" => $response->Cards[0]->ApprovalCode."-".$response->CurrentBatchNumber,
                                                "39^ResponseCode" => $response->Cards[0]->ResponseCode,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => $response->Cards[0]->Balance
                                            );
                                @$this->db_insert('balance','out',json_decode($result,1),$return_array,$input['Stan'],json_encode($headers));
                                $this->response($return_array, $http);
                            }
                            break;

                            case 'settlement':                 
                            // $url     = PINELABS_URL.'/batchclose';
                            foreach ($pl_endpoints as $key => $field) { if($pl_endpoints[$key]->PROPERTY == 'PL_settlement') { $url = $pl_url[0]->VALUE.$pl_endpoints[$key]->VALUE; } }

                            $post_array = array(
                                        "TransactionId"=>$txn_id,
                                        "DateAtClient"=>$date->format('Y-m-d \TH:i:s'),
                                        "ReloadCount"=> 0,
                                        "ReloadAmount"=> 0,
                                        "ActivationCount"=> 0,
                                        "ActivationAmount"=> 0,
                                        "RedemptionCount"=> $input['privateField'][0]['RedemptionCount'],
                                        "RedemptionAmount"=> $input['privateField'][0]['RedemptionAmount'],
                                        "CancelRedeemCount"=> $input['privateField'][0]['CancelRedeemCount'],
                                        "CancelRedeemAmount"=> $input['privateField'][0]['CancelRedeemAmount'],
                                        "CancelLoadAmount"=> 0,
                                        "CancelLoadCount"=> 0,
                                        "CancelActivationCount"=> 0,
                                        "CancelActivationAmount"=> 0,
                                        "IsActivationCancelAmountsProvided"=> false,
                                        "IsActivationCancelCountsProvided"=> false,
                                        "IsLoadCancelAmountsProvided"=> false,
                                        "IsLoadCancelCountsProvided"=> false,
                                        "IsRedeemCancelAmountsProvided"=> ($input['privateField'][0]['CancelRedeemAmount'] ? true : false),
                                        "IsRedeemCancelCountsProvided"=> ($input['privateField'][0]['CancelRedeemCount'] ? true : false),
                                        "BatchValidationRequired"=> true,
                                        "SettlementDate"=> $input['settlementDate'],
                                        "BusinessReferenceNumber" => $busRefNum,
                                        "NetworkTransactionInfo"=> array(
                                            "mid" => $input['MerchantID'],
                                            "tid" => $input['TerminalID'],
                                            "TransactionMode" => 2
                                            )
                                    );
                            if ($input['ProcessingCode'] <> '920000') {
                                $code = '90401';
                                $msg = 'Invalid Processing Code.';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        ); 
                                $this->db_insert_failed_validation('settlement',$input,$response, $request_header);
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }

                            if ( $record = $this->db_model->selectQuery('TT_TRANSACTIONS_GATEWAY',array('STAN' => $input['Stan']))) {
                                $code = '90400';
                                $msg = 'Invalid Stan';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        ); 
                                $this->db_insert_failed_validation('settlement',$input,$response, $request_header);
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }

                            @$this->db_insert('settlement','in',$input,$post_array, NULL, $request_header);
                            $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);
                            //$this->log_trail('settlement',json_encode($input),($result?$result:NULL),($result?json_decode($result)->ResponseMessage:NULL));

                            if (!$result) { 
                                $code = '99204';
                                $msg = 'No Response from PL';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        );
                                $batchNumber = $this->getMaxBatchNumber($input['MerchantID'].'-'.$input['TerminalID']);
                                $this->updateSettlementFlag($input['MerchantID'].'-'.$input['TerminalID'], $batchNumber, 2);
                                $this->db_insert('settlement','out',$response,NULL,$input['Stan'],json_encode($headers));
                                $this->settlement_flag($input['TerminalID'].$input['MerchantID']);
                                $this->response(NULL, REST_Controller::HTTP_NO_CONTENT); 

                            // elseif (json_decode($result)->ResponseCode <> 0) { $this->response(json_decode($result, TRUE), $http); } 
                            } elseif (empty(json_decode($result)->ResponseMessage)) {
                                $code = '90400';
                                $msg = 'Bad Request';
                                $response = array(
                                            "ResponseCode" => $code,
                                            "ResponseMessage" => $msg
                                        );
                                $batchNumber = $this->getMaxBatchNumber($input['MerchantID'].'-'.$input['TerminalID']);
                                $this->updateSettlementFlag($input['MerchantID'].'-'.$input['TerminalID'], $batchNumber, 2);
                                @$this->db_insert('settlement','out',$response,NULL,$input['Stan'],json_encode($headers));

                                $this->settlement_flag($input['TerminalID'].$input['MerchantID']);

                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                            } elseif (json_decode($result)->ResponseCode <> 0 || is_null(json_decode($result)->ResponseCode)) { 
                                $response = json_decode($result,1);
                                $code = $response['ResponseCode'];
                                $msg = $response['ResponseMessage'];
                                $batchNumber = $this->getMaxBatchNumber($input['MerchantID'].'-'.$input['TerminalID']);
                                $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg,"BatchNumber"=>$batchNumber);
                                $this->updateSettlementFlag($input['MerchantID'].'-'.$input['TerminalID'], $batchNumber, 2);
                                $this->db_insert('settlement','out',$response,$message,$input['Stan'],json_encode($headers),$batchNumber);
                                $this->settlement_flag($input['TerminalID'].$input['MerchantID'],@$response->NewAuthToken);

                                $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
                            } else {
                                $response = json_decode($result);
                                $batchNumber = $this->getMaxBatchNumber($input['MerchantID'].'-'.$input['TerminalID']);
                                $return_array = array(
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => @$input['NII'],
                                                "39^ResponseCode" => $response->ResponseCode,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "99^SIIC" => $batchNumber
                                            );
                                $this->updateSettlementFlag($input['MerchantID'].'-'.$input['TerminalID'], $batchNumber, 1);
                                @$this->db_insert('settlement','out',json_decode($result,1),$return_array,$input['Stan'],json_encode($headers),$batchNumber);
                                $this->settlement_flag($input['TerminalID'].$input['MerchantID'],$response->NewAuthToken);
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
            
            //put LOGS here
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

    private function db_insert_failed_validation($payload, $request, $response, $headers = FALSE)
    {
        $date = new DateTime();
        $data = array(
           'TRANSACTIONTYPEID'  => '306',
           'TRANDATETIME'       => $inDate = $date->format('Y-m-d H:i:s'),
           'MIDTID'             => $request['MerchantID'].'-'.$request['TerminalID'],
           'CARDNUMBER'         => @$request['Track2Data'],
           'REQUESTCODE'        => ($payload == 'balance' ? '0100' : '0500'),
           'MSGTYPE'            => ($payload == 'balance' ? '0110' : '0510'),
           'PROCESSINGCODE'     => ($request['ProcessingCode'] ? $request['ProcessingCode'] : NULL),
           'ACTUALMSGREQ'       => json_encode($request),
           'RESPONSECODE'        => $response['ResponseCode'],
           'REQUESTHEADER'      => ($headers ? $headers : NULL),
           'ACTUALMSGRESP'      => json_encode($response),
           'TRANDATETIMERSP'    => $outDate = $date->format('Y-m-d H:i:s')
          );
        @$this->db->insert('tt_transactions_gateway',$data);

    }

    private function db_insert($payload, $type, $input1, $input2 = NULL, $stan = '', $headers = FALSE, $NewBatchNumber=NULL)
    {
        $date = new DateTime();
        if ($input1 && is_array($input1) && in_array($payload, array('balance', 'settlement'))) {
            switch ($payload) {
                case 'balance':
                    if ($type == 'in') {
                        $data = array(
                           'TRANSACTIONTYPEID'  => '306',
                           'TRANDATETIME'       => $inDate = $date->format('Y-m-d H:i:s'),
                           'STAN'               => $input1['Stan'],
                           'MIDTID'             => $input1['MerchantID'].'-'.$input1['TerminalID'],
                           'CARDNUMBER'         => $input1['Track2Data'],
                           'REQUESTCODE'        => '0100',
                           'MSGTYPE'            => '0100',
                           'PROCESSINGCODE'     => ($input1['ProcessingCode'] ? $input1['ProcessingCode'] : NULL),
                           'ACTUALMSGREQ'       => ($input2 ? json_encode($input2) : NULL),
                           'ACTUALMSGBYGCREQ'   => json_encode($input1),
                           'REQUESTHEADER'      => ($headers ? $headers : NULL)
                          );
                        @$this->db->insert('tt_transactions_gateway',$data);

                    } elseif ($type == 'out') {
                        $data = array(
                           'TRANSACTIONID'      => @($input1['TransactionId']  ? $input1['TransactionId']  : 0),
                           'TRANSACTIONTYPEID'  => '306',
                           'BATCHNUMBER'        => @$input1['CurrentBatchNumber'],
                           'APPROVAL_CODE'      => @$input1['Cards'][0]['ApprovalCode'],
                           // 'STAN'               => ($input2 ? $input2['11^Stan'] : NULL),
                           // 'TRANDATETIME'       => $date->format('Y-m-d H:i:s'),
                           // 'CARDNUMBER'         => @$input1['Cards'][0]['CardNumber'],
                           'AMOUNT'             => @$input1['Cards'][0]['Balance'], 
                           'RESPONSECODE'        => $input1['ResponseCode'],
                           'MSGTYPE'            => '0110',
                           // 'PROCESSINGCODE'     => ($input2 ? $input2['03^ProcessingCode'] : NULL),
                           'ACTUALMSGRESP'      => json_encode($input1),
                           'ACTUALMSGBYGCRESP'  => ($input2 ? json_encode($input2) : NULL),
                           'RESPONSEHEADER'      => ($headers ? $headers : NULL),
                           'TRANDATETIMERSP'    => $outDate = date('Y-m-d H:i:s', strtotime($input1['Cards'][0]['TransactionDateTime'])),
                          );
                        @$this->db_model->updateRecord('tt_transactions_gateway',$data,array('STAN'=>$stan));
                    }
                    break;

                case 'settlement':
                    if ($type == 'in') {
                        $data = array(
                           'TRANSACTIONID'      => $input2['TransactionId'],
                           'TRANSACTIONTYPEID'  => '20',
                           'TRANDATETIME'       => $date->format('Y-m-d H:i:s'),
                           'STAN'               => $input1['Stan'],
                           'MIDTID'             => $input1['MerchantID'].'-'.$input1['TerminalID'],
                           'REQUESTCODE'        => '0500',
                           'MSGTYPE'            => '0500',
                           'AMOUNT'             => @$input1['privateField'][0]['batchTotal'], 
                           'PROCESSINGCODE'     => ($input1['ProcessingCode'] ? $input1['ProcessingCode'] : NULL),
                           'ACTUALMSGREQ'       => ($input2 ? json_encode($input2)
                            : NULL),
                           'ACTUALMSGBYGCREQ'   => json_encode($input1),
                           'REQUESTHEADER'      => ($headers ? $headers : NULL)
                          );
                        @$this->db->insert('tt_transactions_gateway',$data);
                    } elseif ($type == 'out') {
                        $data = array(
                           'TRANSACTIONID'      => @($input1['TransactionId']  ? $input1['TransactionId']  : 0),
                           'BATCHNUMBER'        => $NewBatchNumber,
                           'TRANSACTIONTYPEID'  => '20',
                           'APPROVAL_CODE'      => @$input1['ApprovalCode'],
                           // 'STAN'               => ($input2 ? $input2['11^Stan'] : NULL),
                           // 'TRANDATETIME'       => $date->format('Y-m-d H:i:s'),
                           'RESPONSECODE'        => $input1['ResponseCode'],
                           'MSGTYPE'            => '0510',
                           // 'PROCESSINGCODE'     => ($input2 ? $input2['03^ProcessingCode'] : NULL),
                           'ACTUALMSGRESP'      => json_encode($input1),
                           'ACTUALMSGBYGCRESP'  => ($input2 ? json_encode($input2) : NULL),
                           'RESPONSEHEADER'      => ($headers ? $headers : NULL),
                           'TRANDATETIMERSP'    => $outDate = $date->format('Y-m-d H:i:s'),
                          );
                        $result = $this->db_model->updateRecord('tt_transactions_gateway',$data,array('STAN'=>$stan));
                    }
                    break;
            }
            // if(!empty($data)) @$this->db->insert('TT_TRANSACTIONS_GATEWAY',$data);
        }
    }

    public function getMaxBatchNumber ($midTid) {
        $last_row=$this->db->query("SELECT BATCHNUMBER FROM tt_transactions_gateway where BATCHNUMBER is not null AND SETTLEMENT_FLAG = 0 AND MIDTID = '".$midTid."'    order by TT_ID desc  LIMIT 1")->row();
        // $last_row=$this->db->select('BATCHNUMBER')->order_by('BATCHNUMBER',"desc")->limit(1)->get_where('TT_TRANSACTIONS_GATEWAY', array('MIDTID'=>$midTid,'SETTLEMENT_FLAG'=>0))->row();
        return $batchNumber = $last_row->BATCHNUMBER;
    }

    public function updateSettlementFlag ($midTid, $batchNumber, $newFlag, $flag = 0){
        // $last_row=$this->db->query("SELECT BATCHNUMBER FROM tt_transactions_gateway where BATCHNUMBER is not null AND SETTLEMENT_FLAG = 0 AND MIDTID = '".$midTid."'    order by TT_ID desc  LIMIT 1")->row();
        // $batchNumber = $last_row->BATCHNUMBER;
        // echo $batchNumber;
        $data = array('SETTLEMENT_FLAG' => $newFlag);
        $this->db->set($data);
        $this->db->where('MIDTID', $midTid);
        $this->db->where('BATCHNUMBER', $batchNumber);
        $this->db->where_not_in('TRANSACTIONTYPEID', array('20'));
        $this->db->update('tt_transactions_gateway'); 
    }
}