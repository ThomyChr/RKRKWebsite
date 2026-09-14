<?php
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
	? ('?' . $_SERVER['QUERY_STRING'])
	: '';
header('Location: rkrk_map.php' . $query, true, 301);
exit;
