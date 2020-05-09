window.onload=function(){
    var hid = document.getElementById("hid");
    var val = "navprops="+encodeURIComponent(hid.value);
    url = "http://www.parkerriver.com/s/hid";
    httpRequest("POST",url,true,handleResponse,val);

}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                alert("Request went through okay...");
            } else {
                //request.status is 503
                //if the application isn't available;
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