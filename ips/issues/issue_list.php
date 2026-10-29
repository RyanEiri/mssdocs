<?php
// Every issue, newest first, with the statuses, access levels and kinds of extraction a form may offer. For signed-in users (who are the editors).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_issues_user(false);
$options = function ($list) { $out = []; foreach ($list as $id => $label) { $out[] = ['id' => $id, 'label' => $label]; } return $out; };
echo json_encode(['success' => true, 'issues' => ips_issues_all(), 'statuses' => $options(IPS_ISSUE_STATUSES), 'access' => $options(IPS_ISSUE_ACCESS)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
