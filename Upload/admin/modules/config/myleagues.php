<?php

/**
 * MyLeagues 1.0 for MyBB
 * ACP file
 * @author Filip Klar <kontakt@fklar.pl>
 */

if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br />Please make sure IN_MYBB is defined.");
}

require_once("../inc/class_myleagues.php");
require_once("../inc/functions_upload.php");
$myleagues = new myleagues;
$lang->load("myleagues");

ob_start();

$page->extra_header .= "<link rel=\"stylesheet\" href=\"styles/myleagues.css\" type=\"text/css\" />\n";


// Tabs.

$sub_tabs['leagues'] = array(
	'title'       => $lang->myleagues_leagues,
	'link'        => "index.php?module=config-myleagues",
	'description' => $lang->myleagues_leagues_tab_description
);

$sub_tabs['matches'] = array(
	'title'       => $lang->myleagues_matches,
	'link'        => "index.php?module=config-myleagues&amp;action=matchdays",
	'description' => $lang->myleagues_matches_tab_description
);

$sub_tabs['teams'] = array(
	'title'       => $lang->myleagues_teams,
	'link'        => "index.php?module=config-myleagues&amp;action=teams",
	'description' => $lang->myleagues_teams_tab_description
);

$sub_tabs['links'] = array(
	'title'       => $lang->myleagues_links,
	'link'        => "index.php?module=config-myleagues&amp;action=links",
	'description' => $lang->myleagues_links_tab_description
);


// Selects current subpage.

$subpage = array(
	'matchdays'     => "matches",
	'matches'       => "matches",
	'edit_matchday' => "matches",
	'edit_match'    => "matches",
	'teams'         => "teams",
	'edit_team'     => "teams",
	'links'         => "links"
);

if($subpage[$mybb->input['action']]) {
	$current_sub = $subpage[$mybb->input['action']];
}
else {
	$current_sub = "leagues";
}


// Pagination settings.

if($mybb->input['page']) {
	$current_page = $mybb->input['page'];
}
else {
	$current_page = 1;
}

$per_page = 10;
$first_item = $per_page*($current_page-1);
$multiselect_size = 10;


// Adds a new team.

if($mybb->input['action'] == "add_team") {
	
	if(empty($mybb->input['name'])) {
		flash_message($lang->myleagues_add_message_error, "error");
		admin_redirect("index.php?module=config-myleagues&amp;action=teams");	
	}
	
	$new_team = array(
		'tid'      => "NULL",
		'name'     => $db->escape_string($mybb->input['name']),
		'modified' => TIME_NOW
	);
	
	$db->insert_query("myleagues_teams", $new_team);
	
	flash_message($lang->myleagues_add_team_message_success, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=teams");
		
}


// Updates the team.

if($mybb->input['action'] == "update_team") {
	
	$crest_url = $myleagues->crest_url($mybb->input['tid'], "acp");
	
	if($mybb->input['delete_crest'] == "yes") {
		@unlink($crest_url);
	}
	
	switch($_FILES['crest']['type']) {
		case "image/png":
			$sufix = "png";
			break;
		default:
			$sufix = "jpg";
	}
			
	if($_FILES['crest']['type'] == "image/png" || $_FILES['crest']['type'] == "image/jpeg") {
		@unlink($crest_url);
		upload_file($_FILES['crest'], "../uploads/crests", "{$mybb->input['tid']}.{$sufix}");	
	}

	$edit_team = array(
		'name'    => $db->escape_string($mybb->input['name']),
		'coach'   => $db->escape_string($mybb->input['coach']),
		'ground'  => $db->escape_string($mybb->input['ground']),
		'address' => $db->escape_string($mybb->input['address']),
		'website' => $db->escape_string($mybb->input['website'])
	);
	
	$myleagues->check_id($mybb->input['tid'], "teams");
	$db->update_query("myleagues_teams", $edit_team, "`tid` = {$mybb->input['tid']}");
	$myleagues->update_modified($mybb->input['tid'], "teams");
	flash_message($lang->myleagues_update_team_message, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=teams");
	
}


// Deletes the team.

if($mybb->input['action'] == "delete_team" && $mybb->input['my_post_key'] == $mybb->post_code) {
	
	$myleagues->check_id($mybb->input['tid'], "teams");
	$db->delete_query("myleagues_teams", "`tid` = '{$mybb->input['tid']}'");
	flash_message($lang->myleagues_delete_team_message, 'success');
	admin_redirect("index.php?module=config-myleagues&amp;action=teams");
	
}


// Adds a new matchday.

if($mybb->input['action'] == "add_matchday") {

	$startdate = mktime(0, 0, 0, $mybb->input['startdate_month'], $mybb->input['startdate_day'], $mybb->input['startdate_year']);
	$enddate = mktime(0, 0, 0, $mybb->input['enddate_month'], $mybb->input['enddate_day'], $mybb->input['enddate_year']);

	if(empty($mybb->input['no']) || empty($mybb->input['name']) || checkdate($mybb->input['startdate_month'], $mybb->input['startdate_day'], $mybb->input['startdate_year']) == FALSE || checkdate($mybb->input['enddate_month'], $mybb->input['enddate_day'], $mybb->input['enddate_year']) == FALSE || $startdate > $enddate) {
		flash_message($lang->myleagues_add_message_error, "error");
		admin_redirect("index.php?module=config-myleagues&amp;action=matchdays");	
	}
	
	$new_matchday = array(
		'no'        => (int) $mybb->input['no'],
		'name'      => $db->escape_string($mybb->input['name']),
		'league'    => $mybb->input['league'],
		'startdate' => $startdate,
		'enddate'   => $enddate
	);
	
	$db->insert_query("myleagues_matchdays", $new_matchday);

	$myleagues->update_modified($mybb->input['league'], "leagues");
	flash_message($lang->myleagues_add_matchday_message_success, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=matchdays");
	
}


// Deletes the matchday.

if($mybb->input['action'] == "delete_matchday" && $mybb->input['my_post_key'] == $mybb->post_code) {

	$myleagues->check_id($mybb->input['mid'], "matchdays");
	$db->delete_query("myleagues_matchdays", "`mid` = {$mybb->input['mid']}");
	$db->delete_query("myleagues_matches", "`matchday` = {$mybb->input['mid']}");
	$myleagues->update_rank($myleagues->current_league);
	flash_message($lang->myleagues_delete_matchday_message, 'success');
	admin_redirect("index.php?module=config-myleagues&amp;action=matchdays");
	
}


// Adds a new match.

if($mybb->input['action'] == "add_match") {
	
	if($mybb->input['match_home'] == $mybb->input['match_away']) {
		flash_message($lang->myleagues_add_message_error, "error");
		admin_redirect("index.php?module=config-myleagues&amp;action=matches&amp;mid={$mybb->input['matchday']}");
	}
	
	$matchday = $db->fetch_array($db->simple_select("myleagues_matchdays", "`startdate`", "`mid` = {$mybb->input['matchday']}"));
	$date = mktime(12, 0, 0, date("m", $matchday['startdate']), date("d", $matchday['startdate']), date("Y", $matchday['startdate']));
	
	$new_match = array(
		'league'   => $myleagues->current_league,
		'matchday' => $mybb->input['matchday'],
		'dateline' => $date,
		'hometeam' => $mybb->input['match_home'],
		'awayteam' => $mybb->input['match_away']
	);
	
	$db->insert_query("myleagues_matches", $new_match);
	$myleagues->update_modified($myleagues->current_league, "leagues");
	flash_message($lang->myleagues_add_match_message_success, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=matches&amp;mid={$mybb->input['matchday']}");
	
}


// Updates the matches.

if($mybb->input['action'] == "update_matches") {
	
	$list_of_matches = array_filter(explode(";", $mybb->input['list_of_matches']));
	
	foreach($list_of_matches as $mid) {
		
		$time   = explode(":", $mybb->input['match_'.$mid.'_time']);
		$hour   = $time[0];
		$minute = $time[1];
		$day    = $mybb->input['match_'.$mid.'_day'];
		$month  = $mybb->input['match_'.$mid.'_month'];
		$year   = $mybb->input['match_'.$mid.'_year'];
		
		if(checkdate($month, $day, $year) == TRUE) {
			$date =  mktime($hour, $minute, 0, $month, $day, $year);
		}
		else {
			$date = $mybb->input['match_'.$mid.'_date'];
		}
	
		$edit_match = array(
			'dateline' => $date,
			'hometeam' => $mybb->input['match_'.$mid.'_home'],
			'awayteam' => $mybb->input['match_'.$mid.'_away']
		);
		

		if(is_numeric($mybb->input['match_'.$mid.'_homeresult']) && is_numeric($mybb->input['match_'.$mid.'_awayresult'])) {
			$homeresult = intval($mybb->input['match_'.$mid.'_homeresult']);
			$awayresult = intval($mybb->input['match_'.$mid.'_awayresult']);
		}
		else {
			$homeresult = "NULL";
			$awayresult = "NULL";
		}
		
		$db->update_query("myleagues_matches", $edit_match, "`mid` = {$mid}");
		$db->query("UPDATE `".TABLE_PREFIX."myleagues_matches` SET `homeresult` = {$homeresult}, `awayresult` = {$awayresult} WHERE `mid` = {$mid}");
		
	}
	
	$start['day']   = $mybb->input['matchday_start_day'];
	$start['month'] = $mybb->input['matchday_start_month'];
	$start['year']  = $mybb->input['matchday_start_year'];
	$end['day']     = $mybb->input['matchday_end_day'];
	$end['month']   = $mybb->input['matchday_end_month'];
	$end['year']    = $mybb->input['matchday_end_year'];
	
	if(checkdate($start['month'], $start['day'], $start['year']) == TRUE && checkdate($end['month'], $end['day'], $end['year']) == TRUE) {
		$start['date'] = mktime(0, 0, 0, $start['month'], $start['day'], $start['year']);
		$end['date']   = mktime(0, 0, 0, $end['month'], $end['day'], $end['year']);
		$db->query("UPDATE `".TABLE_PREFIX."myleagues_matchdays` SET `startdate` = {$start['date']}, `enddate` = {$end['date']} WHERE `mid` = {$mybb->input['matchday']}");
	}
	
	$myleagues->update_rank($myleagues->current_league);
	flash_message($lang->myleagues_update_matches_message, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=matches&amp;mid={$mybb->input['matchday']}");

}


// Deletes the match.

if($mybb->input['action'] == "delete_match" && $mybb->input['my_post_key'] == $mybb->post_code) {

	$myleagues->check_id($mybb->input['mid'], "matches");
	$myleagues->update_rank($myleagues->current_league);
	$db->delete_query("myleagues_matches", "`mid` = '{$mybb->input['mid']}'");
	flash_message($lang->myleagues_delete_match_message, 'success');
	admin_redirect("index.php?module=config-myleagues&amp;action=matches&amp;mid={$mybb->input['matchday']}");
	
}


// Adds a new league.

if($mybb->input['action'] == "add_league") {
	
	if(empty($mybb->input['name']) || empty($mybb->input['season'])) {
		flash_message($lang->myleagues_add_message_error, "error");
		admin_redirect("index.php?module=config-myleagues&amp;action=leagues");	
	}
	
	$teams_list = "";		
	
	if($mybb->input['team']) {
		
		foreach($mybb->input['team'] as $id) {
			$teams_list .= $id.";";
		}
			
	}
	
	$new_league = array(
		'lid'      => "NULL",
		'name'     => $db->escape_string($mybb->input['name']),
		'public'   => 0,
		'season'   => $db->escape_string($mybb->input['season']),
		'teams'    => $teams_list,
		'modified' => TIME_NOW,
		'colors'   => "1|#00CC33"
	);
	
	$db->insert_query("myleagues_leagues", $new_league);
	
	flash_message($lang->myleagues_add_league_message_success, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=leagues");
		
}


// Updates the league.

if($mybb->input['action'] == "update_league") {
	
	if($mybb->input['team']) {
		$teams = implode(";", $mybb->input['team']);
	}
	
	if(count($mybb->input['extra']) > 0) {
		foreach($mybb->input['extra'] as $tid => $points) {
			$extra_points .= $tid.":".intval($points).";";	
		}
	}

	$edit_league = array(
		'name'          => $db->escape_string($mybb->input['name']),
		'public'        => $mybb->input['public'],
		'season'        => $db->escape_string($mybb->input['season']),
		'teams'         => $teams,
		'pointsforwin'  => (int) $db->escape_string($mybb->input['pointsforwin']),
		'pointsfordraw' => (int) $db->escape_string($mybb->input['pointsfordraw']),
		'pointsforloss' => (int) $db->escape_string($mybb->input['pointsforloss']),
		'sort'          => $mybb->input['sort'],
		'colors'        => $db->escape_string($mybb->input['colors']),
		'wordforgoals'  => $db->escape_string($mybb->input['wordforgoals']),
		'columns'       => $db->escape_string(implode(",", $mybb->input['column'])),
		'extrapoints'   => $db->escape_string($extra_points)
	);
	
	$myleagues->check_id($mybb->input['lid'], "leagues");
	$db->update_query("myleagues_leagues", $edit_league, "`lid` = {$mybb->input['lid']}");
	$myleagues->update_modified($mybb->input['lid'], "leagues");
	$myleagues->update_rank($myleagues->current_league);
	flash_message($lang->myleagues_update_league_message, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=leagues");
	
}


// Publishes the league.

if($mybb->input['action'] == "public_league") {

	$edit_league = array(
		'public' => 1
	);
	
	$myleagues->check_id($mybb->input['lid'], "leagues");
	$db->update_query("myleagues_leagues", $edit_league, "`lid` = '{$mybb->input['lid']}'");
	$myleagues->update_modified($mybb->input['lid'], "leagues");
	flash_message($lang->myleagues_public_league_message, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=leagues");
	
}


// Hides the league.

if($mybb->input['action'] == "hide_league") {

	$edit_league = array(
		'public' => 0
	);
	
	$myleagues->check_id($mybb->input['lid'], "leagues");
	$db->update_query("myleagues_leagues", $edit_league, "`lid` = '{$mybb->input['lid']}'");
	$myleagues->update_modified($mybb->input['lid'], "leagues");
	flash_message($lang->myleagues_hide_league_message, "success");
	admin_redirect("index.php?module=config-myleagues&amp;action=leagues");
	
}


// Deletes the league.

if($mybb->input['action'] == "delete_league" && $mybb->input['my_post_key'] == $mybb->post_code) {
	
	$myleagues->check_id($mybb->input['lid'], "leagues");
	$db->delete_query("myleagues_leagues", "`lid` = {$mybb->input['lid']}");
	$db->delete_query("myleagues_matchdays", "`league` = {$mybb->input['lid']}");
	$db->delete_query("myleagues_matches", "`league` = {$mybb->input['lid']}");
	flash_message($lang->myleagues_delete_league_message, 'success');
	admin_redirect("index.php?module=config-myleagues&amp;action=leagues");
	
}


// Displays the header.

$page->add_breadcrumb_item("MyLeagues", "index.php?module=config-myleagues");
$page->output_header("MyLeagues");
$page->output_nav_tabs($sub_tabs, $current_sub);


// Displays page.

// Links.

if($mybb->input['action'] == "links") {
	
	$table = new Table;
	
	$query = $db->simple_select("myleagues_leagues", "`lid`, `name`", "", array('order_by' => "modified", 'order_dir' => "DESC"));
	
	while($league = $db->fetch_array($query)) {
		
		$table->construct_cell("<strong>{$league['name']}</strong>", array('colspan' => 2));
		$table->construct_row();
		
		$table->construct_cell($lang->myleagues_table, array('width' => "30%"));
		$table_url = "{$mybb->settings['bburl']}/myleagues.php?action=ranking&lid={$league['lid']}";
		$table->construct_cell("<a href=\"{$table_url}\">{$table_url}</a>");
		$table->construct_row();
		
		$table->construct_cell($lang->myleagues_schedule);
		$schedule_url = "{$mybb->settings['bburl']}/myleagues.php?action=schedule&lid={$league['lid']}";
		$table->construct_cell("<a href=\"{$schedule_url}\">{$schedule_url}</a>");
		$table->construct_row();
		
		$table->construct_cell($lang->myleagues_list_of_teams);
		$teams_url = "{$mybb->settings['bburl']}/myleagues.php?action=teams&lid={$league['lid']}";
		$table->construct_cell("<a href=\"{$teams_url}\">{$teams_url}</a>");
		$table->construct_row();
		
	}
	
	$table->output($lang->myleagues_links);
	
}


// Matchdays view.

elseif($mybb->input['action'] == "matchdays") {
	
	if($mybb->input['current_league']) {
		$myleagues->update_modified($mybb->input['current_league'], "leagues");
	}

	$form = new Form("index.php?module=config-myleagues&amp;action=matchdays", "post", "modify");
	$select =  $form->generate_select_box("current_league", $myleagues->get_leagues(), $myleagues->current_league);
	$button = $form->generate_submit_button($lang->ok);
	echo "<div class=\"\" style=\"padding-bottom: 3px; padding-top: 3px; margin-top: -9px; text-align: right;\">{$select} {$button}</div>";
	$form->end();

	$table = new Table;
	$table->construct_header($lang->myleagues_number_short, array('class' => "align_center"));
	$table->construct_header($lang->myleagues_name, array('class' => "align_left", 'width' => "40%"));
	$table->construct_header($lang->myleagues_date, array('class' => "align_center", 'width' => "25%"));
	$table->construct_header($lang->controls, array('class' => "align_center", 'colspan' => 2));
	
	$number_of_matchdays = 0;
	
	$query = $db->simple_select("myleagues_matchdays", "`mid`, `no`, `name`, `league`, `startdate`, `enddate`", "`league` = '{$myleagues->current_league}'", array('order_by' => "no", 'order_dir' => "ASC"));
	
	while($matchday = $db->fetch_array($query)) {
	
		$number_of_matchdays++;
		$new_matchday_no = $matchday['no']+1;
		
		$table->construct_cell($matchday['no'], array('class' => "align_center"));
		$table->construct_cell("<strong>".$matchday['name']."</strong>", array('class' => "align_left"));
		$table->construct_cell(my_date($mybb->settings['dateformat'], $matchday['startdate'])." - ".my_date($mybb->settings['dateformat'], $matchday['enddate']), array('class' => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=matches&amp;mid={$matchday['mid']}\">{$lang->myleagues_matches}</a>", array('class' => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=delete_matchday&amp;mid={$matchday['mid']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->myleagues_confirm_matchday_deletion}')\">{$lang->delete}</a>", array('class' => "align_center"));
		$table->construct_row();
		
	}
	
	if($number_of_matchdays == 0) {
		$table->construct_cell($lang->myleagues_no_matchdays, array('class' => "align_left", 'colspan' => 5));
		$table->construct_row();
	}
	
	$table->output($lang->myleagues_matchdays." - ".$myleagues->get_name($myleagues->current_league, "leagues"));
	
	if(empty($new_matchday_no)) {
		$new_matchday_no = 1;
	}
	
	// The form of adding a new matchday.

	$form = new Form("index.php?module=config-myleagues&amp;action=add_matchday&amp;league={$myleagues->current_league}", "post", "add");
	$form_container = new FormContainer($lang->myleagues_add_matchday);
	$form_container->output_row($lang->myleagues_number." <em>*</em>", "", $form->generate_text_box("no", $new_matchday_no, array('id' => "no")), "no");
	$form_container->output_row($lang->myleagues_name." <em>*</em>", "", $form->generate_text_box("name", $myleagues->get_ordinal($new_matchday_no)." ".strtolower($lang->myleagues_matchday), array('id' => "name")), "name");
	$form_container->output_row($lang->myleagues_start." <em>*</em>", "", $form->generate_date_select("startdate"), "startdate");
	$form_container->output_row($lang->myleagues_end." <em>*</em>", "", $form->generate_date_select("enddate"), "enddate");
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->myleagues_add_matchday);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
}


// Matches view.

elseif($mybb->input['action'] == "matches") {
	
	$matchday = $db->fetch_array($db->simple_select("myleagues_matchdays", "*", "`mid` = {$mybb->input['mid']}"));
	$options = $myleagues->get_teams($matchday['league']);
	
	if(count($options) == 0) {
		flash_message($lang->myleagues_no_teams_in_league, 'error');
		admin_redirect("index.php?module=config-myleagues&amp;action=matchdays");
	}
	
	$form = new Form("index.php?module=config-myleagues&amp;action=update_matches&amp;matchday={$mybb->input['mid']}", "post", "update");

	$table = new Table;
	$table->construct_header("ID", array('class' => "align_center"));
	$table->construct_header($lang->myleagues_date, array('class' => "align_center"));
	$table->construct_header($lang->myleagues_time, array('class' => "align_center"));
	$table->construct_header($lang->myleagues_home, array('class' => "align_center"));
	$table->construct_header($lang->myleagues_score, array('class' => "align_center"));	
	$table->construct_header($lang->myleagues_away, array('class' => "align_center"));	
	$table->construct_header($lang->controls, array('class' => "align_center"));
	
	$number_of_matches = 0;
	$list_of_matches = "";
	
	$query = $db->simple_select("myleagues_matches", "`mid`, `matchday`, `league`, `dateline`, `hometeam`, `awayteam`, `homeresult`, `awayresult`", "`matchday` = '{$mybb->input['mid']}'", array('order_by' => "dateline", 'order_dir' => "ASC"));
		
	while($match = $db->fetch_array($query)) {	

		$table->construct_cell($match['mid'], array('class' => "align_center"));
		$table->construct_cell($form->generate_hidden_field("match_{$match['mid']}_date", $match['dateline']).$form->generate_date_select("match_{$match['mid']}", date("d", $match['dateline']), date("m", $match['dateline']), date("Y", $match['dateline'])), array('class' => "align_center"));
		$table->construct_cell($form->generate_text_box("match_{$match['mid']}_time", date("H:i", $match['dateline']), array('id' => "time", 'style' => "width: 50px;")), array('class' => "align_center"));
		$table->construct_cell($form->generate_select_box("match_{$match['mid']}_home", $options, $match['hometeam'], array('length' => "150px")), array('class' => "align_center"));
		$table->construct_cell($form->generate_text_box("match_{$match['mid']}_homeresult", $match['homeresult'], array('id' => "homeresult", 'style' => "width: 25px;"))." : ".$form->generate_text_box("match_{$match['mid']}_awayresult", $match['awayresult'], array('id' => "awayresult", 'style' => "width: 25px;")), array('class' => "align_center"));
		$table->construct_cell($form->generate_select_box("match_{$match['mid']}_away", $options, $match['awayteam']), array('class' => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=delete_match&amp;mid={$match['mid']}&amp;matchday={$match['matchday']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->myleagues_confirm_match_deletion}')\">{$lang->delete}</a>", array('class' => "align_center"));
		$table->construct_row();
		$number_of_matches++;
		$list_of_matches .= $match['mid'].";";
		
	}
	
	if($number_of_matches == 0) {
		$table->construct_cell($lang->myleagues_no_matches, array('class' => "align_left", 'colspan' => 8));
		$table->construct_row();
	}
	
	$table->construct_cell($lang->myleagues_matchday_start.": ".$form->generate_date_select("matchday_start", date("d", $matchday['startdate']), date("m", $matchday['startdate']), date("Y", $matchday['startdate']))."&nbsp; &nbsp;".$lang->myleagues_matchday_end.": ".$form->generate_date_select("matchday_end", date("d", $matchday['enddate']), date("m", $matchday['enddate']), date("Y", $matchday['enddate'])), array('class' => "align_left", 'colspan' => 8));
	$table->construct_row();
	
	$table->output($matchday['name']." - ".$myleagues->get_name($myleagues->current_league, "leagues"));
	
	if($number_of_matches > 0) {
		$buttons[] = $form->generate_submit_button($lang->myleagues_save);
		$form->output_submit_wrapper($buttons);
	}
	
	echo $form->generate_hidden_field("list_of_matches", $list_of_matches);
	$form->end();
	echo "<br />";
	
	// The form of adding a new match.
	
	$myleagues->match_form($options);
	
}


// Teams view.

elseif($mybb->input['action'] == "teams") {

	// Pagination.
	
	if($myleagues->cache['teams'] > $per_page) {
		$pagination = draw_admin_pagination($current_page, $per_page, $myleagues->cache['teams'], "index.php?module=config-myleagues&amp;action=teams&amp;page={page}");
	}
	
	// Teams table.
	
	echo $pagination;
	
	$table = new Table;
	$table->construct_header("ID", array('class' => "align_center"));
	$table->construct_header($lang->myleagues_name, array('class' => "align_left"));
	$table->construct_header($lang->controls, array('class' => "align_center", 'colspan' => 2));
	
	if($myleagues->cache['teams'] == 0) {
		$table->construct_cell($lang->myleagues_no_teams, array('class' => "align_left", 'colspan' => 4));
		$table->construct_row();
	}	
	
	else {
	
		$query = $db->query("SELECT `tid`, `name` FROM `".TABLE_PREFIX."myleagues_teams` ORDER BY `modified` DESC, `tid` DESC LIMIT {$first_item}, {$per_page}");
		
		while($team = $db->fetch_array($query)) {	
		
			$table->construct_cell($team['tid'], array('class' => "align_center"));
			$table->construct_cell("<strong>".$team['name']."</strong>", array('class' => "align_left"));
			$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=edit_team&amp;tid={$team['tid']}\">{$lang->edit}</a>", array('class' => "align_center"));
			$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=delete_team&amp;tid={$team['tid']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->myleagues_confirm_team_deletion}')\">{$lang->delete}</a>", array('class' => "align_center"));
			$table->construct_row();
			
		}
	
	}
	
	$table->output($lang->myleagues_teams);
	
	echo $pagination;
	
	// The form of adding a new team.
	
	$form = new Form("index.php?module=config-myleagues&amp;action=add_team", "post", "add");
	$form_container = new FormContainer($lang->myleagues_add_team);
	$form_container->output_row($lang->myleagues_name." <em>*</em>", "", $form->generate_text_box("name", "", array('id' => "name")), "name");
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->myleagues_add_team);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
}


// Team editing view.

elseif($mybb->input['action'] == "edit_team") {
	
	$myleagues->check_id($mybb->input['tid'], "teams");
	$team = $db->fetch_array($db->simple_select("myleagues_teams", "*", "`tid` = {$mybb->input['tid']}"));
	$crest_url = $myleagues->crest_url($mybb->input['tid'], "acp");
	
	$form = new Form("index.php?module=config-myleagues&amp;action=update_team&amp;tid={$mybb->input['tid']}", "post", "edit", 1);
	$form_container = new FormContainer($lang->myleagues_edit_team);
	$form_container->output_row($lang->myleagues_name." <em>*</em>", "", $form->generate_text_box("name", $team['name'], array('id' => "name")), "name");
	$form_container->output_row($lang->myleagues_ground, $lang->myleagues_ground_description, $form->generate_text_box("ground", $team['ground'], array('id' => "ground")), "ground");
	$form_container->output_row($lang->myleagues_coach, "", $form->generate_text_box("coach", $team['coach'], array('id' => "coach")), "coach");
	$form_container->output_row($lang->myleagues_new_crest, $lang->myleagues_new_crest_description, $form->generate_file_upload_box("crest", array('id' => "crest")), "crest");
	
	if($crest_url) {
		$crest = "<img src=\"{$crest_url}\" alt=\"crest\" style=\"max-width: 100px; max-height: 100px; margin: 0px;\" />";
		$form_container->output_row($lang->myleagues_current_crest, "", $crest."<br />".$form->generate_check_box("delete_crest", "yes", $lang->delete, array('id' => "delete_crest")), "delete_crest");
	}
	
	$form_container->output_row($lang->myleagues_address, "", $form->generate_text_box("address", $team['address'], array('id' => "address")), "address");
	$form_container->output_row($lang->myleagues_website, "", $form->generate_text_box("website", $team['website'], array('id' => "website")), "website");
	
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->myleagues_save);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
}


// League editing view.

elseif($mybb->input['action'] == "edit_league" && $mybb->input['lid']) {
	
	$myleagues->check_id($mybb->input['lid'], "leagues");
	
	$league = $db->fetch_array($db->simple_select("myleagues_leagues", "*", "`lid` = {$mybb->input['lid']}"));
	
	$all_teams = $myleagues->get_teams();

	$selected = array_filter(explode(";", $league['teams']));
	$number_of_teams = count($selected);
	
	$columns_options = array(
		'points'     => $lang->myleagues_points,
		'goals'      => $lang->myleagues_goals,
		'difference' => $lang->myleagues_difference,
		'matches'    => $lang->myleagues_matches,
		'wins'       => $lang->myleagues_wins,
		'draws'      => $lang->myleagues_draws,
		'losses'     => $lang->myleagues_losses
	);
	
	$columns_selected = array_filter(explode(",", $league['columns']));
	
	$all_extra = array_filter(explode(";", $league['extrapoints']));

	$form = new Form("index.php?module=config-myleagues&amp;action=update_league&amp;lid={$mybb->input['lid']}", "post", "edit");
	
	foreach($all_extra as $current) {
		$temp = explode(":", $current);
		$extra[$temp[0]] = $temp[1];
	}
	
	if($number_of_teams > 0) {
		foreach($selected as $tid) {
			$extra_points .= $form->generate_text_box("extra[{$tid}]", intval($extra[$tid]), array('class' => "smallint"))." ".$myleagues->get_name($tid, "teams")."<br />";
		}
	}
	
	$form_container = new FormContainer($lang->myleagues_edit_league);
	$form_container->output_row($lang->myleagues_name." <em>*</em>", "", $form->generate_text_box("name", $league['name'], array('id' => "name")), "name");
	$form_container->output_row($lang->myleagues_published." <em>*</em>", $lang->myleagues_published_description, $form->generate_yes_no_radio("public", $league['public']), "public");
	$form_container->output_row($lang->myleagues_season." <em>*</em>", "", $form->generate_text_box("season", $league['season'], array('id' => "season")), "season");
	
	if($myleagues->cache['teams'] > 0) {
		$form_container->output_row($lang->myleagues_teams, $lang->myleagues_multiselect_description, $form->generate_select_box("team[]", $all_teams, $selected, array('multiple' => "multiple", 'size' => $multiselect_size)));
	}
	$form_container->output_row($lang->myleagues_pointsforwin." <em>*</em>", "", $form->generate_text_box("pointsforwin", $league['pointsforwin'], array('id' => "pointsforwin")), "pointsforwin");
	$form_container->output_row($lang->myleagues_pointsfordraw." <em>*</em>", "", $form->generate_text_box("pointsfordraw", $league['pointsfordraw'], array('id' => "pointsfordraw")), "pointsfordraw");
	$form_container->output_row($lang->myleagues_pointsforloss." <em>*</em>", "", $form->generate_text_box("pointsforloss", $league['pointsforloss'], array('id' => "pointsforloss")), "pointsforloss");
	$form_container->output_row($lang->myleagues_criterium_of_sorting." <em>*</em>", "", $form->generate_select_box("sort", array('goals' => $lang->myleagues_goals, 'direct' => $lang->myleagues_h2h), $league['sort']));
	$form_container->output_row($lang->myleagues_color_table, $lang->myleagues_color_table_description, $form->generate_text_area("colors", $league['colors'], array('id' => "colors")), "colors");
	$form_container->output_row($lang->myleagues_another_word_for_goals, "", $form->generate_text_box("wordforgoals", $league['wordforgoals'], array('id' => "wordforgoals")), "wordforgoals");
	
	if($number_of_teams > 0) {
		$form_container->output_row($lang->myleagues_extra_points, $lang->myleagues_extra_points_description, $extra_points, "wordforgoals");
	}
	
	$form_container->output_row($lang->myleagues_displayed_columns, $lang->myleagues_multiselect_description, $form->generate_select_box("column[]", $columns_options, $columns_selected, array('multiple' => "multiple", 'size' => 7)));
	
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->myleagues_save);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
}


// Leagues view.

else {
	
	// Pagination.
	
	if($myleagues->cache['leagues'] > $per_page) {
		$pagination = draw_admin_pagination($current_page, $per_page, $myleagues->cache['leagues'], "index.php?module=config-myleagues&amp;action=leagues&amp;page={page}");
	}
	
	// Leagues table.
	
	echo $pagination;
	
	$table = new Table;
	$table->construct_header("ID", array('class' => "align_center"));
	$table->construct_header($lang->myleagues_name, array('class' => "align_left"));
	$table->construct_header($lang->myleagues_teams, array('class' => "align_center"));
	$table->construct_header($lang->myleagues_season, array('class' => "align_center"));
	$table->construct_header($lang->controls, array('class' => "align_center", 'colspan' => 3));

	if($myleagues->cache['leagues'] == 0) {
		$table->construct_cell($lang->myleagues_no_leagues, array('class' => "align_left", 'colspan' => 7));
		$table->construct_row();
	}
	
	else {
	
		$query = $db->query("SELECT `lid`, `name`, `public`, `season`, `teams` FROM `".TABLE_PREFIX."myleagues_leagues` ORDER BY `modified` DESC, `lid` DESC LIMIT {$first_item}, {$per_page}");
		
		while($league = $db->fetch_array($query)) {
			
			if(!empty($league['teams'])) {
				$teams_array = explode(";", trim($league['teams']));
				$number_of_teams = count(array_filter($teams_array));
			}
			else {
				$number_of_teams = 0;
			}
		
			// Publish or hide.
			
			if($league['public'] == 0) {
				$public_action = "<a href=\"index.php?module=config-myleagues&amp;action=public_league&amp;lid={$league['lid']}\">{$lang->myleagues_public}</a>";
			}
			else {
				$public_action = "<a href=\"index.php?module=config-myleagues&amp;action=hide_league&amp;lid={$league['lid']}\">{$lang->myleagues_hide}</a>";
			}
			
			
			$table->construct_cell($league['lid'], array('class' => "align_center"));
			$table->construct_cell("<strong>{$league['name']}</strong>", array('class' => "align_left"));
			$table->construct_cell($number_of_teams, array('class' => "align_center"));
			$table->construct_cell($league['season'], array('class' => "align_center"));
			$table->construct_cell($public_action, array('class' => "align_center"));
			$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=edit_league&amp;lid={$league['lid']}\">{$lang->edit}</a>", array('class' => "align_center"));
			$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=delete_league&amp;lid={$league['lid']}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->myleagues_confirm_league_deletion}')\">{$lang->delete}</a>", array('class' => "align_center"));
			$table->construct_row();
			
		} 
	
	}

	
	$table->output($lang->myleagues_leagues);
	
	echo $pagination;
	
	// The form of adding a new league.
	
	$form = new Form("index.php?module=config-myleagues&amp;action=add_league", "post", "add");
	$form_container = new FormContainer($lang->myleagues_add_league);
	$form_container->output_row($lang->myleagues_name." <em>*</em>", "", $form->generate_text_box("name", "", array('id' => "name")), "name");
	$form_container->output_row($lang->myleagues_season." <em>*</em>", "", $form->generate_text_box("season", "", array('id' => "season")), "season");
	
	if($myleagues->cache['teams'] > 0) {
		$form_container->output_row($lang->myleagues_teams, $lang->myleagues_multiselect_description, $form->generate_select_box("team[]", $myleagues->get_teams(), array(), array('multiple' => "multiple", 'size' => $multiselect_size)));
	}

	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->myleagues_add_league);
	$form->output_submit_wrapper($buttons);
	$form->end();
	
}


// Displays the footer.

$page->output_footer();

ob_end_flush();


?>