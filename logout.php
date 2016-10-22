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

//$theUser = $_SESSION['player'];  /** store to test if the user *was* logged in */
$playerID = get_session_player_id();
$playerResult = get_player_profile_short($playerID);
$name = "";
if ($playerResult) {
	$name = $playerResult["First_Name"]." ".$playerResult["Last_Name"];
} else {
	$name = "Somebody who didn't create a profile";
}
log_entry(Logger::LOGIN,Logger::INFO,0,$playerID,$name." logged out.");

if (isset($_SESSION['player'])) {
	unset($_SESSION['player']);
	unset($_SESSION['event']);
	unset($_SESSION['tmpShortName']);
	$session = dbSession::getInstance();
	$session->stop();
	redirect_page("index.php");
} else {
	display_wrappers();
	build_page();
	display_footer_wrapper();
}

function build_page() {
?>
	<div id="content_wrapper">
		<p>You are no longer logged into the website.  Thanks for visiting.</p>
	</div>
<?php
}
?>