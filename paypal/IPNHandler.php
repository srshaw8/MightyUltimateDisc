<?php
/** general includes */
include_once('../locator.php');
if (IS_LOCAL) {
    include_once('../includes/includes.php');
} else if (IS_TEST) {
    include_once('../../../../data/includes_test/includes.php');
} else {
    include_once('../../../../data/includes_prod/includes.php');
}

$message = "";
$muTxnType = "unknown";
$txnID = "";
$eventID = 0;
$playerID = 0;

/** get transaction type, event ID, and if applicable, player ID from custom field ** THE ORDER CAN'T CHANGE */
if(array_key_exists("custom", $_POST)) {
	$tmpArray = explode(":",$_POST['custom']); // custom is set in utility_register_functions.php
	$muTxnType = $tmpArray[0];
	$eventID = $tmpArray[1];
	$playerID = $tmpArray[2];
}

$tmpArray = array_merge($_POST, array("cmd" => "_notify-validate"));
$postFieldsArray = array();
foreach ($tmpArray as $name => $value) {
	$postFieldsArray[] = "$name=$value";
}
$message = "Sending IPN values:\n".implode("\n", $postFieldsArray);
log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);
/** persist message to database */
if(!insert_ipn_msg($muTxnType, $eventID, $playerID, $message)){
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,"Failed to add new IPN message.");	
}

/** verify that request is coming over https */
if (!strstr(get_cur_page_URL(),"https")) {
	$message = "Received IPN for a ".$muTxnType." transaction that was not over https.";
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
	exit;
}

/** validate that a custom field transaction type exists */
if (!($muTxnType == "register" or $muTxnType == "checkout" or $muTxnType == "donation")) {
	$message = "Received IPN for a transaction without a valid custom transaction type: ".$muTxnType;
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
	exit;
}

/** validate event ID */
if (!is_numeric($eventID)) {
	$message = "Received IPN for a ".$muTxnType." transaction with an event ID in the wrong format.";
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
	exit;
} else {
	if ($eventID == 0 and $muTxnType != "donation") {
		$message = "Received IPN for a ".$muTxnType." transaction with an event ID of 0.";
		log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
		exit;
	} else {
		if (!get_event_profile_for_admin($eventID) and $muTxnType != "donation") {
			$message = "Received IPN for a ".$muTxnType." transaction with an non-existent event ID.";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
	}
}

/** validate player ID if custom transaction type is register */
if ($muTxnType == "register") {
	if (!is_numeric($playerID)) {
		$message = "Received IPN for a ".$muTxnType." transaction with a player ID in the wrong format.";
		log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
		exit;
	} else {
		if ($playerID == 0) {
			$message = "Received IPN for a ".$muTxnType." transaction with a player ID of 0.";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		} else {
			if (!get_player_profile_short($playerID)) {
				$message = "Received IPN for a ".$muTxnType." transaction with an non-existent player ID.";
				log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
				exit;
			}
		}
		/** verify that player ID is registered with event */
		if(!get_roster_player_info($eventID, $playerID)) {
			$message = "A player who is not registered for an event tried to pay for it.";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
	}
}

/** validate that a transaction ID exists */
if(!array_key_exists("txn_id", $_POST)) {
	$message = "Received IPN for a ".$muTxnType." transaction without a transaction ID.";
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
	exit;
} else {
	$txnID = $_POST['txn_id'];
}

/** return received post values back to paypal */
$ppResponseArray = 
	PPHttpPost("https://www.".PAYPAL_ENV."paypal.com/cgi-bin/webscr", implode("&", $postFieldsArray), false);
	
if(!$ppResponseArray['status']) {
	$message = "IPN handler received an error:\n";
	if(0 !== $ppResponseArray['error_no']) {
		$message .= "Error ".$ppResponseArray['error_no'].": ";
	}
	$message .= $ppResponseArray['error_msg'];
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
	exit;
}

$message = "IPN post response:\n".$ppResponseArray['httpResponse'];
log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);

if (strcmp($ppResponseArray['httpResponse'],"VERIFIED") == 0) {
	/** check that the payment_status is Completed. */
	$pmtStatus = htmlspecialchars($_POST['payment_status']);
	if ($pmtStatus != "Completed") {
		$message = "Received IPN ".$muTxnType." transaction with ID=".$txnID." and payment status = ".$pmtStatus.".";
		log_entry(Logger::IPN,Logger::WARN,$eventID,$playerID,$message);
		exit;
	}
	/** debug */
	//$message = "Received IPN ".$muTxnType." transaction with ID=".$txnID." and payment status = ".$pmtStatus.".";
	//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);

	/** check the txn_id to verify that it is not a duplicate in the ipn_tracker table */
	if (get_ipn_txn($txnID)) {
		$message = "Received IPN ".$muTxnType." transaction with ID=".$txnID." which already exists.";
		log_entry(Logger::IPN,Logger::WARN,$eventID,$playerID,$message);
		exit;
	}
	
	/** check that the receiver_email is valid - routine will vary depending on custom transaction type */
	$rcvEmail = strtolower(htmlspecialchars($_POST['receiver_email']));
	if ($muTxnType == "register") {
		$rowEvent = get_event_profile_short($eventID);
		$ppAccount = strtolower($rowEvent[Payment_Account]);
		if ($rcvEmail != $ppAccount) {
			$message = 
				"Received IPN ".$muTxnType." transaction with ID=".$txnID.", receiver email = ".$rcvEmail.", and 
				paypal account email = ".$ppAccount.".";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
		/** debug */
		//$message = 
		//	"Received IPN ".$muTxnType." transaction with ID=".$txnID.", receiver email = ".$rcvEmail.", and 
		//	paypal account email = ".$ppAccount.".";
		//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);	
	} else { /** event checkout or donation **/
		if ($rcvEmail != strtolower(MU_PAYPAL_EMAIL)) {
			$message = 
				"Received IPN ".$muTxnType." transaction with ID=".$txnID.", receiver email = ".$rcvEmail.", and 
				MU account email = ".strtolower(MU_PAYPAL_EMAIL).".";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
		/** debug */
		//$message = 
		//	"Received IPN ".$muTxnType." transaction with ID=".$txnID.", receiver email = ".$rcvEmail.", and 
		//	MU account email = ".strtolower(MU_PAYPAL_EMAIL).".";
		//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);		
	}
		
	/** check that the price (in mc_gross) and the currency (in mc_currency) are correct for the item */
	if ($muTxnType == "register") {
		$rsReport = get_report_roster_player_fees($eventID, $playerID);
		$eventFee = ($rsReport['Event_Fee']) > 0 ? $rsReport['Event_Fee'] : 0; 
		$tshirtFee = ($rsReport['TShirt_Fee']) > 0 ? $rsReport['TShirt_Fee'] : 0;
		$discFee = ($rsReport['Disc_Fee']) > 0 ? $rsReport['Disc_Fee'] : 0;
		$upaEventFee = ($rsReport['UPA_Event_Fee']) > 0 ? $rsReport['UPA_Event_Fee'] : 0;
		$totalFee = $eventFee+$tshirtFee+$discFee+$upaEventFee;
		if (number_format($_POST['mc_gross'], 2, '.', '') != number_format($totalFee, 2, '.', '')) {
			$message = 
				"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
				mc_gross = ".number_format($_POST['mc_gross'], 2, '.', '').", 
				and total roster fees = ".number_format($totalFee, 2, '.', '').".";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
		/** debug */
		//$message = 
		//	"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
		//	mc_gross = ".number_format($_POST['mc_gross'], 2, '.', '').", 
		//	and total roster fees = ".number_format($totalFee, 2, '.', '').".";
		//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);
			
		$rowEvent = get_event_profile_short($eventID);
		if ($_POST['mc_currency'] != $rowEvent['Currency_Code']) {
			$message = 
				"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
				mc_currency = ".$_POST['mc_currency'].", and 
				event currency code = ".$rowEvent['Currency_Code'].".";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
		/** debug */
		//$message = 
		//	"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
		//	mc_currency = ".$_POST['mc_currency'].", and 
		//	event currency code = ".$rowEvent['Currency_Code'].".";
		//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);
		
	} else if ($muTxnType == "checkout") { // event checkout
		if (number_format($_POST['mc_gross'], 2, '.', '') != number_format(EVENT_SETUP_FEE, 2, '.', '')) {
			$message = 
				"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
				mc_gross = ".number_format($_POST['mc_gross'], 2, '.', '').", 
				and total roster fees = ".number_format(EVENT_SETUP_FEE, 2, '.', '').".";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
		/** debug */
		//$message = 
		//	"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
		//	mc_gross = ".number_format($_POST['mc_gross'], 2, '.', '').", 
		//	and total roster fees = ".number_format(EVENT_SETUP_FEE, 2, '.', '').".";
		//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);
		
		$rowEvent = get_event_profile_short($eventID);
		if ($_POST['mc_currency'] != $rowEvent['Currency_Code']) {
			$message = 
				"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
				mc_currency = ".$_POST['mc_currency'].", and 
				event currency code = ".$rowEvent['Currency_Code'].".";
			log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
			exit;
		}
		/** debug */
		//$message = 
		//	"Received IPN ".$muTxnType." transaction with ID=".$txnID.", 
		//	mc_currency = ".$_POST['mc_currency'].", and 
		//	event currency code = ".$rowEvent['Currency_Code'].".";
		//log_entry(Logger::IPN,Logger::INFO,$eventID,$playerID,$message);
	}
	
	/** if transaction passes above validation, save it to db */
	if(!insert_ipn_txn($muTxnType,$eventID,$playerID,$_POST)){ 
		log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,"Failed to add new IPN transaction.");	
	}
	
	/** 
	 * depending on custom transaction type, update the event profile paid field or 
	 * the player's roster entry for their paid and payment type fields
	 */
	if ($muTxnType == "register") {
		if (!update_roster_player_as_paid($eventID,$playerID)) {
		log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,"Failed to update roster/player payment status.");
		}
	} else if ($muTxnType == "checkout") { // event checkout
		if (!update_event_profile_as_paid($eventID)) {
		log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,"Failed to update event profile payment status.");
		}
	}
		
} else if (strcmp($ppResponseArray['httpResponse'], "INVALID") == 0) {
	$message = "Received invalid IPN post response for a ".$muTxnType." transaction with ID ".$txnID.".";
	log_entry(Logger::IPN,Logger::ERROR,$eventID,$playerID,$message);
	exit;
}

/**
 * Send HTTP POST Request
 *
 * @param	string	The request URL
 * @param	string	The POST Message fields in &name=value pair format
 * @param	bool		determines whether to return a parsed array (true) or a raw array (false)
 * @return	array		Contains a bool status, error_msg, error_no,
 *						and the HTTP Response body(parsed=httpParsedResponseAr 
 * 						or non-parsed=httpResponse) if successful
 */
function PPHttpPost($url_, $postFields_, $parsed_) {
	//setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url_);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);

	//turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POST, 1);

	//setting the nvpreq as POST FIELD to curl
	curl_setopt($ch,CURLOPT_POSTFIELDS,$postFields_);

	//getting response from server
	$httpResponse = curl_exec($ch);

	if(!$httpResponse) {
		return array("status" => false, "error_msg" => curl_error($ch), "error_no" => curl_errno($ch));
	}

	if(!$parsed_) {
		return array("status" => true, "httpResponse" => $httpResponse);
	}

	$httpResponseAr = explode("\n", $httpResponse);

	$httpParsedResponseAr = array();
	foreach ($httpResponseAr as $i => $value) {
		$tmpAr = explode("=", $value);
		if(sizeof($tmpAr) > 1) {
			$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
		}
	}

	if(0 == sizeof($httpParsedResponseAr)) {
		$error = "Invalid HTTP Response for POST request($postFields_) to $url_.";
		return array("status" => false, "error_msg" => $error, "error_no" => 0);
	}
	return array("status" => true, "httpParsedResponseAr" => $httpParsedResponseAr);

}
?>