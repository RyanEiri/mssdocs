<?php
session_start();
include 'ips/php/boot.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>

	<!-- Tell search engines not to index the test pages
	  REMOVE FOR PRODUCTION -->
	<meta name="robots" content="noindex">
	
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fragile Heritage Project</title>
<meta name="author" content="Ryan E. Johnson" >
<meta name="date" content="2017-12-04" >
<meta name="copyright" content="Fragile Heritage Project 2017" >
<meta name="keywords" content="Fragile Heritage Project, manuscript, manuscripts, Canada, America, U.S.A., United States, United States of America, textual heritage, Icelandic, Iceland" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="css/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="css/cover.css"

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://vesturheimsrit.com/js/html5shiv.min.js"></script>
<script src="https://vesturheimsrit.com/js/respond.min.js"></script>
<![endif]-->

</head>

<body class="text-center">

<?php
?>
    <div class="cover-container d-flex h-100 p-3 mx-auto flex-column">
      <header class="masthead mb-auto">
        <div class="inner">
          <h3 class="masthead-brand">Í fótspor Árna Magnússonar í Vesturheimi</h3>
          <nav class="nav nav-masthead justify-content-center">
	  <a class="nav-link active" href="<?php echo $full_url ?>">Home</a>
	  <a class="nav-link" href="<?php echo $full_url ?>/gallery">Gallery</a>
            <a class="nav-link" href="/ips">Proofing</a>
          </nav>
        </div>
      </header>

      <main role="main" class="inner cover">
        <h1 class="cover-heading">Fragile Heritage Project</h1>
        <p class="lead mark">The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private holdings across Canada and the U.S.A.</p>
        <!-- <p class="lead">
          <a href="#" class="btn btn-lg btn-secondary">Learn more</a>
        </p> -->
      </main>

      <footer class="mastfoot mt-auto">
        <div class="inner">
          <p>&copy; Fragile Heritage Project 2018</p>
					<p class="small"><a href="https://creativecommons.org/licenses/by-sa/3.0/">Cover photo with minor alterations for size</a>: <a href="img/Some_Light_Reading_by_Brandilyn_Carpenter.jpg"><cite title="Some Light Reading">Some Light Reading</cite> by Brandilyn Carpenter</a></p>
        </div>
      </footer>
    </div>

<!-- Include script files -->
<script src="js/jquery.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<!-- End of script files -->

</body>
</html>
