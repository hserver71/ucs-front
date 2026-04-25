<?php

// kill the old mem.php process except this process
$action = $_REQUEST['action'];
if($action == "start"){
    $this_pid = getmypid();
    exec("ps -eo pid,cmd | grep '[m]em.php' | awk '{print $1}'", $pids);
    foreach ($pids as $pid) {
        if ((int)$pid !== $this_pid) {
            posix_kill((int)$pid, 9);
        }
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
}else if($action == "show"){
    echo json_encode(apcu_fetch('data'));
}else if($action == "clear"){
    apcu_delete('data');
    apcu_delete('last_updated');
    echo "Data cleared";
}else{
    echo "Invalid action";
}