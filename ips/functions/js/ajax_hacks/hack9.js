var request;
var symbol;   //will hold the stock symbol

function getStockPrice(sym){
    symbol=sym;
    if(sym)  {
    var url="http://www.parkerriver.com/s/stocks?symbol="+sym;
    httpRequest("GET",url,true);   }
}

//event handler for XMLHttpRequest
function handleResponse(){
    if(request.readyState == 4){
        if(request.status == 200){
            /*Grab the result as a string*/
            var stockPrice = request.responseText;
            var info = "&#171;The  price is: $"+stockPrice+"&#187;";
                document.getElementById("stPrice").style.fontSize="0.9em";
                document.getElementById("stPrice").style.backgroundColor="yellow";
                document.getElementById("stPrice").innerHTML =info;
            
        } else {
            alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
        }
    }//end outer if
}

/* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
    /* Specify the function that will handle the HTTP response */
    request.onreadystatechange=handleResponse;
    request.open(reqType,url,bool);
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