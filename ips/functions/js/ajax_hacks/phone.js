window.onload=function(){
    //Grab form objects
    var b_phone = document.getElementById("b_phone");
    var f_phone = document.getElementById("f_phone");
    var h_phone = document.getElementById("h_phone");
    var c_phone = document.getElementById("c_phone");
    var toll_free_phone = document.getElementById("toll_free_phone");
    //Save initial values for comparison
    var initB_phone = b_phone.value;
    var initF_phone = f_phone.value;
    var initH_phone = h_phone.value;
    var initC_phone = c_phone.value;
    var initToll_free_phone = toll_free_phone.value;

    b_phone.onblur=function(){
    	var message = "message_b";
        if(this.value) {
	    chkPhone(b_phone.value,message);
	} else { 
//	    sendPostData('http://devel/contacts/body/change.php');
	    document.getElementById(message).innerHTML="";
	}
    };

    f_phone.onblur=function(){
        var message = "message_f";
        if(this.value) {chkPhone(f_phone.value,message);} else { document.getElementById(message).innerHTML="";}
    };

    h_phone.onblur=function(){
        var message = "message_h";
        if(this.value) {chkPhone(h_phone.value,message);} else { document.getElementById(message).innerHTML="";}
    };

    c_phone.onblur=function(){
        var message = "message_c";
        if(this.value) {chkPhone(c_phone.value,message);} else { document.getElementById(message).innerHTML="";}
    };

    toll_free_phone.onblur=function(){
        var message = "message_toll_free";
        if(this.value) {chkPhone(toll_free_phone.value,message);} else { document.getElementById(message).innerHTML="";}
    };

};

function chkPhone(phoneVal,message){
    var re = /^\d{10}$/;
    if(! re.test(phoneVal)) {
        document.getElementById(message).
            innerHTML="<strong>Please enter 10 digits for the telephone number.</strong>";
    }  else {
//    	sendPostData('http://devel/contacts/body/change.php');
        document.getElementById(message).
//            innerHTML="<strong><font color=red>Changed!</font></strong>";
	      innerHTML=" ";
	
    }
}

function chkPhoneOnSubmit(phoneVal){
    var re = /^\d{10}$/;
    if(! re.test(phoneVal)) {
        alert("Please enter 10 digits for the telephone number.");
    } else {
        sendPostData('http://devel/contacts/body/change.php');
    }
}
