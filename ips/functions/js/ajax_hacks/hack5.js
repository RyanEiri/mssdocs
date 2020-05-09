var request;
var queryString;   //will hold the POSTed data

function sendData(){
     setQueryString();
    url="http://www.parkerriver.com/s/json";   //www.parkerriver.com
    httpRequest("POST",url,true);
}

//event handler for XMLHttpRequest
function handleJson(){
    if(request.readyState == 4){
        if(request.status == 200){
            var resp =  request.responseText;
            alert(resp);
            var func = new Function("return "+resp);
            var objt = func();
            var div = document.getElementById("json");
            stylizeDiv(resp,div);
            div = document.getElementById("props");
            div.innerHTML="<h4>In object form...</h4><h5>Properties</h5>firstname= "+
                          objt.firstname +"<br />lastname="+
                          objt.lastname+ "<br />gender="+
                          objt.gender+ "<br />country="+
                          objt.country;
        } else {
            alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
        }
    }//end outer if
}

/* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
    /* Specify the function that will handle the HTTP response */
    request.onreadystatechange=handleJson;
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

function stylizeDiv(bdyTxt,div){
    //reset DIV content
    div.innerHTML=" ";
    div.style.fontSize="1.2em";
    div.style.backgroundColor="yellow";
    div.appendChild(document.createTextNode(bdyTxt));
}
