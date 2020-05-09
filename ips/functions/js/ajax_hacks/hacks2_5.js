var formObj = null;
var sportTyp = "";
var request=null;

window.onload=function(){
    var allInputs = document.getElementsByTagName("input");
    if(allInputs != null){
        for(var i = 0; i < allInputs.length;i++) {
            if(allInputs[i].type == "checkbox"){
                allInputs[i].onchange=function(){
                    sendSportsInfo(this)};
            }
        }
    }
}

function sendSportsInfo(obj){
    if (obj == null ) { return; }
    var url = "";
    var nme = "";
    if(obj.checked) {
        formObj=obj;
        nme = obj.name;
        var sub = nme.substring(0,nme.indexOf("_"));
        sportTyp=sub;
        url = "http://www.parkerriver.com/s/fav_sports?sportType="+nme+
              "&choices="+obj.value;
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
                    //return value is a JSON object
                    var func = new Function("return "+resp);
                    displayPollResults(func());
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

function displayPollResults(obj){
    var div = document.getElementById(sportTyp+"_poll");
    var spans = div.getElementsByTagName("span");
    for(var i = 0; i < spans.length; i++){
        if(spans[i].id.indexOf("title") != -1){
            spans[i].innerHTML = "<strong>Here are the latest poll results for "+sportTyp+
                                 " sports</strong>"
        }   else {
            //use the object and its properties
            var str ="<br />";
            for(var prop in obj) { str += prop + " : "+obj[prop]+"<br />";}
            spans[i].innerHTML = str;
        }
    }
    div.style.visibility="visible";
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
