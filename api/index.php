<?php
    $res = json_decode(file_get_contents(__DIR__.'/data/locallist.json'), true); 
    
    http_response_code($res["code"]);
    header('Content-Type: application/json');
	header('Pragma: no-cache');
	header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
	header('Cache-Control: no-cache, no-store, must-revalidate');

    echo json_encode($res, JSON_PRETTY_PRINT); 
?>