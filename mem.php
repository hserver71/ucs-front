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
    $backend_url = "http://unblocking-central-system.com/api/getAllocatedPairs";
    try {
        $raw = file_get_contents($backend_url);
        if ($raw === false) return;
        $data = json_decode($raw, true);
        if ($data === null) return;
        apcu_store('last_updated', time());
        apcu_delete('data');
        apcu_store('data', $data['pairs']);
        apcu_store('mask_domains', $data['mask_domains']);
        apcu_store('dedicated_domains', $data['dedicated_domains'] ?? []);

        $meta_data = [];

        foreach($data['pairs'] as $client_id => $client_data){
            foreach(array_keys($client_data['lb_domains']) as $domain){
                $meta_data[$domain] = $client_id;
            }
        }
        if(count($meta_data) > 0){
            apcu_delete('meta_data');
            apcu_store('meta_data', $meta_data);
        }
    } catch (\Throwable $th) {
        echo "Error: " . $th;
    }
}else if($action == "show"){
    $updated_time = apcu_fetch('last_updated');
    echo "Updated time: " . date('Y-m-d H:i:s', $updated_time) . "\n\n";
    echo "Meta data: " . json_encode(apcu_fetch('meta_data')) . "\n\n";
    echo "Data: " . json_encode(apcu_fetch('data')) . "\n";
    echo "Mask domains: " . json_encode(apcu_fetch('mask_domains')) . "\n";
    echo "Dedicated domains: " . json_encode(apcu_fetch('dedicated_domains')) . "\n";
}else if($action == "clear"){
    apcu_delete('data');
    apcu_delete('meta_data');
    apcu_delete('last_updated');
    apcu_delete('mask_domains');
    apcu_delete('dedicated_domains');
    echo "Cache cleared successfully!";
}else{
    echo "Invalid action";
}