<?php
    include __DIR__.'/../../config.php'; 
    include __DIR__.'/common.php';

    $list = [
        "meta" => [
            "lastupdated" => date("Y-m-d H:i:s")
        ],
        "list" => [],
        "kolpinghaeuser" => json_decode(file_get_contents(__DIR__.'/kolpinghaeuser.json'), true),
        "workcamps" => []
    ];

    function addListByPattern($pattern, $type){
        global $list, $_CONFIG; 

        $sources = scandir(__DIR__.'/sources'); 
        foreach ($sources as $source){
            if (preg_match($pattern, $source, $matches)){
                // if ($matches[1] != "za") continue; 

                $this_list = include __DIR__.'/sources/'.$source; 
                if (!isset($list[$type])) $list[$type] = array(); 
                $list[$type] = array_merge($list[$type], $this_list);
            }
        }
    }

    addListByPattern("~^kolpingsfamilien\.([a-z0-9]+)\.php$~i", "list");
    addListByPattern("~^(workcamps)\.php$~i", "workcamps");
    addListByPattern("~^kolpinghaeuser\.([a-z0-9]+)\.php$~i", "kolpinghotels");
    addListByPattern("~^pois\.([a-z0-9]+)\.php$~i", "pois");

    file_put_contents(__DIR__.'/locallist.json', json_encode($list, JSON_PRETTY_PRINT)); 

?>