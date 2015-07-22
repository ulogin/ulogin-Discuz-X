<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$sql = <<<EOF

DROP TABLE IF EXISTS `pre_ulogin_member`;

EOF;

runquery($sql);

$finish = true;
?>