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
	$eventID = "";
	$teamID = 0;
	$playerID = "";
	$actionResult = "";	/** registration result: success, wait list, already registered, gender limit */
	$showCost = false; /** display league costs to user */
	$passUPACheck = false;
	$passGenderLimitCheck = false;
	$existEvent = array();
	$existPlayer = array();
	$enteredData = array();
	$status = array();
	$fees = array();
	$errors = array();

	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	if ($processAction == "Update") {
		$action = "update";
	} else if ($processAction == "Register") {
		$action = "register";
	} else if ($processAction == "Add to Wait List") {
		$action = "waitList";
	} else if ($processAction == "Finish") {
		$action = "finish";
	}

	/** get event & player info to build page and register player */
	$playerID = get_session_player_id();
	$eventID = get_session_event_register();
	$existPlayer = get_player_profile($playerID);
	$gender = $existPlayer['Gender'];
	/** get data that was entered on register page */
	$enteredData = get_data_entered($_POST);
	$pctOfGames = (isset($enteredData['Pct_Of_Games']) ? $enteredData['Pct_Of_Games'] : "");
	$tShirtOrder = (isset($enteredData['T_Shirt_Order']) ? $enteredData['T_Shirt_Order'] : "");
	$discOrder = (isset($enteredData['Disc_Order']) ? $enteredData['Disc_Order'] : "");
	$upaEnrollment = (isset($enteredData['UPA_Enrollment']) ? $enteredData['UPA_Enrollment'] : "");

	if ($pctOfGames <> "" or $tShirtOrder <> "" or $discOrder <> "" or $upaEnrollment <> "") {
		$showCost = true;
	}

	if (check_value_is_set($eventID) and is_numeric($eventID)) { /** event ID can't be empty */
		$existEvent = get_event_profile($eventID);
		if (!empty($existEvent)) {  /** event details can't be empty */
			if ($action == "update" or $action == "register") {
				/** need to do this in case player orders a disc which requires additional fields to be validated */
				$thisAction = ($discOrder == 'Y') ? "registerDisc" : "register";
				/** set disc count in case the player doesn't want to buy a disc or there are no discs to buy */
				$enteredData['Disc_Count'] = ($discOrder <> "Y") ? 0 : $enteredData['Disc_Count']; 
				 
				if ($action == "register") { 
					$errors = validate($thisAction, $enteredData);
				}
				if (!empty($errors)){
					$showCost = false;
					build_register_page($errors, $existEvent, $existPlayer, $enteredData, 
						$actionResult, $status, $fees, $showCost);
				} else {
					$status = get_status($existPlayer, $tShirtOrder, $discOrder, $upaEnrollment);
					$fees = get_fees($existEvent, $enteredData['Disc_Count'], $status);
					if ($action == "register") {
						/** check if player is already registered */
						if (check_player_in_event($eventID, $playerID)) {
							/** clear registration event session var */
							unset_session_event_register();
							$errors = error_add($errors, "You are already registered for 
														the ".$existEvent['Event_Name']."!");
							/** check if player is already on the wait list.
						 	 * 	NOTE, if the event gender limit has not been met and the user is on the wait list, 
							 *  let the user pass here in order to try to register since they may have added
							 *  themselves to the wait list when they attempted to register and they weren't 
						     *  a current UPA member... if the gender limit is met and they're already on the 
							 *  wait list, then all bets are off...  got it?
							 *  
							 *	11/12/09 - changed page to not allow user to select "no" that they aren't a 
							 * 	upa member... am leaving the code below as it was - it doesn't hurt to leave 
							 * 	it in and provides flexibility for situation where player is somehow on the 
							 * 	wait list and then perhaps the gender limits change which would allow them to 
							 * 	subsequently reregister - boy, this is really thinking far ahead...
							 */
						} else if (check_player_on_wait_list($eventID, $playerID) and 
							!gender_limit_reached($eventID, $gender)) {
							/** clear registration event session var */
							unset_session_event_register();
							$errors = error_add($errors, "You are already on the wait list for 
														the ".$existEvent['Event_Name']."!");
						} else {
							/** check if player is a current UPA member or opting to pay one time fee */
							if ($existEvent['UPA_Event'] == 'Y') {
								if ($status['upaEnrollment'] == 1 or 
									$status['upaEnrollment'] == 2 or 
									$status['curUPAMember'] == "Y") {
										$passUPACheck = true;
									/** force cur UPA member status to N if they selected to pay 
										the 1 time event fee */
									if ($status['upaEnrollment'] == 2) {
										$status['curUPAMember'] = "N";
									} else if ($status['upaEnrollment'] == 1) {
										$status['curUPAMember'] = "Y";
									}
								}
							} else {
								$passUPACheck = true; /** pass UPA check for non-upa events */
							}
							$passGenderLimitCheck = gender_limit_reached($eventID, $gender);
							/** determine how to route player thru registration **/	
							/** case  1: player is current upa member and gender limit not reached **/
							if ($passUPACheck and $passGenderLimitCheck) {
								/** update player profile with current UPA status */ 
								if (!update_player_profile_upa_status($playerID, $status)) {
									log_entry(Logger::REG,Logger::ERROR,$eventID,$playerID,
										"Failed to update player profile with UPA status.");
									$errors = error_add($errors, "An error occurred while updating your profile. 
																Please restart the registration process by going 
																back to the event home page.");
								} else {
									/** add player to roster */
									if (!insert_roster($eventID,$playerID,$teamID,
										$fees,$enteredData['Disc_Count'],$gender,$pctOfGames)) {
										log_entry(Logger::REG,Logger::ERROR,$eventID,$playerID,
											"Failed to add player to roster.");
										$errors = error_add($errors, "An error occurred while adding you to the 
																	roster. Please restart the registration process 
																	by going back to the event home page.");
									} else {
										/** check if player had been on wait list - if so, update the assigned 
									 	 * 	flag that indicates that they are now on a roster */   
										if (check_player_on_wait_list($eventID, $playerID)) {
											if (!update_wait_list_assignment($eventID, $playerID)) {
												log_entry(Logger::REG,Logger::ERROR,$eventID,$playerID,
													"Failed to update status of player who had previously been on 
													wait list.");
												$errors = error_add($errors, "An error occurred while updating 
																		your status on the wait list. Please 
																		notify the event owner to check that your 
																		name is not on the wait	list");
											} else {
												$actionResult = "registerOk";
											}
										} else {
											$actionResult = "registerOk";
										}
										sendEmail(EmailWrapper::REGISTER,$eventID,0,array($playerID));
									}
								}
								if ($actionResult == "registerOk") {
									/** update list of events in MyEvents session variable */
									set_session_event_list($arrEvents = get_events_for_player($playerID));
									log_entry(Logger::REG,Logger::INFO,$eventID,$playerID,
										"Successfully registered player for event.");							
								}
							/** case  2: player is not current upa member and gender limit not reached **/
							} else if (!$passUPACheck and $passGenderLimitCheck) {
								$actionResult = "upaFail*genderLimitOK";
							
							/** case  3: player is current upa member and gender limit reached **/	
							} else if ($passUPACheck and !$passGenderLimitCheck) {
								if ($gender == "M") {
									$actionResult = "upaOk*manLimitFail";
								} else {
									$actionResult = "upaOk*womanLimitFail";
								}
								
							/** case  4: player is not current upa member and gender limit reached **/
							} else if (!$passUPACheck and !$passGenderLimitCheck) {
								if ($gender == "M") {
									$actionResult = "upaFail*manLimitFail";
								} else {
									$actionResult = "upaFail*womanLimitFail";
								}
							}
						}
					}
					build_register_page($errors, $existEvent, $existPlayer, $enteredData, 
						$actionResult, $status, $fees, $showCost);	
				}
			} else if ($action == "waitList") {
				/** before adding player to wait list, doublecheck if they had added themselves to 
				 *  to the list when the gender limit hadn't yet been reached during registration - 
				 *  this should be very rare... or if they refresh the wait list page... */
				if (check_player_on_wait_list($eventID, $playerID)) {
					/** clear registration event session var */
					unset_session_event_register();
					$errors = 
					error_add($errors, "You are already on the wait list for the ".$existEvent['Event_Name']."!");
				} else {
					$status = get_status($existPlayer, $tShirtOrder, $discOrder, $upaEnrollment);
					$fees = get_fees($existEvent, $enteredData['Disc_Count'], $status);
					
				if (!insert_wait_list($eventID,$playerID,$gender,$pctOfGames,$fees,$enteredData['Disc_Count'])) {
						log_entry(Logger::REG,Logger::ERROR,$eventID,$playerID,"Failed to add player 
																				to wait list.");
						$errors = error_add($errors, "An error occured while trying to add you to the wait list. 
													Please restart the registration process by going back to the 
													event home page.");
					} else {
						$actionResult = "waitListedPlayer";
						sendEmail(EmailWrapper::WAIT_LIST_ON,$eventID,0,array($playerID));
						log_entry(Logger::REG,Logger::INFO,$eventID,$playerID,
							"Successfully added player to event wait list.");
					}
				}
				build_register_page($errors, $existEvent, $existPlayer, $enteredData, 
					$actionResult, $status, $fees, $showCost);
			} else if ($action == "finish") {
				/** clear registration event session var and route user to index page */
				unset_session_event_register();
				redirect_page("index.php");
			} else {
				/** display page for user for first time */ 
				build_register_page($errors, $existEvent, $existPlayer, $enteredData, 
					$actionResult, $status, $fees, $showCost);
			}
		} else {
			$errors = error_add($errors, "Event information was not successfully retrieved. 
										Please restart the registration process by going back to the home page.");
			build_register_page($errors,$existEvent,$existPlayer,$enteredData,$actionResult,$status,$fees,$showCost);
		}
	} else {
		$errors = error_add($errors, "An event has not been selected in which to register. 
									Please restart the registration process by going back to the home page.");
		build_register_page($errors,$existEvent,$existPlayer,$enteredData,$actionResult,$status,$fees,$showCost);
	}
} else {
	display_non_authorization();
}

/** check if male/female limit has been reached */
function gender_limit_reached($eventID, $gender) {
	$genderLimit = get_event_limit_gender($eventID, $gender);
	$genderRegistered = get_event_reg_gender($eventID, $gender);
	if ($genderRegistered < $genderLimit) {
		return true;
	} else {
		return false;
	}
}

function get_status($existPlayer, $tShirtOrder, $discOrder, $upaEnrollment){
	$status = array();
	$status['over18'] = "";
	$status['curUPAMember'] = "";
	$status['upaEnrollment'] = "";
	$status['tShirtOrder'] = "";
	$status['discOrder'] = "";
	
	/** get statuses */
	$status['over18'] = $existPlayer['Over18'];
	$status['curUPAMember'] = $existPlayer['UPA_Cur_Member'];
	$status['tShirtOrder'] = $tShirtOrder;
	$status['discOrder'] = $discOrder;

	/** 0 - non upa event - in here to placate validation */
	/** 1 - current upa member */
	/** 2 - one time upa event fee */
	/** 3 - no action - 11/12/09 no longer an option */
	$status['upaEnrollment'] = $upaEnrollment;
	return $status;
}

function get_fees($existEvent, $discCount, $status) {
	$fees = array();
	$fees['event'] = 0.00;
	$fees['eventTShirt'] = 0.00;
	$fees['eventDisc'] = 0.00;
	$fees['event1Time'] = 0.00;
	$fees['total'] = 0.00;
	$discCount = ($discCount <> "" ? $discCount : 0);

	/** determine event fees */
	$fees['event'] = $existEvent['Event_Fee']; 
	if ($status['tShirtOrder'] == "Y") {
		$fees['eventTShirt'] = $existEvent['Event_TShirt_Fee'];	
	}
	if ($status['discOrder'] == "Y") {
		$fees['eventDisc'] = $existEvent['Event_Disc_Fee'] * $discCount;
	}
	if ($status['upaEnrollment'] == 2) {
		($status['over18'] == "N") ? $fees['event1Time'] = 5.00 : $fees['event1Time'] = 10.00;
	} 
	$fees['total'] = $fees['event'] + $fees['eventTShirt'] + $fees['eventDisc'] + $fees['event1Time'];
	return $fees; 
}

function build_register_page($errors,$existEvent,$existPlayer,$enteredData,$actionResult,$status,$fees,$showCost) {
	display_wrappers();
?>
	<div id="content_wrapper">
	<?php
	if (!empty($errors['app'])) {
		display_errors($errors);	
	} else {
		$pctOfGames = (isset($enteredData['Pct_Of_Games']) ? $enteredData['Pct_Of_Games'] : "");
		$tShirtOrder = (isset($enteredData['T_Shirt_Order']) ? $enteredData['T_Shirt_Order'] : "");
		$discOrder = (isset($enteredData['Disc_Order']) ? $enteredData['Disc_Order'] : "");
		$discCount = (isset($enteredData['Disc_Count']) ? $enteredData['Disc_Count'] : "");
		$upaEnrollment = (isset($enteredData['UPA_Enrollment']) ? $enteredData['UPA_Enrollment'] : "");
		$blurb = "";
		$blurb2 = "";
		$paymentBlurb = "";

		if ($actionResult <> "registerOk" and $actionResult <> "waitListedPlayer") {
			if ($actionResult == "") {
			?>	
				<p>	
				<?php		
				if ($existPlayer['UPA_Cur_Member'] == "N" and $existEvent['UPA_Event'] == "Y") { 
				?>
					Since you indicated that you are not a current UPA member, you need to select one of the  
					two options below to play in this UPA sanctioned event.
					<br/><br/>
					If you have never been a member of the UPA, click 
					<a href="http://www.upa.org/membership" target="_blank">here</a> to choose and sign up 
					for the UPA membership level that is right for you.  After you signup with the UPA, please  
					complete the rest of this page and click on the 'Update' and then the 'Register' buttons to 
					continue with your registration.
					<br/><br/>
					If you have previously been a member of the UPA, click 
					<a href="https://www.upa.org/members/login.php" target="_blank">here</a> to check your 
					membership status and renew it, if necessary.  After you signup with the UPA, please  
					complete the rest of this page and click on the 'Update' and then the 'Register' buttons to 
					continue with your registration.
					<br/><br/>
					Another option is to pay a one time event fee to the UPA.  This fee is $10 if you're 
					18 years old or older, or $5 if you're under 18.  This fee may be paid along with your 
					event registration fees	to the event organizer.  The event organizer, in turn, will 
					forward this fee to the UPA.  At the first game, you will be asked by your captain 
					or event organizer to provide your UPA number or the last four digits of your social 
					security number and address information for a roster report that will be submitted to 
					the UPA.  After you signup with the UPA, please complete the rest of this page and click 
					on the 'Update' and then the 'Register' buttons to continue with your registration.
				<?php 
				} else {
				?>
					You requested to sign up for the <b><?php echo $existEvent['Event_Name'] ?></b>. 
					After you click the 'Update' and then the 'Register' buttons, you will be added to the roster 
					if space is available.  Otherwise, you will be given the option to be added to the wait list.
				<?php 
				}
				
				if ($existEvent['UPA_Event'] == "Y") {
				?>
					<br/><br/>
					Please note that the event organizer will request that you complete some UPA paperwork. 
					This will be done at the time of the first game.  All players 18 years old and older 
					will need to complete a waiver and release of liablity form.  Players under 18 will need 
					to complete a waiver and release of liablity form and a medical authorization form signed 
					by a parent	or guardian.
				<?php
				}
				?>
				</p>
				<br/>
				<div id="xsnazzy">
				<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
				<div class="xboxcontent">
					<form method="post" name="selectionForm" action="register.php" class="box">
					<?php
					/** if this isn't a league event, then assume better than 75% attendance */
					if ($existEvent['Event_Type'] <> "1") {
						echo "<input type=\"hidden\" name=\"Pct_Of_Games\" value=\"3\">";
					}
					if (number_format($existEvent['Event_TShirt_Fee'], 2, '.', '') == 0.00) {
						echo "<input type=\"hidden\" name=\"T_Shirt_Order\" value=\"N\">";
					}
					if (number_format($existEvent['Event_Disc_Fee'], 2, '.', '') == 0.00) {
						echo "<input type=\"hidden\" name=\"Disc_Order\" value=\"N\">";
					}
					if ($existEvent['UPA_Event'] != "Y") {
						echo "<input type=\"hidden\" name=\"UPA_Enrollment\" value=\"99\">";
					}
					?>
					<table class="default">
						<tr>
							<td></td>
							<td><span class="smGD">* required entry</span></td>
						</tr>
						<tr>
							<td class="titleRR">Event Name</td>
							<td class="entryRL"><?php echo $existEvent['Event_Name']?></td>
						</tr>
						<?php
						/** if this is a league event */
						if ($existEvent['Event_Type'] == "1") {
						?>	
							<tr>
								<td class="titleRR">Percentage of games that you can attend*</td>
								<td class="entryRL">
									<select	name="Pct_Of_Games" size="1" tabindex="1">
										<option value="">Please select...
										<option value="1"
											<?php if ($pctOfGames == "1") { echo "selected"; } ?>
											>Less than 50%</option>
										<option value="2"
											<?php if ($pctOfGames == "2") { echo "selected"; } ?>
											>50%-75%</option>
										<option value="3"
											<?php if ($pctOfGames == "3") { echo "selected"; } ?>
											>More than 75%</option>
									</select>
								</td>
							</tr>
						<?php
						}
						if (isset($errors['Pct_Of_Games'])) {
						?>
							<tr><td></td><td class="error"><?php echo $errors['Pct_Of_Games'] ?></td></tr>
						<?php
						}
						
						if ($existEvent['Event_TShirt_Fee'] > 0) {
							if ($existEvent['Event_Type'] == "1") {
								$tShirtQuestion = 
										"Would you like to purchase a league T-shirt?";
							} else {
								$tShirtQuestion = "Would you like to purchase an event T-shirt?";
							}
						?>
							<tr>
								<td class="titleRR"><?php echo $tShirtQuestion ?>*</td>
								<td class="entryRL">
									<input type="radio" 
											name="T_Shirt_Order"
											value="Y" 
											<?php if ($tShirtOrder == "Y") { 
												echo "checked"; } ?>>Yes
									<input type="radio" 
											name="T_Shirt_Order"
											value="N" 
											<?php if ($tShirtOrder == "N") { 
												echo "checked"; } ?>>No
								</td>
							</tr>
						<?php	
						}
						if (isset($errors['T_Shirt_Order'])) {
						?>
							<tr><td></td><td class="error"><?php echo $errors['T_Shirt_Order'] ?></td></tr>
						<?php			
						}
						
						if ($existEvent['Event_Disc_Fee'] > 0) {
							if ($existEvent['Event_Type'] == "1") {
								$discQuestion = 
										"Would you like to purchase a league disc?";
							} else {
								$discQuestion = "Would you like to purchase an event disc?";
							}
						?>
							<tr>
								<td class="titleRR"><?php echo $discQuestion ?>*</td>
								<td class="entryRL">
									<input type="radio" 
											name="Disc_Order"
											value="Y" 
											<?php if ($discOrder == "Y") { 
												echo "checked"; } ?>>Yes
									<input type="radio" 
											name="Disc_Order"
											value="N" 
											<?php if ($discOrder == "N") { 
												echo "checked"; } ?>>No
								</td>
							</tr>
							<?php
							if (isset($errors['Disc_Order'])) {
							?>
								<tr><td></td><td class="error"><?php echo $errors['Disc_Order'] ?></td></tr>
							<?php
							}						
						}
						
						if ($discOrder == "Y") {
						?>
							<tr>
								<td class="titleRR">Number of discs*</td>
								<td class="entryRL">
									<input type="text" 
											name="Disc_Count"
											value="<?php echo $discCount ?>"
											size="30"> 
								</td>
							</tr>
							<?php			
							if (isset($errors['Disc_Count'])) {
							?>
								<tr><td></td><td class="error"><?php echo $errors['Disc_Count'] ?></td></tr>
							<?php	
							}
						}
						
						if ($existEvent['UPA_Event'] == "Y") { 
						?>
							<tr>
								<td class="titleRR">UPA Enrollment*</td>
								<td class="entryRL">
									<select	name="UPA_Enrollment" size="1" tabindex="3">
										<option value="">Please select...
										<option value="1"
											<?php if ($upaEnrollment == "1") { 
												echo "selected"; } ?>
											>I'm now a current member of the UPA !</option>
										<option value="2"
											<?php if ($upaEnrollment == "2") { 
												echo "selected"; } ?>
											>I'm opting to pay the one time UPA event fee</option>
									</select>
								</td>
							</tr>
							<?php					
							if (isset($errors['UPA_Enrollment'])) {
							?>
								<tr><td></td><td class="error"><?php echo $errors['UPA_Enrollment'] ?></td></tr>
							<?php
							}						
						}
						
						if ($showCost) {
						?>
							<tr>
								<td class="titleRR">Event Fee</td>
								<td class="entryRL">
									<?php echo "\$".number_format($fees['event'], 2, '.', '') ?>
								</td>
							</tr>
							<?php
							if ($status['tShirtOrder'] == "Y") { 
							?>
								<tr>
									<td class="titleRR">T-Shirt Fee</td>
									<td class="entryRL">
										<?php echo "\$".number_format($fees['eventTShirt'], 2, '.', '') ?>
									</td>
								</tr>
							<?php
							}
							if ($status['discOrder'] == "Y") { 
							?>
								<tr>
									<td class="titleRR">Disc Fee</td>
									<td class="entryRL">
										<?php echo "\$".number_format($fees['eventDisc'], 2, '.', '') ?>
									</td>
								</tr>
							<?php
							}
							if ($status['upaEnrollment'] == 2) { 
							?>
								<tr>
									<td class="titleRR">One Time UPA Event Fee</td>
									<td class="entryRL">
										<?php echo "\$".number_format($fees['event1Time'], 2, '.', '') ?>
									</td>
								</tr>
							<?php
							}
							?>
							<tr>
								<td class="titleRR">Total Fees</td>
								<td class="entryRL">
									<?php echo "\$".number_format($fees['total'], 2, '.', '') ?>
								</td>
							</tr>
							<tr>
								<td class="titleRR">Payment Deadline</td>
								<td class="entryRL">
									<?php 
									if (IS_LOCAL) {
										echo strftime("%b %d %Y", strtotime($existEvent['Payment_Deadline']));	
									} else {
										echo strftime("%b %e %Y", strtotime($existEvent['Payment_Deadline']));
									}
									?>
								</td>
							</tr>
						<?php
						}
						?>
						<tr>
							<td colspan="2" class="dispRC">
								<button type="submit" value="Update" class="submitBtn" name="ProcessAction">
									<span>Update</span>
								</button>
								&nbsp;&nbsp;
								<?php 
								if ($showCost){ 
								?>
									<button type="submit" value="Register" class="submitBtn" name="ProcessAction">
										<span>Register</span>
									</button>
								<?php
								}
								?>
							</td>
						</tr>
					</table>
					</form>
				</div>
				<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
				</div>
			<?php
			/** player has run into a problem with their UPA status or the gender limit */
			} else { 
				/** setup error display text */
				$upaDisplayText = "";
				$limitDisplayText = "";
				$waitListNumber = "";
				$genderVar = $existPlayer['Gender'];
				
				/** perform prep for current upa member failures */
				if (strstr($actionResult, "upaFail")) {
					$upaDisplayText = "You opted to register for this event even though you are not a 
					current UPA	member or did not elect to pay the one time UPA event fee. In order to play 
					in this event, you must be a UPA member in good standing or choose to pay the one time 
					UPA event fee. When you decide to follow either of these options, update your player profile 
					and	try reregistering for this event.<br/>";		
				}
				
				/** present wait list option */
				if (strstr($actionResult, "Fail")) {
				?>
					<form method="post" name="selectionForm" action="register.php">
						<input type="hidden" name="Pct_Of_Games" value="<?php echo $pctOfGames ?>">
						<input type="hidden" name="T_Shirt_Order" value="<?php echo $tShirtOrder ?>">
						<input type="hidden" name="Disc_Order" value="<?php echo $discOrder ?>">
						<input type="hidden" name="Disc_Count" value="<?php echo $discCount ?>">
						<input type="hidden" name="UPA_Enrollment" value="<?php echo $upaEnrollment ?>">
						<p>Sorry, we weren't able to register you for the <?php echo $existEvent['Event_Name'] ?> 
						because of the following reason(s):</p>
						<ul>
						<?php
						/** perform prep for gender limit failures */
						if (strstr($actionResult, "LimitFail")) {  
							if (strstr($actionResult, "womanLimit")) {
								$limitDisplayText = "The limit for the number of women players has been reached.";
							} else {
								$limitDisplayText = "The limit for the number of men players has been reached.";
							}
							$waitListNumber = get_wait_list_gender($existEvent['Event_ID'], $genderVar);	
						}
						
						if ($upaDisplayText <> "") {
							echo "<li>".$upaDisplayText."</li>";
						}
						if ($limitDisplayText <> "") {
							echo "<li>".$limitDisplayText."</li>";
						} 
						?>
						</ul>
						<p>If you would like to be added to the wait list, please click the 
						'Add to Wait List' button below. Currently, there 
						<?php
						if ($waitListNumber < 1) {
							if ($genderVar == "M") {
								echo "are no men players ahead of you.  You're the first in line! ";
							} else {
								echo "are no women players ahead of you.  You're the first in line! ";
							}
						} else {
							if ($waitListNumber == 1) {
								if ($genderVar == "M") {
									echo "is ".$waitListNumber." man player ahead of you. ";
								} else {
									echo "is ".$waitListNumber." woman player ahead of you. ";
								}
							} else {
								if ($genderVar == "M") {
									echo "are ".$waitListNumber." men players ahead of you. ";
								} else {
									echo "are ".$waitListNumber." women players ahead of you. ";
								}
							}
						}
						?>
						Please note that by adding your name to the wait list, the event organizer will be able 
						to view your email address and home and/or cell phone numbers so that they can easily  
						contact you.
						</p>
						<table class="defaultG">
							<tr>
								<td class="dispRC">
									<button type="submit" value="Add to Wait List" class="submitBtn" 
										name="ProcessAction">
										<span>Add to Wait List</span>
									</button>
								</td>
							</tr>
						</table>
					</form>
				<?php
				}
			}	
		/** player has successfully registered */
		} else if ($actionResult == "registerOk") {
		?>
			<p>
			Congratulations, you are now registered to play in the 
			<?php echo $existEvent['Event_Name'] ?>!<br/><br/>
			</p>
			<?php
			if ($fees['total'] <> 0) {
			?>
				<p>
				Please note that you can not play until you pay your fees to 
				<?php echo $existEvent['Org_Sponsor'] ?>:<br/>
				<table class="defaultG">
					<tr>
						<td>&nbsp;&nbsp;&nbsp;</td>
						<td class="dispRL">
						<p>
						<?php 
						echo "$".number_format($fees['event'], 2, '.', '')." - Event Fee<br/>";
						if ($status['tShirtOrder'] == "Y") { 
							echo "$".number_format($fees['eventTShirt'], 2, '.', '')." - T Shirt Fee<br/>";
						}
						if ($status['discOrder'] == "Y") { 
							echo "$".number_format($fees['eventDisc'], 2, '.', '')." - Disc Fee<br/>";
						}
						if ($status['upaEnrollment'] == 2) {
							echo "$".number_format($fees['event1Time'], 2, '.', '')." - One Time UPA Event Fee<br/>";	
						}
						?>
						$<?php echo number_format($fees['total'], 2, '.', '') ?> - Total Due 
						</p>
						</td>
					</tr>
				</table>
				<p>
				All fees are due to <?php echo $existEvent['Org_Sponsor'] ?> by	
				<?php echo strftime("%B %d, %Y", strtotime($existEvent['Payment_Deadline']))?>.<br/><br/>
				</p>
				<p>
					The following types of payment are accepted for this event:
					<ul>
					<?php 
					$temp = strstr(is_string($existEvent['Payment_Type']) ? 
						$existEvent['Payment_Type'] : implode(',',$existEvent['Payment_Type']), "1");
					if ($temp !== false) {
						echo "<li>Cash</li>";
					}
					$temp = strstr(is_string($existEvent['Payment_Type']) ? 
						$existEvent['Payment_Type'] : implode(',',$existEvent['Payment_Type']), "2");
					if ($temp !== false) {
						echo "<li>Check</li>";
						$blurb = "the address of the check payee";
					}
					$temp = strstr(is_string($existEvent['Payment_Type']) ? 
						$existEvent['Payment_Type'] : implode(',',$existEvent['Payment_Type']), "3");
					if ($temp !== false) {
						echo "<li>PayPal</li>";
						if($blurb <> "") {
							$blurb = $blurb." and a payment link to Paypal"; 
						} else { 
							$blurb = "a payment link to Paypal";
						}
						$blurb2 = "you either pay through Paypal or ";
					}
					$paymentBlurb =  " Also note that, for your convenience, ".$blurb." will be displayed on 
									the Registration Status page until ".$blurb2."your 
									roster spot is marked as being paid by the event organizer.";
					?>
					</ul>
				</p>
				<p>
					<?php
					$temp = strstr(is_string($existEvent['Payment_Type']) ? 
						$existEvent['Payment_Type'] : implode(',',$existEvent['Payment_Type']), "3");
					if ($temp !== false) {
					?>
						<table class="defaultG">
							<tr>
								<td width="40%"><p>Click here to pay through PayPal:</p></td>
								<td class="dispRL">
									<?php build_register_paypal($existEvent, $fees['total']); ?> 
								</td>
							</tr>
						</table>
					<?php
					}
					?>
				</p>
				<?php
				$temp = strstr(is_string($existEvent['Payment_Type']) ? 
					$existEvent['Payment_Type'] : implode(',',$existEvent['Payment_Type']), "2");
				if ($temp !== false) {
				?>
					<p>
						If paying by check, please make the check out to 
						<?php echo $existEvent['Payment_Chk_Payee'] ?> and mail it to the address below:
						<br/><br/>
						<?php build_register_check($existEvent) ?>
					</p>
					<br/>
				<?php
				}
			}
			?>
			<p>
			An email notification has been sent to you confirming your successful registration for 
			this event. <?php echo $paymentBlurb ?>
			<br/><br/>
			If you have any issues or questions regarding this frisbee event, please contact the event organizer, 
			<?php echo stripslashes($existEvent['Contact_Name'])?>, at 
			<a href="mailto:<?php echo stripslashes($existEvent['Contact_Email']) ?>">
			<?php echo stripslashes($existEvent['Contact_Email']) ?></a>.
			<br/><br/>
			Thanks! And get ready for some great ultimate!
			</p>
			<form method="post" name="selectionForm" action="register.php">
				<table class="defaultG">
					<tr>
						<td class="dispRC">
							<button type="submit" value="Finish" class="submitBtn" name="ProcessAction">
								<span>Finish</span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		<?php 
		/** player has been wait listed */
		} else if ($actionResult == "waitListedPlayer") {
			$eventID = get_session_event_register();
			$playerID = get_session_player_id();
			$waitListPosition = new WaitListPosition($eventID, $playerID, $existPlayer['Gender']);
			$waitListNbr = $waitListPosition->get_position();
			$waitListTtl = $waitListPosition->get_total();
			$waitNbrText = "";
			if ($existPlayer['Gender'] == "M") {
				$genderTxt = "men";
			} else {
				$genderTxt = "women";
			}
			
			if ($waitListNbr > 0 and $waitListTtl > 0) {
				$waitNbrText = "  Currently, your wait list number is ".$waitListNbr." out of a total ".$waitListTtl." wait listed ".$genderTxt." players.";
			}			
			?>
			<p>	You have been successfully added to the wait list for the 
			<?php echo $existEvent['Event_Name'] ?>.<?php echo $waitNbrText ?>  If an opening comes up, 
			the event organizer will call or email you to see if you're still interested in participating.  
			<br/><br/>
			Be sure to check the Registration Status page to see your current position on the wait list. An 
			email has been sent to you confirming your successfull addition to the  wait list.
			<br/><br/>
			Thanks for wanting to play with the <?php echo $existEvent['Org_Sponsor'] ?> and we hope to see 
			you on the field!
			</p>
			<form method="post" name="selectionForm" action="register.php">
				<table class="defaultG">
					<tr>
						<td class="dispRC">
							<button type="submit" value="Finish" class="submitBtn" name="ProcessAction">
								<span>Finish</span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		<?php
		}
	}
	?>
	</div>
<?php
}

display_footer_wrapper();
?>