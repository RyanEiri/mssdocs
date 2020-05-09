window.onload=function(){
    if($("w_update")){
        $("w_update").onclick=function(){
            updateWeather();
        }
    }
};

function updateWeather(){
    //ajaxEngine.registerRequest("multiple", "/parkerriver/s/wdisp");
    ajaxEngine.registerRequest("multiple", "/weather.jsp");
    ajaxEngine.registerAjaxElement("boston");
    ajaxEngine.registerAjaxElement("boulder");
    ajaxEngine.registerAjaxElement("portland");
    ajaxEngine.registerAjaxElement("seattle");
    ajaxEngine.sendRequest("multiple","");
}
