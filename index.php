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

/** initialize variables */
$action = "";
$processAction = "";
$eventID = "";
$rsEvents = array();
$rsEvent = "";
$rsHomePage = "";
$searchArray = array();
$errorsSearch = array();
$thisPage = "";

clear_selected_event();

if (isset($_REQUEST['id'])) {
	/** from url on return from paypal after player registered/paid for event */
	$eventID = $_REQUEST['id'];
	if ($eventID <> "") {
		if (is_numeric($eventID)) {
			$rsEvent = get_event_profile($eventID);
			$rsHomePage = get_event_home_page_published($eventID);
			if ($rsEvent) {
				build_index_detail_page($rsEvent,$rsHomePage);
			} else {
				build_index_initial_page($errorsSearch);
			}
		} else {
			build_index_initial_page($errorsSearch);
		}
	} else {
		build_index_initial_page($errorsSearch);
	}
} else if (isset($_POST['ProcessAction'])) { /** from executing a search from initial index page */
	if (is_array($_POST['ProcessAction'])) {
		$processAction = each($_POST['ProcessAction']);
		$eventID = $processAction['key'];
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
	} else {
		$processAction = cleanAction(($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "");
		if ($processAction == "Search") {
			$action = "search";
			$enteredData = get_data_entered($_POST);
			$errorsSearch = validate($action, $enteredData);
			if (empty($errorsSearch)){
				/** store search params into session */
				$searchArray[0] = implode(",",$enteredData['Event_Type']);
				$searchArray[1] = $enteredData['Country'];
				$searchArray[2] = $enteredData['State_Prov']; 
				set_session_search($searchArray);
				/** get events for each type of selected event */
				$rsPaged = 
				new MySQLPagedResultSet(get_event_profiles_active($searchArray[0],$searchArray[1],$searchArray[2]));
				build_index_summary_page($rsPaged);
			} else {
				build_index_initial_page($errorsSearch);
			}
		} else {
			build_index_initial_page($errorsSearch);
		}
	}
} else if (isset($_GET['rp'])) {
	$searchArray = get_session_search();
	$rsPaged = new MySQLPagedResultSet(get_event_profiles_active($searchArray[0],$searchArray[1],$searchArray[2]));
	build_index_summary_page($rsPaged);
} else if (isset($_POST['LearnPlay'])) {	/** from selecting learn about player option */
	redirect_page("index_play.php"); 
} else if (isset($_POST['LearnOrg'])) {	/** from selecting learn about organizer option */
	redirect_page("index_org.php");
} else if (isset($_POST['ProcessEvent'])) {	/** from selecting an event from the index summary page */
	if (is_array($_POST['ProcessEvent'])) {
		$processAction = each($_POST['ProcessEvent']);
		$eventID = $processAction['key'];
		$action = $processAction['value'];
		
		if ($action == "Sign Up Now") {
			/** user wants to register for this event */
			set_session_event_register($eventID);
			/** is user logged in? */
			if (!check_authorization()) {
				/** if not, send them to login page */
				$goHere = "login.php";
			} else {
				/** if player is registering, send them to player profile to update it and 
				 * continue registration process 
				 */
				$goHere = "player_profile.php";
			}
			redirect_page($goHere);
		} else {
			/** clean up register event ID in session for clean SLTopate */ 
			if (isset($_SESSION['event'][0]) and $_SESSION['event'][0] <> 0) {
				$_SESSION['event'][0] = 0;
			}
			$rsEvent = get_event_profile($eventID);
			$rsHomePage = get_event_home_page_published($eventID);
			build_index_detail_page($rsEvent,$rsHomePage);
		}
	} else {
		build_index_initial_page($errorsSearch);
	}
} else {
	build_index_initial_page($errorsSearch);
}

function build_index_initial_page($errorsSearch) {
	display_wrappers($errorsSearch,true);
	?>
	<div id="content_wrapper">
		<form method="post" name="selectionForm" action="index.php">
			<p>
Ultimate Frisbee is one of the greatest sports ever played...  Mighty Ultimate Disc is here to help you play the game you love 
and to give a boost to those who want to make the sport grow.
	<br/><br/>
If you're a player looking for a game, you can use Mighty Ultimate Disc to easily sign up for any league or hat tournament 
created within the website.
	<br/><br/>
If you're looking to organize your own show, use Mighty Ultimate Disc to get it online for FREE.  Mighty Ultimate Disc will 
provide you with the necessary online tools to take the hassle out of running a league or hat tournament.   
			<br/><br/>
How Mighty Ultimate Disc can help you...
			<br/><br/>
			</p>
			<table class="defaultG">
				<tr>
					<td>
				<div id="xsnazzy">
				<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
				<div class="xboxcontentX">
				<p>
				If you're an ultimate frisbee player looking to play in a league or hat tournament...
				<input type="submit" class="linkO" name="LearnPlay" value="Learn More">
				</p>
				</div>
				<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
				</div>			
					</td>
					<td>&nbsp;</td>
					<td>
				<div id="xsnazzy">
				<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
				<div class="xboxcontentX">
				<p>
				If you want to organize your own ultimate frisbee league or hat tournament...
				<input type="submit" class="linkO" name="LearnOrg" value="Learn More">
				</p>
				</div>
				<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
						</div>			
					</td>
				</tr>
			</table>
		</form>
		</p>
	</div>
<?php
}

function build_index_summary_page($rsPaged) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		/** build the table header */
		$searchArray = get_session_search();
		$countrySearch = (isset($searchArray)) ? get_country_name($searchArray[1]) : "";
		$stateProvSearch = (isset($searchArray)) ? get_state_name($searchArray[2]) : "";
		$headerTitle = $stateProvSearch.", ".$countrySearch;
		?>
		<p>Search results for <?php echo $headerTitle ?><br/><br/></p>
		<form method="post" name="selectionForm" action="index.php" class="boxReport">
			<table class="report">
				<tr>
					<th scope="col" class="dispRL">Event<br/>Type</th>
					<th scope="col" class="dispRL">City</th>
					<th scope="col" class="dispRL">&nbsp;&nbsp;Event</th>
					<th scope="col" class="dispRL">Status</th>
					<th scope="col" class="dispRL">Registration Start Time<br/>
						<span class="smGD">* Shown in local event time *</span></th>
				</tr>
				<?php
				/** build list of events */
				if ($rsPaged) {
					if ($rsPaged->getNumPages() > 0) {
						$i=0;
						while ($row=$rsPaged->fetchArray()) {
							if (stripslashes($row['Name']) == "Pickup") {
								$eventType = stripslashes($row['Name']);
								$city = stripslashes($row['City']);
								$name = stripslashes($row['Event_Name']);
								$link = stripslashes($row['Location_Link']);
								$signupStatus = "";
								$regBegin = "";
							} else {							
								$eventType = stripslashes($row['Name']);
								$city = stripslashes($row['City']);
								$name = stripslashes($row['Event_Name']);
								$thisEvent = "ProcessEvent[".$row['Event_ID']."]";
								$regBegin = convert_time_gmt_to_local_people($row['Timezone_ID'], $row['Reg_Begin']);
	
								$signupStatus = get_signup_status($row['Reg_Begin'],$row['Reg_End'],$row['Event_End']);
								$link = $i % 2 ? " class=\"linkAlt2\"" : " class=\"link2\"";
							}
							$altRow = $i % 2 ? " class=\"alt\"" : "";
							?>
							<tr<?php echo $altRow ?>>
								<td class="entrySL">
									<?php echo $eventType; ?>
								</td>
								<td class="entrySL">
									<?php echo $city; ?>
								</td>
								<?php
								if (stripslashes($row['Name']) == "Pickup") { 
								?>
									<td class="entrySL" colspan="3">
										<a href="<?php echo $link ?>" target="_blank"><?php echo $name ?></a>
									</td>
								<?php
								} else {
								?>
									<td class="entrySL">
										<input type="submit" <?php echo $link ?> 
											name="<?php echo $thisEvent ?>" value="<?php echo $name ?>">
									</td>
									<td class="entrySL">
										<?php echo $signupStatus; ?>
									</td>
									<td class="entrySL">
										<?php echo $regBegin; ?>
									</td>
								<?php
								}
								?>
							</tr>
						<?php
							$i++;
						}
						?>
						<tr>
							<th colspan="5" scope="col" class="dispSCx">
								<?php echo $rsPaged->getPageNav()?></th>
						</tr>
					<?php
						
					} else { 
					?>
						<tr><td colspan="5"><p>No ultimate events were found.</p></td></tr>
					<?php
					}
				} else {
				?>
					<tr><td colspan="5"><p>No ultimate events were found.</p></td></tr>
				<?php
				} 
				?>
			</table>
		</form>
	</div>
<?php
}

function build_index_detail_page($rsEvent,$rsHomePage) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		if (check_value_is_set($rsHomePage['Home_Page_Text'])) {
		?>
			<p><?php echo stripslashes($rsHomePage['Home_Page_Text']) ?><br/></p>
		<?php
		}
		?>
		<table class="report">
			<tr>
				<th colspan="4" scope="col" class="dispRL">Event Information</th>
			</tr>
			<tr>
				<td class="titleSR">Sponsor</td>
				<td class="entrySL"><?php echo stripslashes($rsEvent['Org_Sponsor']) ?></td>
				<td class="titleSR">Name</td>
				<td class="entrySL"><?php echo stripslashes($rsEvent['Event_Name']) ?></td>
			</tr>
			<?php
			$regBegin = convert_time_gmt_to_local_people($rsEvent['Timezone_ID'],$rsEvent['Reg_Begin']);
			$regEnd = convert_time_gmt_to_local_people($rsEvent['Timezone_ID'],$rsEvent['Reg_End']);
			$eventBegin = convert_date_gmt_to_local_people($rsEvent['Timezone_ID'],$rsEvent['Event_Begin']);
			$eventEnd = convert_date_gmt_to_local_people($rsEvent['Timezone_ID'],$rsEvent['Event_End']);
			?>
			<tr>
				<td class="titleSR">Registration Start</td>
				<td class="entrySL"><?php echo $regBegin ?></td>
				<td class="titleSR">Registration End</td>
				<td class="entrySL"><?php echo $regEnd ?></td>
			</tr>
			<tr>
				<td class="titleSR">Start of Play</td>
				<td class="entrySL"><?php echo $eventBegin ?></td>
				<td class="titleSR">End of Play</td>
				<td class="entrySL"><?php echo $eventEnd ?></td>
			</tr>
			<tr>
				<td class="titleSR">Local Time Zone</td>
				<td colspan="3" class="entrySL">
					<?php	
					$tzResult = get_timezone_names();
					if($tzResult) {
						while ($row=mysql_fetch_array($tzResult)) {
							$tzID = $row["Timezone_ID"];
							if ($rsEvent["Timezone_ID"] == $tzID) {
								echo $row["Timezone_Name"];	
								break;
							}
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<td class="titleSR">Game Time</td>
				<td class="entrySL"><?php echo $rsEvent['Event_Time']?></td>
				<td class="titleSR">Day(s) of Week</td>
				<td class="entrySL">
				<?php
				$tempArr = explode(",", $rsEvent['Days_Of_Week']);
				foreach ($tempArr as $thisVal) {
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
				<td class="titleSR">City/State</td>
				<td class="entrySL">
					<?php echo stripslashes($rsEvent['City'])." / ".stripslashes($rsEvent['State_Prov'])?></td>
				<td class="titleSR">Location</td>
				<td class="entrySL">
				<?php echo stripslashes($rsEvent['Location'])?>
				<?php
				if(check_value_is_set($rsEvent['Location_Link'])) {
				?>
					&nbsp;&nbsp;
					<a href="<?php echo stripslashes($rsEvent['Location_Link'])?>" target="_blank">Link</a>
				<?php
				}
				?>
				</td>
			</tr>
			<tr>
				<td class="titleSR">Number of Teams</td>
				<td class="entrySL"><?php echo stripslashes($rsEvent['Number_Of_Teams'])?></td>
				<td class="titleSR">Players per Team</td>
				<td class="entrySL"><?php echo stripslashes($rsEvent['Players_Per_Team']) ?></td>
			</tr>
			<tr>
				<td class="titleSR"># of Men/Women Players</td>
				<td class="entrySL">
					<?php echo stripslashes($rsEvent['Limit_Men'])." / ".stripslashes($rsEvent['Limit_Women']) ?>
				</td>
				<td class="titleSR">Team Gender Ratio</td>
				<td class="entrySL"><?php echo stripslashes($rsEvent['Team_Ratio']) ?></td>
			</tr>
			<tr>
				<td class="titleSR">Accepted Payment Types</td>
				<td class="entrySL">
					<?php 
					$temp = strstr(is_string($rsEvent['Payment_Type']) ? 
						$rsEvent['Payment_Type'] : implode(',',$rsEvent['Payment_Type']), "1");
					if ($temp !== false) {
						echo "Cash<br/>";
					}
					$temp = strstr(is_string($rsEvent['Payment_Type']) ? 
						$rsEvent['Payment_Type'] : implode(',',$rsEvent['Payment_Type']), "2");
					if ($temp !== false) {
						echo "Check<br/>";
					}
					$temp = strstr(is_string($rsEvent['Payment_Type']) ? 
						$rsEvent['Payment_Type'] : implode(',',$rsEvent['Payment_Type']), "3");
					if ($temp !== false) {
						echo "PayPal";
					}
					?>
				</td>
				<td class="titleSR">Payment Deadline</td>
				<td class="entrySL">
					<?php 
					if (IS_LOCAL) {
						echo strftime("%b %d %Y", strtotime($rsEvent['Payment_Deadline']));	
					} else {
						echo strftime("%b %e %Y", strtotime($rsEvent['Payment_Deadline']));
					}
					?>
				</td>
			</tr>
			<tr>
				<td class="titleSR">Fees</td>
				<td class="entrySL">
					Event: 
					<?php 
					echo $eventFee = 
						(stripslashes($rsEvent['Event_Fee']) == 0)? 
					" It's FREE!<br/>" : " \$".number_format(stripslashes($rsEvent['Event_Fee']), 2, '.', '')."<br/>";
					
					if (stripslashes($rsEvent['Event_TShirt_Fee']) > 0) {
					?>
					T-Shirt Fee:
					<?php 
						echo " \$".number_format(stripslashes($rsEvent['Event_TShirt_Fee']), 2, '.', '')."<br/>"; 
					}
					
					if (stripslashes($rsEvent['Event_Disc_Fee']) > 0) {
					?>
					Disc Fee:
					<?php
						echo " \$".number_format(stripslashes($rsEvent['Event_Disc_Fee']), 2, '.', ''); 
					}
					?>
				</td>
				<td class="titleSR">UPA Sponsored Event</td>
				<td class="entrySL">
					<?php echo $upaValue = (stripslashes($rsEvent['UPA_Event']) == "Y") ? "Yes" : "No";?>
				</td>
			</tr>
			<tr>
				<td class="titleSR">Contact Name</td>
				<td class="entrySL"><?php echo stripslashes($rsEvent['Contact_Name'])?></td>
				<td class="titleSR">Contact Email</td>
				<td class="entrySL">
					<a href="mailto:<?php echo stripslashes($rsEvent['Contact_Email']) ?>">
						<?php echo stripslashes($rsEvent['Contact_Email']) ?>
					</a>
				</td>
			</tr>
			<?php
			if ($rsEvent['Publish_Phone'] == "Y") {
			?>
				<tr>
					<td class="titleSR">Contact Phone Number</td>
					<td class="entrySL"><?php echo stripslashes($rsEvent['Contact_Phone']) ?></td>
					<td></td>
					<td></td>
				</tr>
			<?php
			}
			?>
			<tr>
				<th scope="col" class="dispSCx" colspan="4"></th>
			</tr>
		</table>
		<br/>
		<?php
		$limitsText = "";
		$signupStatus =	get_signup_status($rsEvent['Reg_Begin'],$rsEvent['Reg_End'],$rsEvent['Event_End']);
		if ($signupStatus == "Signups open") {
			/** for those events that require a registration...
		 	 *  KEY: 
			 *  1 - league
			 *  3 - hat tournament
			 * 
			 */
			$buttonLabel = "Sign Up!";
			/** set show signup and wait list message flags */
			$showSignupM = false;
			$showWaitListM = false;
			$showSignupF = false;
			$showWaitListF = false;
			/** calculate # of men and women who have signed up */
			$mReg = get_event_reg_gender($rsEvent['Event_ID'], 'M');
			$fReg = get_event_reg_gender($rsEvent['Event_ID'], 'F');
			
			/** calculate # of spots available  for men and women */
			$mSpots = stripslashes($rsEvent['Limit_Men']) - $mReg;
			$fSpots = stripslashes($rsEvent['Limit_Women'])  - $fReg;
			
			/** determine if wait list or signup message should be displayed */
			if ($mSpots <= 0) {
				$showWaitListM = true;
			} else if ($mSpots <=20 ) {
				$showSignupM = true;
			}
			if ($fSpots <= 0) {
				$showWaitListF = true;
			} else if ($fSpots <=20 ) { 
				$showSignupF = true;
			}
			
			/** build registration status text for men and women */
			$limitsText = "<span class=\"bigGD2\">Men:</span><br/>";
			if ($showWaitListM) {
				$limitsText = $limitsText."Though the limit for men has been reached, you can still add your name to the wait list.<br/>";
			} else if($showSignupM) {
				if($mSpots == 1) {
					$tempText = "Quick! Only ".$mSpots." spot is open for a man.<br/>";
				} else {
					$tempText = "Quick! Only ".$mSpots." spots are open for men.<br/>";
				}
				$limitsText = $limitsText.$tempText;
			} else {
				$limitsText = $limitsText."Spaces are available for men!<br/>";
			}
		
			$limitsText = $limitsText."<br/><span class=\"bigGD2\">Women:</span><br/>";
			
			if ($showWaitListF) {
				$limitsText = $limitsText."Though the limit for women has been reached, you can still add your name to the wait list.<br/>";
			} else if($showSignupF) {
				if($fSpots == 1) {
					$tempText = "Quick! Only ".$fSpots." spot is open for a woman.<br/>";
				} else {
					$tempText = "Quick! Only ".$fSpots." spots are open for women.<br/>";
				}
				$limitsText = $limitsText.$tempText;
			} else {
				$limitsText = $limitsText."Spaces are available for women!<br/>";
			}
		}
			
		$thisEvent = "ProcessEvent[".$rsEvent['Event_ID']."]";
		?>
		<form method="post" name="selectionForm" action="index.php">
			<table class="report">
				<tr>
					<th colspan="2" scope="col" class="dispRL">Signups Status</th>
				</tr>
				<tr>
					<td class="entrySL"><p><?php echo $limitsText; ?></p></td>
					<td class="dispRC">
						<?php
						if ($signupStatus == "Signups open") {
						?>
							<button type="submit" value="Sign Up Now" class="submitBtn" 
								name="<?php echo $thisEvent ?>"><span>Sign Up Now</span>
							</button>
						<?php	
						} else {
							echo "<p>".$signupStatus."</p>";							
						}
						?>
					</td>
				</tr>
			</table>
		</form>
		<br/>
	</div>
<?php
}

display_footer_wrapper();
?>