<?php
    $rawpage = getURLCached("https://www.kolping.at/kontakt-adressen/verein/kolpingsfamilie");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage); 
    $xpath = new DOMXPath($doc);
    $kflis = $xpath->query('//li[contains(@data-document-url,"kontakt-adressen/detail")]');

    $locallist = []; 
    foreach ($kflis as $kfli){
        $local = [
            "name" => $kfli->getAttribute("data-title"),
            "geo" => [
                "lat" => $kfli->getAttribute("data-lat"),
                "lon" => $kfli->getAttribute("data-lng"),
            ],
            "address" => trim($xpath->query('div/div/address', $kfli)->item(0)->textContent),
            "_kolping_region" => trim($xpath->query('div/div/small', $kfli)->item(0)->textContent),
            "url" => rel2abs($kfli->getAttribute("data-document-url"), "https://www.kolping.at/"),
            "contact" => [],
        ];

        $detailspage = getURLCached($local["url"]);
        $ddoc = new DOMDocument(); 
        @$ddoc->loadHTML($detailspage); 
        $dxpath = new DOMXPath($ddoc);

        $divspaces = $dxpath->query('//div[contains(@class,"spaces")]');
        foreach ($divspaces as $divspace){
            $address = $dxpath->query('address', $divspace);
            if ($address->length != 1) continue; 
            $address = $address->item(0); 

            $email = $dxpath->query('div/span[contains(@class,"email")]/a', $address);
            if ($email->length == 1){
                $local["contact"]["email"] = preg_replace("~^mailto:~", "", $email->item(0)->getAttribute("href"));
            }

            $tel = $dxpath->query('div/span[contains(@class,"tel")]', $address);
            if ($tel->length >= 1){
                $local["contact"]["tel"] = trim($tel->item(0)->textContent);
            }

            $fax = $dxpath->query('div/span[contains(@class,"fax")]', $address);
            if ($fax->length >= 1){
                $local["contact"]["fax"] = trim($fax->item(0)->textContent);
            }

            $mobile = $dxpath->query('div/span[contains(@class,"mobile")]', $address);
            if ($mobile->length >= 1){
                $local["contact"]["mobile"] = trim($mobile->item(0)->textContent);
            }
            
            $website = $dxpath->query('div/a[contains(@class, "btn-primary")]', $divspace);
            if ($website->length >= 1){
                $local["url"] = $website->item(0)->getAttribute("href"); 
            }
        }

        $locallist[] = $local;
    }
    return $locallist; 
?>