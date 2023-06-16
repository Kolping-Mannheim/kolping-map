<?php
    $rawpage = getURLCached("https://kolpingsa.co.za/kolping-families/");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage); 
    $xpath = new DOMXPath($doc);
    $divs = $xpath->query('//div[contains(@class, "et_pb_blurb_container")]');
    $locals = []; 
    foreach ($divs as $div){
        $h4 = $div->getElementsByTagName("h4")->item(0)->textContent; 
        $descdiv = $xpath->query('div[contains(@class, "et_pb_blurb_description")]', $div); 
        if ($descdiv->length == 0) continue; 

        $links = $descdiv->item(0)->getElementsByTagName("a"); 
        foreach ($links as $link){
            logtxt($link->textContent);
            $local = [
                "name" => $link->textContent,
                "url" => $link->getAttribute("href"),
                "_kolping_region" => $h4
            ];
            $geocoding = getGeocodingByQuery($local["name"] . ", South Africa");
            if ($geocoding){
                $local["geo"] = $geocoding;
            }
            $locals[] = $local; 
        }
    }
    return $locals; 
?>