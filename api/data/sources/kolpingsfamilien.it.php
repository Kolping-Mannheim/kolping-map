<?php
    $rawpage = getURLCached("https://www.kolping.it/");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage); 
    $xpath = new DOMXPath($doc);
    $submenus = $xpath->query('//ul[contains(@class,"sub-menu")]/li');
    $locallist = []; 
    foreach ($submenus as $submenu){
        $a = $xpath->query('a', $submenu)->item(0);
        if ($a->textContent == "Kolpingsfamilien"){
            $kflinks = $xpath->query('ul/li/a', $submenu);
            foreach ($kflinks as $kflink){
                logtxt($kflink->textContent);
                $local = array(
                    "name" => str_replace("KF ", "Kolpingsfamilie ", trim($kflink->textContent)),
                    "url" => $kflink->getAttribute("href"),
                    "contact" => [],
                    "links" => [],
                    "news" => [],
                );
                $local["links"][] = array(
                    "type" => "website",
                    "url" => $kflink->getAttribute("href")
                );

                $geocoding = getGeocodingByQuery(preg_replace("~^Kolpingsfamilie ~i", "", $local["name"]).", IT");
                if ($geocoding){
                    $local["geo"] = $geocoding;
                }

                $locallist[] = $local; 
            }
        }
    }
    
    return $locallist; 
?>