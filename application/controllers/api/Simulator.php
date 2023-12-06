<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');
use Restserver\Libraries\REST_Controller;

class Simulator extends REST_Controller {

    public function __construct() {
      parent::__construct();
        $this->load->model('db_model');
        $this->load->library('Authorization_Token');
        $this->lang->load('response');
    }

    public function index_post($payload)
    {
        $date           = new DateTime();
        $input          = json_decode(file_get_contents('php://input'), true);
        $headers        = $this->input->request_headers(); 
        $validateToken  = validateTermID($input, $headers);
        // $txn_id         = gen_txn_id();

        if ($validateToken) {
            $http = REST_Controller::HTTP_OK;

            if (!is_array($input)) {
                $code = '90405';
                $msg = 'Method Not Allowed';
                $http = REST_Controller::HTTP_METHOD_NOT_ALLOWED;
            } elseif ( ! in_array($payload, array('balance','redeem','void','reverse','rev_void','settlement'))) {
                $code = '90400';
                $msg = 'Bad Request';
                $http = REST_Controller::HTTP_BAD_REQUEST; 
            } else {
                foreach ($input as $key => $value) { $_POST[$key] = $value; }

                $this->config->load('form_validation_rules', TRUE);
                $this->form_validation->set_rules($this->config->item($payload, 'form_validation_rules'));
                $this->form_validation->set_error_delimiters('(',')');

                if($this->form_validation->run()) {
                    $busRefNum = $input['Stan'].@$input['InvoiceNumber'];
                    // $request_header = json_encode(array("Content-Type:application/json","DateAtClient:".date('Y-m-d'),"TransactionId:".$txn_id,"Authorization: Bearer ".$validateToken));
                    switch ($payload) {
                        case 'balance':
                            $url     = PINELABS_URL.'/gc/transactions';
                            $processingCode = '310000';
                            $busRefNum = str_pad($input['Stan'].@$txn_id, 12,"0",STR_PAD_LEFT);
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
                                        "TransactionMode" => 2,
                                    )
                                );

                            if ( $record = $this->db_model->selectQuery('TT_TRANSACTIONS_GATEWAY',array('STAN' => $input['Stan']))) {
                                $response = array(
                                            "ResponseCode" => '90400',
                                            "ResponseMessage" => 'Invalid Stan'
                                        ); 
                                $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                            }

                            $return_array = array(
                                                "02^PAN" => $input['Track2Data'],
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => 0,
                                                "11^Stan" => @$input['Stan'] ? $input['Stan'] : NULL,
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => str_pad('',12,'0'),
                                                "38^ApprovalCode" => str_pad('',8,'0'),
                                                "39^ResponseCode" => 0,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => 0
                                            );
                            break;
                        case 'redeem':
                            $url     = PINELABS_URL.'/gc/transactions';
                            $processingCode = '069000';
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
                                        "TransactionMode" => 2,
                                    )
                                );
                                $return_array = array(
                                                "02^PAN" => $input['Track2Data'],
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $input['Amount'],
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => str_pad('',12,'0'),
                                                "38^ApprovalCode" => str_pad('',8,'0'),
                                                "39^ResponseCode" => 0,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "62^InvoiceNumber" => $input['InvoiceNumber'],
                                                "63^Balance" => 0
                                            );
                            break;
                        case 'void':
                            $url     = PINELABS_URL.'/gc/transactions/cancel';
                            $processingCode = '020000';
                            $post_array = array(
                                    "transactionTypeId" => "312",
                                    "TransactionModeID" => 0,
                                    "inputType" => "1",
                                    "numberOfCards" => 1,
                                    "BusinessReferenceNumber" => $input['ReferenceNumber'],
                                    "cards" => [array(
                                        "CardNumber" => $input['PAN'],
                                        "OriginalRequest" => array(
                                            "OriginalBatchNumber" => str_pad('',8,'0'),
                                            "OriginalTransactionId" => str_pad('',3,'0'),
                                            "OriginalApprovalCode" => str_pad('',8,'0'),
                                            "OriginalAmount" => 0,
                                            "OriginalInvoiceNumber" => $input['InvoiceNumber']
                                        ),
                                        "Reason" => "incorrectRedemption"
                                    )],
                                    "NetworkTransactionInfo" => array(
                                        "mid" => $input['MerchantID'],
                                        "tid" => $input['TerminalID'],
                                        "TransactionMode" => 2,
                                    )
                                );

                                $return_array = array(
                                                "02^PAN" => $input['PAN'],
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $input['Amount'],
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => str_pad('',12,'0'),
                                                "38^ApprovalCode" => str_pad('',8,'0'),
                                                "39^ResponseCode" => 0,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => 0
                                            );
                            break;
                        case 'reverse':
                            $url     = PINELABS_URL.'/gc/transactions/reverse';
                            $processingCode = '069000';
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
                                        "TransactionMode" => 2,
                                    )
                                );
                                $return_array = array(
                                                "02^PAN" => $input['Track2Data'],
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $input['Amount'],
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => str_pad('',12,'0'),
                                                "38^ApprovalCode" => str_pad('',8,'0'),
                                                "39^ResponseCode" => 0,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => 0
                                            );
                            break;
                        case 'rev_void':
                            $url     = PINELABS_URL.'/gc/transactions/reverse';
                            $processingCode = '020000';
                            $post_array = array(
                                    "transactionTypeId" => "312",
                                    "TransactionModeID" => 0,
                                    "inputType" => "1",
                                    "numberOfCards" => 1,
                                    "BusinessReferenceNumber" => $busRefNum,
                                    "cards" => [array(
                                        "CardNumber" => $input['PAN'],
                                        "OriginalRequest" => array(
                                            "OriginalBatchNumber" => str_pad('',8,'0'),
                                            "OriginalTransactionId" => str_pad('',3,'0'),
                                            "OriginalApprovalCode" => str_pad('',8,'0'),
                                            "OriginalAmount" => $input['Amount'],
                                            "OriginalInvoiceNumber" => $input['InvoiceNumber']
                                        ),
                                        "Reason" => "incorrectRedemption",
                                        "NetworkTransactionInfo" => array(
                                            "mid" => $input['MerchantID'],
                                            "tid" => $input['TerminalID'],
                                            "TransactionMode" => 2
                                        )
                                    )]
                                );

                                $return_array = array(
                                                "02^PAN" => $input['PAN'],
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "04^TransactionAmount" => $input['Amount'],
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => $input['NII'],
                                                "37^ReferenceNumber" => str_pad('',12,'0'),
                                                "38^ApprovalCode" => str_pad('',8,'0'),
                                                "39^ResponseCode" => 0,
                                                "41^TerminalID" => $input['TerminalID'],
                                                "42^MerchantID" => $input['MerchantID'],
                                                "63^Balance" => 0
                                            );
                            break;

                        case 'settlement':                 
                            $url     = PINELABS_URL.'/batchclose';
                            $processingCode = '920000';
                            $post_array = array(
                                        "TransactionId"=>@$txn_id,
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
                                $return_array = array(
                                                "03^ProcessingCode" => $input['ProcessingCode'],
                                                "11^Stan" => $input['Stan'],
                                                "12^Time" => $date->format('H:iA'),
                                                "13^Date" => $date->format('Y-m-d'),
                                                "24^NII" => @$input['NII'],
                                                "39^ResponseCode" => 0,
                                                "41^TerminalID" => $input['TerminalID']
                                            );
                            break;
                    }

                    if ($input['ProcessingCode'] <> $processingCode) {
                        $response = array("ResponseCode"=>"90401","ResponseMessage"=>"Invalid Processing Code.");
                        $this->response($response, REST_Controller::HTTP_BAD_REQUEST);
                    }

                    $result = TRUE;
                    // $result = $this->run_curl($url, $post_array, 'post', 'json', $txn_id, $validateToken);

                    if (!$result) { 
                        $response = array(
                                    "ResponseCode" => '99204',
                                    "ResponseMessage" => 'No Response from PL'
                                );
                        $this->response([], REST_Controller::HTTP_NO_CONTENT);
                    // } elseif (empty(json_decode($result)->ResponseMessage)) {
                    //     $response = array("ResponseCode"=>'90400',"ResponseMessage"=>'Bad Request');
                    //     $this->response($response, REST_Controller::HTTP_BAD_REQUEST); 
                    // } elseif (json_decode($result)->ResponseCode <> 0 || is_null(json_decode($result)->ResponseCode)) { 
                    //     $response = json_decode($result,1);
                    //     $code = (empty($response['Cards']) ? $response['ResponseCode'] : $response['Cards'][0]['ResponseCode']);
                    //     $msg = (empty($response['Cards']) ? $response['ResponseMessage'] : $response['Cards'][0]['ResponseMessage']);
                    //     $message = array("ResponseCode"=>$code,"ResponseMessage"=>$msg);

                    //     $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
                    } else {
                        $this->response($return_array, $http);
                    }
                } else {
                    $code = '90400';
                    $msg = str_replace("\n", ', ',trim(validation_errors()));
                    $http = REST_Controller::HTTP_BAD_REQUEST; 
                }
            }
            
            $this->response(array("ResponseCode" => $code, "ResponseMessage" => $msg), $http);
        } else {
            $this->response(array("ResponseCode"=>"90400","ResponseMessage"=>"Bad Request"), REST_Controller::HTTP_BAD_REQUEST);
        }
    }

}