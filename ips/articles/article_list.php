<?php
// Every article without its body, newest update first, with the options a form offers. For signed-in users (the editors).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_articles_user(false);
echo json_encode(['success' => true, 'articles' => ips_articles_all()] + ips_articles_form_options(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
