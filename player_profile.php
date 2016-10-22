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
	$processAction = "";
	$enteredData = array();
	$errors = array();
	$playerExists = false; /** does the player exist, key to what sql we run: insert vs. update */

	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	if ($processAction == "Continue Registration") {
		$action = "playerSave";
	} else if ($processAction == "Save Profile") {
		$action = "playerSave";
	} else if ($processAction == "Edit Profile") {
		$action = "playerEdit";
	} else if ($processAction == "Cancel") {
		$action = "playerCancel";
	} else {
		$action = "routeThis";
	}
	
	$playerID = get_session_player_id();
	/** check if there is existing player info */
	if ($enteredData = get_player_profile($playerID)) {
		$playerExists = true;
	}
	
	if ($action == "playerSave") { /** user is in edit mode and is posting data to db */
		$enteredData = get_data_entered($_POST);
		$errors = validate($action, $enteredData);

		if (!empty($errors)){
			$action = "playerEdit";
			build_player_profile_page($errors, $playerExists, $enteredData, $action);
		} else {
			if (!$playerExists) {
				$result = insert_player_profile($playerID, $enteredData);
			} else {
				$result = update_player_profile($playerID, $enteredData);
			}
			if (!$result) {
				log_entry(Logger::PLAYP,Logger::ERROR,0,$playerID,
					"Failed to save player profile.");
				$errors = 
					error_add($errors, "An error occurred while saving your profile. Please try again later.");
				$action = "playerEdit";
				build_player_profile_page($errors, $playerExists, $enteredData, $action);
			} else {					
				/** if player is in process of registering for an event, 
				 *  send them to the registration page */
				if (get_session_event_register()) {
					redirect_page("register.php");
				} else {
					/** return user to profile page in read mode **/  
					$playerExists = true;
					$action = "routeThis";
					build_player_profile_page($errors, $playerExists, $enteredData, $action);
				}
			}
		}
	} else if ($action == "playerEdit"){
		build_player_profile_page($errors, $playerExists, $enteredData, $action);
	} else if ($action == "playerCancel"){
		/** if player is registering for event, clear the registration event session vars */
		unset_session_event_register();
		redirect_page("player_profile.php");
	} else { /** user is entering profile page for first time or is changing from display to edit mode */
		build_player_profile_page($errors, $playerExists, $enteredData, $action);
	}
} else {
	display_non_authorization();
}

function build_player_profile_page($errors, $playerExists, $enteredData, $action) {
	display_wrappers();
?>
	<div id="content_wrapper">		
	<?php
	/** for players who are creating/editing a profile and signing up for an event */
	if(get_session_event_register()) { 
		if($playerExists) {
	?>
			<p>As part of the registration process, please review and 
			update your Player Profile.</p><br/>
		<?php
		} else {
		?>
			<p>As part of the registration process, please complete 
			your Player Profile.</p><br/>
		<?php
		}
		editForm($errors, $enteredData);
	/** for players who are creating/editing a profile, but not signing up for anything */	
	} else {
		if ($action == "playerEdit" or !$playerExists) { 
		?>
			<p>Reminder: please make sure that your profile is complete and up to date.  This information will 
			be used to place you on a team when you	sign up	for an event.</p><br/>
		<?php
			editForm($errors, $enteredData);
		} else {
			displayForm($enteredData);
		}
	}
	?>
	</div>
	<?php
}

function editForm($errors, $enteredData) {
	display_errors($errors);
	?>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
	<form method="post" id="selectionForm" name="selectionForm" action="player_profile.php" class="box">
		<table class="default">
			<tr>
				<td></td>
				<td><span class="smGD">* required entry</span></td>
			</tr>
			<tr>
				<td class="titleRR">First Name*</td>
				<td class="entryRL">
					<input 	type="text"
							name="First_Name"
							value="<?php echo stripslashes($enteredData['First_Name'])?>" 
							size="30" 
							maxlength="50"
							tabindex="1"> 
				</td>
			</tr>
			<?php 
			if (isset($errors['First_Name'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['First_Name'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Last Name*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="Last_Name"
							value="<?php echo stripslashes($enteredData['Last_Name'])?>" 
							size="30" 
							maxlength="50" 
							tabindex="2">
				</td>
			</tr>
			<?php 
			if (isset($errors['Last_Name'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Last_Name'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Address*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="Address"
							value="<?php echo stripslashes($enteredData['Address'])?>" 
							size="30" 
							maxlength="255" 
							tabindex="3">
				</td>
			</tr>
			<?php 
			if (isset($errors['Address'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Address'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">City*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="City"
							value="<?php echo stripslashes($enteredData['City'])?>" 
							size="30" 
							maxlength="50" 
							tabindex="4">
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
				<td class="titleRR">State*</td>
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
				<td class="titleRR">Zip Code*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="Post_Code"
							value="<?php echo $enteredData['Post_Code']?>" 
							size="30" 
							maxlength="5" 
							tabindex="6">
				</td>
			</tr>
			<?php 
			if (isset($errors['Post_Code'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Post_Code'] ?></td></tr>				
			<?php 
			}
			?>
			<tr>
				<td class="titleRR">Country*</td>
				<td class="entryRL">
					<select name="Country" size="1" tabindex="7">
						<option value="">Please select</option>
					<?php	
					$countriesResult = get_countries();
					while ($row=mysql_fetch_array($countriesResult)) {
						$countryCode = $row["Code"];
						$countryName = $row["Name"];
						if ($enteredData['Country']== "") {
							$selected = ($countryCode == "US") ? "selected" : "";
						} else {
							$selected = ($enteredData['Country'] == $countryCode) ? "selected" : "";
						}
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
				<td class="titleRR">Home Phone*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="H_Phone"
							value="<?php echo $enteredData['H_Phone']?>" 
							size="30" 
							maxlength="13" 
							onkeydown="javascript:backspacerDOWN(this,event);" 
							onkeyup="javascript:backspacerUP(this,event);"
							tabindex="8">
				</td>
			</tr>
			<?php 
			if (isset($errors['H_Phone'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['H_Phone'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Cell Phone</td>
				<td class="entryRL">
					<input 	type="text" 
							name="C_Phone"
							value="<?php echo $enteredData['C_Phone']?>" 
							size="30" 
							maxlength="20" 
							onkeydown="javascript:backspacerDOWN(this,event);" 
							onkeyup="javascript:backspacerUP(this,event);"
							tabindex="9">
				</td>
			</tr>
			<?php 
			if (isset($errors['C_Phone'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['C_Phone'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Work Phone</td>
				<td class="entryRL">
					<input 	type="text" 
							name="W_Phone"
							value="<?php echo $enteredData['W_Phone']?>" 
							size="30" 
							maxlength="20" 
							onkeydown="javascript:backspacerDOWN(this,event);" 
							onkeyup="javascript:backspacerUP(this,event);"
							tabindex="10">
				</td>
			</tr>
			<?php 
			if (isset($errors['W_Phone'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['W_Phone'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Emergency Contact Name*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="E_Contact_Name"
							value="<?php echo stripslashes($enteredData['E_Contact_Name'])?>" 
							size="30" 
							maxlength="50" 
							tabindex="11">
				</td>
			</tr>
			<?php 
			if (isset($errors['E_Contact_Name'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['E_Contact_Name'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Emergency Contact Phone*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="E_Contact_Phone"
							value="<?php echo $enteredData['E_Contact_Phone']?>" 
							size="30" 
							maxlength="20" 
							onkeydown="javascript:backspacerDOWN(this,event);" 
							onkeyup="javascript:backspacerUP(this,event);"
							tabindex="12">
				</td>
			</tr>
			<?php 
			if (isset($errors['E_Contact_Phone'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['E_Contact_Phone'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Gender*</td>
				<td class="entryRL">
					<input type="radio" 
						name="Gender" 
						tabindex="13" 
						value="M"
						<?php if (strstr($enteredData['Gender'],"M")) { 
							print "checked"; } ?>>Male
					<input type="radio" 
						name="Gender" 
						tabindex="13" 
						value="F"
						<?php if (strstr($enteredData['Gender'],"F")) { 
							print "checked"; } ?>>Female
				</td>
			</tr>
			<?php 
			if (isset($errors['Gender'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Gender'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Height*</td>
				<td class="entryRL">
					<select	name="Height" size="1" tabindex="14">
						<option value="">Please select...
						<option value="1" 
							<?php if ($enteredData['Height'] == "1") { print "selected"; } ?>
							>5'0" and under</option>
						<option value="2" 
							<?php if ($enteredData['Height'] == "2") { print "selected"; } ?>
							>5'1" - 5'4"</option>
						<option value="3" 
							<?php if ($enteredData['Height'] == "3") { print "selected"; } ?>
							>5'5" - 5'8"</option>
						<option value="4" 
							<?php if ($enteredData['Height'] == "4") { print "selected"; } ?>
							>5'9" - 6'0"</option>
						<option value="5" 
							<?php if ($enteredData['Height'] == "5") { print "selected"; } ?>
							>6'1" and higher</option>
					</select>
				</td>
			</tr>
			<?php 
			if (isset($errors['Height'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Height'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Physical Condition*</td>
				<td class="entryRL">
					<select	name="Conditionx" size="1" tabindex="15">
						<option value="">Please select...
						<option value="1" 
							<?php if ($enteredData['Conditionx'] == "1") { 
								print "selected"; } ?>
							>Turtle - can't run fast or for very long</option>
						<option value="2" 
							<?php if ($enteredData['Conditionx'] == "2") { 
								print "selected"; } ?>
							>Elephant - can run slow for a long time</option>
						<option value="3" 
							<?php if ($enteredData['Conditionx'] == "3") { 
								print "selected"; } ?>
							>Rabbit - can run fast but not for very long</option>
						<option value="4" 
							<?php if ($enteredData['Conditionx'] == "4") { 
								print "selected"; } ?>
							>Gazelle - can run and run and run</option>
						</select>
				</td>
			</tr>
			<?php 
			if (isset($errors['Conditionx'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Conditionx'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Handling Skills*</td>
				<td class="entryRL">
					<select	name="Skill_Lvl" size="1" tabindex="16">
						<option value="">Please select...
						<option value="1" 
							<?php if ($enteredData['Skill_Lvl'] == "1") { 
								print "selected"; } ?>
							>Newbie at throwing and catching</option>
						<option value="2" 
							<?php if ($enteredData['Skill_Lvl'] == "2") { 
								print "selected"; } ?>
							>Solid backhand or forehand</option>
						<option value="3" 
							<?php if ($enteredData['Skill_Lvl'] == "3") { 
								print "selected"; } ?>
							>Solid backhand and forehand</option>
						<option value="4" 
							<?php if ($enteredData['Skill_Lvl'] == "4") { 
								print "selected"; } ?>
							>Can handle vs. zone defense</option>
						<option value="5" 
							<?php if ($enteredData['Skill_Lvl'] == "5") { 
								print "selected"; } ?>
							>Franchise handler</option>
						</select>
				</td>
			</tr>
			<?php 
			if (isset($errors['Skill_Lvl'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Skill_Lvl'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Defensive Skills*</td>
				<td class="entryRL">
					<select	name="Skill_Lvl_Def" size="1" tabindex="17">
						<option value="">Please select...
						<option value="1" 
							<?php if ($enteredData['Skill_Lvl_Def'] == "1") { 
								print "selected"; } ?>
							>You can play defense in ultimate?</option>
						<option value="2" 
							<?php if ($enteredData['Skill_Lvl_Def'] == "2") { 
								print "selected"; } ?>
							>Can play man D, learning finer points</option>
						<option value="3" 
							<?php if ($enteredData['Skill_Lvl_Def'] == "3") { 
								print "selected"; } ?>
							>Comfortable playing man D, learning zone D</option>
						<option value="4" 
							<?php if ($enteredData['Skill_Lvl_Def'] == "4") { 
								print "selected"; } ?>
							>Comfortable playing man or zone D</option>
						<option value="5" 
							<?php if ($enteredData['Skill_Lvl_Def'] == "5") { 
								print "selected"; } ?>
							>Gonzo defensive specialist</option>
						</select>
				</td>
			</tr>
			<?php 
			if (isset($errors['Skill_Lvl_Def'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Skill_Lvl_Def'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Play Level*</td>
				<td class="entryRL">
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="1" 
						tabindex="18"
						<?php 
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "1");
								if ($temp !== false) {
									echo "checked";	}}} ?>>Never played at all<br/>
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="2" 
						tabindex="18"
						<?php 
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "2");
								if ($temp !== false) {
									echo "checked";	}}} ?>>Pickup<br/>
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="3" 
						tabindex="18"
						<?php 
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "3");
								if ($temp !== false) {
									echo "checked";	}}} ?>>High School<br/>
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="4" 
						tabindex="18"
						<?php 
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "4");
								if ($temp !== false) {
									echo "checked";	}}} ?>>College<br/>
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="5" 
						tabindex="18"
						<?php
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "5");
								if ($temp !== false) {
									echo "checked";	}}} ?>>Club (Open, Women, or Mixed)<br/> 
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="6" 
						tabindex="18"
						<?php 
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "6");
								if ($temp !== false) {
									echo "checked";	}}} ?>>Masters<br/>
					<input type="checkbox" 
						name="Play_Lvl[]"
						value="7" 
						tabindex="18"
						<?php
						if(isset($enteredData['Play_Lvl'])) {
							if (check_value_is_set($enteredData['Play_Lvl'])) {
								$temp = strstr(is_string($enteredData['Play_Lvl']) ? 
									$enteredData['Play_Lvl'] : implode(',',$enteredData['Play_Lvl']), "7");
								if ($temp !== false) {
									echo "checked";	}}} ?>>League<br/> 
				</td>
			</tr>
			<?php 
			if (isset($errors['Play_Lvl'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Play_Lvl'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Years Experience*</td>
				<td class="entryRL">
					<select	name="Yr_Exp" size="1" tabindex="19">
						<option value="">Please select...
						<option value="<1" 
							<?php if ($enteredData['Yr_Exp'] == "<1") { 
								print "selected"; } ?>
							>Less than 1</option>
						<option value="1-4" 
							<?php if ($enteredData['Yr_Exp'] == "1-4") { 
								print "selected"; } ?>
							>1-4</option>
						<option value="5-9" 
							<?php if ($enteredData['Yr_Exp'] == "5-9") { 
								print "selected"; } ?>
							>5-9</option>
						<option value="10-14" 
							<?php if ($enteredData['Yr_Exp'] == "10-14") { 
								print "selected"; } ?>
							>10-14</option>
						<option value="15-19" 
							<?php if ($enteredData['Yr_Exp'] == "15-19") { 
								print "selected"; } ?>
							>15-19</option>
						<option value="20+" 
							<?php if ($enteredData['Yr_Exp'] == "20+") { 
								print "selected"; } ?>
							>20+</option>
					</select>
				</td>
			</tr>
			<?php 
			if (isset($errors['Yr_Exp'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Yr_Exp'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">T Shirt Size*</td>
				<td class="entryRL">
					<input type="radio" 
						name="T_Shirt_Size" 
						tabindex="20" 
						value="S"
						<?php 
						if (isset($enteredData['T_Shirt_Size'])) {
							if (strstr($enteredData['T_Shirt_Size'],"S")) { 
								print "checked"; }} ?>>Small
					<input type="radio" 
						name="T_Shirt_Size" 
						tabindex="20" 
						value="M"
						<?php
						if (isset($enteredData['T_Shirt_Size'])) {
							if (strstr($enteredData['T_Shirt_Size'],"M")) { 
								print "checked"; }} ?>>Medium
					<input type="radio" 
						name="T_Shirt_Size" 
						tabindex="20" 
						value="L"
						<?php
						if (isset($enteredData['T_Shirt_Size'])) {
							if (strstr($enteredData['T_Shirt_Size'],"L")) { 
								print "checked"; }} ?>>Large
					<input type="radio" 
						name="T_Shirt_Size" 
						tabindex="20" 
						value="XL" 
						<?php
						if (isset($enteredData['T_Shirt_Size'])) {
							if (strstr($enteredData['T_Shirt_Size'],"XL")) { 
								print "checked"; }} ?>>Xtra Large
				</td>
			</tr>
			<?php 
			if (isset($errors['T_Shirt_Size'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['T_Shirt_Size'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">If your spouse / lover / partner will be playing in this 
				league, enter their name to be on the same team</td>
				<td class="entryRL">
					<input 	type="text" 
							name="Buddy_Name"
							value="<?php echo stripslashes($enteredData['Buddy_Name'])?>" 
							size="30" 
							maxlength="50" 
							tabindex="21">
				</td>
			</tr>
			<?php 
			if (isset($errors['Buddy_Name'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Buddy_Name'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Current UPA Member?*</td>
				<td class="entryRL">
					<input type="radio" 
							name="UPA_Cur_Member"
							value="Y" 
							tabindex="22"
							<?php
							if (isset($enteredData['UPA_Cur_Member'])) {
								if (strstr($enteredData['UPA_Cur_Member'],"Y")) { 
								print "checked"; }} ?>>Yes
					<input type="radio" 
						name="UPA_Cur_Member" 
						value="N" 
						tabindex="22"
						<?php
						if (isset($enteredData['UPA_Cur_Member'])) {
							if (strstr($enteredData['UPA_Cur_Member'],"N")) { 
								print "checked"; }} ?>>No
				</td>
			</tr>
			<?php 
			if (isset($errors['UPA_Cur_Member'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['UPA_Cur_Member'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">UPA Number or<br/>Last 4 Digits of SSN*</td>
				<td class="entryRL">
					<input 	type="text" 
							name="UPA_Number"
							value="<?php echo stripslashes($enteredData['UPA_Number'])?>" 
							size="30" 
							maxlength="30" 
							tabindex="23">
				</td>
			</tr>
			<?php 
			if (isset($errors['UPA_Number'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['UPA_Number'] ?></td></tr>				
			<?php			
			}
			
			$thisVal = 
				(isset($enteredData['Student']) ? (strstr($enteredData['Student'],"Y") ? "checked" : "") : ""); 
			?>
			<tr>
				<td class="titleRR">Student?</td>
				<td class="entryRL">
					<input type="checkbox" 
							name="Student"
							value="Y" 
							tabindex="24"
							<?php echo $thisVal; ?>>Yes
				</td>
			</tr>
			<?php 
			if (isset($errors['Student'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Student'] ?></td></tr>				
			<?php			
			}
			?>
			<tr>
				<td class="titleRR">Are you over 18 years old?*</td>
				<td class="entryRL">
					<input type="radio" 
							name="Over18"
							value="Y" 
							tabindex="25"
							<?php
							if (isset($enteredData['Over18'])) {
								if (strstr($enteredData['Over18'],"Y")) { 
									print "checked"; }} ?>>Yes
					<input type="radio" 
							name="Over18"
							value="N" 
							tabindex="25"
							<?php
							if (isset($enteredData['Over18'])) {
								if (strstr($enteredData['Over18'],"N")) { 
									print "checked"; }} ?>>No
				</td>
			</tr>
			<?php 
			if (isset($errors['Over18'])) {
			?>
				<tr><td></td><td class="error"><?php echo $errors['Over18'] ?></td></tr>				
			<?php			
			}
			?>
			<?php
			if (get_session_event_register()) {
				$buttonLabel = "Continue Registration";
			} else {
				$buttonLabel = "Save Profile";
			}
			?>
			<tr>
				<td colspan="2" class="dispRC">
					<button type="submit" value="<?php print $buttonLabel ?>" class="submitBtn" 
						name="ProcessAction">
						<span><?php print $buttonLabel ?></span>
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
<?php
}

function displayForm($enteredData) {
?>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
	<form method="post" id="selectionForm" name="selectionForm" action="player_profile.php" class="box">
		<table class="default">
			<tr>
				<th colspan="2" scope="col" class="dispRL">Player Contact Information</th>
			</tr>
			<tr>
				<td class="titleRR">Name</td>
				<td class="entryRL">
				<?php echo stripslashes($enteredData['First_Name'])." ".stripslashes($enteredData['Last_Name']); ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Address</td>
				<td class="entryRL">
				<?php echo stripslashes($enteredData['Address']); ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">City / State / Zip Code / Country</td>
				<td class="entryRL">
				<?php 
				$countriesResult = get_countries();
				$thisDisplay = "";
				while ($row=mysql_fetch_array($countriesResult)) {
					if ($enteredData['Country'] == $row["Code"]) {
						$thisDisplay = $row["Name"];	
						break;
					}
				} 
				echo stripslashes($enteredData['City']).", ".$enteredData['State_Prov']."&nbsp;&nbsp;".$enteredData['Post_Code']."&nbsp;&nbsp;- ".$thisDisplay;	?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Home Phone</td>
				<td class="entryRL">
				<?php echo $enteredData['H_Phone'];	?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Cell Phone</td>
				<td class="entryRL">
				<?php echo $enteredData['C_Phone'];	?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Work Phone</td>
				<td class="entryRL">
				<?php echo $enteredData['W_Phone'];	?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Emergency Contact Name</td>
				<td class="entryRL">
				<?php echo stripslashes($enteredData['E_Contact_Name']); ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Emergency Contact Phone</td>
				<td class="entryRL">
				<?php echo $enteredData['E_Contact_Phone'];	?>
				</td>
			</tr>
		</table>
		<table class="default">
			<tr>
				<th colspan="2" scope="col" class="dispRL">Player Essentials</th>
			</tr>
			<tr>
				<td class="titleRR">Gender</td>
				<td class="entryRL">
				<?php 
				if (strstr($enteredData['Gender'],"M")) { 
					echo "Male"; 
				} else if (strstr($enteredData['Gender'],"F")) {
					echo "Female";
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Height</td>
				<td class="entryRL">
				<?php 
				switch ($enteredData['Height']){ 
					case "1":
					echo "5'0\" and under";
					break;
					case "2":
					echo "5'1\" - 5'4\"";
					break;
					case "3":
					echo "5'5\" - 5'8\"";
					break;
					case "4":
					echo "5'9\" - 6'0\"";
					break;
					case "5":
					echo "6'1\" and higher";
					break;
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Physical Condition</td>
				<td class="entryRL">
				<?php 
				switch ($enteredData['Conditionx']){ 
					case "1":
					echo "Turtle - can't run fast or for very long";
					break;
					case "2":
					echo "Elephant - can run slow for a long time";
					break;
					case "3":
					echo "Rabbit - can run fast but not for very long";
					break;
					case "4":
					echo "Gazelle - can run and run and run";
					break;
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Handling Skills</td>
				<td class="entryRL">
				<?php 
				switch ($enteredData['Skill_Lvl']){ 
					case "1":
					echo "Newbie at throwing and catching";
					break;
					case "2":
					echo "Solid backhand or forehand";
					break;
					case "3":
					echo "Solid backhand and forehand";
					break;
					case "4":
					echo "Can handle vs. zone defense";
					break;
					case "5":
					echo "Franchise handler";
					break;
				} 
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Defensive Skills</td>
				<td class="entryRL">
				<?php 
				switch ($enteredData['Skill_Lvl_Def']){ 
					case "1":
					echo "You can play defense in ultimate?";
					break;
					case "2":
					echo "Can play man D, learning finer points";
					break;
					case "3":
					echo "Comfortable playing man D, learning zone D";
					break;
					case "4":
					echo "Comfortable playing man or zone D";
					break;
					case "5":
					echo "Gonzo defensive specialist";
					break;
				} 
				?>
				</td>
			</tr>			
			<tr>
				<td class="titleRR">Play Level</td>
				<td class="entryRL">
				<?php
				if (is_array($enteredData['Play_Lvl'])) {
					$tempArr = $enteredData['Play_Lvl'];
				} else { 
					$tempArr =	explode(",", $enteredData['Play_Lvl']);
				}
				foreach ($tempArr as &$thisVal) {
					switch ($thisVal){ 
						case "1":
						echo "Never played at all<br/>";
						break;
						case "2":
						echo "Pickup<br/>";
						break;
						case "3":
						echo "High School<br/>";
						break;
						case "4":
						echo "College<br/>";
						break;
						case "5":
						echo "Club (Open, Women, or Mixed)<br/>";
						break;
						case "6":
						echo "Masters<br/>";
						break;
						case "7":
						echo "League<br/>";
						break;
					}
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Years Experience</td>
				<td class="entryRL">
				<?php 
				switch ($enteredData['Yr_Exp']){
					case "<1":
					echo "Less than 1";
					break;
					case "1-4":
					echo "1-4";
					break;
					case "5-9":
					echo "5-9";
					break;
					case "10-14":
					echo "10-14";
					break;
					case "15-19":
					echo "15-19";
					break;
					case "20+":
					echo "20+";
					break;
				} 
				?>
				</td>
			</tr>
		</table>
		<table class="default">
			<tr>
				<th colspan="2" scope="col" class="dispRL">Player Miscellany</th>
			</tr>
			<tr>
				<td class="titleRR">T Shirt Size</td>
				<td class="entryRL">
				<?php
				if (strstr($enteredData['T_Shirt_Size'],"S")) {
					echo "Small";
				} else if (strstr($enteredData['T_Shirt_Size'],"M")) {
					echo "Medium";
				} else if (strstr($enteredData['T_Shirt_Size'],"L")) {
					echo "Large";
				} else if (strstr($enteredData['T_Shirt_Size'],"XL")) {
					echo "Xtra Large";
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">
					If your spouse / lover / partner will be playing in this event, 
					enter their name to be on the same team</td>
				<td class="entryRL">
				<?php echo stripslashes($enteredData['Buddy_Name']); ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Current UPA Member?</td>
				<td class="entryRL">
				<?php 
				if (strstr($enteredData['UPA_Cur_Member'],"Y")) {
					echo "Yes";
				} else if (strstr($enteredData['UPA_Cur_Member'],"N")) {
					echo "No";
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">UPA Number or Last 4 Digits of SSN</td>
				<td class="entryRL">
				<?php echo stripslashes($enteredData['UPA_Number']); ?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Student?</td>
				<td class="entryRL">
				<?php
				$thisVal = 
					(isset($enteredData['Student']) ? (strstr($enteredData['Student'],"Y") ? "Yes" : "No") : "No"); 
					echo $thisVal;
				?>
				</td>
			</tr>
			<tr>
				<td class="titleRR">Are you under 18 years old?</td>
				<td class="entryRL">
				<?php
				if (strstr($enteredData['Over18'],"Y")) {
					echo "Yes";
				} else if (strstr($enteredData['Over18'],"N")) {
					echo "No";
				} 
				?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="dispRC">
					<button type="submit" value="Edit Profile" class="submitBtn" name="ProcessAction">
						<span>Edit Profile</span>
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