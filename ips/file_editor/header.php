<?php
/* The app bar of the file editor's pages: the base shell's look in PHP (these pages are not Vue views), with this module marked as the current one.
 * Expects the signed-in user's name and admin flag in $fe_user and $fe_admin. */
$fe_h = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
$fe_logo = is_file(PROGRAM_BASE . 'img/logo/logo32.png') ? PROGRAM_WEB_BASE . 'img/logo/logo32.png' : null;
?>
<header class="fe-header">
	<a class="fe-brand" href="<?php echo PROGRAM_WEB_BASE ?>index.php"><?php if ($fe_logo) { ?><img src="<?php echo $fe_h($fe_logo) ?>" alt=""><?php } ?><span><?php echo $fe_h(SITE_NAME) ?></span></a>
	<nav class="fe-nav" aria-label="Main">
		<a href="<?php echo PROGRAM_WEB_BASE ?>index.php">Home</a>
		<a href="<?php echo PROGRAM_WEB_BASE ?>browser.php">Files</a>
<?php if ($fe_admin) { ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>users.php">Users</a>
<?php } ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>file_editor/" aria-current="page">File editor</a>
	</nav>
	<div class="fe-user">
		<span class="fe-avatar" aria-hidden="true"><?php echo $fe_h(strtoupper(substr($fe_user, 0, 1))) ?></span>
		<span><?php echo $fe_h($fe_user) ?></span>
<?php if ($fe_admin) { ?>
		<span class="fe-admin">Admin</span>
<?php } ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>index.php?header=cookieDel">Sign out</a>
	</div>
</header>
