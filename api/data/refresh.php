<?php
    include __DIR__.'/../../config.php'; 
    include __DIR__.'/common.php';

    $list = [
        "meta" => [
            "lastupdated" => date("Y-m-d H:i:s")
        ],
        "list" => [],
        "kolpinghaeuser" => json_decode(file_get_contents(__DIR__.'/kolpinghaeuser.json'), true)
    ];

    $sources = scandir(__DIR__.'/sources'); 
    foreach ($sources as $source){
        if (preg_match("~^kolpingsfamilien\.([a-z0-9]+)\.php$~i", $source, $matches)){
            // if ($matches[1] != "pl") continue; 

            $this_list = include __DIR__.'/sources/'.$source; 
            $list["list"] = array_merge($list["list"], $this_list);
        }
    }

    file_put_contents(__DIR__.'/locallist.json', json_encode($list, JSON_PRETTY_PRINT)); 
?>