var sportTyp = "";
var checksArray = null;
var request=null;
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
    //Place all existing checkbox elements in two arrays
    //for restoring the original checkbox lists
    checksArray = new Object();
    checksArray.team = new Array();
    checksArray.individual = new Array();
    var ckArr = document.getElementsByTagName("input");
    populateArray(ckArr,"team");
    populateArray(ckArr,"individual");
}

function populateArray(arr,typ)  {
    var inc = 0;
    for(var i = 0; i < arr.length; i++){
        if(arr[i].type == "checkbox") {
            if(arr[i].name.indexOf(typ) != -1) {
                checksArray[typ][inc] = arr[i];
                inc++;
            }
        }
    }
}
//Return the number of checkbox elements contained
//by a div element
function getCheckboxesLength(_sportTyp){
    var div = document.getElementById(_sportTyp+"_d");
    var len=0;
    for(var i =0; i < div.childNodes.length; i++){
        if(div.childNodes[i].nodeName == "INPUT" ||
           div.childNodes[i].nodeName == "input" ){
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
    //determine whether the checkboxes have already been expanded
    if(checksArray[sportTyp].length < getCheckboxesLength(sportTyp))  {
        return;
    }
    url = "http://www.parkerriver.com/s/expand?expType="+val;
    httpRequest("GET",url,true);
}

function addToChecks(obj){
    //div element that contains the checkboxes
    var div = document.getElementById(sportTyp+"_d");
    var el = null;
    //Now add the new checkboxes derived from the server
    for(var h = 0; h < obj.length; h++){
        el = document.createElement("input");
        el.type="checkbox";
        el.name=sportTyp+"_sports";
        el.value=obj[h];
        div.appendChild(el);
        div.appendChild(document.createTextNode(obj[h]));
        div.appendChild(document.createElement("br"));
    }
}

function restore(_sel) {
    var val;
    var opts =  _sel.options;
    for (var i = 0; i < opts.length; i++){
        if(opts[i].selected) { val=opts[i].value; break;}
    }
    //Only restore if the checkboxes have
    //already been expanded
    if(checksArray[sportTyp].length < getCheckboxesLength(sportTyp)) {
        var _div = document.getElementById(val+"_d");
        if(_div != null)  {
            //rebuild the list of original checkboxes
            _div.innerHTML="";
            var tmpArr = checksArray[val];
            for(var j = 0; j < tmpArr.length; j++){
                _div.appendChild(tmpArr[j]);
                _div.appendChild(document.createTextNode(tmpArr[j].value));
                _div.appendChild(document.createElement("br"));
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
                    //return value is a JSON object
                    addToChecks(eval(resp));
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
/* Initialize a Request object that is already constructed */
function initReq(reqType,url,bool){
    try{
        /* Specify the function that will handle the HTTP response */
        request.onreadystatechange=handleResponse;
        request.open(reqType,url,bool);
        request.send(null);
    } catch (errv) {
        alert(
                "The application cannot contact "+
                "the server at the moment. "+
                "Please try again in a few seconds." );
    }
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
