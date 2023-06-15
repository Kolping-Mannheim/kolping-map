<?php
    function getURLCached ($url, $noncache = false){
        logtxt($url);
        $cache_fn = __DIR__.'/cache/'.md5($url).'.html';

        $rawhtml = false; 
        if (file_exists($cache_fn) && filemtime($cache_fn) > time()-60*60*24*7 && $noncache == false){
            $rawhtml = file_get_contents($cache_fn); 
            logtxt("using cache");
        } else {
            logtxt("loading page...");
            $ch = curl_init(); 
        
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "User-Agent: curl @ map.kolping.community",
                "Referrer: map.kolping.community"
            ));
            
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $rawhtml = curl_exec($ch); 

            curl_close($ch); 

            if ($rawhtml){
                file_put_contents($cache_fn, $rawhtml); 
            }
        }
        return $rawhtml;
    }

    function getGeocodingByQuery($q){
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($q) . "&format=jsonv2";

        $geo = false; 
        
        $geocoding = getURLCached($url);
        if ($geocoding){
            $geocoding = json_decode($geocoding, true);
            
            if (isset($geocoding[0])){
                $geo = array("lat" => doubleval($geocoding[0]["lat"]), "lon" => doubleval($geocoding[0]["lon"]));
            }
        }
        return $geo; 
    }
    
?>