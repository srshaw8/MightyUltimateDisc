<?php

/**
 * @author Steven Shaw
 * @copyright 2009
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
	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization();
	$eventID = get_session_event_mgmt();
	$playerID = get_session_player_id();
	$enteredData = array();
	$eventInfo = array();
	$errors = array();
	
	if ($isOwner or $isAdmin) {
		$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
		$processAction = cleanAction($thisProcessAction);
		if ($processAction == "No Thanks") {
			$action = "noThanks";
		}
		if (check_value_is_number($eventID)) {
			$eventInfo = get_event_profile($eventID); 
            if ($action == "noThanks") {	
				redirect_page("event_mgmt.php");
			} else {
                build_activate_page($errors,$eventInfo);
			}
		} else {
			clear_selected_event();
			redirect_page("index.php");
		}	
	} else {
		log_entry(Logger::EVENTP,Logger::WARN,$eventID,$playerID,
				"Non-authorized player tried to peek at Event Activation page.");
		$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
		build_activate_page($errors,$eventInfo);
	}
} else {
	display_non_authorization();
}

function build_activate_page($errors,$eventInfo) {
	display_wrappers();
?>
	<div id="content_wrapper">
	<?php
	display_errors($errors);

	$isOwner = check_owner_authorization();
	$isAdmin = check_admin_authorization(); 
	if ($isOwner or $isAdmin) {
		$payStatus = "N"; // by default
		if (isset($eventInfo['Payment_Status'])) {
			$payStatus = $eventInfo['Payment_Status'];
		}
		?>
		<p>
		<?php
		if ($payStatus == "N") {
		?>
			Weird!  You arrived here without your event being activated.
			<br/><br/>
			Here's what you should do... contact 
			<a href="<?php echo LOCATION_SITE ?>contact.php">Mighty Ultimate Disc support</a>.  
		<?php
		} else {
		?>
			Your event is now activated and ready to be published so that any player can see it.  You can publish 
            your event by editing the publish field on your event's profile page.  Piece of cake!
			<br/><br/>
			Please remember that players can only sign up for your event between the start and end registration 
			time stamps that you set on the	event's profile. 
            <br/><br/>
            And... please take a moment to consider a Paypal donation to Mighty Ultimate Disc.... As you go through the event hosting 
            process, you'll realize a lot of the benefits that the site provides.  If not now, you can always donate later.
            <table class="defaultG">
                <tr>
                    <td class="dispRC">
                    <?php build_donate_paypal($eventInfo); ?>
                    </td>
				</tr>
			</table>
            Thanks for using MightyUltimate.com!!!
            <br/><br/>
            <table class="defaultG">
                <tr>
				    <td class="dispRC">
                        <form method="post" name="selectionForm" action="event_activate.php">
		      				<button type="submit" value="No Thanks" class="submitBtn" name="ProcessAction">
                                <span>Not right now, thanks...</span>
                            </button>
                        </form>
                    </td>
                </tr>
            </table>            
		<?php
		}
		?>
		</p>
	<?php
	}
	?>
	</div>
<?php
}

display_footer_wrapper();
?>