<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS `pre_ulogin_member` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL,
  `identity` varchar(255)  NOT NULL,
  `network` varchar(255)  NOT NULL,
  PRIMARY KEY (`id`)
)
ENGINE = MyISAM;
EOF;

runquery($sql);

$finish = true;
?>
