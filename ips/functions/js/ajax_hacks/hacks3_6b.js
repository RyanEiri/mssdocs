window.onload=function(){
    var sts = document.getElementById("sts");
    sts.onclick=function(){
        var cit = document.getElementById("city");
        if(cit.value) {getZipcode(cit.value,sts.value.toUpperCase());}

    };
};

function getZipcode(_ct,_st){
    if(_ct.length > 0 && _st.length > 0){
        httpRequest("GET","http://www.parkerriver.com/s/zip?city="+
                          encodeURIComponent(_ct)+"&state="+
                          encodeURIComponent(_st),
                true,handleResponse);
    } else {
        document.getElementById("zip5").value="";
    }
}

function handleResponse(){
    var xmlReturnVal;
    try{
         if(request.readyState == 4){
            if(request.status == 200){
                xmlReturnVal=request.responseXML;
                if(xmlReturnVal != null)  {
                    var zip5=xmlReturnVal.getElementsByTagName("zip")[0];
                    if(zip5 && zip5.childNodes.length > 0) {
                        document.getElementById("zip5").value=zip5.childNodes[0].data;
                    }
                }
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