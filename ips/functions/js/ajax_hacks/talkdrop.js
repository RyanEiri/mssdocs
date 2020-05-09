window.onload=function(){
    dndMgr.registerDraggable( new MyDraggable('b1','firstbook'));
    dndMgr.registerDraggable( new MyDraggable('b2','book2') );
    dndMgr.registerDraggable( new MyDraggable('b3','book3') );
    dndMgr.registerDraggable( new MyDraggable('b4','book4') );
    dndMgr.registerDraggable( new MyDraggable('b5','book5') );
    dndMgr.registerDraggable( new MyDraggable('b6','book6') );
    dndMgr.registerDropZone( new Rico.Dropzone('basket') );
    dndMgr.registerDropZone( new Rico.Dropzone('shelf') );
};

