var request;
var queryString;   //will hold the POSTed data

function sendData(){
    setQueryString();
    var url="http://www.parkerriver.com/s/sender";
    httpRequest("POST",url,true);
}

//event handler for XMLHttpRequest
function handleCheck(){
    if(request.readyState == 4){
        if(request.status == 200){
            alert(request.responseText);
        } else {
            alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
        }
    }//end outer if
}

/* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
    /* Specify the function that will handle the HTTP response */
    request.onreadystatechange=handleCheck;
    request.open(reqType,url,bool);
    request.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded; charset=UTF-8");
    request.send(queryString);
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

function setQueryString(){
    queryString="";
    var frm = document.forms[0];
    var numberElements =  frm.elements.length;
    for(var i = 0; i < numberElements; i++)  {
            if(i < numberElements-1)  {
                queryString += frm.elements[i].name+"="+
                               encodeURIComponent(frm.elements[i].value)+"&";
            } else {
                queryString += frm.elements[i].name+"="+
                               encodeURIComponent(frm.elements[i].value);
            }

    }
}
