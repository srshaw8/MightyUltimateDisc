<?php
/**
 * @author Steven Shaw
 * @copyright 2008
 */
function get_events_for_player($playerID) {
	/** initialize return array */
	$arrEvents = array();
	if (check_admin_authorization()) {
		/** admin can look at all active events */
		$rsEvents = get_events_for_admin();
		if ($rsEvents) {
			$numResults = mysql_num_rows($rsEvents);
			if ($numResults > 0) {
				while ($row=mysql_fetch_array($rsEvents)) {
					$arrThisEvent['Event_ID'] = $row['Event_ID'];
					$arrThisEvent['Event_Name'] = $row['Event_Name'];
					$arrEvents[] = $arrThisEvent;
				}
			}
		}
	} else {
		/** 
		 * these results reflect all events that player may potentially be in - doing 2 queries for now
		 * to get events where player is on the roster and events where they are an owner (they may be an 
		 * owner but not on the roster) - after retrieving the results, merge them to end up with distinct 
		 * values.		
		 */
		$arrEventsRoster = array();
		$arrEventsRole = array();
		$arrEvents = array();
		$rsEventsRoster = get_events_for_player_roster($playerID);
		if ($rsEventsRoster) {
			$numResults = mysql_num_rows($rsEventsRoster);
			if ($numResults > 0) {
				while ($row=mysql_fetch_array($rsEventsRoster)) {
					$arrThisEvent['Event_ID'] = $row['Event_ID'];
					$arrThisEvent['Event_Name'] = $row['Event_Name'];
					$arrEventsRoster[] = $arrThisEvent;
				}
			}
		}
		$rsEventsRole = get_events_for_player_role($playerID,"Owner");
		if ($rsEventsRole) {
			$numResults = mysql_num_rows($rsEventsRole);
			if ($numResults > 0) {
				while ($row=mysql_fetch_array($rsEventsRole)) {
					$arrThisEvent['Event_ID'] = $row['Event_ID'];
					$arrThisEvent['Event_Name'] = $row['Event_Name'];
					$arrEventsRole[] = $arrThisEvent;
				}
			}
		}
		/** compare and do merge if role event is not in roster event array */
		if (!empty($arrEventsRole)) {
			foreach ($arrEventsRole as $arrValueRole) {
				$eventIDRole = $arrValueRole["Event_ID"];
				$eventIDexists = false;
				foreach ($arrEventsRoster as $arrValueRoster) {
					if ($eventIDRole == $arrValueRoster["Event_ID"]) {
						$eventIDexists = true;
						break;
					}
				}
				if (!$eventIDexists) {
					$arrThisEvent['Event_ID'] = $arrValueRole['Event_ID'];
					$arrThisEvent['Event_Name'] = $arrValueRole['Event_Name'];
					$arrEventsRogue[] = $arrThisEvent;
				}
			}
			if (!empty($arrEventsRogue)) {
				$arrEvents = array_merge($arrEventsRoster,$arrEventsRogue);
			} else {
				$arrEvents = $arrEventsRoster;
			}
		} else {
			$arrEvents = $arrEventsRoster;
		}
	}
	return $arrEvents;
}

function build_event_navbar($display) {
	$curPage = get_cur_page_URL();
	$isCaptain = check_captain_authorization();
	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization();
	$selEvntName = get_session_event_name();
?>
	<span class="smLB">Currently selected event: <?php echo $selEvntName == "" ? "None" : $selEvntName ?></span>
	<br/><br/>
	<div id="navbar_event_menu_wrapper">
		<ul id="navbar_event_list">
			<?php
			if (strstr($curPage, "event_select")) {
			?>
				<li>
					<a href="event_select.php" onMouseOver="window.status=''; return true" 
					<?php
					if (strstr($curPage, "event_select")) {
						echo "class=\"current\"";
					}
					?>
					><span>Select Event</span></a>
				</li>
			<?php
			} else {
			?>
				<li>
					<a href="event_mgmt.php" onMouseOver="window.status=''; return true" 
					<?php
					if (strstr($curPage, "event_mgmt")) {
						echo "class=\"current\"";
					}
					?>
					><span>Event Profile</span></a>
				</li>
				<?php
				if ($display == "all") {
					if ($isOwner or $isAdmin) {
					?>
						<li>			
							<a href="event_home_page.php" onMouseOver="window.status=''; return true"
							<?php
							if (strstr($curPage, "event_home_page")) {
								echo "class=\"current\"";
							}
							?>
							><span>Home Page</span></a>
						</li>
					<?php
					}
					if ($isCaptain or $isOwner or $isAdmin) {
					?>
						<li>
							<a href="email.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "email")) {
								echo "class=\"current\"";
							}
							?>
							><span>Email</span></a>
						</li>
					<?php
					}
					?>
					<li>
						<a href="team_mgmt.php" onMouseOver="window.status=''; return true" 
						<?php
						if (strstr($curPage, "team_mgmt")) {
							echo "class=\"current\"";
						}
						?>
						><span>Teams</span></a>
					</li>
					<?php
					if ($isOwner or $isAdmin) {
					?>
						<li>
							<a href="wait_list.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "wait_list")) {
								echo "class=\"current\"";
							}
							?>
							><span>Wait List</span></a>
						</li>
					<?php
					}
					if ($isCaptain or $isOwner or $isAdmin) {
					?>
						<li>
							<a href="reports.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "reports")) {
								echo "class=\"current\"";
							}
							?>
							><span>Reports</span></a>
						</li>
					<?php
					}
				}
			}
			?>
		</ul>
	</div>
<?php
}

function process_event_selection($eventID,$playerID) {
	/** set selected event's ID to session for event mgmt pages */
	set_session_event_mgmt($eventID);
	
	/** determine player's role in event */
	if (check_admin_authorization()) {
		$rsEventProfile = get_event_profile_for_admin($eventID);
	} else { 
		/** this will check if player is owner or captain */
		$rsEventProfile = get_event_profile_mgmt($eventID, $playerID);
	}
	
	/** player is either an admin, owner, or captain */
	if ($rsEventProfile) {  
		$numResults = mysql_num_rows($rsEventProfile);
		if ($numResults > 0) {
			while ($row=mysql_fetch_array($rsEventProfile)) {
				$eventName = $row['Event_Name'];
				if (check_admin_authorization()) {
					$eventRole = "Admin";
				} else {
					$eventRole = $row['Role'];
				}
			}
		}
		if ($eventName <> "" and $eventRole <> "") {
			set_session_event_name($eventName);
			set_session_player_role($eventRole);
			return true;
		}
	} else { /** player is just a player */
		$rsEvent = get_roster_player_info($eventID, $playerID);
		$eventName = $rsEvent['Event_Name'];
		if ($eventName <> "") {
			set_session_event_name($eventName);
			set_session_player_role("Player");
			return true;
		}
	}
	return false;
}
?>