<?php
    $rawpage = getURLCached("https://www.kolping.ch/gemeinschaft/");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage); 
    $xpath = new DOMXPath($doc);
    $kflinks = $xpath->query('//div[contains(@class,"liste-kolpingsfamilien")]/div/a');
    $locallist = []; 
    foreach ($kflinks as $kflink){
        logtxt($kflink->textContent);
        $local = array(
            "name" => trim($kflink->textContent),
            "url" => $kflink->getAttribute("href"),
            "contact" => [],
            "links" => [],
            "news" => [],
        );
        $local["links"][] = array(
            "type" => "website",
            "url" => $kflink->getAttribute("href")
        );

        $geocoding = getGeocodingByQuery($local["name"].", CH");
        if ($geocoding){
            $local["geo"] = $geocoding;
        }

        $locallist[] = $local; 
    }

    
    return $locallist; 
?>