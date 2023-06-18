(function(){
    var defaultView = [50.9979695352362, 9.64683525084557], defaultZoom = 6; 
    var mymap = L.map('map', {
        fullscreenControl: true
    }).setView(defaultView, defaultZoom); 

    var command = L.control({position: 'topright'});
    command.onAdd = function (mymap) {
        var div = L.DomUtil.create('div', 'command');
        
        div.innerHTML = '<form><input id="command" type="checkbox" checked>Zeige Kolpingsfamilien</form>'; 
        return div;
    };
    command.addTo(mymap);

    var command = L.control({position: 'topright'});
    command.onAdd = function (mymap) {
        var div = L.DomUtil.create('div', 'command');
        
        div.innerHTML = '<form><input id="command" type="checkbox">Zeige Kolpingshäuser</form>'; 
        return div;
    };
    command.addTo(mymap);


    var mapicons = {
        'kf': L.icon({
            iconUrl: '/img/kolping_logo_quadrat_100px.png',
            iconSize: [20, 20]
            //iconAnchor: [22, 94],
            //popupAnchor: [-3, -76],
            //shadowUrl: 'my-icon-shadow.png',
            //shadowSize: [68, 95],
            //shadowAnchor: [22, 94]
        }),
        'kh': L.icon({
            iconUrl: '/img/kolpinghaeuser_tuer.png',
            iconSize: [20, 20]
            //iconAnchor: [22, 94],
            //popupAnchor: [-3, -76],
            //shadowUrl: 'my-icon-shadow.png',
            //shadowSize: [68, 95],
            //shadowAnchor: [22, 94]
        }),
        'wc': L.icon({
            iconUrl: '/img/kolping-jgd-weltkugel_orange_100px.png',
            iconSize: [20, 20]
            //iconAnchor: [22, 94],
            //popupAnchor: [-3, -76],
            //shadowUrl: 'my-icon-shadow.png',
            //shadowSize: [68, 95],
            //shadowAnchor: [22, 94]
        }),
        'khotel': L.icon({
            iconUrl: '/img/kolping-hotel.png',
            iconSize: [20, 20]
            //iconAnchor: [22, 94],
            //popupAnchor: [-3, -76],
            //shadowUrl: 'my-icon-shadow.png',
            //shadowSize: [68, 95],
            //shadowAnchor: [22, 94]
        }),
        'kpoi': L.icon({
            iconUrl: '/img/kolping_poi_200px.png',
            iconSize: [20, 20]
            //iconAnchor: [22, 94],
            //popupAnchor: [-3, -76],
            //shadowUrl: 'my-icon-shadow.png',
            //shadowSize: [68, 95],
            //shadowAnchor: [22, 94]
        })
    };

    L.tileLayer('/osmtiles/tile.php?s={s}&z={z}&x={x}&y={y}&r={r}', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Beitragende | &copy; Engagierte im Kolpingwerk weltweit'
    }).addTo(mymap);

    var kfevents = []; 
    var wordCloudWords = [];

    

    $.ajax({
        url: '/api/',
        dataType: 'json',
        success: function (data){
            var markercluster = L.markerClusterGroup({
                maxClusterRadius: 50
            });
            data.list.forEach((e,i) => {
                

                var popup_text = [
                    '<b>' + e.name + '</b>',
                    ''
                ];
                if (e.contact){
                    popup_text.push("<u>Ansprechpartner:</u>");
                    if (e.contact.name) popup_text.push(e.contact.name);
                    if (e.contact.email) popup_text.push('<a href="mailto:'+e.contact.email+'">'+e.contact.email+'</a>');
                    if (e.contact.tel) popup_text.push(e.contact.tel);
                    if (e.contact.fax) popup_text.push(e.contact.fax);
                    popup_text.push("");
                }
                if (e.hasMicrosite){
                    popup_text.push("Diese Kolpingsfamilie nutzt das Microsite-Angebot");
                    popup_text.push('<a href="'+e.url+'" target="_blank">Zur Microsite &raquo;</a>');
                } else {
                    popup_text.push('<a href="'+e.url+'" target="_blank">'+e.url+'</a>');
                }
                popup_text.push("");
                popup_text.push("Diözesanverband " + e._kolping_region);

                if (e.hasMicrositeNewsLast6m){
                    popup_text.push("<i>Aktuelle Neuigkeiten:</i>");
                    var popup_news = []; 
                    popup_news.push("<ul>");
                    e.news.forEach((news, n) => {
                        if (n > 4) return; 
                        popup_news.push('<li><a href="'+news.url+'" target="_blank">'+news.title+'</a></li>');
                        wordCloudWords = wordCloudWords.concat(news.title.split(" "));
                    });
                    popup_news.push("</ul>");
                    popup_text.push(popup_news.join(''));
                } else {
                    popup_text.push("Diese Kolpingsfamilie hat in den letzten 6 Monaten keine Neuigkeiten veröffentlicht.");
                }
                if (e.events && e.events.length > 0){
                    popup_text.push("<i>Anstehende Veranstaltungen:</i>");
                    var popup_events = []; 
                    popup_events.push("<ul>");
                    e.events.forEach((event) => {
                        
                        popup_events.push('<li>'+event.date+': ' +event.title+'</li>');
                        
                        event.kf = e.name; 
                        event.dv = e._kolping_region; 
                        kfevents.push(event); 
                        wordCloudWords = wordCloudWords.concat(event.title.split(" "));
                    });
                    popup_events.push("</ul>");
                    popup_text.push(popup_events.join(''));
                }
                if (e.geo){
                    markercluster.addLayer(L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["kf"]}).bindPopup(popup_text.join('<br>')));
                    //L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["kf"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });
            mymap.addLayer(markercluster);
            data.kolpinghaeuser.forEach((e,i) => {
                var popup_text = [
                    '<b>' + e.name + '</b>',
                    ''
                ];
                
                popup_text.push('<a href="'+e.url+'" target="_blank">'+e.url+'</a>');
                popup_text.push("");
                popup_text.push(e.address);
                popup_text.push(e.zip + " " + e.town);

                if (e.geo){
                    L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["kh"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });

            data.workcamps.forEach((e,i) => {
                var popup_text = [
                    '<b>Kolping Workcamp: "' + e.name + '"</b>'
                ];

                if (e.subname) popup_text.push(e.subname);

                if (e.description){
                    popup_text.push('<p>' + e.description + '</p>');
                }
                
                popup_text.push('<a href="'+e.url+'" target="_blank">Zum Angebot &raquo;</a>');
                popup_text.push("");
                if (e.image){
                    popup_text.push('<img src="'+e.image+'" style="max-width: 200px;">');
                }

                if (e.geo){
                    L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["wc"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });

            data.kolpinghotels.forEach((e,i) => {
                var popup_text = [
                    '<b>' + e.name + '</b>'
                ];

                if (e.subname) popup_text.push(e.subname);

                if (e.description){
                    popup_text.push('<p>' + e.description + '</p>');
                }

                if (e.address){
                    popup_text.push(e.address);
                    popup_text.push("");
                }
                if (e.tel){
                    popup_text.push(e.tel);
                }
                if (e.email){
                    popup_text.push(e.email);
                }
                popup_text.push('<a href="'+e.url+'" target="_blank">Zum Hotel &raquo;</a>');
                popup_text.push("");

                if (e.geo){
                    L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["khotel"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });

            data.pois.forEach((poi,i) => {
                var popup_text = [
                    '<b>' + poi.name + '</b>'
                ];

                if (poi.subname) popup_text.push(poi.subname);

                if (poi.description){
                    popup_text.push('<p>' + poi.description + '</p>');
                }

                if (poi.address){
                    popup_text.push(poi.address);
                    popup_text.push("");
                }
                if (poi.tel){
                    popup_text.push(poi.tel);
                }
                if (poi.email){
                    popup_text.push(poi.email);
                }
                if (poi.url) popup_text.push('<a href="'+poi.url+'" target="_blank">Webseite &raquo;</a>');

                if (poi.geo){
                    L.marker([poi.geo.lat, poi.geo.lon], {icon: mapicons["kpoi"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });

            updateEventTable(); 
            drawWordCloud();
        }
    });

    updateEventTable = function(){
        $("#events tbody").html(""); 

        var $tbody = $("#events tbody"); 

        kfevents.sort((a,b) => (a.date > b.date) ? 1 : ((b.date > a.date) ? -1 : 0))


        kfevents.forEach((event, e) => {
            $tr = $("<tr>");
            $tr.append($("<td>").text(event.date));
            $tr.append($("<td>").text(event.dv));
            $tr.append($("<td>").text(event.kf));
            $tr.append($("<td>").html(event.title + '<br><small>'+event.description+'</small>'));
            $tbody.append($tr);
        });
    }


    // d3 wordcloud
    drawWordCloud = function (){
        // List of words#
        var wordsByCount = {};
        wordCloudWords.forEach((word) => {
            word = word.replace(/(:|”|“)/i, '');
            var wordLC = word.toLowerCase().trim(); 

            // word is too short
            if (word.length <= 3) return; 

            // word doesn't contain at least one number/letter
            if (word.match(/^[^0-9a-z]+$/i)) return;

            // word is stopword
            if (stopwords_de.includes(wordLC)) return; 
            

            if (wordsByCount[wordLC]){
                wordsByCount[wordLC].count++; 
            } else {
                wordsByCount[wordLC] = { text: word, count: 1 };
            }
        });

        var wordsByCountArr = []; 
        for (var i in wordsByCount){
            if (wordsByCount.hasOwnProperty(i) && wordsByCount[i].count > 2){
                wordsByCountArr.push(wordsByCount[i]);
            }
        }

        // shuffle - credit: https://stackoverflow.com/a/46545530
        wordsByCountArr = wordsByCountArr.map(value => ({ value, sort: Math.random() }))
        .sort((a, b) => a.sort - b.sort)
        .map(({ value }) => value);

        wordsByCountArr.forEach((word) => {
            $("#wc_test").append($("<span>").text(word.text+" ").attr("title", word.text + " - " + word.count + "x").css("font-size", (10+(word.count*0.3)) + "px"));
        })

        // set the dimensions and margins of the graph
        var margin = {top: 10, right: 10, bottom: 10, left: 10},
        width = 550 - margin.left - margin.right,
        height = 550 - margin.top - margin.bottom;

        // append the svg object to the body of the page
        var svg = d3.select("#my_dataviz").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("width", "100%")
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform",
            "translate(" + margin.left + "," + margin.top + ")");

        // Constructs a new cloud layout instance. It run an algorithm to find the position of words that suits your requirements
        // Wordcloud features that are different from one word to the other must be here
        var layout = d3.layout.cloud()
        .size([width, height])
        .words(wordsByCountArr.map(function(d) { return {text: d.text, size: d.count }; }))
        .padding(5)        //space between words
        //.rotate(-45)       // rotation angle in degrees
        .fontSize(20)      // font size of words
        .on("end", draw);
        layout.start();

        // This function takes the output of 'layout' above and draw the words
        // Wordcloud features that are THE SAME from one word to the other can be here
        function draw(words) {
        svg
        .append("g")
        .attr("transform", "translate(" + layout.size()[0] / 2 + "," + layout.size()[1] / 2 + ")")
        .selectAll("text")
            .data(words)
        .enter().append("text")
            .style("font-size", function(d) { return d.size + "px"; })
            .style("fill", "#ff8c00")
            .attr("text-anchor", "middle")
            .style("font-family", "Impact")
            .attr("transform", function(d) {
            return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
            })
            .text(function(d) { return d.text; });
        }
    }
    
})();