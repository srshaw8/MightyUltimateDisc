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
	$actionResult = array();
	$processAction = "";
	$enteredData = array();
	$errors = array();
	$assignees = array();
	$playerID = get_session_player_id();
	$eventID = get_session_event_mgmt();
	$teamID = 0; /** always set to 0 when managing event and owners */
	$eventName = get_session_event_name(); 
	$eventRole = get_session_player_role();
	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization();
	
	$tempAction = (isset($_POST['Action'])) ? $_POST['Action'] : "";
	$tempID = (isset($_POST['ID'])) ? $_POST['ID'] : "";
	
	if (isset($_REQUEST['a'])) {
		$processAction = $_REQUEST['a'];
	} else if (check_value_is_set($tempAction) and check_value_is_set($tempID)) { /** for delete */
		$processAction = $_POST['Action'];
		$eventID = $_POST['ID'];
	} else {
		$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
		$processAction = cleanAction($thisProcessAction);
	}

	if ($processAction == "Create") {  /** action from general display page */
		$action = "eventCreate";
	} else if ($processAction == "Create Event") {  /** action from detail page */
		$action = "eventCreateEvent";
	} else if ($processAction == " Cancel ") {  /** action from detail page */
		$action = "eventCancel";
	} else if ($processAction == "Edit") {  /** action from view detail page */
		$action = "eventEdit";		
	} else if ($processAction == "Save Event") {  /** action from edit detail page */
		$action = "eventSave";
	} else if ($processAction == "Activate") {  /** action from view detail page */
		$action = "eventActivate";
	} else if ($processAction == "Activate Event") {  /** action from edit detail page */
		$action = "eventActivateEvent";
	} else if ($processAction == "Delete") {  /** action from view detail page */
		$action = "eventDelete";
	} else {	
		$action = "eventView";  /** default action */
	}

	if ($action == "eventCreate") { /** action from general display page */
		/** clear out any previously selected event from session and role */
		reset_event_session($isAdmin);
		/** if you don't set enteredData to false, php will throw a gabillion error notices saying 
		 *  that xyz enteredData array element is not initialized due to the way its reference in 
		 *  the edit version of the event management form below... 
		 */
		$enteredData = false;
		build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
	} else if ($action == "eventCreateEvent") {
		$enteredData = get_data_entered($_POST);
		$errors = validate_data($enteredData);
		if (empty($errors)){
			/** check if event already exists  - if not, then do insert for new event */				
			if (!check_dupe_event($enteredData['Event_Name'])) {
				/** determine type of insert based on event type: league/hat vs. pickup */
				if ($enteredData['Event_Type'] == "1" or $enteredData['Event_Type'] == "3") {
					/** compute return URL for the event after payment to MU is made */
					//$enteredData['Payment_Return_URL'] = get_site_URL()."/event_activate.php";
					/** determine the currency code based on the country selected */
					$enteredData['Currency_Code'] = get_currency_code($enteredData['Country']);
					$result = insert_event_profile_plus($enteredData);
				} else {
					$result = insert_event_profile_base($enteredData);
				}
				if (!$result) {
					log_entry(Logger::EVENTP,Logger::ERROR,0,$playerID,
						"Failed to create new event profile.");
					$errors = error_add($errors, "An error occurred while saving your new event.");
				} else {
					/** get ID of newly created event */
					$eventID = get_event_profile_id($enteredData['Event_Name']);
					if (check_value_is_number($eventID)) {
						/** if user is admin, then no need to add to the event role table */
						if (!$isAdmin) { 
							/** add non-admin user with a default role of owner */
							/** NOTE: teamID always set to 0 when managing event and owners */
							if(!insert_event_team_role($playerID, $eventID, $teamID, "Owner")) {
								log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
									"Failed to add new owner role while creating new event.");
								$errors = error_add($errors, "An error occurred while completing the setup
															for your new event.");
							}
						}
						/** insert entries in event role table for additional owners */
						if (check_value_is_set($enteredData['Owner_List'])) {
							$thisOwner = explode(",", $enteredData['Owner_List']);
							foreach ($thisOwner as $thisOwnerVal) {
								if (!insert_event_team_role($thisOwnerVal, $eventID, $teamID, "Owner")){
									log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
										"Failed to add additional owner role(s) while creating new event.");
									$errors = error_add($errors, "An error occurred while saving 
															additional owners to your new event.");
								}
							}
						}
						if (!empty($errors)) {
							/** in case errors encountered while adding records to the EVENT_ROLE 
							 * table, archive the new event to back it out of the EVENT_PROFILE table - 
							 * Both inserts must work */
							update_event_profile_archive($eventID);
						}
					} else {
						log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
							"Event ID of newly created event does not exist or is not a number.");
						$errors = error_add($errors, "A problem was detected with your new event's ID. 
													Technical support has been notified.");
					}
				}
			} else {
				$errors = error_add($errors, "The event name entered already exists.  
											Please enter a different event name.");
			}
			if (!empty($errors)) {
				$assignees = getAssigneesAll($eventID, $teamID);
				build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
			} else {
				log_entry(Logger::EVENTP,Logger::INFO,$eventID,$playerID,"New event successfully created.");
				set_session_event_mgmt($eventID);
				set_session_event_name($enteredData['Event_Name']);
				/** update list of events in MyEvents session variable */
				set_session_event_list($arrEvents = get_events_for_player($playerID));
				if (!$isAdmin) {
					set_session_player_role("Owner");
				}
				/** if everything saved ok w/no errors, go to activate page to give user the option to donate */
                activate_event($eventID,$eventName,$playerID);
				redirect_page("event_activate.php");
			}
		} else {
			build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
		} 
	} else if ($action == "eventCancel") { /** action from event profile page */
		redirect_page("index.php"); 
	} else if (check_value_is_set($eventID) and is_numeric($eventID)) {
		if ($action == "eventView") {
			view_event_page($errors, $eventID, $teamID);
		} else if ($action == "eventEdit") {
			if ($isOwner or $isAdmin) {
				$enteredData = get_event_profile($eventID);
				/** convert registration date values from GMT to local */ 
				$enteredData = convert_reg_dates_to_local($enteredData);
				$assignees = getAssigneesAll($eventID, $teamID);
				build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
			} else {
				log_entry(Logger::EVENTP,Logger::WARN,$eventID,$playerID,
					"Non-authorized player tried to peek at Event Mgmt Edit page.");
				$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
				view_event_page($errors, $eventID, $teamID);
			}
		} else if ($action == "eventSave" or $action == "eventActivateEvent") { /** action from detail page */
			if ($isOwner or $isAdmin) {
				$enteredData = get_data_entered($_POST);
				$errors = validate_data($enteredData);
				if (empty($errors)){
					/** when updating, compute the return URL for the event */
					//$enteredData['Payment_Return_URL'] = get_site_URL()."/event_activate.php";
					/** determine the currency code based on the country selected */
					$enteredData['Currency_Code'] = get_currency_code($enteredData['Country']);
					/** update entry in event profile table */
					if ($enteredData['Event_Type'] == "1" or $enteredData['Event_Type'] == "3") {
						$result = update_event_profile_plus($eventID, $enteredData);
					} else {
						$result = update_event_profile_base($eventID, $enteredData);
					}
					if (!$result) {
						log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,"Failed to save event profile.");
						$errors = error_add($errors, "An error occurred while updating the event profile.");
					}
					
					/** delete and insert entries in event role table for owners */
					if (!delete_event_team_role ($eventID, $teamID, "Owner")) {
						log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
							"Failed to delete event owner roles while updating event profile.");
						$errors = error_add($errors, "An error occurred while refreshing event owner roles.");
					}
					/** if user is admin, then no need to add to the event role table */
					if (!$isAdmin) { 
						/** add non-admin user with a default role of owner */
						if(!insert_event_team_role($playerID, $eventID, $teamID, "Owner")) {
							log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
								"Failed to add new owner role while updating event profile.");
							$errors = error_add($errors, "An error occurred while completing the setup for 	
													 this event.");
						}
					}
					if (check_value_is_set($enteredData['Owner_List'])) {
						$thisOwner = explode(",", $enteredData['Owner_List']);
						foreach ($thisOwner as $thisOwnerVal) {
							if (!insert_event_team_role($thisOwnerVal, $eventID, $teamID, "Owner")) {
								log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
										"Failed to add additional owner role(s) while updating event profile.");
								$errors = error_add($errors, "An error occurred while adding event owners.");
							}
						}
					}
					
					if (!empty($errors)) {
						$assignees = getAssigneesAll($eventID, $teamID);
						build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
					} else {
						if ($action == "eventActivateEvent") {
                            activate_event($eventID,$eventName,$playerID);
							redirect_page("event_activate.php");
						} else {						
							$action = "eventView";
							$assignees = getAssigneesAssigned($eventID, $teamID);
							build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
						}
					}
				} else {
					$assignees = getAssigneesAll($eventID, $teamID);
					build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
				}
			} else {
				log_entry(Logger::EVENTP,Logger::WARN,$eventID,$playerID,
					"Non-authorized player tried to peek at Event Mgmt Edit page.");
				$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
				view_event_page($errors, $eventID, $teamID);
			}	
		} else if ($action == "eventDelete") { /** action from summary page */
			if ($isOwner or $isAdmin) {
				/** delete all entries from tables that support event: event_profile, event_role, roster, and							event_home_page */ 
				if (!update_archive_event_profile($eventID)) {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Failed to archive event profile.");
					$errors = error_add($errors, "An error occurred while deleting event profile.");
				}
				if (!update_archive_event_home_page($eventID)) {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Failed to archive event home page.");
					$errors = error_add($errors, "An error occurred while deleting event home page.");
				}
				if (!update_archive_event_role($eventID)) {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Failed to archive event roles.");
					$errors = error_add($errors, "An error occurred while deleting event owner(s)/captain(s).");
				}
				if (!update_archive_roster($eventID)) {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Failed to archive event roster.");
					$errors = error_add($errors, "An error occurred while deleting event roster.");
				}
				if (!update_archive_team($eventID)) {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Failed to archive event team(s).");
					$errors = error_add($errors, "An error occurred while deleting event team(s).");
				}
				if (!update_archive_wait_list($eventID)) {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Failed to archive event wait list.");
					$errors = error_add($errors, "An error occurred while deleting event wait list.");
				}
				if (!empty($errors)) {
					$action = "eventView";
					build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
				} else {
					log_entry(Logger::EVENTP,Logger::INFO,$eventID,$playerID,"Event successfully archived.");
					clear_selected_event();
					/** update list of events in MyEvents session variable */
					set_session_event_list($arrEvents = get_events_for_player($playerID));
					redirect_page("event_select.php");
				} 
			} else {
				log_entry(Logger::EVENTP,Logger::WARN,$eventID,$playerID,
					"Non-authorized player tried to peek at Event Mgmt Edit page.");
				$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
				view_event_page($errors, $eventID, $teamID);
			}
		} else if ($action == "eventActivate") {
			activate_event($eventID,$eventName,$playerID);
            redirect_page("event_activate.php");
		}
	} else {
		clear_selected_event();
		redirect_page("index.php");
	}
} else {
	display_non_authorization();
}

function activate_event($eventID,$eventName,$playerID) {
   	if (!update_event_profile_as_paid($eventID)) {
		log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,"Failed to update event profile payment status during activation.");
    } else {
        log_entry(Logger::EVENTP,Logger::INFO,$eventID,$playerID,"Event activated.");
        $subject = "Event Activation! >> ".$eventName;
        $message = "The ".$eventName." event was activated.";
        if (!sendEmailGeneral(EMAIL_SUPPORT_ADDRESS,"N",$subject,$message)) {
            log_entry(Logger::EVENTP,Logger::WARN,0,$playerID,"An error occurred while sending event activate email to MU.");
        }
	}
    return;
}

function view_event_page($errors, $eventID, $teamID) { 
	$action = "eventView";
	$enteredData = get_event_profile($eventID);
	/** convert registration date values from GMT to local */ 
	$enteredData = convert_reg_dates_to_local_people($enteredData);				
	$assignees = getAssigneesAssigned($eventID, $teamID);
	build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action);
}

function convert_reg_dates_to_local($enteredData) {
	$enteredData['Reg_Begin'] = 
		convert_time_gmt_to_local($enteredData['Timezone_ID'],$enteredData['Reg_Begin']);
	$enteredData['Reg_End'] = 
		convert_time_gmt_to_local($enteredData['Timezone_ID'],$enteredData['Reg_End']);
	return $enteredData;
}

function convert_reg_dates_to_local_people($enteredData) {
	$enteredData['Reg_Begin'] = 
		convert_time_gmt_to_local_people($enteredData['Timezone_ID'],$enteredData['Reg_Begin']);
	$enteredData['Reg_End'] = 
		convert_time_gmt_to_local_people($enteredData['Timezone_ID'],$enteredData['Reg_End']);
	$enteredData['Event_Begin'] = 
		convert_date_gmt_to_local_people($enteredData['Timezone_ID'],$enteredData['Event_Begin']);
	$enteredData['Event_End'] = 
		convert_date_gmt_to_local_people($enteredData['Timezone_ID'],$enteredData['Event_End']);
	return $enteredData;
}

function getAssigneesUnassigned($eventID, $teamID) {
	$assignees = array();
	$assignees['ownersUnassigned'] = get_player_role_unassigned($eventID, $teamID, "Owner");
	return $assignees;	
}

function getAssigneesAssigned($eventID, $teamID) {
	$assignees = array();
	$assignees['ownersAssigned'] = get_player_role_assigned($eventID, $teamID, "Owner");
	return $assignees;	
}

function getAssigneesAll($eventID, $teamID) {
	return array_merge(getAssigneesUnassigned($eventID, $teamID), getAssigneesAssigned($eventID, $teamID));	
}

function validate_data($enteredData) {
	$errors1 = array();
	$errors2 = array();
	$errors3 = array();
	$errors4 = array();
	/** check #1: base data */
	$errors1 = validate("eventEditBase", $enteredData);
	/** check #2: plus data (includes all tourney/league fields) */
	if ($enteredData['Event_Type'] == "1" or $enteredData['Event_Type'] == "3") {
		$errors2 = validate("eventEditPlus", $enteredData);
		/** check #3: check data */
		if (check_value_is_set($enteredData['Payment_Type'])) {
			$temp = strstr(is_string($enteredData['Payment_Type']) ? 
				$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "2");
			if ($temp !== false) {
				$errors3 = validate("eventEditCheck", $enteredData);
			}
		}
		/** check #4: paypal data */
		if (check_value_is_set($enteredData['Payment_Type'])) {
			$temp = strstr(is_string($enteredData['Payment_Type']) ? 
				$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "3");
			if ($temp !== false) {
				$errors4 = validate("eventEditPayPal", $enteredData);
			}
		}
	}
	return array_merge($errors1, $errors2, $errors3, $errors4);	
}

function build_event_mgmt_detail_page($errors, $enteredData, $assignees, $action) {
	display_wrappers();
?>
	<div id="content_wrapper">
	<?php
	if ($action == "eventCreate" or $action == "eventCreateEvent") {
		build_event_navbar("create");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		editForm($errors, $enteredData, $assignees, $action);
		?>
		</div>
		<?php
	} else if ($action == "eventEdit" or $action == "eventSave" or $action == "eventCheckoutEvent") {
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		editForm($errors, $enteredData, $assignees, $action);
		?>
		</div>
		<?php
	} else {
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		displayForm($enteredData, $assignees, $action);
		?>
		</div>
		<?php
	}
	?>
	</div>
<?php
}
	
function editForm($errors, $enteredData, $assignees, $action) {
	display_errors($errors);

	/** set variable to control whether tournament/league field is displayed or not */ 
	$tlIdStyleText = "id='tl' style='display:none;'"; /** default */
	/** set variable to control whether check field is displayed or not */ 
	$chkIdStyleText = "id='chk' style='display:none;'"; /** default */
	/** set variable to control whether paypal field is displayed or not */
	$ppIdStyleText = "id='paypal' style='display:none;'"; /** default */

	if ($enteredData['Event_Type'] == "1" or $enteredData['Event_Type'] == "3") {
		$tlIdStyleText = "id='tl' style='display:;'";

		if (check_value_is_set($enteredData['Payment_Type'])) {
			$temp = strstr(is_string($enteredData['Payment_Type']) ? 
				$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "2");
			if ($temp !== false) {
				$chkIdStyleText = "id='chk' style='display:;'";
			}

			$temp = strstr(is_string($enteredData['Payment_Type']) ? 
				$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "3");
			if ($temp !== false) {
				$ppIdStyleText = "id='paypal' style='display:;'";
			}
		}
	}

	$payStatus = "N"; // by default
	if (isset($enteredData['Payment_Status'])) {
		$payStatus = $enteredData['Payment_Status'];
	}
	?>
	<script language="JavaScript" type="text/javascript">
	function submitThis(){
		var fields = ['selectedOwners'];
		submitForm(fields);	
	} 
	</script>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
		<form method="post" id="selectionForm" name="selectionForm" action="event_mgmt.php" 
			onsubmit="return submitThis();return false;" class="box">
			<input type="hidden" name="Owner_List" value="">
			<input type="hidden" name="Payment_Status" value="<?php echo $enteredData['Payment_Status'] ?>">
			<?php
			if ($payStatus == "N") {
			?> 
				<input type="hidden" name="Publish_Event" value="<?php echo $enteredData['Publish_Event'] ?>">
			<?php
			}
			?>
			<table class="default">
				<tr>
					<td></td>
					<td colspan="2"><span class="smGD">* required entry</span></td>
				</tr>
				<tr>
					<td class="titleRR">Publish Event?</td>
					<?php
					if ($payStatus == "N") {
					?>
						<td class="entryRL" colspan="2">
						<?php echo (stripslashes($enteredData['Publish_Event']) == "Y") ? "Yes" : "No";	?>
						</td>
					<?php
					} else {
					?>	
						<td class="entryRL" colspan="2">
							<input type="radio" 
								name="Publish_Event" 
								value="Y"
								<?php if (strstr($enteredData['Publish_Event'],"Y")) { 
									echo "checked"; } ?>>Yes
							<input type="radio" 
								name="Publish_Event" 
								value="N"
								<?php if (strstr($enteredData['Publish_Event'],"N") or 
											!check_value_is_set($enteredData['Publish_Event'])) { 
										echo "checked"; } ?>>No
							</td>					
					<?php
					}
					?>
				</tr>
				<?php 
				if (isset($errors['Publish_Event'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Publish_Event'] ?></td></tr>
				<?php			
				}
				?>
				<tr>
					<td class="titleRR">Event Type*</td>
					<td class="entryRL" colspan="2">
						<select	name="Event_Type" size="1" id="dd" onchange="switchDisplayDriver('dd')" 
							tabindex="1">
							<option value="">Please select...
							<option value="1" 
								<?php if ($enteredData['Event_Type'] == "1") { echo "selected"; } ?>
								>League</option>
					<!--	<option value="2" 
								<?php if ($enteredData['Event_Type'] == "2") { echo "selected"; } ?>
								>Pickup</option> -->
							<option value="3" 
								<?php if ($enteredData['Event_Type'] == "3") { echo "selected"; } ?>
								>Hat Tournament</option>
						</select>
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_Type'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Event_Type'] ?></td></tr>
				<?php			
				}
				?>			
				<tr>
					<td class="titleRR">Event Name*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text"
								name="Event_Name"
								value="<?php echo stripslashes($enteredData['Event_Name']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="2">
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_Name'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Event_Name'] ?></td></tr>
				<?php
				}
				?>			
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Organization Sponsor*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text"
								name="Org_Sponsor"
								value="<?php echo $enteredData['Org_Sponsor'] ?>" 
								size="30" 
								maxlength="50" 
								tabindex="3">
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the name of the person, group or organization who is hosting this event." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Org_Sponsor'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Org_Sponsor'] ?></td></tr>
				<?php			
				}
				?>
				<tr>
					<td class="titleRR">Country where event<br/>
						will be held*</td>
					<td class="entryRL">
						<select name="Country" size="1" tabindex="4">
							<option value="">Please select</option>
						<?php	
						$countriesResult = get_countries();
						while ($row=mysql_fetch_array($countriesResult)) {
							$countryCode = $row["Code"];
							$countryName = $row["Name"];
							($enteredData['Country'] == $countryCode) ? $selected="selected" : $selected="";
	 						echo "<option $selected value=$countryCode>$countryName</option>";
						} 
						?>
						</select>
					</td>
				</tr>
				<?php 
				if (isset($errors['Country'])) {
				?>
					<tr><td></td><td class="error"><?php echo $errors['Country'] ?></td></tr>
				<?php 
				} 
				?>
				<tr>
					<td class="titleRR">State where event<br/>
						will be held*</td>
					<td class="entryRL">
						<select name="State_Prov" size="1" tabindex="5">
							<option value="">Please select</option>
							<?php	
							$statesResult = get_states();
							while ($row=mysql_fetch_array($statesResult)) {
								$stateCode = $row["Code"];
								$stateName = $row["Name"];
								($enteredData['State_Prov'] == $stateCode) ? $selected="selected" : $selected="";
		 						echo "<option $selected value=$stateCode>$stateName</option>";
							} 
							?>
						</select>
					</td>
				</tr>
				<?php 
				if (isset($errors['State_Prov'])) {
				?>
					<tr><td></td><td class="error"><?php echo $errors['State_Prov'] ?></td></tr>
				<?php 
				} 
				?>
				<tr>
					<td class="titleRR">City where event<br/>
						will be held*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text"
								name="City"
								value="<?php echo stripslashes($enteredData['City']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="6">
					</td>
				</tr>
				<?php 
				if (isset($errors['City'])) {
				?>
					<tr><td></td><td class="error"><?php echo $errors['City'] ?></td></tr>
				<?php 
				} 
				?>
				<tr>
					<td class="titleRR">Field Location*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text" 
								name="Location"
								value="<?php echo stripslashes($enteredData['Location']) ?>" 
								size="30" 
								maxlength="255" 
								tabindex="7">
					</td>
				</tr>
				<?php 
				if (isset($errors['Location'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Location'] ?></td></tr>				
				<?php			
				}
				?>
				<tr>
					<td class="titleRR">Field Location URL</td>
					<td class="entryRL" colspan="2">
						<input 	type="text" 
								name="Location_Link"
								value="<?php echo stripslashes($enteredData['Location_Link']) ?>" 
								size="30" 
								maxlength="255" 
								tabindex="8">
					</td>
				</tr>
				<?php 
				if (isset($errors['Location_Link'])) {
				?>
					<tr><td></td><td class="error" colspan="2">
						<?php echo $errors['Location_Link'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Registration Start*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Reg_Begin"
								id="dt1"
								value= "<?php echo $enteredData['Reg_Begin'] ?>" 
								size="30" 
								maxlength="25" 
								tabindex="9">
						<a href="javascript:NewCssCal('dt1','yyyyMMdd','Arrow','true','24','true')">
							<img src="images/cal.gif" border="0" width="16" height="16"	style="cursor: pointer;">
						</a>
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" 
								alt="Enter the date when players can start registering for your event.  This is typically set several weeks prior to the start of the event." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Reg_Begin'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Reg_Begin'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Registration End*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Reg_End"
								id="dt2"
								value= "<?php echo $enteredData['Reg_End'] ?>"  
								size="30" 
								maxlength="25" 
								tabindex="10">
						<a href="javascript:NewCssCal('dt2','yyyyMMdd','Arrow','true','24','true')">
							<img src="images/cal.gif" border="0" width="16" height="16"	style="cursor: pointer;"> 
						</a>
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the date when players can no longer register for your event.  If this is a league, the registration end date can be set after play starts to ensure that you give late comers a chance to sign up." />
						</a>							
					</td>
				</tr>
				<?php 
				if (isset($errors['Reg_End'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Reg_End'] ?></td></tr>
				<?php			
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Time Zone*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<select name="Timezone_ID" tabindex="11">
							<option value="">Please select</option>
						<?php	
						$tzResult = get_timezone_names();
						while ($row=mysql_fetch_array($tzResult)) {
							$tzID = $row["Timezone_ID"];
							$tzName = $row["Timezone_Name"];
							($enteredData['Timezone_ID'] == $tzID) ? $selected="selected" : $selected="";
	 						echo "<option $selected value=\"$tzID\">$tzName</option>";
						} 
						?>
						</select> 
					</td>
				</tr>
				<?php 
				if (isset($errors['Timezone_ID'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Timezone_ID'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Event Start Day*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Event_Begin"
								id="dt3"
								value= "<?php echo $enteredData['Event_Begin'] ?>" 
								size="30" 
								maxlength="25" 
								tabindex="12">
						<a href="javascript:NewCssCal('dt3','yyyyMMdd','Arrow')">
							<img src="images/cal.gif" border="0" width="16" height="16"	style="cursor: pointer;">
						</a>
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the date when play starts." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_Begin'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Event_Begin'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Event End Day*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Event_End"
								id="dt4"
								value= "<?php echo $enteredData['Event_End'] ?>" 
								size="30" 
								maxlength="25" 
								tabindex="13">
						<a href="javascript:NewCssCal('dt4','yyyyMMdd','Arrow')">
							<img src="images/cal.gif" border="0" width="16" height="16"	style="cursor: pointer;"> 
						</a>
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the date when play ends." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_End'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Event_End'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR">Game Time*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text" 
								name="Event_Time"
								value="<?php echo $enteredData['Event_Time'] ?>" 
								size="30" 
								maxlength="50" 
								tabindex="14">
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the hours of the day when play occurs." />
						</a>	
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_Time'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Event_Time'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR">Day(s) of Week*</td>
					<td class="entryRL" colspan="2">
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="1" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ? 
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "1");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Sunday<br/>
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="2" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ? 
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "2");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Monday<br/>
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="3" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ? 
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "3");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Tuesday<br/>
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="4" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ? 
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "4");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Wednesday<br/>
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="5" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ?	
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "5");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Thursday<br/>
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="6" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ?	
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "6");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Friday<br/>
						<input type="checkbox" 
							name="Days_Of_Week[]"
							value="7" 
							tabindex="15"
							<?php 
							if(isset($enteredData['Days_Of_Week'])) {
								if (check_value_is_set($enteredData['Days_Of_Week'])) {
									$temp = strstr(is_string($enteredData['Days_Of_Week']) ?	
									$enteredData['Days_Of_Week'] : implode(',',$enteredData['Days_Of_Week']), "7");
									if ($temp !== false) {
										echo "checked";	}}} ?>>Saturday<br/>
					</td>
				</tr>
				<?php 
				if (isset($errors['Days_Of_Week'])) {
				?>
					<tr><td></td><td class="error" colspan="2"><?php echo $errors['Days_Of_Week'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Number of Teams*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Number_Of_Teams"
								value="<?php echo $enteredData['Number_Of_Teams'] ?>" 
								size="30" 
								maxlength="3" 
								tabindex="16">
					</td>
				</tr>
				<?php 
				if (isset($errors['Number_Of_Teams'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Number_Of_Teams'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Players per Team*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Players_Per_Team"
								value="<?php echo $enteredData['Players_Per_Team'] ?>" 
								size="30" 
								maxlength="10" 
								tabindex="17">
					</td>
				</tr>
				<?php 
				if (isset($errors['Players_Per_Team'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Players_Per_Team'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Team Gender Ratio (M:F)</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Team_Ratio"
								value="<?php echo stripslashes($enteredData['Team_Ratio']) ?>" 
								size="30" 
								maxlength="25" 
								tabindex="18">
					</td>
				</tr>
				<?php 
				if (isset($errors['Team_Ratio'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Team_Ratio'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Max Male Players*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Limit_Men"
								value="<?php echo $enteredData['Limit_Men'] ?>" 
								size="30" 
								maxlength="5" 
								tabindex="19">
					</td>
				</tr>
				<?php 
				if (isset($errors['Limit_Men'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Limit_Men'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Max Female Players*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Limit_Women"
								value="<?php echo $enteredData['Limit_Women'] ?>" 
								size="30" 
								maxlength="5" 
								tabindex="20">
					</td>
				</tr>
				<?php 
				if (isset($errors['Limit_Women'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Limit_Women'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						UPA Event?*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input type="radio" 
							name="UPA_Event" 
							tabindex="21" 
							value="Y"
							<?php 
							if(isset($enteredData['UPA_Event'])) {
								if (strstr($enteredData['UPA_Event'],"Y")) { 
									print "checked"; }} ?>>Yes
						<input type="radio" 
							name="UPA_Event" 
							tabindex="21" 
							value="N"
							<?php 
							if(isset($enteredData['UPA_Event'])) {
								if (strstr($enteredData['UPA_Event'],"N")) { 
									print "checked"; }} ?>>No
					</td>
				</tr>
				<?php 
				if (isset($errors['UPA_Event'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['UPA_Event'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td valign="top" class="titleRR" <?php echo $tlIdStyleText; ?>>
						Event Fee*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Event_Fee"
								value="<?php echo number_format($enteredData['Event_Fee'], 2, '.', '') ?>" 
								size="30" 
								maxlength="5" 
								tabindex="22">
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_Fee'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Event_Fee'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td valign="top" class="titleRR" <?php echo $tlIdStyleText; ?>>
						Event T-Shirt Fee*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Event_TShirt_Fee"
								value="<?php echo number_format($enteredData['Event_TShirt_Fee'], 2, '.', '') ?>" 
								size="30" 
								maxlength="5" 
								tabindex="23">
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_TShirt_Fee'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Event_TShirt_Fee'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td valign="top" class="titleRR" <?php echo $tlIdStyleText; ?>>
						Event Disc Fee*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Event_Disc_Fee"
								value="<?php echo number_format($enteredData['Event_Disc_Fee'], 2, '.', '') ?>" 
								size="30" 
								maxlength="5" 
								tabindex="24">
					</td>
				</tr>
				<?php 
				if (isset($errors['Event_Disc_Fee'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Event_Disc_Fee'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Payment Deadline*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Deadline"
								id="dt5"
								value=
								"<?php echo $enteredData['Payment_Deadline'] ?>" 
								size="30" 
								maxlength="25" 
								tabindex="25">
						<a href="javascript:NewCssCal('dt5','yyyyMMdd','Arrow')">
							<img src="images/cal.gif" border="0" width="16" height="16"	style="cursor: pointer;"> 
						</a>
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the date by when you want players to pay you for participating in your event." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Deadline'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Deadline'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>
						Payment Type*</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?> colspan="2">
						<input type="checkbox" 
							name="Payment_Type[]"
							value="1"
							tabindex="26"
							<?php 
							if(isset($enteredData['Payment_Type'])) {
								if (check_value_is_set($enteredData['Payment_Type'])) {
									$temp = strstr(is_string($enteredData['Payment_Type']) ? 
									$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "1");
									if ($temp !== false) {
										print "checked"; }}} ?>>Cash<br/>
						<input type="checkbox" 
							name="Payment_Type[]"
							value="2" 
							onclick="switchDisplay('chk',this)"
							tabindex="26"
							<?php 
							if(isset($enteredData['Payment_Type'])) {
								if (check_value_is_set($enteredData['Payment_Type'])) {
									$temp = strstr(is_string($enteredData['Payment_Type']) ? 
									$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "2");
									if ($temp !== false) {
										print "checked"; }}} ?>>Check<br/>
						<input type="checkbox" 
							name="Payment_Type[]"
							value="3" 
							onclick="switchDisplay('paypal',this)"
							tabindex="26"
							<?php 
							if(isset($enteredData['Payment_Type'])) {
								if (check_value_is_set($enteredData['Payment_Type'])) {
									$temp = strstr(is_string($enteredData['Payment_Type']) ? 
									$enteredData['Payment_Type'] : implode(',',$enteredData['Payment_Type']), "3");
									if ($temp !== false) {
										print "checked"; }}} ?>>PayPal<br/>
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Type'])) {
				?>
					<tr><td <?php echo $tlIdStyleText; ?>></td>
					<td class="error" <?php echo $tlIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Type'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $ppIdStyleText; ?>>
						PayPal Account Email*</td>
					<td class="entryRL" <?php echo $ppIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Account"
								value="<?php echo stripslashes($enteredData['Payment_Account'])?>" 
								size="30" 
								maxlength="50"
								tabindex="27">
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter the email address for your Paypal account.  This account will be used to receive online payment from players who register for your event. Please ensure that your account email address has a Confirmed status with Paypal." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Account'])) {
				?>
					<tr><td <?php echo $ppIdStyleText; ?>></td>
					<td class="error" <?php echo $ppIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Account'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $ppIdStyleText; ?>>
						PayPal Item Name*</td>
					<td class="entryRL" <?php echo $ppIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Item_Name"
								value="<?php echo stripslashes($enteredData['Payment_Item_Name']) ?>" 
								size="30" 
								maxlength="50"
								tabindex="28">
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Enter a description for this event. This description will appear on the payee's Paypal statement." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Item_Name'])) {
				?>
					<tr><td <?php echo $ppIdStyleText; ?>></td>
					<td class="error" <?php echo $ppIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Item_Name'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>
						Check Payee Name*</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Chk_Payee"
								value="<?php echo stripslashes($enteredData['Payment_Chk_Payee']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="29">
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Chk_Payee'])) {
				?>
					<tr><td <?php echo $chkIdStyleText; ?>></td>
					<td class="error" <?php echo $chkIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Chk_Payee'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>
						Check Payee Address*</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Chk_Address"
								value="<?php echo stripslashes($enteredData['Payment_Chk_Address']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="30">
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Chk_Address'])) {
				?>
					<tr><td <?php echo $chkIdStyleText; ?>></td>
					<td class="error" <?php echo $chkIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Chk_Address'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>
						Check Payee City*</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Chk_City"
								value="<?php echo stripslashes($enteredData['Payment_Chk_City'])?>" 
								size="30" 
								maxlength="50" 
								tabindex="31">
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Chk_City'])) {
				?>
					<tr><td <?php echo $chkIdStyleText; ?>></td>
					<td class="error" <?php echo $chkIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Chk_City'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>
						Check Payee State*</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?> colspan="2">
						<select name="Payment_Chk_State" size="1" tabindex="32">
							<option value="">Please select</option>
						<?php	
						$statesResult = get_states();
						$thisState = stripslashes($enteredData['Payment_Chk_State']);
						while ($row=mysql_fetch_array($statesResult)) {
							$stateCode = $row["Code"];
							($thisState == $stateCode) ? 
								$selected="selected" : $selected="";
	 						echo "<option $selected value=$stateCode>$stateCode</option>";
						} 
						?>
						</select>
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Chk_State'])) {
				?>
					<tr><td <?php echo $chkIdStyleText; ?>></td>
					<td class="error" <?php echo $chkIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Chk_State'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>
						Check Payee Zip Code*</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?> colspan="2">
						<input 	type="text" 
								name="Payment_Chk_Zip"
								value="<?php echo stripslashes($enteredData['Payment_Chk_Zip']) ?>" 
								size="30" 
								maxlength="5" 
								tabindex="33">
					</td>
				</tr>
				<?php 
				if (isset($errors['Payment_Chk_Zip'])) {
				?>
					<tr><td <?php echo $chkIdStyleText; ?>></td>
					<td class="error" <?php echo $chkIdStyleText; ?> colspan="2">
						<?php echo $errors['Payment_Chk_Zip'] ?></td></tr>
				<?php			
				}
				?>
				<tr>
					<td class="titleRR">Contact Name*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text" 
								name="Contact_Name"
								value="<?php echo stripslashes($enteredData['Contact_Name']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="34">
					</td>
				</tr>
				<?php 
				if (isset($errors['Contact_Name'])) {
				?>
					<tr><td></td><td class="error" colspan="2">
						<?php echo $errors['Contact_Name'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR">Contact Email*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text" 
								name="Contact_Email"
								value="<?php echo stripslashes($enteredData['Contact_Email']) ?>" 
								size="30" 
								maxlength="50" 
								tabindex="35">
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Note that this email address will be visible to all who view your event." />
						</a>
					</td>
				</tr>
				<?php 
				if (isset($errors['Contact_Email'])) {
				?>
					<tr><td></td><td class="error" colspan="2">
						<?php echo $errors['Contact_Email'] ?></td></tr>
				<?php
				}
				?>
	
				<tr>
					<td class="titleRR">Contact Phone #*</td>
					<td class="entryRL" colspan="2">
						<input 	type="text" 
								name="Contact_Phone"
								value="<?php echo stripslashes($enteredData['Contact_Phone']) ?>" 
								size="30" 
								maxlength="13" 
								onkeydown="javascript:backspacerDOWN(this,event);" 
								onkeyup="javascript:backspacerUP(this,event);"
								tabindex="36">
					</td>
				</tr>
				<?php 
				if (isset($errors['Contact_Phone'])) {
				?>
					<tr><td></td><td class="error" colspan="2">
						<?php echo $errors['Contact_Phone'] ?></td></tr>
				<?php
				}
				?>
				<tr>
					<td class="titleRR">Publish Phone #?*</td>
					<td class="entryRL" colspan="2">
						<input type="radio" 
							name="Publish_Phone" 
							tabindex="37" 
							value="Y"
							<?php if (strstr($enteredData['Publish_Phone'],"Y")) { 
								echo "checked"; } ?>>Yes
						<input type="radio" 
							name="Publish_Phone" 
							tabindex="37" 
							value="N"
							<?php if (strstr($enteredData['Publish_Phone'],"N") or 
										!check_value_is_set($enteredData['Publish_Phone'])) { 
									echo "checked"; } ?>>No
					</td>
				</tr>
				<?php 
				if (isset($errors['Publish_Phone'])) {
				?>
					<tr><td></td><td class="error" colspan="2">
						<?php echo $errors['Publish_Phone'] ?></td></tr>
				<?php
				}
			
				$numOwnersUnassignedResults = 0;
				$numOwnersAssignedResults = 0;
	
				if (!empty($assignees['ownersUnassigned'])) {
					$numOwnersUnassignedResults = mysql_num_rows($assignees['ownersUnassigned']);
				}
				if (!empty($assignees['ownersAssigned'])) {
					$numOwnersAssignedResults = mysql_num_rows($assignees['ownersAssigned']);
				}
	
				if ($numOwnersUnassignedResults == 0){
				?>
					<tr>
						<td colspan="3">
						<p>Currently, no players have registered for this event.  Once players have registered, 
						you will be able to assign additional organizers.</p> 
						</td>
					</tr>
				<?php
				} else {
				?>
					<tr>
						<td class="titleRL" colspan="3">
							Optionally, you may select up to two additional organizer(s) for your event
						</td>
					</tr>
					<tr>
						<td>
							<select name="owners1" multiple size="10" 
								ondblclick="moveSelectedOptions(this.form['owners1'],this.form['owners2']);">
								<?php	
								if ($numOwnersUnassignedResults >  0) {
									while ($row=mysql_fetch_array($assignees['ownersUnassigned'])) {
										$playerID = $row['Player_ID'];
										$name = $row['Last_Name'].", ".$row['First_Name'];
										echo "<option value=$playerID>$name</option>";
									}
								}
								?>
							</select>
						</td>
						<td colspan="2">
							<table class="default">
								<tr>
								<td valign="top">
									<input type="button" name="right" value="&gt;&gt;" 
										onclick="moveSelectedOptions(this.form['owners1'],this.form['owners2']);">
									<br/><br/>
									<input type="button" name="left" value="&lt;&lt;" 
										onclick="moveSelectedOptions(this.form['owners2'],this.form['owners1']);">
									<br/><br/>
									<input type="button" name="left" value="All &lt;&lt;" 
										onclick="moveAllOptions(this.form['owners2'],this.form['owners1']);">
								</td>
								<td>
									<select id="selectedOwners" name="owners2" multiple size="10" 
									ondblclick="moveSelectedOptions(this.form['owners2'],this.form['owners1']);">
										<?php	
										if ($numOwnersAssignedResults >  0) {
											while ($row=mysql_fetch_array($assignees['ownersAssigned'])) {
												$playerID = $row['Player_ID'];
												$name = $row['Last_Name'].", ".$row['First_Name'];
												/** only display other owners, not owner who is editing this event*/
												if ($playerID <> get_session_player_id()) {
													echo "<option value=$playerID>$name</option>";
												}
											}
										}
										?>
									</select>
								</td>
								</tr>
								<?php 
								if (isset($errors['Owner_List'])) {
								?>
									<tr><td class="error" colspan="2">
										<?php echo $errors['Owner_List'] ?></td></tr>
								<?php
								}
								?>
							</table>
						</td>
					</tr>
				<?php
				}
	
				$buttonLabel1 = "";
				$buttonLabel2 = "";
				$buttonLabel3 = "";
				
				if ($action == "eventCreate" or $action == "eventCreateEvent") {
					$buttonLabel1 = "Create Event";
					/** note the spaces... done to differentiate from Cancel below */
					$buttonLabel2 = " Cancel ";
				} else {
					if ($payStatus == "N") {
						$buttonLabel3 = "Activate Event";	
					}
					$buttonLabel1 = "Save Event";
					$buttonLabel2 = "Cancel";
				}
				?>
				<tr>
					<td colspan="3" class="dispRC">
						<?php
						if ($payStatus == "N" and !($action == "eventCreate" or $action == "eventCreateEvent")) {
						?>
							<button type="submit" value="<?php echo $buttonLabel3 ?>" class="submitBtn" 
								name="ProcessAction">
								<span><?php echo $buttonLabel3 ?></span>
							</button>
							&nbsp;&nbsp;
						<?php
						}
						?>
						<button type="submit" value="<?php echo $buttonLabel1 ?>" class="submitBtn" 
							name="ProcessAction">
							<span><?php echo $buttonLabel1 ?></span>
						</button>
						&nbsp;&nbsp;
						<button type="submit" value="<?php echo $buttonLabel2 ?>" class="submitBtn" 
							name="ProcessAction">
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

function displayForm($enteredData, $assignees, $action) {
	/** set variable to control whether tournament/league field is displayed or not */ 
	$tlIdStyleText = "id='tl' style='display:none;'"; /** default */
	/** set variable to control whether check field is displayed or not */ 
	$chkIdStyleText = "id='chk' style='display:none;'"; /** default */
	/** set variable to control whether paypal field is displayed or not */
	$ppIdStyleText = "id='pp' style='display:none;'"; /** default */

	if ($enteredData['Event_Type'] == "1" or $enteredData['Event_Type'] == "3") {
		$tlIdStyleText = "id='tl' style='display:;'";

		$tempArr = (is_array($enteredData['Payment_Type'])) ? 
			$enteredData['Payment_Type'] : explode(",", $enteredData['Payment_Type']);

		foreach ($tempArr as &$thisVal) {
			if ($thisVal == "2"){ 
				$chkIdStyleText = "id='chk' style='display:;'";
				break;
			}
		}

		foreach ($tempArr as &$thisVal) {
			if ($thisVal == "3"){ 
				$ppIdStyleText = "id='pp' style='display:;'";
				break;
			}
		}
	}
	
	$payStatus = "N"; // by default
	if (isset($enteredData['Payment_Status'])) {
		$payStatus = $enteredData['Payment_Status'];
	}
	?>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
		<form method="post" name="selectionForm" action="event_mgmt.php">
			<input type="hidden" name="Action" value="">
			<input type="hidden" name="ID" value="">
			<table class="default">
				<?php
				if (check_owner_authorization() or check_admin_authorization()) {
				?>
					<tr>
						<td class="titleRR">Publish Event?</td>
						<td class="entryRL" colspan="2">
							<?php echo (stripslashes($enteredData['Publish_Event']) == "Y") ? "Yes" : "No";	?>
						</td>
					</tr>
				<?php
				}
				?>	
				<tr>
					<td class="titleRR">Event Type</td>
					<td class="entryRL">
					<?php 
					switch ($enteredData['Event_Type']){ 
						case "1":
						echo "League";
						break;
						case "2":
						echo "Pickup";
						break;
						case "3":
						echo "Hat Tournament";
						break;
					}
					?>
					</td>
				</tr>		
				<tr>
					<td class="titleRR">Event Name</td>
					<td class="entryRL">
					<?php 
					echo stripslashes($enteredData['Event_Name'])
					?> 
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Organization Sponsor</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Org_Sponsor']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR">Location</td>
					<td class="entryRL">
					<?php 
					$countriesResult = get_countries();
					$thisCountry = "";
					while ($row=mysql_fetch_array($countriesResult)) {
						if ($enteredData['Country'] == $row["Code"]) {
							$thisCountry = $row["Name"];	
							break;
						}
					} 
					
					$thisLocLink = (($enteredData['Location_Link']) <> "") ? "<br/><a href=\"".stripslashes($enteredData['Location_Link'])."\" target=\"_blank\">Link</a>" : "";

					echo stripslashes($enteredData['Location'])."  ".$thisLocLink."<br/>".$enteredData['City'].", ".$enteredData['State_Prov']."  - ".$thisCountry; 
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Registration Start</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Reg_Begin']);
					?> 
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Registration End</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Reg_End']);
					?> 
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Time Zone</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php	
					$tzResult = get_timezone_names();
					if($tzResult) {
						while ($row=mysql_fetch_array($tzResult)) {
							$tzID = $row["Timezone_ID"];
							if ($enteredData['Timezone_ID'] == $tzID) {
								echo $row["Timezone_Name"];	
								break;
							}
						}
					}
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Event Start Day</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Event_Begin']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Event End Day</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Event_End']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR">Game Time</td>
					<td class="entryRL">
					<?php 
					echo stripslashes($enteredData['Event_Time']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR">Day(s) of Week</td>
					<td class="entryRL">
					<?php
					
					$tempArr = (is_array($enteredData['Days_Of_Week'])) ? 
						$enteredData['Days_Of_Week'] : explode(",", $enteredData['Days_Of_Week']);
					foreach ($tempArr as &$thisVal) {
						switch ($thisVal){ 
							case "1":
							echo "Sunday<br/>";
							break;
							case "2":
							echo "Monday<br/>";
							break;
							case "3":
							echo "Tuesday<br/>";
							break;
							case "4":
							echo "Wednesday<br/>";
							break;
							case "5":
							echo "Thursday<br/>";
							break;
							case "6":
							echo "Friday<br/>";
							break;
							case "7":
							echo "Saturday";
							break;
						}
					}
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Number of Teams</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Number_Of_Teams']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Players per Team</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Players_Per_Team']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Team Gender Ratio (M:F)</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Team_Ratio']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Max Male Players</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Limit_Men']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Max Female Players</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Limit_Women']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>UPA Event?</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php
					if (strstr($enteredData['UPA_Event'],"Y")) {
						echo "Yes";
					} else if (strstr($enteredData['UPA_Event'],"N")) {
						echo "No";
					} 
					?>
					</td>
				</tr>
				<tr>
					<td valign="top" class="titleRR" <?php echo $tlIdStyleText; ?>>Event Fee</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo "\$".number_format($enteredData['Event_Fee'], 2, '.', '');
					?>
					</td>
				</tr>
				<tr>
					<td valign="top" class="titleRR" <?php echo $tlIdStyleText; ?>>Event T-Shirt Fee</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo "\$".number_format($enteredData['Event_TShirt_Fee'], 2, '.', '');
					?>
					</td>
				</tr>
				<tr>
					<td valign="top" class="titleRR" <?php echo $tlIdStyleText; ?>>Event Disc Fee</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					echo "\$".number_format($enteredData['Event_Disc_Fee'], 2, '.', '');
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Payment Deadline</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					if (IS_LOCAL) {
						echo strftime("%b %d %Y", strtotime(stripslashes($enteredData['Payment_Deadline'])));	
					} else {
						echo strftime("%b %e %Y", strtotime(stripslashes($enteredData['Payment_Deadline'])));
					}
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $tlIdStyleText; ?>>Payment Type</td>
					<td class="entryRL" <?php echo $tlIdStyleText; ?>>
					<?php 
					$tempArr = (is_array($enteredData['Payment_Type'])) ? 
						$enteredData['Payment_Type'] : explode(",", $enteredData['Payment_Type']);
					foreach ($tempArr as &$thisVal) {
						switch ($thisVal){ 
							case "1":
							echo "Cash<br/>";
							break;
							case "2":
							echo "Check<br/>";
							break;
							case "3":
							echo "PayPal<br/>";
							break;
						}
					}
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $ppIdStyleText; ?>>PayPal Account Email</td>
					<td class="entryRL" <?php echo $ppIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Account']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $ppIdStyleText; ?>>PayPal Item Name</td>
					<td class="entryRL" <?php echo $ppIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Item_Name']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>Check Payee Name</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Chk_Payee']);
					?>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>Check Payee Address</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Chk_Address']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>Check Payee City</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Chk_City']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>Check Payee State</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Chk_State']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR" <?php echo $chkIdStyleText; ?>>Check Payee Zip Code</td>
					<td class="entryRL" <?php echo $chkIdStyleText; ?>>
					<?php 
					echo stripslashes($enteredData['Payment_Chk_Zip']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR">Contact Name</td>
					<td class="entryRL">
					<?php 
					echo stripslashes($enteredData['Contact_Name']);
					?>
					</td>
				</tr>
				<tr>
					<td class="titleRR">Contact Email</td>
					<td class="entryRL">
					<a href="mailto:<?php echo stripslashes($enteredData['Contact_Email']) ?>">
						<?php echo stripslashes($enteredData['Contact_Email']) ?>
					</a>
					</td>
				</tr>
				<?php
				if (strstr($enteredData['Publish_Phone'],"Y")) {
				?>
					<tr>
						<td class="titleRR">Contact Phone #</td>
						<td class="entryRL">
						<?php 
						echo stripslashes($enteredData['Contact_Phone']);
						?>
						</td>
					</tr>
				<?php
				}
				
				if (check_owner_authorization() or check_admin_authorization()) {
				?>
					<tr>
						<td class="titleRR">Publish Phone</td>
						<td class="entryRL">
						<?php 
						if (strstr($enteredData['Publish_Phone'],"Y")) {
							echo "Yes";
						} else if (strstr($enteredData['Publish_Phone'],"N")) {
							echo "No";
						} 
						?>
						</td>
					</tr>
				<?php
				}
                ?>
				<tr>
					<td class="titleRR">Your Event's URL Link</td>
					<td class="entryRL">
					   <?php echo LOCATION_SITE."index.php?id=".get_session_event_mgmt() ?>
						<a href="#">
							<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="This link will bring users to your event's home page.  Use this link in emails that you send to potential players or in other web pages." />
						</a>
					</td>
				</tr>
                <?php
				$numOwnersAssignedResults = 0;
	
				if (!empty($assignees['ownersAssigned'])) {
					$numOwnersAssignedResults = mysql_num_rows($assignees['ownersAssigned']);
				}
	
				if ($numOwnersAssignedResults == 0){
				?>			
					<tr>
						<td colspan="2">
						<p>No owners have been assigned to this team.</p> 
						</td>
					</tr>
				<?php
				} else {
				?>
					<tr>
						<td class="titleRR">Other Event Organizers</td>
						<td class="entryRL">
							<?php	
							while ($row=mysql_fetch_array($assignees['ownersAssigned'])) {
								$playerID = $row['Player_ID'];
								$name = $row['Last_Name'].", ".$row['First_Name'];
								/** only display other owners, not owner who is editing this event */
								if ($playerID <> get_session_player_id()) {
									echo $name."<br/>";
								}
							}
							?>
						</td>
					</tr>
				<?php
				}
	
				if (check_owner_authorization() or check_admin_authorization()) {
				?>
					<tr>
						<td colspan="2" class="dispRC">
							<?php
							if ($payStatus == "N") {
							?>
								<button type="submit" value="Activate" class="submitBtn" name="ProcessAction">
									<span>Activate</span>
								</button>
								&nbsp;&nbsp;
							<?php
							}
							?>
							<button type="submit" value="Edit" class="submitBtn" name="ProcessAction">
								<span>Edit</span>
							</button>
							&nbsp;&nbsp;
							<button type="submit" value="Delete" class="submitBtn" name="ProcessAction"
								onclick="dispDeleteBox(<?php echo get_session_event_mgmt() ?>); return false;">
								<span>Delete</span>
							</button>
						</td>
					</tr>
				<?php
				}
				?>
			</table>
		</form>	
	</div>
	<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
	</div>
<?php
}

display_footer_wrapper();
?>