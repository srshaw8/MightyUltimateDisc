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
		$rs = get_report_draft($eventID);
		$rowEvent = get_event_profile_short($eventID);
		/** send header */
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=draft_report.xls");
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
				<td><b>Gender</b></td>
				<td><b>Handling Skill</b></td>
				<td><b>Defense Skill</b></td>
				<td><b>Name</b></td>
				<td><b>Yrs Exp</b></td>
				<td><b>Play Level</b></td>
				<td><b>Condition</b></td>
				<td><b>Height</b></td>
				<td><b>% of Games</b></td>
				<td><b>Buddy</b></td>
			</tr>
			<?php
			while($row = mysql_fetch_array($rs)) {
				$yrExp = "";
				$skillVal = "";
				$heightVal = "";
				$condVal = "";
				$playVal = "";
				$gamesVal = "";
			
				$yrExp = "[".$row['Yr_Exp']."]";
			
				$tempArray = explode(",", $row['Height']);
				foreach($tempArray as $tempVal) {
					switch ($tempVal) {
						case 1:
							$heightVal = "<5'0\"";
							break;
						case 2:
							$heightVal = "5'1\" - 5'4\"";
							break;
						case 3:
							$heightVal = "5'5\" - 5'8\"";
							break;
						case 4:
							$heightVal = "5'9\" - 6'0\"";
							break;
						case 5:
							$heightVal = "6'1\"+";
							break;
					}
				}
				
				$tempArray = explode(",", $row['Conditionx']);
				foreach($tempArray as $tempVal) {
					switch ($tempVal) {
						case 1:
							$condVal = "Turtle";
							break;
						case 2:
							$condVal = "Elephant";
							break;
						case 3:
							$condVal = "Rabbit";
							break;
						case 4:
							$condVal = "Gazelle";
							break;
					}
				}
				
				$tempArray = explode(",", $row['Play_Lvl']);
				foreach($tempArray as $tempVal) {
					if ($tempVal == "1") {
						if ($playVal == "") {
							$playVal = "Never played at all";
						}  else {
							$playVal = $playVal.", Never played at all";
						}
					}
					if ($tempVal == "2") {
						if ($playVal == "") {
							$playVal = "Pickup";
						}  else {
							$playVal = $playVal.", Pickup";
						}
					}
					if ($tempVal == "3") {
						if ($playVal == "") {
							$playVal = "High School";
						}  else {
							$playVal = $playVal.", High School";
						}
					}
					if ($tempVal == "4") {
						if ($playVal == "") {
							$playVal = "College";
						}  else {
							$playVal = $playVal.", College";
						}
					}
					if ($tempVal == "5") {
						if ($playVal == "") {
							$playVal = "Club";
						}  else {
							$playVal = $playVal.", Club";
						}
					}
					if ($tempVal == "6") {
						if ($playVal == "") {
							$playVal = "Masters";
						}  else {
							$playVal = $playVal.", Masters";
						}
					}
					if ($tempVal == "7") {
						if ($playVal == "") {
							$playVal = "League";
						}  else {
							$playVal = $playVal.", League";
						}
					}
				}
				
				$tempArray = explode(",", $row['Pct_Of_Games']);
				foreach($tempArray as $tempVal) {
					switch ($tempVal) {
						case 1:
							$gamesVal = "<50%";
							break;
						case 2:
							$gamesVal = "50%-75%";
							break;
						case 3:
							$gamesVal = "+75%";
							break;
					}
				}
		    ?>
			<tr>
				<td><?php echo $row['Gender'] ?></td>
				<td><?php echo $row['Skill_Lvl'] ?></td>
				<td><?php echo $row['Skill_Lvl_Def'] ?></td>
				<td><?php echo $row['Last_Name'].", ".$row['First_Name'] ?></td>
				<td><?php echo $yrExp ?></td>
				<td><?php echo $playVal ?></td>
				<td><?php echo $condVal ?></td>
				<td><?php echo $heightVal ?></td>
				<td><?php echo $gamesVal ?></td>
				<td><?php echo $row['Buddy_Name'] ?></td>
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