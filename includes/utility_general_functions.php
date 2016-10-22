<?php
/**
 * @author Steven Shaw
 * @copyright 2008
 */
function clear_selected_event() {
	/** reset event being managed (if applic) to clear highlighted event in left bar event list 
		menu - function used for when user is navigating away from event management pages
		to non-event management pages 
	**/
	$isAdmin = check_admin_authorization();
	reset_event_session($isAdmin);
} 

function convert_time_local_to_gmt($timeZoneID, $dateTimeLocal) {
	/** Set date/time format */
	$thisDateTimeFormat = 'Y-m-d H:i:s';

	/** create timezone object with user specified local timezone */
	$thisTimeZone = new DateTimeZone($timeZoneID);
	$thisDateTime =  new DateTime($dateTimeLocal, $thisTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** create timezone object with gmt timezone */
	$gmtTimeZone = new DateTimeZone(GMT_TIMEZONE_ID);
	/** apply gmt timezone to original local time */
	$thisDateTime->setTimezone($gmtTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** format the new gmt time */
	$dateTimeGMT = $thisDateTime->format($thisDateTimeFormat);
	return $dateTimeGMT;
}

function convert_time_gmt_to_local($timeZoneID, $dateTimeGMT) {
	/** Set date/time format */
	$thisDateTimeFormat = 'Y-m-d H:i:s';

	/** create timezone object with gmt timezone */
	$thisTimeZone = new DateTimeZone(GMT_TIMEZONE_ID);
	$thisDateTime =  new DateTime($dateTimeGMT, $thisTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** create timezone object with user specified local timezone */
	$localTimeZone = new DateTimeZone($timeZoneID);
	/** apply local timezone to gmt datetime */
	$thisDateTime->setTimezone($localTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** format the new local datetime */
	$dateTimeLocal = $thisDateTime->format($thisDateTimeFormat);
	return $dateTimeLocal;
 }

function convert_date_gmt_to_local_people($timeZoneID, $dateTimeGMT) {
	/** Set date/time format: */
	$thisDateTimeFormat = 'M j Y';

	/** create timezone object with gmt timezone */
	$thisTimeZone = new DateTimeZone(GMT_TIMEZONE_ID);
	$thisDateTime =  new DateTime($dateTimeGMT, $thisTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** create timezone object with user specified local timezone */
	$localTimeZone = new DateTimeZone($timeZoneID);
	/** apply local timezone to gmt datetime */
	$thisDateTime->setTimezone($localTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	
	/** add a day to account for the loss of a day during the conversion from GMT to local */
	$thisDateTime->modify("+1 day");
	
	/** format the new local datetime */
	$dateTimeLocal = $thisDateTime->format($thisDateTimeFormat);

	return $dateTimeLocal;
 }

function convert_time_gmt_to_local_people($timeZoneID, $dateTimeGMT) {
	/** Set date/time format */
	$thisDateTimeFormat = 'M j Y g:i A';
	
	/** create timezone object with gmt timezone */
	$thisTimeZone = new DateTimeZone(GMT_TIMEZONE_ID);
	$thisDateTime =  new DateTime($dateTimeGMT, $thisTimeZone);
	
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** create timezone object with user specified local timezone */
	$localTimeZone = new DateTimeZone($timeZoneID);
	/** apply local timezone to gmt datetime */
	$thisDateTime->setTimezone($localTimeZone);
	//echo $thisDateTime->format($thisDateTimeFormat), $thisDateTime->format('I')?' DST':'';
	/** format the new local datetime */
	$dateTimeLocal = $thisDateTime->format($thisDateTimeFormat);
	return $dateTimeLocal;
 }

function get_current_gmt_time() {
	$thisDateTimeFormat = 'Y-m-d H:i:s';
	$gmtTimeZone = new DateTimeZone(GMT_TIMEZONE_ID);
	$thisCurrentDateTime =  new DateTime("now", $gmtTimeZone);
	$tsCurrent = $thisCurrentDateTime->format($thisDateTimeFormat);
	return $tsCurrent;
}

function get_cur_page_URL() {
	$pageURL = 'http';
	if (isset($_SERVER['HTTPS'])) {
		if ($_SERVER['HTTPS'] == "on") {$pageURL .= "s";}
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function get_random_word($min_length, $max_length) {
	// generate a random word
	$word = '';
	//remember to change this path to suit your system
    if (IS_LOCAL) {
	   $dictionary = 'includes/words.txt';  // the ispell dictionary
    } else if (IS_TEST) {
        $dictionary = '/nfs/c04/h02/mnt/63137/data/includes_test/words.txt';  // the ispell dictionary
    } else if (IS_PROD) {
        $dictionary = '/nfs/c04/h02/mnt/63137/data/includes_prod/words.txt';  // the ispell dictionary
    }
       
	$fp = fopen($dictionary, 'r');
	if(!$fp)
		return false; 
	$size = filesize($dictionary);

	// go to a random location in dictionary
	srand ((double) microtime() * 1000000);
	$rand_location = rand(0, $size);
	fseek($fp, $rand_location);

	// get the next whole word of the right length in the file
	while (strlen($word)< $min_length || strlen($word)>$max_length || strstr($word, "'")) {  
		if (feof($fp))   
			fseek($fp, 0);        // if at end, go to start
		$word = fgets($fp, 80);  // skip first word as it could be partial
		$word = fgets($fp, 80);  // the potential password
	};
	$word=trim($word); // trim the trailing \n from fgets
	return $word;  
}

function get_signup_status($startRegDateTime, $endRegDateTime, $endEventDate) {
	/** set up array to hold signup status for event */
	$signupStatus = "";
	
	/** times coming in are in GMT format - they come from the DB */
	$thisDateTimeFormat = 'Y-m-d H:i:s';
	$gmtTimeZone = new DateTimeZone(GMT_TIMEZONE_ID);

	$thisRegStartDateTime =  new DateTime($startRegDateTime, $gmtTimeZone);
	$thisRegEndDateTime =  new DateTime($endRegDateTime, $gmtTimeZone);
	$thisEventEndDateTime =  new DateTime($endEventDate, $gmtTimeZone);

	$tsRegStart = $thisRegStartDateTime->format($thisDateTimeFormat);
	$tsRegEnd = $thisRegEndDateTime->format($thisDateTimeFormat);
	$tsEventEnd = $thisEventEndDateTime->format($thisDateTimeFormat);
	$tsCurrent = get_current_gmt_time();
	
  	if (strtotime($tsRegStart) > strtotime($tsCurrent)) {
		$signupStatus = "Signups not started";
 	}  else if ((strtotime($tsRegStart)<=strtotime($tsCurrent)) and	(strtotime($tsRegEnd)>=strtotime($tsCurrent))) {
		$signupStatus = "Signups open";
	/** 86400 added to extend day so that event is really closed when we say its closed */
	}  else if (((strtotime($tsEventEnd)+86400)>=strtotime($tsCurrent)) and	
				(strtotime($tsRegEnd)<=strtotime($tsCurrent))) {
		$signupStatus = "Signups closed";
	/** 86400 added to extend day so that event is really closed when we say its closed */
	} else if ((strtotime($tsEventEnd)+86400) < strtotime($tsCurrent)) { 
		$signupStatus = "Event has ended";
	}
	return $signupStatus;
}

function get_site_URL() {
	$siteURL = 'http';
	$siteURL .= "://";
	$siteURL .= $_SERVER["SERVER_NAME"];
	return $siteURL;
}

function redirect_page($goHere) {
	$url = (strstr($goHere,"event_checkout") or strstr($goHere,"register")) ? SECURE_LOCATION_SITE.$goHere : LOCATION_SITE.$goHere;	
	header("Location: $url");
	exit();
}

class WaitListPosition {
	private $position = 0;
	private $total = 0;
	
	public function __construct($eventID=0,$playerID=0,$gender='') {
		$rsWaitPlayers = get_wait_list_players($eventID,$gender);
		$arrayWaitPlayers = array();
		if ($rsWaitPlayers) {
			$numResults = mysql_num_rows($rsWaitPlayers);
			if ($numResults > 0) {
				for($count=1; $row=mysql_fetch_array($rsWaitPlayers); $count++) {
					$arrayWaitPlayers[$count] = $row['Player_ID']; 				
				}
				$this->total = sizeof($arrayWaitPlayers);
				while (list($key, $value) = each ($arrayWaitPlayers)) {
					if ($value == $playerID) {
						$this->position = $key;
						break;
					}
				}
			}
		}
	}
	
	public function get_position() {
		return $this->position;
	}
	
	public function get_total() {
		return $this->total;
	}
}
?>