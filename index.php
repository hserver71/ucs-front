<?php
$country_code = strtolower($_SERVER['GEOIP_COUNTRY_CODE'] ?? "fallback");
function shutdown(){
    global $redirect_url;
    header('Content-Type: text/html; charset=UTF-8');
    header('Access-Control-Allow-Origin: *'); 
    if( isset( $_SERVER['SERVER_ADDR'] ) ){  
        header('special_header: '.$_SERVER['SERVER_ADDR']); 
    }

    if(!is_empty($redirect_url)){
        $redirect_url = utf8_decode($redirect_url);
        header("Location: " . $redirect_url, true, 302);
    }else{
        header("Location: https://www.google.com.1", true, 302);
    }
}

 function decrypt_subdomain($sub_domain){
    try {
        if(strlen($sub_domain) >= 14){
            return strval(intval(substr($sub_domain, 9, -1)) - 1234);
        }else{
            return $sub_domain;
        }
    } catch (\Throwable $th) {
        return "stranger";
    }
 }

register_shutdown_function('shutdown');

$host = $_SERVER['HTTP_HOST'];              // aaa.xxx.com
$host = preg_replace('/:\d+$/', '', $host);
$server_name = $_SERVER['SERVER_NAME'];     // *.xxx.com
$redirect_url = '';
$sub_domain = explode('.', $host)[0];
$master_domain = explode('.', $host, 2)[1];
str_replace(":80", "", $master_domain);

function is_empty($value){
    if (is_null($value)) return true;
    if (is_string($value)) return trim($value) === '';
    if (is_array($value)) return count($value) === 0;
    if (is_object($value)) return count((array)$value) === 0;
    if (is_bool($value)) return $value === false;
    if (is_int($value) || is_float($value)) return $value == 0;
    return empty($value);
}

if(!is_empty(apcu_fetch('data')) && !is_empty(apcu_fetch('meta_data'))){
    $meta_data = apcu_fetch('meta_data');
    
    if(array_key_exists($master_domain, $meta_data)){
        $client_id = $meta_data[$master_domain];
    }else{
        
        $redirect_url = 'https://www.google.com'.$master_domain;
        exit;
    }

    $client_data = apcu_fetch('data')[$client_id];
    $line_username = decrypt_subdomain($sub_domain);
    $mask_domains = apcu_fetch('mask_domains');


    $pair_domain = $client_data['pairs'][$line_username];
    if(is_empty($pair_domain)){
        if(array_key_exists($country_code, $mask_domains)){
            $pair_domain = $mask_domains[$country_code][array_rand($mask_domains[$country_code])];
        }else if(array_key_exists('fallback', $mask_domains)){
            $pair_domain = $mask_domains['fallback'][array_rand($mask_domains['fallback'])];
        }else{
            $redirect_url = 'https://www.google.com.3';
            exit;
        }
    }
    // xxxxxxxxx1235x.ip2long(lb_ip).cf1.com
    $redirect_url = 'http://' . $sub_domain . '.' .  ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . $_SERVER['REQUEST_URI'];
    //$redirect_url = 'http://' . ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . $_SERVER['REQUEST_URI'];
}else{
    $redirect_url = 'https://www.google.com.4';
    exit;
}
