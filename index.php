<?php

function getUCSData($host){
    $data = file_get_contents("http://172.110.220.100:8000/api/getUCSData");
    $data = json_decode($data, true);
    return $data;
}


echo "UCS Front Distrobutor!";
$host = $_SERVER['HTTP_HOST'];
echo "Host: $host";

if(time() % 30 == 0){
    echo "Getting UCS Data...";
    $data = getUCSData($host);
    print_r($data);
}




