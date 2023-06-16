<?php
    $rawpage = getURLCached("https://kolpingbenin.org/les-dioceses/");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage); 
    $links = $doc->getElementsByTagName("a");
    $locals = []; 
    foreach ($links as $link){
        $href = $link->getAttribute("href"); 
        if (preg_match("~/dioces(e)?-([^/]+)(/|$)~", $href, $matches)){
            $local = [
                "name" => "",
                "url" => $href
            ];
            $name = $matches[2];
            $name = preg_replace("~^de-~", "", $name); 
            $local["name"] = ucfirst(str_replace("-", " ", $name));

            $geocoding = getGeocodingByQuery($local["name"] . ", Benin");
            if ($geocoding){
                $local["geo"] = $geocoding;
            }
            $locals[] = $local; 
        }
    }
   
    return $locals; 
?>