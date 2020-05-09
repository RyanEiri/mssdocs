window.onload=function(){
    var _url="http://www.parkerriver.com/energy.jsp?priceTyp=";
    if($("fuelType")){
        $("fuelType").onchange=function(){
            try{
                showQuote(document.getElementById("msg"),"Fetching energy price...");
                httpRequest("GET",_url+$F("fuelType"),true,handlePrice);
            } catch(errv) {
                alert("Sorry, but we failed to get the energy price because "+
                      "of this error: "+errv.message);
            }

        };
    }

}
function showQuote(_id,txt){
    if(_id && txt){_id.innerHTML=txt;}
}
function handlePrice(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseText;
                if(resp != null && resp.length > 0){
                    showQuote($("msg"),"The requested price is: "+resp);
                }   else {
                    showQuote(document.getElementById("msg"),
                            "The price is not available at this time.");
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