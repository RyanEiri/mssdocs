window.onload=function(){
    var but = document.getElementById("timer");
    if(but != null){
        but.onclick=function(){
            var _url =
                 "http://www.parkerriver.com/s/sender?text=hello%20there";
            var dt=new Date().getTime();
            httpRequest("GET",_url,false,handleResponse);
            alert("Send() finally completed after "+((new Date().
					getTime() - dt) / 1000)+
                  " seconds!!");
        };  }

}
//event handler for XMLHttpRequest
function handleResponse(){
    try{

        if(request.readyState == 4){
            if(request.status == 200){
                var msg = document.getElementById("response_msg");
                if(msg) {
                    var cnt = "";
                    cnt += "<pre>";
                    cnt += request.responseText;
                    cnt += "</pre>";
                    msg.innerHTML  = cnt;
                }
            } else {
                //request.status is 503
                //if the application isn't available;
                //500 if the application has a bug
                alert(
                        "A problem occurred with communicating between "+
                        "the XMLHttpRequest object and the server program.");
            }
        }//end outer if
    } catch (err)   {
        alert("It does not appear that the server is "+
              "available for this application. Please"+
              " try again very soon. \nError: "+err.message);

    }
}
