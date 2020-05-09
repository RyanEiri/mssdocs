var queryString="";

window.onload=function(){
    var _url="http://www.parkerriver.com/s/em";
    var b1 = document.getElementById("b1");
    if(b1){
        b1.onclick=function(){
            //clear any existing messages
            clearMsg(document.getElementById("err"),"");
            clearMsg(document.getElementById("msg"),"");
            var isValidEmail=validate(document.getElementById("sender").value,
                    document.getElementById("receiver").value);
            if(isValidEmail){
                try{
                    showMsg(document.getElementById("msg"),"Sending email...");
                    setQueryString();
                    httpRequest("POST",_url,true,handleResponse,queryString);
                } catch(errv) {
                    alert("Sorry, but we failed to send the email because "+
                          "of this error: "+errv.message);
                }
            }

        }; //end function
    }

}
//validate however many email addresses have been
//passed in as arguments using the Arguments array.
//break and return false if one of them is invalid
function validate(em1) {
    var bool = false;
    var eml = null;
    for(var i = 0; i < arguments.length; i++)  {
        eml = new Email(arguments[i]);
        eml.validate();
        bool=eml.valid;
        if(! bool) {  showMsg(document.getElementById("err"),
                eml.message);break;}
    }
    return bool;
}
function showMsg(_id,txt){
    if(_id && txt){_id.innerHTML=txt;}
}
function clearMsg(_id){
    if(_id){_id.innerHTML="";}
}
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseXML;
                if(resp != null){
                    var outc=resp.getElementsByTagName("outcome")[0];
                    if(outc.childNodes[0].nodeValue == "true") {
                        showMsg(document.getElementById("msg"),
                                "Your email is on its way!");
                    }   else {
                        showMsg(document.getElementById("msg"),
                                "The email could not be sent at this time.");}
                }   else {
                    showMsg(document.getElementById("msg"),
                            "The email could not be sent at this time.");
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