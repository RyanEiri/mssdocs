var timeoutId, request;
var hostName="192.168.0.3";//192.168.0.3"10.0.1.3;

//a pull-it-all-together function
function execute(val){
url="http://"+hostName+":8080/parkerriver/s/checker?email="+encodeURIComponent("brucewperry@comcast.net");
httpRequest("GET",url,true);
}

//event handler for XMLHttpRequest
function handleCheck(){
  if(request.readyState == 4){
    clearTimeout(timeoutId);
    if(request.status == 200){
      //Implement document object in DOM
      xmlReturnVal = request.responseText;
      alert(xmlReturnVal);
     } else {
      alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
    }
  }//end outer if
 }
 
 function warn(){
   request.abort();
   alert("A problem occurred with communicating with the server program. Please make sure you are connected to the Internet and try again in a few moments.");
 }
 
 
 /* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
   /* Specify the function that will handle the HTTP response */
   request.onreadystatechange=handleCheck; 
   request.open(reqType,url,bool);
   timeoutId = setTimeout(warn,10000);
   request.send(null);
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
        initReq(reqType,url,asynch);
    } else if (window.ActiveXObject){
        request=new ActiveXObject("Msxml2.XMLHTTP");
        if (! request){
            request=new ActiveXObject("Microsoft.XMLHTTP");
        }
        if(request){
            initReq(reqType,url,asynch);
        }
    } else {
        alert("Your browser does not permit the use of all of this application's features!");}
}
