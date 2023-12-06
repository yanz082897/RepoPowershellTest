<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CI URL Helpers [Override]
 * 
 * @author norlan
 * @version 1.0.0
 * @copyright (c) 2020, Channel Solutions Inc.
 */
if (!function_exists('getUserIpAddress')) {

    /**
     * This method will return client ip address
     * 
     * @author norlan
     * 
     * @return string IP Address
     */
    function getUserIpAddress(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

}


if (!function_exists('base_url_query')) {

    /**
     * This method will return url with query
     * 
     * @author norlan
     * 
     * @return string url
     */
    function base_url_query(string $uri = ''): string {
        return base_url($uri) . '?' . $_SERVER['QUERY_STRING'];
    }

}

if (!function_exists('show_unauthorized')) {

    /**
     * This method will response Unauthorized 401 page
     * 
     * @author norlan
     * 
     * @param type $message
     * @param type $heading
     */
    function show_unauthorized($message = 'Unautorized', $heading = 'Unautorized') {
        $_error = & load_class('Exceptions', 'core');
        echo $_error->show_error($heading, $message, 'error_401', 401);
        exit(1);
    }

}

if (!function_exists('show_forbidden')) {

    /**
     * This method will response Forbidden 403 page
     * 
     * @author norlan
     * 
     * @param type $message
     * @param type $heading
     */
    function show_forbidden($message = 'Forbidden', $heading = 'Forbidden') {
        $_error = & load_class('Exceptions', 'core');
        echo $_error->show_error($heading, $message, 'error_403', 403);
        exit(1);
    }

}

if (!function_exists('grantPermission')) {

    /**
     * This method will return GrantPermission annotation rules
     * 
     * @author norlan
     * 
     * @return \Annotation\GrantPermission|null
     */
    function grantPermission(): ?\GrantPermission {
        return isset(get_instance()->grantpermission) ? get_instance()->grantpermission : NULL;
    }

}

if (!function_exists('public_url')) {

    function public_url(): ?string {
        $CI = & get_instance();
        return $CI->config->item('public_url') . '/';
    }

}


if (!function_exists('hasPermission')){
    
    function hasPermission($priv, $user_priviliges=''){
        $CI =& get_instance();
        if($user_priviliges === ''){$user_priviliges = $CI->session->userdata('userprivs');}
        $priv_array = json_decode($user_priviliges, true);//json string to array

        if(array_key_exists($priv,$priv_array)){//check priv exists
            //check priv value
            return true;
            //if ( $priv_array[$priv] ) {return true;}
        }

        return false;
    }
}

function debug($data,$die=FALSE){
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if($die){
        die();
    }
}

function searchFilter($searchOptions){//input array
        $condition = "";
        foreach ($searchOptions as $option => $value) {
            if(!empty($searchOptions[$option])){
                
                $addAnd = (!empty($condition)) ? ' AND ' : '';
                
                if (strpos($searchOptions[$option], '(' ) > 0){
                     $condition .= $addAnd.$searchOptions[$option];                
                }elseif (strpos($searchOptions[$option], '%' ) === false){
                    $condition .= $addAnd."$option = '".$searchOptions[$option]."'";
                }else{
                    $condition .= $addAnd.$searchOptions[$option];
                }
            }
        }
        return   $condition = (empty($condition)) ? "" : "WHERE ".$condition;
}

if (!function_exists('format_preview_query')) { //FOR APPROVAL PREVIEW

    function format_preview_query($field = NULL, $records = NULL)
    {
        if ( ! $field || ! $records || ! is_array($records)) return FALSE;

        $ci =& get_instance();

        $sql = NULL;
        $count = 0;

        foreach ($records as $data => $val)
        {
            if ($count++) $sql .= ' OR';

            $data = $ci->security->xss_clean($data);
            $data = $ci->db->escape($data);

            $sql .= " {$field} = {$data}";
        }

        return $sql;
    }
}

if (!function_exists('iencode')) { //FOR ENCRYPTION

    function iencode( $string ) 
    {
        $secret_key     = SALT;
        $secret_iv      = ENC_IV;
        $output         = false;
        $encrypt_method = "AES-256-CBC";

        $key    = hash( 'sha256', $secret_key );
        $iv     = substr( hash( 'sha256', $secret_iv ), 0, 16 );
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        
        return $output;
    }
}

if (!function_exists('idecode')) { //FOR DECRYPTION
    
    function idecode( $string ) 
    {
        $secret_key     = SALT;
        $secret_iv      = ENC_IV;
        $output         = false;
        $encrypt_method = "AES-256-CBC";

        $key    = hash( 'sha256', $secret_key );
        $iv     = substr( hash( 'sha256', $secret_iv ), 0, 16 );
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        
        return $output;
    }
}
