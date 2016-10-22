<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
/** page specific includes */
include_relative("utility_register_functions.php");

function display_wrappers($errorsSearch=array(),$homeDisp=false) { 

	/** variable declarations */
	$action = "";
	$processAction = "";
	$firstName = "";
	$lastName = "";
	$playerID = get_session_player_id();
	$goHere = "index.php";
	$arrEvents = array();
	$errors = array();
	
	/** login logic */
	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	if ($processAction == "Login") {
		$action = "login";
		$goHere = isset($_POST['Cur_Page']) ? $_POST['Cur_Page'] : "";
		$enteredData = get_data_entered($_POST);
		$errors = validate($action, $enteredData);
		if (empty($errors)){
			$playerID=get_player_id($enteredData['Short_Name'],$enteredData['Password']);
			if(check_value_is_number($playerID)) {
				/** if player had a previously created account, but without the terms agreement, then send em...*/
				if(!check_player_terms($playerID)) {
					set_session_tmpShortName($enteredData['Short_Name']);
					redirect_page("login.php?a=terms");
				} else {
					/** successful login **/
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

					/** for non-admins, load up event IDs and names to display under My Events **/
					if ($adminRole <> "Admin") {
						set_session_event_list($arrEvents = get_events_for_player($playerID));
					}
					/** send user to player info page to update their profile if they are registering for event */
					if (get_session_event_register()) {
						$goHere = "player_profile.php";
					}
					redirect_page($goHere);
				}
			} else {
				log_entry(Logger::LOGIN,Logger::ERROR,0,$playerID,"Could not retrieve player ID.");
				$errors = error_add($errors, "Your player ID could not be retrieved. Please try again.");
			}
		}
	} else if (isset($_POST['ProcessMyEvent'])) { /** from selecting event in My Event list */
		if (is_array($_POST['ProcessMyEvent'])) {
			$processMyEvent = each($_POST['ProcessMyEvent']);
			$eventID = $processMyEvent['key'];
			if ($eventID <> "" and is_numeric($eventID)) {
				if (process_event_selection($eventID,$playerID)) {
					redirect_page("event_mgmt.php");
				} else {
					log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
						"Player role in event could not be determined. This is whacky...");
					$errors = error_add($errors, "Your role in the event could not be determined. 
												Technical support has been notified.");
					clear_selected_event();
				}
			} else {
				log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
					"No event ID present after selecting event. This is whacked...");
				$errors = error_add($errors, "The event can't be retrieved.	Technical support has been notified.");
			}
		}
	}

	display_header_wrapper($homeDisp);
	display_left_side_wrapper($errors);
	display_right_side_wrapper($errorsSearch);
}

function display_header_wrapper($homeDisp=false) {
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="keywords" content="ultimate frisbee, frisbee, registration, frisbee league,	ultimate league, hat tournament">		
		<title><?php echo ORG_NAME ?></title>
		<script language="JavaScript" type="text/javascript" src="scripts/page_functions.js"></script>
		<script language="JavaScript" type="text/javascript" src="scripts/jquery/jquery-1.3.2.min.js"></script>
		<script language="JavaScript" type="text/javascript" src="scripts/cssButtonIE.js"></script>
		
		<?php
		$curPage = get_cur_page_URL();
		if (strstr($curPage, "event_mgmt")) {
		?>
			<!-- for event date/time helper: mySQL format yyyy-mm-dd -->
			<script language="JavaScript" type="text/javascript" src="scripts/datetimepicker_css.js"></script>
			<!-- for owner assignment -->
			<script language="JavaScript" type="text/javascript" src="scripts/mkSelectBox.js"></script>
			<!-- for deleting stuff -->
			<link href="styles/lert.css" rel="stylesheet" type="text/css">
			<script language="JavaScript" type="text/javascript" src="scripts/lert.js"></script>			
		<?php
		}else if (strstr($curPage, "event_home_page")) {
		?>
			<!-- for RTE -->
			<script language="JavaScript" type="text/javascript" src="scripts/cbrte/html2xhtml.js"></script>
			<script language="JavaScript" type="text/javascript" src="scripts/cbrte/richtext_compressed.js"></script>
		<?php
		}else if (strstr($curPage, "team_mgmt")) {
		?>
			<!-- for player assignment -->
			<script language="JavaScript" type="text/javascript" src="scripts/mkSelectBox.js"></script>
			<!-- for deleting stuff -->
			<link href="styles/lert.css" rel="stylesheet" type="text/css">
			<script language="JavaScript" type="text/javascript" src="scripts/lert.js"></script>
		<?php
		}
		?>		
		<link rel="stylesheet" type="text/css" href="styles/layout.css">
		<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
	</head>
	<?php
	$dispOnload = "";
	/** set field focus depending on page	
	if (strstr($curPage, "index")) { 
		$dispOnload = " onload=\"setFocus('loginForm','Short_Name');\"";
	} else if (strstr($curPage, "player_profile")) { 
		$dispOnload = " onload=\"setFocus('selectionForm','First_Name');\"";
	}
	 */
	?>
	<body<?php echo $dispOnload;?>>
		<div id="page_wrapper">
			<div id="header_wrapper">
				<div id="header_inner_wrapper">
					<div id="header_image">
						<a href="index.php" onMouseOver="window.status=''; return true">
							<img src="images/headerMU.jpg" width="421" height="74" border="0">
						</a>
					</div>
				</div>
			</div>
			<div id="navbar_wrapper">
				<div id="navbar_login_wrapper">
					<?php 
					if (check_authorization()) { 
						$firstName = get_session_player_first_name();
						($firstName <> "") ? $showName = "&nbsp;&nbsp;Hi ".$firstName."..." : $showName = "";
					?>
					<?php echo "<span class=\"reg\">".$showName."</span>" ?> 
					<a href="logout.php" onMouseOver="window.status=''; return true">Logout</a><br/>
					<?php
					}
					?>
				</div>
				<div id="navbar_menu_wrapper">
					<ul id="navbar_list">
						<li>
							<a href="index.php" onMouseOver="window.status=''; return true" 
							<?php
							if ((strstr($curPage, "index") or !strstr($curPage, "php")) and $homeDisp)  {
								echo "class=\"current\"";
							}
							?>
							><span>Home</span></a>
						</li>
						<?php
						if (check_authorization()) {
						?>
							<li>			
								<a href="event_mgmt.php?a=Create" onMouseOver="window.status=''; return true"
								<?php
								if (strstr($curPage, "a=Create")) {
									echo "class=\"current\"";
								}
								?>
								><span>Create Event</span></a>
							</li>
						<?php
						}
						?>
						<li>
							<a href="features.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "features")) {
								echo "class=\"current\"";
							}
							?>
							><span>Features</span></a>
						</li>
						<li>
							<a href="faq.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "faq")) {
								echo "class=\"current\"";
							}
							?>
							><span>FAQ</span></a>
						</li>
					</ul>
				</div>
			</div>
			<div id="header_gapper"></div>
<?php
}

function display_left_side_wrapper($errors) {
?>
	<div id="leftbar_wrapper">
	<?php
	$curPage = get_cur_page_URL();
	if (!check_authorization()) { ?>
		<div id="leftbar_inner_wrapper">
			<h3>Login</h3>
			<form method="post" id="loginForm" name="loginForm" 
				action="<?php echo substr($_SERVER["REQUEST_URI"],1); ?>">
				<input type="hidden" value="<?php echo substr($_SERVER["REQUEST_URI"],1); ?>" name="Cur_Page"> 
				<table cellspacing="0">
					<tr>
						<td class="fld">Player ID:</td>
					</tr>
					<tr>
						<td class="fld">
						<input 	type="text"
								name="Short_Name"
								value="<?php echo (isset($_POST['Short_Name']) ? $_POST['Short_Name'] : "")?>" 
								size="18" 
								maxlength="30"
								<?php if (!strstr($curPage,"login")) { echo "tabindex=\"1\""; } ?>>
						</td>
					</tr>
					<?php 
					if (isset($errors['Short_Name'])) {
					?>
						<tr><td class="error"><?php echo $errors['Short_Name'] ?></td></tr>				
					<?php			
					}
					?>
					<tr>
						<td class="fld">Password:</td>
					</tr>
					<tr>
						<td class="fld">
							<input 	type="password" 
									name="Password"
									value="" 
									size="18" 
									maxlength="30" 
									<?php if (!strstr($curPage,"login")) { echo "tabindex=\"2\""; } ?>>
						</td>
					</tr>
					<?php 
					if (isset($errors['Password'])) {
					?>
						<tr><td class="error"><?php echo $errors['Password'] ?></td></tr>
					<?php			
					}
					?>
					<tr>
						<td class="dispRC">
							<button type="submit" value="Login" class="submitBtn" name="ProcessAction">
								<span>Login</span>
							</button>
						</td>
					</tr>
				</table>
			</form>
			<ul class="sm">
				<li>
					<a href="login.php?a=forgetPlayerID" onMouseOver="window.status=''; return true"
					<?php
					echo $temp = (strstr($curPage, "forgetPlayerID")) ? "class=\"smOn\"" : "class=\"sm\"";?>
					>Forget Player ID?</a>	
				</li>
				<li>
					<a href="login.php?a=forgetPassword" onMouseOver="window.status=''; return true"
					<?php
					echo $temp = (strstr($curPage, "forgetPassword")) ? "class=\"smOn\"" : "class=\"sm\"";?>	
					>Forget Password?</a>	
				</li>
			</ul>
		</div>
		<br/>
		<div id="leftbar_inner_wrapper">
			<h3>Not a member?</h3>
			<p>
			Sign up to play some disc by registering as a Mighty Ultimate Player... It's quick 
			and best of all - free!
			</p>
			<form method="post" name="selectionForm" action="login.php">
				<table cellspacing="0" width="100%">
					<tr>
						<td class="dispRC">
							<button type="submit" value="New Member" class="submitBtn" name="ProcessAction">
								<span>New Member</span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		</div>
	<?php
	} else {
	?>
		<div id="leftbar_inner_wrapper">
			<h3>Player Menu</h3>
			<ul>
				<li>
					<a href="account_settings.php" onMouseOver="window.status=''; return true" 
					<?php
					echo $temp = (strstr($curPage, "account_settings")) ? "class=\"regOn\"" : "class=\"reg\"";?>
					>Account Settings</a>
				</li>
				<li>
					<a href="player_profile.php" onMouseOver="window.status=''; return true" 
					<?php 
					echo $temp = (strstr($curPage, "player_profile")) ? "class=\"regOn\"" : "class=\"reg\"";?>
					>Player Profile</a><br/>
				</li>
				<li>
					<a href="<?php echo SECURE_LOCATION_SITE ?>registration_status.php" 
						onMouseOver="window.status=''; return true" 
					<?php 
					echo $temp = (strstr($curPage,"registration_status")) ? "class=\"regOn\"":"class=\"reg\"";?>
					>Registration Status</a><br/>
				</li>
				<li>
					<?php
					if (check_admin_authorization()) {
					?>
						<a href="event_select.php" onMouseOver="window.status=''; return true"
						<?php 
						echo $temp = (strstr($curPage, "event_select")) ? "class=\"regOn\"" : "class=\"reg\"";?>
						>My Events</a>
						<br/><br/>
					<?php
					} else {
					?>
						<span class="regLB">My Events:</span>
					<?php
					}
					?>
				</li>
			</ul>
			<?php
			/** display this for players only; no admin since the list would be too freakin long */ 
			if (!check_admin_authorization()) {
			?>
				<form method="post" name="myEventForm" action="<?php echo substr($_SERVER["REQUEST_URI"],1); ?>">
					<?php
					$thisEventID = get_session_event_mgmt();
					$arrEvents = get_session_event_list();
					if (!empty($arrEvents)) {
						foreach ($arrEvents as $arrValue) {
							$selectedEvent = false;
							$eventID = $arrValue["Event_ID"];
							$eventName = wordwrap($arrValue["Event_Name"], 26, "<br/>", false);
							
							if ($thisEventID == $eventID) {
								$selectedEvent = true;
							}
							$btnClass = ($selectedEvent) ? " class=\"eventBtnOn\"" : " class=\"eventBtn\"";
							$thisEvent = "ProcessMyEvent[".$eventID."]";
						?>
							<button type="submit" value="<?php echo $eventName ?>" <?php echo $btnClass ?> 
								name="<?php echo $thisEvent ?>">
								<span><?php echo $eventName ?></span>
							</button>
							<br/>
						<?php
						}
						echo "<br/>";
					} else {
					?>
						<span class="smLB">You have not registered</span><br/>
						<span class="smLB">for any ultimate events</span><br/><br/>
					<?php
					}
					?>
				</form>
			<?php
			}
			?>
		</div>
	<?php	
	}
	?>
	<?php
	if (check_admin_authorization()) {
		$session = dbSession::getInstance();
		$count = $session->get_users_online();
		$thisText = ($count == 1) ? " player is " : " players are ";
		echo "<br/><p>".$count.$thisText."currently<br/>logged in.</p><br/>";
	}
	$tempPlayID = (isset($_SESSION['player'][0]) ? $_SESSION['player'][0] : "");
	if (IS_LOCAL & $tempPlayID != ""){
		$tempPlayRole = (isset($_SESSION['player'][1]) ? $_SESSION['player'][1] : "");
		$tempEventRegID = (isset($_SESSION['event'][0]) ? $_SESSION['event'][0] : "");
		$tempEventMngID = (isset($_SESSION['event'][1]) ? $_SESSION['event'][1] : "");
		$tempEventMngName = (isset($_SESSION['event'][2]) ? $_SESSION['event'][2] : "");
		$tempEventList = (isset($_SESSION['event'][3]) ? $_SESSION['event'][3] : "");
		echo "<p><b>playID:</b><br/>".$tempPlayID."<br/>";
		echo "<b>playRole:</b><br/>".$tempPlayRole."<br/>";
		echo "<b>eventRegID:</b><br/>".$tempEventRegID."<br/>";
		echo "<b>eventMngID:</b><br/>".$tempEventMngID."<br/>";
		echo "<b>eventMngName:</b><br/>".wordwrap($tempEventMngName, 26, "<br/>", false)."<br/>";
		echo "<b>eventList:</b><br/>";
		if ($tempEventList <> "") {
			foreach ($tempEventList as $arrValue) {
				$eventID = $arrValue["Event_ID"];
				$eventName = $arrValue["Event_Name"];
				echo $eventID." ".wordwrap($eventName, 26, "<br/>", false)."<br/>";  
			}
		}
		$playerID = get_session_player_id();
		$eventIDs = "";
		if ($playerID <> "") {
			if ($eventRolesResultTemp = get_event_roles($playerID)) {
				$numResultsTemp = mysql_num_rows($eventRolesResultTemp);
				if ($numResultsTemp > 0) {
					while ($row=mysql_fetch_array($eventRolesResultTemp)) {
						if ($eventIDs == "") {
							$eventIDs = $row['Event_ID'];
						} else {
							$eventIDs = $eventIDs.",".$row['Event_ID'];
						}
					}
				}
			}
		}
		echo "<b>eventIDs w/role:</b><br/>".$eventIDs."</p>";
	}
	?>		
	</div>
<?php
}

function display_right_side_wrapper($errorsSearch) {
	$searchArray = array();
	$curPage = get_cur_page_URL();
	if (strstr($curPage, "index.php")) { 
		$searchArray = get_session_search();
	} else {
		unset_session_search();
	}	
	$eventTypeSearch = (isset($searchArray[0])) ? $searchArray[0] : "";
	$countrySearch = (isset($searchArray[1])) ? $searchArray[1] : "";
	$stateProvSearch = (isset($searchArray[2])) ? $searchArray[2] : "";
  ?>
	<div id="rightbar_gapper"></div>
	<div id="rightbar_wrapper">
		<div id="rightbar_inner_wrapper">
			<h3>Find A Game</h3>
			<form method="post" name="searchForm" action="index.php">
				<table cellspacing="0">
					<tr>
						<td class="fld">
							<input type="checkbox" 
								name="Event_Type[]"
								value="3" 
								<?php if (strstr($eventTypeSearch,"3")) { print "checked"; } ?>
								>Hat Tournment<br/>
							<input type="checkbox" 
								name="Event_Type[]"
								value="1" 
								<?php if (strstr($eventTypeSearch,"1")) { print "checked"; } ?>
								>League<br/>
							<input type="checkbox" 
								name="Event_Type[]"
								value="2" 
								<?php if (strstr($eventTypeSearch,"2")) { print "checked"; } ?>
								>Pickup<br/>
						</td>
					</tr>
					<?php 
					if (isset($errorsSearch['Event_Type'])) {
					?>
						<tr><td class="error" colspan="2"><?php echo $errorsSearch['Event_Type'] ?></td></tr>
					<?php			
					}
					?>
					<tr>
						<td class="fld">
							<select name="Country" size="1" class="fld">
								<option value="">Please select</option>
							<?php	
							$countriesResult = get_countries();
							while ($row=mysql_fetch_array($countriesResult)) {
								$countryCode = $row["Code"];
								$countryName = $row["Name"];
								($countryCode == "US") ? $selected="selected" : $selected="";
								if ($countrySearch == "") {
									$selected = ($countryCode == "US") ? "selected" : "";
								} else {
									$selected = ($countrySearch == $countryCode) ? "selected" : "";
								}
		 						echo "<option $selected value=$countryCode>$countryName</option>";
							} 
							?>
							</select>
						</td>
					</tr>
					<?php 
					if (isset($errorsSearch['Country'])) {
					?>
						<tr><td class="error" colspan="2"><?php echo $errorsSearch['Country'] ?></td></tr>
					<?php			
					}
					?>
					<tr>
						<td class="fld">
							<select name="State_Prov" size="1" class="fld">
								<option value="">Please select</option>
							<?php	
							$statesResult = get_states();
							while ($row=mysql_fetch_array($statesResult)) {
								$stateCode = $row["Code"];
								$stateName = $row["Name"];
								if ($stateProvSearch == "") {
									$selected = ($stateCode == "US") ? "selected" : "";
								} else {
									$selected = ($stateProvSearch == $stateCode) ? "selected" : "";
								}
		 						echo "<option $selected value=$stateCode>$stateName</option>";
							} 
							?>
							</select>
						</td>
					</tr>
					<?php 
					if (isset($errorsSearch['State_Prov'])) {
					?>
						<tr><td class="error" colspan="2"><?php echo $errorsSearch['State_Prov'] ?></td></tr>
					<?php			
					}
					?>
					<tr>
						<td class="dispRC">
							<button type="submit" value="Search" class="submitBtn" name="ProcessAction">
								<span>Search</span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<br/>
		<span class="smLBC">Pickup games powered by</span><br/>
		<a href="http://pickupultimate.com" target="_blank">
			<img src="images/pickuplogo.jpg" width="136" height="30" border="0">
		</a>
        <?php
        if (!strstr($curPage, "event_activate")) {
        ?>
            <br/><br/>
            <?php build_donate_paypal(); ?>
        <?php
        }
        ?>
	</div>
<?php
}

function display_footer_wrapper() {
	/** NOTE: db connection is closed by call from write function in db_session_function.php **/
?>
				<div id="footer_gapper">
				</div>
				<div id="footer_wrapper">
					<a href="about.php">About</a>&nbsp;&nbsp;|&nbsp;&nbsp;
					<a href="terms_of_use.php">Terms of Use</a>&nbsp;&nbsp;|&nbsp;&nbsp;
					<a href="privacy.php">Privacy Policy</a>&nbsp;&nbsp;|&nbsp;&nbsp;
					<a href="contact.php">Contact</a>
					<br/>
					<span class="smLB">Copyright &copy; 2009 Mighty Ultimate Disc. All Rights Reserved.</span>
				</div>
			</div>
			<!-- for help system -->
			<script type="text/javascript" src="scripts/jquery/jquery.qtip-1.0.0-rc3.min.js"></script>
			<script type="text/javascript">
				$(document).ready(function()
				{
					$('img[alt]').qtip(
				   		{ 
				   			style: { 
								'font-family': 'verdana, arial, sans-serif',
								'font-size': '11px',
								width: 180,
				      			padding: 5,
								background: '#F2F7E9',
								color: '#333333',
								textAlign: 'left',
								border: {
									width: 1,
									radius: 3,
									color: '#4F6135'
								},
								tip: 'bottomLeft',
								name: 'dark' // Inherit the rest of the attributes from the preset dark style
							},
							position: {
						    	corner: {
				        			target: 'topRight',
				         			tooltip: 'bottomLeft'
				      			},
				      			adjust: { x: 10, y: 0 }
				   			},
							show: {effect: {type:'slide',length:'100'}}, 
							hide: {effect: {type:'fade',length:'1000'}} 
						}
					);
				});
			</script>
		</body>
	</html>
<?php
}

function cleanAction($processAction) {
	/** this function traps for an IE bug that occurs when submitting a page with a button tag.
	 *  IE sends the entire innerHTML within the button tag (in this case <span>); Firefox behaves 
	 *  nicely and IE does not.
	 */
	$processAction = (isset($processAction)) ? $processAction : "";
	if (!is_array($processAction)) {
		if (stristr(strtolower($processAction), "<span>")) {
		 	return $processAction = strip_tags($processAction);
		} else {
			return $processAction;
		}
	} else {
		return $processAction;
	}
}

function close_db() {
    $db = Database::getInstance();
    $db->close();
}
?>