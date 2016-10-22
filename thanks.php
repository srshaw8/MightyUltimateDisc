<?php

/**
 * @author Steven Shaw
 * @copyright 2010
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


$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
$processAction = cleanAction($thisProcessAction);
if ($processAction == "Go To My Event") {
	if (check_value_is_set($eventID) and is_numeric($eventID)) {
        redirect_page("event_mgmt.php");
	} else {
		clear_selected_event();
		redirect_page("index.php");
	}
} else if ($processAction == "Home") {
    redirect_page("index.php");
} else {
    build_thanks_page($errors,$eventID);
}

function build_thanks_page($errors,$eventID) {
	display_wrappers();
?>
	<div id="content_wrapper">
	<?php
	display_errors($errors);
    ?>
    <p>
    Great! Thanks for your donation...  It'll help keep this website up and running for the benefit of many ultimate players. 
    </p>
        <table class="defaultG">
            <tr>
			    <td class="dispRC">
                    <form method="post" name="selectionForm" action="thanks.php">
                        <button type="submit" value="Home" class="submitBtn" name="ProcessAction">
                            <span>Home</span>
                        </button>
                        &nbsp;&nbsp;&nbsp;
                        <?php 
                        if (check_value_is_number($eventID)) {
                        ?>
                            <button type="submit" value="Go To My Event" class="submitBtn" name="ProcessAction">
                                <span>Go To My Event</span>
                            </button>
                        <?php 
                        }
                        ?>
                    </form>
                </td>
            </tr>
       </table>            
	</div>
<?php
}

display_footer_wrapper();
?>