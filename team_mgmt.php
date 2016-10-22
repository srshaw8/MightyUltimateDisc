<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
/** general includes */
include_once('locator.php');
if (IS_LOCAL) {
    include_once('includes/includes.php');
} else if (IS_TEST) {
    include_once('../../../data/includes_test/includes.php');
} else {
    include_once('../../../data/includes_prod/includes.php');
}

if (check_authorization()) {
	/** variable declarations */
	$action = "";
	$processAction = "";
	$enteredData = array();
	$errors = array();
	$assignees = array();
	$playerID = get_session_player_id();
	$eventID = get_session_event_mgmt();
	$teamID = 0;
	$eventName = get_session_event_name(); 
	$isCaptain = check_captain_authorization();
	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization();
	
	$tempAction = (isset($_POST['Action'])) ? $_POST['Action'] : "";
	$tempID = (isset($_POST['ID'])) ? $_POST['ID'] : "";
	if (isset($_POST['ProcessAction']) and is_array($_POST['ProcessAction'])) {
    	$thisProcessAction = each($_POST['ProcessAction']);
		$teamID = $thisProcessAction['key'];
        $processAction = cleanAction($thisProcessAction['value']);
	} else if (check_value_is_set($tempAction) and check_value_is_set($tempID)) {  /** for delete */
		$processAction = $_POST['Action'];
		$teamID = $_POST['ID'];
	} else {
		$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
		$processAction = cleanAction($thisProcessAction);
	}
	
	if (check_value_is_set($eventID) and is_numeric($eventID)) {
		$rsTeams = get_team_profiles_active($eventID);
		if (is_numeric($teamID)) {
			switch ($processAction) {
			    case "View": /** action from summary page */
					view_team_page($errors, $eventID, $teamID);
			        break;
			    case "Edit Team": /** action from summary/detail page */
					if ($isOwner or $isAdmin) {
						$action = "teamEdit";
						$enteredData = get_team_profile($eventID, $teamID);
						$enteredData['ProcessAction'] = $enteredData['Team_ID'];
						$assignees = get_all_assigned_and_not($eventID, $teamID);
						build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Team Mgmt edit page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
			        break;
			    case "Edit Captains": /** action from summary/detail page */
					if ($isOwner or $isAdmin) {
						$action = "captainEdit";
						$enteredData = get_team_profile($eventID, $teamID);
						$enteredData['ProcessAction'] = $enteredData['Team_ID'];
						$assignees = get_all_assigned_and_not($eventID, $teamID);
						build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Team Captain edit page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
			        break;
			    case "Delete": /** action from summary page */
					if ($isOwner or $isAdmin) {
						$action = "confirmDelete";
						/** archive entry from team profile table */ 			
						if (!update_archive_team_profile($eventID, $teamID)) {
							log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
								"Failed to archive team profile ".$teamID.".");
							$errors = error_add($errors, "An error occurred while deleting your team.");
						}
						/** archive entries from event profile table for event/team */
						if (!update_archive_event_team_role($eventID,$teamID)) {
							log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
								"Failed to archive team captain roles for team ".$teamID.".");
							$errors = error_add($errors, "An error occurred while deleting team captain roles.");
						}
						/** reset team ID entries to 0 in roster table for event */
						if (!update_roster_team_reset($eventID, $teamID)) {
							log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
								"Failed to reset team ID in roster table while archiving team ".$teamID.".");
							$errors = error_add($errors, "An error occurred while resetting team roster status.");
						}
						/** get a fresh list of teams */				
						$rsTeams = get_team_profiles_active($eventID);
						build_team_mgmt_summary_page($errors, $rsTeams);
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Team Captain delete page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
			        break;
			    case "Create Team": /** action from summary page to create team */
					if ($isOwner or $isAdmin) {
						$action = "teamCreate";
						$assignees['playersUnassigned'] = get_team_players_unassigned($eventID);
						/** if you don't set enteredData to false, php will throw a gabillion error notices saying 
			 			*  that xyz enteredData array element is not initialized due to the way its reference in 
			 			*  the edit version of the event management form below... 
			 			*/
						$enteredData = false;
						build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Team Create page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
					break;
			    case "Add Team": /** action from detail page while creating team */
					if ($isOwner or $isAdmin) {
						$action = "teamAdd";
						$enteredData = get_data_entered($_POST); 
						$errors = validate("teamEdit", $enteredData);
						if (empty($errors)){
							/** check if team already exists  - if not, then do insert for new team */				
							if (!check_dupe_team($eventID, $enteredData['Team_Name'])) {
								/** insert entry in team profile table */
								if (!insert_team_profile($eventID, $enteredData)) {
									log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
										"Failed to create new team.");
									$errors = error_add($errors, "An error occurred while creating new team.");
								} else { /** team profile creation a success */
									/** get ID of newly created team ID */
									$teamID = get_team_profile_id($eventID, $enteredData['Team_Name']);
									if (check_value_is_number($teamID)) {
										/** reset and update player/team entries in roster table for players */
										if (check_value_is_set($enteredData['Player_List'])) {
											if (!update_roster_team_reset($eventID,$teamID)) {/** resets teamID=0 */
												log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
													"Failed to reset team ID ".$teamID." in roster table while 
													creating new team.");
												$errors = error_add($errors, 
														"An error occurred while resetting team roster status.");
											}
									if (!update_roster_team_players($eventID,$teamID,$enteredData['Player_List'])){
												log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
													"Failed to set team ID ".$teamID." for players assigned to team 
													while creating new team.");
												$errors = error_add($errors, "An error occurred while updating
																 		players on the roster.");
											}
										}
									} else {
										log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
											"Team ID of newly created team does not exist or is not a number.");
										$errors = error_add($errors, "A problem was detected with your new team's 
																ID.	Technical support has been notified.");
									}
								}
							} else {
								$errors = error_add($errors, "The team name entered already exists for this event.
											Please enter a different team name.");
							}
							if (!empty($errors)) {
								$action = "teamCreate";
								$assignees['playersUnassigned'] = get_team_players_unassigned($eventID);
								build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
							} else {
								$action = "captainAdd"; /** set up to add captains after adding players to team */
								$assignees = get_all_assigned_and_not($eventID, $teamID);
								$enteredData['Team_ID'] = $teamID; /** add team ID to entereddata for detail form */
								build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
							}
						} else {
							$assignees = get_all_assigned_and_not($eventID, $teamID);
							build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
						}
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Add Team page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
			        break;
			    case "Add Captains": /** action from detail page while creating team captains */
					if ($isOwner or $isAdmin) {
						$action = "captainAdd";
						$enteredData = get_data_entered($_POST);
						process_captains($errors, $eventID, $teamID, $enteredData);
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Add Captain page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
			        break;
			    case "Save Team": /** action from detail page */
					if ($isOwner or $isAdmin) {
						$action = "teamSave";
						$enteredData = get_data_entered($_POST);
						$errors = validate("teamEdit", $enteredData);
						if (empty($errors)){
							/** update entry in team profile table */
							if (!update_team_profile($eventID, $teamID, $enteredData)) {
								log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
									"Failed to save team profile ".$teamID.".");
								$errors = error_add($errors, 
												"An error occurred while updating the team profile ".$teamID.".");
							}
							/** reset and update player/team entries in roster table for players */
							if (!update_roster_team_reset($eventID, $teamID)) {
								log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
									"Failed to reset team ID in roster table while updating 
									team profile ".$teamID.".");
								$errors = error_add($errors,"An error occurred during team roster status reset.");
							}
							if (check_value_is_set($enteredData['Player_List'])) {
								if (!update_roster_team_players($eventID, $teamID, $enteredData['Player_List'])) {
									log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
										"Failed to set team ID for players assigned to team while updating 
										team profile ".$teamID.".");
									$errors = error_add($errors, "An error occurred while updating players 
																on the roster.");
								}
							}
							/** if a player has been removed from team who is captains, 
								then we gotta delete da bugga 
							*/
							$assignees['captainsAssigned'] = get_player_role_assigned($eventID, $teamID, "Captain");
							if (!empty($assignees['captainsAssigned'])) {
								if(mysql_num_rows($assignees['captainsAssigned']) > 0) {
									$captainList = array();
									while ($row=mysql_fetch_array($assignees['captainsAssigned'])) {
										$captainID = $row['Player_ID'];
										array_push($captainList, $captainID);
									}
									foreach ($captainList as $thisCaptainVal) {
									if (!strstr($enteredData['Player_List'], $thisCaptainVal)) {
								if (!delete_event_team_player_role($eventID,$teamID,$thisCaptainVal,"Captain")) {
										log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
										"Failed to delete team captain ".$thisCaptainVal." on team ".$teamID.".");
										$errors = error_add($errors, 
											"An error occurred while refreshing a team captain role.");
											}
										}
									}
								}
							}
		
							if(!empty($errors)) {
								$assignees = get_all_assigned_and_not($eventID, $teamID);
								build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
							} else {
								/** get a fresh list of teams */
								$rsTeams = get_team_profiles_active($eventID);
								build_team_mgmt_summary_page($errors, $rsTeams);
							}
						} else {
							$assignees = get_all_assigned_and_not($eventID, $teamID);
							build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
						}
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Save Team page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}
			        break;
			    case "Save Captains": /** action from detail page */
					if ($isOwner or $isAdmin) {
						$action = "captainSave";
						$enteredData = get_data_entered($_POST);
						process_captains($errors, $eventID, $teamID, $enteredData);
					} else {
						log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Save Captain page.");
						$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
						view_team_page($errors, $eventID, $teamID);
					}	
			        break;
			    default:
			    	build_team_mgmt_summary_page($errors, $rsTeams);
			}
		} else {
			log_entry(Logger::TEAM,Logger::WARN,$eventID,$playerID,
				"Team ID was not numeric.");
			$errors = error_add($errors, "An error occurred with the team that you selected.");
			build_team_mgmt_summary_page($errors, $rsTeams);
		}
	} else {
			clear_selected_event();
			redirect_page("index.php");
	}
} else {
	display_non_authorization();
}

function view_team_page($errors, $eventID, $teamID){
	$action = "teamView";			
	$enteredData = get_team_profile($eventID, $teamID);
	$enteredData['ProcessAction'] = $enteredData['Team_ID'];
	$assignees = get_all_assigned($eventID, $teamID);
	build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
}

function get_all_assigned_and_not($eventID, $teamID) {
	$assignees = array();
	$assignees['playersAssigned'] = get_team_players_assigned($eventID, $teamID);
	$assignees['playersUnassigned'] = get_team_players_unassigned($eventID);
	$assignees['captainsAssigned'] = get_player_role_assigned($eventID, $teamID, "Captain");
	return $assignees;	
}

function get_all_assigned($eventID, $teamID) {
	$assignees = array();
	$assignees['playersAssigned'] = get_team_players_assigned($eventID, $teamID);
	$assignees['captainsAssigned'] = get_player_role_assigned($eventID, $teamID, "Captain");
	return $assignees;	
}

function process_captains($errors, $eventID, $teamID, $enteredData) {
	/** delete and insert entries in event role table for captains */
	if (!delete_event_team_role($eventID, $teamID, "Captain")) {
		log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
			"Failed to delete event captain roles while updating team ".$teamID.".");
		$errors = error_add($errors, "An error occurred while refreshing team captain roles.");
	}
	$captainList = (isset($enteredData['Captain_List'])) ? $enteredData['Captain_List'] : "";
	if ($captainList <> "") {
		foreach ($enteredData['Captain_List'] as $thisCaptainVal) {
			if (!insert_event_team_role($thisCaptainVal, $eventID, $teamID, "Captain")) {
				log_entry(Logger::TEAM,Logger::ERROR,$eventID,$playerID,
					"Failed to add new captain role while updating team profile ".$teamID.".");
				$errors = error_add($errors, "An error occurred while saving the team captains.");
			}
		}
	}
	if(!empty($errors)) {
		$assignees = get_all_assigned_and_not($eventID, $teamID);
		build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action);
	} else {
		/** get a fresh list of teams */
		$rsTeams = get_team_profiles_active($eventID);
		build_team_mgmt_summary_page($errors, $rsTeams);
	}
}


function build_team_mgmt_summary_page($errors, $rsTeams) {
	display_wrappers();
	?>
	<div id="content_wrapper">
		<?php
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		display_errors($errors);

		$isCaptain = check_captain_authorization();
		$isOwner = check_owner_authorization();
		$isAdmin = check_admin_authorization();
		?>
		<form method="post" name="selectionForm" action="team_mgmt.php" class="boxReport">
			<input type="hidden" name="Action" value="">
			<input type="hidden" name="ID" value="">
			<table class="report">
				<tr>
					<th colspan="4" scope="col" class="dispSL">Action</th>
					<th scope="col" class="dispSL">Team Name</th>
				</tr>
				<?php
				if ($rsTeams) {
					$numResults = mysql_num_rows($rsTeams);
					if ($numResults > 0) {
						while ($row=mysql_fetch_array($rsTeams)) {
							$thisAction = "ProcessAction[".$row['Team_ID']."]";
							$linkClass=" class=\"linkSm\"";
							?>
							<tr>
								<td>
									<input type="submit" <?php echo $linkClass ?> 
										name="<?php echo $thisAction ?>" value="View">
								</td>
								<td>
								<?php
								if ($isOwner or $isAdmin) {
								?>
									<input type="submit" <?php echo $linkClass ?> 
										name="<?php echo $thisAction ?>" value="Edit Captains">
								<?php
								}
								?>
								</td>
								<td>
								<?php
								if ($isOwner or $isAdmin) {
								?>
									<input type="submit" <?php echo $linkClass ?> 
										name="<?php echo $thisAction ?>" value="Edit Team">
								<?php
								}
								?>							
								</td>
								<td>
								<?php
								if ($isOwner or $isAdmin) {
								?>
									<input type="submit" <?php echo $linkClass ?> 
										name="<?php echo $thisAction ?>" value="Delete"
										onclick="dispDeleteBox(<?php echo $row['Team_ID']?>); return false;">
								<?php
								}
								?>
								</td>
								<td class="entrySL">
									<?php echo stripslashes($row['Team_Name']); ?>
								</td>
							</tr>
						<?php
						}
					} else {
					?>
						<tr>
							<td colspan="5" class="entrySL">No teams have been created!</td>
						</tr>
					<?php
					}
				} else {
				?>
					<tr>
						<td colspan="5" class="entrySL">No teams have been created!</td>
					</tr>
				<?php
				}
				if ($isOwner or $isAdmin) {
				?>
					<tr>
						<td colspan="5" class="dispRC">
							<button type="submit" value="Create Team" class="submitBtn" name="ProcessAction">
								<span>Create Team</span>
							</button>
						</td>
					</tr>
				<?php
				}
				?>
			</table>
		</form>
		</div>
	</div>
<?php
}

function build_team_mgmt_detail_page($errors, $enteredData, $assignees, $action) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		if ($action == "teamCreate" or 
			$action == "teamEdit" or 
			$action == "captainEdit" or
			$action == "teamAdd" or 
			$action == "captainAdd") {
			editForm($errors, $enteredData, $assignees, $action);
		} else {
			displayForm($errors, $enteredData, $assignees, $action);
		}
		?>
		</div>
	</div>
<?php
}

function editForm($errors, $enteredData, $assignees, $action) {
	display_errors($errors);
	
	$rsNumCaptainsAssigned = 0;
	$rsNumPlayersUnassigned = 0;
	$rsNumPlayersAssigned = 0;

	if (!empty($assignees['captainsAssigned'])) {
		$rsNumCaptainsAssigned = mysql_num_rows($assignees['captainsAssigned']);
	}
	if (!empty($assignees['playersUnassigned'])) {
		$rsNumPlayersUnassigned = mysql_num_rows($assignees['playersUnassigned']);
	}
	if (!empty($assignees['playersAssigned'])) {
		$rsNumPlayersAssigned = mysql_num_rows($assignees['playersAssigned']);
	}
	if ($action == "teamCreate" or $action == "teamEdit" or	$action == "teamAdd") {
	?>
    <script language="JavaScript" type="text/javascript">
        function procForm(){
            var fields = ['selectedPlayers'];
            submitForm(fields);	
        }
    </script>
	<?php
	}
	?>
    <div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
    	<?php
		if ($action == "teamCreate" or $action == "teamEdit" or	$action == "teamAdd") {
		?>
		<form method="post" name="selectionForm" action="team_mgmt.php"	onsubmit="return procForm();return false;">
        <?php
        } else {
        ?>
        <form method="post" name="selectionForm" action="team_mgmt.php">
        <?php
        }
        ?>
			<input type="hidden" name="Player_List" value="">
			<?php
			if ($action == "teamCreate" or $action == "teamEdit" or	$action == "teamAdd") {
			?>
				<input type="hidden" name="Captain_List[]" value="">
			<?php
			}
			?>
			<table class="default">
			<?php
			/** display form field variation for team entry vs. captain entry */
			if ($action == "teamCreate" or $action == "teamEdit" or	$action == "teamAdd") {
			?>	
				<tr>
					<td></td>
					<td colspan="2"><span class="smGD">* required entry</span></td>
				</tr>
				<tr>
					<td class="titleRR">Team Name*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text"
								name="Team_Name"
								value="<?php echo stripslashes($enteredData['Team_Name']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="1">
					</td>
				</tr>
				<?php 
				if (isset($errors['Team_Name'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Team_Name'] ?></td></tr>			
				<?php			
				}
				?>
				<tr>
					<td class="titleRR">Team Captain(s)</td>
					<?php
					if ($rsNumCaptainsAssigned == 0){
					?>			
						<td colspan="2"><p>No captains have been assigned to this team.</p></td>
					<?php
					} else {
					?>
						<td class="entryRL" colspan="2">
						<?php	
						if ($rsNumCaptainsAssigned > 0) {
							while ($row=mysql_fetch_array($assignees['captainsAssigned'])) {
								$playerID = $row['Player_ID'];
								$name = stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']);
								echo "$name<br/>";
							}
						}
						?>
						</td>
					<?php
					}
					?>
				</tr>
				<?php
				if ($rsNumPlayersUnassigned == 0 and $rsNumPlayersAssigned == 0) {
				?>
					<tr>
						<td class="titleRR">Team Players</td>
						<td class="entryRL" colspan="2">
						<p>No players are available to be assigned to team.</p>	
						</td>
					</tr>
				<?php
				} else {	
				?>
					<tr>
						<td class="titleRL" colspan="3">Assign Players to Team - including Captains:</td>
						<td></td>
					</tr>
					<tr>
						<td>
							<select name="players1" multiple size="15" 
								ondblclick="moveSelectedOptions(this.form['players1'],this.form['players2']);">
								<?php	
								if ($rsNumPlayersUnassigned >  0) {
									while ($row=mysql_fetch_array($assignees['playersUnassigned'])) {
										$playerID = $row['Player_ID'];
										$name = stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']);
										echo "<option value=$playerID>$name</option>";
									}
								}
								?>
							</select>
						</td>
						<td valign="top">
							<input type="button" name="right" value="&gt;&gt;" 
								onclick="moveSelectedOptions(this.form['players1'],this.form['players2']);">
							<br/><br/>
							<input type="button" name="left" value="&lt;&lt;" 
								onclick="moveSelectedOptions(this.form['players2'],this.form['players1']);">
							<br/><br/>
							<input type="button" name="left" value="All &lt;&lt;" 
								onclick="moveAllOptions(this.form['players2'],this.form['players1']);">
						</td>
						<td>
							<select id="selectedPlayers" name="players2" multiple size="15" 
								ondblclick="moveSelectedOptions(this.form['players2'],this.form['players1']);">
								<?php	
								if ($rsNumPlayersAssigned >  0) {
									while ($row=mysql_fetch_array($assignees['playersAssigned'])) {
										$playerID = $row['Player_ID'];
										$name = stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']);
										echo "<option value=$playerID>$name</option>";
									}
								}
								?>
							</select>
						</td>
					</tr>
					<?php 
					if (isset($errors['Player_List'])) {
					?>
						<tr><td></td><td class="error" colspan="2"><?php echo $errors['Player_List'] ?></td></tr>
					<?php			
					}
					?>
				<?php
				}
			} else {  /** if we're displaying captain entry stuff... */
			?>
				<tr>
					<td class="titleRR">Team Name</td>
					<td class="entryRL" colspan="2">
						<?php echo stripslashes($enteredData['Team_Name']) ?> 
					</td>
				</tr>
				<?php
				if ($rsNumPlayersAssigned == 0) {
				?>
					<tr>
						<td class="titleRR">Team Captain(s)</td>
						<td class="entryRL" colspan="2">
						<p>No players are available to be assigned as captains.</p>	
						</td>
					</tr>
				<?php
				} else {	
				?>
					<tr>
						<td class="titleRR">Select players who are captains</td>
						<td class="entryRL" colspan="2">
						<?php
						$captainList = array();
						if ($rsNumCaptainsAssigned > 0) {
							while ($row=mysql_fetch_array($assignees['captainsAssigned'])) {
								$captainID = $row['Player_ID'];
								$captainList[] = $captainID;  
							}
						}
						while ($row=mysql_fetch_array($assignees['playersAssigned'])) {
							$playerID = $row['Player_ID'];
							$name = stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']);
							$checkThis = false;
							foreach($captainList as $thisValue) {
								if ($thisValue == $playerID) {
									$checkThis = true;
								}
							}
							?>
							<input type="checkbox" 
								name="Captain_List[]"
								value="<?php echo $playerID ?>" 
								<?php if ($checkThis) { echo "checked"; } ?>>
								<?php echo $name ?><br/>
						<?php
						}
						?>
						</td>
					</tr>
					<?php 
					if (isset($errors['Captain_List'])) {
					?>
						<tr><td></td><td class="error" colspan="2"><?php echo $errors['Captain_List'] ?></td></tr>
					<?php			
					}
					?>
				<?php
				}
			}
	
			$thisAction = "ProcessAction[".$enteredData['Team_ID']."]";
			if ($action == "teamCreate" or $action == "teamAdd") {
				$buttonLabel1 = "Add Team";
				$buttonLabel2 = "Cancel";
			} else if ($action == "teamEdit") {
				$buttonLabel1 = "Save Team";
				$buttonLabel2 = "Cancel";
			} else if ($action == "captainAdd") {
				if ($rsNumPlayersAssigned != 0) {
					$buttonLabel1 = "Add Captains";
					$buttonLabel2 = "Team Summary";
				} else {
					$buttonLabel1 = "";
					$buttonLabel2 = "Team Summary";
				}
			} else if ($action == "captainEdit") {
				$buttonLabel1 = "Save Captains";
				$buttonLabel2 = "Cancel";
			}
			?>
				<tr>
					<td colspan="3" class="dispRC">
						<?php
						if ($buttonLabel1 != "") {
						?>
							<button type="submit" value="<?php echo $buttonLabel1 ?>" class="submitBtn" 
								name="<?php echo $thisAction ?>">
								<span><?php echo $buttonLabel1 ?></span>
							</button>
							&nbsp;
						<?php
						}
						?>
						<button type="submit" value="<?php echo $buttonLabel2 ?>" class="submitBtn" 
							name="<?php echo $thisAction ?>">
							<span><?php echo $buttonLabel2 ?></span>
						</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
	</div>
<?php
}  

function displayForm($errors, $enteredData, $assignees, $action) {
	display_errors($errors);
	
	$rsNumCaptainsAssigned = 0;
	$rsNumPlayersAssigned = 0;
	if (!empty($assignees['captainsAssigned'])) {
		$rsNumCaptainsAssigned = mysql_num_rows($assignees['captainsAssigned']);
	}
	if (!empty($assignees['playersAssigned'])) {
		$rsNumPlayersAssigned = mysql_num_rows($assignees['playersAssigned']);
	}
	
	$isCaptain = check_captain_authorization();
	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization();
?>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
		<form method="post" name="selectionForm" action="team_mgmt.php" class="box">
			<table class="default">
				<tr>
					<td class="titleRR">Team Name</td>
					<td class="entryRL" colspan="2">
					<?php echo stripslashes($enteredData['Team_Name']) ?> 
					</td>
				</tr>
				<?php			
	
				if ($rsNumCaptainsAssigned == 0){
				?>			
					<tr>
						<td colspan="3">
						<p>No captains have been assigned to this team.</p> 
						</td>
					</tr>
				<?php
				} else {
				?>
					<tr>
						<td class="titleRR">Team Captain(s)</td>
						<td class="entryRL">
							<?php	
							while ($row=mysql_fetch_array($assignees['captainsAssigned'])) {
								$name = stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']);
								echo "$name<br/>";
							}
							?>
						</td>
						<td class="entryRL">
							<?php
							if ($isCaptain or $isOwner or $isAdmin) {
								mysql_data_seek($assignees['captainsAssigned'],0);	
								while ($row=mysql_fetch_array($assignees['captainsAssigned'])) {
									$phone = stripslashes($row['H_Phone']);
									echo "(H) $phone<br/>";
								}
							}
							?>
						</td>
					</tr>
				<?php
				}
	
				if ($rsNumPlayersAssigned == 0){
				?>			
					<tr>
						<td colspan="3">
						<p>No players have been assigned to this team.</p> 
						</td>
					</tr>
				<?php
				} else {
				?>
					<tr>
						<td class="titleRR">Players on Team</td>
						<td class="entryRL">
							<?php	
							while ($row=mysql_fetch_array($assignees['playersAssigned'])) {
								$playerID = $row['Player_ID'];
								$name = stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']);
								echo "$name<br/>";
							}
							?>
						</td>
						<td class="entryRL">
							<?php
							if ($isCaptain or $isOwner or $isAdmin) {
								mysql_data_seek($assignees['playersAssigned'],0);	
								while ($row=mysql_fetch_array($assignees['playersAssigned'])) {
									$phone = stripslashes($row['H_Phone']);
									echo "(H) $phone<br/>";
								}
							}
							?>
						</td>
					</tr>
				<?php
				}
				?>
				<tr>
					<td colspan="3" class="dispRC">
					<?php
					if (is_array($enteredData['ProcessAction'])) {
						$thisProcessAction = each($enteredData['ProcessAction']);
						$teamID = $thisProcessAction['key'];
						$thisAction = "ProcessAction[".$teamID."]";
					} else {
						$thisAction = "ProcessAction[".$enteredData['ProcessAction']."]";
					}
				
					if ($isOwner or $isAdmin) {
					?>
						<button type="submit" value="Edit Team" class="submitBtn" name="<?php echo $thisAction ?>">
							<span>Edit Team</span>
						</button>
						&nbsp;
						<button type="submit" value="Edit Captains" class="submitBtn" 
							name="<?php echo $thisAction ?>">
							<span>Edit Captains</span>
						</button>
						&nbsp;
					<?php
					}
					?>
						<button type="submit" value="Back" class="submitBtn" name="ProcessAction">
							<span>Back</span>
						</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
	</div>
<?php
}

display_footer_wrapper();
?>