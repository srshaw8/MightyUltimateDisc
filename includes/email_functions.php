<?php
/**
 * @author Steven Shaw
 * @copyright 2009
 */

require_once('htmlMimeMail5.php');

class EmailWrapper {
	
	const NEW_ACCT = "NEW_ACCT"; // new account 
	const FORGOT_PW = "FORGET_PW"; // forgot password
	const FORGOT_ID = "FORGET_ID"; // forgot account ID'
	const REGISTER = "REGISTER"; // successful registration
	const WAIT_LIST_ON = "WAIT_LIST_ON"; // added to wait list
	const WAIT_LIST_OFF = "WAIT_LIST_OFF"; // assigned from wait list to roster

	/** general email vars */
	private $emailAddr; //array $address = 'fruity@licious.com';
	private $from;
	private $bcc;
	private $cc;
	private $subject;
	private $message;
	private $returnPath;
	private $statusOk = true;
	/** specific email message vars */
	private $shortName;
	private $newPW;
	private $eventName;
	private $eventPayDeadline;
	private $eventOrgSponsor;
	private $eventContactName;
	private $eventContactEmail;
	private $eventEventFee = 0;
	private $eventTShirtFee = 0;
	private $eventDiscFee = 0;
	private $eventUPAEventFee = 0;
	private $eventFeeTotal = 0;
	private $eventDiscCost;
	private $eventDiscCount;
	private $gender;
	private $waitListNbr;
	private $waitListTtl;
	private $teamName;
		
	public function __construct() {;}

	/** $keyIDs is flexible - basically used to hold main object that is referenced by the email */
	function processEmail($emailType,$eventID,$teamID,$keyIDs) {
		switch($emailType) {
			case EmailWrapper::NEW_ACCT:
				$this->processNewAcct($keyIDs); /** new player's ID */
				break;
			case EmailWrapper::FORGOT_PW:
				$this->processForgotPW($keyIDs); /** player's email address */
				break;
			case EmailWrapper::FORGOT_ID:
				$this->processForgotID($keyIDs); /** player's email address */
				break;
			case EmailWrapper::REGISTER:
				$this->processRegister($eventID,$teamID,$keyIDs); /** registering player's ID */
				break;
			case EmailWrapper::WAIT_LIST_ON:
				$this->processWaitListOn($eventID,$keyIDs); /** waitlisted player's ID */
				break;
			case EmailWrapper::WAIT_LIST_OFF:
				$this->processWaitListOff($eventID,$teamID,$keyIDs); /** waitlisted player's ID */
				break;
		}
		$this->sendEmailMessage();
		return $this->statusOk;
	}

	function processEmailGeneral($from,$sendCopy,$subject,$message) {
		$this->emailAddr = array(EMAIL_SUPPORT_ADDRESS);
		$this->from = $from;
		$this->cc = ($sendCopy == 'Y') ? $from : ""; 
		$this->bcc = EMAIL_WORK_ADDRESS;
		$this->returnPath = $from;
		$this->subject = $subject;
		$this->message = $message;
		$this->sendEmailMessage();
		return $this->statusOk;
	}

	function processEmailTeam($eventID,$teamID,$playerID,$subject,$message) {
 		if ($teamID == 0) {
 			$rsEmailAddr = get_email_roster($eventID);
 		} else {
 			$rsEmailAddr = get_email_team($eventID,$teamID);	
 		}
 		$emailAddresses = array();
		$senderEmail = "";
		if (!empty($rsEmailAddr)) {
			$rsNumEmails = mysql_num_rows($rsEmailAddr);
			if ($rsNumEmails >  0) {
				/** get sender email from player ID **/
				$playerInfo = get_player_account($playerID); /** this should be capt or organizer **/
				$senderEmail = $playerInfo['Email'];
				if ($senderEmail != "") {
					/** roster email with one email per roster member */
					if ($teamID == 0) {
						$rosterMailStatus = true;
						$emailAddresses = array();
						while ($row=mysql_fetch_array($rsEmailAddr)) {
							$emailAddresses[] = $row['Email'];
							$this->emailAddr = $emailAddresses;
							$this->from = $senderEmail;  /** organizer only **/
							$this->returnPath = $senderEmail;
							$this->subject = $subject;
							$this->message = $message;
						 	$this->sendEmailMessage();
						 	unset($emailAddresses);
						 	if (!$this->statusOk) {
								$rosterMailStatus = false;
								log_entry(Logger::EMAIL,Logger::ERROR,$eventID,$playerID,	
								"Error encountered while sending email to ".$row['Email'].".");
							}
						}
						return $rosterMailStatus;
					} else {
						/** else this is one email to a team */
						while ($row=mysql_fetch_array($rsEmailAddr)) {
							$emailAddresses[] = $row['Email'];
						}
						$this->emailAddr = $emailAddresses;
						$this->from = $senderEmail;  /** capt or organizer **/
						$this->returnPath = $senderEmail;
						$this->subject = $subject;
						$this->message = $message;
					 	$this->sendEmailMessage();
						return $this->statusOk;
					}	
				} else {
					log_entry(Logger::EMAIL,Logger::ERROR,$eventID,$playerID,"No recipient address was found.");
					return false;
				}
			} else {
				log_entry(Logger::EMAIL,Logger::WARN,$eventID,$playerID,"No player email addresses were found.");
				return false;
			}
		} else {
			log_entry(Logger::EMAIL,Logger::ERROR,$eventID,$playerID,"No player email address record set found.");
			return false;			
		}
	}

	function processEmailError($moduleType,$priority,$eventID,$playerID,$message){
		$thisEnv = (IS_PROD) ? "PROD" : "TEST";
		$this->emailAddr = array(EMAIL_SUPPORT_ADDRESS,EMAIL_WORK_ADDRESS);
		$this->from = EMAIL_SUPPORT_ADDRESS;
		$this->returnPath = EMAIL_SUPPORT_ADDRESS;
		$this->subject = "Error condition encountered - ENV: ".$thisEnv;
		$this->message = 
"An error was encountered:  

Module: ".$moduleType."
Priority: ".$priority."
Event ID: ".$eventID."
Player ID: ".$playerID." 
Message: ".$message."
";
		$this->sendEmailMessage();
		return;
	}

	function processForgotID($keyIDs) {
		$this->emailAddr = array($keyIDs[0]); /** need to pass player's email address in array */
		$this->from = EMAIL_SUPPORT_ADDRESS;
		$this->returnPath = EMAIL_SUPPORT_ADDRESS;
		$this->subject = ORG_NAME." login information";
		$this->setShortName($keyIDs[0]);
		$this->buildForgotIDMessage();
	}
	
	function processForgotPW($keyIDs) {
		$this->emailAddr = array($keyIDs[0]); /** need to pass player's email address in array */
		$this->from = EMAIL_SUPPORT_ADDRESS;
		$this->returnPath = EMAIL_SUPPORT_ADDRESS;
		$this->subject = ORG_NAME." login information";
		$this->resetPW($keyIDs[0]);
		$this->buildForgotPWMessage();
	}

	function processNewAcct($keyIDs) {
		$this->setAccountInfo($keyIDs[0]); /** need to pass player's email address in array */
		$this->from = EMAIL_SUPPORT_ADDRESS;
		$this->returnPath = EMAIL_SUPPORT_ADDRESS;
		$this->subject = "Welcome to ".ORG_NAME;
		$this->buildNewAcctMessage();
		return;
	}

	function processRegister($eventID,$teamID,$keyIDs) {
		$this->setRosterInfo($eventID,$teamID,$keyIDs[0]);
		$this->from = EMAIL_DIRECTOR_ADDRESS;
		$this->returnPath = EMAIL_DIRECTOR_ADDRESS;
		$this->subject = "Successfully registered for ".$this->eventName." @ ".ORG_NAME;
		$this->buildRegisterMessage();
		return;
	}

	function processWaitListOff($eventID,$teamID,$keyIDs) {
		$this->setRosterInfo($eventID,$teamID,$keyIDs[0]);
		$this->from = EMAIL_DIRECTOR_ADDRESS;
		$this->returnPath = EMAIL_DIRECTOR_ADDRESS;
		$this->subject = "Added to roster for ".$this->eventName." @ ".ORG_NAME;
		$this->buildWaitListOffMessage();
		return;
	}

	function processWaitListOn($eventID,$keyIDs) {
		$this->setWaitListInfo($eventID,$keyIDs[0]);
		$this->from = EMAIL_DIRECTOR_ADDRESS;
		$this->returnPath = EMAIL_DIRECTOR_ADDRESS;
		$this->subject = "Added to wait list for ".$this->eventName." @ ".ORG_NAME;
		$this->buildWaitListOnMessage();
		return;
	}

	function buildForgotIDMessage() {
		$this->message = 
"Your ".ORG_NAME." website user name is ".$this->shortName.".

Cheers,
Mighty Ultimate Disc";
		return;
	}

	function buildForgotPWMessage() {
		$this->message = 
"Your ".ORG_NAME." website password has been changed to ".$this->newPW.".  Please update your password the next time you log into the site.

Cheers,
Mighty Ultimate Disc";
		return;
	}

	function buildNewAcctMessage() {
		$this->message = 
"Thanks for signing up with ".ORG_NAME." !!  

Now that you're a member, please take a moment to fill out your player profile.  Once you create your profile, you will be able to:
 - sign up for any ultimate frisbee league or hat tournament in your neighborhood or wherever you happen to be
 - find or create postings for local pickup play
 - create and manage your own league or hat tournament online
						
Your player ID: ".$this->shortName."

Please be sure to save this email and store your player ID in a safe place in case you need it in the future. 

Play Ultimate!

Mighty Ultimate Disc";
		return;
	}

	function buildRegisterMessage() {
		/** initialize fee message text */
		$feeText = "";
		if ($this->eventEventFee > 0) {
			$feeText = "   $".$this->eventEventFee." - Event Fee ";			
		}
		if ($this->eventTShirtFee > 0) {
			$feeText = $feeText."\n   $".$this->eventTShirtFee." - TShirt Fee ";
		}
		if ($this->eventDiscFee > 0) {
			$feeText = $feeText."\n   $".$this->eventDiscFee." - Disc Fee (".$this->eventDiscCount." @ $".$this->eventDiscCost."/disc) ";
		}
		if ($this->eventUPAEventFee > 0) {
			$feeText = $feeText."\n	$".$this->eventUPAEventFee." - UPA Event Fee ";
		}
		if ($this->eventFeeTotal > 0) {
			$feeText = $feeText."\n   $".number_format($this->eventFeeTotal, 2, '.', '')." - Total Fee(s) ";
		}
		
		$this->message = 
"Congratulations, you are now registered to play in the ".$this->eventName." !!  

Please be sure to save this email as it is a record of what you have paid ".$this->eventOrgSponsor.". 

Note that you can not play until you pay your fees to ".$this->eventOrgSponsor.":
".$feeText."

All fees are due to ".$this->eventOrgSponsor." by ".strftime("%B %d, %Y", strtotime($this->eventPayDeadline)).".

Be sure to check the registration status page for payment details and your future team assignment.

If you have any issues or questions regarding this frisbee event, please contact the event organizer, ".$this->eventContactName.", at ".$this->eventContactEmail.".

Play Ultimate!

Mighty Ultimate Disc";
	}

	function buildWaitListOnMessage() {
		$waitNbrText = "";
		$genderTxt = ($this->gender == "M") ? "men" : "women";
		if ($this->waitListNbr > 0 and $this->waitListTtl >0) {
			$waitNbrText = "  Currently, your wait list number is ".$this->waitListNbr." out of a total ".$this->waitListTtl." wait listed ".$genderTxt." players.";
		}
		
		$this->message =
"Thanks for your interest in wanting to play in the ".$this->eventName.", an event sponsored by the ".$this->eventOrgSponsor.".

Your name has been successfully added to the wait list.".$waitNbrText."  If an opening comes up, the event organizer will call or email you to see if you're still interested in participating.  Be sure to check the Registration Status page to see your current position on the wait list. 

Thanks again and we hope to see you on the field!

Mighty Ultimate Disc";
	}

	function buildWaitListOffMessage() {
		/** initialize fee message text */
		$feeText = "";
		if ($this->eventEventFee > 0) {
			$feeText = "   $".$this->eventEventFee." - Event Fee ";			
		}
		if ($this->eventTShirtFee > 0) {
			$feeText = $feeText."\n   $".$this->eventTShirtFee." - TShirt Fee ";
		}
		if ($this->eventDiscFee > 0) {
			$feeText = $feeText."\n   $".$this->eventDiscFee." - Disc Fee (".$this->eventDiscCount." @ $".$this->eventDiscCost."/disc) ";
		}
		if ($this->eventUPAEventFee > 0) {
			$feeText = $feeText."\n	$".$this->eventUPAEventFee." - UPA Event Fee ";
		}
		if ($this->eventFeeTotal > 0) {
			$feeText = $feeText."\n   $".number_format($this->eventFeeTotal, 2, '.', '')." - Total Fee(s) ";
		}

		$teamText = "";
		if ($this->teamName <> "") {
			$teamText = "  The team that you have been assigned to is ".$this->teamName.".";
		} else {
			$teamText = "  Currently, you are not assigned to a team.  The event organizer or team captain will contact you with your team assigment.  Or, check the registration status page for your team assignment.";
		}
		
		$this->message = 
"Congratulations, you have been drafted off the wait list and are now registered to play in the ".$this->eventName." !!".$teamText."

Please note that you can not play until you pay your fees to ".$this->eventOrgSponsor.":
".$feeText."

All fees are due to ".$this->eventOrgSponsor." by ".strftime("%B %d, %Y", strtotime($this->eventPayDeadline)).".

Be sure to check the registration status page for payment details.

Play Ultimate!

Mighty Ultimate Disc";
	}

	function resetPW($email) {
		if ($password=update_password($email)) { 
			$this->newPW = $password;
		} else {
			$this->statusOk = false;
			log_entry(Logger::EMAIL,Logger::ERROR,0,0,"Password reset failed with email ".$email.".");
		}
		return;
	}

	function sendEmailMessage() {
		//if ($this->statusOk and !IS_LOCAL) {
		if ($this->statusOk) {
			$mail = new htmlMimeMail5();
			$mail->setFrom($this->from);
			$mail->setCc($this->cc);
			$mail->setBcc($this->bcc);
		    $mail->setSubject($this->subject);
		    $mail->setText($this->message);
		    $mail->setReturnPath($this->returnPath);
			$result = $mail->send($this->emailAddr);
			if ($result <> 1) {
				log_entry(Logger::EMAIL,Logger::ERROR,0,0,"Mail from ".$this->from." with result: ".strval($result));
				$this->statusOk = false;
			}
		}
		return;
	}

	function setAccountInfo($playerID) {
		$playerAccount = get_player_account($playerID);
		$this->shortName = $playerAccount['Short_Name'];
		$this->emailAddr = array($playerAccount['Email']);
		return;
	}
	
	function setRosterInfo($eventID,$teamID,$playerID)  {
		$rosterInfo = get_roster_player_info($eventID, $playerID);
		if ($teamID <> 0) {
			 $teamInfo = get_team_profile($eventID,$teamID);
			 $this->teamName = $teamInfo['Team_Name'];
		}
		$this->emailAddr = array($rosterInfo['Email']); /** need to pass player's email address in array */
		$this->eventName = $rosterInfo['Event_Name'];
		$this->eventPayDeadline = $rosterInfo['Payment_Deadline'];
		$this->eventOrgSponsor = $rosterInfo['Org_Sponsor'];
		$this->eventContactName = $rosterInfo['Contact_Name'];
		$this->eventContactEmail = $rosterInfo['Contact_Email'];
		$this->eventEventFee = $rosterInfo['Event_Fee'];
		$this->eventTShirtFee = $rosterInfo['TShirt_Fee'];
		$this->eventDiscFee = $rosterInfo['Disc_Fee'];
		$this->eventUPAEventFee = $rosterInfo['UPA_Event_Fee'];
		$this->eventDiscCost = $rosterInfo['Event_Disc_Fee'];
		$this->eventDiscCount = $rosterInfo['Disc_Count'];
		$this->eventFeeTotal = 
			$this->eventEventFee + $this->eventTShirtFee + $this->eventDiscFee + $this->eventUPAEventFee;
		return;
	}
	
	function setShortName($email) {
		$shortName = get_short_name($email);
		if (check_value_is_set($shortName)) {	
			$this->shortName = $shortName;
		} else {
			$this->statusOk = false;
			log_entry(Logger::EMAIL,Logger::ERROR,0,0,"Short name retrieval failed with email ".$email.".");
		}
		return;
	}
	
	function setWaitListInfo($eventID, $playerID) {
		$waitInfo = get_wait_list_player($eventID, $playerID);
		$this->emailAddr = array($waitInfo['Email']); /** need to pass player's email address in array */
		$this->eventName = $waitInfo['Event_Name'];
		$this->eventOrgSponsor = $waitInfo['Org_Sponsor'];
		$this->gender = $waitInfo['Gender'];

		$waitListPosition = new WaitListPosition($eventID,$playerID,$this->gender);
		$this->waitListNbr = $waitListPosition->get_position();
		$this->waitListTtl = $waitListPosition->get_total();
		return;
	}
}

function sendEmail($emailType,$eventID,$teamID,$keyIDs) {
	$emailWrapper = new EmailWrapper();
	return $emailWrapper->processEmail($emailType,$eventID,$teamID,$keyIDs);
}

function sendEmailError($moduleType,$priority,$eventID,$playerID,$message) {
	$emailWrapper = new EmailWrapper();
	return $emailWrapper->processEmailError($moduleType,$priority,$eventID,$playerID,$message);
}

function sendEmailGeneral($emailFromClean,$sendCopy,$subjectClean,$messageClean) {
	$emailWrapper = new EmailWrapper();
	return $emailWrapper->processEmailGeneral($emailFromClean,$sendCopy,$subjectClean,$messageClean);
}

function sendEmailTeam($eventID,$teamID,$playerID,$subject,$message) {
	$emailWrapper = new EmailWrapper();
	return $emailWrapper->processEmailTeam($eventID,$teamID,$playerID,$subject,$message);
}
?>