<?php
/* The app bar of the Links page: the same as the file editor's (file_editor/header.php) with this module marked as the current one. Expects the signed-in
 * user's name and admin flag in $ln_user and $ln_admin. */
$ln_h = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
$ln_logo = is_file(PROGRAM_BASE . 'img/logo/logo32.png') ? PROGRAM_WEB_BASE . 'img/logo/logo32.png' : null;
?>
<header class="fe-header">
	<a class="fe-brand" href="<?php echo PROGRAM_WEB_BASE ?>index.php"><?php if ($ln_logo) { ?><img src="<?php echo $ln_h($ln_logo) ?>" alt=""><?php } ?><span><?php echo $ln_h(SITE_NAME) ?></span></a>
	<nav class="fe-nav" aria-label="Main">
		<a href="<?php echo PROGRAM_WEB_BASE ?>index.php">Home</a>
		<a href="<?php echo PROGRAM_WEB_BASE ?>browser.php">Files</a>
<?php if ($ln_admin) { ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>users.php">Users</a>
<?php } ?>
<?php if (is_file(PROGRAM_BASE . 'file_editor/index.php')) { ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>file_editor/">File editor</a>
<?php } ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>links/" aria-current="page">Links</a>
	</nav>
	<div class="fe-user">
		<a href="<?php echo $ln_h(dirname(rtrim(PROGRAM_WEB_BASE, '/')) . '/') ?>">View site</a>
		<span class="fe-avatar" aria-hidden="true"><?php echo $ln_h(strtoupper(substr($ln_user, 0, 1))) ?></span>
		<span><?php echo $ln_h($ln_user) ?></span>
<?php if ($ln_admin) { ?>
		<span class="fe-admin">Admin</span>
<?php } ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>index.php?header=cookieDel">Sign out</a>
	</div>
</header>
