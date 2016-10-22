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
	clear_selected_event();
	
	/** variable declarations */
	$action = "";
	$result = "";
	$enteredData = array();
	$errors = array();
	
	$playerID = get_session_player_id();
	$enteredData = get_player_account($playerID);
	
	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	if ($processAction == "Update Player ID") {
		$action = "updateID";
	} else if ($processAction == "Save ID") {
		$action = "accountID";
	} else if ($processAction == "Update Password") {
		$action = "updatePassword";
	} else if ($processAction == "Save Password") {
		$action = "accountPassword";
	} else if ($processAction == "Update Email") {
		$action = "updateEmail";
	} else if ($processAction == "Save Email") {
		$action = "accountEmail";
	}

	if ($action == "updateID") {
		build_account_settings_short_name($errors, $enteredData);
	} else if ($action == "updatePassword") {
		build_account_settings_password($errors, $enteredData);
	} else if ($action == "updateEmail") {
		build_account_settings_email($errors, $enteredData);
	} else if ($action == "accountID") {
		$enteredData = get_data_entered($_POST);
		$errors = validate($action, $enteredData);
		if (empty($errors)) {
			$rs = update_player_account_id($playerID, $enteredData);
			if (!$rs) {
				log_entry(Logger::ACCT,Logger::ERROR,0,$playerID,"Failed to update player account ID.");
				$errors = error_add($errors, "An error occurred while updating your ID.");
			} else {
				log_entry(Logger::ACCT,Logger::INFO,0,$playerID,"Successfully updated player account ID.");
				$action = "updateSuccessful";
			}
			$enteredData = get_player_account($playerID);
			build_account_settings_page($errors, $action, $enteredData);
		} else {
			build_account_settings_short_name($errors, $enteredData);
		}	
	} else if ($action == "accountPassword") {
		$enteredData = get_data_entered($_POST);
		$errors = validate($action, $enteredData);
		if (empty($errors)) {
			$rs = update_player_account_password($playerID, $enteredData);
			if (!$rs) {
				log_entry(Logger::ACCT,Logger::ERROR,0,$playerID,"Failed to update player account password.");
				$errors = error_add($errors, "An error occurred while updating your password.");			
			} else {
				log_entry(Logger::ACCT,Logger::INFO,0,$playerID,"Successfully updated player account password.");
				$action = "updateSuccessful";
			}
			$enteredData = get_player_account($playerID);
			build_account_settings_page($errors, $action, $enteredData);
		} else {
			build_account_settings_password($errors, $enteredData);
		}
			
	} else if ($action == "accountEmail") {
		$enteredData = get_data_entered($_POST);
		$errors = validate($action, $enteredData);
		if (empty($errors)) {
			$rs = update_player_account_plus($playerID, $enteredData);
			if (!$rs) {
				log_entry(Logger::ACCT,Logger::ERROR,0,$playerID, "Failed to update player account email.");
				$errors = error_add($errors, "An error occurred while updatng your email.");
			} else {
				log_entry(Logger::ACCT,Logger::INFO,0,$playerID,"Successfully updated player account email.");
				$action = "updateSuccessful";
			}
			$enteredData = get_player_account($playerID);
			build_account_settings_page($errors, $action, $enteredData);
		} else {
			build_account_settings_email($errors, $enteredData);
		}
	} else {
		build_account_settings_page($errors, $action, $enteredData);
	}
} else {
	display_non_authorization();
}	

function build_account_settings_page($errors, $action, $enteredData) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		display_errors($errors);
		
		if ($action == "updateSuccessful") {
			echo "<p>Your new player account settings were successfully updated!</p>";
		}
		?>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" name="selectionForm" action="account_settings.php" class="box">		
				<table class="default">
					<tr>
						<td class="titleRR">Player ID</td>
						<td class="entryRL">
							<?php echo (isset($enteredData['Short_Name']) ? $enteredData['Short_Name'] : "")?>
						</td>
					</tr>
					<tr>
						<td class="titleRR">Email</td>
						<td class="entryRL">
							<?php echo (isset($enteredData['Email']) ? $enteredData['Email'] : "")?>
						</td>
					</tr>
					<tr>
						<td class="titleRR">Opt In Email Notification</td>
						<td></td>
					</tr>
					<tr>				
						<td class="titleRR">Ok to receive event specific email from event Captain 
											or Organizer through MightyUltimate.com</td>
						<td class="entryRL">
							<?php 
							$thisVal = 
								(isset($enteredData['Email_Opt_Capt']) ? 
									(strstr($enteredData['Email_Opt_Capt'],"Y") ? "Yes" : "No") : "No"); 
							echo $thisVal;
							?>
						</td>
					</tr>
					<tr>
						<td class="titleRR">Ok to receive general email from<br/>Mighty Ultimate Disc</td>
						<td class="entryRL">
							<?php 
							$thisVal = 
								(isset($enteredData['Email_Opt_MU']) ? 
									(strstr($enteredData['Email_Opt_MU'],"Y") ? "Yes" : "No") : "No"); 
							echo $thisVal;
							?>
						</td>
					</tr>
					<tr>
						<td class="dispRC" colspan="3">
							<button type="submit" value="Update Player ID" class="submitBtn" name="ProcessAction">
								<span>Update Player ID</span>
							</button>
							&nbsp;
							<button type="submit" value="Update Password" class="submitBtn" name="ProcessAction">
								<span>Update Password</span>
							</button>
							&nbsp;
							<button type="submit" value="Update Email" class="submitBtn" name="ProcessAction">
								<span>Update Email</span>
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
}

function build_account_settings_email($errors, $enteredData) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		display_errors($errors);
		?>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" name="selectionForm" action="account_settings.php" class="box">		
				<table class="default">
					<tr>
						<td></td>
						<td>
							<span class="smGD">* required entry</span>
						</td>
					</tr>
					<tr>
						<td class="titleRR">Email Address<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="text"
									name="Email"
									value="<?php echo $enteredData['Email']?>" 
									size="30" 
									maxlength="50" 
									tabindex="1">
						</td>
					</tr>
					<?php 
					if (isset($errors['Email'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Email'] ?></td></tr>
					<?php
					}
					?>
					<tr>
						<td class="titleRR">Email Notification Opt In</td>
						<td class="entryRL">
							<input type="checkbox" 
								name="Email_Opt_Capt"
								value="Y" 
								tabindex="5"
								<?php if (strstr($enteredData['Email_Opt_Capt'],"Y")) { print "checked"; } ?>
							>Ok to receive event specific email from event Captain<br/>&nbsp;&nbsp;&nbsp;&nbsp;
							or Organizer through MightyUltimate.com
							<br/>
							<input type="checkbox" 
								name="Email_Opt_MU"
								value="Y" 
								tabindex="6"
								<?php if (strstr($enteredData['Email_Opt_MU'],"Y")) { print "checked"; } ?>
							>Ok to receive general email from Mighty Ultimate Disc
						</td>
					</tr>
					<?php
					if (isset($errors['Email_Opt_Capt']) or isset($errors['Email_Opt_MU'])) {
					?>
						<tr>
							<td></td><td class="error">
								<?php echo $errors['Email_Opt_Capt']." ".$errors['Email_Opt_MU'] ?>
							</td>
						</tr>				
					<?php
					}
					?>
					<tr>
						<td colspan="2" class="dispRC">
							<button type="submit" value="Save Email" class="submitBtn" name="ProcessAction">
								<span>Save Email</span>
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
<?php
}

function build_account_settings_short_name($errors, $enteredData) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		display_errors($errors);
		?>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" name="selectionForm" action="account_settings.php" class="box">		
				<table class="default">
					<tr>
						<td></td>
						<td>
							<span class="smGD">* required entry</span>
						</td>
					</tr>
					<tr>
						<td class="titleRR">New Player ID<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="text"
									name="Short_Name"
									value="<?php echo $enteredData['Short_Name']?>" 
									size="30" 
									maxlength="30" 
									tabindex="1">
						</td>
					</tr>
					<?php 
					if (isset($errors['Short_Name'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Short_Name'] ?></td></tr>
					<?php
					}
					?>
					<tr>
						<td colspan="2" class="dispRC">
							<button type="submit" value="Save ID" class="submitBtn" name="ProcessAction">
								<span>Save ID</span>
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
<?php
}

function build_account_settings_password($errors, $enteredData) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		display_errors($errors);
		?>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" name="selectionForm" action="account_settings.php" class="box">
				<input type="hidden" name="Short_Name" value="<?php echo $enteredData['Short_Name'] ?>">		
				<table class="default">
					<tr>
						<td></td>
						<td>
							<span class="smGD">* required entry</span>
						</td>
					</tr>
					<tr>
						<td class="titleRR">Old Password<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="password" 
									name="passwordOld"
									value="" 
									size="30" 
									maxlength="30" 
									tabindex="2">
						</td>
					</tr>
					<?php 
					if (isset($errors['passwordOld'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['passwordOld'] ?></td></tr>
					<?php
					}
					?>
					<tr>
						<td class="titleRR">New Password<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="password" 
									name="Password"
									value="" 
									size="30" 
									maxlength="30" 
									tabindex="3">
							<a href="#">
								<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Passwords must be between 6-13 characters in length." />
							</a>
						</td>
					</tr>
					<?php 
					if (isset($errors['Password'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Password'] ?></td></tr>
					<?php
					}
					?>
					<tr>
						<td class="titleRR">Retype New Password<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="password" 
									name="password2"
									value="" 
									size="30" 
									maxlength="30" 
									tabindex="4">
						</td>
					</tr>
					<?php 
					if (isset($errors['password2'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['password2'] ?></td></tr>
					<?php
					}
					?>
					<tr>
						<td colspan="2" class="dispRC">
							<button type="submit" value="Save Password" class="submitBtn" name="ProcessAction">
								<span>Save Password</span>
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
<?php
}

display_footer_wrapper();
?>