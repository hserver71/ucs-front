<?php

function shutdown(){
    global $redirect_url;
    header('Content-Type: text/html; charset=UTF-8');
    header('Access-Control-Allow-Origin: *'); 
    if( isset( $_SERVER['SERVER_ADDR'] ) ){
        header('special_header: '.$_SERVER['SERVER_ADDR']); 
    }

    if(!is_empty($redirect_url)){
        // $redirect_url = mb_convert_encoding($redirect_url, 'ISO-8859-1', 'UTF-8');
        header("Location: $redirect_url", true, 302);
    }else{
        header("Location: https://www.google.com", true, 302);
    }
}

 function decrypt_subdomain($sub_domain, $panel_type){
    try {
        if($panel_type == 0){ // means no decrypt
            return $sub_domain;
        }else if($panel_type == 1){ // means decrypt for the NXT panel
            if(strlen($sub_domain) >= 14){
                return strval(intval(substr($sub_domain, 9, -1)) - 1234);
            }else{
                return $sub_domain;
            }
        }
    } catch (\Throwable $th) {
        return $sub_domain;
    }
 }

register_shutdown_function('shutdown');

$host = $_SERVER['HTTP_HOST'];              // aaa.xxx.com
$server_name = $_SERVER['SERVER_NAME'];     // *.xxx.com
$redirect_url = '';
$sub_domain = explode('.', $host)[0];

function is_empty($value){
    if (is_null($value)) return true;
    if (is_string($value)) return trim($value) === '';
    if (is_array($value)) return count($value) === 0;
    if (is_object($value)) return count((array)$value) === 0;
    if (is_bool($value)) return $value === false;
    if (is_int($value) || is_float($value)) return $value == 0;
    return empty($value);
}

function getUCSData(){
    $backend_url = "http://172.110.220.100:8000/api/getUCSBackData";
    try {
        $last_updated = apcu_fetch('last_updated');
        if($last_updated == null || time() > $last_updated + 30){
            $raw = file_get_contents($backend_url);
            if ($raw === false) return;
            $data = json_decode($raw, true);
            if ($data === null) return;
            apcu_store('last_updated', time());
            apcu_delete('data');
            apcu_store('data', $data);
        }
    } catch (\Throwable $th) {
        echo "Error: " . $th;
    }
}

getUCSData();

if(!is_empty(apcu_fetch('data')) && !is_empty(apcu_fetch('data')['data']) ){
    $data = array_values(array_filter(apcu_fetch('data')['data'], function($item) use ($server_name) {
        return strpos($item['domain'], substr($server_name, 2)) !== false;
    }));
}else{
    $redirect_url = 'https://www.google.com';
    exit;
}

$allocated_lines = [];
if(!is_empty($data)){
    $allocated_lines = $data[0]['allocated_lines'];
}else{
    $redirect_url = 'https://www.google.com';
    exit;
}
$panel_type = $data[0]['panel_type'] ?? 1;
$line_id = decrypt_subdomain($sub_domain,$panel_type);
$danger_lines = $data[0]['danger_lines'] ?? [];
$blackhole_domains = $data[0]['blackhole_nodes'] ?? [];   

// redirect the request to the blackhole domain
if(in_array(strval($line_id), $danger_lines) && !is_empty($blackhole_domains)){
    $redirect_url = 'http://' . ip2long($data[0]['original_ip']) . "." . $blackhole_domains[array_rand($blackhole_domains)] . $_SERVER['REQUEST_URI'];
    exit;
}

// redirect the request to the original ip if we can't sure the line_id
if($line_id == ''){
    $redirect_url = "http://" . $data[0]['original_ip'] . $_SERVER['REQUEST_URI'];
    exit;
}


$cf_domain = array_values(array_filter($allocated_lines, function($item) use ($line_id){
    return $item['linename']  == strval($line_id);
}));

// redirect the request to the allocated cloudflare domain
if(!is_empty($cf_domain)) {
    $redirect_url = "http://" . strval(ip2long($data[0]['original_ip'])) . '.' . $cf_domain[0]['domain'] . $_SERVER['REQUEST_URI'];
}else{
    $redirect_url = "http://" . $data[0]['original_ip'] . $_SERVER['REQUEST_URI'];
}
