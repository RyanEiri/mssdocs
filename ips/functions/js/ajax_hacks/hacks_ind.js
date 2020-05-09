window.onload=function(){
    var buts = document.getElementsByTagName("button"), spans;
    for(var i = 0; i < buts.length; i++) {
        buts[i].onclick=function(){
            Element.toggle($(this.id.split("_")[0]));
            spans=this.getElementsByTagName("span");
            for(var j = 0; j < spans.length; j++) {
                if(spans[j].style.fontWeight == "bold"){
                    spans[j].style.fontWeight = "normal";
                }   else {
                    spans[j].style.fontWeight = "bold";
                }
            }

        }
    }
} ;