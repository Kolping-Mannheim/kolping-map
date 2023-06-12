<?php
    include __DIR__.'/../../config.php'; 

    $raw = file_get_contents("https://www.kolpinghaeuser.de/vereinshaeuser");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($raw); 

    $xpath = new DOMXPath($doc);
    $infoboxmaps = $xpath->query('//div[contains(@id,"infobox-maps")]')->item(0);
    $osmdatas = $xpath->query('div[contains(@class,"openstreetmap-data")]', $infoboxmaps);
    $kolpinghaeuser = []; 
    foreach ($osmdatas as $osmdata){
        $hiddeninputs = $xpath->query('input', $osmdata);
        $data = [];
        foreach ($hiddeninputs as $hiddeninput){
            $data[str_replace("openstreetmap-","", $hiddeninput->getAttribute("class"))] = $hiddeninput->getAttribute("value");
        }

        $haus = [
            "name" => $data["label"],
            "geo" => [
                "lat" => doubleval($data["latitude"]),
                "lon" => doubleval($data["longitude"]),
            ],
            "url" => $data["website_link"],
            "_raw" => $data
        ];
        if (isset($data["mapinfo"])){
            $data["mapinfo"] = preg_replace("~<br( /)?>~i", "||", $data["mapinfo"]);
            $data["mapinfo"] = explode("||", $data["mapinfo"]);
            
            if (sizeof($data["mapinfo"]) == 3){
                $haus["address"] = $data["mapinfo"][0];
                if (preg_match("~([0-9]{5}) (.*)$~i", $data["mapinfo"][1], $matches)){
                    $haus["zip"] = $matches[1];
                    $haus["town"] = $matches[2];
                }
            }
        }

        $kolpinghaeuser[] = $haus; 
    }

    file_put_contents(__DIR__.'/kolpinghaeuser.json', json_encode($kolpinghaeuser, JSON_PRETTY_PRINT));
        
?>