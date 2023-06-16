<?php
    $rawpage = getURLCached("https://www.kolping.net/ueber-uns/kolpinghauser-weltweit/");

    $locallist = []; 
    $doc = new DOMDocument(); 
    @$doc->loadHTML($rawpage);
    $xpath = new DOMXPath($doc);
    $wpbs = $xpath->query('//div[contains(@class,"wpb_wrapper")]');
    foreach ($wpbs as $wpb){
        // 1 wpb = 1 hotel
        $hotel = [
            "links" => []
        ];
        $div = $xpath->query('div[contains(@class,"mpc-icon-column")]', $wpb)->item(0);
        if ($xpath->query('div/div/i[contains(@class,"fa-map-marker")]')){
            $p = $xpath->query('div/div/div/p', $div); 
            if ($p->length != 1) continue; 
            $p = $p->item(0); 
            $hotel["name"] = $xpath->query('strong', $p)->item(0)->textContent; 
            $hotel["address"] = str_replace("\n", ", ", trim(str_replace($hotel["name"], "", $p->textContent)));
        }

        $links = $xpath->query('a', $wpb);
        foreach ($links as $link){
            $text = trim($xpath->query('div/div/div/p', $link)->item(0)->textContent);
            if ($xpath->query('div/div/i[contains(@class, "fa-phone")]', $link)->length > 0 || $xpath->query('div/div/i[contains(@class, "fa-mobile-phone")]', $link)->length > 0){
                $hotel["tel"] = $text; 
            } elseif ($xpath->query('div/div/i[contains(@class, "fa-envelope")]', $link)->length > 0){
                $hotel["email"] = $text; 
            } elseif ($xpath->query('div/div/i[contains(@class, "fa-link")]', $link)->length > 0){
                $hotel["url"] = $text; 
            } elseif ($xpath->query('div/div/i[contains(@class, "fa-globe")]', $link)->length > 0){
                $hotel["links"][] = array("type" => "googlemaps", "url" => $link->getAttribute("href")); 
            }
        }

        if ($hotel["address"]){
            $geo = getGeocodingByQuery($hotel["address"]);
            if ($geo){
                $hotel["geo"] = $geo; 
            }
        }

        if (!isset($hotel["geo"])){
            foreach ($hotel["links"] as $link){
                if ($link["type"] == "googlemaps"){
                    logtxt("Fallback: GoogleMaps-Link");
                    $ch = curl_init(); 
        
                    curl_setopt($ch, CURLOPT_URL, $link["url"]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "User-Agent: curl @ map.kolping.community",
                        "Referrer: map.kolping.community"
                    ));
                    
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    $rawhtml = curl_exec($ch); 
                    $info = curl_getinfo($ch); 
        
                    curl_close($ch); 

                    $finalURL = $info["url"];
                    logtxt($finalURL);
                    if ($finalURL && preg_match("~/@([-0-9\.]+),([-0-9\.]+),[-0-9\.]+z/~i", $finalURL, $matches)){
                        $hotel["geo"] = array("lat" => $matches[1], "lon" => $matches[2]);
                    }
                }
            }
        }

        $locallist[] = $hotel; 
    }
    
    return $locallist; 
?>