<?php
    $pois = [
        [
            "name" => "Minoritenkirche",
            "description" => "",
            "address" => "Kolpingpl. 1, 50667 Köln"
        ],[
            "name" => "Geburtshaus / Kolping-Museum Kerpen",
            "description" => "",
            "address" => "Obermühle 21, 50171 Kerpen",
            "tel" => "+49 2237 3728"
        ]
    ];

    foreach ($pois as $p => $poi){
        if (isset($poi["address"]) && !isset($poi["geo"])){
            $geocoding = getGeocodingByQuery($poi["address"] . ", DE");
            if ($geocoding){
                $local["geo"] = $geocoding;
            }
        }
    }

    return $pois; 
?>