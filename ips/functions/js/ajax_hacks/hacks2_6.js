var origOptions = null;
var request=null;

window.onload=function(){
    var sel = document.getElementById("cts");
    var sel2 = document.getElementById("sts");
    if(sel != null){
        sel.onclick=function(){
            addCountries(this)};
    }
    origOptions = new Array();
    for(var i = 0; i < sel2.options.length; i++){
        origOptions[i]=sel2.options[i];
    }
}

function addCountries(obj){
    if (obj == null ) { return; }
    var url = "";
    var optsArray = obj.options;
    var val = "";
    for(var i=0; i < optsArray.length; i++){
        if(optsArray[i].selected) {
            val=optsArray[i].value; break;
        }

    }
    url = "http://www.parkerriver.com/s/selectl?countryType="+
          encodeURIComponent(val);
    httpRequest("GET",url,true);

}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseText;
                if(resp != null){
                    alert(resp);
                    //return value is a JSON array
                    var objt = eval(resp);
                    addToSelect(objt);
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

function addToSelect(obj){
    var _select = document.getElementById("sts");
    var el;
    //first remove all options
    while(_select.hasChildNodes()){
        for(var i = 0; i < _select.childNodes.length; i++){
            _select.removeChild(_select.firstChild);
        }
    }
    //now add just the original options
    for(var h=0; h < origOptions.length;h++) {

        _select.appendChild(origOptions[h]);
    }
    //obj is an array of new options values
    for(var i=0; i < obj.length;i++) {
        el = document.createElement("option");
        el.appendChild(document.createTextNode(obj[i]));
        _select.insertBefore(el,_select.firstChild);
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
