var lgn="";
window.onload=function(){
    if(navigator.language) {
        lgn=navigator.language;
    }  else if(navigator.userLanguage) {
        lgn=navigator.userLanguage;
    }
    if(lgn.length >= 2){
        displayLanguage(lgn);
    } else {
        showMsg(document.getElementById("msg"),
                "Sorry, no language information is available "+
                "from your browser.");
    }
    var b1 = document.getElementById("b1");
    if(b1) {
        b1.onclick=function(){
            var lg = lgn.substring(0,2);
            var ct = lgn.length > 2 ? lgn.substring(3) : "";
            var _url="http://www.parkerriver.com/s/lang?lang="+
                     lg+"&country="+ct;
            httpRequest("GET",_url,true,handleResponse);
        }
    }
}
function showMsg(_id,txt){
    if(_id && txt){_id.innerHTML=txt;}
}
function clearMsg(_id){
    if(_id){_id.innerHTML="";}
}
function displayLanguage(_code){
    showMsg(document.getElementById("country"),
            "<strong>"+_code+"</strong>");
}
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseXML;
                if(resp != null){
                    var intl=resp.getElementsByTagName("intl")[0];
                    var c= intl.getElementsByTagName("country")[0];
                    var l= intl.getElementsByTagName("language")[0];
                    var lval="";
                    var cval="";

                    if(l.hasChildNodes()){lval=l.childNodes[0].nodeValue;}
                    if(c.hasChildNodes()){cval=c.childNodes[0].nodeValue;}

                    if(lval && cval) {
                        showMsg(document.getElementById("msg"),
                                "<strong>"+lval+
                                "_"+cval+"</strong>");
                    } else if (lval && ! cval) {
                        showMsg(document.getElementById("msg"),
                                "<strong>"+lval+"</strong>");
                    }  else if (! lval && cval){
                        showMsg(document.getElementById("msg"),
                                "<strong>"+cval+"</strong>");
                    }
                }   else {
                    showMsg(document.getElementById("msg"),
                            "The language info could not be accessed at this time.");
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