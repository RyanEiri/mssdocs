var map = null;
window.onload=function(){
    createMap();
    document.getElementById("submit").onclick=function(){
        getDirections(document.forms[0]._street.value,
                document.forms[0]._city.value,
                document.forms[0]._state.value,
                document.forms[0]._dname.value,
                document.forms[0]._latitude.value,
                document.forms[0]._longitude.value);
    };
    document.getElementById("rem_bubbles").onclick=function(){
        clearOverlays();
    };

    document.getElementById("yah_maps").onclick=function(){
        createYMap();
    };
};
function createYMap(){
    writeMap(document.forms[0]._latitude.value,
            document.forms[0]._longitude.value);
}
function createMap(){
    map = new GMap(document.getElementById("map"));
    GEvent.addListener(map, 'click', function(overlay, point) {
        document.forms[0]._longitude.value=point.x;
        document.forms[0]._latitude.value=point.y;
        map.addOverlay(new GMarker(point));

    });
    map.addControl(new GLargeMapControl());
    map.addControl(new GMapTypeControl());
    //center on roughly middle of USA continent
    map.centerAndZoom(new GPoint(-97.20703, 40.580584), 14);
}

function clearOverlays(){
    if(map != null){
        map.clearOverlays();
    }
}

function getDirections(street,city,state,
                       destName,lat,lng){

    var _str = encodeURIComponent(street);
    var _cit = encodeURIComponent(city);
    var url = "http://www.parkerriver.com/s/dd?"+_str+"&tlt="+
              lat+"&tln="+lng+"&csz="+
              _cit+"%2C"+state+"&country=us&tname="+destName;
    httpRequest("GET",url,true,handleResponse);
}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var _dirs = request.responseText;
                var targDiv = document.getElementById("ymap_container");
                targDiv.innerHTML=_dirs+
                                  '<p><form><button type=\"button\" onclick=\"window.print()\">Print Directions</button></form></p>';
            } else {
                //request.status is 503  if the application isn't available; 500 if the application has a bug
                alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
            }
        }//end outer if
    } catch (err)   {
        alert("It does not appear that the server is available for this application. Please"+
              " try again very soon. \nError: "+err.message);

    }
}

function writeMap(lat,lng){
    //document.getElementById('ymap_container').style.visibility="visible";
    var _point = new YGeoPoint(parseInt(lat), parseInt(lng));
    var _map = new  YMap(document.getElementById('ymap_container'));
    _map.drawZoomAndCenter(_point, 8);
    _map.addPanControl();
    _map.addZoomLong();
    document.getElementById('yah_maps').disabled=true;
}
