<?php
/* The app bar of the Issues page: the same as the file editor's (file_editor/header.php) with this module marked as the current one. Expects the signed-in
 * user's name and admin flag in $is_user and $is_admin. */
$is_h = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
$is_logo = is_file(PROGRAM_BASE . 'img/logo/logo32.png') ? PROGRAM_WEB_BASE . 'img/logo/logo32.png' : null;
?>
<header class="fe-header">
	<a class="fe-brand" href="<?php echo PROGRAM_WEB_BASE ?>index.php"><?php if ($is_logo) { ?><img src="<?php echo $is_h($is_logo) ?>" alt=""><?php } ?><span><?php echo $is_h(SITE_NAME) ?></span></a>
	<nav class="fe-nav" aria-label="Main">
		<a href="<?php echo PROGRAM_WEB_BASE ?>index.php">Home</a>
		<a href="<?php echo PROGRAM_WEB_BASE ?>browser.php">Files</a>
<?php if ($is_admin) { ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>users.php">Users</a>
<?php } ?>
<?php if (is_file(PROGRAM_BASE . 'file_editor/index.php')) { ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>file_editor/">File editor</a>
<?php } ?>
<?php if (is_file(PROGRAM_BASE . 'projects/index.php')) { ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>projects/">Projects</a>
<?php } ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>issues/" aria-current="page">Issues</a>
	</nav>
	<div class="fe-user">
		<a href="<?php echo $is_h(dirname(rtrim(PROGRAM_WEB_BASE, '/')) . '/') ?>">View site</a>
		<span class="fe-avatar" aria-hidden="true"><?php echo $is_h(strtoupper(substr($is_user, 0, 1))) ?></span>
		<span><?php echo $is_h($is_user) ?></span>
<?php if ($is_admin) { ?>
		<span class="fe-admin">Admin</span>
<?php } ?>
		<a href="<?php echo PROGRAM_WEB_BASE ?>index.php?header=cookieDel">Sign out</a>
	</div>
</header>
