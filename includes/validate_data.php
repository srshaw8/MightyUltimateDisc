<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
function validate($page, $enteredData) {
	$pageChecks = array();
	$pageFields = array();
	$errors = array();
	$pageChecks = load_page_checks($page);
	$pageFields = load_page_fields($page, $enteredData);
	return $errors = perform_checks($pageChecks, $pageFields, $errors);
}

function load_page_checks($page) {
	/** general field checks:
 	 * 	d - date	
	 *  dt - datetime
	 *  dend - end date is not earlier than beginning date
 	 *  ed - email dupe
 	 *  edexist - email dupe of others, not yourself
 	 *  ee - email exist 
	 *  ef - email format
	 *  n - number
	 *  naltd - limited number in array
	 *  naany - any number in array
	 *  nl - string list of numbers
	 * 	pal - password length
	 * 	pam - password match
	 *  pcus - post code us
  	 *  pidd - player id dupe
	 *  pidf - player id format
	 *  plog - player login authentication
	 *  s - string  
	 *  t - telephone
	 * 	vs - value is set
	 *  y - single yes value
	 *  yn - either yes or no value
	 * 
	 *  unique field checks:
	 *  uctc - country
	 *  ug - gender
	 *  ul - legal
	 *  ulmto - max # of additional owners
	 *  ustc - state
	 *  utss - t shirt size
	 *  uyex - years experience
	 */
	$pageChecks = array(); 
	switch ($page) {
		case "login":
			$pageChecks = load_login_check();
			break;
		case "newAccount":
			$pageChecks = load_login_new_account_check();
			break;
		case "forgetPassword":
			$pageChecks = load_login_forget_check();
			break;
		case "forgetPlayerID":
			$pageChecks = load_login_forget_check();
			break;
		case "updateTerms":
			$pageChecks = load_terms_check();
			break;
		case "search":
			$pageChecks = load_search_check();
			break;
		case "playerSave":
			$pageChecks = load_player_check();
			break;
		case "register":
			$pageChecks = load_register_check();
			break;
		case "registerDisc":
			$pageChecks = load_register_disc_check();
			break;
		case "accountID":
			$pageChecks = load_account_id_check();
			break;
		case "accountPassword":
			$pageChecks = load_account_password_check();
			break;
		case "accountEmail":
			$pageChecks = load_account_email_check();
			break;
		case "eventEditBase":
			$pageChecks = load_event_base_check();
			break;
		case "eventEditPlus":
			$pageChecks = load_event_plus_check();
			break;
		case "eventEditCheck":
			$pageChecks = load_event_check_check();
			break;
		case "eventEditPayPal":
			$pageChecks = load_event_paypal_check();
			break;
		case "teamEdit":
			$pageChecks = load_team_check();
			break;
		case "eventHPSave":
			$pageChecks = load_event_home_page_check();
			break;
		case "rosterEdit":
			$pageChecks = load_roster_check();
			break;
		case "teamEmail":
			$pageChecks = load_team_email_check();
			break;
		case "contactEmail":
			$pageChecks = load_contact_email_check();
			break;
	} 
	return $pageChecks;
}

function load_page_fields($page, $enteredData) {
	$pageFields = array(); 
	switch ($page) {
		case "login":
			$pageFields = load_login_fields($enteredData);
			break;
		case "newAccount":
			$pageFields = load_login_new_account_fields($enteredData);
			break;
		case "forgetPassword":
			$pageFields = load_login_forget_fields($enteredData);
			break;
		case "forgetPlayerID":
			$pageFields = load_login_forget_fields($enteredData);
			break;
		case "updateTerms":
			$pageFields = load_terms_fields($enteredData);
			break;
		case "search":
			$pageFields = load_search_fields($enteredData);
			break;
		case "playerSave":
			$pageFields = load_player_fields($enteredData);
			break;
		case "register":
			$pageFields = load_register_fields($enteredData);
			break;
		case "registerDisc":
			$pageFields = load_register_disc_fields($enteredData);
			break;
		case "accountID":
			$pageFields = load_account_id_fields($enteredData);
			break;
		case "accountPassword":
			$pageFields = load_account_password_fields($enteredData);
			break;
		case "accountEmail":
			$pageFields = load_account_email_fields($enteredData);
			break;
		case "eventEditBase":
			$pageFields = load_event_base_fields($enteredData);
			break;
		case "eventEditPlus":
			$pageFields = load_event_plus_fields($enteredData);
			break;			
		case "eventEditCheck":
			$pageFields = load_event_check_fields($enteredData);
			break;
		case "eventEditPayPal":
			$pageFields = load_event_paypal_fields($enteredData);
			break;
		case "teamEdit":
			$pageFields = load_team_fields($enteredData);
			break;
		case "eventHPSave":
			$pageFields = load_event_home_page_fields($enteredData);
			break;
		case "rosterEdit":
			$pageFields = load_roster_fields($enteredData);
			break;
		case "teamEmail":
			$pageFields = load_team_email_fields($enteredData);
			break;
		case "contactEmail":
			$pageFields = load_contact_email_fields($enteredData);
			break;
	}
	return $pageFields;
}	

function load_login_check(){
	$pageChecks = array('Short_Name' => 'vs,s',
						'Password' => 'vs,s,plog');
	return $pageChecks; 
}

function load_login_fields($enteredData) {
	$pageFields = array('Short_Name' => $enteredData['Short_Name'],
						'Password' => $enteredData['Password']);
	return $pageFields;
}

function load_login_new_account_check(){
	$pageChecks = array('Short_Name' => 'vs,s,pidd,pidf',
						'Email' => 'vs,ef,ed',
						'Password' => 'vs,s,pal',
						'password2' => 'vs,s,pal,pam',
						'Email_Opt_Capt' => 'y',
						'Email_Opt_MU' => 'y',
						'Terms' => 'ul');
	return $pageChecks; 
}

function load_login_new_account_fields($enteredData) {
	$pageFields = array('Short_Name' => $enteredData['Short_Name'],
				'Email' => $enteredData['Email'],
				'Password' => $enteredData['Password'],
				'password2' => $enteredData['password2'],
				'Email_Opt_Capt' => ((isset($enteredData['Email_Opt_Capt'])) ? $enteredData['Email_Opt_Capt']: ""),
				'Email_Opt_MU' => ((isset($enteredData['Email_Opt_MU'])) ? $enteredData['Email_Opt_MU']: ""),
				'Terms' => ((isset($enteredData['Terms'])) ? $enteredData['Terms']: ""));
	return $pageFields;
}

function load_login_forget_check(){
	$pageChecks = array('Email' => 'vs,ef,ee');
	return $pageChecks; 
}

function load_login_forget_fields($enteredData) {
	$pageFields = array('Email' => $enteredData['Email']);
	return $pageFields;
}

function load_terms_check(){
	$pageChecks = array('Short_Name' => 'vs,s',
						'Password' => 'vs,s,plog',
						'Email_Opt_Capt' => 'y',
						'Email_Opt_MU' => 'y',
						'Terms' => 'ul');
	return $pageChecks; 
}

function load_terms_fields($enteredData) {
	$pageFields = array('Short_Name' => $enteredData['Short_Name'],
				'Password' => $enteredData['Password'],
				'Email_Opt_Capt' => ((isset($enteredData['Email_Opt_Capt'])) ? $enteredData['Email_Opt_Capt']: ""),
				'Email_Opt_MU' => ((isset($enteredData['Email_Opt_MU'])) ? $enteredData['Email_Opt_MU']: ""),
				'Terms' => ((isset($enteredData['Terms'])) ? $enteredData['Terms']: ""));
	return $pageFields;
}

function load_search_check(){
	$pageChecks = array('Event_Type' => 'vs,naltd',
						'Country' => 'vs,uctc',
						'State_Prov' => 'vs,ustc');
	return $pageChecks; 
}

function load_search_fields($enteredData) {
	$pageFields = array('Event_Type' => ((isset($enteredData['Event_Type'])) ? $enteredData['Event_Type']: ""),
						'Country' => $enteredData['Country'],
						'State_Prov' => $enteredData['State_Prov']);
	return $pageFields;
}

function load_player_check(){
	$pageChecks = array('First_Name' => 'vs,s',
						'Last_Name' => 'vs,s',
						'Address' => 'vs,s',
						'City' => 'vs,s',
						'State_Prov' => 'vs,ustc',
						'Post_Code' => 'vs,pcus',
						'Country' => 'vs,uctc',
						'H_Phone' => 'vs,t',
						'C_Phone' => 't',
						'W_Phone' => 't',
						'E_Contact_Name' => 'vs,s',
						'E_Contact_Phone' => 'vs,t',
						'Gender' => 'vs,ug',
						'Height' => 'vs,n',
						'Conditionx' => 'vs,n',
						'Skill_Lvl' => 'vs,n',
						'Skill_Lvl_Def' => 'vs,n',
						'Play_Lvl' => 'vs,naltd',
						'Yr_Exp' => 'vs,uyex',
						'T_Shirt_Size' => 'vs,utss',
						'Buddy_Name' => 's',
						'UPA_Cur_Member' => 'vs,yn',
						'UPA_Number' => 'vs,n',
						'Student' => 'y',
						'Over18' => 'vs,yn');
	return $pageChecks; 
}

function load_player_fields($enteredData) {
	$pageFields = array('First_Name' => $enteredData['First_Name'],
				'Last_Name' => $enteredData['Last_Name'],
				'Address' => $enteredData['Address'],
				'City' => $enteredData['City'],
				'State_Prov' => $enteredData['State_Prov'],
				'Post_Code' => $enteredData['Post_Code'],
				'Country' => $enteredData['Country'],
				'H_Phone' => $enteredData['H_Phone'],
				'C_Phone' => $enteredData['C_Phone'],
				'W_Phone' => $enteredData['W_Phone'],
				'E_Contact_Name' => $enteredData['E_Contact_Name'],
				'E_Contact_Phone' => $enteredData['E_Contact_Phone'],
				'Gender' => $enteredData['Gender'],
				'Height' => $enteredData['Height'],
				'Conditionx' => $enteredData['Conditionx'],
				'Skill_Lvl' => $enteredData['Skill_Lvl'],
				'Skill_Lvl_Def' => $enteredData['Skill_Lvl_Def'],
				'Play_Lvl' => ((isset($enteredData['Play_Lvl'])) ? $enteredData['Play_Lvl']: ""),
				'Yr_Exp' => $enteredData['Yr_Exp'],
				'T_Shirt_Size' => (isset($enteredData['T_Shirt_Size']) ? $enteredData['T_Shirt_Size'] : ""),
				'Buddy_Name' => $enteredData['Buddy_Name'],
				'UPA_Cur_Member' => (isset($enteredData['UPA_Cur_Member']) ? $enteredData['UPA_Cur_Member'] : ""),
				'UPA_Number' => $enteredData['UPA_Number'],
				'Student' => (isset($enteredData['Student']) ? $enteredData['Student'] : ""),
				'Over18' => (isset($enteredData['Over18']) ? $enteredData['Over18'] : ""));
	return $pageFields;
}

function load_register_check(){
	$pageChecks = array('Pct_Of_Games' => 'vs,n',
						'T_Shirt_Order' => 'vs,yn',
						'Disc_Order' => 'vs,yn',
						'UPA_Enrollment' => 'vs,n');
	return $pageChecks; 
}

function load_register_fields($enteredData) {
	$pageFields = array('Pct_Of_Games' => $enteredData['Pct_Of_Games'],
					'T_Shirt_Order' => (isset($enteredData['T_Shirt_Order']) ? $enteredData['T_Shirt_Order'] : ""),
					'Disc_Order' => (isset($enteredData['Disc_Order']) ? $enteredData['Disc_Order'] : ""),
					'UPA_Enrollment' => $enteredData['UPA_Enrollment']);
	return $pageFields;
}

function load_register_disc_check(){
	$pageChecks = array('Pct_Of_Games' => 'vs,n',
						'T_Shirt_Order' => 'vs,yn',
						'Disc_Order' => 'vs,yn',
						'Disc_Count' => 'vs,n',
						'UPA_Enrollment' => 'vs,n');
	return $pageChecks; 
}

function load_register_disc_fields($enteredData) {
	$pageFields = array('Pct_Of_Games' => $enteredData['Pct_Of_Games'],
						'T_Shirt_Order' => $enteredData['T_Shirt_Order'],
						'Disc_Order' => $enteredData['Disc_Order'],
						'Disc_Count' => $enteredData['Disc_Count'],
						'UPA_Enrollment' => $enteredData['UPA_Enrollment']);
	return $pageFields;
}

function load_account_id_check(){
	$pageChecks = array('Short_Name' => 'vs,s,pidd,pidf');
	return $pageChecks; 
}

function load_account_id_fields($enteredData) {
	$pageFields = array('Short_Name' => $enteredData['Short_Name']);
	return $pageFields;
}

function load_account_password_check(){
	$pageChecks = array('passwordOld' => 'vs,s,pal,plog',
						'Password' => 'vs,s,pal',
						'password2' => 'vs,s,pal,pam');
	return $pageChecks; 
}

function load_account_password_fields($enteredData) {
	$pageFields = array('Short_Name' => $enteredData['Short_Name'],
						'passwordOld' => $enteredData['passwordOld'],
						'Password' => $enteredData['Password'],
						'password2' => $enteredData['password2']);
	return $pageFields;
}

function load_account_email_check(){
	$pageChecks = array('Email' => 'vs,ef,edexist',
						'Email_Opt_Capt' => 'y',
						'Email_Opt_MU' => 'y');
	return $pageChecks; 
}

function load_account_email_fields($enteredData) {
	$pageFields = array('Email' => $enteredData['Email'],
						'Email_Opt_Capt' => 
							((isset($enteredData['Email_Opt_Capt'])) ? $enteredData['Email_Opt_Capt']: ""),
						'Email_Opt_MU' => 
							((isset($enteredData['Email_Opt_MU'])) ? $enteredData['Email_Opt_MU']: ""));
	return $pageFields;
}

function load_event_base_check(){
	$pageChecks = array('Event_Name' => 'vs,s',
						'Event_Type' => 'vs,n',
						'Country' => 'vs,uctc',
						'State_Prov' => 'vs,ustc',
						'City' => 'vs,s',
						'Event_Time' => 'vs,s',
						'Days_Of_Week' => 'vs,naltd',
						'Location' => 'vs,s',
						'Location_Link' => 's',
						'Contact_Name' => 'vs,s',
						'Contact_Phone' => 'vs,t',
						'Contact_Email' => 'vs,ef',
						'Publish_Phone' => 'vs,yn',
						'Publish_Event' => 'yn',
						'Payment_Status' => 'yn',
						'Owner_List' => 'naany,ulmto');
	return $pageChecks; 
}

function load_event_base_fields($enteredData) {
	$pageFields = array('Event_Name' => $enteredData['Event_Name'],
						'Event_Type' => $enteredData['Event_Type'],
						'Country' => $enteredData['Country'],
						'State_Prov' => $enteredData['State_Prov'],
						'City' => $enteredData['City'],
						'Event_Time' => $enteredData['Event_Time'],
						'Days_Of_Week' => ((isset($enteredData['Days_Of_Week']))?$enteredData['Days_Of_Week']: ""),
						'Location' => $enteredData['Location'],
						'Location_Link' => $enteredData['Location_Link'],
						'Contact_Name' => $enteredData['Contact_Name'],
						'Contact_Phone' => $enteredData['Contact_Phone'],
						'Contact_Email' => $enteredData['Contact_Email'],
						'Publish_Phone' => $enteredData['Publish_Phone'],
						'Publish_Event' => $enteredData['Publish_Event'],
						'Payment_Status' => $enteredData['Payment_Status'],
						'Owner_List' => $enteredData['Owner_List']);
	return $pageFields;
}

function load_event_plus_check(){
	$pageChecks = array('Org_Sponsor' => 'vs,s',
						'Reg_Begin' => 'vs,dt',
						'Reg_End' => 'vs,dt,dend',
						'Timezone_ID' => 'vs,s',
						'Event_Begin' => 'vs,d',
						'Event_End' => 'vs,d,dend',
						'Number_Of_Teams' => 'vs,n',
						'Players_Per_Team' => 'vs,s',
						'Team_Ratio' => 's',
						'Limit_Men' => 'vs,n',
						'Limit_Women' => 'vs,n',
						'UPA_Event' => 'vs,yn',
						'Event_Fee' => 'vs,n',
						'Event_TShirt_Fee' => 'vs,n',
						'Event_Disc_Fee' => 'vs,n',
						'Payment_Type' => 'vs,naltd',
						'Payment_Deadline' => 'vs,d');
	return $pageChecks; 
}

function load_event_plus_fields($enteredData) {
	$pageFields = array('Org_Sponsor' => $enteredData['Org_Sponsor'],
						'Reg_Begin' => $enteredData['Reg_Begin'],
						'Reg_End' => $enteredData['Reg_End'],
						'Timezone_ID' => $enteredData['Timezone_ID'],
						'Event_Begin' => $enteredData['Event_Begin'],
						'Event_End' => $enteredData['Event_End'],
						'Number_Of_Teams' => $enteredData['Number_Of_Teams'],
						'Players_Per_Team' => $enteredData['Players_Per_Team'],
						'Team_Ratio' => $enteredData['Team_Ratio'],
						'Limit_Men' => $enteredData['Limit_Men'],
						'Limit_Women' => $enteredData['Limit_Women'],
						'UPA_Event' => $enteredData['UPA_Event'],
						'Event_Fee' => $enteredData['Event_Fee'],
						'Event_TShirt_Fee' => $enteredData['Event_TShirt_Fee'],
						'Event_Disc_Fee' => $enteredData['Event_Disc_Fee'],
						'Payment_Type' => $enteredData['Payment_Type'],
						'Payment_Deadline' => $enteredData['Payment_Deadline']);
	return $pageFields;
}

function load_event_check_check(){
	$pageChecks = array('Payment_Chk_Payee' => 'vs,s',
						'Payment_Chk_Address' => 'vs,s',
						'Payment_Chk_City' => 'vs,s',
						'Payment_Chk_State' => 'vs,ustc',
						'Payment_Chk_Zip' => 'vs,pcus');
	return $pageChecks; 
}

function load_event_check_fields($enteredData) {
	$pageFields = array('Payment_Chk_Payee' => $enteredData['Payment_Chk_Payee'],
						'Payment_Chk_Address' => $enteredData['Payment_Chk_Address'],
						'Payment_Chk_City' => $enteredData['Payment_Chk_City'],
						'Payment_Chk_State' => $enteredData['Payment_Chk_State'],
						'Payment_Chk_Zip' => $enteredData['Payment_Chk_Zip']);
	return $pageFields;
}

function load_event_paypal_check(){
	$pageChecks = array('Payment_Account' => 'vs,s',
						'Payment_Item_Name' => 'vs,s');
	return $pageChecks; 
}

function load_event_paypal_fields($enteredData) {
	$pageFields = array('Payment_Account' => $enteredData['Payment_Account'],
						'Payment_Item_Name' => $enteredData['Payment_Item_Name']);
	return $pageFields;
}

function load_team_check(){
	$pageChecks = array('Team_Name' => 'vs,s',
						'Player_List' => 'nl', 
						'Captain_List' => 'naany');
	return $pageChecks; 
}

function load_team_fields($enteredData) {
	$pageFields = array('Team_Name' => $enteredData['Team_Name'],
						'Player_List' => $enteredData['Player_List'], 
						'Captain_List' => $enteredData['Captain_List']);
	return $pageFields;
}

function load_event_home_page_check(){
	$pageChecks = array('Publish_Home_Page' => 'vs,yn');
	return $pageChecks; 
}

function load_event_home_page_fields($enteredData) {
	$pageFields = array('Publish_Home_Page' => $enteredData['Publish_Home_Page']);
	return $pageFields;
}

function load_roster_check(){
	$pageChecks = array('Event_Fee' => 'vs,n',
						'TShirt_Fee' => 'vs,n',
						'UPA_Event_Fee' => 'vs,n',
						'Disc_Fee' => 'vs,n',
						'Disc_Count' => 'vs,n',
						'Registered' => 'vs,yn',
						'Payment_Status' => 'vs,yn',
						'Payment_Type' => 'n');
	return $pageChecks; 
}

function load_roster_fields($enteredData) {
	$pageFields = array('Event_Fee' => $enteredData['Event_Fee'],
						'TShirt_Fee' => $enteredData['TShirt_Fee'],
						'UPA_Event_Fee' => $enteredData['UPA_Event_Fee'],
						'Disc_Fee' => $enteredData['Disc_Fee'],
						'Disc_Count' => $enteredData['Disc_Count'],
						'Registered' => $enteredData['Registered'],
						'Payment_Status' => $enteredData['Payment_Status'],
						'Payment_Type' => ((isset($enteredData['Payment_Type'])) ? $enteredData['Payment_Type']: "")
						);
	return $pageFields;
}

function load_team_email_check(){
	$pageChecks = array('Recipient' => 'vs,n',
						'Subject' => 'vs,s',
						'Message' => 'vs');
	return $pageChecks; 
}

function load_team_email_fields($enteredData) {
	$pageFields = array('Recipient' => $enteredData['Recipient'],
						'Subject' => $enteredData['Subject'],
						'Message' => $enteredData['Message']);
	return $pageFields;
}

function load_contact_email_check(){
	$pageChecks = array('From' => 'vs,ef',
						'Subject' => 'vs,s',
						'Message' => 'vs');
	return $pageChecks; 
}

function load_contact_email_fields($enteredData) {
	$pageFields = array('From' => $enteredData['From'],
						'Subject' => $enteredData['Subject'],
						'Message' => $enteredData['Message']);
	return $pageFields;
}

function perform_checks($pageChecks, $pageFields, $errors) {
	foreach ($pageChecks as $key => $value) {
		/** get the field value entered by user */
		$thisValue = $pageFields[$key];
		/** get the comma seperated list of checks for this field */
		$allChecks = $value;
		$thisCheck = explode(",", $allChecks);
		/** cycle thru and perform each check, add error message to error array */
		foreach ($thisCheck as $thisCheckVal) {
			/** initialize variable that holds error message for this check iteration*/
			$thisError = "";
			switch ($thisCheckVal) {
			case "d":
				$thisRegex = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';
				if (!check_date_format($thisValue, $thisRegex)) {
					$thisError = "A valid date should be entered in the following format: YYYY-MM-DD";
				}
				break;
			case "dt":
				if (!check_date_time($thisValue)) {
					$thisError = 
					"A valid date time value should be entered in the following format: YYYY-MM-DD HH:MM:SS";
				}
				break;
			case "dend":
				$compareValue = '';
				if ($key == 'Reg_End') {
					$compareValue = $pageFields['Reg_Begin']; 
				} else if ($key == 'Event_End') { 
					$compareValue = $pageFields['Event_Begin'];
				}
				if ($compareValue <> '') {
					if (!check_end_begin_dates($compareValue, $thisValue)) {
						$thisError = "The end date must occur after the begin date.";
					}
				}
				break;			
			case "ed":
				if (check_email_exist($thisValue)) {
					$thisError = "The email entered already exists. Please try again.";
				}
				break;
			case "edexist":
				$playerID = get_session_player_id();
				if (check_email_exist_others($playerID, $thisValue)) {
					$thisError = "The email address entered already exists. Please try again.";
				}
				break;
			case "ee":
				if (!check_email_exist($thisValue)) {
					$thisError = "The email address entered could not be found. Please try again.";
				}
				break;				
			case "ef":
				if (!check_email_format($thisValue)) {
					$thisError = "The format of the email address is incorrect.";
				}
				break;
			case "n":
				if (!check_value_is_number($thisValue)) {
					$thisError = "A numeric entry is required for this field.";
				}
				break;
			case "naany":
				if (check_value_is_set($thisValue)) {
					foreach ($thisValue as $value) {  
						if (!check_value_is_number($value)) {
							$thisError = "The value entered is not valid for this field.";
						}
					}
				}
				break;
			case "naltd":
				$thisArray = array('1','2','3','4','5','6','7','8','9',',');
				if (check_value_is_set($thisValue)) {
					foreach ($thisValue as $value) {  
						if (!check_value_is_in_array($value,$thisArray)) {
							$thisError = "The value entered is not valid for this field.";
						}
					}
				}
				break;
			case "nl":
				if (is_string($thisValue)) {
					$thisValue = explode(",",$thisValue);
					foreach ($thisValue as $value) {
						if (!check_value_is_number($value)) {
							$thisError = "The value entered is not a valid number.";
						}	
					}
				} else {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "pal":
				if (!check_password_length($thisValue)) {
					$thisError = "Password must be between and 6-13 characters long.";
				}
				break;
			case "pam":
				if (!check_password_match($pageFields['Password'],$thisValue)) {
					$thisError = "The passwords entered do not match. Please try again.";
				}
				break;
			case "pidd":
				if (check_player_id_dupe($thisValue)) {
					$thisError = "The player ID entered already exists. Please try again.";
				}
				break;
			case "pidf":
				$thisRegex ='/^[a-z\d_@]{6,20}$/i';
				if (!check_value_regex($thisValue, $thisRegex)) {
					$thisError = "Your player ID must be an alphanumeric between 6-20 characters long.";
				}
				break;
			case "plog":
				if (!check_player_login($pageFields['Short_Name'],$thisValue)) {
					$thisError = "Your player ID and/or password were not valid. Please try again.";
				}
				break;
			case "pcus":
				if (!check_post_code_us($thisValue)) {
					$thisError = "The post code must consist of five numbers.";
				}
				break;
			case "s":
				if (!check_value_is_string($thisValue)) {
					$thisError = "A valid entry is required for this field.";
				}
				break;
			case "t":
				$thisRegex = '/^(\([2-9]{1}[0-9]{2}\)[0-9]{3,3}[-])[0-9]{4,4}$/';
				if (!check_value_regex($thisValue, $thisRegex)) {
					$thisError = "The telephone number should be in the following format:(###)###-####";
				}
				break;
			case "vs":
				if (!check_value_is_set($thisValue)) {
					$thisError = "Entry of a value is required for this field.";
				}
				break;
			case "y":
				$thisArray = array('Y');
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "yn":
				$thisArray = array('Y','N');
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "ug":
				$thisArray = array('M','F');
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "ul":
				$thisArray = array('Y');
				if (!check_value_is_set($thisValue) or !check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "In order to create a player account, you must agree to the terms of use.";
				}
				break;
			case "ulmto":
				if (!check_value_max($thisValue)) {
					$thisError = "Only two additional owners can be assigned to an event.";
				}
				break;
			case "uctc":
				$thisArray = array();
				$countriesResult = get_countries();
				while ($row=mysql_fetch_array($countriesResult)) {
					array_push($thisArray, $row["Code"]);
				}
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "ustc":
				$thisArray = array();
				$statesResult = get_states();
				while ($row=mysql_fetch_array($statesResult)) {
					array_push($thisArray, $row["Code"]);
				}
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "utss":
				$thisArray = array('S','M', 'L', 'XL');
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			case "uyex":
				$thisArray = array('<1','1-4','5-9','10-14','15-19','20+'); 
				if (!check_value_is_in_array($thisValue,$thisArray)) {
					$thisError = "The value entered is not valid for this field.";
				}
				break;
			}
			if (check_value_is_set($thisError)) {
				/** check if field key exists in error array - has another error message
			 	 * 	already been recorded?  If so, then concatenate the error messages 
				 *  with a semi-colon.  If not, then add the field key and message to error array
				 */   
				if (array_key_exists($key, $errors)) {
					$existMessage = $errors[$key];
					if (check_value_is_set($existMessage)) {
						$existMessage = $existMessage.";".$thisError;
					} else {
						$existMessage = $thisError;
					}
					$error[$key] = $existMessage;
				} else {
					$errors[$key] = $thisError;
				}	
		 	}
		}
	}
	return $errors;
}

function check_email_exist($thisValue) {
	if (check_email_exists($thisValue)) {
		return true;
	}
	return false;
}

function check_email_exist_others($playerID, $thisValue) {
	if (check_email_exists_others($playerID, $thisValue)) {
		return true; 
	}
	return false;
}

/** courtesy of Douglas Lovell - http://www.linuxjournal.com/article/9585 */
function check_email_format($email) {
	$isValid = true;
   	$atIndex = strrpos($email, "@");
   	if (is_bool($atIndex) && !$atIndex) {
    	$isValid = false;
   	} else {
    	$domain = substr($email, $atIndex+1);
      	$local = substr($email, 0, $atIndex);
      	$localLen = strlen($local);
      	$domainLen = strlen($domain);
      	if ($localLen < 1 || $localLen > 64) {
        	// local part length exceeded
         	$isValid = false;
      	} else if ($domainLen < 1 || $domainLen > 255) {
        	// domain part length exceeded
         	$isValid = false;
      	} else if ($local[0] == '.' || $local[$localLen-1] == '.') {
        	// local part starts or ends with '.'
         	$isValid = false;
      	} else if (preg_match('/\\.\\./', $local)) {
        	// local part has two consecutive dots
         	$isValid = false;
      	} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
        	// character not valid in domain part
         	$isValid = false;
      	} else if (preg_match('/\\.\\./', $domain)) {
        	// domain part has two consecutive dots
         	$isValid = false;
      	} else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))) {
        	// character not valid in local part unless 
         	// local part is quoted
         	if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))) {
            	$isValid = false;
         	}
    	}
    
	    /** for linux server host */
		if (!strstr(LOCATION_SITE,"local")) {
			if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
				// domain not found in DNS
		        $isValid = false;
		    }
		} else {	     
			/** for windows server host */
		    if ($isValid && !(myCheckDNSRR($domain,"MX") || myCheckDNSRR($domain,"A"))) {
		    	// domain not found in DNS
		        $isValid = false;
		    }
		}
   	}
	return $isValid;
}

function myCheckDNSRR($hostName, $recType = '') {
	if(!empty($hostName)) {
		if( $recType == '' ) $recType = "MX";
		exec("nslookup -type=$recType $hostName", $result);
		// check each line to find the one that starts with the host
		// name. If it exists then the function succeeded.
		foreach ($result as $line) {
			if(eregi("^$hostName",$line)) {
				return true;
			}
		}
	}
}

function check_end_begin_dates($compareValue, $thisValue) {
	if ($compareValue > $thisValue) {
		return false;
	}
	return true;
}

function check_password_length($thisValue) {
	if (strlen($thisValue) <6 || strlen($thisValue) >13) {
		return false;
	}
	return true;
}

function check_password_match($password,$password2) {
	if ($password == $password2) {
		return true;
	}
	return false;
}

function check_player_id_dupe($thisValue) {
	if (check_dupe_short_name($thisValue)) {
		return true;
	}
	return false;
}

function check_player_login($shortName,$thisValue) {
	if ($playerID=get_player_id($shortName,$thisValue)) {
		return true;
	}
	return false;
}

function check_post_code_us($thisValue) {
	if (check_value_is_set($thisValue)) {
		if (!ctype_digit($thisValue) | strlen($thisValue) != 5) { 
			return false;
		}
	}
	return true;
}

function check_value_is_in_array($thisValue, $thisArray) {
	if (check_value_is_set($thisValue)) {
		if (!in_array($thisValue, $thisArray)) { 
			return false;
		}
	}
	return true;
}

function check_value_is_number($thisValue) {
	if (check_value_is_set($thisValue)) {
		if (is_numeric($thisValue)) {
			return true;
		}
	}
	return false;
}

function check_value_is_set($thisValue) {
	if (!isset($thisValue)) {
		return false;
	} else {
		if ($thisValue !== '0') {
			if (empty($thisValue)) {
				return false;
			}
		}
		if (is_string($thisValue)) {
			if ((trim($thisValue) == '')) {
				return false;
			}
		}
	} 
	return true;
}

function check_value_is_string($thisValue) {
	if (check_value_is_set($thisValue)) {
		$temp = str_split($thisValue);
		foreach($temp as $char) {
			if (!ctype_alnum($char) and !ctype_space($char) and !strpos('`:!?%*&#+=@\\"^&-/_.,[]()\'', $char)) {
				return false;
			}
		}
	}
	return true;
}

function check_value_is_within_range($thisValue, $min, $max) {
	if (check_value_is_set($thisValue)) {
		return ((is_numeric($value) && $value >= $min && $value <= $max) ? true : false);
	}
	return true;
}

function check_value_max($thisValue) {
	if (check_value_is_set($thisValue)) {
		if (!(count(explode(",", $thisValue)) <= 2)) {
			return false;
		}
	}
	return true;
}

function check_value_regex($thisValue, $thisRegex) {
	if (check_value_is_set($thisValue)) {
		// forces match of this format: (###)###-####
		if (!preg_match($thisRegex, $thisValue)) {
			return false;
		}
	}
	return true;	
}


function check_date_format($thisValue, $thisRegex) {
	// match the format of the yyyy-mm-dd date
  	if (preg_match ($thisRegex, $thisValue, $parts)) {
    	//check whether the date is valid of not
        if(checkdate($parts[2],$parts[3],$parts[1])) {
        	return true;
        } else {
        	return false;
        }
	} else {
    	return false;
    }
}

function check_date_time($thisValue) {
  	if(strtotime($thisValue)) {
    	return true;
    } else {
      	return false;
	}
}

function get_data_entered($_POST) {
	$enteredData = $_POST;
	return $enteredData;
}
?>