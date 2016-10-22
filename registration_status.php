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

	$playerID = get_session_player_id();

	/** get upa status */	
	$existPlayer = get_player_profile_short($playerID);
	if ($existPlayer) {
		$curUPAMember = $existPlayer['UPA_Cur_Member'];
	} else {
		$curUPAMember = "NA"; /** no profile available */
	}

	/** get roster, team assignment, fees, payment status */
	$rsRosterStatus = get_roster_status($playerID);
	/** get wait list status */
	$rsWaitListStatus = get_wait_list_status($playerID);
	build_registration_status_page($rsRosterStatus, $rsWaitListStatus, $curUPAMember);
} else {
	display_non_authorization();
}

function build_registration_status_page($rsRosterStatus, $rsWaitListStatus, $curUPAMember) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<table class="report">
			<th scope="col" class="dispRL">UPA Member Status</th>
			<tr>
				<td class="entrySL">
					<?php
					if ($curUPAMember == 'Y') {
						echo "Current";
					} else if ($curUPAMember == 'N') {
						echo "You indicated that you are not a current member of the UPA.  Please 
						note that in order to play in any of our UPA sponsered events, you will need 
						to be a UPA member in good standing. Click 
						<a href=\"http://www.upa.org/membership\" target=\"_blank\">here</a> 
						to become a member.
						<br/><br/>
						After enrolling in the UPA, you can try registering for an UPA sanctioned event if it 
						is still open.  While registering, make sure that you update the UPA 
						membership field on your player profile. If the event is full, you can add 
						your name to the wait list if you have not already done so.";		
					} else {
						echo "Once you create your player profile, your UPA status will display here.";
					}
					?>			
				</td>
			</tr>
		</table>
		<br/>
		<table class="report">
			<th colspan="2" scope="col" class="dispRL">Roster Registration Status</th>
		</table>
		<table class="report">	
			<?php
			if($rsRosterStatus) {
				$numResults = mysql_num_rows($rsRosterStatus);
				if ($numResults > 0) {
					$eventType = "";
					$paymentType = "";
					$teamName = "";
					while ($row=mysql_fetch_array($rsRosterStatus)) {
						/** do some data prepping */
						if (check_value_is_set($row['Team_Name'])) {
							$teamName = $row['Team_Name'];
						} else {
							$teamName = "Not yet assigned";
						}
						$created = convert_time_gmt_to_local_people($row['Timezone_ID'], $row['Created']);
					?>
						<tr>
							<th scope="col" class="dispSL" colspan="2"><?php echo $row['Event_Name']?></th>
						</tr>
						<tr>
							<td class="titleSRN">Added to Roster</td>
							<td class="entrySL"><?php echo $created ?></td>
						</tr>
						<tr>
							<td class="titleSRN">Assigned Team</td>
							<td class="entrySL"><?php echo $teamName?></td>
						</tr>
						<tr>
							<td class="titleSRN">Event Fee</td>
							<td class="entrySL">
								<?php echo "\$".number_format($row['Event_Fee'], 2, '.', '') ?>
							</td>
						</tr>
						<?php
						if (isset($row['TShirt_Fee']) and $row['TShirt_Fee'] > 0) {
						?>
							<tr>
								<td class="titleSRN">T-Shirt Fee</td>
								<td class="entrySL">
									<?php echo "\$".number_format($row['TShirt_Fee'], 2, '.', '') ?>
								</td>
							</tr>
						<?php
						}
						if (isset($row['Disc_Fee']) and $row['Disc_Fee'] > 0) {
						?>
							<tr>
								<td class="titleSRN">Disc Fee</td>
								<td class="entrySL">
									<?php echo "\$".number_format($row['Disc_Fee'], 2, '.', '') ?>
								</td>
							</tr>
						<?php
						}
						if (isset($row['UPA_Event_Fee']) and $row['UPA_Event_Fee'] > 0) {
						?>
							<tr>
								<td class="titleSRN">One Time UPA Event Fee</td>
								<td class="entrySL">
									<?php echo "\$".number_format($row['UPA_Event_Fee'], 2, '.', '') ?>
								</td>
							</tr>
						<?php
						}
						?>											
						<tr>
							<td class="titleSRN">Payment Status</td>
							<td class="entrySL">
								<?php 
								echo (strstr(stripslashes($row['Payment_Status']),"Y")) ? "Paid" : "Not Paid"; 
								
								if (!strstr(stripslashes($row['Payment_Status']),"Y")) {
								?>
								<a href="#">
									<img src="/images/q2.jpg" align="top" width="15" height="15" border="0" alt="Please note that there may be a delay in the update of this status if you pay through Paypal."/>
								</a>
								<?php
								}
								?>
							</td>
						</tr>
						<?php
						if (strtoupper($row['Payment_Status']) == 'Y') {
						?>
							<tr>
								<td class="titleSRN">Payment Type</td>
								<td class="entrySL">
									<?php
									$payType = "";
									switch (stripslashes($row['Payment_Type_Roster'])) {
										case "1":
											$payType = "Cash";
											break;
										case "2":
											$payType = "Check";
											break;
										case "3":
											$payType = "Paypal";
											break;
									}
									echo $payType ?>
								</td>
							</tr>
						<?php
						} else {
							$temp = strstr(is_string($row['Payment_Type']) ? 
								$row['Payment_Type'] : implode(',',$row['Payment_Type']), "2");
							if ($temp !== false) {
							?>
							<tr>
							<td class="titleSRN">To pay by check, please make the check<br/>out to 
							<?php echo $row['Payment_Chk_Payee'] ?> and mail<br/>it to this address</td>
							<td class="entrySL"><?php build_register_check($row); ?></td>
							</tr>
							<?php
							}
							$temp = strstr(is_string($row['Payment_Type']) ? 
								$row['Payment_Type'] : implode(',',$row['Payment_Type']), "3");
							if ($temp !== false) { 
								$feeTotal = 
									$row['Event_Fee']+$row['TShirt_Fee']+$row['Disc_Fee']+$row['UPA_Event_Fee'];
							?>
							<tr>
							<td class="titleSRN">Click this link to pay through PayPal</td>
							<td class="entrySL"><?php build_register_paypal($row, $feeTotal); ?></td>
							</tr>
							<?php
							}
						}
						?>
						<tr><td class="entrySL"></td><td class="entrySL"></td></tr>
					<?php
					}
				}
			} else {
			?>
				<tr>
					<td colspan="2" class="entrySL">You have not registered for any events.</td>
				</tr>
			<?php
			}
			?>
		</table>
		<br/>
		<?php				
		if($rsWaitListStatus) {
			$numResults = mysql_num_rows($rsWaitListStatus);
			if ($numResults > 0) {
			?>
				<table class="report">
					<th colspan="2" scope="col" class="dispRL">Wait List Status</th>
					<?php
					$eventType = "";
					while ($row=mysql_fetch_array($rsWaitListStatus)) {
						$waitListPosition = new WaitListPosition($row['Event_ID'],$row['Player_ID'],$row['Gender']);
						$waitListNbr = $waitListPosition->get_position();
						$waitListTtl = $waitListPosition->get_total();
						$created = convert_time_gmt_to_local_people($row['Timezone_ID'], $row['Created']);
						if ($row['Gender'] == "M") {
							$genderTxt = "Men";
						} else {
							$genderTxt = "Women";
						}
					?>
						<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
						<tr>
							<th colspan="2" scope="col" class="dispRL"><?php echo $row['Event_Name']?></th>
						</tr>
						<tr>
							<td class="titleSRN">Added to Wait List</td>
							<td class="entrySL"><?php echo $created ?></td>
						</tr>
						<tr>
							<td class="titleSRN">Wait List Position</td>
							<td class="entrySL"><?php echo $waitListNbr ?></td>
						</tr>
						<tr>
							<td class="titleSRN">Total Number of <?php echo $genderTxt ?> Players on Wait List</td>
							<td class="entrySL"><?php echo $waitListTtl ?></td>
						</tr>
					<?php
					}
					?>
				</table>
			<?php
			}
		}
		?>
	</div>
<?php
}
display_footer_wrapper();
?>