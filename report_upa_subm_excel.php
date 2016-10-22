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
		$rs = get_report_upa_subm($eventID);
		$rowEvent = get_event_profile_short($eventID);
		/** send header */
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=upa_submission.xls");
		?>
		<table>
			<tr>
				<td colspan="2"><b>Organization Sponsor:</b></td>
				<td colspan="7"><b><?php echo $rowEvent['Org_Sponsor'] ?></b></td>
			</tr>
			<tr>
				<td colspan="2"><b>Event Name:</b></td>
				<td colspan="7"><b><?php echo $rowEvent['Event_Name'] ?></b></td>
			</tr>
			<tr>
				<td colspan="9">&nbsp;</td>
			</tr>
			<tr>
				<td><b>First Name</b></td>
				<td><b>Last Name</b></td>
				<td><b>Address</b></td>
				<td><b>City</b></td>
				<td><b>State</b></td>
				<td><b>Zip Code</b></td>
				<td><b>UPA Number</b></td>
				<td><b>Home Phone Number</b></td>
				<td><b>Email</b></td>
			</tr>
			<?php
			while($row = mysql_fetch_array($rs)) {
		    ?>
			<tr>
				<td><?php echo $row['First_Name'] ?></td>
				<td><?php echo $row['Last_Name'] ?></td>
				<td><?php echo $row['Address'] ?></td>
				<td><?php echo $row['City'] ?></td>
				<td><?php echo $row['State_Prov'] ?></td>
				<td><?php echo $row['Post_Code'] ?></td>
				<td><?php echo $row['UPA_Number'] ?></td>
				<td><?php echo $row['H_Phone'] ?></td>
				<td><?php echo $row['Email'] ?></td>
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