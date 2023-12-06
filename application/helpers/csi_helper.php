<?php 
 function debug($data,$die=FALSE){
	echo '<pre>';
	var_dump($data);
	echo '</pre>';
    if($die){
        die();
    }
} 

function validateDate($date,$format='Y-m-d'){
 
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;

}


/**
 * Calls Sequence for Transaction ID
 *
 * @author KIM 20230626
 * @return Transaction ID
 */

function gen_txn_id($payload = FALSE)
{
    $ci = get_instance();
    /* //get sequence from mssql
    $res = $ci->db->query("SELECT NEXT VALUE FOR TXN_ID_SEQ AS TXN_ID")->row();
    return $res->TXN_ID;
    */
    //get sequence from mysql
    @$ci->db->insert('mt_sequences',array('payload'=>($payload?$payload:'TRANSACTION')));
    return $ci->db->insert_id();
}

/**
 * Calls look-up table for TransactionMode value
 *
 * @author KIM 20231121
 * @return TransactionMode
 */

function getTranMode($posCode = NULL)
{
    $ci = get_instance();
    $txnMode = 1;
    if ($posCode) {
        $tranModeSql = $ci->db_model->selectQuery('lu_posentrycodes',array('entrymodecode' => $posCode));
        $txnMode = $tranModeSql ? $tranModeSql[0]->pltrancode : $txnMode;
    }
    return $txnMode;
}

/**
 * Validates Signature and Terminal ID
 *
 * @author KIM 20230627
 * @return Token
 */
function validateTermID($input, $headers)
{
    $ci = get_instance();
    $signature = hashsha256(GC_USERNAME.hashsha256(GC_PASSWORD));
    if (empty($headers['Signature']) || empty($input['TerminalID']) || empty($input['MerchantID'])) {
        header("HTTP/1.1 401 Authentication Failed");
        return FALSE;
    } elseif ($headers['Signature'] <> $signature) {
        header("HTTP/1.1 401 Unauthorized");
        return FALSE;
    } else {
        $tid = $input['TerminalID'].$input['MerchantID'];
        if (GC_ALWAYS_AUTH == 0) {
            //FOR TEMPORARY CODE 08012023 BRYAN
            
             $retriveauth = @$ci->retrieve_auth($tid,date('Y-m-d'));
             // $retriveauth = @$ci->retrieve_auth('SM-Store-WPOS-01',date('Y-m-d'));

        }else{
            $retriveauth=false;
        }

        if ($retriveauth) {
            

        if ($retriveauth[0]['tokendate'] == date('Y-m-d') &&  $retriveauth[0]['regen_flag'] =='0') {
                $auth_response=@$retriveauth[0]['api_token'];
            }else{
            //FOR TEMPORARY CODE 08012023 BRYAN

                @$decodetoken = @$ci->authorizedAPI($tid);
                // $decodetoken = @$ci->authorizedAPI('SM-Store-WPOS-01');


                @$auth_response = json_decode(@$decodetoken)->AuthToken;


            }

        }else{
            //FOR TEMPORARY CODE 08012023 BRYAN
                // $decodetoken = @$ci->authorizedAPI($input['TerminalID']);
 
                @$decodetoken = @$ci->authorizedAPI($tid);

               
                @$auth_response = json_decode(@$decodetoken)->AuthToken;
        }
       
        

        //FOR TEMPORARY CODE 07252023 BRYAN
        // $auth_response = $ci->authorizedAPI('SM-Store-WPOS-01');

        if ($auth_response ) {
            return $auth_response;
        } elseif (empty($auth_response)) {
             @$responseCodeErr = json_decode(@$decodetoken)->ResponseCode;
             @$responseCodeMsg = json_decode(@$decodetoken)->ResponseMessage;

          //$response = array("ResponseCode"=>"90999","ResponseMessage"=>"Connection Failed");
          echo json_encode(array('ResponseCode' =>$responseCodeErr,'ResponseMessage'=>$responseCodeMsg));

            header("HTTP/1.1 401 Authentication Failed");
            return FALSE;
        } else {
            header("HTTP/1.1 400 Bad Request");
            return FALSE;  
        }
    }
}

/**
 * To check if a string follows DE4 format
 *
 * @author KIM 20230811
 * @return boolean
 */
function isDE4Format($amount) {
    // DE4 format typically consists of numeric characters with a fixed length
    $pattern = '/^\d{12}$/'; // Adjust the pattern based on your ISO8583 specification
    
    return preg_match($pattern, $amount) === 1;
}

/**
 * Insert decimal point at the appropriate position of Amount and convert to float
 *
 * @author KIM 20230811
 * @return Amount
 */
function iso8583Amount($amount)
{
    $currencyPrecision = 2; // Currency precision (e.g., 2 for PHP)
    $amountWithDecimal = (float)(substr_replace($amount, '.', -$currencyPrecision, 0));
    return (string)$amountWithDecimal;
}

function input_prep($string){ //used to handle inputs before going to database
    if(is_array($string)){
        foreach ($string as $key => $value) {
             $string[$key] = clean_string($value);
        }
    }else{
        $string = clean_string($string);
    }
   
   return $string;
    
}
/**BRYAN API 09152021 **/
function hashsha256($string){
    
    return  hash('sha256', $string);
}

function jsonbuild($json=array()){
    header('Content-Type: application/json');
     
    
    return json_encode($json); 
}
function jsonparse($json){
    header('Content-Type: application/json');
     
    
    return json_decode($json); 
}
function xmlbuild($xml_data,$tagname){
   header("Content-type: text/xml");
     
    
   $xml = new SimpleXMLElement('<'.$tagname.'/>');
   array_walk_recursive($xml_data, array ($xml, 'addChild'));

  return $xml->asXML();
}

// function defination to convert array to xml
function array_to_xml( $data, &$xml_data, $url=null) { //added $url 20230208KIM
    foreach( $data as $key => $value ) {
        if (!empty($value)) {
            if( is_array($value)) {
                if (!empty($value["@attributes"])) {
                        $subnode = $xml_data->addChild($key, (!empty(@$value["@value"]) ? $value["@value"] : null));
                        foreach ($value["@attributes"] as $key1 => $val1) {
                            $subnode->addAttribute($key1, $val1);
                        }
                    if (count($value)>1 && empty(@$value["@value"])) array_to_xml($value, $subnode); //added 20230208KIM
                } else if ($key === "@value") {
                    foreach ($value as $attr => $attrVal) {
                        $subnode = $xml_data->addChild("$attr", $attrVal);
                        array_to_xml($attrVal, $subnode);
                    }
                } else {
                        if (!empty($value) && $key <> "@attributes") {
                                $subnode = $xml_data->addChild($key, null, $url);
                                array_to_xml($value, $subnode);
                        }
                }
            } else {
                    $xml_data->addChild("$key",$value);
            }
        }
    }
}

function build_xml($data, $root = '<root></root>', $prefix, $payload, $url){
    // creating object of SimpleXMLElement
    // $xml_data = new SimpleXMLElement($root);
    $xml_res  = new SimpleXMLElement($root, 0, false, $prefix, true);
    $xml_data   = $xml_res->addChild($prefix.':'.$payload, null, $url);
    
    // function call to convert array to xml
    array_to_xml($data,$xml_data);
    return $xml_res->asXML();
}

function xmlObj($xml, $prefix=false){

    $parse = simplexml_load_string($xml);
    if (!$prefix) return $parse;
    $namespaces = $parse->getNameSpaces(true);

    return $parse->children($namespaces[$prefix]);
}

function build_xml_head($data, $msgdefidr = 'pacs.008.001.08', $prefix, $payload, $url)
{
    $CI = & get_instance();
    $headers = $CI->input->request_headers();
    $signature = @$headers['Signature'];

    switch ($msgdefidr) {
        case 'pacs.008.001.08': $BizMsgIdr_char22 = 'S'; break;
        case 'pacs.002.001.10': $BizMsgIdr_char22 = 'R'; break;
        default: $BizMsgIdr_char22 = '0'; break;
    }
    switch ($payload) {
        case 'AdmnSignOnReq': 
            $Fr_BIC = $data['SignOnReq']['InstgAgt']['FinInstnId']['BIC']; 
            $To_BIC = $data['SignOnReq']['InstdAgt']['FinInstnId']['BIC']; 
            break;
        case 'AdmnSignOffReq': 
            $Fr_BIC = $data['SignOffReq']['InstgAgt']['FinInstnId']['BIC']; 
            $To_BIC = $data['SignOffReq']['InstdAgt']['FinInstnId']['BIC']; 
            break;
        case 'AdmnEchoReq': 
            $Fr_BIC = $data['EchoTxInf']['InstgAgt']['FinInstnId']['BIC']; 
            $To_BIC = $data['EchoTxInf']['InstdAgt']['FinInstnId']['BIC']; 
            break;
        default: $Fr_BIC = 'BNNNPHM2XXX'; $To_BIC = 'BANKPHPHXXX'; break;
    }
    $head_url   = 'urn:iso:std:iso:20022:tech:xsd:head.001.001.01';
    $head_data1 = array(
            'FIId' => array(
                'FinInstnId' => array(
                    'BICFI' => $Fr_BIC
                ),
                'BrnchId' => array(
                    'Id' => BANKIDSOURCE//DBP BANK ID
                )
            )
        );
    $head_data2 = array(
            'FIId' => array(
                'FinInstnId' => array(
                    'BICFI' => $To_BIC
                ),
                'BrnchId' => array(
                    'Id' => BANKIDDESTINATION //PCHCP BANK ID
                )
            )
        );
    $sgntr_data = array(
            'Signature' => array(
                'SignedInfo' => array(
                    'CanonicalizationMethod' => array(
                        '@attributes' => array(
                              'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315'
                            )
                    ),
                    'SignatureMethod' => array(
                        '@attributes' => array(
                              'Algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
                            )
                    ),
                    'Reference' => array(
                        '@attributes' => array(
                              'URI' => ''
                            ),
                        'Transforms' => array( //INVALID ARRAY: duplicate 'Transform' keys from the example
                            'Transform' => array(
                                '@attributes' => array(
                                      'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
                                    )
                            ),
                            'Transform' => array(
                                '@attributes' => array(
                                      'Algorithm' => 'http://www.w3.org/2006/12/xml-c14n11'
                                    )
                            )
                        ),
                        'DigestMethod' => array(
                            '@attributes' => array(
                                  'Algorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256'
                                )
                        ),
                        'DigestValue' => 'xe/kONljHYOi5X1sw8AmgIjbHw/SX8zjAT98zpJahhI='
                    )
                ),
                'SignatureValue' => (empty($signature) ? '7vdS9h04J/slnfUO1aoQ/RvbvWE=' : $signature),
                'KeyInfo' => array(
                    'X509Data' => array(
                        'X509SubjectName' => 'CN=Bank1, OU=Vocalink, O=VL, L=Rickmansworth, ST=UK, C=en',
                        'X509IssuerSerial' => array(
                            'X509IssuerName' => 'CN=VL1, OU=Vocalink, O=VL, L=Rickmansworth, ST=UK, C=en',
                            'X509SerialNumber' => '1328092436'
                        )
                    )
                )
            )
        );

    $root       = '<Message xmlns="urn:instapay" xmlns:head="urn:iso:std:iso:20022:tech:xsd:head.001.001.01" xmlns:'.$prefix.'="urn:iso:std:iso:20022:tech:xsd:'.$msgdefidr.'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:instapay messages.xsd" />';
    $xml_res    = new SimpleXMLElement($root, 0, false, $prefix, true);
    $header     = $xml_res->addChild('AppHdr');
    $head_item1 = $header->addChild('Fr', null, $head_url);
    $head_item2 = $header->addChild('To', null, $head_url);
                  $header->addChild('BizMsgIdr', 'B'.date('Ymd').BANKIDSOURCE.'B'.$BizMsgIdr_char22.'000002344', $head_url);
                  $header->addChild('MsgDefIdr', $msgdefidr, $head_url);
                  $header->addChild('CreDt', date('Y-m-d\\TH:i:s'), $head_url);
                  $header->addChild('CpyDplct', 'DUPL', $head_url);
    $signature  = $header->addChild('Sgntr', null, $head_url);
    $body       = $xml_res->addChild('Document');
    $xml_data   = $body->addChild($payload);
    

    array_to_xml($head_data1,$head_item1);
    array_to_xml($head_data2,$head_item2);
    array_to_xml($sgntr_data,$signature,'http://www.w3.org/2000/09/xmldsig#');
    array_to_xml($data,$xml_data,$url);

    return $xml_res->asXML();
}

function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}

function get_http_response_msg($url) {
    $headers = get_headers($url);
    return substr($headers[0], 13);
}

function api_response($code, $msg, $payload = FALSE, $src = '')
{
    switch ($code) {
        case '100': $desc = 'Continue'; break;
        case '101': $desc = 'Switching Protocols'; break;
        case '200': $desc = 'OK'; break;
        case '201': $desc = 'Created'; break;
        case '202': $desc = 'Accepted'; break;
        case '203': $desc = 'Non-Authoritative Information'; break;
        case '204': $desc = 'No Content'; break;
        case '205': $desc = 'Reset Content'; break;
        case '206': $desc = 'Partial Content'; break;
        case '300': $desc = 'Multiple Choices'; break;
        case '301': $desc = 'Moved Permanently'; break;
        case '302': $desc = 'Moved Temporarily'; break;
        case '303': $desc = 'See Other'; break;
        case '304': $desc = 'Not Modified'; break;
        case '305': $desc = 'Use Proxy'; break;
        case '400': $desc = 'Bad Request'; break;
        case '401': $desc = 'Unauthorized'; break;
        case '402': $desc = 'Payment Required'; break;
        case '403': $desc = 'Forbidden'; break;
        case '404': $desc = 'Not Found'; break;
        case '405': $desc = 'Method Not Allowed'; break;
        case '406': $desc = 'Not Acceptable'; break;
        case '407': $desc = 'Proxy Authentication Required'; break;
        case '408': $desc = 'Request Time-out'; break;
        case '409': $desc = 'Conflict'; break;
        case '410': $desc = 'Gone'; break;
        case '411': $desc = 'Length Required'; break;
        case '412': $desc = 'Precondition Failed'; break;
        case '413': $desc = 'Request Entity Too Large'; break;
        case '414': $desc = 'Request-URI Too Large'; break;
        case '415': $desc = 'Unsupported Media Type'; break;
        case '500': $desc = 'Internal Server Error'; break;
        case '501': $desc = 'Not Implemented'; break;
        case '502': $desc = 'Bad Gateway'; break;
        case '503': $desc = 'Service Unavailable'; break;
        case '504': $desc = 'Gateway Time-out'; break;
        case '505': $desc = 'HTTP Version not supported'; break;
        default:
            exit('Unknown http status code "' . htmlentities($code) . '"');
        break;
    }

    if ($payload=='error') {
        $response = array(
            'Errors' => array(
                'Error' => [ array(
                    "Source" => $src,
                    "ReasonCode" => $code,
                    "Description" => $msg,
                    "Recoverable" => TRUE,
                    "Details" => $desc
                ) ]
            )
        );
    } elseif ($payload) {
        $response = array(
            $payload => array(
                "code" => $code,
                "message" => $msg
            )
        );
    } else {
        $response = array(
            "code" => $code,
            "message" => $msg
        );
    }

    header("HTTP/1.1 ".$code." ".$desc);
    return jsonbuild($response);
}

function clean_string($string){
    $string = trim($string);
    $string = str_replace("'", "''", $string);
    return $string;
}

function remove_newline($string){
	return trim(preg_replace('/\s+/', ' ', $string));
}

function generatePassword() {
    $length = 8;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];}

        return $randomString;
}
/*
function iencode( $string ) {
    $secret_key = SALT;
    $secret_iv = ENC_IV;
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash( 'sha256', $secret_key );
    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
    $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    return $output;
}

function idecode( $string ) {
 
    $secret_key = SALT;
    $secret_iv = ENC_IV;
 
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash( 'sha256', $secret_key );
    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

    $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    return $output;
}
*/
function getUserType($usertype){

    $user = [
        1 => 'SYSTEM USER',
        0 => 'PORTAL USER'
    ];

    return $user[$usertype];
}
 
function date_difference($date1,$date2="now"){
    $datediff = strtotime($date1) - strtotime($date2) ;
    return floor($datediff / (60 * 60 * 24));
}

function sendTCPmsg($message, $address, $port){
    // Get the port for the WWW service.
    //$port = CASA_PORT; //getservbyname('www', 'tcp');
    // Get the IP address for the target host.
    //$address = CASA_HOST; //gethostbyname('www.example.com');

    debug("Creating TCP/IP socket...");
    // Create a TCP/IP socket.
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        //debug("ERROR: socket_create() failed: reason: " . socket_strerror(socket_last_error()));
        return false;
    } 
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 1, 'usec' => 0));
    debug("Attempting to connect to $address on port $port .... ");
    //echo "Attempting to connect to '$address' on port '$service_port'...";
    $result = socket_connect($socket, $address, $port);
    if ($result === false) {
        //debug("ERROR: socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)));
        return false;
    } else {
        debug("Socket Connected.");
    }

    debug("Sending Message: [{$message}] ");
    // send string to server
    socket_write($socket, $message, strlen($message)) or debug("ERROR: Could not send data to server");
    
    // get server response
    $bytes ='';
    if (false !== ($bytes = socket_recv($socket, $buf, 2048, 2))) {
        debug("Read $bytes bytes from socket_recv(). Closing socket...");
    } else {
        //debug("socket_recv() failed; reason: " . socket_strerror(socket_last_error($socket)) . "\n");
        return false;
    }
       
    //$result = socket_read ($socket, 1024) or die("Could not read server response\n");
    //echo "Server  says :".$result;

    // close socket
    socket_close($socket);
    return $buf;
}

function set_sysmsg($msg="",$msgtype="info",$url="")
  { 
    $ci = get_instance();
    $msg = '<p class="text-'.$msgtype.'"> '.$msg.'</p>';
    $ci->session->set_flashdata('sys_msg',$msg);
    // if ($msgtype != ""){
    //     $ci->session->set_flashdata('msg_type',$msgtype);
    //     $ci->session->set_flashdata('url',$url);
    // }
  }

  function now()
  {
    //MSSQL accepts YYYYMMDD format
       return date('Ymd H:i:s');
  }

  function conv_date($date,$format='m d, Y')
  {
    if($date="")return $date;
    $new_date = date($format,strtotime($date));
    return $new_date;
  }

function pwComplexity($pwd){
/*Regex Pattern Description
'
'(                  #   Start of group
'  (?=.*\d)         #   must contain one digit from 0-9
'  (?=.*[a-z])      #   must contain one lowercase characters
'  (?=.*[A-Z])      #   must contain one uppercase characters
'  (?=.*[\W])       #   must contain one special symbol
'  .                #   match anything with previous condition checking
'  {6,20}           #   length at least 6 characters and maximum of 20
')                  #   End of group*/

   return preg_match("((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W]).{" & MIN_PWD_LEN & "})", $pwd);
}

function hasAccess($accepted_roles){
   // debug( showAccess($accepted_roles) );
    if(showAccess($accepted_roles) === false)
    {
        redirect('retail/restricted');
    }
    
}

function showAccess($accepted_roles)
{
    $ci = get_instance();
    if ($ci->session->userdata('group_id') == 1) {
        return true;
    }
    $e=$ci->db->query("SELECT PRIV_CODE FROM LU_PRIV WHERE PRIV_CODE='$accepted_roles'")->row();

    //debug ($e);
    $accepted_roles = $e->PRIV_CODE;
    //echo $accepted_roles;
    $tasks = $ci->db->where('GROUP_CODE',$ci->session->userdata('group_id'))->get('MT_GROUP_PRIV')->result_array();
     // $tasks = $ci->db->where('ID_USERS',$ci->session->userdata('uid'))->get('LU_USER_PRIV')->result_array();
    // debug($tasks);die();
    //debug($accepted_roles);die();
    if(is_array($accepted_roles))
    {
        // foreach ($ci->session->userdata('task_codes') as $tc) 
        foreach ($tasks as $key => $tc) 
        {
            if(in_array($tc['PRIV_CODE'], $accepted_roles))
            {   
               return true;
               break;
            }
        }
        return false;
    }else{
        foreach ($tasks as $key => $tc) 
        {

            if($accepted_roles == $tc['PRIV_CODE']){

                return true;
                break;
            }
        }
        return false;

                
    }
}




function parseData($data)
{   
    // debug(explode('|', $data));
    $string = "";
    foreach (explode('|', $data) as $a)
    {
        $arr = (explode(',', $a));
        // var_dump($arr);
     $string .= $arr[0]." &raquo; ".$arr[1].'<br/>';
    }
    if($string != ""){
        return $string;
    }
    return $data;
    // return $string;
}


function prep_insert_val($sql){
    
    $result = '';
    if (strpos($sql, '~')){
      
        $sqlarr = explode("~", $sql);

        for($i=0; $i < count($sqlarr); $i++){
          $result .= "('" . str_replace("|", "','",$sqlarr[$i]) . "')" . ($i == count($sqlarr) - 1 ? "" : ",");
        }

    }else{
     
        $result =  "('" . str_replace("|", "','", $sql) . "')";

    }

    return $result;


}

function cntFields($message){
   return substr_count($message, '|') + substr_count($message, '~') + substr_count($message, '^') + 1;
}
