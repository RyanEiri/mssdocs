<?php
// Every project (active ones first) with its number of files, and the kinds, statuses and roles a form may offer.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_projects_user(false);
echo json_encode(['success' => true, 'projects' => ips_projects_all(), 'kinds' => ips_projects_options(IPS_PROJECT_KINDS), 'statuses' => ips_projects_options(IPS_PROJECT_STATUSES),
	'roles' => ips_projects_options(IPS_PROJECT_ROLES)]);
