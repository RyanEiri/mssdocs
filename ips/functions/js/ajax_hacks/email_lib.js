/* Define an Email constructor*/
function Email(e){
    this.emailAddr=e;
    this.message="";
    this.valid=false;
}

function validate(){
    //do a basic check for null, zero-length string, ".", "@",
    //and the absence of spaces
    if (this.emailAddr == null || this.emailAddr.length == 0 ||
        this.emailAddr.indexOf(".") == -1 ||
        this.emailAddr.indexOf("@") == -1 ||
        this.emailAddr.indexOf(" ") != -1){
        this.message="Make sure the email address does not contain any spaces "+
                     "and is otherwise valid (e.g., contains the \"commercial at\" @ sign).";
        this.valid=false;
        return;
    }

    /*Get the user; they cannot begin or end with a "."
Regular expression specifies: the group of characters before the @ symbol, which
are made up of word characters, followed by zero or one period char,
followed by at least 2 word characters. */
    regex=/(^\w{2,}\.?\w{2,})@/;
    _match = regex.exec(this.emailAddr);

    if ( _match){
        user=RegExp.$1;
        //alert("user: "+user);
    } else {
        this.message="Make sure the user name is more than two characters"+
                     ", does not begin or end with a period (.), or is not otherwise "+
                     "invalid!";
        this.valid=false;
        return;
    }
    //get the domain after the @ char
    //first take care of domain literals like @[19.25.0.1] however rare
    regex=/@(\[\d{1,3}\.\d{1,3}\.\d{1,3}.\d{1,3}\])$/;
    _match = regex.exec(this.emailAddr);

    if( _match){
        domain=RegExp.$1;
         //alert("domain: "+domain);
        this.valid=true;
    } else {
        /*the @ character followed by at least 2 chars that are not a period (.),
followed by a period, followed by zero or one instances of at least two
characters ending with a period, followed by two-three chars that are not periods */
        regex=/@(\w{2,}\.(\w{2,}\.)?[a-zA-Z]{2,3})$/;
        _match = regex.exec(this.emailAddr);
        if( _match){
            domain=RegExp.$1;
        } else {
            this.message="The domain portion of the email had less than 2 chars "+
                         "or was otherwise invalid!";
            this.valid=false;
            return;
        }
    }//end domain check
    this.valid=true;

}

//Make validate() an instance method of the Email object
Email.prototype.validate=validate;
