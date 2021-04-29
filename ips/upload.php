<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);
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
<meta name="date" content="2018-06-04" >
<meta name="copyright" content="Ryan Eric Johnson 2018" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">

<!-- Uploader Generic page styles -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>style.fileupload.css">
<!-- blueimp Gallery styles -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>blueimp-gallery.min.css">
<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>jquery.fileupload.css">
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>jquery.fileupload-ui.css">
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>jquery.fileupload-ui-noscript.css"></noscript>

<!-- Internet Explorer Tweaks -->
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>ie10-viewport-bug-workaround.css">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<!-- Custom styles for this page -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/sticky-footer-navbar.css">
</head>

<body class="d-flex flex-column h-100">
<header>
<?php include(HTML_TEMPLATES.'navbar.php'); ?>
</header>
<!-- Begin page content -->
<main role="main" class="container">
	<!-- Files table -->
	<!--<div class="container-fluid">-->
	<div class="container">
		<h3 class="panel-title">File Upload</h3>
		<h4 class="lead">Files to be added to project database</h4>
		<!-- The file upload form used as target for the file upload widget -->
		<form id="fileupload" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
		<!-- redirect for disabled javascript -->
		<noscript><input type="hidden" name="redirect" value="<?php echo $_SERVER['PHP_SELF']; ?>"></noscript>
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
		    <h3>Note</h3>
		    <ul>
		      <li>You can <strong>drag &amp; drop</strong> files from your desktop into this container.</li>
		      <li>Mobile devices can be used, but only iOS 6 and above will do multiple files. Android at this time does not support multiple files.</li>
		      <li>Please upload files first in order to create a directory for your user, then you can manipulate the files from the Browser.</li>
		    </ul>

	</div>

</main>

<footer class="footer mt-auto py-3">
	<div class="container">
			<span class="text-muted">&copy; Fragile Heritage Project 2018</span>
	</div>
</footer>

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
				</td>
				<td>
	      <label class="title">
	        <span>Title:</span>
	        <input name="title[]" class="form-control">
	      </label>
	      <label class="description">
	        <span>Description:</span>
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

	<!-- jQuery for javascript -->
	<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
	<!-- JSON scripts -->

	<!-- End of JSON scripts -->
	<!-- Bootstrap extensions for jQuery -->
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="<?php echo PROGRAM_JS_BASE ?>ie10-viewport-bug-workaround.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.ui.widget.js"></script>
<!-- The Templates plugin is included to render the upload/download listings -->
<script src="<?php echo PROGRAM_JS_BASE ?>tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="<?php echo PROGRAM_JS_BASE ?>load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="<?php echo PROGRAM_JS_BASE ?>canvas-to-blob.min.js"></script>
<!-- blueimp Gallery script -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.blueimp-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-video.js"></script>
<!-- The File Upload validation plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-ui.js"></script>
<!-- The main application script -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.fileupload-main.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.xdr-transport.js"></script>
<![endif]-->

</body>

</html>
<?php
}
?>
