<?php
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
    $backend_url = "http://172.110.220.100:8000/api/getUCSData";
    try {
        $last_updated = apcu_fetch('last_updated');
        if($last_updated == null || time() > $last_updated + 30){
            $data = file_get_contents($backend_url);
            $data = json_decode($data, true);
            apcu_store('last_updated', time());
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
}

$allocated_lines = [];
if(!is_empty($data)){
    $allocated_lines = $data[0]['allocated_lines'];
}
$cf_domain = array_values(array_filter($allocated_lines, function($item) use ($sub_domain){
    return $item['linename']  == $sub_domain;
}));

if(is_empty($data)){
    $redirect_url = 'https://www.google.com';
}else if(!is_empty($cf_domain)) {
    $redirect_url = "http://" . ip2long($data[0]['ip']) . '.' . $cf_domain[0]['domain'] . $_SERVER['REQUEST_URI'];
}else{
    $redirect_url = "http://" . $data[0]['ip'] . '.' . $_SERVER['REQUEST_URI'];
}


header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *'); 
if( isset( $_SERVER['SERVER_ADDR'] ) ){
    header('special_header: '.$_SERVER['SERVER_ADDR']); 
}

$redirect_url = utf8_decode($redirect_url);
header("Location: $redirect_url", true, 302);
