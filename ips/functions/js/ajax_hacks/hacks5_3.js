window.onload=function(){  JsBikeBean.getBikeInfo(populate);};

function populate(map){
    DWRUtil.removeAllOptions("bikes");
    addOptionValues("bikes", map);
}
function addOptionValues(_id,_map){
    //handle the return value as a JS object
  //  var jMap = new Function("return "+_map)();
    var sel = document.getElementById(_id);
    var opt = null;
    if(sel != null){
         for(var prop in _map) {
             opt=document.createElement("option");
             opt.setAttribute("value",_map[prop]);
             opt.appendChild(document.createTextNode(prop));
             sel.appendChild(opt);
         }
    }
}