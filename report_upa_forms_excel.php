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
		$rs = get_report_upa_forms($eventID);
		$rowEvent = get_event_profile_short($eventID);
		/** send header */
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=upa_forms.xls");
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
				<td><b>Team Name</b></td>
				<td><b>Player Name</b></td>
				<td><b>UPA #</b></td>
				<td><b>Cur UPA Member?</b></td>
				<td><b>Student?</b></td>
				<td><b>Over 18?</b></td>
				<td><b>UPA Event Fee</b></td>
				<td><b>Waiver Form?</b></td>
				<td><b>Med Auth Form?</b></td>
				<td><b>UPA Event Fee Form?</b></td>
				<td><b>Chaperone Form?</b></td>
			</tr>
			<?php
			while($row = mysql_fetch_array($rs)) {
				$waiverVal = "Yes";
				$upaCurVal = "";
				$studentVal = "";
				$over18Val = "";
				$medicalVal = "";
				$eventFormVal = "";
				$chapVal = "";								
				
				if ($row['UPA_Cur_Member'] == "Y") {
					$upaCurVal = "Yes";
				} else {
					$upaCurVal = "No";
				}
				if ($row['Student'] == "Y") {
					$studentVal = "Yes";
				} else {
					$studentVal = "No";
				}
				if ($row['Over18'] == "Y") {
					$medicalVal = "No";
					$over18Val = "Yes";
				} else {
					$medicalVal = "Yes";
					$over18Val = "No";
				}
				if ($row['UPA_Event_Fee'] > 0) {
					$eventFormVal = "Yes";
				} else {
					$eventFormVal = "No";
				}
				if ($row['Role'] == "Captain") {
					$chapVal = "Yes";
				} else {
					$chapVal = "No";
				}
		    ?>
			<tr>
				<td><?php echo $row['Team_Name'] ?></td>
				<td><?php echo $row['Last_Name'].", ".$row['First_Name'] ?></td>
				<td><?php echo $row['UPA_Number'] ?></td>
				<td><?php echo $upaCurVal ?></td>
				<td><?php echo $studentVal ?></td>
				<td><?php echo $over18Val ?></td>
				<td><?php echo $row['UPA_Event_Fee'] ?></td>
				<td><?php echo $waiverVal ?></td>
				<td><?php echo $medicalVal ?></td>
				<td><?php echo $eventFormVal ?></td>
				<td><?php echo $chapVal ?></td>
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