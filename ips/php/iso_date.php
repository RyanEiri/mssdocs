<?php
/* ISO 8601 calendar dates, shared by the modules that keep dates (units of description, issues). */

// [first day, last day] of an ISO 8601 date as YYYYMMDD numbers (a year covers the whole year, a month the whole month), or null when it is not a real YYYY, YYYY-MM or YYYY-MM-DD.
function ips_iso_date_bounds($d) {
	if (!is_string($d) || !preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/', $d, $m)) {
		return null;
	}
	$year = (int) $m[1];
	if (!isset($m[2])) {
		return [$year * 10000 + 101, $year * 10000 + 1231];
	}
	$month = (int) $m[2];
	if ($month < 1 || $month > 12) {
		return null;
	}
	$leap = $year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0);
	$days = [31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][$month - 1];
	if (!isset($m[3])) {
		return [$year * 10000 + $month * 100 + 1, $year * 10000 + $month * 100 + $days];
	}
	$day = (int) $m[3];
	if ($day < 1 || $day > $days) {
		return null;
	}
	$n = $year * 10000 + $month * 100 + $day;
	return [$n, $n];
}

