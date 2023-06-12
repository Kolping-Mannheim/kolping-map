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
        })
    };

    L.tileLayer('/osmtiles/tile.php?s={s}&z={z}&x={x}&y={y}', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Beitragende | &copy; Kolpingwerk Deuschland'
    }).addTo(mymap);

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
                if (e.geo){
                    L.marker([e.geo.lat, e.geo.lon], {icon: mapicons["kf"]}).addTo(mymap).bindPopup(popup_text.join('<br>'));
                }
            });
            
        }
    });

})();