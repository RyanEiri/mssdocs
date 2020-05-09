window.onload=function(){
   var sts = document.getElementById("sts");
   sts.onclick=function(){
   var cit = document.getElementById("city");
   if(cit.value) {getZipcode(cit.value,sts.value);}

        };
};

function getZipcode(_ct,_st){
     if(_ct == "Greenbelt" && _st.toUpperCase() == "MD"){
     httpRequest("GET","http://localhost:8080/parkerriver/s/zipper",
           true,handleResponse);
    } else {
        document.getElementById("zip4").value="";
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
                    var zip4=xmlReturnVal.getElementsByTagName("Zip4")[0];
                    var zip5=xmlReturnVal.getElementsByTagName("Zip5")[0];
                    if(zip4) {
                        document.getElementById("zip4").value=zip4.childNodes[0].data;
                    }
                     if(zip5) {
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