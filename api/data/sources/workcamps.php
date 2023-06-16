<?php
    $rawpage = getURLCached("https://www.kolping-jgd.de/workcamps");

    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage);
    $xpath = new DOMXPath($doc);
    $cards = $xpath->query('//div[contains(@class,"project-card")]');
    $wclist = []; 
    foreach ($cards as $card){
        $camp = [
            "name" => $card->getAttribute("data-location").", ".$card->getAttribute("data-country"),
            "geo" => [
                "lat" => $card->getAttribute("data-lat"),
                "lon" => $card->getAttribute("data-long"),
            ]
        ];

        $link = $xpath->query('div/h2/a[contains(@class,"js-project-link")]', $card)->item(0);

        if ($link){
            $camp["url"] = rel2abs($link->getAttribute("href"), "https://www.kolping-jgd.de/workcamps");
            $camp["name"] = trim($link->textContent);
            $camp["subname"] = $card->getAttribute("data-location").", ".$card->getAttribute("data-country");
        }
        $camp["description"] = trim($xpath->query('div/p[contains(@class,"project-card-teaser")]', $card)->item(0)->textContent);

        $img = $xpath->query('div/a/img[contains(@class,"project-card-img")]', $card)->item(0);
        if ($img && $img->getAttribute("src")){
            $imgsrc = rel2abs($img->getAttribute("src"), "https://www.kolping-jgd.de/workcamps");
            $img = getURLCached($imgsrc);
            if ($img){
                $camp["image"] = 'data:image/jpeg;base64,' . base64_encode($img);
            }
        }

        var_dump($camp);

        $wclist[] = $camp; 
    }
    return $wclist; 

    die(); 


?>