<?php
    $rawpage = getURLCached("https://www.kolping.cz/kolpingovy-rodiny/");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage); 
    $links = $doc->getElementsByTagName("a");
    $locals = []; 
    foreach ($links as $link){
        $href = $link->getAttribute("href"); 
        if (preg_match("~/rodiny/([^/]+)(/|$)~", $href, $matches)){
            $name = trim($link->textContent);
            $name = preg_replace("~^[0-9]+\. ~", "", $name); 
            $local = [
                "name" => $name,
                "url" => $href
            ];
            
            $geocoding = getGeocodingByQuery(str_replace("KR ", "", $local["name"]) . ", CZ");
            if ($geocoding){
                $local["geo"] = $geocoding;
            }
            $locals[] = $local; 
        }
    }
   
    return $locals; 
?>