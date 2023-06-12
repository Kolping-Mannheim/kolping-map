<?php
    include __DIR__.'/../../config.php'; 

    $data = file_get_contents($_CONFIG["url_kolpinglocally"]);
    $data = json_decode($data, true); 
    // var_dump($data); 

    function getMicrositePage ($url){
        logtxt($url);
        $cache_fn = __DIR__.'/cache/'.md5($url).'.html';

        $rawhtml = false; 
        if (file_exists($cache_fn) && filemtime($cache_fn) > time()-60*60*24*7){
            $rawhtml = file_get_contents($cache_fn); 
            logtxt("using cache");
        } else {
            logtxt("loading page...");
            $rawhtml = file_get_contents($url); 
            if ($rawhtml){
                file_put_contents($cache_fn, $rawhtml); 
            }
        }
        return $rawhtml;
    }

    function getMicrositeData ($url){
        $res = array(
            "representative" => [],
            "contact" => [],
            "links" => []
        ); 

        // home page
        $rawhtml = getMicrositePage($url); 
        $doc = new DOMDocument();
        @$doc->loadHTML($rawhtml);
        $links = $doc->getElementsByTagName("a");

        $linkpatterns = array(
            "~facebook.com/~i" => "facebook",
            "~chat.whatsapp.com/~i" => "whatsapp"
        );
        foreach ($links as $a){
            $href = $a->getAttribute("href"); 

            foreach ($linkpatterns as $pattern => $linktype){
                if (preg_match($pattern, $href)){
                    $link = [
                        "url" => $href,
                        "type" => $linktype,
                        "text" => trim($a->nodeValue)
                    ];
                    $res["links"][] = $link; 
                }
            }
        }

        $xpath = new DOMXPath($doc);
        $contactbox = $xpath->query('//div[contains(@class,"main-content")]/div/div/div[contains(@class,"col-md-4")]/div[contains(@class, "box--standalone")]')->item(0);
        //$res["contact"]["_raw"] = $contactbox->textContent;
        $contactps = $xpath->query('div/p', $contactbox);
        foreach ($contactps as $p){
            $class = $p->getAttribute("class");
            if (preg_match("~text-primary~i", $class)){
                $res["contact"]["role"] = $p->textContent;
            }
            if (preg_match("~mb-0~i", $class)){
                if (preg_match("~@~i", $p->textContent)){
                    $res["contact"]["email"] = $p->textContent;
                } else {
                    $res["contact"]["name"] = $p->textContent;
                }
            }
        }

        // impressum
        $rawhtml = getMicrositePage($url."impressum/"); 
        if ($rawhtml){
            // echo $rawhtml; 
            $rawhtml = str_replace("\r\n", "", $rawhtml); 
            $rawhtml = str_replace("\n", "", $rawhtml); 
            // TODO: Better work with DOMDocument, but since there isn't any class/ID we can use for parsing...
            $representative = []; 
            if (preg_match('~<h2 class="h3">Vertreten durch:</h2>(.+)~i', $rawhtml, $matches)){
                $ex_p = explode("</p>", $matches[1]); 
                $rawaddress = $ex_p[0];
                //$representative["_rawaddress"] = $rawaddress; 

                $lines = explode("<br>", $rawaddress); 
                foreach ($lines as $l => $line){
                    $line = trim($line); 
                    
                    // role is always first line
                    if ($l == 0 && preg_match("~<p>(.+) der ~", $line, $match_role)){
                        $representative["role"] = $match_role[1]; 
                        continue; 
                    }

                    // name is not in first line
                    if ($l > 0 && preg_match("~<b>(.+)</b>~", $line, $match_name)){
                        $representative["name"] = $match_name[1]; 
                        continue; 
                    }

                    // telefon is not in first line and starts with "telefon:"
                    if ($l > 0 && preg_match("~^Telefon:(.+)$~i", $line, $match_tel)){
                        $representative["telefon"] = $match_tel[1]; 
                        $representative["telefon"] = trim(preg_replace("~[^0-9+]~i", "", $representative["telefon"]));
                        continue; 
                    }

                    // zip/town starts with ZIP
                    if ($l > 0 && preg_match("~^([0-9]{5}) (.+)$~i", $line, $match_zip_town)){
                        $representative["zip"] = $match_zip_town[1];
                        $representative["town"] = $match_zip_town[2];
                        continue; 
                    }

                    if ($l > 0 && $l < sizeof($lines)-1){
                        $representative["address"] = $line; 
                        continue; 
                    }

                    // E-Mail is always last line
                    if ($l == sizeof($lines)-1 && preg_match("~^(.+@.+)$~i", $line, $match_email)){
                        $representative["email"] = $match_email[1]; 
                        continue; 
                    }

                    logtxt("Non-matching line: ");
                    var_dump($line); 
                }
                $res["representative"] = $representative;
            }
        }
        return $res; 
    }

    $locallist = []; 
    foreach ($data as $e => $entry){
        if (trim($entry["type"]) != "family") continue; 

        $local = array(
            "name" => trim($entry["name"]),
            "url" => $entry["url"],
            "hasMicrosite" => false,
            "representativeIsContact" => false,
            "representative" => [],
            "contact" => [],
            "links" => [],
            "_kolping_type" => trim($entry["type"]),
            "_kolping_region" => trim($entry["region"]),
            "_raw_kolping" => $entry
        ); 
        logtxt($e." - ".$entry["name"]);
        $local["links"][] = array(
            "type" => "website",
            "url" => $entry["url"]
        );
        if (preg_match("~https?://vor-ort\.kolping\.de/~i", $entry["url"])){
            // KF with (just) microsite
            $local["hasMicrosite"] = true; 
            logtxt("KF has microsite");
            $msdata = getMicrositeData($entry["url"]);

            if (isset($msdata["representative"]["name"])){
                $local["representative"] = $msdata["representative"];
                $local["representative"]["source"] = "microsite";
            }
            if (isset($msdata["contact"]["name"])){
                $local["contact"] = $msdata["contact"];
                $local["contact"]["source"] = "microsite";
            }
            
            $local["links"] = array_merge($msdata["links"], $local["links"]);
        } else {
            logtxt("custom domain: ".$entry["url"]);
        }

        if (isset($local["contact"]["name"]) && isset($local["representative"]["name"]) 
            && $local["contact"]["name"] == $local["representative"]["name"]){
            $local["representativeIsContact"] = true; 
        }

        $locallist[] = $local; 

        if ($e > 300) break; 
    }
    $list = [
        "meta" => [
            "lastupdated" => date("Y-m-d H:i:s")
        ],
        "list" => $locallist
    ];
    file_put_contents(__DIR__.'/locallist.json', json_encode($list, JSON_PRETTY_PRINT)); 
?>