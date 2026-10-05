<?php
/* Shared footer scripts for the Vue/Tailwind admin pages. Expects $ips_config (array) from the page.
 *
 * The page sets the view and the signed-in user; everything else the app needs to know about this site comes from
 * here: the base URLs, the site name and logo, and which optional modules are installed. A site may add
 * js/site-home.js to contribute panels to the home page (IPS.registerHomePanel, see js/ui/home.js).
 */
$ips_modules = [];
foreach ([
	'xml'         => ['XML editor', 'Edit and ingest the TEI / XML records.', 'code', 'xml/'],
	'html_editor' => ['HTML editor', 'Edit the HTML pages kept in the file store.', 'edit-3', 'html_editor/'],
	'file_editor' => ['Pages', 'Create, edit and delete HTML and XML pages in your own folder.', 'edit-3', 'file_editor/'],
	'catalogue'   => ['Catalogue', 'Browse the catalogue of items.', 'book-open', 'catalogue/'],
	'contacts'    => ['Contacts', 'The contacts list.', 'users', 'contacts/'],
] as $dir => $info) {
	if (is_file(PROGRAM_BASE . $dir . '/index.php')) {
		$ips_modules[] = ['id' => $dir, 'title' => $info[0], 'text' => $info[1], 'icon' => $info[2], 'href' => PROGRAM_WEB_BASE . $info[3]];
	}
}
$ips_config = array_merge([
	'base'    => PROGRAM_WEB_BASE,
	'site'    => dirname(rtrim(PROGRAM_WEB_BASE, '/')) . '/', // the web root that holds ips/
	'name'    => SITE_NAME,
	'logo'    => is_file(PROGRAM_BASE . 'img/logo/logo32.png') ? PROGRAM_WEB_BASE . 'img/logo/logo32.png' : null,
	'modules' => $ips_modules,
], $ips_config);
$ips_js = function ($path) {
	return PROGRAM_WEB_BASE . $path . '?v=' . (is_file(PROGRAM_BASE . $path) ? filemtime(PROGRAM_BASE . $path) : 0);
};
$ips_scripts = ['js/ips-icons.js', 'js/ui/core.js', 'js/ui/shell.js'];
if ($ips_config['view'] === 'browser') {
	array_push($ips_scripts, 'js/ui/browser-store.js', 'js/ui/browser-actions.js', 'js/ui/browser-parts.js', 'js/ui/browser-dialogs.js', 'js/ui/browser-upload.js', 'js/ui/browser-viewer.js', 'js/ui/browser-extras.js', 'js/ui/browser-panel.js', 'js/ui/browser.js');
} else {
	array_push($ips_scripts, 'js/ui/login.js', 'js/ui/home.js', 'js/ui/users.js');
}
if (is_file(PROGRAM_BASE . 'js/site-home.js')) {
	$ips_scripts[] = 'js/site-home.js';
}
$ips_scripts[] = 'js/ui/main.js';
?>
<noscript><p style="padding:2rem;font-family:sans-serif">These tools need JavaScript enabled.</p></noscript>
<script>window.IPS_CONFIG = <?php echo json_encode($ips_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;</script>
<?php foreach ($ips_scripts as $ips_script) { ?>
<script src="<?php echo $ips_js($ips_script) ?>"></script>
<?php } ?>
