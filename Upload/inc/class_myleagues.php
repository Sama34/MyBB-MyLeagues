<?php

/**
 * MyLeagues 1.0 for MyBB
 * Main class
 * @author Filip Klar <kontakt@fklar.pl>
 */
 

// MyLeagues class.

class myleagues {
	
	/**
	 * @var array Cache with numbers of rows in the tables.
	 */
	 
	public $cache = array();
	
	/**
	 * @var int The ID of the selected league.
	 */
	
	public $current_league;
	
	/**
	 * @var array The array of the names from database.
	 */
	 	
	public $names = array();
	
	/**
	 * @var string The class of the last table row.
	 */
	
	public $trow;
	
	
	public function __construct() {
		
		global $db;
		
		// Gets cache.
		
		$this->cache['leagues'] = $db->num_rows($db->simple_select("myleagues_leagues", "`lid`"));
		$this->cache['teams']   = $db->num_rows($db->simple_select("myleagues_teams", "`tid`"));
		
		
		// Checks which league was last modified.
		
		$league = $db->fetch_array($db->simple_select("myleagues_leagues", "`lid`", "", array('order_by' => "modified", 'order_dir' => "DESC")));
		if($league['lid']) {
			$this->current_league = $league['lid'];
		}
		
	}
	
	
	/**
	 * Gets the name of the entry.
	 *
	 * @param int The ID of row.
	 * @param string The table to be searched.	
	 * @return string The name of the row. 
	 */
	
	public function get_name($id, $table) {
	
		global $db;
		
		if(empty($this->names[$table][$id])) {
			$column = substr($table, 0, 1)."id";
			$row = $db->fetch_array($db->simple_select("myleagues_{$table}", "`name`", "`{$column}` = {$id}"));
			$this->names[$table][$id] = $row['name'];
		}
		
		return $this->names[$table][$id];
		
	}
	
	
	/**
	 * Updates the date of last modification.
	 *
	 * @param int The ID of row.
	 * @param string The table to be updated. 
	 */

	public function update_modified($id, $table) {
	
		global $db;
		
		$edit = array(
			'modified' => TIME_NOW
		);
		
		$db->update_query("myleagues_{$table}", $edit, "`".substr($table, 0, 1)."id` = {$id}");
		$this->current_league = $id;
		
	}
	
	
	/**
	 * Gets the list of all teams.
	 *
	 * @param int The ID of league to loaded.
	 * @return array The list of all teams. 
	 */

	public function get_teams($league = "") {
	
		global $db;
		
		if(empty($league)) {
			$condition = "";
		}
		else {
			$condition = "`tid` = 0";
			$league = $db->fetch_array($db->simple_select("myleagues_leagues", "`teams`", "`lid` = {$league}"));
			
			
			if($league['teams']) {
				
				$temp = array_filter(explode(";", $league['teams']));
			
				foreach($temp as $id) {
					$condition .= " OR `tid` = {$id}";	
				}
			
			}
			
		}
		
		$query = $db->simple_select("myleagues_teams", "`tid`, `name`", $condition, array('order_by' => "name", 'order_dir' => "ASC"));
		
		while($team = $db->fetch_array($query)) {
			$array[$team['tid']] = $team['name'];	
		}
		
		return $array;
		
	}
	
	
	/**
	 * Gets the list of all leagues.
	 *
	 * @return array The list of all leagues. 
	 */

	public function get_leagues() {
	
		global $db;
		
		$query = $db->simple_select("myleagues_leagues", "`lid`, `name`, `season`", "", array('order_by' => "name", 'order_dir' => "ASC"));
		
		while($league = $db->fetch_array($query)) {
			$array[$league['lid']] = $league['name']." ".$league['season'];	
		}
		
		return $array;
		
	}
	
	
	/**
	 * Checks if the entry exists in database.
	 *
	 * @param int The ID of row.
	 * @param string The table to be checked.	 
	 */

	public function check_id($id, $table) {
		
		global $db, $lang;
	
		$column = substr($table, 0, 1)."id";
		$row = $db->fetch_array($db->simple_select("myleagues_".$table, "`{$column}`", "`{$column}` = '{$id}'"));
		
		if(empty($row[$column])) {
			flash_message($lang->myleagues_invalid_id, "error");
			admin_redirect("index.php?module=config-myleagues&amp;action={$table}");	
		} 
		
	}
	
	
	/**
	 * Creates ordinal number.
	 *
	 * @param int Number.
	 * @return string Ordinal number.
	 */
	 
	public function get_ordinal($number) {
	
		global $mybb;
		
		if($mybb->settings['cplanguage'] == "english") {
			
			$last_digit = substr($number, -1);
			
			if($last_digit == 1) {
				$sufix = "st";
			}
			elseif($last_digit == 2) {
				$sufix = "nd";
			}
			elseif($last_digit == 3) {
				$sufix = "rd";
			}
			else {
				$sufix = "th";
			}
			
			return $number.$sufix;
				
		}
		
		elseif($mybb->settings['cplanguage'] == "polish") {
			return $number.".";		
		}
		
		else {
			return $number;
		}
		
	}
	
	/**
	 * Cutes the name of the team.
	 *
	 * @param string The full name of the team.
	 * @return string The short name of the team.
	 */
	 
	public function cut_name($name) {
		
		$short = mb_strtoupper(mb_substr(str_replace(" ", "", str_replace("\"", "", $name)), 0, 3));
		return $short;
		
	}
	
	
	/**
	 * Makes a matches grid.
	 *
	 * @param array The list of the clubs.
	 */
	 
	public function match_form($teams) {
		
		global $mybb, $lang;
		
		$table = new Table;
		$table->construct_cell("<strong>".$lang->myleagues_teams."</strong>");
		
		foreach($teams as $awayname) {
			$table->construct_cell($this->cut_name($awayname), array('class' => "align_center"));
		}
		
		$table->construct_row();
		
		foreach($teams as $homeid => $homename) {

			$table->construct_cell($homename);
			
			foreach($teams as $awayid => $awayname) {
			
				if($homeid !== $awayid) {
					$table->construct_cell("<a href=\"index.php?module=config-myleagues&amp;action=add_match&amp;matchday={$mybb->input['mid']}&amp;match_home={$homeid}&amp;match_away={$awayid}\" title=\"{$homename} - {$awayname}\" class=\"valid_match\"> </a>", array('class' => "align_center"));
				}			
				else {
					$table->construct_cell("<div class=\"invalid_match\"> </div>", array('class' => "align_center"));
				}
			}
			
			$table->construct_row();
			
		}
		
		$table->output($lang->myleagues_add_match);
		
	}
	
	
	/**
	 * Updates the league table.
	 *
	 * @param int The ID of the league to updated.
	 */
	 
	public function update_rank($lid) {
		
		global $db;
	
		$db->delete_query("myleagues_rows", "`league` = {$lid}");
		
		$league = $db->fetch_array($db->simple_select("myleagues_leagues", "`teams`, `pointsforwin`, `pointsfordraw`, `pointsforloss`, `sort`, `extrapoints`", "`lid` = {$lid}"));
		
		$all_extra = array_filter(explode(";", $league['extrapoints']));
		foreach($all_extra as $current) {
			$temp = explode(":", $current);
			$extra[$temp[0]] = $temp[1];
		}
		
		$list_of_teams = explode(";", $league['teams']);
		
		foreach($list_of_teams as $tid) {
			$team[$tid] = array(
				'team'             => $tid,
				'points'           => intval($extra[$tid]),
				'goalsfor'         => 0,
				'goalsagainst'     => 0,
				'goalsdifference'  => 0,
				'wins'             => 0,
				'draws'            => 0,
				'losses'           => 0,
				'points2'          => 0,
				'goalsfor2'        => 0,
				'goalsagainst2'    => 0,
				'goalsdifference2' => 0
			);
		}
		
		$query = $db->simple_select("myleagues_matches", "`hometeam`, `awayteam`, `homeresult`, `awayresult`", "`league` = {$lid} AND `homeresult` IS NOT NULL AND `awayresult` IS NOT NULL");
		
		while($match = $db->fetch_array($query)) {
		
			$team[$match['hometeam']]['goalsfor']     += $match['homeresult'];
			$team[$match['hometeam']]['goalsagainst'] += $match['awayresult'];
			$team[$match['awayteam']]['goalsfor']     += $match['awayresult'];
			$team[$match['awayteam']]['goalsagainst'] += $match['homeresult'];
			
			if($match['homeresult'] > $match['awayresult']) {
				$team[$match['hometeam']]['points'] += $league['pointsforwin'];
				$team[$match['awayteam']]['points'] += $league['pointsforloss'];
				$team[$match['hometeam']]['wins']   += 1;
				$team[$match['awayteam']]['losses'] += 1;
			}
			elseif($match['homeresult'] < $match['awayresult']) {
				$team[$match['hometeam']]['points'] += $league['pointsforloss'];
				$team[$match['awayteam']]['points'] += $league['pointsforwin'];
				$team[$match['hometeam']]['losses'] += 1;
				$team[$match['awayteam']]['wins']   += 1;
			}
			else {
				$team[$match['hometeam']]['points'] += $league['pointsfordraw'];
				$team[$match['awayteam']]['points'] += $league['pointsfordraw'];
				$team[$match['hometeam']]['draws']  += 1;
				$team[$match['awayteam']]['draws']  += 1;
			}
			
		}
		
		
		// If it uses "head to head"...
		
		if($league['sort'] == "direct") {
		
			// Searches the teams with the same number of points.
			
			foreach($team as $tid => $array) {
				$points[$array['points']][] = $tid;
			}
			
			
			// Deletes the arrays which has one team only or processes the matches.
			
			foreach($points as $key => $array) {
				
				if(count($array) <= 1) {
					unset($points[$key]);
				}
				
				else {
					
					// Generates the condition for the query.
					
					$condition = "`league` = {$lid} AND `homeresult` IS NOT NULL AND `awayresult` IS NOT NULL AND (`hometeam` = 0";
	
					foreach($array as $key => $team1) {
						foreach($array as $key => $team2) {
							$condition .= " OR (`hometeam` = {$team1} AND `awayteam` = {$team2})";
						}
					}
					
					$condition .= ")";
					
					// Hurray! It really works :P
					
					$query = $db->simple_select("myleagues_matches", "*", $condition);
					
					while($match = $db->fetch_array($query)) {
						
						if($match['homeresult'] > $match['awayresult']) {
							$team[$match['hometeam']]['points2'] += $league['pointsforwin'];
							$team[$match['awayteam']]['points2'] += $league['pointsforloss'];
						}
						elseif($match['homeresult'] < $match['awayresult']) {
							$team[$match['hometeam']]['points2'] += $league['pointsforloss'];
							$team[$match['awayteam']]['points2'] += $league['pointsforwin'];
						}
						else {
							$team[$match['hometeam']]['points2'] += $league['pointsfordraw'];
							$team[$match['awayteam']]['points2'] += $league['pointsfordraw'];
						}
						
						$team[$match['hometeam']]['goalsfor2']     += $match['homeresult'];
						$team[$match['hometeam']]['goalsagainst2'] += $match['awayresult'];
						$team[$match['awayteam']]['goalsfor2']     += $match['awayresult'];
						$team[$match['awayteam']]['goalsagainst2'] += $match['homeresult'];
						
					}
					
				}
				
			}
		
		}
		
		
		foreach($team as $tid => $details) {
			
			$new_row = array(
				'league'           => $lid,
				'team'             => $tid,
				'points'           => $team[$tid]['points'],
				'goalsfor'         => $team[$tid]['goalsfor'],
				'goalsagainst'     => $team[$tid]['goalsagainst'],
				'goalsdifference'  => ($team[$tid]['goalsfor']-$team[$tid]['goalsagainst']),
				'matches'          => ($team[$tid]['wins']+$team[$tid]['draws']+$team[$tid]['losses']),
				'wins'             => $team[$tid]['wins'],
				'draws'            => $team[$tid]['draws'],
				'losses'           => $team[$tid]['losses'],
				'points2'          => $team[$tid]['points2'],
				'goalsfor2'        => $team[$tid]['goalsfor2'],
				'goalsagainst2'    => $team[$tid]['goalsagainst2'],
				'goalsdifference2' => ($team[$tid]['goalsfor2']-$team[$tid]['goalsagainst2'])
			);
			
			if($tid > 0) {
				$db->insert_query("myleagues_rows", $new_row);
			}
			
		}
		
	}
	
	
	/**
	 * Returns the URL of the crest.
	 *
	 * @param int The ID of the team.
	 * @param string The place - "acp" or "forum".
	 * @return string The URL of the crest if it exists.
	 */ 	
	
	public function crest_url($tid, $place = "forum") {
		
		if($place == "acp") {
			$prefix = "../";
		}
		
		if(file_exists("{$prefix}uploads/crests/{$tid}.jpg")) {
			$crest = "{$prefix}uploads/crests/{$tid}.jpg";
		}
		elseif(file_exists("{$prefix}uploads/crests/{$tid}.png")) {
			$crest = "{$prefix}uploads/crests/{$tid}.png";
		}
		else {
			$crest = FALSE;
		}
		
		return $crest;
		
	}
	
	
	/**
	 * Returns the new table row class.
	 *
	 * @return string The class of the new table row.
	 */
	
	public function trow() {
		
		if($this->trow == "trow2") {
			$this->trow = "trow1";
		}
		else {
			$this->trow = "trow2";
		}
		
		return $this->trow;		
			
	}
	
	
}



?>