window.onload=function(){
    var zip = document.getElementById("zip5");
    var cit = document.getElementById("city");
    zip.onblur=function(){
        if(this.value && cit.value) {chkZipcode(zip.value);}
        //validate actual zip code:
        // httpRequest("GET","http://localhost:8080/parkerriver/s/zip?city="+
        //                       encodeURIComponent(cit)+"&state="+
        //                       encodeURIComponent(_st),
        //             true,handleResponse);
    };
};

function chkZipcode(zipVal){
    var re = /^\d{5}$/;
    if(! re.test(zipVal)) {
        document.getElementById("message").
                innerHTML="<strong>Please enter a valid zip code.</strong>";
    }  else {
        document.getElementById("message").
                innerHTML="";
    }
}

function handleResponse(){
    var xmlReturnVal;
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                xmlReturnVal=request.responseXML;
                if(xmlReturnVal != null)  {
                    //validate entered zip code against this value
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