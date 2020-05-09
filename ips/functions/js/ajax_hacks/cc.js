var finalCnumber;

window.onload=function(){
    var csc = document.getElementById("csc");
    if(csc != null) {
        csc.onblur=function(){
            var typ = document.getElementById("cctype");
            if(typ != null){
               if(csc.value.indexOf("Choose") == -1 && ! checkCSC(typ.value,csc.value.
                       replace(/\s/,""))) {
                   eMsg("Please enter a valid value for the security code.","red");
                   csc.focus();
                   document.getElementById("submit").disabled=true;
               }  else {
                   clearMsg();
                   document.getElementById("submit").disabled=false;

               }
            }
        };
    }
    document.forms[0].onsubmit=function(){
        verify(this.cc.value,this.scode.value,this.cctype.value,
                this.exp_month.value+" "+this.exp_year.value);
        return false;
    };
}

function checkCSC(cardTyp,fldValue){
    var re = null;
    if(cardTyp != null){
        if(cardTyp == "American Express"){
            re = /^\d{4}$/;
            return re.test(fldValue);
            //Mastercard, Visa, Discover
        } else {
             re = /^\d{3}$/;
            return re.test(fldValue);

        }
    }

}
//credit-card number, security code, credit-card type, and expiration date
function verify(ccard,secure_code,cctype,ccexp){
    if(secure_code.length < 3) {
        eMsg("Please enter a valid value for the security code.","red");
        return;}
    if(cctype=="Choose one...") {
        eMsg("Please enter a valid value for the credit card type.","red");
        return;}
    if (! clientsideVerify(ccard)) {
        eMsg("Please enter a valid value for the credit card.","red");}
    else{
        eMsg("Please wait while we process the credit card.","blue");
        ccard=remDashSpace(ccard);
        url="http://www.parkerriver.com/s/verify?cc="+
            encodeURIComponent(ccard)+"&scode="+
            encodeURIComponent(secure_code)+"&type="+
            encodeURIComponent(cctype)+"&exp="+
            encodeURIComponent(ccexp);
        httpRequest("GET",url,true,handleCheck);
    }
}
/* Check whether the credit-card entry is null, not lengthy enough,
  or contains any letters. Remove any dashes or spaces from the entry, then
   run the Luhn algorithm on the resulting number. */
function clientsideVerify(ccVal){
    if(ccVal == null || ccVal.length < 13 ||
       ccVal.search(/[a-zA-Z]+/) != -1){ return false; }
    ccVal=remDashSpace(ccVal);
    return (applyLuhn(ccVal) % 10) == 0;

}
function remDashSpace(_number){
    //remove dashes or spaces
    _number = _number.replace(/-/g,"");
    _number = _number.replace(/ /g,"");
    return _number;
}
//http://en.wikipedia.org/wiki/Luhn_formula
function applyLuhn(cc){
    //reverse the String
    var rev = reverse(cc);
    //get array of character Strings
    var revArr = rev.split("");
    var total = 0;
    var tmp = 0;
    //add up the numbers
    for(var i = 0; i < revArr.length; i++){
        if((i % 2) > 0){
            tmp = revArr[i]*2;
            tmp= (tmp < 9 ? tmp : (tmp - 9) );
            total += tmp;
        }   else {
            total += Number(revArr[i]);
        }
    }//end for
    alert(total);
    return total;
}
//event handler for XMLHttpRequest
function handleCheck(){
    var sTag,answer,xmlReturnVal;
    if(request.readyState == 4){
        if(request.status == 200){
            //Implement document object in DOM
            xmlReturnVal = request.responseXML;
            sTag = xmlReturnVal.getElementsByTagName("cc_status")[0];
            answer= sTag.childNodes[0].data;
            if(answer=="okay"){ eMsg("Your purchase information has"+
                                     " been submtted to our online store.","blue");  }
            else { eMsg("There was a problem with processing the credit card.","red"); }
        } else {
            alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
        }
    }//end outer if
}
/* Utility functions:
reverse a string. */
function reverse(str){

    var sArray = str.split("");
    var newS="";
    for(var i = sArray.length-1; i >= 0; i--){
        newS += sArray[i];
    }
    return newS;
}
/*Generate a styled message */
function eMsg(msg,sColor){
    var div = document.getElementById("message");
    div.style.color=sColor;
    div.style.fontSize="0.9em";
    //remove old messages
   clearMsg();
   div.appendChild(document.createTextNode(msg));
}
function clearMsg(){
   document.getElementById("message").innerHTML="";
}