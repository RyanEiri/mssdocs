var request;
var urlFragment="http://www.parkerriver.com/s/sender?name=guest";
var st;

function getAllHeaders(url,styl){
    st=styl;
    httpRequest("GET",url,true);
}

 /*  Set one or more CSS style attributes on an DOM Element 
 CSS2Properties Object.
 stType stands for a style name, as in 'plain,''fancy,''loud,' or 'cosmo'*/
function setStyle(stType,stylObj){
  switch(stType){
    case 'plain' : 
      stylObj.maxWidth="80%";
      stylObj.border="thin solid black"; 
      stylObj.padding="5%"
      stylObj.textShadow="none";
      stylObj.fontFamily="Arial, serif";
      stylObj.fontSize="0.9em";
      stylObj.backgroundColor="yellow"; break;
    case 'loud' : 
      stylObj.maxWidth="80%";
      stylObj.border="thin solid black"; 
      stylObj.padding="5%"
      stylObj.fontFamily="Impact, serif";
      stylObj.fontSize="1.4em";
      stylObj.textShadow="0 0 2.0em black";
      stylObj.backgroundColor="rgb(181,77,79)"; break;
    case 'fancy' :
      stylObj.maxWidth="80%";
      stylObj.border="thin solid black"; 
      stylObj.padding="5%"
      stylObj.fontFamily="Herculanum, Verdana, serif";
      stylObj.fontSize="1.2em";
      stylObj.fontStyle="oblique";
      stylObj.textShadow="0.2em 0.2em grey";
      stylObj.color="rgb(21,49,110)";
      stylObj.backgroundColor="rgb(234,197,49)"; break;
     case 'cosmo' :
      stylObj.maxWidth="80%";
      stylObj.border="thin solid black"; 
      stylObj.padding="1%"
      stylObj.fontFamily="Papyrus, serif";
      stylObj.fontSize="0.9em";
      stylObj.textShadow="0 0 0.5em black";
      stylObj.color="aqua";
      stylObj.backgroundColor="teal"; break;
     default : 
       alert('default');
       
  }
}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                /*All headers received as a single stringt*/
                var headers = request.getAllResponseHeaders( );
                var div = document.getElementById("msgDisplay");
                if(st){
                  setStyle(st,div.style);
                } else {
                  setStyle("plain",div.style);
                }
                div.innerHTML="<pre>"+headers+"</pre>";
            } else {
                //request.status is 503  if the application isn't available; 500 if the application has a bug
                alert(request.status);
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
