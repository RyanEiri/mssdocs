<!DOCTYPE html>
<html lang="en">

<head>

        <!-- Tell search engines not to index the gallery page -->
        <meta name="robots" content="noindex">

<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fragile Heritage Project::Image Gallery</title>
<meta name="author" content="Ryan E. Johnson" >
<meta name="date" content="2019-05-07" >
<meta name="copyright" content="Fragile Heritage Project 2017" >
<meta name="keywords" content="image gallery, images, Fragile Heritage Project, Minneota, Daren Gislason, manuscript, manuscripts, Canada, America, U.S.A., United States, United States of America, textual heritage, Icelandic, Iceland" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="../css/cover.css">
<link rel="stylesheet" href="../css/album.css">

<!-- Blueimp Gallery CSS -->
<link rel="stylesheet" href="../css/blueimp-gallery.min.css">

<!-- Internet Explorer Tweaks -->
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="../css/ie10-viewport-bug-workaround.css">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body class="text-center">

	<style>.cover-container { max-width: 90%; }</style>

    <div class="cover-container d-flex h-100 p-3 mx-auto flex-column">
      <header class="masthead mb-auto">
        <div class="inner">
          <h3 class="masthead-brand">Í fótspor Árna Magnússonar í Vesturheimi</h3>
          <nav class="nav nav-masthead justify-content-center">
            <a class="nav-link" href="../index.php">Home</a>
						<a class="nav-link active" href="<?php echo $_SERVER['PHP_SELF'] ?>">Gallery</a>
            <a class="nav-link" href="../ips">Proofing</a>
          </nav>
        </div>
      </header>

      <main role="main" class="inner cover filemanager">
        <h1 class="cover-heading">Minneota Book Collections</h1>

				<div class="album albummanager py-5">
					<div class="container">

						<div class="row album-list">
							<div class="col-md-4">
								<div class="card mb-4 box-shadow">
									<a href="gallery-farmhouse.png"><img class="card-img-top" src="gallery-farmhouse.png" alt="Farmhouse"></a>
									<div class="card-body">
										<p class="card-text">Daren's farmhouse just outside of town.</p>
										<div class="d-flex justify-content-between align-items-center">
											<div class="btn-group">
												<a href="../ips/server/php/files/ryan/Daren Gislason/Book Collections/Farmhouse" class="btn btn-sm btn-outline-secondary album-item" id="farmhouse" role="button">View</a>
											</div>
											<small class="text-muted">Farmhouse</small>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="card mb-4 box-shadow">
									<a href="gallery-minneota_house.png"><img class="card-img-top" src="gallery-minneota_house.png" alt="Minneota House"></a>
									<div class="card-body">
										<p class="card-text">Daren's house in Minneota.</p>
										<div class="d-flex justify-content-between align-items-center">
											<div class="btn-group">
												<a href="../ips/server/php/files/ryan/Daren Gislason/Book Collections/Minneota House" class="btn btn-sm btn-outline-secondary album-item" id="minneotahouse" role="button">View</a>
											</div>
											<small class="text-muted">Minneota House</small>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="card mb-4 box-shadow">
									<a href="gallery-opera_house.png"><img class="card-img-top" src="gallery-opera_house.png" alt="Opera House"></a>
									<div class="card-body">
										<p class="card-text">The Opera House in Minneota.</p>
										<div class="d-flex justify-content-between align-items-center">
											<div class="btn-group">
												<a href="../ips/server/php/files/ryan/Daren Gislason/Book Collections/Opera House" class="btn btn-sm btn-outline-secondary album-item" id="operahouse" role="button">View</a>
											</div>
											<small class="text-muted">Opera House</small>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- The Gallery as lightbox dialog, should be a child element of the document body -->
				<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls">
						<div class="slides"></div>
						<h3 class="title"></h3>
						<a class="prev">‹</a>
						<a class="next">›</a>
						<a class="close">×</a>
						<a class="play-pause"></a>
						<ol class="indicator"></ol>
				</div>

				<!-- Image links -->
				<div id="links" class="data">
					<!--
					<a href="ips/upload/server/php/files/ryan/Daren Gislason/Book Collections/Farmhouse/IMG_3506.JPG" title="Farmhouse" data-gallery>
						<img src="ips/upload/server/php/files/ryan/Daren Gislason/Book Collections/Farmhouse/thumbnail/IMG_3506.JPG" alt="Farmhouse">
					</a>
					<a href="ips/upload/server/php/files/ryan/Daren Gislason/Book Collections/Farmhouse/Box 01/IMG_2885.JPG" title="Box 01" data-gallery>
						<img src="ips/upload/server/php/files/ryan/Daren Gislason/Book Collections/Farmhouse/Box 01/thumbnail/IMG_2885.JPG" alt="Box 01">
					</a>
					-->
				</div>

      </main>

      <footer class="mastfoot mt-auto">
        <div class="inner">
          <p>&copy; Fragile Heritage Project 2018</p>
					<p class="small"><a href="https://creativecommons.org/licenses/by-sa/3.0/">Cover photo with minor alterations for size</a>: <a href="../img/Some_Light_Reading_by_Brandilyn_Carpenter.jpg"><cite title="Some Light Reading">Some Light Reading</cite> by Brandilyn Carpenter</a></p>
        </div>
      </footer>
    </div>

<!-- Include Javascript files -->
<script src="../js/jquery.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<!-- End of script files -->

<!-- Blueimp Gallery JS -->
<script src="../js/jquery.blueimp-gallery.min.js"></script>
<script>
document.getElementById('links').onclick = function (event) {
    event = event || window.event;
    var target = event.target || event.srcElement,
        link = target.src ? target.parentNode : target,
        options = {index: link, event: event, hidePageScrollbars: false},
        links = this.getElementsByTagName('a');
    blueimp.Gallery(links, options);
};
</script>
<script>
  $('#blueimp-gallery').data('fullScreen', 'true');
	//$('#blueimp-gallery').data('')
</script>
<!-- JSON Scripts -->
<script src="json_request-file_info.js"></script>

</body>
</html>
