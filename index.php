<?php


function shutdown(){
    global $redirect_url;
    header('Content-Type: text/html; charset=UTF-8');
    header('Access-Control-Allow-Origin: *'); 
    if( isset( $_SERVER['SERVER_ADDR'] ) ){
        header('special_header: '.$_SERVER['SERVER_ADDR']); 
    }

    if(!is_empty($redirect_url)){
        $redirect_url = mb_convert_encoding($redirect_url, 'ISO-8859-1', 'UTF-8');
        header("Location: $redirect_url", true, 302);
    }else{
        header("Location: https://www.google.com", true, 302);
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
    $backend_url = "http://172.110.220.100:8000/api/getUCSData";
    try {
        $last_updated = apcu_fetch('last_updated');
        if($last_updated == null || time() > $last_updated + 30){
            $raw = file_get_contents($backend_url);
            if ($raw === false) return;
            $data = json_decode($raw, true);
            if ($data === null) return;
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
    exit;
}

$allocated_lines = [];
if(!is_empty($data)){
    $allocated_lines = $data[0]['allocated_lines'];
    $resolve_url = strpos($data[0]['resolve_url'], 'http') !== false ? $data[0]['resolve_url']:"";
}else{
    $redirect_url = 'https://www.google.com';
    exit;
}

//https://pbrfn-xivrb5mzu7.r200.eu/dnsdecooooo/mjqkd9nj24667s => USER_ID: 3433
try {
    $line_id = file_get_contents($resolve_url.$sub_domain);
    if(!is_empty($line_id)){
        $parts = explode(':', $line_id, 2);
        $line_id = isset($parts[1]) ? trim($parts[1]) : '';
    }else{
        $redirect_url = "http://" . $data[0]['ip'] . $_SERVER['REQUEST_URI'];
        exit;
    }
} catch (\Throwable $th) {
    $redirect_url = "http://" . $data[0]['ip'] . $_SERVER['REQUEST_URI'];
    exit;
}

$cf_domain = array_values(array_filter($allocated_lines, function($item) use ($sub_domain){
    return $item['linename']  == strval($line_id);
}));


if(!is_empty($cf_domain)) {
    $redirect_url = "http://" . strval($line_id) . '.' . $cf_domain[0]['domain'] . $_SERVER['REQUEST_URI'];
}else{
    $redirect_url = "http://" . $data[0]['ip'] . $_SERVER['REQUEST_URI'];
}
