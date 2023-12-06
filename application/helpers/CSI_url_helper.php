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


function curl($url,$options){
    //initialization
    $curl=curl_init($url);
    //set curl options
    curl_setopt_array($curl, $options);
    //actual call
     $resp = curl_exec($curl);
    // close cURL resource, and free up system resources
    curl_close($curl);
    return $resp;
   }