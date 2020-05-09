var map = null;
window.onload = function(){
    if(GBrowserIsCompatible()){
        googleAjax('http://www.parkerriver.com/s/gmap?user=bwperry');
    } else { alert('Your browser is not compatible with Google Maps!');}
};
function createMap(lat,lng,zoomLevel){
    map = new GMap(document.getElementById("map"));
    GEvent.addListener(map, 'click', function(overlay, point) {
        document.forms[0]._longitude.value=point.x;
        document.forms[0]._latitude.value=point.y;
        map.addOverlay(new GMarker(point));

    });
    map.addControl(new GLargeMapControl());
    map.addControl(new GMapTypeControl());
    if(lat != null && lat.length != 0 && lng != null && lng.length != 0 &&
       zoomLevel != null && zoomLevel.length != 0){
        map.centerAndZoom(new GPoint(lng, lat), zoomLevel);
    } else {
        //center on roughly the middle of USA continent
        map.centerAndZoom(new GPoint(-97.20703, 40.580584), 14);
    }
}

function googleAjax(url){

    var request = GXmlHttp.create();
    request.open("GET", url, true);
    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            if (request.status == 200) {
                var resp = request.responseXML;

                var rootNode = resp.documentElement;
                var zoom = rootNode.getElementsByTagName("zoomLevel")[0];
                var latLng = rootNode.getElementsByTagName("centerCoords")[0];
                var coordArr = latLng.firstChild.nodeValue.split(" ");
                var zoomLevel=zoom.firstChild.nodeValue;
                createMap(coordArr[0],coordArr[1],zoomLevel);
                document.forms[0]._latitude.value=coordArr[0];
                document.forms[0]._longitude.value=coordArr[1];
                document.forms[0]._zoomLevel.value=zoomLevel;
            } else {
                alert(
                        "The application had a problem communicating with the server. Please try again soon.");
            }
        }
    }
    request.send(null);

}
