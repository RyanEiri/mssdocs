<?php
/* Shared <head> for the Vue/Tailwind admin pages (login, home, users, browser).
 *
 * Self-hosted: fonts, the compiled Tailwind stylesheet and Vue all come from this site, so the pages make no
 * third-party requests. A site changes the look by adding css/site-theme.css, which overrides the --ips-* tokens
 * of css/ips-theme.css and is loaded last (see that file).
 */
$ips_asset = function ($path) {
	return PROGRAM_WEB_BASE . $path . '?v=' . (is_file(PROGRAM_BASE . $path) ? filemtime(PROGRAM_BASE . $path) : 0);
};
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="expires" content="0">
<?php if (is_file(PROGRAM_BASE . 'img/logo/logo32.png')) { ?>
<link rel="icon" type="image/png" href="<?php echo $ips_asset('img/logo/logo32.png') ?>">
<?php } ?>
<link rel="stylesheet" href="<?php echo $ips_asset('css/ips-fonts.css') ?>">
<link rel="stylesheet" href="<?php echo $ips_asset('css/ips-theme.css') ?>">
<link rel="stylesheet" href="<?php echo $ips_asset('css/ips-ui.css') ?>">
<?php if (is_file(PROGRAM_BASE . 'css/site-theme.css')) { ?>
<link rel="stylesheet" href="<?php echo $ips_asset('css/site-theme.css') ?>">
<?php } ?>
<script src="<?php echo $ips_asset('js/vue.global.prod.js') ?>"></script>
