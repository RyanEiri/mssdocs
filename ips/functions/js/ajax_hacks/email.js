var user,domain, regex, _match;

window.onload=function(){
    document.forms[0].onsubmit=function() {
        checkAddress(this.email.value);
        return false;
    };
};
/* Define an Email constructor*/
function Email(e){
    this.emailAddr=e;
    this.message="";
    this.valid=false;
}

function validate(){
    //do a basic check for null, zero-length string, ".", "@",
    //and the absence of spaces
    if (this.emailAddr == null || this.emailAddr.length == 0 ||
        this.emailAddr.indexOf(".") == -1 ||
        this.emailAddr.indexOf("@") == -1 ||
        this.emailAddr.indexOf(" ") != -1){
        this.message="Make sure the email address does not contain any spaces "+
                     "and is otherwise valid (e.g., contains the \"commercial at\" @ sign).";
        this.valid=false;
        return;
    }

    /*Get the user; they cannot begin or end with a "."
Regular expression specifies: the group of characters before the @ symbol, which
are made up of word characters, followed by zero or one period char,
followed by at least 2 word characters. */
    regex=/(^\w{2,}\.?\w{2,})@/;
    _match = regex.exec(this.emailAddr);

    if ( _match){
        user=RegExp.$1;
        alert("user: "+user);
    } else {
        this.message="Make sure the user name is more than two characters"+
                     ", does not begin or end with a period (.), or is not otherwise "+
                     "invalid!";
        this.valid=false;
        return;
    }
    //get the domain after the @ char
    //first take care of domain literals like @[19.25.0.1] however rare
    regex=/@(\[\d{1,3}\.\d{1,3}\.\d{1,3}.\d{1,3}\])$/;
    _match = regex.exec(this.emailAddr);

    if( _match){
        domain=RegExp.$1;
         //alert("domain: "+domain);
        this.valid=true;
    } else {
        /*the @ character followed by at least 2 chars that are not a period (.),
followed by a period, followed by zero or one instances of at least two
characters ending with a period, followed by two-three chars that are not periods */
        regex=/@(\w{2,}\.(\w{2,}\.)?[a-zA-Z]{2,3})$/;
        _match = regex.exec(this.emailAddr);
        if( _match){
            domain=RegExp.$1;
            alert("domain: "+domain);
        } else {
            this.message="The domain portion of the email had less than 2 chars "+
                         "or was otherwise invalid!";
            this.valid=false;
            return;
        }
    }//end domain check
    this.valid=true;

}

//Make validate() an instance method of the Email object
Email.prototype.validate=validate;

function eMsg(msg,sColor){
    var div = document.getElementById("message");
    div.style.color=sColor;
    div.style.fontSize="0.9em";
    //remove old messages
    if(div.hasChildNodes()){
        div.removeChild(div.firstChild);
    }
    div.appendChild(document.createTextNode(msg));

}
//a pull-it-all-together function
function checkAddress(val){
    var eml = new Email(val);
    var url;
    eml.validate();
    if (! eml.valid) {eMsg(eml.message,"red")};
    if(eml.valid)
    {
        //www.parkerriver.com
        url="http://www.parkerriver.com/s/checker?email="+
            encodeURIComponent(val);
        httpRequest("GET",url,true,handleResponse);
    }
}
//event handler for XMLHttpRequest
function handleResponse(){
    var usedTag,answer,xmlReturnVal;
    if(request.readyState == 4){
        if(request.status == 200){
            //Implement document object in DOM
            xmlReturnVal = request.responseXML;
            usedTag = xmlReturnVal.getElementsByTagName("is_used")[0];
            answer= usedTag.childNodes[0].data;
            if(answer==true){
                eMsg("The user name you have chosen is not available. Kindly try again.",
                        "red");  }
            else { eMsg("Your new user name has been saved.","blue"); }
        } else {
            alert("A problem occurred with communicating between the "+
                  "XMLHttpRequest object and the server program.");
        }
    }//end outer if
}