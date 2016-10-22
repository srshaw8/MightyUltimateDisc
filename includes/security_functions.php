<?php
/**
 * @author Steven Shaw
 * @copyright 2008
 */
/** setup session that is used by all pages */
$session = dbSession::getInstance();
$session->gc();
 
function check_authorization() {
	if(isset($_SESSION['player'][1])) {
		return true;
	} else {
		return false;
	}
}

function check_captain_authorization() {
	if(isset($_SESSION['player'][1]) && $_SESSION['player'][1] == "Captain") {
		return true;
	} else {
		return false;
	}
}

function check_owner_authorization() {
	if(isset($_SESSION['player'][1]) && $_SESSION['player'][1] == "Owner") {
		return true;
	} else {
		return false;
	}
}

function check_admin_authorization() {
	if(isset($_SESSION['player'][1]) && $_SESSION['player'][1] == "Admin") {
		return true;
	} else {
		return false;
	}
}

function display_non_authorization() {
	display_wrappers();
	if (isset($_SESSION['player'])) {
		unset($_SESSION['player']);
		unset($_SESSION['event']);
		$session = dbSession::getInstance();
		$session->stop();
		redirect_page("index.php");
	}
	$url = LOCATION_SITE."login.php";
	?>
	<div id="content_wrapper">
	<p class="errorLg">Sorry, in order to access this page, you need to <a href="<?php echo $url ?>">login</a>.</p>
	</div>
	<?php
}

function get_session_event_mgmt() {
	return (isset($_SESSION['event'][1]) ? $_SESSION['event'][1] : false);
}

function get_session_event_name() {
	return (isset($_SESSION['event'][2]) ? $_SESSION['event'][2] : false);
}

function get_session_event_register() {
	return (isset($_SESSION['event'][0]) ? $_SESSION['event'][0] : false);
}

function get_session_event_list() {
	return (isset($_SESSION['event'][3]) ? $_SESSION['event'][3] : false);
}

function get_session_player_first_name() {
	return (isset($_SESSION['player'][2]) ? $_SESSION['player'][2] : false);
}


function get_session_player_id() {
	return (isset($_SESSION['player'][0]) ? $_SESSION['player'][0] : false);
}

function get_session_player_role() {
	return (isset($_SESSION['player'][1]) ? $_SESSION['player'][1] : false);
}

function get_session_search() {
	return (isset($_SESSION['search']) ? $_SESSION['search'] : false);
}

function get_session_tmpShortName() {
	return (isset($_SESSION['tmpShortName']) ? $_SESSION['tmpShortName'] : false);
}

function reset_event_session($isAdmin) {
	$_SESSION['event'][1] = "";
	$_SESSION['event'][2] = "";
	if (!$isAdmin) {
		set_session_player_role('');
	}
}

function set_session_event_init(){
	/** event [
	 *	0-> eventID of registered event 
	 *	1-> eventID of managed event 
	 *	2-> event name of managed event
	 *  3-> array of IDs/names of events that player has registered for
	 */
	$_SESSION['event'][0] = "";
	$_SESSION['event'][1] = "";
	$_SESSION['event'][2] = "";
	$_SESSION['event'][3] = "";
}

function set_session_event_mgmt($eventID) {
	if (!isset($_SESSION['event'])) {
		set_session_event_init();
	}
	$_SESSION['event'][1] = $eventID;
}

function set_session_event_name($eventName) {
	if (!isset($_SESSION['event'])) {
		set_session_event_init();
	}
	$_SESSION['event'][2] = $eventName;
}

function set_session_event_register($eventID) {
	if (!isset($_SESSION['event'])) {
		set_session_event_init();
	}
	$_SESSION['event'][0] = $eventID;
}

function set_session_event_list($arrEvents) {
	if (!isset($_SESSION['event'])) {
		set_session_event_init();
	}
	$_SESSION['event'][3] = $arrEvents;
}

function set_session_player($playerArray) {
	/** 
	 *	player [
 	 * 	0->playerID 
	 *  1->role [ie. player/admin/captain/owner] 
	 *  2->first name 
	 */
	$_SESSION['player'] = $playerArray;
}

function set_session_player_role($role) {
	if (!isset($_SESSION['player'])) {
		return false;
	}
	$_SESSION['player'][1] = $role;
}

function set_session_search($searchArray) {
	/** 
	 *	player [
 	 * 	0->eventType 
	 *  1->countryCode 
	 *  2->stateCode
	 */
	$_SESSION['search'] = $searchArray;
}

function set_session_tmpShortName($tmpShortName) {
	$_SESSION['tmpShortName'] = $tmpShortName;
}

function unset_session_event_register(){
	/** event [
	 *	0-> eventID of registered event 
	 */
	if (isset($_SESSION['event'])) {
		unset($_SESSION['event'][0]);
	}
}

function unset_session_search(){
	if (isset($_SESSION['search'])) {
		unset($_SESSION['search']);
	}
}
?>