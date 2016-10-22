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
	$teamID = 9999;
	$eventName = get_session_event_name(); 
	$isCaptain = check_captain_authorization();
	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization();
	
	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	
	if (check_value_is_set($eventID) and is_numeric($eventID)) {
		$rsTeams = get_team_profiles_active($eventID);
		switch ($processAction) {
		    case "Send Email":  /** action from email page */
		        if ($isCaptain or $isOwner or $isAdmin) {
					$action = "teamEmail";
					$enteredData = get_data_entered($_POST);
					$errors = validate($action, $enteredData);
					$messageClean = strip_tags($enteredData['Message']);
					if (empty($errors)){
						$teamID = (int)$enteredData['Recipient'];
						if ($teamID == 0) {
							if ($isOwner or $isAdmin) {
								if (!sendEmailTeam($eventID,$teamID,$playerID,$enteredData['Subject'],$messageClean)) {
									log_entry(Logger::EMAIL,Logger::WARN,$eventID,$playerID,
										"An error occurred while sending email to team ".$teamID.".");
									$errors = error_add($errors, 
										"An error occurred while sending your email to one or more players 
										on the roster.");
									build_email_page($errors, $rsTeams);
								} else {
									$actionResult = "You're email was successfully sent.";
									build_email_result_page($errors, $actionResult);
								}
							} else {
								log_entry(Logger::EMAIL,Logger::WARN,$eventID,$playerID,
								"A non Owner or Admin tried to email the roster.");
								$errors = error_add($errors, 
									"Sorry, your not authorized to send an email to the active roster.");
								build_email_page($errors, $rsTeams);
							}
						} else {
							if (!sendEmailTeam($eventID,$teamID,$playerID,$enteredData['Subject'],$messageClean)) {
								log_entry(Logger::EMAIL,Logger::WARN,$eventID,$playerID,
									"An error occurred while sending email to team ".$teamID.".");
								$errors = error_add($errors, 
									"An error occurred while sending your email to the recipient.");
								build_email_page($errors, $rsTeams);
							} else {
								$actionResult = "You're email was successfully sent.";
								build_email_result_page($errors, $actionResult);
							}
						}
					} else {
						build_email_page($errors, $rsTeams);
					}
				} else {
					log_entry(Logger::EMAIL,Logger::WARN,$eventID,$playerID,
						"Non-authorized player tried to peek at Email page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_email_page($errors, $rsTeams);
				}
		        break;
		    default:
		    	if ($isCaptain or $isOwner or $isAdmin) {
					build_email_page($errors, $rsTeams);
				} else {
					log_entry(Logger::EMAIL,Logger::WARN,$eventID,$playerID,
						"Non-authorized player tried to peek at Email page.");
					$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
					build_email_page($errors, $rsTeams);
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

function build_email_page($errors, $rsTeams) {
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
			display_errors($errors);
			?>
			<div id="xsnazzy">
			<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
			<div class="xboxcontent">
 				<form method="post" name="selectionForm" action="email.php" class="box">
					<table class="default">
						<tr>
							<td></td>
							<td colspan="2"><span class="smGD">* required entry</span></td>
						</tr>
						<tr>
							<td class="titleRL">Recipient*</td>
							<td class="entryRL">
								<select name="Recipient" size="1" tabindex="1">
									<option value="">Please select</option>
									<?php 
									if ($isOwner or $isAdmin) {
										echo "<option value=0>Active Roster</option>";
									}
									if($rsTeams) {
										$numResults = mysql_num_rows($rsTeams);
										if ($numResults > 0) {
											$teamID = "";
											$teamName = "";
											while ($row=mysql_fetch_array($rsTeams)) {
												$teamID = $row['Team_ID'];
												$teamName = $row['Team_Name'];
												echo "<option value=$teamID>$teamName</option>";
											}
										}
									} 
									?>
								</select>
							</td>
						</tr>
						<?php 
						if (isset($errors['Recipient'])) {
						?>
							<tr><td></td><td class="error" colspan="2"><?php echo $errors['Recipient'] ?></td></tr>	
						<?php			
						}
						?>
						<tr>
							<td class="titleRL">Subject*</td>
							<td class="entryRL">
								<input 	type="text" 
									name="Subject"
							value="<?php echo (isset($enteredData['Subject'])) ? $enteredData['Subject'] : ""; ?>" 
									size="30" 
									maxlength="40" 
									tabindex="2"> 
							</td>
						</tr>
						<?php 
						if (isset($errors['Subject'])) {
						?>
							<tr><td></td><td class="error" colspan="2"><?php echo $errors['Subject'] ?></td></tr>	
						<?php			
						}
						?>
						<tr>
							<td colspan="2" class="titleRL">
								Text Only Email Message*
							</td>
						</tr>
						<tr>
							<td class="entryRL" colspan="2">
								 <textarea name="Message" rows="10" cols="60" tabindex="3"><?php echo (isset($enteredData['Message'])) ? $enteredData['Message'] : ""; ?></textarea>
							</td>
						</tr>
						<?php 
						if (isset($errors['Message'])) {
						?>
							<tr><td></td><td class="error" colspan="2"><?php echo $errors['Message'] ?></td></tr>	
						<?php			
						}
						?>				
						<?php	
						$buttonLabel1 = "Send Email";
						?>
						<tr>
							<td colspan="2" class="dispRC">
								<button type="submit" value="<?php echo $buttonLabel1 ?>" class="submitBtn" 
									name="ProcessAction">
									<span><?php echo $buttonLabel1 ?></span>
								</button>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
			</div>
		</div>
		<?php
		} else {
			display_errors($errors);
		}
		?>
	</div>
<?php
}

function build_email_result_page($errors, $actionResult) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<?php
		$isCaptain = check_captain_authorization();
		$isOwner = check_owner_authorization();
		$isAdmin = check_admin_authorization();

		if ($isCaptain or $isOwner or $isAdmin) {
			display_errors($errors);
			echo "<br/><p>".$actionResult."</p>";
			$buttonLabel1 = "Ok";
			?>
			<form method="post" name="selectionForm" action="email.php">
				<table class="defaultG">
					<tr>
						<td class="dispRC">
							<button type="submit" value="<?php echo $buttonLabel1 ?>" class="submitBtn" 
								name="ProcessAction">
								<span><?php echo $buttonLabel1 ?></span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		<?php
		} else {
			display_errors($errors);
		}
		?>
	</div>
<?php
}

display_footer_wrapper();
?>