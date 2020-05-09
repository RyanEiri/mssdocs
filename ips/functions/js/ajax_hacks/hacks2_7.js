var sportType="";
var request=null;
window.onload=function(){
    var rads = document.getElementsByTagName("input");
    if(rads != null) {
        for(var i = 0; i < rads.length; i++) {
            if(rads[i].type=="radio"){ rads[i].onclick=function(){
                getSports(this)};}

        }
    }

}

function getSports(obj){
    if (obj == null ) { return; }
    var url = "";
    var val = "";
    if(obj.checked) {
        val=obj.value;
        sportType=val;
        url = "http://www.parkerriver.com/s/fav_sports"+
              "?sportType="+encodeURIComponent(val)+"&col=y";
        httpRequest("GET",url,true);
    }

}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseText;
                if(resp != null){
                    //return value is a JSON array
                    var objt = eval(resp);
                    createChecks(objt);
                }
            } else {
                //request.status is 503  if the application isn't available;
                //500 if the application has a bug
                alert(
                        "A problem occurred with communicating between"+
                        " the XMLHttpRequest object and the server program.");
            }
        }//end outer if
    } catch (err)   {
        alert("It does not appear that the server "+
              "is available for this application. Please"+
              " try again very soon. \nError: "+err.message);

    }
}

function createChecks(obj){
    //alert(obj);
    var _div = document.getElementById("checks");
    var el;
    //first remove all existing checkboxes
    while(_div.hasChildNodes()){
        for(var i = 0; i < _div.childNodes.length; i++){
            _div.removeChild(_div.firstChild);
        }
    }
    //obj is an array of new sports names
    for(var i=0; i < obj.length;i++) {
        el = document.createElement("input");
        el.setAttribute("type","checkbox");
        el.setAttribute("name",sportType);
        el.setAttribute("value",obj[i]);
        _div.appendChild(el);
        _div.appendChild(document.createTextNode(obj[i]));
        _div.appendChild(document.createElement("br"));
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
                "The application cannot contact "+
                "the server at the moment. "+
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

