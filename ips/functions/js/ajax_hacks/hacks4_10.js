var _host="www.parkerriver.com"; //localhost
var _fhost="www.parkerriver.com";  //localhost:8080
var _fpath="/";///parkerriver
var _path="/";///parkerriver
//Cookie object definition
function MyCookie(name,val,domain,
                  path) {
    this.name=name;
    this.value=val;
    this.domain=domain;
    this.path=path;
    //the cookie lives for three days by default
    var dtsec=new Date();
    dtsec.setSeconds(dtsec.getSeconds()+(60*60*24*3));
    this.expires=dtsec.toGMTString();
    this.toString=function(){
        return this.name+"="+this.value+"; expires="+this.expires+
               "; path="+this.path+"; domain="+
               this.domain;
    }
}
window.onload=function(){
    var b1 = document.getElementById("ckCreate");
    var b2 = document.getElementById("ckView");
    var b3 = document.getElementById("ckSend");
    var _url="";
    if(b1 && b2 && b3){
        b1.onclick=function(){
            var nm = document.getElementById("ck_nm");
            var v=document.getElementById("ck_val");
            try{
                if(nm && nm.value && v && v.value)   {
                    var cook=new MyCookie(escape(nm.value),v.value,_host,_path);
                   // alert(cook.toString());
                    document.cookie=cook.toString();
                    showMsg(document.getElementById("msg"),
                            "Cookie creation was successful.");
                }
            } catch(errv) {
                alert("Sorry, but we failed to create a cookie because "+
                      "of this error: "+errv.message);
            }

        }
        b2.onclick=function(){
            location.href="http://"+_fhost+_fpath+"/s/ckreader";
        }
        b3.onclick=function(){
            _url="http://"+_fhost+_fpath+"/s/ckserv";
            httpRequest("POST",_url,true,function(){},
                    "allcookies="+encodeURIComponent(document.cookie));
        }
    }
    _url="http://"+_fhost+_fpath+"/s/ckserv";
    httpRequest("GET",_url,true,handleInit);
}
function showMsg(_id,txt){
     if(_id && txt){_id.innerHTML=txt;}
}
function handleInit(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseXML;
                if(resp != null){
                    var outcome=resp.getElementsByTagName("outcome")[0];
                    var msg = document.getElementById("msg");
                    if(outcome != null){
                        if(outcome.childNodes[0].nodeValue != "success")  {
                            showMsg(msg,"Initial Cookie creation was not successful.");
                        }    
                    }
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