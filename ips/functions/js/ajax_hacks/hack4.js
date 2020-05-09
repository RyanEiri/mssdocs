var request;
var symbol;   //will hold the stock symbol
var numberOfShares;

function getStockPrice(sym,shs){
    if(sym && shs)  {
        symbol=sym;
        numberOfShares=shs;
        var url="http://www.parkerriver.com/s/stocks?symbol="+sym;
        httpRequest("GET",url,true);
    }
}

//event handler for XMLHttpRequest
function handleResponse(){
    if(request.readyState == 4){
        if(request.status == 200){
            /*Check if the return value is actually a number. If so, multiple by the number
            of shares and display the result*/
            var stockPrice = request.responseText;

            try{
                if(isNaN(stockPrice)) { throw new Error("The returned price is an invalid number.");}
                if(isNaN(numberOfShares)) { throw new Error("The share amount is an invalid number.");}
                var info = "Total stock value: $"+  calcTotal(stockPrice);
                displayMsg(document.getElementById("msgDisplay"),info,"black");
                document.getElementById("stPrice").style.fontSize="0.9em";
                document.getElementById("stPrice").innerHTML ="price: "+stockPrice;
            } catch (err) {
                displayMsg(document.getElementById("msgDisplay"),"An error occurred: "
                        +err.message,"red");
            }
        } else {
            alert("A problem occurred with communicating between the XMLHttpRequest object and the server program.");
        }
    }//end outer if
}

/* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
    /* Specify the function that will handle the HTTP response */
    request.onreadystatechange=handleResponse;
    request.open(reqType,url,bool);
    request.send(null);
}

/* Wrapper function for constructing a Request object.
 Parameters:
  reqType: The HTTP request type such as GET or POST.
  url: The URL of the server program.
  asynch: Whether to send the request asynchronously or not. */
function httpRequest(reqType,url,asynch){
    //Mozilla-based browsers
    if(window.XMLHttpRequest){
        request = new XMLHttpRequest();
    } else if (window.ActiveXObject){
        request=new ActiveXObject("Msxml2.XMLHTTP");
        if (! request){
            request=new ActiveXObject("Microsoft.XMLHTTP");
        }
     }
    //the request could still be null if neither ActiveXObject
    //initializations succeeded
    if(request){
       initReq(reqType,url,asynch);
    }  else {
        alert("Your browser does not permit the use of all "+
        "of this application's features!");}
}

function calcTotal(price){
    return stripExtraNumbers(numberOfShares * price);
}
/*Strip any characters beyond a scale of four characters past the decimal point, as in
12.3454 */
function stripExtraNumbers(num) {
    alert(typeof num)
    //check if the number's already okay
    //assume a whole number is valid
    var numStr =  num.toString();//working with the number as a string
    var indx =numStr.indexOf(".");
    if(indx == -1)  { return num; }
    var chArray = numStr.split(".");
    //the second array member includes all the chars after the decimal point
    if(chArray[1].length <= 4) { return num; }
    //use the Number.toPrecision method to restrict the
    //decimal-point numbers to four
    //the length of the characters prior to the decimal point plus four
    return num.toPrecision(chArray[0].length + 4);
}

function displayMsg(div,bdyText,txtColor){
    //reset DIV content
    div.innerHTML="";
    div.style.backgroundColor="yellow";
    div.style.color=txtColor
    div.innerHTML=bdyText;
}
