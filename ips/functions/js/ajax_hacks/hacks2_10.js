var sportType="";
window.onload=function(){
    var rads = document.getElementsByTagName("input");
    if(rads != null) {
        for(var i = 0; i < rads.length; i++) {
            if(rads[i].type=="radio"){ rads[i].onclick=function(){
                getSports(this)};}

        }
    }

}

function getSports(obj){
    if (obj == null ) { return; }
    var url = "";
    var val = "";
    if(obj.checked) {
        val=obj.value;
        sportType=val;
        url = "http://www.parkerriver.com/s/fav_sports"+
              "?sportType="+encodeURIComponent(val)+"&col=y";
        httpRequest("GET",url,true,handleResponse);
    }

}

//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseText;
                if(resp != null){
                    //return value is a JSON array
                    var objt = eval(resp);
                    createBullets(objt);
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

function createBullets(obj){
    var _div = document.getElementById("bullets");
    var innerHTMLstr =  "<ul>";
    //obj is an array of new sports names
    for(var i=0; i < obj.length;i++) {
        innerHTMLstr += "<li>";
        innerHTMLstr += obj[i];
        innerHTMLstr += "</li>";
    }
    innerHTMLstr += "</ul>";
    _div.innerHTML= innerHTMLstr;
}
