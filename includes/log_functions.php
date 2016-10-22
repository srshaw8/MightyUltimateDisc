<?php
/**
 * @author Steven Shaw
 * @copyright 2008
*/

class Logger {
	
	const DEBUG	= 1;	// Most Verbose
	const INFO	= 2;	// ...
	const WARN	= 3;	// ...
	const ERROR	= 4;	// ...
	const FATAL	= 5;	// Least Verbose
	const OFF	= 6;	// Nothing at all.
	
	const LOGIN = "LOG"; // login 
	const DB = "DB"; // database
	const REG = "REG"; // registration
	const WAIT = "WAIT"; // wait list
	const PAY = "PAY"; // payment
	const PLAYP = "PLAYP"; // player profile
	const EVENTP = "EVENTP"; // event profile
	const TEAM = "TEAM"; // team profile
	const EMAIL = "EMAIL"; // email
	const ACCT = "ACCT"; // account settings
	const REPORT = "REPORT"; // one of the rosters
	const IPN = "IPN"; //Instant Payment Notification
	const RSS = "RSS"; //Instant Payment Notification
	const GEN = "GEN"; // general

	const LOG_OPEN 		= 1;
	const OPEN_FAILED 	= 2;
	const LOG_CLOSED 	= 3;
	
	private $logStatus;
	private $dateFormatEntry;
	private $dateFormatFile;
	private $logFile;
	private $fileHandle;
	private $isLocal;
	/** minimum reporting level */
	private $priority;
	/** minimum email reporting level */
	private $priorityEmail;

	public function __construct() {
		$this->logStatus = Logger::LOG_CLOSED;
		$this->dateFormatEntry = "d-M-Y H:i:s";
		$this->dateFormatFile = LOG_FILE_NAME_DATE;
		$this->priority = Logger::INFO;
		$this->priorityEmail = Logger::ERROR;
	}
		
	public function processLogEntry($moduleType,$priority,$eventID,$playerID,$message) {
		/** check if this log entry is above minimum reporting level */
		if ($this->priority <= $priority)	{
			$line = $this->setLogLine($message,$priority);
			$this->setLogStatus();
			if ($this->logStatus == Logger::LOG_OPEN) {
				if (fwrite( $this->fileHandle , $line ) === false) {
	    			/** could not be write to the log file" */
		    	}
		    }
		}
		/** send email message for certain priority messages if not working locally */
		if (!IS_LOCAL) {
			if($priority >= $this->priorityEmail) {
				sendEmailError($moduleType,$priority,$eventID,$playerID,$message);
			}
		}
		/** insert error message into db */
		if(!($moduleType == Logger::DB and $priority == Logger::FATAL)) {
			insert_msg_log($moduleType,$this->getMessageType($priority),$eventID,$playerID,$message);
		}
		return;
	}

	private function setLogStatus() {
		/** check if log file exists and can be opened for writing. */
 		if ($this->fileHandle = fopen(LOG_FILE.date($this->dateFormatFile, time()).".log","at")) {
			/** The log file was opened successfully. */
			$this->logStatus = Logger::LOG_OPEN;
		} else {
			/** The log file could not be opened. Check permissions. */
			$this->logStatus = Logger::OPEN_FAILED;
		}
		return;
	}
	
	private function setLogLine($message,$priority) {
		$status = $this->getTimeLine($priority);
		return "$status $message \n";
	}
	
	private function getMessageType($priority) {
		switch($priority) {
			case Logger::INFO:
				return "INFO";
			case Logger::WARN:
				return "WARN";				
			case Logger::DEBUG:
				return "DEBUG";				
			case Logger::ERROR:
				return "ERROR";
			case Logger::FATAL:
				return "FATAL";
			default:
				return "LOG";
		}
	}
	
	private function getTimeLine($priority) {
  		$temp = "[".date($this->dateFormatEntry, time())."]";
		switch($priority) {
 			case Logger::INFO:
				return "$temp INFO -->";
			case Logger::WARN:
				return "$temp WARN -->";				
			case Logger::DEBUG:
				return "$temp DEBUG -->";				
			case Logger::ERROR:
				return "$temp ERROR -->";
			case Logger::FATAL:
				return "$temp FATAL -->";
			default:
				return "$temp LOG -->";
		}
	}	
	
	public function __destruct() {
		if ( $this->fileHandle )
			fclose( $this->fileHandle );
	}
}

function log_entry($moduleType,$priority,$eventID,$playerID,$message) {
	$log = new Logger();
	$log->processLogEntry($moduleType,$priority,$eventID,$playerID,$message);
}
?>