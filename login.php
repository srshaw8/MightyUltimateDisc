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
include_relative("utility_legal_functions.php");

/** variable declarations */
$action = "";
$id = "";
$processAction = "";
$actionResult = "";
$source = "";
$firstName = "";
$lastName = "";
$playerID = 0;
$enteredData = array();
$errors = array();

if (isset($_REQUEST['a'])) {
	$action = $_REQUEST['a'];
} else {
	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	if ($processAction == "New Member") { /** from general page */
		$action = "newMember";
	} else if ($processAction == "Create Account") { /** from login page */
		$action = "newAccount";
	} else if ($processAction == "Continue Sign Up" or $processAction == "Home") { /** from login after new acct */
		$action = "continueRegister";
	} else if ($processAction == "Create New Password") { /** from general page */
		$action = "forgetPassword";
	} else if ($processAction == "Recover Player ID") { /** from general page */
		$action = "forgetPlayerID";
	} else if ($processAction == "Update Account") { /** from login page */
		$action = "updateTerms";
	}
	$source = "page"; /** user submitted from page */		
}

if ($source <> "") {
	$enteredData = get_data_entered($_POST);
	$errors = validate($action, $enteredData);
}
	
if ($action == "newMember") {
	build_new_account_page($errors, $actionResult);
} else if ($action == "newAccount") {
	if (!empty($errors)) {
		build_new_account_page($errors, $actionResult);
	} else {
		if (!insert_player_account($enteredData)) {
			log_entry(Logger::LOGIN,Logger::ERROR,0,0,"Could not create a new player account.");
			$errors = error_add($errors, "An error occurred while registering you. Please try again later.");
			build_new_account_page($errors, $actionResult);
		} else {
			$playerID=get_player_id($enteredData['Short_Name'],$enteredData['Password']);
			$thisRole = "player"; /** since this is a new player, they're likely not an admin */
			$firstName = ""; /** and we don't know their name since they haven't created a profile */
			set_session_player($playerArray = array($playerID, $thisRole, $firstName));
			sendEmail(EmailWrapper::NEW_ACCT,0,0,array($playerID));
			log_entry(Logger::LOGIN,Logger::INFO,0,$playerID,
				"A new account was created for ".$enteredData['Short_Name'].".");
			$actionResult = "successfulNewAcct";
			build_new_account_page($errors, $actionResult);
		}
	}
} else if ($action == "continueRegister") {
	/** send user to player info page to update their profile if they are registering for event */
	if (get_session_event_register()) {
		$goHere = "player_profile.php";
	} else {
		$goHere = "index.php";
	}
	redirect_page($goHere);
} else if ($action == "forgetPassword" or $action == "forgetPlayerID") {
	if ($source <> "") {
		if (!empty($errors)) {
			build_forget_login_page($errors, $action);
		} else {
			if ($action == "forgetPassword") {
				if (!sendEmail(EmailWrapper::FORGOT_PW,0,0,array($enteredData['Email']))) {
					log_entry(Logger::LOGIN,Logger::ERROR,0,0,
						"Password reset failed with email ".$enteredData['Email'].".");
					$errors = 
						error_add($errors, "An error occurred while resetting your password. Please try again.");
				} else {
					log_entry(Logger::LOGIN,Logger::INFO,0,0,
						"A new password was sent to ".$enteredData['Email'].".");
				}
			}
			if ($action == "forgetPlayerID") {
				if (!sendEmail(EmailWrapper::FORGOT_ID,0,0,array($enteredData['Email']))) {
					log_entry(Logger::LOGIN,Logger::ERROR,0,0,
						"Retrieval of player ID failed with email: ".$enteredData['Email']);
					$errors = 
						error_add($errors, "An error occurred while getting your player ID.	Please try again.");
				} else {
					log_entry(Logger::LOGIN,Logger::INFO,0,0,
						"A new player ID was sent to ".$enteredData['Email'].".");
				}
			}
			if (!empty($errors)) {
				build_forget_login_page($errors, $action);
			} else {
				if ($action == "forgetPassword") {
					$actionResult = "forgetPassword";
				} else if ($action == "forgetPlayerID") {	
					$actionResult = "forgetPlayerID";
				}
				build_login_page($errors, $actionResult);
			}
		}
	} else {
		build_forget_login_page($errors, $action);
	}
} else if ($action == "terms") {
	build_login_terms_page($errors, get_session_tmpShortName()); 
} else if ($action == "updateTerms") {
	if (!empty($errors)) {
		build_login_terms_page($errors, get_session_tmpShortName());
	} else {
		$playerID=get_player_id($enteredData['Short_Name'],$enteredData['Password']);
		if(check_value_is_number($playerID)) {
			if (update_player_account($playerID, $enteredData)) {
				$adminRole = (check_admin_role($playerID)) ? "Admin" : "";
				$rsPlayer = get_player_profile_short($playerID);
				if ($rsPlayer) {
					$firstName = $rsPlayer["First_Name"];
					$lastName =  $rsPlayer["Last_Name"];
					log_entry(Logger::LOGIN,Logger::INFO,0,$playerID,$firstName." ".$lastName." logged in.");
				} else {
					log_entry(Logger::LOGIN,Logger::WARN,0,$playerID,"Could not retrieve player name.");
				}
				set_session_player($playerArray = array($playerID, $adminRole, $firstName));
				/** send user to player info page to update their profile if they are registering for event */
				if (get_session_event_register()) {
					$goHere = "player_profile.php";
				} else {
					$goHere = "index.php";
				}
				redirect_page($goHere);
			} else {
				log_entry(Logger::LOGIN,Logger::ERROR,0,$playerID,"Could not update player account.");
				$errors = error_add($errors, "Your player account could not be updated. Please try again.");
				build_login_terms_page($errors, $id);
			}
		} else {
			log_entry(Logger::LOGIN,Logger::ERROR,0,$playerID,"Could not retrieve player ID.");
			$errors = error_add($errors, "Your player ID could not be retrieved. Please try again.");
			build_login_terms_page($errors, $id);
		}
	}
} else if ($action == "" & check_authorization()) {
	redirect_page("index.php");
} else {
	build_login_page($errors, $actionResult);
}

function build_login_page($errors, $actionResult) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		display_errors($errors);
		
		if ($actionResult == "") {
			if (check_authorization()) {
				$displayText = "Great! You're logged in.";
			} else {
				$displayText = "Please login by entering your player ID and password in the Login fields 
								to the left.  Or, if you haven't yet created an account with Mighty Ultimate Disc, 
								click on the 'New Member' button to the left.";				
			}
		?>
			<p><?php echo $displayText ?></p>
		<?php
		} else {
			$displayText = "";
			if ($actionResult == "forgetPassword") {
				$displayText = "A new password for your account has been mailed to your registered email address."; 
			} else if ($actionResult == "forgetPlayerID") {
				$displayText = "Your player ID has been mailed to your registered email address.";
			}	
			?>			
			<p><?php echo $displayText ?></p>
			<?php
			$label = "Home";
			?>
			<form method="post" name="selectionForm" action="login.php">
				<table class="defaultG">
					<tr>
						<td class="dispRC">
							<button type="submit" value="<?php echo $label ?>" class="submitBtn" 
								name="ProcessAction">
								<span><?php echo $label ?></span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		<?php
		}
		?>
	</div>
<?php
}

function build_new_account_page($errors, $actionResult) {
	display_wrappers();
	?>
	<div id="content_wrapper">
	<?php
		display_errors($errors);
		
		if ($actionResult == "") {
		?>
			<div id="xsnazzy">
			<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
			<div class="xboxcontent">
				<form method="post" name="selectionForm" action="login.php" class="box">
					<table class="default">
						<tr>
							<td class="titleRR">Player ID</td>
							<td class="entryRL">
								<input 	type="text"
									name="Short_Name"
									value="<?php echo (isset($_POST['Short_Name'])) ? $_POST['Short_Name'] : "";?>" 
									size="30" 
									maxlength="20" 
									tabindex="1">
								<a href="#">
									<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Player ID must be between 6-20 characters in length." />
								</a>
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
							<td class="titleRR">Email</td>
							<td class="entryRL">
								<input 	type="text"
										name="Email"
										value="<?php echo (isset($_POST['Email'])) ? $_POST['Email'] : ""; ?>" 
										size="30" 
										maxlength="50" 
										tabindex="2">
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
							<td class="titleRR">Password</td>
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
							<td class="titleRR">Retype Password</td>
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
							<td class="titleRR">Default Email Notifications</td>
							<td class="entryRL">
								Please note that MightyUltimate.com is designed to automatically send you email 
								notifications based on the result of your activity while registering for a league 
								or hat tournament.  Please review the 
								<a href="<?php echo LOCATION_SITE ?>privacy.php">Privacy Policy</a> for full details.
							</td>
						</tr>
						<tr>
							<td class="titleRR">Opt In Email Notification</td>
							<td class="entryRL">
								<input type="checkbox" 
									name="Email_Opt_Capt"
									value="Y" 
									tabindex="5"
									<?php
									$emailOptCapt=(isset($_POST['Email_Opt_Capt'])) ? $_POST['Email_Opt_Capt'] : "";
									if (strstr($emailOptCapt,"Y")) { echo "checked"; }
									?>
								>Ok to receive event related email from event Captain or organizer through MightyUltimate.com
								<br/>
								<input type="checkbox" 
									name="Email_Opt_MU"
									value="Y" 
									tabindex="6"
									<?php
									$emailOptMU = (isset($_POST['Email_Opt_MU'])) ? $_POST['Email_Opt_MU'] : "";
									if (strstr($emailOptMU,"Y")) { echo "checked"; }
									?>
								>Ok to receive general email from Mighty Ultimate Disc
							</td>
						</tr>
						<?php
						if (isset($errors['Email_Opt_Capt']) or isset($errors['Email_Opt_MU'])) {
						?>
							<tr><td></td><td class="error">
								<?php echo $errors['Email_Opt_Capt']." ".$errors['Email_Opt_MU'] ?>
							</td></tr>				
						<?php
						}
						?>
						<tr>
							<td class="entryRL" colspan="2">
								<textarea cols ="70" rows = "8"	readonly>
<?php echo get_terms_of_use(); ?>
								</textarea>
							</td>
						</tr>
						<tr>
							<td class="titleRR">Terms of Use</td>
							<td class="entryRL">
								<input type="checkbox" 
									name="Terms"
									value="Y" 
									tabindex="7"
									<?php
									$terms = (isset($_POST['Terms'])) ? $_POST['Terms'] : "";
									if (strstr($terms,"Y")) { echo "checked"; }
									?>
									>I accept the Terms of Use
							</td>
						</tr>
						<?php
						if (isset($errors['Terms'])) {
						?>
							<tr><td></td><td class="error"><?php echo $errors['Terms'] ?></td></tr>				
						<?php
						}
						?>
						<tr>
							<td colspan="2" class="dispRC">
								<button type="submit" value="Create Account" class="submitBtn" name="ProcessAction">
									<span>Create Account</span>
								</button>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
			</div>
		<?php
		} else if ($actionResult == "successfulNewAcct") {
			if (get_session_event_register()) {
				$label = "Continue Sign Up";
				$thisWelcome = "Your next step in the registration process will be to create a player profile. 
						You'll be directed to the player profile page once you click the 'Continue' link below.";
			} else {
				$thisURL = "<a href=\"".LOCATION_SITE."player_profile.php\">player profile</a>.";
				$thisWelcome = "Super! You're a member!... please take a moment to fill out your ".$thisURL.".";
				$label = "Home";
			}
			?>
			<p>
			Welcome to <?php echo ORG_NAME ?> !!
			<br/><br/>
			<?php echo $thisWelcome ?>
			<br/><br/>
  			Once you create your profile, you will be able to:
			<ul>
				<li>
					sign up for any ultimate frisbee league or hat tournament in your neighborhood or 
					wherever you happen to be
				</li>
				<li>
					create and manage your own league or hat tournament online
				</li>
			</ul>
			</p>
			<p>
			Play Ultimate!
			<form method="post" name="selectionForm" action="login.php">
			<table class="defaultG">
				<tr>
					<td class="dispRC">
						<button type="submit" value="<?php echo $label ?>" class="submitBtn" name="ProcessAction">
							<span><?php echo $label ?></span>
						</button>
					</td>
				</tr>
			</table>
			</form>
			</p>
		<?php
		}
		?>
	</div>
<?php
}

function build_forget_login_page($errors, $action) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		display_errors($errors);
		?>			
		<p>
		<?php
		if ($action == 'forgetPassword') {
			$label = "Create New Password"
		?>
			Please enter the email address used to create your player profile. An email with a 
			new password will be sent to this address.
		<?php
		} else {
			$label = "Recover Player ID"
		?>
			Please enter the email address used to create your player profile. An email with your 
			player ID will be sent to this address.
		<?php
		}
		?>
		<br/><br/>
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" name="selectionForm" action="login.php" class="box">
				<table class="default">
					<tr>
						<td class="titleRR">Email</td>
						<td class="entryRL">
							<input 	type="text"
									name="Email"
									value="<?php echo (isset($_POST['Email']) ? $_POST['Email'] : "")?>" 
									size="30" 
									maxlength="50" 
									tabindex="2">
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
						<td colspan="2" class="dispRC">
							<button type="submit" value="<?php echo $label ?>" class="submitBtn" 
								name="ProcessAction">
								<span><?php echo $label ?></span>
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

function build_login_terms_page($errors, $id) {
	display_wrappers();
	$shortName = (isset($_POST['Short_Name'])) ? $_POST['Short_Name'] : ((isset($id)) ? $id : "");
	$terms = (isset($_POST['Terms'])) ? $_POST['Terms'] : "";
?>
	<div id="content_wrapper">
		<p>
		As someone who had previously created a player account with MarinUltimate.org, you're being 
		directed to this page so that you can set your email notification options and review and accept 
		the Term of Use agreement.  You will only need to accept the Terms of Use once; we will not prompt 
		you for this again.  If you want to change your email notification options later, click on the 
		"Account Settings" link that displays on the left after	you log in. Thanks!
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" id="selectionForm" action="login.php" class="box">
				<table class="default">
					<tr>
						<td class="titleRR">Player ID</td>
						<td class="entryRL">
						<input 	type="text"
								name="Short_Name"
								value="<?php echo $shortName ?>" 
								size="22" 
								maxlength="30"
								tabindex= "1">
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
						<td class="titleRR">Password</td>
						<td class="entryRL">
							<input 	type="password" 
									name="Password"
									value="" 
									size="22" 
									maxlength="30" 
									tabindex= "2">
						</td>
					</tr>
					<?php 
					if (isset($errors['Password'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Password'] ?></td></tr>
					<?php			
					}
					$thisVal = 
					(isset($_POST['Email_Opt_Capt']) ? (strstr($_POST['Email_Opt_Capt'],"Y") ? "checked" : "") : "");
					$thisVal2 = 
					(isset($_POST['Email_Opt_MU']) ? (strstr($_POST['Email_Opt_MU'],"Y") ? "checked" : "") : "");
					?>	
					<tr>
						<td class="titleRR">Default Email Notifications</td>
						<td class="entryRL">
							Please note that MightyUltimate.com is designed to automatically send you email 
							notifications based on the result of your activity while registering for a league 
							or hat tournament.  Please review the 
							<a href="<?php echo LOCATION_SITE ?>privacy.php">Privacy Policy</a> for full details.
						</td>
					</tr>
					<tr>
						<td class="titleRR">Opt In Email Notification</td>
						<td class="entryRL">
							<input type="checkbox" 
								name="Email_Opt_Capt"
								value="Y" 
								tabindex="5"
								<?php echo $thisVal; ?>
							>Ok to receive event specific email from event Captain<br/>&nbsp;&nbsp;&nbsp;&nbsp;
							or Organizer through MightyUltimate.com
							<br/>
							<input type="checkbox" 
								name="Email_Opt_MU"
								value="Y" 
								tabindex="6"
								<?php echo $thisVal2; ?>
							>Ok to receive general email from Mighty Ultimate Disc
						</td>
					</tr>
					<?php
					if (isset($errors['Email_Opt_Capt']) or isset($errors['Email_Opt_MU'])) {
					?>
						<tr><td></td><td class="error">
							<?php echo $errors['Email_Opt_Capt']." ".$errors['Email_Opt_MU'] ?>
						</td></tr>				
					<?php
					}
					?>
					<tr>
						<td class="entryRL" colspan="2">
							<textarea cols ="70" rows = "8"	readonly>
<?php echo get_terms_of_use(); ?>
							</textarea>
						</td>
					</tr>
					<tr>
						<td class="titleRR">Terms of Use</td>
						<td class="entryRL">
							<input type="checkbox" 
								name="Terms"
								value="Y" 
								tabindex="7"
								<?php
								$terms = (isset($_POST['Terms'])) ? $_POST['Terms'] : "";
								if (strstr($terms,"Y")) { echo "checked"; }
								?>
								>I accept the Terms of Use
						</td>
					</tr>
					<?php
					if (isset($errors['Terms'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Terms'] ?></td></tr>				
					<?php
					}
					?>
					<tr>
						<td colspan="2" class="dispRC">
							<button type="submit" value="Update Account" class="submitBtn" 
								name="ProcessAction">
								<span>Update Account</span>
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