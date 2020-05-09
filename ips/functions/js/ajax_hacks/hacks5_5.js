window.onload=function(){
    JsBikeJavaBean.toJSON(function(javaStr){
        alert(javaStr);
        var div = document.getElementById("bean");
        //remove old content
        div.innerHTML="";
        var javaObj = new Function("return "+javaStr)();
        var   innerHt="<p>Property names and product codes:</p>";

        for(var propName in javaObj) {
            innerHt += "<p>";
            innerHt += "<strong>";
            innerHt += propName;
            innerHt += "</strong> : ";
            innerHt += javaObj[propName];
            innerHt += "</p>";
        }
        div.innerHTML=innerHt;
    });
};
