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
	$eventID = get_session_event_mgmt();
	if (check_value_is_set($eventID) and is_numeric($eventID)) {	
		$rs = get_report_roster($eventID);
		$rowEvent = get_event_profile_short($eventID);
		/** send header */
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=roster_report.xls");
?>
		<table>
			<tr>
				<td colspan="2"><b>Organization Sponsor:</b></td>
				<td colspan="9"><b><?php echo $rowEvent['Org_Sponsor'] ?></b></td>
			</tr>
			<tr>
				<td colspan="2"><b>Event Name:</b></td>
				<td colspan="9"><b><?php echo $rowEvent['Event_Name'] ?></b></td>
			</tr>
			<tr>
				<td colspan="11">&nbsp;</td>
			</tr>
			<tr>
				<td><b>Name</b></td>
				<td><b>Reg?</b></td>
				<td><b>Paid?</b></td>
				<td><b>Payment Type</b></td>
				<td><b>Total Fees</b></td>
				<td><b>Event Fee</b></td>
				<td><b>TShirt Fee</b></td>
				<td><b>Size</b></td>
				<td><b>UPA Event Fee</b></td>
				<td><b>Disc Fee</b></td>
				<td><b># of Discs</b></td>
			</tr>
			<?php
			while($row = mysql_fetch_array($rs)) {
				$reg = (strstr($row['Registered'],"Y")) ? "Yes" : "No";
				$payStatus = (strstr(stripslashes($row['Payment_Status']),"Y")) ? "Yes" : "No";
				$payType = "";
				switch (stripslashes($row['Payment_Type'])) {
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
		    ?>
			<tr>
				<td><?php echo stripslashes($row['Last_Name']).", ".stripslashes($row['First_Name']) ?></td>
				<td><?php echo $reg ?></td>
				<td><?php echo $payStatus ?></td>
				<td><?php echo $payType ?></td>
				<td><?php echo "\$".number_format($totalFee, 2, '.', '') ?></td>
				<td><?php echo "\$".number_format($eventFee, 2, '.', '') ?></td>
				<td><?php echo "\$".number_format($tshirtFee, 2, '.', '') ?></td>
				<td><?php echo $tshirtSize ?></td>
				<td><?php echo "\$".number_format($upaEventFee, 2, '.', '') ?></td>
				<td><?php echo "\$".number_format($discFee, 2, '.', '') ?></td>
				<td><?php echo $thisVal = ($row['Disc_Count']>0) ? $row['Disc_Count'] : "0" ?></td>
			</tr>
			<?php
			}
			?>
		</table>
	<?php 
	} else {
		clear_selected_event();
		redirect_page("index.php");
	}
} else {
	display_non_authorization();
	display_footer_wrapper();
}
?>