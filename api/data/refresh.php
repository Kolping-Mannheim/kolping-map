<?php
    include __DIR__.'/../../config.php'; 

    $data = file_get_contents($_CONFIG["url_kolpinglocally"]);
    $data = json_decode($data, true); 
    // var_dump($data); 

    function getMicrositeData ($url){
        $url .= "impressum/";
        logtxt($url);
        $cache_fn = __DIR__.'/cache/'.md5($url).'.html';
        if (file_exists($cache_fn) && filemtime($cache_fn) > time()-60*60*24*7){
            $rawhtml = file_get_contents($cache_fn); 
            logtxt("using cache");
        } else {
            logtxt("loading page...");
            $rawhtml = file_get_contents($url); 
            if ($rawhtml){
                file_put_contents($cache_fn, $rawhtml); 
            } else {
                return false; 
            }
        }

        $res = array(); 

        if ($rawhtml){
            // echo $rawhtml; 
            $rawhtml = str_replace("\r\n", "", $rawhtml); 
            $rawhtml = str_replace("\n", "", $rawhtml); 
            // TODO: Better work with DOMDocument, but since there isn't any class/ID we can use for parsing...
            if (preg_match('~<h2 class="h3">Vertreten durch:</h2>(.+)~i', $rawhtml, $matches)){
                $ex_p = explode("</p>", $matches[1]); 
                $rawaddress = $ex_p[0];
                $res["_rawaddress"] = $rawaddress; 

                $lines = explode("<br>", $rawaddress); 
                foreach ($lines as $l => $line){
                    $line = trim($line); 

                    // role is always first line
                    if ($l == 0 && preg_match("~<p>(.+) der ~", $line, $match_role)){
                        $res["name_role"] = $match_role[1]; 
                    }

                    // name is not in first line
                    if ($l > 0 && preg_match("~<b>(.+)</b>~", $line, $match_name)){
                        $res["name"] = $match_name[1]; 
                    }

                    // E-Mail is always last line
                    if ($l == sizeof($lines)-1 && preg_match("~^(.+@.+)$~i", $line, $match_email)){
                        $res["email"] = $match_email[1]; 
                    }

                    var_dump($line); 
                }
            }
        }
        return $res; 
    }

    foreach ($data as $entry){
        logtxt($entry["name"]);
        if (preg_match("~https?://vor-ort\.kolping\.de/~i", $entry["url"])){
            // KF with (just) microsite
            logtxt("KF has microsite");
            $msdata = getMicrositeData($entry["url"]);
            var_dump($msdata); 
        }
    }
?>