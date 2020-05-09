window.onload=function(){ callSetups();};

function callSetups(){
  setupSelect();
  setupMap();
    setupList(document.getElementById("hid").value);
    setupDates();
}
function setupSelect(){
    JsBikeBean.getDesignerInfo(populate);
}
function populate(list){
    DWRUtil.removeAllOptions("bikes");
    DWRUtil.addOptions("bikes", list);
}
function setupList(typ){
    JsBikeBean.getDesignerInfo(function(list) {
        var div = document.getElementById("orlist");
        var el = null;
        if(div != null){
            //remove any existing lists
            div.innerHTML="";
            if(typ.indexOf("un") == -1) {
                //create an ordered list
                el=document.createElement("ol");
            }   else {
                //create an unordered list
                el=document.createElement("ul");
            }
            el.setAttribute("id","servlist");
            div.appendChild(el);
            //create li elements from server information
            DWRUtil.addOptions("servlist", list);
        }
    });
}

function setupMap(){
    JsBikeBean.getBikeInfo(setProdCodes);
}
function setProdCodes(jsHashmap){
    //"jsHashmap" is the JS object representation of a HashMap

    var div = document.getElementById("prodCodes");
    //remove old messages
    div.innerHTML="";
    div.style.color="purple";
    div.style.fontSize="0.9em";
    var tmpText;
    for(var prop in jsHashmap) {
          tmpText = prop + " :: "+ jsHashmap[prop];
           div.appendChild(document.createTextNode(tmpText));
            div.appendChild(document.createElement("br"));
    }
    /*JsBean.getDesignerInfo(function(arrayOfNames){
        var tmpText;
        for(var i = 0; i < arrayOfNames.length; i++){
            tmpText = arrayOfNames[i] + " :: "+ data[arrayOfNames[i]];
            div.appendChild(document.createTextNode(tmpText));
            if (i != arrayOfNames.length-1){
                div.appendChild(document.createElement("br")); }
        }
    });*/
}

function setupDates(){
    var div = document.getElementById("showDates");
    //remove old messages
    div.innerHTML="";
    //define callback function for displaying a local date
    JsDate.toLocaleString(function(dateString){
        div.appendChild(document.createTextNode("Your local date: "+
                                                dateString));
        div.appendChild(document.createElement("br"));
    });
    //define callback function for displaying Greenwich Mean Time
    JsDate. toGMTString(
            function(dateString){
                div.appendChild(document.createTextNode(
                        "Greenwich Mean Time date: "+
                        dateString));
                div.appendChild(document.createElement("br"));});

    JsDate.getTimezoneOffset(
            function(dateString){
                div.appendChild(document.createTextNode(
                        "The difference between your time and GMT (in minutes): "+
                        dateString));
                div.appendChild(document.createElement("br"));
            }
            );
}