<?php
    $rawpage = getURLCached("https://kolping.pl/rodziny-kolpinga/");

    $locallist = []; 
    if (preg_match('~var loc = ({"lokalizacje".+});~i', $rawpage, $matches)){
        $data = json_decode($matches[1], true);
        
        if ($data){
            $locations = json_decode($data["lokalizacje"], true); 
            foreach ($locations as $location){
                $location["places"] = array($location);
                if (isset($location["miejsca"])) $location["places"] = $location["miejsca"];

                foreach ($location["places"] as $place){
                    $local = array(
                        "name" => trim($place["nazwa_miejsca"])
                    );
                    $geo = explode(", ", $place["wspolrzedne"]);
                    if (sizeof($geo) == 2){
                        $local["geo"] = array(
                            "lat" => $geo[0],
                            "lon" => $geo[1]
                        );
                    }

                    $locallist[] = $local; 
                }
                
            }
        }
    }
    return $locallist; 
?>