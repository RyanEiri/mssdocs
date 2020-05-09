var MyDraggable = Class.create();
MyDraggable.prototype = (new Rico.Draggable()).extend( {

   initialize: function( htmlElement, name ) {
      this.type        = 'MyDraggable';
      this.htmlElement = $(htmlElement);
      this.originZone="not defined";
   },

   getContainer: function() {
      var el = this.htmlElement;
      if(el.parentNode) {
          if(el.parentNode.id){
              return  el.parentNode.id;
          }  else {
              return "no_id_"+el.parentNode.nodeName;
          }
      }  else {
          return this.name+"_no_supported_parentNode";
      }
   },

   startDrag: function() {
      var el = this.htmlElement;
       this.originZone=this.getContainer();
   },

   cancelDrag: function() {
      var el = this.htmlElement;
   },

    endDrag: function() {
        if(this.originZone == "shelf" &&
           this.getContainer() == "basket"){
            var bk=this.htmlElement.childNodes[0].id;
            new Ajax.Request("/parkerriver/s/checkout", {method: "get",
                parameters: "book="+bk,
                onComplete:function(request){
                    $("outcome").innerHTML=request.responseText;}});

        }
   }

} );