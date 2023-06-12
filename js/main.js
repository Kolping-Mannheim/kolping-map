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
        })
    };

    L.tileLayer('/osmtiles/tile.php?s={s}&z={z}&x={x}&y={y}', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Beitragende | &copy; Kolpingwerk Deuschland'
    }).addTo(mymap);

    var kfevents = []; 

    $.ajax({
        url: '/api/data/locallist.json',
        dataType: 'json',
        success: function (data){
            console.log(data); 
            data.list.forEach((e,i) => {
                var popup_text = [
                    '<b>' + e.name + '</b>',
                    ''
                ];
                if (e.contact && e.contact.name){
                    popup_text.push("<u>Ansprechpartner:</u>");
                    popup_text.push(e.contact.name);
                    if (e.contact.email){
                        popup_text.push('<a href="mailto:'+e.contact.email+'">'+e.contact.email+'</a>');
                    }
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
                    popup_text.push("<ul>");
                    e.news.forEach((news, n) => {
                        popup_text.push('<li><a href="'+news.url+'" target="_blank">'+news.title+'</a></li>');
                    });
                    popup_text.push("</ul>");
                } else {
                    popup_text.push("Diese Kolpingsfamilie hat in den letzten 6 Monaten keine Neuigkeiten veröffentlicht.");
                }
                if (e.events){
                    e.events.forEach((event) => {
                        event.kf = e.name; 
                        kfevents.push(event); 
                    });
                }
                if (e.geo){
                    L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["kf"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });
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

            updateEventTable(); 
        }
    });

    updateEventTable = function(){
        $("#events tbody").html(""); 

        var $tbody = $("#events tbody"); 

        console.log(kfevents);
        kfevents.sort((a,b) => (a.date > b.date) ? 1 : ((b.date > a.date) ? -1 : 0))


        kfevents.forEach((event, e) => {
            $tr = $("<tr>");
            $tr.append($("<td>").text(event.date));
            $tr.append($("<td>").text(event.kf));
            $tr.append($("<td>").html(event.title + '<br><small>'+event.description+'</small>'));
            $tbody.append($tr);
        });
    }

})();