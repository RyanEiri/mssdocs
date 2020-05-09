window.onload=function(){
    if($("name_info")) {
        $("name_info").onblur=function(){

            if($F("name_info")){
                _url="http://localhost:3000/hacks/proto?inf="+
                     $F("name_info");
                showInfo(_url);
            }
        }
    }
    if($("but1")) {
        $("but1").onclick=function(){

            if($F("name_info")){
                _url="http://localhost:3000/hacks/proto";
                showUpInfo(_url);
            }
        }
    }
    // insertion:Insertion.Top,
    var counter=0;
    var _uurl="http://localhost:8080/parkerriver/s/date"
    var executor= null;
    if($("time_info") && $("display_area")) {
        executor=new PeriodicalExecuter(function() {
            new Ajax.Updater('time_info',_uurl,
            {asynchronous:true, evalScripts:true,  insertion:Insertion.Top,
                onFailure: function(request){$("msg").style.color="red";
                    $("msg").innerHTML="Request conked out; request status="+
                                       request.status},
                onComplete: function(request){$("msg").style.color="green"; counter++;
                    $("msg").innerHTML="Update #"+counter+"; request status="+
                                       request.status}})}, 5);

        new Form.Element.Observer("time_info",10,function(){
            $("display_area").value+=("Change detected on: "+new Date() + "\n\n");
        });
        
    }

};

function showInfo(go_url){
    try{
        if($("display_area") && go_url){
            var xmlHttp=   new Ajax.Request(go_url, {method: "GET",
                parameters:Form.serialize(document.forms[0]),
                onComplete:function(){
                    $("display_area").value=xmlHttp.transport.responseText;
                    document.getElementById("msg").innerHTML="Some response and server information..."},
                onFailure:function(){$("display_area").value="XMlHttpRequest failed..."}});
        }
    } catch (err) {
        alert("showInfo() exception: "+err.message) ;
    }
}

function showUpInfo(go_url){
    try{
        if($("display_area") && go_url){
            var xmlHttp=   new Ajax.Updater("display_area",go_url, {
                parameters:Form.serialize(document.forms[0]),
                onComplete:function(){
                    $("display_area").value=xmlHttp.transport.responseText;
                    document.getElementById("msg").innerHTML="Some response and server information..."},
                onFailure:function(){$("display_area").value="XMlHttpRequest failed..."}});
        }
    } catch (err) {
        alert("showInfo() exception: "+err.message) ;
    }
}

