var request;
var queryString;   //will hold the POSTed data

function sendPostData(url){
    setQueryString();
    httpRequest("POST",url,true)
}

function setQueryString(){
    queryString="";
    var frm = document.forms[0];
    var numberElements = frm.elements.length;
    for(var i = 0; i < numberElements; i++) {
        if(i < numberElements-1) {
	    queryString += frm.elements[i].name+"="+
	                   encodeURIComponent(frm.elements[i].value)+"&";
        } else {
	    queryString += frm.elements[i].name+"="+
	                   encodeURIComponent(frm.elements[i].value);
	}
    }
}

/* Wrapper function for constructing a request object.
 Paramaters:
  reqType: The HTTP request type, such as GET or POST.
  url: The URL of the server program
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
    //the request could still be null if neither AcitveXObject
    //initialization succeeded
    if(request){
        initReq(reqType,url,asynch);
    } else {
        alert("Your browser does not permit the use of all "+
	      "of this application's features!");
    }
}


/* Initialize a request object that is already constructed.
 Parameters:
   reqType: The HTTP request type, such as GET or POST.
   url: The URL of the server program.
   isAsynch: Whether to send the request asynchronously or not. */

function initReq(reqType,url,isAsynch){
    /* Specify the function that will handle the HTTP response */
    request.onreadystatechange=handleResponse;
    request.open(reqType,url,isAsynch);
    /* Set the Content-Type header for a POST request */
    request.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded; charset=UTF-8");
    request.send(queryString);
}


//event handler for XMLHttpRequest
function handleResponse(){
    if(request.readyState == 4){
        if(request.status == 200){
	    alert(request.responseText);
	} else {
	    alert("A problem occurred with communicating between "+
	          "the XMLHttpRequest object and the server program.");
    	}
    }
}

