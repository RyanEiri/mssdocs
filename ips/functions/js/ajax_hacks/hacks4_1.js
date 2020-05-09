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
};
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
    httpRequest("GET",url,true);
}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var _dirs = request.responseText;
                var targDoc = parent.frames[1].document;
                targDoc.open();
                targDoc.write('<html><head><title></title></head><body>'+
                              _dirs+'<p><form><button type=\"button\" onclick=\"window.print()\">Print Directions</button></form></p></body></html>');
                targDoc.close();
                //doesn't work:  var div = targdoc.getElementById("dirs");
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

/* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
    try{
        /* Specify the function that will handle the HTTP response */
        request.onreadystatechange=handleResponse;
        request.open(reqType,url,bool);
        request.send(null);
    } catch (errv) {

        alert(
                "The application cannot contact the server at the moment. "+
                "Please try again in a few seconds." );
    }
}
/* Wrapper function for constructing a Request object.
 Parameters:
  reqType: The HTTP request type such as GET or POST.
  url: The URL of the server program.
  asynch: Whether to send the request asynchronously or not. */
function httpRequest(reqType,url,asynch){
    //Mozilla-based browsers
    if(window.XMLHttpRequest){
        request = new XMLHttpRequest();
    } else if (window.ActiveXObject){
        request=new ActiveXObject("Msxml2.XMLHTTP");
        if (! request){
            request=new ActiveXObject("Microsoft.XMLHTTP");
        }
     }
    //the request could still be null if neither ActiveXObject
    //initializations succeeded
    if(request){
       initReq(reqType,url,asynch);
    }  else {
        alert("Your browser does not permit the use of all "+
        "of this application's features!");}
}