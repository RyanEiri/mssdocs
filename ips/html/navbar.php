<!-- Top navigation bar -->
<nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-dark">
	<a class="navbar-brand" href="<?php echo PROGRAM_WEB_BASE ?>"><img class="m-1" src="<?php echo PROGRAM_WEB_BASE ?>img/logo/logo32.png" alt="FH" width="32" height="32">NA Ice Vets</a>
	<button type="button" class="navbar-toggler collapsed" data-toggle="collapse" data-target="#fhp-navbar-collapse-1" aria-expanded="false">
	  <span class="sr-only">Toggle navigation</span>
	  <span class="navbar-toggler-icon"></span>
	</button>

	<!-- Collect the nav links, forms, and other content for toggling -->
	<div class="collapse navbar-collapse" id="fhp-navbar-collapse-1">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item active"><a class="nav-link" href="<?php echo PROGRAM_WEB_BASE ?>index.php">Home <span class="sr-only">(current)</span></a></li>
			<li class="nav-item"><a class="nav-link" href="<?php echo PROGRAM_WEB_BASE ?>index.php?header=cookieDel">Sign Out</a></li>
<?php if(ADMIN_STATUS){ ?>
			<li class="nav-item dropdown">
			  <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Users <span class="caret"></span></a>
			  <div class="dropdown-menu" aria-labelledby="adminDropdown">
			    <a class="dropdown-item" href="<?php echo PROGRAM_WEB_BASE ?>users.php?users=list">Add/Remove</a>
			  </div>
			</li>
<?php } ?>
			<li class="nav-item dropdown">
			  <a class="nav-link dropdown-toggle" href="#" id="proofingDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Proofing <span class="caret"></span></a>
			  <div class="dropdown-menu" aria-labelledby="proofingDropdown">
			    <a class="dropdown-item" href="<?php echo PROGRAM_WEB_BASE ?>upload.php">Upload</a>
			    <a class="dropdown-item" href="<?php echo PROGRAM_WEB_BASE ?>browser.php">Browse</a>
			  </div>
			</li>
		</ul>
	</div><!-- /.navbar-collapse -->
	</div><!-- /.container-fluid -->
</nav>
<!-- end of navigation bar -->
