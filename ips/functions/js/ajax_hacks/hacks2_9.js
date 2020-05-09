var sportTyp = "";
var itemsArray = null;
//define Object for cacheing li items
//this is a workaround for IE 6, which
//doesn't save the li element's text node
//when you cache it
function CachedLiItem(liElement,liLabel){
    //an li element object
    this.liElement=liElement;
    //a string representing the li text node or label
    this.liLabel=liLabel;
}

window.onload=function(){
    var sel = document.getElementById("expand");
    //bind onclick event handler to a function
    if(sel != null){
        sel.onclick=function(){
            getMoreChoices(this)};
    }
    var selr = document.getElementById("restore");
    //bind onclick event handler to a function
    if(selr != null){
        selr.onclick=function(){
            restore(this)};
    }
    //Place all existing bullet items in two arrays
    //for restoring later
    itemsArray = new Object();
    itemsArray.team = new Array();
    itemsArray.individual = new Array();
    var bulletArr = document.getElementsByTagName("li");
    populateArray(bulletArr,"team");
    populateArray(bulletArr,"individual");
}
//Create Arrays of CachedLiItem objects for
//restoring the unordered lists later
function populateArray(arr,typ)  {
    var inc = 0;
    var el = null;
    var liObj=null;
    for(var i = 0; i < arr.length; i++){
        el = arr[i].parentNode;
        if(el.id.indexOf(typ) != -1) {
            liObj=new CachedLiItem(arr[i],arr[i].childNodes[0].nodeValue);
            itemsArray[typ][inc] = liObj;
            inc++;
        }
    }
}
//Return the number of li elements contained
//by a ul element
function getULoptionsLength(_sportTyp){
    var ul = document.getElementById(_sportTyp+"_u");
    var len=0;
    for(var i =0; i < ul.childNodes.length; i++){
        if(ul.childNodes[i].nodeName == "LI" ||
           ul.childNodes[i].nodeName == "li" ){
            len++;
        }
    }
    return len;
}
function getMoreChoices(obj){
    if (obj == null ) { return; }
    var url = "";
    var optsArray = obj.options;
    var val = "";
    for(var i=0; i < optsArray.length; i++){
        if(optsArray[i].selected) {
            val=optsArray[i].value;  break;
        }
    }
    sportTyp=val;
    //determine whether the bullets have already been expanded
    if(itemsArray[sportTyp].length < getULoptionsLength(sportTyp))  {
        return;
    }
    url = "http://www.parkerriver.com/s/expand?expType="+val;
    httpRequest("GET",url,true,handleResponse);
}

function addToBullets(obj){
    //ul element that contains the bullet items
    var ul = document.getElementById(sportTyp+"_u");
    var el = null;
    //Now add the new items derived from the server
    for(var h = 0; h < obj.length; h++){
        el = document.createElement("li");
        el.appendChild(document.createTextNode(obj[h]));
        ul.appendChild(el);
    }
}

function restore(_sel) {
    var val;
    var opts =  _sel.options;

    for (var i = 0; i < opts.length; i++){
        if(opts[i].selected) { val=opts[i].value; break;}
    }
    sportTyp=val;
    //Only restore the lists if the bullets have
    //already been expanded
    if(itemsArray[sportTyp].length < getULoptionsLength(sportTyp)) {
        var ul = document.getElementById(val+"_u");
        if(ul != null)  {
            //rebuild the list of original bullets
            ul.innerHTML="";
            var tmpArr = itemsArray[val];
            var tmpLiElement = null;
            for(var j = 0; j < tmpArr.length; j++){
                tmpLiElement=tmpArr[j].liElement;
                //workaround for IE6
                if(tmpLiElement.hasChildNodes()){tmpLiElement.
                        removeChild(tmpLiElement.firstChild);}
                tmpLiElement.appendChild(document.
                        createTextNode(tmpArr[j].liLabel))
                ul.appendChild(tmpLiElement);
            }
        }
    }
}
//event handler for XMLHttpRequest
function handleResponse(){
    try{
        if(request.readyState == 4){
            if(request.status == 200){
                var resp =  request.responseText;
                if(resp != null){
                    //return value is a JSON array object
                    addToBullets(eval(resp));
                }
            } else {
                //request.status is 503
                //if the application isn't available;
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