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
/** page specific includes */
include_relative("paged_results.php");

if (check_authorization()) {
	/** variable declarations */
	$action = "";
	$processAction = "";
	$eventID = get_session_event_mgmt();
	$playerID = get_session_player_id();
	$thisPlayerID = "";
	$rsPlayer = "";
	$rsTeams = "";
	$rsEvent = "";
	$pagedResult = "";
	$waitListData = array();
	$fees = array();
	$fees['event'] = 0.00;
	$fees['eventTShirt'] = 0.00;
	$fees['eventDisc'] = 0.00;
	$fees['event1Time'] = 0.00;
	$fees['total'] = 0.00;
	$discCount = 0.00;
	$errors = array();
	
	if (isset($_POST['ProcessAction']) and is_array($_POST['ProcessAction'])) {
		$processActionTemp = each($_POST['ProcessAction']);
		$thisPlayerID = $processActionTemp['key'];
        $processAction = cleanAction($processActionTemp['value']);        
		$action = "edit";
	} else { 
		$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
		$processAction = cleanAction($thisProcessAction);
	}
	if ($processAction == "Select") {
		$action = "edit";
	} else if ($processAction == "Assign") {
		$action = "assign";
	}

	if (check_value_is_set($eventID) and is_numeric($eventID)) {
		if (check_admin_authorization() or check_owner_authorization()) {
			$rsTeams = get_team_profiles_active($eventID);
			if ($action == "edit") {
				if (is_numeric($thisPlayerID)) {
					$rsPlayer = get_player_profile_short($thisPlayerID);
					build_wait_list_detail_page($errors,$rsPlayer,$rsTeams);					
				} else {
					log_entry(Logger::WAIT,Logger::ERROR,$eventID,$playerID,
						"Failed to edit waitlisted player ".$thisPlayerID);
					$errors = error_add($errors, "An error occured while trying to edit the selected wait listed 
												player.");
					$rsWaitList = get_wait_list($eventID);
					$pagedResult = new MySQLPagedResultSet($rsWaitList);
					build_wait_list_summary_page($errors,$pagedResult);
				}
			} else if ($action == "assign") {
				if (is_numeric($thisPlayerID)) {
					$rsPlayer = get_player_profile_short($thisPlayerID);
					$enteredData = get_data_entered($_POST);
					$teamID = (isset($enteredData['Team_Assign'])) ? $enteredData['Team_Assign'] : 0;
					if (is_numeric($teamID)) {
						$eventAssign = $enteredData['Event_Assign'];
						if ($teamID > 0 or $eventAssign == 'Y'){
							if ($eventAssign == 'Y') {
								$teamID = 0;
							}
							$waitListData =	get_wait_list_unassigned($eventID, $thisPlayerID);
							if(is_array($waitListData)) {
								$fees['event'] = $waitListData['Event_Fee'];
								$fees['eventTShirt'] = $waitListData['TShirt_Fee'];
								$fees['eventDisc'] = $waitListData['Disc_Fee'];
								$fees['event1Time'] = $waitListData['UPA_Event_Fee'];
								$fees['total'] = 
									$fees['event']+$fees['eventTShirt']+$fees['eventDisc']+$fees['event1Time'];
								$discCount = $waitListData['Disc_Count'];
								$gender = $waitListData['Gender'];
								$pctOfGames = $waitListData['Pct_Of_Games'];
						if (!insert_roster($eventID,$thisPlayerID,$teamID,$fees,$discCount,$gender,$pctOfGames)){
									log_entry(Logger::WAIT,Logger::ERROR,$eventID,$playerID,
											"Failed to add player ".$thisPlayerID." to roster from wait list.");
									$errors = error_add($errors, "An error occured while trying to assign wait	
											 listed	player to the roster.");
								} else {
									if(!update_wait_list_assignment($eventID, $thisPlayerID)) {
										log_entry(Logger::WAIT,Logger::ERROR,$eventID,$playerID,
											"Failed to update assignment flag for player ".$thisPlayerID." who was 
											added to roster from wait list.");
										$errors = error_add($errors, "An error occurred while removing the player  
																from the wait list.");
									}
									sendEmail(EmailWrapper::WAIT_LIST_OFF,$eventID,$teamID,array($thisPlayerID));
								}
							} else {
								log_entry(Logger::WAIT,Logger::ERROR,$eventID,$playerID,
										"Could not get wait list data for player ".$thisPlayerID.".");
								$errors = error_add($errors, "This player is no longer on the wait list. Please go
														 to the	wait list summary page.");
							}
						}
					} else {
						log_entry(Logger::WAIT,Logger::ERROR,$eventID,$playerID,
							"Failed to assign waitlisted player to team ".$teamID." It is non-numeric.");
						$errors = error_add($errors, "An error occured while trying to assign the selected 
														wait listed	player to a team.");
					}	
				} else {
					log_entry(Logger::WAIT,Logger::ERROR,$eventID,$playerID,
						"Failed to assign waitlisted player ".$thisPlayerID." It is non-numeric.");
					$errors = error_add($errors, "An error occured while trying to assign the selected wait listed 
												player.");
				}
				
				/** handle special case of redirect to detail page */
				if (!empty($errors)) {
					if ($rsPlayer and $rsTeams) {
						build_wait_list_detail_page($errors,$rsPlayer,$rsTeams);
					}
				}
				/** go here whether there is error or not.. */
				$rsWaitList = get_wait_list($eventID);
				$pagedResult = new MySQLPagedResultSet($rsWaitList);
				build_wait_list_summary_page($errors,$pagedResult);
			} else {
				$rsWaitList = get_wait_list($eventID);
				$pagedResult = new MySQLPagedResultSet($rsWaitList);
				build_wait_list_summary_page($errors,$pagedResult);
			}
		} else {
			log_entry(Logger::WAIT,Logger::WARN,$eventID,$playerID,
					"Non-authorized player tried to peek at Wait List page.");
			$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
			build_wait_list_summary_page($errors,$pagedResult);
		}
	} else {
		clear_selected_event();
		redirect_page("index.php");
	}
} else {
	display_non_authorization();
}

function build_wait_list_summary_page($errors,$pagedResult) {
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
	
		$isOwner = check_owner_authorization();
		$isAdmin = check_admin_authorization(); 
		if ($isOwner or $isAdmin) {
		?>
			<form method="post" name="selectionForm" action="wait_list.php" class="boxReport">
				<table class="report">
					<tr>
						<th scope="col" class="dispSL">Sex</th>
						<th scope="col" class="dispSL">Wait<br/>&nbsp;&nbsp;#</th>
						<th scope="col" class="dispSL">Name/Contact Info</th>
						<th scope="col" class="dispSL">Handling<br/>Skill <a href="#"><img src="/images/q2b.jpg" align="top" width="15" height="15" border="0" alt="1: Newbie at throwing and<br/>&nbsp;&nbsp;&nbsp;&nbsp;catching<br/>2: Solid backhand or<br/>&nbsp;&nbsp;&nbsp;&nbsp;forehand<br/>3: Solid backhand and<br/>&nbsp;&nbsp;&nbsp;&nbsp;forehand<br/>4: Can handle vs. zone<br/>&nbsp;&nbsp;&nbsp;&nbsp;defense<br/>5: Franchise handler" /></a></th>
						<th scope="col" class="dispSL">Defense<br/>Skill <a href="#"><img src="/images/q2b.jpg" align="top" width="15" height="15" border="0" alt="1: You can play defense in<br/>&nbsp;&nbsp;&nbsp;&nbsp;ultimate?<br/>2: Can play man D, learning<br/>&nbsp;&nbsp;&nbsp;&nbsp;finer points<br/>3: Comfortable playing man<br/>&nbsp;&nbsp;&nbsp;&nbsp;D, learning zone D<br/>4: Comfortable playing man<br/>&nbsp;&nbsp;&nbsp;&nbsp;or zone D<br/>5: Gonzo defensive<br/>&nbsp;&nbsp;&nbsp;&nbsp;specialist" /></a></th>
						<th scope="col" class="dispSL">Yrs<br/>Exp</th>
						<th scope="col" class="dispSL">Play Level</th>
						<th scope="col" class="dispSL">Condition</th>
						<th scope="col" class="dispSL">Height</th>
						<th scope="col" class="dispSL">% of<br/>Games</th>
					</tr>
					<?php	
					if ($pagedResult->getNumPages() > 0) {
						$i=0;
						while ($row=$pagedResult->fetchArray()) {
							$heightVal = "";
							$condVal = "";
							$playVal = "";
							$gamesVal = "";
							
							$tempArray = explode(",", $row['Height']);
							foreach($tempArray as $tempVal) {
								switch ($tempVal) {
									case 1:
										$heightVal = "<5'0\"";
										break;
									case 2:
										$heightVal = "5'1\"-<br/>5'4\"";
										break;
									case 3:
										$heightVal = "5'5\"-<br/>5'8\"";
										break;
									case 4:
										$heightVal = "5'9\"-<br/>6'0\"";
										break;
									case 5:
										$heightVal = "6'1\"+";
										break;
								}
							}
							
							$tempArray = explode(",", $row['Conditionx']);
							foreach($tempArray as $tempVal) {
								switch ($tempVal) {
									case 1:
										$condVal = "Turtle";
										break;
									case 2:
										$condVal = "Elephant";
										break;
									case 3:
										$condVal = "Rabbit";
										break;
									case 4:
										$condVal = "Gazelle";
										break;
								}
							}
							
							$tempArray = explode(",", $row['Play_Lvl']);
							foreach($tempArray as $tempVal) {
								if ($tempVal == "1") {
									if ($playVal == "") {
										$playVal = "Never played<br/>at all";
									}  else {
										$playVal = $playVal."<br/>Never played<br/>at all";
									}
								}
								if ($tempVal == "2") {
									if ($playVal == "") {
										$playVal = "Pickup";
									}  else {
										$playVal = $playVal."<br/>Pickup";
									}
								}
								if ($tempVal == "3") {
									if ($playVal == "") {
										$playVal = "High School";
									}  else {
										$playVal = $playVal."<br/>High School";
									}
								}
								if ($tempVal == "4") {
									if ($playVal == "") {
										$playVal = "College";
									}  else {
										$playVal = $playVal."<br/>College";
									}
								}
								if ($tempVal == "5") {
									if ($playVal == "") {
										$playVal = "Club";
									}  else {
										$playVal = $playVal."<br/>Club";
									}
								}
								if ($tempVal == "6") {
									if ($playVal == "") {
										$playVal = "Masters";
									}  else {
										$playVal = $playVal."<br/>Masters";
									}
								}
								if ($tempVal == "7") {
									if ($playVal == "") {
										$playVal = "League";
									}  else {
										$playVal = $playVal."<br/>League";
									}
								}
							}
							
							$tempArray = explode(",", $row['Pct_Of_Games']);
							foreach($tempArray as $tempVal) {
								switch ($tempVal) {
									case 1:
										$gamesVal = "<50%";
										break;
									case 2:
										$gamesVal = "50%-<br/>75%";
										break;
									case 3:
										$gamesVal = "+75%";
										break;
								}
							}
							
							$altRow = $i % 2 ? " class=\"alt\"" : "";
							
							$thisValue = "ProcessAction[".$row['Player_ID']."]";
							$linkSm = $i % 2 ? " class=\"linkSmAlt\"" : " class=\"linkSm\"";
						?>
							<tr<?php echo $altRow ?>>
								<td class="entrySL"><?php echo stripslashes($row['Gender']) ?></td>
								<td class="entrySL"><?php echo $row['Wait_Number'] ?></td>
								<td class="entrySL">
									<input type="submit" <?php echo $linkSm ?> 
									name="<?php echo $thisValue ?>" 
									value="<?php echo stripslashes($row['Last_Name']).', '.stripslashes($row['First_Name'])?>">
								<?php 
								$paren = array("(",")");
								echo '(h)'.str_replace($paren, " ", stripslashes($row['H_Phone'])).'<br/>';
								if ($row['C_Phone'] <> "") {
									echo '(c)'.str_replace($paren, " ", stripslashes($row['C_Phone'])).'<br/>';
								}
								?>
								<a href="mailto:<?php echo stripslashes($row['Email']) ?>">Email</a>
								</td>
								<td class="entrySL"><?php echo $row['Skill_Lvl'] ?></td>
								<td class="entrySL"><?php echo $row['Skill_Lvl_Def'] ?></td>
								<td class="entrySL"><?php echo stripslashes($row['Yr_Exp']) ?></td>
								<td class="entrySL"><?php echo $playVal ?></td>
								<td class="entrySL"><?php echo $condVal ?></td>
								<td class="entrySL"><?php echo $heightVal ?></td>
								<td class="entrySL"><?php echo $gamesVal ?></td>
							</tr>
						<?php
							$i++;
						}
						?>
						<tr>
							<th colspan="11" scope="col" class="dispSCx">
								<?php echo $pagedResult->getPageNav()?>
							</th>
						</tr>
					<?php
					} else {
					?>
						<tr>
							<td class="entrySL" colspan="11">
								Currently, no players are on the wait list for this event.
							</td>
						</tr>
					<?php
					}
					?>
				</table>
			</form>
		<?php
		}
		?>
		</div>
	</div>
<?php
}

function build_wait_list_detail_page($errors,$rsPlayer,$rsTeams) {
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
			?>
			<div id="xsnazzy">
			<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
			<div class="xboxcontent">
				<form method="post" name="selectionForm" action="wait_list.php" class="box">
					<table class="default">
						<tr>
							<td></td>
							<td colspan="2"><span class="smGD">* required entry</span></td>
						</tr>
						<tr>
							<td class="titleRR">Player</td>
							<td class="entryRL">
								<?php echo $rsPlayer["First_Name"]." ".$rsPlayer["Last_Name"] ?>
							</td>
						</tr>
						<tr>
							<td class="titleRR">
							<?php
							if ($rsTeams) {
								echo "Assign player to a team*";
							} else {
								echo "Would you like to assign the player to your event?*";
							}
							?>
							</td>
							<td class="entryRL">
							<?php
							/** determine if we display an assignment list of teams or just the event */
							if ($rsTeams) {
								$numResults = mysql_num_rows($rsTeams);
							?>
								<select name="Team_Assign" size="1">
								<option value="0">Please select</option>
								<?php	
								while ($row=mysql_fetch_array($rsTeams)) {
									$teamID = $row["Team_ID"];
									$teamName = stripslashes($row["Team_Name"]);
									echo "<option value=$teamID>$teamName</option>";
								} 
								?>
								</select>
							<?php
							} else {	
							?>	
								<input type="radio" 
										name="Event_Assign"
										value="Y">Yes
								<input type="radio" 
									name="Event_Assign"
									value="N" checked="true">No
							<?php
							}
							?>
							</td>
						</tr>
						<?php 
						if (isset($errors['Assign_This'])) {
						?>
						<tr><td></td><td class="error" colspan="2"><?php echo $errors['Assign_This'] ?></td></tr>	
						<?php			
						}
						?>
						<tr>
							<td colspan="2" class="dispRC">
								<?php 
								$thisValue = "ProcessAction[".$rsPlayer['Player_ID']."]";
								?>
								<button type="submit" value="Assign" class="submitBtn" name="<?php echo $thisValue ?>">
									<span>Assign</span>
								</button>
								&nbsp;&nbsp;
								<button type="submit" value="Cancel" class="submitBtn" name="ProcessAction">
									<span>Cancel</span>
								</button>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
			</div>
		</div>
	</div>
<?php
}

display_footer_wrapper();
?>