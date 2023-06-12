(function(){
    var defaultView = [49.5909695352362, 8.64683525084557], defaultZoom = 14.5; 
    var mymap = L.map('map', {
        fullscreenControl: true
    }).setView(defaultView, defaultZoom); 

    L.tileLayer('/osmtiles/tile.php?s={s}&z={z}&x={x}&y={y}', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Beitragende | &copy; Kolpingwerk Deuschland'
    }).addTo(mymap);

})();