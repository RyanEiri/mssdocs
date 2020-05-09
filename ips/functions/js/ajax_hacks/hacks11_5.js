window.onload=function(){
    if(document.styleSheets && document.styleSheets.length > 0 &&
       document.styleSheets[0].cssRules &&
       document.styleSheets[0].cssRules.length > 0) {
        document.styleSheets[0].cssRules[0].style.visibility="visible";
        var hrefs = document.getElementsByTagName("a");
        var _url;
        if(hrefs != null){
            for(var i = 0; i < hrefs.length; i++) {
                hrefs[i].onclick=function(){
                    _url=
                    "http://www.parkerriver.com/s/fav_sports?sportType="+
                    this.id+"&col=y";
                    httpRequest("GET",_url,true,handleResponse);
                    return false;
                };
            }
        }
    } else if (document.getElementById){
        //for IE6, which returns undefined from the
        //document.styleSheets[0].cssRules reference
        var lnk =document.getElementById("lnks");
        lnk.style.visibility="visible";
        //now implement the above hrefs and httpRequest() code...
    }
};
//event handler for XMLHttpRequest
function handleResponse(){
    try{

        if(request.readyState == 4){
            if(request.status == 200){
                //return value is a JavaScript array
                var resp=eval(request.responseText);
                var _div = document.getElementById("results");
                var _innerHt = "";
                for(var i=0; i < resp.length; i++) {
                    _innerHt += resp[i];
                    _innerHt += i == resp.length-1 ? ""  : "<br />";
                }
                _div.innerHTML= _innerHt;
            } else {
                //request.status is 503
                //if the application isn't available;
                //500 if the application has a bug
                alert(
                        "A problem occurred with communicating between "+
                        "the XMLHttpRequest object and the server program.");
            }
        }//end outer if
    } catch (err)   {
        alert("It does not appear that the server is "+
              "available for this application. Please"+
              " try again very soon. \nError: "+err.message);

    }
}
