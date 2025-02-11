<?php
$show_to_groups = array(82,83,95,31,35,30,58,29,43,44,72,32,33,34,80,84,85,77,36,78,76,62,64,63,71,68,61,69,66,67,65,70,81,93);
// $user_groups = check_user_group($user_id);
$user_groups = array(91,87,38,39,75,92,50,94,101,86,40);

$common_groups = array_intersect($user_groups, $show_to_groups);

if (!empty($common_groups))
{
	echo "User is part of the group<br>";
	// load_banner();
}
else 
{
	echo "User is not part of the group<br>";
	exit;
}

