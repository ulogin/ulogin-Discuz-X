<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$sql = <<<EOF

DELETE a1 FROM pre_common_member_profile AS a1 
INNER JOIN pre_ulogin_member AS a2
WHERE a1.uid=a2.uid;");   

DELETE a1 FROM pre_common_member AS a1 
INNER JOIN pre_ulogin_member AS a2
WHERE a1.uid=a2.uid;");

DROP TABLE IF EXISTS `pre_ulogin_member`;

EOF;

runquery($sql);

$finish = true;
?>