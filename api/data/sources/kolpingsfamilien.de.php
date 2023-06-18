<?php
    $data = file_get_contents($_CONFIG["url_kolpinglocally"]);
    $data = json_decode($data, true); 
    // var_dump($data); 

    function getMicrositeData ($url){
        $res = array(
            "representative" => [],
            "contact" => [],
            "links" => [],
            "news" => []
        ); 

        // home page
        $rawhtml = getURLCached($url); 
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

        $newstabscount = $xpath->query('//ul[contains(@id,"news-tabs")]/li')->length; 
        if ($newstabscount > 1){
            $news = $xpath->query('//div[@id="news"]/div/article[contains(@class,"news-item")]'); 
            foreach ($news as $article){
                $this_news = [
                    "date" => $xpath->query('div/p[contains(@class,"news-item-date")]/time', $article)->item(0)->getAttribute("datetime"),
                    "url" => $xpath->query('div/p[contains(@class,"news-item-headline")]/a', $article)->item(0)->getAttribute("href"),
                    "title" => trim($xpath->query('div/p[contains(@class,"news-item-headline")]/a', $article)->item(0)->textContent),
                    "description" => trim($xpath->query('div/p[contains(@class,"news-item-teaser")]', $article)->item(0)->textContent)
                    
                ];
                $res["news"][] = $this_news;
            }
        }

        // termine
        $events = $xpath->query('//div[@class="events"]/div[contains(@class,"event--small")]'); 
        
        if ($events->length > 0){
            foreach ($events as $event){
                $res["events"][] = [
                    "date" => $xpath->query('p[@class="event-date"]/time', $event)->item(0)->getAttribute("datetime"),
                    "title" => $xpath->query('p[@class="event-title"]', $event)->item(0)->textContent,
                    "description" => $xpath->query('div[@class="event-modal-content"]/div/div[@class="em-content"]', $event)->item(0)->textContent,
                ];
            }
        }



        // impressum
        $rawhtml = getURLCached($url."impressum/"); 
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
            "usesMicrositeNews" => false,
            "hasMicrositeNewsLast6m" => false,
            "usesMicrositeEvents" => false,
            "hasMicrositeEvents" => false,
            "representativeIsContact" => false,
            "representative" => [],
            "contact" => [],
            "links" => [],
            "news" => [],
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

            if (isset($msdata["news"])){
                $local["news"] = $msdata["news"];
                if (sizeof($local["news"]) > 0){
                    $local["usesMicrositeNews"] = true; 

                    foreach ($local["news"] as $news){
                        if ($news["date"] > date("Y-m-d", strtotime("-6 months"))){
                            $local["hasMicrositeNewsLast6m"] = true; 
                            break; 
                        }
                    }
                }
            }

            if (isset($msdata["events"]) && sizeof($msdata["events"]) > 0){
                $local["usesMicrositeEvents"] = true; 
                $local["hasMicrositeEvents"] = true; 

                $local["events"] = $msdata["events"];
            }
            
            $local["links"] = array_merge($msdata["links"], $local["links"]);
        } else {
            logtxt("custom domain: ".$entry["url"]);

            // geocoding fallback via name
            $townname = preg_replace("~^Kolpingsfamilie ~i", "", $local["name"]);
            // Kolpingsfamilie Nürnberg/St. Elisabeth
            $townname = preg_replace("~(/| )(St\.|Herz).*$~i", "", $townname);
            $townname = preg_replace("~e\.V\.$~i", "", $townname);
            $townname = preg_replace("~-Zentral$~i", "", $townname);
            $townname = trim($townname);
            
            $geocoding = getGeocodingByQuery($townname . ", DE");
            if ($geocoding){
                $local["geo"] = $geocoding;
            }
        }

        if (isset($local["contact"]["name"]) && isset($local["representative"]["name"]) 
            && $local["contact"]["name"] == $local["representative"]["name"]){
            $local["representativeIsContact"] = true; 
        }

        if (isset($local["representative"]["address"]) && isset($local["representative"]["zip"]) && isset($local["representative"]["town"])){
            $geocoding = getGeocodingByQuery($local["representative"]["address"].", ".$local["representative"]["zip"]." ".$local["representative"]["town"]);
            if ($geocoding){
                $local["geo"] = $geocoding;
            }
        }
        $locallist[] = $local; 
    }

    return $locallist; 
?>