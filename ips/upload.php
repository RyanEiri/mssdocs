<?php
$dir_base = 'ips';
$html_base = basename($_SERVER['DOCUMENT_ROOT']);
$html_base = 'https://'.$html_base.'/'.$dir_base.'/';
$config_base = $_SERVER['DOCUMENT_ROOT'];
$config_base = $_SERVER['DOCUMENT_ROOT'].'/'.$dir_base.'/';
//require_once ('functions/functions.php');
require_once ($config_base.'classes/classes.php');
require_once ($config_base.'config/config.php');
session_start();
if(isset($_SESSION['login_cookie'])) {
	$login_cookie = $_SESSION['login_cookie'];
} else {
	$login_cookie = new UserCookie();
} 
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FHP::Upload</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2016-12-16" >
<meta name="copyright" content="Ryan Eric Johnson 2016" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<!-- Bootstrap styles used for file uploader 
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
-->
<!-- Uploader Generic page styles -->
<link rel="stylesheet" href="upload/css/style.css">
<!-- blueimp Gallery styles -->
<link rel="stylesheet" href="//blueimp.github.io/Gallery/css/blueimp-gallery.min.css">
<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
<link rel="stylesheet" href="upload/css/jquery.fileupload.css">
<link rel="stylesheet" href="upload/css/jquery.fileupload-ui.css">
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="upload/css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="upload/css/jquery.fileupload-ui-noscript.css"></noscript>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<!-- jQuery for javascript -->
<!-- <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script> -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<!-- AJAX calls for forms 
Database access is handled by external PHP scripts
JSON output is created through PHP
and handled by jQuery to populate fields
Invoke the JSON scripts here -->

<!-- End of JSON scripts -->
<!-- Bootstrap extensions for jQuery -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>

<body>

<!-- Top navigation bar -->
<nav class="navbar navbar-default navbar-fixed-top">
	<div class="container-fluid">
	<!-- Brand and toggle get grouped for better mobile display -->
	<div class="navbar-header">
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#fhp-navbar-collapse-1" aria-expanded="false">
		<span class="sr-only">Toggle navigation</span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="<?php echo $html_base; ?>">FHP::Proofing System</a>
	</div>

	<!-- Collect the nav links, forms, and other content for toggling -->
	<div class="collapse navbar-collapse" id="fhp-navbar-collapse-1">
		<ul class="nav navbar-nav"> 
			<li><a href="<?php echo $html_base; ?>index.php">Main <span class="sr-only">(current)</span></a></li>
			<li><a href="<?php echo $html_base; ?>index.php?header=cookieDel">Sign Out</a></li>
<?php if($admin){ ?>	<li class="dropdown">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
			  <ul class="dropdown-menu">
			    <li><a href="<?php echo $html_base; ?>users.php?users=list">Users</a></li>
			  </ul>
			</li>
<?php } ?>
			<li class="dropdown active">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Files <span class="caret"></span></a>
			  <ul class="dropdown-menu">
			    <li class="active"><a href="<?php echo $html_base; ?>upload.php">Upload</a></li>
			    <li><a href="<?php echo $html_base; ?>server/php/browser.php">Browser</a></li>
			  </ul>
			</li>
		</ul>
	</div><!-- /.navbar-collapse -->
	</div><!-- /.container-fluid -->
</nav>
<!-- end of navigation bar -->

<!-- Files table -->
<div class="container-fluid">
<div class="row">
<div class="col-md-1"></div>
<div class="col-md-10">
<div class="panel panel-primary">
	<div class="panel-heading">
	<h3 class="panel-title">File Upload</h3>
	</div>
	<div class="panel-body">
	<h4 class="lead">Files to be added to project database</h4>
	<!-- The file upload form used as target for the file upload widget -->
	<form id="fileupload" action="upload/" method="POST" enctype="multipart/form-data">
	<!-- redirect to full demo for disabled javascript
	     not exactly sure why this was done, original
	     redirected to blueimp static version on their
	     own web site. See index.php under upload/ -->
	<noscript><input type="hidden" name="redirect" value="upload/"></noscript>
	<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
	<div class="row fileupload-buttonbar">
	  <div class="col-lg-7">
	  <!-- The fileinput-button span is used to style the file input field as button -->
	  <span class="btn btn-success fileinput-button">
	    <i class="glyphicon glyphicon-plus"></i>
	    <span>Add files...</span>
	    <input type="file" name="files[]" multiple>
	  </span>
	  <button type="submit" class="btn btn-primary start">
	    <i class="glyphicon glyphicon-upload"></i>
	    <span>Start upload</span>
	  </button>
	  <button type="reset" class="btn btn-warning cancel">
	    <i class="glyphicon glyphicon-ban-circle"></i>
	    <span>Cancel upload</span>
	  </button>
	  <button type="button" class="btn btn-danger delete">
	    <i class="glyphicon glyphicon-trash"></i>
	    <span>Delete</span>
	  </button>
	  <input type="checkbox" class="toggle">
	  <!-- The global file processing state -->
	  <span class="fileupload-process"></span>
	  </div>
	  <div class="col-lg-5 fileupload-progress fade">
	    <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
	      <div class="progress-bar progress-bar-success" style="width:0%;"></div>
	    </div>
	    <div class="progress-extended">&nbsp;</div>
	  </div>
	</div>
	<!-- The table listing the files available for upload/download -->
	<table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>
	<!-- bottom file/upload button bar -->
	<div class="row fileupload-buttonbar">
	  <div class="col-lg-7">
	  <span class="btn btn-success fileinput-button">
	    <i class="glyphicon glyphicon-plus"></i>
	    <span>Add files...</span>
	    <input type="file" name="files[]" multiple>
	  </span>
	  <button type="submit" class="btn btn-primary start">
	    <i class="glyphicon glyphicon-upload"></i>
	    <span>Start upload</span>
	  </button>
	  <button type="reset" class="btn btn-warning cancel">
	    <i class="glyphicon glyphicon-ban-circle"></i>
	    <span>Cancel upload</span>
	  </button>
	  <button type="button" class="btn btn-danger delete">
	    <i class="glyphicon glyphicon-trash"></i>
	    <span>Delete</span>
	  </button>
	  <input type="checkbox" class="toggle">
	  <span class="fileupload-process"></span>
	  </div>
	</div>
	</form>		
	<br>
	<div class="panel panel-default">
	  <div class="panel-heading">
	    <h3 class="panel-title">Note</h3>
	  </div>
	  <div class="panel-body">
	    <ul>
	      <li>You can <strong>drag &amp; drop</strong> files from your desktop into this container.</li>
	      <li>Mobile devices can be used, but only iOS 6 and above will do multiple files. Android at this time does not support multiple files.</li>
	      <li>Please upload files first in order to create a directory for your user, then you can manipulate the files from the Browser.</li>
	    </ul>
	  </div>
	</div>
	</div>
</div>
</div>
<div class="col-md-1"></div>
</div>

<div class="row">
<div class="well well-sm">
<center>&copy;Fragile Heritage Project</center>
</div>
</div>
</div>
<!-- The blueimp Gallery widget -->
<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter=":even">
    <div class="slides"></div>
    <h3 class="title"></h3>
    <a class="prev">‹</a>
    <a class="next">›</a>
    <a class="close">×</a>
    <a class="play-pause"></a>
    <ol class="indicator"></ol>
</div>
<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td>
            <span class="preview"></span>
	      <label class="title">
	        <span>Title:</span><br>
	        <input name="title[]" class="form-control">
	      </label>
	      <label class="description">
	        <span>Description:</span><br>
	        <input name="description[]" class="form-control">
	      </label>
        </td>
        <td>
            <p class="name">{%=file.name%}</p>
            <strong class="error text-danger"></strong>
        </td>
        <td>
            <p class="size">Processing...</p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
        </td>
        <td>
            {% if (!i && !o.options.autoUpload) { %}
                <button class="btn btn-primary start" disabled>
                    <i class="glyphicon glyphicon-upload"></i>
                    <span>Start</span>
                </button>
            {% } %}
            {% if (!i) { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Cancel</span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        <td>
            <span class="preview">
                {% if (file.thumbnailUrl) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                {% } %}
            </span>
        </td>
	<td>
	    <p class="title"><strong>{%=file.title||''%}</strong></p>
	    <p class="description">{%=file.description||''%}</p>
	</td>
        <td>
            <p class="name">
                {% if (file.url) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                {% } else { %}
                    <span>{%=file.name%}</span>
                {% } %}
            </p>
            {% if (file.error) { %}
                <div><span class="label label-danger">Error</span> {%=file.error%}</div>
            {% } %}
        </td>
        <td>
            <span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
        <td>
            {% if (file.deleteUrl) { %}
                <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
                    <i class="glyphicon glyphicon-trash"></i>
                    <span>Delete</span>
                </button>
                <input type="checkbox" name="delete" value="1" class="toggle">
            {% } else { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Cancel</span>
                </button>
            {% } %}
        </td>
    </tr>
{% } %}
</script>
<!--
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
-->
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="upload/js/vendor/jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="upload/js/blueimp/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="upload/js/blueimp/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="upload/js/blueimp/canvas-to-blob.min.js"></script>
<!-- Bootstrap JS is not required, but included for the responsive demo navigation 
<script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script> -->
<!-- blueimp Gallery script -->
<script src="upload/js/blueimp/jquery.blueimp-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="upload/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="upload/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="upload/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="upload/js/jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="upload/js/jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<script src="upload/js/jquery.fileupload-video.js"></script>
<!-- The File Upload validation plugin -->
<script src="upload/js/jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="upload/js/jquery.fileupload-ui.js"></script>
<!-- The main application script -->
<script src="upload/js/main.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="upload/js/cors/jquery.xdr-transport.js"></script>
<![endif]-->

</body>

</html>
<?php
}
?>
