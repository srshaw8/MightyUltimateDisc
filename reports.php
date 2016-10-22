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
	$eventID = get_session_event_mgmt();
	$playerID = get_session_player_id();
	$thisPlayerID = "0";
	$errors = array();
	$rsPaged = "";
	$rsReport = "";
	$reportType = "";
	$pageType = "";
	$action = "";
	
	if (isset($_GET['type'])) {  /** for multipage report navigation */
		if ($_GET['type'] == "draft") {
			$reportType = "Draft";
		} else if ($_GET['type'] == "upaforms") {
			$reportType = "UPA Forms";
		} else if ($_GET['type'] == "upasubm") {
			$reportType = "UPA Submission";
		} else if ($_GET['type'] == "roster") {
			$reportType = "Roster";
		} else if ($_GET['type'] == "summary") {
			$reportType = "Summary";
		}
	} else if (isset($_POST['Report_Type'])) { /** from the report type button navigator */
		$reportType = $_POST['Report_Type'];	
	} else {
		$reportType = "Draft"; /** default report that is displayed */
	}

	if (check_value_is_set($eventID) and is_numeric($eventID)) {
		$isCaptain = check_captain_authorization();
		$isOwner = check_owner_authorization();
		$isAdmin = check_admin_authorization();
		
		$rsEvent =  get_event_profile_short($eventID);
		$upaEvent = $rsEvent['UPA_Event'];
		
		switch ($reportType) {
		    case "UPA Forms": 
		    	if ($isCaptain or $isOwner or $isAdmin) {
					$rsPaged = new MySQLPagedResultSet(get_report_upa_forms($eventID));
					build_report_default_page($errors,$rsPaged,$rsReport,$reportType,$pageType,$upaEvent);
				} else {
					log_entry(Logger::REPORT,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at UPA Forms page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_report_default_page($errors,$rsPaged,$rsReport,"toast",$pageType,$upaEvent);
					break;
				}
				break;
			case "UPA Submission" : 
		    	if ($isOwner or $isAdmin) {			
					$rsPaged = new MySQLPagedResultSet(get_report_upa_subm($eventID));
					build_report_default_page($errors,$rsPaged,$rsReport,$reportType,$pageType,$upaEvent);
					break;
				} else {
					log_entry(Logger::REPORT,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at UPA Submission page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_report_default_page($errors,$rsPaged,$rsReport,"toast",$pageType,$upaEvent);
					break;
				}
			case "Roster" :
				if ($isOwner or $isAdmin) {
					if (isset($_POST['ProcessAction']) and is_array($_POST['ProcessAction'])) {
						$processActionTemp = each($_POST['ProcessAction']);
						$thisPlayerID = $processActionTemp['key'];
						$processAction = $processActionTemp['value'];
						$action = "edit";
					} else { 
						$processAction = (isset($_POST['ProcessAction'])) ? $_POST['ProcessAction'] : "";
					}
					if ($processAction == "Save Roster Info") {
						$action = "save";
					}
			
					if ($action == "edit") {
						$rsReport = get_report_roster_player_info($eventID,$thisPlayerID);
						build_report_default_page($errors,$rsPaged,$rsReport,$reportType,"detail",$upaEvent);
					} else if ($action == "save") {
						$enteredData = get_data_entered($_POST);
						$errors = validate('rosterEdit', $enteredData);
						if (empty($errors)) {
							if ($enteredData['Registered'] == "Y") { /** simply save whatever data was entered **/
								if (!update_roster_player($eventID,$thisPlayerID,$enteredData)) {
									log_entry(Logger::REPORT,Logger::ERROR,$eventID,$playerID,
											"Player roster info update failed for player ".$thisPlayerID.".");
									$errors = 
									error_add($errors,"An error occurred while updating the player's roster data.");
								}
							} else { /** if unregistering, set team ID=0 and remove player role if applicable */
								if (!update_roster_player_reset($eventID,$thisPlayerID,$enteredData)) {
									log_entry(Logger::REPORT,Logger::ERROR,$eventID,$playerID,
											"Player roster info update failed for player ".$thisPlayerID.".");
									$errors = 
									error_add($errors,"An error occurred while updating the player's roster data.");
								}
								if (!update_archive_event_player_role($eventID,$thisPlayerID)) {
									log_entry(Logger::REPORT,Logger::ERROR,$eventID,$playerID,
										"Failed to archive player role for player ".$thisPlayerID.".");
									$errors = 
									error_add($errors, "An error occurred while deleting player's captain role.");
								}
							}
						}
						if (!empty($errors)) {
							$rsReport = get_report_roster_player_info($eventID,$thisPlayerID);
							build_report_default_page($errors,$rsPaged,$rsReport,$reportType,"detail",$upaEvent);
						} else {
							$rsPaged = new MySQLPagedResultSet(get_report_roster($eventID));
							build_report_default_page($errors,$rsPaged,$rsReport,$reportType,"summary",$upaEvent);
						}
					} else {
						$rsPaged = new MySQLPagedResultSet(get_report_roster($eventID));
						build_report_default_page($errors,$rsPaged,$rsReport,$reportType,"summary",$upaEvent);
					}
				} else {
					log_entry(Logger::REPORT,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at the Roster page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_report_default_page($errors,$rsPaged,$rsReport,"toast","",$upaEvent);
				}
				break;
			case "Summary" :
		    	if ($isOwner or $isAdmin) {			
					/** queries to get info */
					$rsReport = array();
					$rsTemp = get_report_summary_gender_reg($eventID,'M');
					$rsReport['Reg_Men'] = $rsTemp['Gender_Total'];
					$rsTemp = get_report_summary_gender_reg($eventID,'F');
					$rsReport['Reg_Women'] = $rsTemp['Gender_Total'];
					$rsTemp = get_report_summary_gender_wait($eventID,'M');
					$rsReport['Wait_Men'] = $rsTemp['Gender_Total'];
					$rsTemp = get_report_summary_gender_wait($eventID,'F');
					$rsReport['Wait_Women'] = $rsTemp['Gender_Total'];
					$rsTemp = get_report_summary_tshirts($eventID,'S');
					$rsReport['TShirt_S'] = $rsTemp['TShirt_Total'];
					$rsTemp = get_report_summary_tshirts($eventID,'M');
					$rsReport['TShirt_M'] = $rsTemp['TShirt_Total'];
					$rsTemp = get_report_summary_tshirts($eventID,'L');
					$rsReport['TShirt_L'] = $rsTemp['TShirt_Total'];
					$rsTemp = get_report_summary_tshirts($eventID,'XL');
					$rsReport['TShirt_XL'] = $rsTemp['TShirt_Total'];
					$rsTemp = get_report_summary_fees($eventID);
					$rsReport['Event_Fee'] = $rsTemp['Event_Fee'];
					$rsReport['TShirt_Fee'] = $rsTemp['TShirt_Fee'];
					$rsReport['UPA_Event_Fee'] = $rsTemp['UPA_Event_Fee'];
					$rsReport['Disc_Fee'] = $rsTemp['Disc_Fee'];
					$rsReport['Disc_Count'] = $rsTemp['Disc_Count'];
					$rsTemp = get_report_summary_fees_paid($eventID);
					$rsReport['Paid_Fees'] = 
						$rsTemp['Event_Fee']+$rsTemp['TShirt_Fee']+$rsTemp['UPA_Event_Fee']+$rsTemp['Disc_Fee'];
					build_report_default_page($errors,$rsPaged,$rsReport,$reportType,$pageType,$upaEvent);
					break;
				} else {
					log_entry(Logger::REPORT,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at the Summary page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_report_default_page($errors,$rsPaged,$rsReport,"toast",$pageType,$upaEvent);
					break;
				}
			default:  /** go to draft report */
				if ($isCaptain or $isOwner or $isAdmin) {
					$rsPaged = new MySQLPagedResultSet(get_report_draft($eventID));
					build_report_default_page($errors,$rsPaged,$rsReport,$reportType,$pageType,$upaEvent);
				} else {
					log_entry(Logger::REPORT,Logger::WARN,$eventID,$playerID,
							"Non-authorized player tried to peek at Draft page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_report_default_page($errors,$rsPaged,$rsReport,"toast",$pageType,$upaEvent);
					break;
				}
				break;			
		}
	} else {
		clear_selected_event();
		redirect_page("index.php");
	}
} else {
	display_non_authorization();
}

function build_report_links($reportType,$upaEvent) {
?>
	<form method="post" name="selectionForm" action="reports.php">
		<div id="report_links">
			<?php
			$style = ($reportType == "" or $reportType == "Draft") ? 
				" class=\"linkOn\"" : " class=\"link\"";
			?>
			<input type="submit"<?php echo $style ?> name="Report_Type" value="Draft">
			<?php
			if ($upaEvent == "Y") {
				$style = ($reportType == "UPA Forms") ? 
					" class=\"linkOn\"" : " class=\"link\"";
			?>
				<input type="submit"<?php echo $style ?> name="Report_Type" value="UPA Forms">
			<?php
			}
			if (check_admin_authorization() or check_owner_authorization()) {
			?>
				<?php
				if ($upaEvent == "Y") {
					$style = ($reportType == "UPA Submission") ? 
					" class=\"linkOn\"" : " class=\"link\"";
				?>
					<input type="submit"<?php echo $style ?> name="Report_Type" value="UPA Submission">
				<?php
				}
				$style = ($reportType == "Roster") ? 
					" class=\"linkOn\"" : " class=\"link\"";
				?>
				<input type="submit"<?php echo $style ?> name="Report_Type" value="Roster">
				<?php
				$style = ($reportType == "Summary") ? 
					" class=\"linkOn\"" : " class=\"link\"";
				?>
				<input type="submit"<?php echo $style ?> name="Report_Type" value="Summary">
			<?php
			}
			?>
		</div>
	</form>
<?php
}

function build_report_default_page($errors=array(),$rsPaged="",$rsReport="",$reportType="",$pageType="",$upaEvent="") {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		$isCaptain = check_captain_authorization();
		$isOwner = check_owner_authorization();
		$isAdmin = check_admin_authorization();
		if ($isCaptain or $isOwner or $isAdmin) {
		?>
			<?php
			if ($pageType != "detail") {
				build_report_links($reportType,$upaEvent);
			}
			display_errors($errors);
			switch ($reportType) {
				case "Draft" :
					build_report_draft_page($rsPaged);
					break;
			    case "UPA Forms":
				    build_report_upa_forms_page($rsPaged);
				    break;
				case "UPA Submission" :
					if ($isOwner or $isAdmin) {
						build_report_upa_subm_page($rsPaged);
					} else {
						"Sorry, your access to this page is not authorized...";
					}				
					break;
				case "Roster" :
					if ($isOwner or $isAdmin) {
						if ($pageType == "detail") {
							build_report_roster_detail_page($errors,$rsReport);
						} else {
							build_report_roster_summary_page($rsPaged);						
						}
					} else {
						"Sorry, your access to this page is not authorized...";
					}
					break;
				case "Summary" :
					if ($isOwner or $isAdmin) {
						build_report_summary_page($rsReport);						
					} else {
						"Sorry, your access to this page is not authorized...";
					}
					break;
			}
		} else {
			display_errors($errors); /** to non captain, non owner, non owner commoners... */
		}
		?>
		</div>
	</div>
<?php		
}

function build_report_draft_page($rsPaged) {
?>
	<table class="report">
		<tr>
			<th scope="col" class="dispSL">Gender</th>
			<th scope="col" class="dispSL">Handling<br/>Skill <a href="#"><img src="/images/q2b.jpg" align="top" 
width="15" height="15" border="0" alt="1: Newbie at throwing and<br/>&nbsp;&nbsp;&nbsp;&nbsp;catching<br/>2: Solid backhand or<br/>&nbsp;&nbsp;&nbsp;&nbsp;forehand<br/>3: Solid backhand and<br/>&nbsp;&nbsp;&nbsp;&nbsp;forehand<br/>4: Can handle vs. zone<br/>&nbsp;&nbsp;&nbsp;&nbsp;defense<br/>5: Franchise handler" /></a>
			</th>
			<th scope="col" class="dispSL">Defense<br/>Skill <a href="#"><img src="/images/q2b.jpg" align="top" 
width="15" height="15" border="0" alt="1: You can play defense in<br/>&nbsp;&nbsp;&nbsp;&nbsp;ultimate?<br/>2: Can play man D, learning<br/>&nbsp;&nbsp;&nbsp;&nbsp;finer points<br/>3: Comfortable playing man<br/>&nbsp;&nbsp;&nbsp;&nbsp;D, learning zone D<br/>4: Comfortable playing man<br/>&nbsp;&nbsp;&nbsp;&nbsp;or zone D<br/>5: Gonzo defensive<br/>&nbsp;&nbsp;&nbsp;&nbsp;specialist" /></a></th>
			<th scope="col" class="dispSL">Name</th>
			<th scope="col" class="dispSL">Yrs<br/>Exp</th>
			<th scope="col" class="dispSL">Play<br/>Level</th>
			<th scope="col" class="dispSL">Condition</th>
			<th scope="col" class="dispSL">Height</th>
			<th scope="col" class="dispSL">% of<br/>Games</th>
			<th scope="col" class="dispSL">Buddy</th>
		</tr>
		<?php	
		if ($rsPaged->getNumPages() > 0) {
			$i=0;
			while ($row=$rsPaged->fetchArray()) {
				$heightVal = "";
				$condVal = "";
				$playVal = "";
				$gamesVal = "";
				$tshirt = "";
				$discNum = "";
			
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
							$playVal = $playVal.",<br/> Never played<br/>at all";
						}
					}
					if ($tempVal == "2") {
						if ($playVal == "") {
							$playVal = "Pickup";
						}  else {
							$playVal = $playVal.",<br/> Pickup";
						}
					}
					if ($tempVal == "3") {
						if ($playVal == "") {
							$playVal = "High School";
						}  else {
							$playVal = $playVal.",<br/> High School";
						}
					}
					if ($tempVal == "4") {
						if ($playVal == "") {
							$playVal = "College";
						}  else {
							$playVal = $playVal.",<br/> College";
						}
					}
					if ($tempVal == "5") {
						if ($playVal == "") {
							$playVal = "Club";
						}  else {
							$playVal = $playVal.",<br/> Club";
						}
					}
					if ($tempVal == "6") {
						if ($playVal == "") {
							$playVal = "Masters";
						}  else {
							$playVal = $playVal.",<br/> Masters";
						}
					}
					if ($tempVal == "7") {
						if ($playVal == "") {
							$playVal = "League";
						}  else {
							$playVal = $playVal.",<br/> League";
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
			?>
				<tr<?php echo $altRow ?>>
					<td class="entrySL"><?php echo stripslashes($row['Gender']) ?></td>
					<td class="entrySL"><?php echo $row['Skill_Lvl'] ?></td>
					<td class="entrySL"><?php echo $row['Skill_Lvl_Def'] ?></td>
					<td class="entrySL">
					<?php echo stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']) ?></td>
					<td class="entrySL"><?php echo stripslashes($row['Yr_Exp']) ?></td>
					<td class="entrySL"><?php echo $playVal ?></td>
					<td class="entrySL"><?php echo $condVal ?></td>
					<td class="entrySL"><?php echo $heightVal ?></td>
					<td class="entrySL"><?php echo $gamesVal ?></td>
					<td class="entrySL"><?php echo stripslashes($row['Buddy_Name']) ?></td>
				</tr>
			<?php
				$i++;
			}
			?>
			<tr>
				<td colspan="10" class="entrySC">
					<?php echo $rsPaged->getPageNav("type=draft")?>
				</td>
			</tr>
			<tr>
				<th scope="col" class="dispSL" colspan="10">
					Download as an >>> <a href="report_draft_excel.php" target="_blank">Excel spreadsheet</a>
				</th>
			</tr>
		<?php
		} else {
		?>
			<tr>
				<td colspan="10" class="entrySL">
					Currently, no players have registered for this event.  Once players have registered, 
					you will be able to draft them to the roster.
				</td>
			</tr>
		<?php
		}
		?>
	</table>
<?php
}

function build_report_upa_forms_page($rsPaged) {
?>
	<table class="report">
		<tr>
			<th scope="col" class="dispSL">Team Name</th>
			<th scope="col" class="dispSL">Player Name</th>
			<th scope="col" class="dispSL">UPA #</th>
			<th scope="col" class="dispSL">Cur<br/>UPA<br/>Mem-<br/>ber?</th>
			<th scope="col" class="dispSL">Stu-<br/>dent?</th>
			<th scope="col" class="dispSL">Over<br/>18?</th>
			<th scope="col" class="dispSL">UPA<br/>Event<br/>Fee</th>
			<th scope="col" class="dispSL">Waiver<br/>Form?</th>
			<th scope="col" class="dispSL">Med<br/>Auth<br/>Form?</th>
			<th scope="col" class="dispSL">UPA<br/>Event<br/>Fee<br/>Form?</th>
			<th scope="col" class="dispSL">Chap-<br/>erone<br/>Form?</th>
		</tr>
		<?php	
		if ($rsPaged->getNumPages() > 0) {
			$i=0;
			while ($row=$rsPaged->fetchArray()) {
				$waiverVal = "Yes";
				$upaCurVal = "";
				$studentVal = "";
				$over18Val = "";
				$medicalVal = "";
				$eventFormVal = "";
				$chapVal = "";								
				
				if (stripslashes($row['UPA_Cur_Member']) == "Y") {
					$upaCurVal = "Yes";
				} else {
					$upaCurVal = "No";
				}
				if (stripslashes($row['Student']) == "Y") {
					$studentVal = "Yes";
				} else {
					$studentVal = "No";
				}
				if (stripslashes($row['Over18']) == "Y") {
					$medicalVal = "No";
					$over18Val = "Yes";
				} else {
					$medicalVal = "Yes";
					$over18Val = "No";
				}
				if (stripslashes($row['UPA_Event_Fee']) > 0) {
					$eventFormVal = "Yes";
				} else {
					$eventFormVal = "No";
				}
				if (stripslashes($row['Role']) == "Captain") {
					$chapVal = "Yes";
				} else {
					$chapVal = "No";
				}
				
				$altRow = $i % 2 ? " class=\"alt\"" : "";
				?>
				<tr<?php echo $altRow ?>>
					<td class="entrySL">
					<?php echo stripslashes($row['Team_Name']) ?></td>
					<td class="entrySL">
					<?php echo stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']) ?></td>
					<td class="entrySL"><?php echo stripslashes($row['UPA_Number']) ?></td>
					<td class="entrySL"><?php echo $upaCurVal ?></td>
					<td class="entrySL"><?php echo $studentVal ?></td>
					<td class="entrySL"><?php echo $over18Val ?></td>
					<td class="entrySL"><?php echo stripslashes($row['UPA_Event_Fee']) ?></td>
					<td class="entrySL"><?php echo $waiverVal ?></td>
					<td class="entrySL"><?php echo $medicalVal ?></td>
					<td class="entrySL"><?php echo $eventFormVal ?></td>
					<td class="entrySL"><?php echo $chapVal ?></td>
				</tr>
			<?php
				$i++;
			}
			?>
			<tr>
				<td colspan="11" class="entrySC">
					<?php echo $rsPaged->getPageNav("type=upaforms")?>
				</td>
			</tr>
			<tr>
				<th colspan="11" scope="col" class="dispSL">
					Download as an >>> <a href="report_upa_forms_excel.php" target="_blank">
					Excel spreadsheet</a>
				</th>
			</tr>
		<?php
		} else {
		?>
			<tr>
				<td colspan="11" class="entrySL">
					Currently, no players have registered and/or have been assigned to a team for this event.  
					Once players have registered, you will be able to view their UPA related information.
				</td>
			</tr>
		<?php
		}
		?>
	</table>
<?php
}

function build_report_upa_subm_page($rsPaged) {
?>
	<table class="report">
		<tr>
			<th scope="col" class="dispSL">Name</th>
			<th scope="col" class="dispSL">Address</th>
			<th scope="col" class="dispSL">UPA Number</th>
			<th scope="col" class="dispSL">Home Phone</th>
			<th scope="col" class="dispSL">Email</th>
		</tr>
		<?php	
		if ($rsPaged->getNumPages() > 0) {
			$i=0;
			while ($row=$rsPaged->fetchArray()) {
				$altRow = $i % 2 ? " class=\"alt\"" : "";
				?>
				<tr<?php echo $altRow ?>>
					<td class="entrySL">
					<?php echo stripslashes($row['First_Name'])." ".stripslashes($row['Last_Name']) ?></td>
					<td class="entrySL">
						<?php echo stripslashes($row['Address'])."<br/>".stripslashes($row['City']).", ".stripslashes($row['State_Prov'])." ".stripslashes($row['Post_Code']) ?>
					</td>
					<td class="entrySL"><?php echo stripslashes($row['UPA_Number']) ?></td>
					<td class="entrySL"><?php echo stripslashes($row['H_Phone']) ?></td>
					<td class="entrySL"><?php echo stripslashes($row['Email']) ?></td>
				</tr>
			<?php
				$i++;
			}
			?>
			<tr>
				<td colspan="8" class="entrySC">
					<?php echo $rsPaged->getPageNav("type=upasubm")?>
				</td>
			</tr>
			<tr>
				<th scope="col" class="dispSL" colspan="5">
					Download as an >>> <a href="report_upa_subm_excel.php" target="_blank">
					Excel spreadsheet</a>
				</th>
			</tr>
		<?php
		} else {
		?>
			<tr>
				<td colspan="5" class="entrySL">
					Currently, no players have registered for this event.  Once players have registered, 
					you will be able to view their UPA related information.
				</td>
			</tr>
		<?php
		}
		?>
	</table>
<?php
}

function build_report_roster_summary_page($rsPaged) {
	$resultpage = (isset($_GET['rp'])) ? $_GET['rp'] : "";
?>
	<form method="post" name="selectionForm" action="reports.php?rp=<?php echo $resultpage ?>&type=roster" class="boxReport">
	<table class="report">
		<tr>
			<th scope="col" class="dispSL">Name</th>
			<th scope="col" class="dispSL">Reg?</th>
			<th scope="col" class="dispSL">Paid?</th>
			<th scope="col" class="dispSL">Pay<br/>Type</th>
			<th scope="col" class="dispSR">Total<br/>Fees</th>
			<th scope="col" class="dispSR">Event<br/>Fee</th>
			<th scope="col" class="dispSR">TShirt<br/>Fee</th>
			<th scope="col" class="dispSR">Size</th>
			<th scope="col" class="dispSR">UPA<br/>Event<br/>Fee</th>
			<th scope="col" class="dispSR">Disc<br/>Fee</th>
			<th scope="col" class="dispSR"># of<br/>Discs</th>
		</tr>
		<?php	
		if ($rsPaged->getNumPages() > 0) {
			$i=0;
			while ($row=$rsPaged->fetchArray()) {
				$reg = (strstr($row['Registered'],"Y")) ? "Yes" : "No";
				$payStatus = (strstr(stripslashes($row['Payment_Status']),"Y")) ? "Yes" : "No";
				$payType = "";
				switch (stripslashes($row['Payment_Type'])) {
					case "1":
						$payType = "Csh";
						break;
					case "2":
						$payType = "Chk";
						break;
					case "3":
						$payType = "Ppal";
						break;
				}
				
				$tshirtSize = "";
				switch (stripslashes($row['T_Shirt_Size'])) {
					case "S":
						$tshirtSize = "S";
						break;
					case "M":
						$tshirtSize = "M";
						break;
					case "L":
						$tshirtSize = "L";
						break;
					case "XL":
						$tshirtSize = "XL";
						break;
				}
				$eventFee = ($row['Event_Fee'] > 0) ? $row['Event_Fee'] : "0";
				$tshirtFee = ($row['TShirt_Fee'] > 0) ? $row['TShirt_Fee'] : "0";
				$upaEventFee = ($row['UPA_Event_Fee'] > 0) ? $row['UPA_Event_Fee'] : "0";
				$discFee = ($row['Disc_Fee'] > 0) ? $row['Disc_Fee'] : "0";
				$totalFee = $eventFee + $tshirtFee + $upaEventFee + $discFee;
				
				$altRow = $i % 2 ? " class=\"alt\"" : "";
				
				$thisValue = "ProcessAction[".$row['Player_ID']."]";
				$linkSm = $i % 2 ? " class=\"linkSmAlt\"" : " class=\"linkSm\"";
			?>
				<tr<?php echo $altRow ?>>
					<td class="entrySL">
						<input type="submit" 
						<?php echo $linkSm ?> name="<?php echo $thisValue ?>" 
						value="<?php echo stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']) ?>">
					</td>
					<td class="entrySL"><?php echo $reg ?></td>
					<td class="entrySL"><?php echo $payStatus ?></td>
					<td class="entrySL"><?php echo $payType ?></td>
					<td class="entrySR">
						<?php echo "\$".number_format($totalFee, 2, '.', '') ?>
					</td>
					<td class="entrySR">
						<?php echo "\$".number_format($eventFee, 2, '.', '') ?>
					</td>
					<td class="entrySR">
						<?php echo "\$".number_format($tshirtFee, 2, '.', '') ?>
					</td>
					<td class="entrySR">
						<?php echo $tshirtSize ?>
					</td>
					<td class="entrySR">
						<?php echo "\$".number_format($upaEventFee, 2, '.', '') ?>
					</td>
					<td class="entrySR">
						<?php echo "\$".number_format($discFee, 2, '.', '') ?>
					</td>
					<td class="entrySR">
						<?php echo $thisVal = ($row['Disc_Count']>0) ? $row['Disc_Count'] : "0" ?>
					</td>
				</tr>
			<?php
				$i++;
			}
			?>
			<tr>
				<td colspan="11" class="entrySC">
					<?php echo $rsPaged->getPageNav("type=roster")?>
				</td>
			</tr>
			<tr>
				<th scope="col" class="dispSL" colspan="11">
					Download as an >>> <a href="report_roster_excel.php" target="_blank">
					Excel spreadsheet</a>
				</th>
			</tr>
		<?php
		} else {
		?>
			<tr>
				<td colspan="11" class="entrySL">
					Currently, no players have registered for this event. Once players have registered, you will 
					be able to view them on the roster. 
				</td>
			</tr>
		<?php
		}
		?>
	</table>
	</form>
<?php
}

function build_report_roster_detail_page($errors,$rsReport) {
$resultpage = (isset($_GET['rp'])) ? $_GET['rp'] : "";
?>
	<form method="post" name="selectionForm" action="reports.php?rp=<?php echo $resultpage ?>&type=roster" class="boxReport">
		<table class="report">
			<tr>
				<td></td>
				<td><span class="smGD">* required entry</span></td>
			</tr>
			<tr>
				<td class="titleRR">Player</td>
				<td class="entryRL">
				<?php echo stripslashes($rsReport["First_Name"])." ".stripslashes($rsReport["Last_Name"]) ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Home Phone #</td>
				<td class="entryRL">
				<?php echo $rsReport['H_Phone']; ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Cell Phone</td>
				<td class="entryRL">
				<?php echo $rsReport['C_Phone']; ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Work Phone</td>
				<td class="entryRL">
				<?php echo $rsReport['W_Phone']; ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Emergency Contact Name</td>
				<td class="entryRL">
				<?php echo stripslashes($rsReport['E_Contact_Name']); ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Emergency Contact Phone Number</td>
				<td class="entryRL">
				<?php echo $rsReport['E_Contact_Phone']; ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">UPA Member Status</td>
				<td class="entryRL">
					<?php 
					echo $upaStatus = (strstr($rsReport['UPA_Cur_Member'],"Y")) ? "Current" : "Not Current";
					?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">T Shirt Size</td>
				<td class="entryRL">
				<?php
				switch (stripslashes($rsReport['T_Shirt_Size'])) {
					case "S":
						echo $tshirtSize = "Small";
						break;
					case "M":
						echo $tshirtSize = "Medium";
						break;
					case "L":
						echo $tshirtSize = "Large";
						break;
					case "XL":
						echo $tshirtSize = "Xtra Large";
						break;
				}						 
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Team Assigned To</td>
				<td class="entryRL">
				<?php
				echo $teamName = 
					(stripslashes($rsReport['Team_Name']) <> "") ? 
						stripslashes($rsReport['Team_Name']) : "Not yet assigned";
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Event Fee*</td>
				<td class="entryRL">
					<?php $eventFee = ($rsReport['Event_Fee'] > 0) ? $rsReport['Event_Fee'] : "0"; ?>
					<input 	type="text" 
							name="Event_Fee"
							value="<?php echo number_format($eventFee, 2, '.', '') ?>" 
							size="30" 
							maxlength="5">
				</td>
			</tr>
			<?php 
			if (isset($errors['Event_Fee'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Event_Fee'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR">T-Shirt Fee*</td>
				<td class="entryRL">
					<?php $tshirtFee = ($rsReport['TShirt_Fee'] > 0) ? $rsReport['TShirt_Fee'] : "0"; ?>
					<input 	type="text" 
							name="TShirt_Fee"
							value="<?php echo number_format($tshirtFee, 2, '.', '') ?>" 
							size="30" 
							maxlength="5">
				</td>
			</tr>
			<?php 
			if (isset($errors['TShirt_Fee'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['TShirt_Fee'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR">UPA Event Fee*</td>
				<td class="entryRL">
					<?php $upaEventFee = ($rsReport['UPA_Event_Fee'] > 0) ? $rsReport['UPA_Event_Fee'] : "0"; ?>
					<input 	type="text" 
							name="UPA_Event_Fee"
							value="<?php echo number_format($upaEventFee, 2, '.', '') ?>" 
							size="30" 
							maxlength="5">
				</td>
			</tr>
			<?php 
			if (isset($errors['UPA_Event_Fee'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['UPA_Event_Fee'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR">Disc Fee*</td>
				<td class="entryRL">
					<?php $discFee = ($rsReport['Disc_Fee'] > 0) ? $rsReport['Disc_Fee'] : "0";	?>
					<input 	type="text" 
							name="Disc_Fee"
							value="<?php echo number_format($discFee, 2, '.', '') ?>" 
							size="30" 
							maxlength="5">
				</td>
			</tr>
			<?php 
			if (isset($errors['Disc_Fee'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Disc_Fee'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR"># of Discs Purchased*</td>
				<td class="entryRL">
				<?php $discCount = ($rsReport['Disc_Count'] > 0) ? $rsReport['Disc_Count'] : "0"; ?>
					<input 	type="text" 
							name="Disc_Count"
							value="<?php echo $discCount ?>" 
							size="30" 
							maxlength="5">
				</td>
			</tr>
			<?php 
			if (isset($errors['Disc_Count'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Disc_Count'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR">Total Fees</td>
				<td class="entryRL">
					<?php 
					echo "\$".number_format(($eventFee + $tshirtFee + $upaEventFee + $discFee), 2, '.', '');
					?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Registered</td>
				<td class="entryRL">
					<input type="radio" 
						name="Registered" 
						tabindex="1" 
						value="Y"
						<?php if (strstr($rsReport['Registered'],"Y")) { 
							print "checked"; } ?>>Yes
					<input type="radio" 
						name="Registered" 
						tabindex="1" 
						value="N"
						<?php if (strstr($rsReport['Registered'],"N")) { 
							print "checked"; } ?>>No
				</td>
			</tr>
			<?php 
			if (isset($errors['Registered'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Registered'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR">Payment Status</td>
				<td class="entryRL">
					<input type="radio" 
						name="Payment_Status" 
						tabindex="2" 
						value="Y"
						<?php if (strstr($rsReport['Payment_Status'],"Y")) { 
							print "checked"; } ?>>Paid
					<input type="radio" 
						name="Payment_Status" 
						tabindex="2" 
						value="N"
						<?php if (strstr($rsReport['Payment_Status'],"N")) { 
							print "checked"; } ?>>Not Paid
				</td>
			</tr>
			<?php 
			if (isset($errors['Payment_Status'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Payment_Status'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td class="titleRR">Payment Type</td>
				<td class="entryRL">
					<input type="radio" 
						name="Payment_Type" 
						tabindex="3" 
						value="1"
						<?php if (strstr($rsReport['Payment_Type'],"1")) { 
							print "checked"; } ?>>Cash
					<input type="radio" 
						name="Payment_Type" 
						tabindex="3" 
						value="2"
						<?php if (strstr($rsReport['Payment_Type'],"2")) { 
							print "checked"; } ?>>Check
					<input type="radio" 
						name="Payment_Type" 
						tabindex="3" 
						value="3"
						<?php if (strstr($rsReport['Payment_Type'],"3")) { 
							print "checked"; } ?>>Paypal
				</td>
			</tr>
			<?php 
			if (isset($errors['Payment_Type'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Payment_Type'] ?></td></tr>
			<?php
			}
			?>
			<tr>
				<td colspan="2" class="dispRC">
					<?php 
					$thisValue = "ProcessAction[".$rsReport['Player_ID']."]";
					?>
					<button type="submit" value="Save Roster Info" class="submitBtn" 
						name="<?php echo $thisValue ?>">
						<span>Save Roster Info</span>
					</button>
					&nbsp;&nbsp;
					<button type="submit" value="Cancel" class="submitBtn" name="ProcessAction">
						<span>Cancel</span>
					</button>
				</td>
			</tr>
		</table>
	</form>
<?php
}

function build_report_summary_page($rsReport) {
	$eventFee = ($rsReport['Event_Fee']) > 0 ? $rsReport['Event_Fee'] : 0; 
	$tshirtFee = ($rsReport['TShirt_Fee']) > 0 ? $rsReport['TShirt_Fee'] : 0;
	$discFee = ($rsReport['Disc_Fee']) > 0 ? $rsReport['Disc_Fee'] : 0;
	$upaEventFee = ($rsReport['UPA_Event_Fee']) > 0 ? $rsReport['UPA_Event_Fee'] : 0;
	$discCount = ($rsReport['Disc_Count']) > 0 ? $rsReport['Disc_Count'] : 0;
?>
	<table class="report">
		<tr>
			<th colspan="3" scope="col" class="dispSL">Player Counts</th>
		</tr>
		<tr>
			<td class="titleSL">Number of Registered Men:</td>
			<td class="entrySR"><?php echo $rsReport['Reg_Men'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Number of Registered Women:</td>
			<td class="entrySR"><?php echo $rsReport['Reg_Women'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Number of Registered Player:</td>
			<td class="entrySR"><?php echo ($rsReport['Reg_Men']+$rsReport['Reg_Women']) ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td class="titleSL">Number of Wait Listed Men:</td>
			<td class="entrySR"><?php echo $rsReport['Wait_Men'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Number of Wait Listed Women:</td>
			<td class="entrySR"><?php echo $rsReport['Wait_Women'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Number of Wait Listed Player:</td>
			<td class="entrySR"><?php echo ($rsReport['Wait_Men']+$rsReport['Wait_Women']) ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<th scope="col" class="dispSL" colspan="3">Fees</th>
		</tr>
		<tr>
			<td class="titleSL">Total Event Fees:</td>
			<td class="entrySR"><?php echo "\$".number_format($eventFee, 2, '.', ',') ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total T-Shirt Fees:</td>
			<td class="entrySR"><?php echo "\$".number_format($tshirtFee, 2, '.', ',') ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Disc Fees:</td>
			<td class="entrySR"><?php echo "\$".number_format($discFee, 2, '.', ',') ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total UPA 1 Time Event Fees:</td>
			<td class="entrySR"><?php echo "\$".number_format($upaEventFee, 2, '.', ',') ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Accrued Fees:</td>
			<td class="entrySR">
				<?php echo "\$".number_format(($eventFee+$tshirtFee+$discFee+$upaEventFee), 2, '.', ',') ?>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Fees Marked as Paid:</td>
			<td class="entrySR"><?php echo "\$".number_format($rsReport['Paid_Fees'], 2, '.', ',') ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<th colspan="3" scope="col" class="dispSL">Schwag</th>
		</tr>
		<tr>
			<td class="titleSL">Total Small T-Shirts:</td>
			<td class="entrySR"><?php echo $rsReport['TShirt_S'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Medium T-Shirts:</td>
			<td class="entrySR"><?php echo $rsReport['TShirt_M'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Large T-Shirts:</td>
			<td class="entrySR"><?php echo $rsReport['TShirt_L'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Xtra Large T-Shirts:</td>
			<td class="entrySR"><?php echo $rsReport['TShirt_XL'] ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="titleSL">Total Discs:</td>
			<td class="entrySR"><?php echo $discCount ?></td>
			<td>&nbsp;</td>
		</tr>
	</table>
<?php	
}

display_footer_wrapper();
?>