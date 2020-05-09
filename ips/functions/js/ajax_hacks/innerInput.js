 var delim = ":::";
 document.write(
      "<input type=\"hidden\" id=\"hid\" name=\"data\"  value=\""+
      location.pathname+delim+new Date()+
      delim+navigator.appName+delim+navigator.platform+
      delim+navigator.language+delim+navigator.userAgent+"\" />");