<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();

if($login_cookie->CheckIt()) {
	$_SESSION['login_cookie'] = $login_cookie;
	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

  if(ADMIN_STATUS) {
    $file_keys = array(
      3556 => array(
        "contentid" => "zip",
        "urlkey"    => keymaker("zip")
      ),
      3557 => array(
        "contentid" => "sql",
        "urlkey"    => keymaker("sql")
      )
    );

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
    <title>URL Keys</title>
    <meta name="author" content="Ryan Eric Johnson" >
    <meta name="date" content="2021-05-16" >
    <meta name="copyright" content="Ryan Johnson 2021" >
    <meta http-equiv="expires" content="0" >

    <!-- Bootstrap for CSS -->
    <link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
    <!-- Custom styles for this page -->
    <link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/sticky-footer-navbar.css">

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

      <body class="d-flex flex-column h-100">

        <header>
        <!-- Fixed Navbar -->
        <?php include(HTML_TEMPLATES.'navbar.php'); ?>
        </header>

        <main role="main" class="container">
          <?php
          foreach ($file_keys as $key => $value) {
          ?>
            <div>
              File ID: <?php echo $key; ?><br />
              Content ID: <?php echo $value['contentid']; ?><br />
              URL Key: <?php echo $value['urlkey']; ?><br />
              URL: <a href=<?php echo PROGRAM_WEB_BASE.'download.php?file_id='.$key.'&urlkey='.$value['urlkey']; ?>><?php echo PROGRAM_WEB_BASE.'download.php?file_id='.$key.'&urlkey='.$value['urlkey']; ?></a>
            </div>
          <?php
          }
          ?>
        </main>

        <!-- Footer template -->
        <?php include(HTML_TEMPLATES.'footer.php'); ?>

        <!-- jQuery for javascript -->
        <script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
        <!-- Bootstrap extensions  for jQuery -->
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
  } else {

  }
}
?>
