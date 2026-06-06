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

 function decrypt_subdomain($sub_domain, $panel_type){
    try {
        $panel_type = (int) $panel_type;
        if($panel_type === 1){
            if(strlen($sub_domain) >= 14){
                return strval(intval(substr($sub_domain, 9, -1)) - 1234);
            }else{
                return $sub_domain;
            }
        }else if($panel_type === 2){
            if (preg_match('/^[um]+[0-9a-f]+$/i', $sub_domain)) {
                return strval(hexdec(ltrim($sub_domain, 'um')));
            }
            if (ctype_digit($sub_domain)) {
                return $sub_domain;
            }
            return 'stranger';
        }else{
            return $sub_domain;
        }
    } catch (\Throwable $th) {
        return "stranger";
    }
 }

register_shutdown_function('shutdown');

$host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']); // aaa.xxx.com:8080
$port = $_SERVER['SERVER_PORT'];
$server_name = $_SERVER['SERVER_NAME'];     // *.xxx.com
$redirect_url = '';
$sub_domain = explode('.', $host)[0];
$master_domain = explode('.', $host, 2)[1];

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
    $panel_type = intval($client_data['panel_type']);
    $max_id = intval($client_data['max_uid']);
    $min_id = intval($client_data['min_uid']);
    $uid = strtolower(decrypt_subdomain($sub_domain, $panel_type));
    $mask_domains = apcu_fetch('mask_domains');
    $normal_lines = $client_data['normal_lines'];

    if($panel_type === 2){
        $pair_domain = $client_data['pairs'][$sub_domain];
    }else{
        $pair_domain = $client_data['pairs'][$uid];
    }
    
    // country code check, and choose the proper cf domain
    if(is_empty($pair_domain)){
        if(array_key_exists($country_code, $mask_domains)){
            $available_domains = $mask_domains[$country_code];
            $count = floatval(count($available_domains)) ;
            $index = (int) floor(($uid - $min_id) / (($max_id - $min_id + 1) / $count));
            $index = max(0, min($count - 1, $index));
            $pair_domain = $available_domains[$index];
        }else if(array_key_exists('fallback', $mask_domains)){
            $pair_domain = $mask_domains['fallback'][array_rand($mask_domains['fallback'])];
        }else{
            $redirect_url = 'https://www.google.com.3';
            exit;
        }
    }

    // ensure if we keep node count as 3 or 4 on both modes
    if($panel_type === 2){
        if(!is_empty($normal_lines) && in_array($sub_domain, $normal_lines)){
            $redirect_url = 'http://' . ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . ":" . $port . $_SERVER['REQUEST_URI'];
        }else{
            $redirect_url = 'http://' . $uid . '.' .  ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . ":" . $port . $_SERVER['REQUEST_URI'];
        }
    }else{
        if(!is_empty($normal_lines) && in_array($uid, $normal_lines)){
            $redirect_url = 'http://' . ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . ":" . $port . $_SERVER['REQUEST_URI'];
        }else{
            $redirect_url = 'http://' . $uid . '.' .  ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . ":" . $port . $_SERVER['REQUEST_URI'];
        }
        
    }
    //$redirect_url = 'http://' . ip2long($client_data['lb_domains'][$master_domain]) . "." . $pair_domain . $_SERVER['REQUEST_URI'];
}else{
    $redirect_url = 'https://www.google.com.4';
    exit;
}
