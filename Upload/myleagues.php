<?php

/**
 * MyLeagues 1.0 for MyBB
 * Main page
 * @author Filip Klar <kontakt@fklar.pl>
 */
 
define("IN_MYBB", 1);
define("THIS_SCRIPT", "myleagues.php");

require_once "./global.php";
require_once "./inc/class_myleagues.php";
$myleagues = new myleagues;
$lang->load("myleagues");

if($mybb->settings['myleagues'] == 0) { // Checks if it's activated.
	error_no_permission();	
}

// Gets the informations about the league from the database.
	
$lid = intval($mybb->input['lid']);
$league = $db->fetch_array($db->simple_select("myleagues_leagues", "*", "`lid` = {$lid}"));
$number_of_matches = $db->num_rows($db->simple_select("myleagues_matches", "`mid`", "`league` = {$lid}"));
$list_of_teams = array_filter(explode(";", $league['teams']));
$number_of_teams = count($list_of_teams);

$title = $league['name']." ".$league['season'];

if(empty($league) || ($league['public'] == "no" && $mybb->user['ismoderator'] !== 1) || ($number_of_matches == 0 && $mybb->input['action'] !== "teams") || ($number_of_teams == 0 && $mybb->input['action'] == "teams")) {
	error_no_permission();	
}


// Shows the league table.

if($mybb->input['action'] == "ranking") {
	
	if($league['wordforgoals']) {
		$wordforgoals = $league['wordforgoals'];
	}
	else {
		$wordforgoals = $lang->myleagues_goals;
	}
	
	
	// Processes the colors.
	
	$colors_array = explode("\n", $league['colors']);
	
	foreach($colors_array as $value) {
		$temp = explode("|", $value);
		$colors[$temp[0]] = trim($temp[1]);
	}
	
	$columns = explode(",", $league['columns']);
	
	
	// Prints the header of the table.	
	
	$content .= "<table border=\"0\" cellspacing=\"{$theme['borderwidth']}\" cellpadding=\"{$theme['tablespace']}\" class=\"tborder\">\n";
	$content .= "<thead>\n";
	$content .= "<tr><td class=\"thead\" colspan=\"10\"><strong>{$lang->myleagues_table} - {$league['name']} {$league['season']}</strong></td></tr>\n";
	$content .= "</thead>\n";
	
	$content .= "<tr>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_place}</strong></span></td>\n";
	$content .= "<td class=\"tcat\"><span class=\"smalltext align_left\"><strong>{$lang->myleagues_team}</strong></span></td>\n";
	
	if(in_array("points", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_points}</strong></span></td>\n";
	}
	
	if(in_array("goals", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$wordforgoals}</strong></span></td>\n";
	}
	
	if(in_array("difference", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_difference}</strong></span></td>\n";
	}
	
	if(in_array("matches", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_matches}</strong></span></td>\n";
	}
	
	if(in_array("wins", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_wins}</strong></span></td>\n";
	}
	
	if(in_array("draws", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_draws}</strong></span></td>\n";
	}
	
	if(in_array("losses", $columns)) {
		$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_losses}</strong></span></td>\n";
	}
	
	$content .= "</tr>\n";
	
	
	// Gets the rows of the league table from the database. 	
	
	if($league['sort'] == "goals") {
		$query = $db->query("SELECT `team`, `points`, `goalsfor`, `goalsagainst`, `goalsdifference`, `matches`, `wins`, `draws`, `losses` FROM `".TABLE_PREFIX."myleagues_rows` WHERE `league` = {$lid} ORDER BY `points` DESC, `goalsdifference` DESC, `goalsfor` DESC, `goalsagainst` ASC");
	}
	else {
		$query = $db->query("SELECT `team`, `points`, `goalsfor`, `goalsagainst`, `goalsdifference`, `matches`, `wins`, `draws`, `losses` FROM `".TABLE_PREFIX."myleagues_rows` WHERE `league` = {$lid} ORDER BY `points` DESC, `points2` DESC, `goalsdifference2` DESC, `goalsfor2` DESC, `goalsagainst2` ASC, `goalsdifference` DESC, `goalsfor` DESC, `goalsagainst` ASC");
	}
	
	while($row = $db->fetch_array($query)) {
		foreach($row as $name => $value) {
			$teams[$row['team']][$name] = $value;	
		}
	}
	
	$place = 0;
	
	foreach($teams as $tid => $team) {
		
		$place++;
		$class = $myleagues->trow();
		$team['name'] = $myleagues->get_name($team['team'], "teams");
		
		
		// Processes the color of the current row.
		
		unset($style);
		if(isset($colors[$place])) {
			$style = "style=\"background: {$colors[$place]};\"";
		}
		
		
		// Prints the row.
		
		$content .= "<tr>\n";
		$content .= "<td class=\"{$class}\" align=\"right\" width=\"20px\" {$style}>{$place}.</td>\n";
		$content .= "<td class=\"{$class}\" {$style}>{$team['name']}</td>\n";
		
		if(in_array("points", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['points']}</td>\n";
		}
		
		if(in_array("goals", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['goalsfor']}:{$team['goalsagainst']}</td>\n";
		}
		
		if(in_array("difference", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['goalsdifference']}</td>\n";
		}
		
		if(in_array("matches", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['matches']}</td>\n";
		}
		
		if(in_array("wins", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['wins']}</td>\n";
		}
		
		if(in_array("draws", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['draws']}</td>\n";
		}
		
		if(in_array("losses", $columns)) {
			$content .= "<td class=\"{$class}\" align=\"center\" width=\"7%\" {$style}>{$team['losses']}</td>\n";
		}
		
		$content .= "</tr>\n";
		
	}
	
	$content .= "</table>\n";
	
}


// Shows the league schedule.

elseif($mybb->input['action'] == "schedule") {
	
	// Prints the header of the table.	
	
	$content .= "<table border=\"0\" cellspacing=\"{$theme['borderwidth']}\" cellpadding=\"{$theme['tablespace']}\" class=\"tborder\">\n";
	$content .= "<thead>\n";
	$content .= "<tr><td class=\"thead\" colspan=\"4\"><strong>{$lang->myleagues_schedule} - {$league['name']} {$league['season']}</strong></td></tr>\n";
	$content .= "</thead>\n";

	
	// Loads all of the matchdays and matches.
	
	$query = $db->simple_select("myleagues_matchdays", "`mid`, `name`, `startdate`, `enddate`", "`league` = {$lid}", array('order_by' => "no", 'order_dir' => "ASC"));
	
	while($temp_matchday = $db->fetch_array($query)) {
		foreach($temp_matchday as $name => $value) {		
			$matchdays[$temp_matchday['mid']][$name] = $value;
		}	
	}
	
	$query = $db->simple_select("myleagues_matches", "`mid`, `matchday`, `dateline`, `hometeam`, `awayteam`, `homeresult`, `awayresult`", "`league` = {$lid}", array('order_by' => "dateline", 'order_dir' => "ASC"));
	
	while($temp_match = $db->fetch_array($query)) {
		foreach($temp_match as $name => $value) {		
			$matches[$temp_match['matchday']][$temp_match['mid']][$name] = $value;
		}	
	}
	
	
	// Show the matchdays.
	
	foreach($matchdays as $matchday) {
		
		$start = my_date($mybb->settings['dateformat'], $matchday['startdate']);
		$end   = my_date($mybb->settings['dateformat'], $matchday['enddate']);
		
		if($start == $end) {
			$time = $start;
		}
		else {
			$time = $start." - ".$end;
		}
	
		$content .= "<tr>\n";
		$content .= "<td class=\"tcat\" colspan=\"3\"><strong>{$matchday['name']}</strong></td><td class=\"tcat\" align=\"center\">{$time}</span></td>\n";
		$content .= "</tr>\n";
		
		foreach((array) $matches[$matchday['mid']] as $match) {
			
			$class = $myleagues->trow();
		
			$content .= "<tr cellspacing=\"0\">\n";
			$content .= "<td class=\"{$class}\" align=\"right\" width=\"35%\" style=\"padding-right: 10px;\">".$myleagues->get_name($match['hometeam'], "teams")."</td>\n";
			$content .= "<td class=\"{$class}\" align=\"center\">{$match['homeresult']}:{$match['awayresult']}</td>\n";
			$content .= "<td class=\"{$class}\" align=\"left\" width=\"35%\" style=\"padding-left: 10px;\">".$myleagues->get_name($match['awayteam'], "teams")."</td>\n";
			$content .= "<td class=\"{$class}\" align=\"center\">".my_date($mybb->settings['dateformat'], $match['dateline'])." ".my_date($mybb->settings['timeformat'], $match['dateline'])."</td>\n";
			$content .= "</tr>\n";
				
		}
		
	}
	
	$content .= "</table>\n";
	
}


// Shows the list of the teams.

elseif($mybb->input['action'] == "teams") {

	$content .= "<table border=\"0\" cellspacing=\"{$theme['borderwidth']}\" cellpadding=\"{$theme['tablespace']}\" class=\"tborder\">\n";
	$content .= "<thead>\n";
	$content .= "<tr><td class=\"thead\" colspan=\"6\"><strong>{$lang->myleagues_list_of_teams} - {$league['name']} {$league['season']}</strong></td></tr>\n";
	$content .= "</thead>\n";
	
	$content .= "<tr>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_crest}</strong></span></td>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_name}</strong></span></td>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_coach}</strong></span></td>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_ground}</strong></span></td>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_address}</strong></span></td>\n";
	$content .= "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->myleagues_website}</strong></span></td>\n";
	$content .= "</tr>\n";

	
	$query_teams = "SELECT * FROM `".TABLE_PREFIX."myleagues_teams` WHERE `tid` = 0";
	
	foreach($list_of_teams as $teamid) {
		$query_teams .= " OR `tid` = {$teamid}";	
	}
	
	$query_teams .= " ORDER BY `name` ASC";
	
	$query = $db->query($query_teams);
	
	while($team = $db->fetch_array($query)) {
		
		unset($crest);
		unset($website);
	
		$class = $myleagues->trow();
		$crest_url = $myleagues->crest_url($team['tid']);
		
		if($crest_url) {
			$crest = "<img src=\"{$crest_url}\" alt=\"crest\" style=\"max-width: 70px; max-height: 70px;\" />";
		}
		
		if($team['website']) {
			$website = "<a href=\"{$team['website']}\">{$team['website']}</a>";	
		}
		
		$content .= "<tr valign=\"middle\">\n";
		$content .= "<td class=\"{$class}\" align=\"center\">{$crest}</td>\n";
		$content .= "<td class=\"{$class}\">{$team['name']}</td>\n";
		$content .= "<td class=\"{$class}\">{$team['coach']}</td>\n";
		$content .= "<td class=\"{$class}\">{$team['ground']}</td>\n";
		$content .= "<td class=\"{$class}\">{$team['address']}</td>\n";
		$content .= "<td class=\"{$class}\">{$website}</td>\n";
		$content .= "</tr>\n";	
		
	}
	
	$content .= "</table>\n";
	
}


// Link. You can't remove it! It's my work :)

$content .= "<p class=\"smalltext\" style=\"text-align: right;\">{$lang->myleagues_powered_by} <a href=\"http://fklar.pl/tag/myleagues/\">MyLeagues</a></p>";


// Prints the ready page.

add_breadcrumb($title);
output_page("<html>\n<head>\n<title>{$mybb->settings['bbname']} - {$title}</title>\n{$headerinclude}\n</head>\n<body>\n{$header}\n{$content}\n{$boardstats}\n{$footer}\n</body>\n</html>");
 
?>