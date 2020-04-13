<?php

/**
 * MyLeagues by Filip Klar 2012
 * Plugin file
 * @author Filip Klar <kontakt@fklar.pl>
 */

if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("admin_config_menu", "myleagues_admin_menu");
$plugins->add_hook("admin_home_menu_quick_access", "myleagues_admin_menu");
$plugins->add_hook("admin_config_action_handler", "myleagues_admin_action_handler");


function myleagues_info() {
	
	global $lang;
	$lang->load("myleagues");
	
	return array(
		'name'			 => "MyLeagues",
		'description'	 => $lang->myleagues_plugin_description,
		'website'		 => "http://fklar.pl/tag/myleagues/",
		'author'		    => "Filip Klar",
		'authorsite'	 => "http://fklar.pl/",
		'version'	  	 => "1.0",
		'guid' 			 => "f3a93b453b3fa2b43f2c98c284457f75",
		'compatibility' => "16*"
	);
	
}


function myleagues_install() {

	global $db;
	
	$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."myleagues_leagues` (
		`lid` int(10) NOT NULL AUTO_INCREMENT,
		`name` varchar(120) NOT NULL,
		`public` enum('0','1') NOT NULL DEFAULT '0',
		`season` varchar(50) NOT NULL,
		`teams` varchar(200) NOT NULL,
		`modified` bigint(30) NOT NULL,
		`pointsforwin` int(2) NOT NULL DEFAULT '3',
		`pointsfordraw` int(2) NOT NULL DEFAULT '1',
		`pointsforloss` int(2) NOT NULL DEFAULT '0',
		`sort` enum('goals','direct') NOT NULL DEFAULT 'goals',
		`colors` text NOT NULL,
		`wordforgoals` varchar(20) NOT NULL,
		`columns` varchar(100) NOT NULL DEFAULT 'points,goals,difference,matches,wins,draws,losses',
		`extrapoints` VARCHAR(200) NOT NULL,
		PRIMARY KEY (`lid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
	
	$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."myleagues_matchdays` (
		`mid` int(10) NOT NULL AUTO_INCREMENT,
		`no` int(10) NOT NULL,
		`name` varchar(30) NOT NULL,
		`league` int(10) NOT NULL,
		`startdate` bigint(30) NOT NULL,
		`enddate` bigint(30) NOT NULL,
		PRIMARY KEY (`mid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
	
	$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."myleagues_matches` (
		`mid` int(10) NOT NULL AUTO_INCREMENT,
		`league` int(10) NOT NULL,
		`matchday` int(10) NOT NULL,
		`dateline` bigint(30) NOT NULL,
		`hometeam` int(10) NOT NULL,
		`awayteam` int(10) NOT NULL,
		`homeresult` int(5) DEFAULT NULL,
		`awayresult` int(5) DEFAULT NULL,
		PRIMARY KEY (`mid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
	
	$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."myleagues_rows` (
		`rid` int(10) NOT NULL AUTO_INCREMENT,
		`league` int(10) NOT NULL,
		`team` int(10) NOT NULL,
		`points` int(5) NOT NULL DEFAULT '0',
		`goalsfor` int(5) NOT NULL DEFAULT '0',
		`goalsagainst` int(5) NOT NULL DEFAULT '0',
		`goalsdifference` int(5) NOT NULL DEFAULT '0',
		`matches` int(5) NOT NULL DEFAULT '0',
		`wins` int(5) NOT NULL DEFAULT '0',
		`draws` int(5) NOT NULL DEFAULT '0',
		`losses` int(5) NOT NULL DEFAULT '0',
		`points2` int(5) NOT NULL DEFAULT '0',
		`goalsfor2` int(5) NOT NULL DEFAULT '0',
		`goalsagainst2` int(5) NOT NULL DEFAULT '0',
		`goalsdifference2` int(5) NOT NULL DEFAULT '0',
		PRIMARY KEY (`rid`),
		UNIQUE KEY `league` (`league`,`team`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
	
	$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."myleagues_teams` (
		`tid` int(10) NOT NULL AUTO_INCREMENT,
		`name` varchar(100) NOT NULL,
		`coach` varchar(50) NOT NULL,
		`ground` varchar(100) NOT NULL,
		`address` varchar(50) NOT NULL,
		`website` varchar(50) NOT NULL,
		`modified` bigint(30) NOT NULL,
		PRIMARY KEY (`tid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
	
	$config = array(
		'sid'				=> "NULL",
		'name'			=> "myleagues",
		'title'			=> "MyLeagues",
		'description'	=> "",
		'optionscode'	=> "yesno",
		'value'			=> 1,
		'disporder'		=> 0,
		'gid'				=> 0,
	);
	
	$db->insert_query("settings", $config);
	rebuild_settings();
	
}


function myleagues_is_installed() {
 
	global $db;

	if($db->table_exists("myleagues_leagues")) {
		return TRUE;
	}
	else {
		return FALSE;
	}
	
}


function myleagues_uninstall() {

	global $db;
	
	$db->query("DROP TABLE `".TABLE_PREFIX."myleagues_leagues`");
	$db->query("DROP TABLE `".TABLE_PREFIX."myleagues_teams`");
	$db->query("DROP TABLE `".TABLE_PREFIX."myleagues_matchdays`");
	$db->query("DROP TABLE `".TABLE_PREFIX."myleagues_matches`");
	$db->query("DROP TABLE `".TABLE_PREFIX."myleagues_rows`");
	
}


function myleagues_activate() {

	global $db;
	
	$config = array(
		'value' => 1
	);
	
	$db->update_query("settings", $config, "`name` = 'myleagues'");
	rebuild_settings();
	
}


function myleagues_deactivate() {

	global $db;
	
	$config = array(
		'value' => 0
	);
	
	$db->update_query("settings", $config, "`name` = 'myleagues'");
	rebuild_settings();
	
}


function myleagues_admin_menu(&$sub_menu) {
	
	$sub_menu[] = array(
		'id'    => "myleagues",
		'title' => "MyLeagues",
		'link'  => "index.php?module=config-myleagues"
	);  	
	
}

function myleagues_admin_action_handler(&$action) {
	 
	$action['myleagues'] = array(
		'active' => "myleagues",
		'file'   => "myleagues.php"
	);	
	
}


?>