<?php

$action = $_REQUEST['action'];
if($action == "start"){
    // kill the old mem.php process except this process
    $this_pid = getmypid();
    exec("ps -eo pid,cmd | grep '[m]em.php' | awk '{print $1}'", $pids);
    foreach ($pids as $pid) {
        if ((int)$pid !== $this_pid) {
            posix_kill((int)$pid, 9);
        }
    }

    // fetch the data
    $backend_url = "http://unblocking-central-system.com/api/getUCSBackData";
    try {
        $raw = file_get_contents($backend_url);
        if ($raw === false) return;
        $data = json_decode($raw, true);
        if ($data === null) return;
        apcu_store('last_updated', time());
        apcu_delete('data');
        apcu_store('data', $data);
    } catch (\Throwable $th) {
        echo "Error: " . $th;
    }
}else if($action == "show"){
    $updated_time = apcu_fetch('last_updated');
    echo "Updated time: " . date('Y-m-d H:i:s', $updated_time) . "\n";
    echo "Data: " . json_encode(apcu_fetch('data')) . "\n";
}else if($action == "clear"){
    apcu_delete('data');
    apcu_delete('last_updated');
    echo "Data cleared";
}else{
    echo "Invalid action";
}