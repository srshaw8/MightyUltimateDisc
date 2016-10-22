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
	/** variable declarations */
	$action = "";
	$processAction = "";
	$enteredData = array();
	$errors = array();
	$rsEvents = array();
	$rsEventProfile = array();
	$eventID = "";
	$playerID = get_session_player_id();
	$eventName = "";
	$eventRole = "";
	
	if (isset($_POST['ProcessAction']) and is_array($_POST['ProcessAction'])) {
		$processAction = each($_POST['ProcessAction']);
		$eventID = $processAction['key'];
		$action = $processAction['value'];
	}

	if ($action == "Select") {
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
		build_event_select_page($errors, $eventRolesResult);
	} else {
		$arrEvents = get_events_for_player($playerID);
		build_event_select_page($errors, $arrEvents);
	}
} else {
	display_non_authorization();
}

function build_event_select_page($errors, $arrEvents) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		build_event_navbar("");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		display_errors($errors);
		?>
			<form method="post" name="selectionForm" action="event_select.php" class="boxReport">
				<table class="report">
					<tr>
						<th scope="col" class="dispSL">Action</th>
						<th scope="col" class="dispSL">My Events</th>
					<tr>
					<?php
					$thisEventID = get_session_event_mgmt();
					if (!empty($arrEvents)) {
						foreach ($arrEvents as $arrValue) {
							$selectedEvent = false;
							$eventID = $arrValue["Event_ID"];
							$eventName = $arrValue["Event_Name"];
							$thisSelect = "ProcessAction[".$eventID."]";
							$linkClass=" class=\"linkSm\"";
							?>
							<tr>
								<td>
									<input type="submit" <?php echo $linkClass ?> 
										name="<?php echo $thisSelect ?>" value="Select">
								</td>
								<td class="entrySL">
									<?php echo $eventName; ?>
								</td>
							</tr>
						<?php
						}
					} else {
					?>
						<tr>
							<td colspan="2" class="entrySL">You have not registered for any ultimate events</td>
						</tr>
					<?php
					}
					?>
				</table>
			</form>
		</div>
	</div>
<?php
}

display_footer_wrapper();
?>