<?php
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
session_start();
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
<title>Fragile Heritage Project::Internal Proofing System::XML Editor</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2018-07-01" >
<meta name="copyright" content="Fragile Heritage Project 2018" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/dashboard.css">

<!-- Internet Explorer Tweaks -->
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>ie10-viewport-bug-workaround.css">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>

<?php //include(HTML_TEMPLATES.'navbar.php'); ?>

    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo PROGRAM_WEB_BASE ?>xml/">XML Editor</a>
      <input class="form-control form-control-dark w-100" type="text" placeholder="Inactive Search Bar" aria-label="Search">
      <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
          <a class="nav-link" href="<?php echo PROGRAM_WEB_BASE ?>">Main</a>
        </li>
      </ul>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky">
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
              <span>XML Files</span>
              <a class="d-flex align-items-center text-muted" href="#">
                <span data-feather="plus-circle"></span>
              </a>
            </h6>
            <ul class="nav flex-column mb-2">
              <li class="nav-item">
                <a class="nav-link" href="#allfiles">
                  <span data-feather="file-text"></span>
                  All files
                </a>
              </li>
            </ul>
          </div>
        </nav>

        <main role="main" class="col-md-10 ml-auto col-lg-10 pt-3 px-4">

          <h2>XML Files</h2>
          <div id="allfiles" class="table-responsive filemanager">

						<table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th>Parse</th>
                  <th>Type</th>
									<th>Name</th>
									<th>Size</th>
                </tr>
              </thead>

							<tbody class="data">

              </tbody>

            </table>

          </div>
        </main>
      </div>
    </div>

	<!-- jQuery for javascript -->
	<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
	<!-- JSON scripts -->
	<script src="json_request-file_info.js"></script>
	<!-- End of JSON scripts -->
	<!-- Bootstrap extensions for jQuery -->
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="<?php echo PROGRAM_JS_BASE ?>ie10-viewport-bug-workaround.js"></script>

	<!-- Icons -->
  <script src="<?php echo PROGRAM_JS_BASE ?>icons/feather.min.js"></script>
  <script>
    feather.replace()
  </script>

</body>
</html>
<?php
}
session_unset();
?>
