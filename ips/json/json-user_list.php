<?php
/* User list for users.php. Admin only; never returns a password or secretword. */
chdir('..');
include getcwd().'/php/boot.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$cookie = new UserCookie();
$username = $cookie->Peek();
if ($username === false || !(new UserGrab($username))->admin) {
	http_response_code(403);
	echo json_encode(['error' => 'Admin login required']);
	exit;
}

$list = (new UserList())->userlist;
$users = [];
foreach ((is_array($list) ? $list : []) as $row) {
	$users[] = [
		'id'       => (int)$row['id'],
		'username' => $row['username'],
		'email'    => isset($row['email']) ? $row['email'] : '',
		'admin'    => ((int)$row['admin']) === 1,
		'date'     => isset($row['date']) ? $row['date'] : null,
	];
}
echo json_encode(['users' => $users]);
