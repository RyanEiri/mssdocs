<?php
// The groups and every link (hidden ones too) as JSON, for the Links page.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_links_user(false);
echo json_encode(['success' => true, 'groups' => ips_links_groups(), 'links' => ips_links_all()]);
