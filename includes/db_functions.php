<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
			
final class Database {

    private static $instance;
    protected $connection;
    protected $hostname;
    protected $database;
    protected $username;
    protected $password; 

    private function __construct($dbHost=null, $dbName=null, $dbUser=null, $dbPass=null) {
		$this->database = $dbName;
        $this->hostname = $dbHost;
        $this->username = $dbUser;
        $this->password = $dbPass;
		/** create a connection during instantiation of the object */
		$this->verifyConnection();
	}

    public static function getInstance() {
        if(!self::$instance instanceof self) {
			$dbHost = DB_HOST;
			$dbName = DB_NAME;
			$dbUser = DB_USER;
			$dbPass = DB_PASS;
            self::$instance = new Database($dbHost, $dbName, $dbUser, $dbPass);
        }
        return self::$instance;
    }

    private function isConnected() {
        if (is_resource($this->connection)) {
            return true;
        } else {
            return false;
        }
    }

    private function getConnection() {
        if (is_null($this->database)) {
			log_entry(Logger::DB,Logger::FATAL,0,0,"A database was not selected while trying to get a connection.");
            redirect_page("hand_block.php");
        }
        if (is_null($this->hostname)) {
			log_entry(Logger::DB,Logger::FATAL,0,0,"A host name was not set while trying to get a connection.");
			redirect_page("hand_block.php");
		}
		
        $this->connection = @mysql_connect($this->hostname, $this->username, $this->password);

        if ($this->connection === false) {
        	log_entry(Logger::DB,Logger::FATAL,0,0,
				"A connection to the database does not exist. Check your username and password then try again.");
			redirect_page("hand_block.php");
		}

        if (!mysql_select_db($this->database, $this->connection)) {
            log_entry(Logger::DB,Logger::FATAL,0,0,"The database could not be selected.");
            redirect_page("hand_block.php");
        }
    }

	public function verifyConnection() {
		if (!self::isConnected()){
			self::getConnection();
		}
	}

    public function query($sql,$returnType) {
        $this->verifyConnection();		
       	$result = mysql_query($sql,$this->connection);	
 		if (!$result) {
			log_entry(Logger::DB,Logger::ERROR,0,0,"MYSQL ERROR #: ".mysql_errno()." MESSAGE: " . mysql_error()."  SQL STMT: ".$sql);
			return false;
		} else if ($returnType == "insert" or $returnType == "update" or $returnType == "delete") {
			return true;
		}
		if (mysql_num_rows($result)>0) {
			if ($returnType == "row") {
				$resultArray = mysql_fetch_array($result);
				return $resultArray;
			} else if ($returnType == "resultset") {
				return $result;
			} else if ($returnType == "boolean") {
				return true;
			} else {
				$row = mysql_fetch_array($result);
				$fieldVal = $row[$returnType];
				return $fieldVal;
			}
		} else {
			return false;
		}
    }
    
    public function close() {
    	if (self::isConnected()) {
        	mysql_close($this->connection);
        	$this->connection = null;
        }
    }

    public function __clone() { 
    	die("Clone is not allowed for the database."); 
	} 
  
  	public function __wakeup() { 
    	die("Deserializing is not allowed for the database."); 
  	}
}

function check_admin_role($playerID) {
	$db = Database::getInstance();
	$sql = "select * 
			from admin 
			where 
			Player_ID = $playerID";
	return $db->query($sql,"boolean");
}

function check_dupe_event($eventName) {
	$db = Database::getInstance();
	$eventName = trim(mysql_real_escape_string($eventName));
	$sql = "select Event_ID 
			from event_profile 
			where 
			Event_Name = '$eventName' and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"boolean");
}

function check_dupe_short_name($shortName) {
	$db = Database::getInstance();
	$shortName = trim(mysql_real_escape_string($shortName));
	$sql = "select * 
			from player_account 
			where 
			Short_Name = '$shortName'";
	return $db->query($sql, "boolean");
}

function check_dupe_team($eventID, $teamName) {
	$db = Database::getInstance();
	$teamName = trim(mysql_real_escape_string($teamName));
	$sql = "select Event_ID 
			from team_profile 
			where  
			Event_ID = $eventID and 
			Team_Name = '$teamName' and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"boolean");
}

function check_email_exists($email) {
	$db = Database::getInstance();
	$sql = "select * 
			from player_account 
			where 
			Email = '$email'";
	return $db->query($sql, "boolean");
}

function check_email_exists_others($playerID, $email) {
	$db = Database::getInstance();
	$sql = "select * 
			from player_account 
			where 
			Player_ID <> $playerID and 
			Email = '$email'";
	return $db->query($sql, "boolean");
}

function check_password_old($playerID, $passwordOld) {
	$db = Database::getInstance();
	$hashPassword = sha1(sha1($passwordOld).”flash”);
	$sql = "select Password 
			from player_account 
			where 
			Player_ID = $playerID and 
			Password = '$hashPassword'";
	return $db->query($sql, "boolean");
}

function check_player_in_event($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select Player_ID 
			from roster 
			where 
			Event_ID = $eventID and 
			Player_ID = $playerID";
	return $db->query($sql, "boolean");
}

function check_player_on_wait_list($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select Player_ID 
			from wait_list 
			where 
			Event_ID = $eventID and 
			Player_ID = $playerID";
	return $db->query($sql, "boolean");
}

function check_player_terms($playerID) {
	$db = Database::getInstance();
	$sql = "select Player_ID 
			from player_account 
			where 
 			Player_ID = $playerID and 
			Terms = 'Y'";
	return $db->query($sql, "boolean");
}

function delete_event_pickup($deleteTime) {
	$db = Database::getInstance();
	$sql = "delete from event_profile
			where 
			Event_Type = '2' and 
			Created < '$deleteTime'";
	return $db->query($sql,"delete");
}

function delete_event_team_role ($eventID, $teamID, $role){
	$db = Database::getInstance();
	$sql = "delete from event_role
			where 
			Event_ID = $eventID and 
			Team_ID = $teamID and 
			Role = '$role'";
	return $db->query($sql,"delete");
}

function delete_event_team_player_role($eventID,$teamID,$playerID,$role) {
	$db = Database::getInstance();
	$sql = "delete from event_role
			where 
			Event_ID = $eventID and 
			Team_ID = $teamID and 
			Player_ID = $playerID and 
			Role = '$role'";
	return $db->query($sql,"delete");
}

function delete_session_data($session_id) {
	$db = Database::getInstance();
	$sql = "delete from session_data 
			where 
			Session_ID = '".mysql_real_escape_string($session_id)."'";
	return $db->query($sql,"delete");
}

function delete_session_expired($maxlifetime) {
	$db = Database::getInstance();
	$sql = "delete from session_data 
			where 
            Session_Expire < '".mysql_real_escape_string(time() - $maxlifetime)."'";
	return $db->query($sql,"delete");
}

function get_countries() {
	$db = Database::getInstance();
	$sql = "select Code, Name 
			from country_lookup";
	return $db->query($sql,"resultset");
}

function get_country_name($countryCode) {
	$db = Database::getInstance();
	$sql = "select Name 
			from country_lookup
			where 
			Code = '$countryCode'";
	return $db->query($sql,"Name");
}

function get_currency_code($countryCode) {
	$db = Database::getInstance();
	$sql = "select Currency_Code 
			from country_lookup
			where 
			Code = '$countryCode'";
	return $db->query($sql,"Currency_Code");
}

function get_email_all_list() {
	$db = Database::getInstance();
	$sql = "select player_profile.Last_Name, player_profile.First_Name, player_account.Email 
			from 
			player_profile, player_account
			where
			player_account.Player_ID = player_profile.Player_ID 
			order by player_profile.Last_Name";
	return $db->query($sql,"resultset");
}

function get_email_roster($eventID) {
	$db = Database::getInstance();
	$sql = "select player_account.Player_ID, player_account.Email 
			from player_account, roster 
			where 
			roster.Event_ID = $eventID and 
			roster.Registered = 'Y' and
			roster.Player_ID = player_account.Player_ID and 
			player_account.Email_Opt_Capt = 'Y'";
	return $db->query($sql,"resultset");
}

function get_email_team($eventID,$teamID) {
	$db = Database::getInstance();
	$sql = "select player_account.Player_ID, player_account.Email 
			from player_account, roster 
			where 
			roster.Event_ID = $eventID and 
			roster.Team_ID = $teamID and
			roster.Registered = 'Y' and
			roster.Team_ID > 0 and 
			roster.Player_ID = player_account.Player_ID and 
			player_account.Email_Opt_Capt = 'Y'";
	return $db->query($sql,"resultset");
}

function get_event_home_page($eventID) {
	$db = Database::getInstance();
	$sql = "select Publish_Home_Page, Home_Page_Text 
			from event_home_page 
			where 
			Event_ID = $eventID and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_event_home_page_published($eventID) {
	$db = Database::getInstance();
	$sql = "select Home_Page_Text 
			from event_home_page 
			where 
			Event_ID = $eventID and 
			Publish_Home_Page = 'Y' and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_event_limit_gender($eventID, $gender)  {
	$db = Database::getInstance();
	if ($gender == "M") {
		$sql = "select Limit_Men as Gender_Limit 
				from event_profile 
				where 
				Event_ID = $eventID";
	} else {
		$sql = "select Limit_Women as Gender_Limit 
				from event_profile 
				where 
				Event_ID = $eventID";
	}
	return $db->query($sql, "Gender_Limit");
}

function get_event_profile($eventID) {
	$db = Database::getInstance();
	$sql = "select * 
			from event_profile 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"row");
}

function get_event_profile_id($eventName) {
	$db = Database::getInstance();
	$eventName = trim(mysql_real_escape_string($eventName));
	$sql = "select Event_ID 
			from event_profile 
			where 
			Event_Name = '$eventName' and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"Event_ID");
}

function get_event_profile_mgmt($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select event_profile.Event_Name, event_profile.Event_Type, event_role.Role 
			from event_profile, event_role  
			where 
			event_profile.Event_ID = event_role.Event_ID and 
			event_profile.Event_ID = $eventID and 
			event_role.Player_ID = $playerID and 
			(event_profile.Archive is null or event_profile.Archive = '0000-00-00') and 
			(event_role.Archive is null or event_role.Archive = '0000-00-00')";
	return $db->query($sql,"resultset");
}

function get_event_profile_for_admin($eventID) {
	$db = Database::getInstance();
	$sql = "select Event_Name, Org_Sponsor  
			from event_profile 
			where 
			Event_ID = $eventID and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"resultset");
}

function get_event_profile_location($lat,$long) {
	$db = Database::getInstance();
	$sql = "select Latitude, Longitude  
			from event_profile 
			where 
			Latitude = $lat and 
			Longitude = $long";
	return $db->query($sql,"row");
}

function get_event_profile_short($eventID) {
	$db = Database::getInstance();
	$sql = "select Event_Name, Org_Sponsor, Event_Disc_Fee, Payment_Account, Currency_Code, UPA_Event, Payment_Status 
			from event_profile 
			where 
			Event_ID = $eventID and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_event_profiles_active($eventType,$countryCode,$stateProv) {
	$db = Database::getInstance();
	$thisEventType = mysql_real_escape_string($eventType);
	$thisCountry = trim(mysql_real_escape_string($countryCode));
	$thisStateProv = trim(mysql_real_escape_string($stateProv));
	$sql = "select event_profile.Event_ID, event_profile.Event_Name, event_type.Name, 
			event_profile.City, event_profile.Limit_Men, event_profile.Limit_Women, 
			event_profile.Reg_Begin, event_profile.Reg_End, event_profile.Event_End, 
			event_profile.Timezone_ID, event_profile.Location_Link  
			from event_profile, event_type 
			where 
			event_profile.Country = '$thisCountry' and 
			event_profile.State_Prov = '$thisStateProv' and 
			event_profile.Event_Type in ($thisEventType) and 
			event_profile.Event_Type = event_type.Code and 
			event_profile.Publish_Event = 'Y' and  
			(Archive is null or Archive = '0000-00-00') 
			order by event_type.Name, City, Event_Name asc";
	return $db->query($sql,"resultset");
}

function get_event_reg_gender($eventID, $gender) {
	$db = Database::getInstance();
	$sql = "select count(*) as Gender_Count 
			from roster 
			where 
			Event_ID= $eventID and 
			Gender = '$gender' and 
			Registered ='Y'";
	return $db->query($sql, "Gender_Count");
}

function get_event_roles($playerID) {
	$db = Database::getInstance();
	$sql = "select event_role.Event_ID, event_role.Team_ID, event_role.Role, event_profile.Event_Name 
			from event_role, event_profile 
			where 
			event_role.Player_ID = $playerID and 
			event_role.Event_ID = event_profile.Event_ID and 
			(event_role.Archive is null or event_role.Archive = '0000-00-00') 
			order by event_profile.Event_Name";
	return $db->query($sql,"resultset");
}

function get_events_for_admin() {
	$db = Database::getInstance();
	$sql = "select Event_ID, Event_Name 
			from event_profile 
			where 
			(Event_Type = 1 or Event_Type = 3) and 
			(Archive is null or Archive = '0000-00-00') 
			order by Event_Name asc";
	return $db->query($sql,"resultset");
}

function get_events_for_player_role($playerID,$role) {
	$db = Database::getInstance();
	$sql = "select 
			event_profile.Event_ID, event_profile.Event_Name 
			from event_profile, event_role  
			where 
			event_role.Event_ID = event_profile.Event_ID and 
			event_role.Player_Id = $playerID and 
			event_role.Role = '$role' and 
			(event_role.Archive is null or event_role.Archive = '0000-00-00')";
	return $db->query($sql,"resultset");
}

function get_events_for_player_roster($playerID) {
	$db = Database::getInstance();
	$sql = "select  
			event_profile.Event_ID, event_profile.Event_Name 
			from event_profile, roster 
			where 
			roster.Event_ID = event_profile.Event_ID and 
			roster.Player_Id = $playerID and 
			roster.Registered='Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"resultset");
}

function get_games($eventID,$gameID) {
    
    
}

function get_ipn_txn($txnID) {
	$db = Database::getInstance();
	$sql = "select Txn_ID  
			from ipn_tracker 
			where 
			Txn_ID = '$txnID'";
	return $db->query($sql,"Txn_ID");
}

function get_player_account($playerID) {
	$db = Database::getInstance();
	$sql = "select Short_Name, Email, Email_Opt_Capt, Email_Opt_MU  
			from player_account 
			where 
			Player_ID = '$playerID'";
	return $db->query($sql,"row");
}

function get_player_id($shortName,$thisValue) {
	$db = Database::getInstance();
	$shortName = trim(mysql_real_escape_string($shortName));
	$password = mysql_real_escape_string($thisValue);
	$hashPassword = sha1(sha1($password).”flash”);
	$sql = "select Player_ID 
			from player_account 
			where 
			Short_Name = '$shortName' and 
			Password = '$hashPassword'";
	return $db->query($sql,"Player_ID");
}

function get_player_profile_short($playerID) {
	$db = Database::getInstance();
	$sql = "select Player_ID, First_Name, Last_Name, UPA_Cur_Member 
			from player_profile 
			where 
			Player_ID = $playerID";
	return $db->query($sql,"row");
}

function get_player_profile($playerID) {
	$db = Database::getInstance();
	$sql = "select * 
			from player_profile 
			where 
			Player_ID= $playerID";
	return $db->query($sql,"row");
}

function get_player_role_assigned($eventID, $teamID, $role) {
	$db = Database::getInstance();
	$sql = "select player_profile.Player_ID, player_profile.First_Name, player_profile.Last_Name, 
			player_profile.H_Phone 
			from player_profile, event_role  
			where 
			event_role.Event_ID = $eventID and 
			event_role.Team_ID = $teamID and
			event_role.Role = '$role' and 
			event_role.Player_ID = player_profile.Player_ID and 
			(event_role.Archive is null or event_role.Archive = '0000-00-00') 
			order by player_profile.Last_Name asc";
	return $db->query($sql,"resultset");
}

function get_player_role_unassigned($eventID, $teamID, $role) {
	$db = Database::getInstance();
	if ($role == "Owner") {
	$sql = "select player_profile.Player_ID, player_profile.First_Name, player_profile.Last_Name 
			from player_profile, roster
			where 
			roster.Event_ID = $eventID and 
			roster.Registered = 'Y' and
			roster.Player_ID = player_profile.Player_ID and
			roster.Player_ID not in (select event_role.Player_ID 
									from event_role 
									where 
									event_role.Event_ID = $eventID and
									event_role.Role = '$role' and 
									(event_role.Archive is null or event_role.Archive = '0000-00-00')) 
									order by player_profile.Last_Name asc";
	} else {
	$sql = "select player_profile.Player_ID, player_profile.First_Name, player_profile.Last_Name 
			from player_profile, roster
			where 
			roster.Event_ID = $eventID and 
			(roster.Team_ID = 0 or
			roster.Team_ID = $teamID) and  
			roster.Registered = 'Y' and
			roster.Player_ID = player_profile.Player_ID and
			roster.Player_ID not in (select event_role.Player_ID 
									from event_role 
									where 
									event_role.Event_ID = $eventID and
									event_role.Role = '$role' and 
									(event_role.Archive is null or event_role.Archive = '0000-00-00')) 
									order by player_profile.Last_Name asc";
	}
	return $db->query($sql,"resultset");
}

function get_report_draft($eventID) {
	$db = Database::getInstance();
	$sql = "select player_profile.Player_ID, player_profile.Last_Name, player_profile.First_Name, 
			player_profile.Gender, player_profile.Height, player_profile.Conditionx, player_profile.Skill_Lvl, 
			player_profile.Skill_Lvl_Def, player_profile.Yr_Exp, player_profile.Play_Lvl, 
			player_profile.Buddy_Name, roster.Pct_Of_Games 
			from player_profile inner join roster on player_profile.Player_ID = roster.Player_ID 
			where 
			roster.Event_ID = $eventID and 
			roster.Registered = 'Y' 
			order by player_profile.Gender asc, player_profile.Skill_Lvl desc, 
			player_profile.Skill_Lvl_Def desc, player_profile.Last_Name asc";
	return $db->query($sql,"resultset");
}

function get_report_roster($eventID) {
	$db = Database::getInstance();
	$sql = "select roster.Player_ID, roster.Event_Fee, roster.TShirt_Fee, roster.Disc_Fee, 
			roster.UPA_Event_Fee, roster.Disc_Count, roster.Payment_Status, roster.Registered, 
			roster.Payment_Type, player_profile.First_Name, player_profile.Last_Name, 
			player_profile.UPA_Cur_Member, player_profile.T_Shirt_Size	
			from roster, player_profile 
			where 
			roster.Event_ID = $eventID and  
			roster.Player_ID = player_profile.Player_ID and 
			(roster.Archive is null or roster.Archive = '0000-00-00') 
			order by player_profile.Last_Name";
	return $db->query($sql,"resultset");
}

function get_report_roster_player_info($eventID, $thisPlayerID) {
	$db = Database::getInstance();
	$sql = "select roster.Player_ID, roster.Event_Fee, roster.TShirt_Fee, roster.Disc_Fee, 
			roster.UPA_Event_Fee, roster.Disc_Count, roster.Payment_Status, roster.Payment_Type, 
			roster.Registered, player_profile.First_Name, player_profile.Last_Name, 
			player_profile.UPA_Cur_Member, player_profile.H_Phone, player_profile.C_Phone, 
			player_profile.W_Phone,	player_profile.E_Contact_Name, player_profile.E_Contact_Phone, 
			player_profile.T_Shirt_Size, player_account.Email, team_profile.Team_Name 
			from
			(roster left join team_profile on 
			roster.Event_ID = team_profile.Event_ID and 
			roster.Team_ID = team_profile.Team_ID), player_profile, player_account 
			where 
			roster.Event_ID = $eventID and 
			roster.Player_ID = $thisPlayerID and
			roster.Player_ID = player_account.Player_ID and 
			roster.Player_ID = player_profile.Player_ID and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_roster_player_fees($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select roster.Player_ID, roster.Event_Fee, roster.TShirt_Fee, roster.Disc_Fee, 
			roster.UPA_Event_Fee 
			from
			roster 
			where 
			roster.Event_ID = $eventID and 
			roster.Player_ID = $playerID and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_summary_fees($eventID) {
	$db = Database::getInstance();
	$sql = "select sum(Event_Fee) as Event_Fee, sum(TShirt_Fee) as TShirt_Fee, sum(Disc_Fee) as Disc_Fee, 
			sum(UPA_Event_Fee) as UPA_Event_Fee, sum(Disc_Count) as Disc_Count 
			from roster 
			where
			Event_ID = $eventID and 
			Registered = 'Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_summary_fees_paid($eventID) {
	$db = Database::getInstance();
	$sql = "select sum(Event_Fee) as Event_Fee, sum(TShirt_Fee) as TShirt_Fee, sum(Disc_Fee) as Disc_Fee, 
			sum(UPA_Event_Fee) as UPA_Event_Fee  
			from roster 
			where
			Event_ID = $eventID and 
			Payment_Status = 'Y' and
			Registered = 'Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_summary_gender_reg($eventID,$gender) {
	$db = Database::getInstance();
	$sql = "select count(Gender) as Gender_Total 
			from roster 
			where
			Event_ID = $eventID and 
			Gender = '$gender' and 
			Registered = 'Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_summary_gender_wait($eventID,$gender) {
	$db = Database::getInstance();
	$sql = "select count(Gender) as Gender_Total 
			from wait_list 
			where
			Event_ID = $eventID and 
			Gender = '$gender' and 
			Assigned <> 'Y' and 
			(wait_list.Archive is null or wait_list.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_summary_tshirts($eventID,$size) {
	$db = Database::getInstance();
	$sql = "select count(player_profile.T_Shirt_Size) as TShirt_Total 
			from roster, player_profile 
			where
			roster.Event_ID = $eventID and 
			roster.Player_ID = player_profile.Player_ID and 
			roster.TShirt_Fee > 0 and 
			player_profile.T_Shirt_Size = '$size' and 
			roster.Registered = 'Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_report_upa_forms($eventID) {
	$db = Database::getInstance();
	$sql = "select player_profile.Player_ID, player_profile.Last_Name, player_profile.First_Name, 
			player_profile.UPA_Cur_Member, player_profile.UPA_Number, player_profile.Student, 
			player_profile.Over18, roster.UPA_Event_Fee, roster.Payment_Status, team_profile.Team_Name, 
			event_role.Role   
			from
			player_profile, team_profile, roster left join event_role on 
			(roster.Player_ID = event_role.Player_ID and 
			event_role.Event_ID = $eventID and 
			event_role.Role = 'Captain' and 
			(event_role.Archive is null or event_role.Archive = '0000-00-00')) 
			where 
			roster.Event_ID = $eventID and 
			roster.Registered = 'Y' and
			player_profile.Player_ID = roster.Player_ID and 
			team_profile.Team_ID = roster.Team_ID 
			order by team_profile.team_name, player_profile.Last_Name asc";
	return $db->query($sql,"resultset");
}

function get_report_upa_subm($eventID) {
	$db = Database::getInstance();
	$sql = "select player_profile.First_Name, player_profile.Last_Name, 
			player_profile.Address, player_profile.City, player_profile.State_Prov, 
			player_profile.Post_Code, player_profile.UPA_Number, player_profile.H_Phone, 
			player_account.Email 
			from player_profile, player_account, roster 
			where 
			player_profile.Player_ID = player_account.Player_ID and 
			player_profile.Player_ID = roster.Player_ID and
			roster.Event_ID = $eventID and 
			roster.Registered = 'Y' 
			order by player_profile.Last_Name";
	return $db->query($sql,"resultset");
}

function get_roster_player_info($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select event_profile.Event_Name, event_profile.Org_Sponsor, event_profile.Payment_Deadline,
			event_profile.Event_Disc_Fee, event_profile.Contact_Name , event_profile.Contact_Email, 
			roster.Event_Fee, roster.TShirt_Fee, roster.Disc_Fee, 
			roster.UPA_Event_Fee, roster.Disc_Count, player_account.Email 
			from roster, event_profile, player_account 
			where 
			roster.Event_ID = $eventID and  
			roster.Player_ID = $playerID and 
			roster.Event_ID = event_profile.Event_ID and 
			roster.Player_ID = player_account.Player_ID and 
			roster.Registered ='Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00')";
	return $db->query($sql,"row");
}

function get_roster_status($playerID) {
	$db = Database::getInstance();
	$sql = "select event_profile.Event_Name, event_profile.Event_Type, event_profile.Payment_Type, 
			event_profile.Payment_Chk_Payee, event_profile.Payment_Chk_Address, event_profile.Payment_Chk_City, 
			event_profile.Payment_Chk_State, event_profile.Payment_Chk_Zip,	event_profile.Payment_Account,  
			event_profile.Payment_Item_Name, event_profile.Payment_Return_URL, event_profile.Timezone_ID, 
			roster.Event_ID, roster.Event_Fee, roster.TShirt_Fee, roster.Disc_Fee, roster.UPA_Event_Fee, 
			roster.Payment_Status, roster.Payment_Type as Payment_Type_Roster, roster.Created, 
			team_profile.Team_Name 
			from
			(roster left join team_profile on 
			roster.Event_ID = team_profile.Event_ID and 
			roster.Team_ID = team_profile.Team_ID), event_profile 
			where 
			roster.Event_ID = event_profile.Event_ID and 
			roster.Player_ID = $playerID and
			roster.Registered ='Y' and 
			(roster.Archive is null or roster.Archive = '0000-00-00') 
			order by roster.Event_ID";
	return $db->query($sql,"resultset");
}

function get_session_data($session_id, $securityCode) {
	$db = Database::getInstance();
	$sql = "select session_data.Session_Data 
			from session_data 
			where
			session_data.Session_ID = '".mysql_real_escape_string($session_id)."' AND
            session_data.Http_User_Agent = '".mysql_real_escape_string(md5($_SERVER["HTTP_USER_AGENT"] . $securityCode))."' AND
            session_data.Session_Expire > '".time()."' 
            LIMIT 1";
	return $db->query($sql,"resultset");
}

function get_session_users_online() {
	$db = Database::getInstance();
	$sql = "select count(session_data.session_id) as count 
			from session_data";
	return $db->query($sql,"count");	
}

function get_short_name($email) {
	$db = Database::getInstance();
	$sql = "select Short_Name 
			from player_account 
			where 
			Email = '$email'";
	return $db->query($sql,"Short_Name");
}

function get_state_code($stateName) {
	$db = Database::getInstance();
	$sql = "select Code 
			from state_lookup
			where 
			Name = '$stateName'";
	return $db->query($sql,"Code");
}

function get_state_name($stateCode) {
	$db = Database::getInstance();
	$sql = "select Name 
			from state_lookup
			where 
			Code = '$stateCode'";
	return $db->query($sql,"Name");
}

function get_states() {
	$db = Database::getInstance();
	$sql = "select Code, Name 
			from state_lookup";
	return $db->query($sql,"resultset");
}

function get_team_players_assigned($eventID, $teamID) {
	$db = Database::getInstance();
	$sql = "select player_profile.Player_ID, player_profile.First_Name, player_profile.Last_Name, 
			player_profile.H_Phone 
			from player_profile, roster 
			where 
			roster.Event_ID = $eventID and 
			roster.Team_ID = $teamID and
			roster.Registered = 'Y' and
			roster.Team_ID > 0 and 
			roster.Player_ID = player_profile.Player_ID
			order by player_profile.Last_Name asc";
	return $db->query($sql,"resultset");
}

function get_team_players_unassigned($eventID) {
	$db = Database::getInstance();
	$sql = "select player_profile.Player_ID, player_profile.First_Name, player_profile.Last_Name 
			from player_profile, roster 
			where 
			roster.Event_ID = $eventID and
			roster.Registered = 'Y' and
			roster.Team_ID = 0 and 
			roster.Player_ID = player_profile.Player_ID 
			order by player_profile.Last_Name asc";
	return $db->query($sql,"resultset");
}

function get_team_profile($eventID, $teamID) {
	$db = Database::getInstance();
	$sql = "select Team_ID, Team_Name 
			from team_profile 
			where 
			Event_ID = $eventID and 
			Team_ID = $teamID";
	return $db->query($sql,"row");
}

function get_team_profile_id($eventID, $teamName) {
	$db = Database::getInstance();
	$teamName = trim(mysql_real_escape_string($teamName));
	$sql = "select Team_ID 
			from team_profile 
			where 
			Event_ID = $eventID and 
			Team_Name = '$teamName' and 
			(Archive is null or Archive = '0000-00-00')";
	return $db->query($sql,"Team_ID");
}

function get_team_profiles_active($eventID) {
	$db = Database::getInstance();
	$sql = "select Team_ID, Team_Name 
			from team_profile 
			where 
			Event_ID = $eventID and 
			(Archive is null or Archive = '0000-00-00') 
			order by Team_Name asc";
	return $db->query($sql,"resultset");
}

function get_timezone_names() {
	$db = Database::getInstance();
	$sql = "select Timezone_Key, Timezone_ID, Timezone_Name 
			from 
			timezone
			order by Timezone_Key asc";
	return $db->query($sql,"resultset");
}

function get_wait_list_gender($eventID, $gender) {
	$db = Database::getInstance();
	$sql = "select count(*) as Gender_Count 
			from wait_list 
			where 
			Event_ID = $eventID and 
			Gender = '$gender' and 
			Assigned='N'";
	return $db->query($sql, "Gender_Count");
}

function get_wait_list($eventID) {
	$db = Database::getInstance();
	$sql = "select player_profile.Player_ID, player_profile.Last_Name, player_profile.First_Name, 
			player_profile.Gender, player_profile.Height, player_profile.Conditionx, player_profile.Skill_Lvl, 
			player_profile.Skill_Lvl_Def, player_profile.Yr_Exp, player_profile.Play_Lvl, player_profile.H_Phone, 
			player_profile.C_Phone, player_account.Email, wait_list.Wait_Number, wait_list.Pct_Of_Games 
			from player_profile, player_account, wait_list 
			where
			player_profile.Player_ID = wait_list.Player_ID and 
			player_profile.Player_ID = player_account.Player_ID and
			wait_list.Event_ID = $eventID and 
			wait_list.Assigned = 'N' 
			order by player_profile.Gender asc, wait_list.Wait_Number asc";
	return $db->query($sql, "resultset");
}

function get_wait_list_player($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select event_profile.Event_Name, event_profile.Org_Sponsor,	player_account.Email, wait_list.Gender 
			from event_profile, wait_list, player_account 
			where 
			wait_list.Event_ID = $eventID and  
			wait_list.Player_ID = $playerID and 
			wait_list.Event_ID = event_profile.Event_ID and 
			wait_list.Player_ID = player_account.Player_ID and 
			(wait_list.Archive is null or wait_list.Archive = '0000-00-00')";
	return $db->query($sql, "row");
}

function get_wait_list_players($eventID,$gender) {
	$db = Database::getInstance();
	$sql = "select wait_list.Player_ID 
			from wait_list 
			where
			wait_list.Event_ID = $eventID and 
			wait_list.Gender = '$gender' and 
			wait_list.Assigned = 'N' 
			order by wait_list.Wait_Number asc";
	return $db->query($sql, "resultset");
}

function get_wait_list_status($playerID) {
	$db = Database::getInstance();
	$sql = "select event_profile.Event_Name, event_profile.Event_Type, event_profile.Timezone_ID,  
			wait_list.Event_ID,	wait_list.Player_ID, wait_list.Gender, wait_list.Created 
			from
			wait_list, event_profile 
			where 
			wait_list.Event_ID = event_profile.Event_ID and 
			wait_list.Player_ID = $playerID and 
			wait_list.Assigned <> 'Y' and 
			(wait_list.Archive is null or wait_list.Archive = '0000-00-00')
			order by wait_list.Event_ID";
	return $db->query($sql,"resultset");
}

function get_wait_list_unassigned($eventID, $playerID) {
	$db = Database::getInstance();
	$sql = "select wait_list.Event_Fee, wait_list.TShirt_Fee, wait_list.Disc_Fee, 
			wait_list.UPA_Event_Fee, wait_list.Disc_Count, wait_list.Gender, wait_list.Pct_Of_Games 
			from wait_list 
			where
			wait_list.Event_ID = $eventID and
			wait_list.Player_ID = $playerID and 			 
			wait_list.Assigned = 'N'";
	return $db->query($sql,"row");
}

function insert_event_home_page($eventID, $enteredData) {
	$db = Database::getInstance();
	$publishHomePage = trim(mysql_real_escape_string($enteredData['Publish_Home_Page']));
	$homePageText = trim(mysql_real_escape_string($enteredData['Home_Page_Text']));
	$sql = "insert into event_home_page 
			(Event_ID, 
			Publish_Home_Page, 
			Home_Page_Text) 
			values ($eventID, '$publishHomePage', '$homePageText')";
	return $db->query($sql,"insert");
}

function insert_event_profile_base($enteredData) {
	$db = Database::getInstance();
	$orgSponsor = trim(mysql_real_escape_string($enteredData['Org_Sponsor']));
	$eventName = trim(mysql_real_escape_string($enteredData['Event_Name']));
	$eventType = mysql_real_escape_string($enteredData['Event_Type']);
	$stateProv = trim(mysql_real_escape_string($enteredData['State_Prov']));
	$eventTime =  trim(mysql_real_escape_string($enteredData['Event_Time']));
	$daysOfWeek = mysql_real_escape_string(implode(",", $enteredData['Days_Of_Week']));
	$location = trim(mysql_real_escape_string($enteredData['Location']));
	$locationLink = trim(mysql_real_escape_string($enteredData['Location_Link']));
	$contactName = trim(mysql_real_escape_string($enteredData['Contact_Name']));
	$contactPhone = trim(mysql_real_escape_string($enteredData['Contact_Phone']));
	$contactEmail = trim(mysql_real_escape_string($enteredData['Contact_Email']));
	$publishPhone = trim(mysql_real_escape_string($enteredData['Publish_Phone']));
	$created = get_current_gmt_time();
	$sql = "insert into event_profile 
			(Event_Name, Event_Type, Org_Sponsor, State_Prov, 
			Event_Time, Days_Of_Week, Location, Location_Link, 
			Contact_Name, Contact_Phone, Contact_Email, Publish_Phone, Created) 
			values ('$eventName', '$eventType', '$orgSponsor', '$eventTime', '$daysOfWeek',  
			'$location', '$locationLink', '$contactName', '$contactPhone', '$contactEmail', 
			'$publishPhone', '$created')";
	return $db->query($sql,"insert");
}

function insert_event_profile_plus($enteredData) {
	$db = Database::getInstance();
	$orgSponsor = trim(mysql_real_escape_string($enteredData['Org_Sponsor']));
	$eventName = trim(mysql_real_escape_string($enteredData['Event_Name']));
	$eventType = mysql_real_escape_string($enteredData['Event_Type']);
	$country = trim(mysql_real_escape_string($enteredData['Country']));
	$stateProv = trim(mysql_real_escape_string($enteredData['State_Prov']));
	$city = trim(mysql_real_escape_string($enteredData['City']));
	$regBegin = mysql_real_escape_string($enteredData['Reg_Begin']);
	$regEnd = mysql_real_escape_string($enteredData['Reg_End']);
	$timeZoneID = mysql_real_escape_string($enteredData['Timezone_ID']);
	$eventBegin = mysql_real_escape_string($enteredData['Event_Begin']);
	$eventEnd = mysql_real_escape_string($enteredData['Event_End']);
	$eventTime =  trim(mysql_real_escape_string($enteredData['Event_Time']));
	$daysOfWeek = mysql_real_escape_string(implode(",", $enteredData['Days_Of_Week']));
	$numOfTeams = trim(mysql_real_escape_string($enteredData['Number_Of_Teams']));
	$playersPerTeam = trim(mysql_real_escape_string($enteredData['Players_Per_Team']));
	$teamRatio = trim(mysql_real_escape_string($enteredData['Team_Ratio']));
	$limitMen = trim(mysql_real_escape_string($enteredData['Limit_Men']));
	$limitWomen = trim(mysql_real_escape_string($enteredData['Limit_Women']));
	$upaEvent = mysql_real_escape_string($enteredData['UPA_Event']);
	$currencyCode = mysql_real_escape_string($enteredData['Currency_Code']);
	$eventFee = trim(mysql_real_escape_string($enteredData['Event_Fee']));
	$eventTShirtFee = trim(mysql_real_escape_string($enteredData['Event_TShirt_Fee']));
	$eventDiscFee = trim(mysql_real_escape_string($enteredData['Event_Disc_Fee']));
	$paymentDeadline = trim(mysql_real_escape_string($enteredData['Payment_Deadline']));
	$paymentType = mysql_real_escape_string(implode(",", $enteredData['Payment_Type']));
	$paymentAccount = trim(mysql_real_escape_string($enteredData['Payment_Account']));
	$paymentItemName = trim(mysql_real_escape_string($enteredData['Payment_Item_Name']));
	$paymentChkPayee = trim(mysql_real_escape_string($enteredData['Payment_Chk_Payee']));
	$paymentChkAddress = trim(mysql_real_escape_string($enteredData['Payment_Chk_Address']));
	$paymentChkCity = trim(mysql_real_escape_string($enteredData['Payment_Chk_City']));
	$paymentChkState = trim(mysql_real_escape_string($enteredData['Payment_Chk_State']));
	$paymentChkZip = trim(mysql_real_escape_string($enteredData['Payment_Chk_Zip']));
	$location = trim(mysql_real_escape_string($enteredData['Location']));
	$locationLink = trim(mysql_real_escape_string($enteredData['Location_Link']));
	$contactName = trim(mysql_real_escape_string($enteredData['Contact_Name']));
	$contactPhone = trim(mysql_real_escape_string($enteredData['Contact_Phone']));
	$contactEmail = trim(mysql_real_escape_string($enteredData['Contact_Email']));
	$publishPhone = trim(mysql_real_escape_string($enteredData['Publish_Phone']));
 	$created = get_current_gmt_time();
 	
 	$regBeginGMT = convert_time_local_to_gmt($timeZoneID, $regBegin);
	$regEndGMT = convert_time_local_to_gmt($timeZoneID, $regEnd);
	$eventBeginGMT = convert_time_local_to_gmt($timeZoneID, $eventBegin);
	$eventEndGMT = convert_time_local_to_gmt($timeZoneID, $eventEnd);
 	
	$sql = "insert into event_profile 
			(Event_Name, Event_Type, Org_Sponsor, Country, State_Prov, City, 
			Reg_Begin, Reg_End, Timezone_ID, Event_Begin, Event_End, 
			Event_Time, Days_Of_Week, Number_Of_Teams, Players_Per_Team, 
			Team_Ratio, Limit_Men, Limit_Women, UPA_Event, Currency_Code, Event_Fee, 
			Event_TShirt_Fee, Event_Disc_Fee, 
			Payment_Deadline, Payment_Type, Payment_Account, Payment_Item_Name,   
			Payment_Chk_Payee, Payment_Chk_Address, Payment_Chk_City, Payment_Chk_State, Payment_Chk_Zip, 
			Location, Location_Link, Contact_Name, Contact_Phone, Contact_Email, Publish_Phone, Created) 
			values ('$eventName', '$eventType', '$orgSponsor', '$country', '$stateProv', '$city', 
			'$regBeginGMT', '$regEndGMT', '$timeZoneID', '$eventBeginGMT', '$eventEndGMT', '$eventTime', 
			'$daysOfWeek', $numOfTeams,	'$playersPerTeam', '$teamRatio', $limitMen, $limitWomen, 
			'$upaEvent', '$currencyCode', $eventFee, $eventTShirtFee, $eventDiscFee, 
			'$paymentDeadline', '$paymentType',	'$paymentAccount', '$paymentItemName',  
			'$paymentChkPayee','$paymentChkAddress', '$paymentChkCity', '$paymentChkState', '$paymentChkZip',
			'$location', '$locationLink', '$contactName', '$contactPhone', '$contactEmail', 
			'$publishPhone', '$created')";
	return $db->query($sql,"insert");
}

function insert_event_profile_pickup($lat, $long, $countryCd, $stateProv, $city, $gameName, $link) {
	$updated = get_current_gmt_time();
	$thisLat = (isset($lat) and is_numeric($lat)) ? mysql_real_escape_string($lat) : 0;
	$thisLong = (isset($long) and is_numeric($long)) ? mysql_real_escape_string($long) : 0;
	$thisStateProv = trim(mysql_real_escape_string($stateProv));
	$thisCity = trim(mysql_real_escape_string($city));
	$thisGameName = trim(mysql_real_escape_string($gameName));
	$db = Database::getInstance();
	$sql = "insert into event_profile 
			(Latitude, Longitude, Event_Type, Country, State_Prov, City, Event_Name, Location_Link, 
			Publish_Event, Created) 
			values ($lat, $long, '2', '$countryCd', '$thisStateProv', '$thisCity', '$thisGameName', 
			'$link', 'Y', '$updated')";
	return $db->query($sql,"insert");
}

function insert_event_team_role($playerID, $eventID, $teamID, $role) {
	$db = Database::getInstance();
	$sql = "insert into event_role 
			(Player_ID, Event_ID, Team_ID, Role) 
			values ($playerID, $eventID, $teamID, '$role')";
	return $db->query($sql,"insert");
}

function insert_ipn_msg($muTxnType, $eventID, $playerID, $msg) {
	$created = get_current_gmt_time();
	$txnType = trim(htmlspecialchars($muTxnType));
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$thisMsg = trim(mysql_real_escape_string(htmlspecialchars($msg)));
	$db = Database::getInstance();
	$sql = "insert into ipn_message 
			(Created, Txn_Type, Event_ID, Player_ID, Message) 
			values ('$created', '$muTxnType', $thisEventID, $thisPlayerID, '$thisMsg')";
	return $db->query($sql,"insert");
}

function insert_ipn_txn($muTxnType,$eventID,$playerID,$_POST) {
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$txnID = trim(htmlspecialchars($_POST['txn_id']));
	$txnType = htmlspecialchars($_POST['txn_type']);
	$itemName = trim(mysql_real_escape_string($_POST['item_name']));
	$firstName = trim(mysql_real_escape_string($_POST['first_name']));
	$lastName = trim(mysql_real_escape_string($_POST['last_name']));
	$payerEmail = trim(mysql_real_escape_string($_POST['payer_email']));
	$business = trim(mysql_real_escape_string($_POST['business']));
	$recEmail = trim(mysql_real_escape_string($_POST['receiver_email']));
	$payDate = trim(mysql_real_escape_string($_POST['payment_date']));
	$mcCurrency = htmlspecialchars($_POST['mc_currency']);
	$mcGross = htmlspecialchars($_POST['mc_gross']);
	$mcFee = isset($_POST['mc_fee']) ? htmlspecialchars($_POST['mc_fee']) : 0;
	$custom = trim(mysql_real_escape_string($_POST['custom']));
	$created = get_current_gmt_time();
	$db = Database::getInstance();
	$sql = "insert into ipn_tracker 
			(Txn_ID, MU_Txn_Type, Txn_Type, Event_ID, Player_ID, Item_Name, First_Name, Last_Name, 
			Payer_Email, Business, Receiver_Email, Payment_Date, MC_Currency, MC_Gross, MC_Fee, Custom, Created) 
			values ('$txnID', '$muTxnType', '$txnType', $thisEventID, $thisPlayerID, '$itemName', '$firstName', 
			'$lastName', '$payerEmail', '$business', '$recEmail', '$payDate', '$mcCurrency', $mcGross, 
			$mcFee, '$custom', '$created')";
	return $db->query($sql,"insert");
}

function insert_msg_log($modType, $msgType, $eventID, $playerID, $msg) {
	$created = get_current_gmt_time();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$thisMsg = trim(mysql_real_escape_string($msg));
	$db = Database::getInstance();
	$sql = "insert into message_log 
			(Created, Module_Type, Message_Type, Event_ID, Player_ID, Message) 
			values ('$created', '$modType', '$msgType', $thisEventID, $thisPlayerID, '$thisMsg')";
	return $db->query($sql,"insert");
}

function insert_player_account($enteredData) {
	$db = Database::getInstance();
	$shortName = trim(mysql_real_escape_string($enteredData['Short_Name']));
	$email = trim(mysql_real_escape_string($enteredData['Email']));
	$password = mysql_real_escape_string($enteredData['Password']);
	$hashPassword = sha1(sha1($password).”flash”);
	$emailOptCapt = trim(mysql_real_escape_string($enteredData['Email_Opt_Capt']));
	$emailOptMU = trim(mysql_real_escape_string($enteredData['Email_Opt_MU']));
	$terms = trim(mysql_real_escape_string($enteredData['Terms']));
	$created = get_current_gmt_time();
	$sql = "insert into player_account 
			(Short_Name, Email, Password, Email_Opt_Capt, Email_Opt_MU, Terms, Created) 
			values 
			('$shortName', '$email', '$hashPassword', '$emailOptCapt', '$emailOptMU', '$terms', '$created')";
	return $db->query($sql,"insert");
}

function insert_player_profile($playerID, $enteredData) {
	$db = Database::getInstance();
	$firstName = trim(mysql_real_escape_string($enteredData['First_Name']));
	$lastName = trim(mysql_real_escape_string($enteredData['Last_Name']));
	$address = trim(mysql_real_escape_string($enteredData['Address']));
	$city = trim(mysql_real_escape_string($enteredData['City']));
	$stateProv = mysql_real_escape_string($enteredData['State_Prov']);
	$postCode = trim(mysql_real_escape_string($enteredData['Post_Code']));
	$country = trim(mysql_real_escape_string($enteredData['Country']));
	$hPhone = trim(mysql_real_escape_string($enteredData['H_Phone']));
	$cPhone = trim(mysql_real_escape_string($enteredData['C_Phone']));
	$wPhone = trim(mysql_real_escape_string($enteredData['W_Phone']));
	$eContactName = trim(mysql_real_escape_string($enteredData['E_Contact_Name']));
	$eContactPhone = trim(mysql_real_escape_string($enteredData['E_Contact_Phone']));
	$gender = mysql_real_escape_string($enteredData['Gender']);
	$height = mysql_real_escape_string($enteredData['Height']);
	$tShirtSize = mysql_real_escape_string($enteredData['T_Shirt_Size']);
	$condition = mysql_real_escape_string($enteredData['Conditionx']);
	$skillLvl = mysql_real_escape_string($enteredData['Skill_Lvl']);
	$skillLvlDef = mysql_real_escape_string($enteredData['Skill_Lvl_Def']);
	$playLvl = mysql_real_escape_string(implode(',', $enteredData['Play_Lvl']));
	$yrsExp = mysql_real_escape_string($enteredData['Yr_Exp']);
	$buddyName = trim(mysql_real_escape_string($enteredData['Buddy_Name']));
	$upaCurMember = mysql_real_escape_string($enteredData['UPA_Cur_Member']);
	$upaNumber = trim(mysql_real_escape_string($enteredData['UPA_Number']));
	$student = isset($enteredData['Student']) ? mysql_real_escape_string($enteredData['Student']) : "";
	$over18 = mysql_real_escape_string($enteredData['Over18']);
	$sql = "insert into player_profile 
			(Player_ID, First_Name, Last_Name, Address, City, State_Prov,
			Post_Code, Country, H_Phone, C_Phone, W_Phone, 
			E_Contact_Name, E_Contact_Phone, Gender, Height, 
			T_Shirt_Size, Conditionx, Skill_Lvl, Skill_Lvl_Def, Play_Lvl, 
			Yr_Exp,  Buddy_Name, UPA_Cur_Member, UPA_Number, Student, Over18) 
			values ($playerID, '$firstName', '$lastName', '$address', 
			'$city', '$stateProv', '$postCode', '$country', '$hPhone',  
			'$cPhone', '$wPhone', '$eContactName', '$eContactPhone', '$gender', 
			'$height', '$tShirtSize', '$condition', '$skillLvl', '$skillLvlDef', 
			'$playLvl', '$yrsExp', '$buddyName', '$upaCurMember', '$upaNumber', '$student', '$over18')";
	return $db->query($sql,"insert");
}

function insert_roster($eventID, $playerID, $teamID, $fees, $discCount, $gender, $pctOfGames) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$thisTeamID = (isset($teamID) and is_numeric($teamID)) ? $teamID : 0;
	$eventFee =	mysql_real_escape_string($fees['event']);
	$eventTShirtFee = mysql_real_escape_string($fees['eventTShirt']);
	$eventDiscFee = mysql_real_escape_string($fees['eventDisc']);
	$event1TimeFee = mysql_real_escape_string($fees['event1Time']);
	$thisDiscCount = mysql_real_escape_string($discCount);
	$thisGender = mysql_real_escape_string($gender);
	$thisPctOfGames = mysql_real_escape_string($pctOfGames);
	$created = get_current_gmt_time();
	$sql = "insert into roster 
			(Event_ID, Player_ID, Team_ID, Event_Fee, TShirt_Fee, Disc_Fee, UPA_Event_Fee, Disc_Count, 
			Registered, Gender,	Pct_Of_Games, Created) 
			values ($thisEventID, $thisPlayerID, $thisTeamID, $eventFee, $eventTShirtFee, $eventDiscFee, 
			$event1TimeFee, $thisDiscCount, 'Y', '$thisGender', $thisPctOfGames, '$created')";
	return $db->query($sql,"insert");
}

function insert_session_data($session_id, $session_data, $securityCode, $sessionLifetime) {
	$db = Database::getInstance();
	/** needed to add this check to support db based session management - seems that php 
	 *  was trying to insert a record into the SESSION_DATA table after all of the other 
	 *  db processing on the page had completed and the existing db connection closed.
	 *  Don't know why this session insert activity happened after everything else had been 
	 *  completed.  Consequently, also needed to add code to close the connection within the 
	 *  insert session function. 
	 */
	$db->verifyConnection();
	
	$thisSessionID = mysql_real_escape_string($session_id);
	$thisHTTPUserAgent = mysql_real_escape_string(md5($_SERVER["HTTP_USER_AGENT"] . $securityCode));
	$thisSessionData = mysql_real_escape_string($session_data);
	$thisSessionLifetime = mysql_real_escape_string(time() + $sessionLifetime);
	$sql = "insert into session_data
			(Session_Id, Http_User_Agent, Session_Data, Session_Expire)
	 		values ('$thisSessionID', '$thisHTTPUserAgent', '$thisSessionData', '$thisSessionLifetime')
            on duplicate key update
                Session_Data = '$thisSessionData',
                Session_Expire = '$thisSessionLifetime'";
	return $db->query($sql,"insert");
}

function insert_team_profile($eventID, $enteredData) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$teamName = trim(mysql_real_escape_string($enteredData['Team_Name']));
	$sql = "insert into team_profile 
			(Event_ID, Team_Name) 
			values ($thisEventID, '$teamName')";
	return $db->query($sql,"insert");
}

function insert_wait_list($eventID, $playerID, $gender, $pctOfGames, $fees, $discCount) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$eventFee =	mysql_real_escape_string($fees['event']);
	$eventTShirtFee = mysql_real_escape_string($fees['eventTShirt']);
	$eventDiscFee = mysql_real_escape_string($fees['eventDisc']);
	$event1TimeFee = mysql_real_escape_string($fees['event1Time']);
	$created = get_current_gmt_time();
	$sql = "insert into wait_list 
			(Event_ID, Player_ID, Event_Fee, TShirt_Fee, Disc_Fee, UPA_Event_Fee, Disc_Count, 
			Gender, Pct_Of_Games, Created) 
			values ($thisEventID, $thisPlayerID, $eventFee, $eventTShirtFee, $eventDiscFee, 
					$event1TimeFee, $discCount, '$gender', $pctOfGames, '$created')";
	return $db->query($sql,"insert");
}

function update_archive_event_home_page($eventID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", mktime());
	$sql = "update event_home_page 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_archive_event_profile($eventID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update event_profile 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_archive_event_role($eventID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update event_role 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_archive_event_player_role($eventID,$playerID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update event_role 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID and 
			Player_ID = $playerID";
	return $db->query($sql,"update");
}

function update_archive_event_team_role($eventID,$teamID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update event_role 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID and 
			Team_ID = $teamID";
	return $db->query($sql,"update");
}

function update_archive_roster($eventID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update roster 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_archive_team_profile($eventID, $teamID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update team_profile 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID and 
			Team_ID = $teamID";
	return $db->query($sql,"update");
}

function update_archive_team($eventID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update team_profile 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_archive_wait_list($eventID) {
	$db = Database::getInstance();
	$archiveDate = date("Y-m-d", time());
	$sql = "update wait_list 
			set
			Archive = '$archiveDate' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_event_home_page($eventID, $enteredData) {
	$db = Database::getInstance();
	$publishHomePage = trim(mysql_real_escape_string($enteredData['Publish_Home_Page']));
	$homePageText = trim(mysql_real_escape_string($enteredData['Home_Page_Text']));
	$sql = "update event_home_page 
			set 
			Publish_Home_Page = '$publishHomePage', 
			Home_Page_Text = '$homePageText' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_event_profile_as_paid($eventID) {
	$db = Database::getInstance();
	$sql = "update event_profile 
			set
			Payment_Status = 'Y' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_event_profile_as_published($eventID,$publishEvent) {
	$db = Database::getInstance();
	$thisPublishEvent = trim(mysql_real_escape_string($publishEvent));
	$sql = "update event_profile 
			set
			Publish_Event = '$thisPublishEvent' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_event_profile_base($eventID, $enteredData) {
	$db = Database::getInstance();
	$publishEvent = trim(mysql_real_escape_string($enteredData['Publish_Event']));
	$eventName = trim(mysql_real_escape_string($enteredData['Event_Name']));
	$eventType = mysql_real_escape_string($enteredData['Event_Type']);
	$orgSponsor = trim(mysql_real_escape_string($enteredData['Org_Sponsor']));
	$stateProv = trim(mysql_real_escape_string($enteredData['State_Prov']));
	$eventTime =  trim(mysql_real_escape_string($enteredData['Event_Time']));
	$daysOfWeek = mysql_real_escape_string(implode(',',$enteredData['Days_Of_Week']));
	$location = trim(mysql_real_escape_string($enteredData['Location']));
	$locationLink = trim(mysql_real_escape_string($enteredData['Location_Link']));
	$contactName = trim(mysql_real_escape_string($enteredData['Contact_Name']));
	$contactPhone = trim(mysql_real_escape_string($enteredData['Contact_Phone']));
	$contactEmail = trim(mysql_real_escape_string($enteredData['Contact_Email']));
	$publishPhone = trim(mysql_real_escape_string($enteredData['Publish_Phone']));
	$sql = "update event_profile 
			set
			Event_Name = '$eventName',
			Event_Type = '$eventType',
			Org_Sponsor = '$orgSponsor',  
			State_Prov = '$stateProv',
			Event_Time = '$eventTime', 
			Days_Of_Week = '$daysOfWeek', 
			Location = '$location', 
			Location_Link = '$locationLink', 
			Contact_Name = '$contactName',
			Contact_Phone = '$contactPhone',
			Contact_Email = '$contactEmail',
			Publish_Phone = '$publishPhone', 
			Publish_Event = '$publishEvent' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_event_profile_plus($eventID, $enteredData) {
	$db = Database::getInstance();
	$publishEvent = trim(mysql_real_escape_string($enteredData['Publish_Event']));
	$eventName = trim(mysql_real_escape_string($enteredData['Event_Name']));
	$eventType = mysql_real_escape_string($enteredData['Event_Type']);
	$orgSponsor = trim(mysql_real_escape_string($enteredData['Org_Sponsor']));
	$country = trim(mysql_real_escape_string($enteredData['Country']));
	$stateProv = trim(mysql_real_escape_string($enteredData['State_Prov']));
	$city = trim(mysql_real_escape_string($enteredData['City']));
	$regBegin = mysql_real_escape_string($enteredData['Reg_Begin']);
	$regEnd = mysql_real_escape_string($enteredData['Reg_End']);
	$timeZoneID = mysql_real_escape_string($enteredData['Timezone_ID']);
	$eventBegin = mysql_real_escape_string($enteredData['Event_Begin']);
	$eventEnd = mysql_real_escape_string($enteredData['Event_End']);
	$eventTime =  trim(mysql_real_escape_string($enteredData['Event_Time']));
	$daysOfWeek = mysql_real_escape_string(implode(',',$enteredData['Days_Of_Week']));
	$numOfTeams = trim(mysql_real_escape_string($enteredData['Number_Of_Teams']));
	$playersPerTeam = trim(mysql_real_escape_string($enteredData['Players_Per_Team']));
	$teamRatio = trim(mysql_real_escape_string($enteredData['Team_Ratio']));
	$limitMen = trim(mysql_real_escape_string($enteredData['Limit_Men']));
	$limitWomen = trim(mysql_real_escape_string($enteredData['Limit_Women']));
	$upaEvent = mysql_real_escape_string($enteredData['UPA_Event']);
	$currencyCode = mysql_real_escape_string($enteredData['Currency_Code']);	
	$eventFee = trim(mysql_real_escape_string($enteredData['Event_Fee']));
	$eventTShirtFee = trim(mysql_real_escape_string($enteredData['Event_TShirt_Fee']));
	$eventDiscFee = trim(mysql_real_escape_string($enteredData['Event_Disc_Fee']));
	$paymentType = mysql_real_escape_string(implode(',',$enteredData['Payment_Type']));
	$paymentAccount = trim(mysql_real_escape_string($enteredData['Payment_Account']));
	$paymentItemName = trim(mysql_real_escape_string($enteredData['Payment_Item_Name']));
	$paymentChkPayee = trim(mysql_real_escape_string($enteredData['Payment_Chk_Payee']));
	$paymentChkAddress = trim(mysql_real_escape_string($enteredData['Payment_Chk_Address']));
	$paymentChkCity = trim(mysql_real_escape_string($enteredData['Payment_Chk_City']));
	$paymentChkState = mysql_real_escape_string($enteredData['Payment_Chk_State']);
	$paymentChkZip = trim(mysql_real_escape_string($enteredData['Payment_Chk_Zip']));
	$paymentDeadline = trim(mysql_real_escape_string($enteredData['Payment_Deadline']));
	$location = trim(mysql_real_escape_string($enteredData['Location']));
	$locationLink = trim(mysql_real_escape_string($enteredData['Location_Link']));
	$contactName = trim(mysql_real_escape_string($enteredData['Contact_Name']));
	$contactPhone = trim(mysql_real_escape_string($enteredData['Contact_Phone']));
	$contactEmail = trim(mysql_real_escape_string($enteredData['Contact_Email']));
	$publishPhone = trim(mysql_real_escape_string($enteredData['Publish_Phone']));

	$regBeginGMT = convert_time_local_to_gmt($timeZoneID, $regBegin);
	$regEndGMT = convert_time_local_to_gmt($timeZoneID, $regEnd);
	$eventBeginGMT = convert_time_local_to_gmt($timeZoneID, $eventBegin);
	$eventEndGMT = convert_time_local_to_gmt($timeZoneID, $eventEnd);
	
	$sql = "update event_profile 
			set
			Event_Name = '$eventName',
			Event_Type = '$eventType',
			Org_Sponsor = '$orgSponsor',  
			Country = '$country', 
			State_Prov = '$stateProv',
			City = '$city',
			Reg_Begin = '$regBeginGMT',
			Reg_End = '$regEndGMT',
			Timezone_ID = '$timeZoneID',
			Event_Begin = '$eventBeginGMT', 
			Event_End = '$eventEndGMT', 
			Event_Time = '$eventTime', 
			Days_Of_Week = '$daysOfWeek', 
			Number_Of_Teams = '$numOfTeams', 
			Players_Per_Team = '$playersPerTeam', 
			Team_Ratio = '$teamRatio', 
			Limit_Men = $limitMen, 
			Limit_Women = $limitWomen,
			UPA_Event = '$upaEvent', 
			Currency_Code = '$currencyCode', 
			Event_Fee = $eventFee, 
			Event_TShirt_Fee = $eventTShirtFee, 
			Event_Disc_Fee = $eventDiscFee,
			Payment_Type = '$paymentType',
			Payment_Account = '$paymentAccount',
			Payment_Item_Name = '$paymentItemName',
			Payment_Chk_Payee = '$paymentChkPayee',
			Payment_Chk_Address = '$paymentChkAddress',
			Payment_Chk_City = '$paymentChkCity',
			Payment_Chk_State = '$paymentChkState',
			Payment_Chk_Zip = '$paymentChkZip',
			Payment_Deadline = '$paymentDeadline', 
			Location = '$location', 
			Location_Link = '$locationLink', 
			Contact_Name = '$contactName',
			Contact_Phone = '$contactPhone',
			Contact_Email = '$contactEmail',
			Publish_Phone = '$publishPhone', 
			Publish_Event = '$publishEvent' 
			where 
			Event_ID = $eventID";
	return $db->query($sql,"update");
}

function update_event_profile_time($lat,$long) {
	$updated = get_current_gmt_time();
	$db = Database::getInstance();
	$sql = "update event_profile 
			set 
			created = '$updated' 
			where 
			latitude = $lat and 
			longitude = $long";
	return $db->query($sql,"update");
}

function update_password($email) { 
	// get a random dictionary word b/w 6 and 13 chars in length
	$newPassword = get_random_word(6, 13);
	if($newPassword==false)
		return false;
	// add a number  between 0 and 999 to it
	// to make it a slightly better password
	srand ((double) microtime() * 1000000);
	$rand_number = rand(0, 999); 
	$newPassword .= $rand_number;
	// set user's password to this in database or return false
	$db = Database::getInstance();
	$hashPassword = sha1(sha1($newPassword).”flash”);
	$sql = "update player_account 
			set 
			password = '$hashPassword' 
			where 
			email = '$email'";
	if(!$db->query($sql,"update")) {
		return false;  // not changed
	} else {
		return $newPassword;  // changed successfully  
	}
}

function update_player_account($playerID, $enteredData) {
	$db = Database::getInstance();
	$emailOptCapt = 
		(isset($enteredData['Email_Opt_Capt']) ? mysql_real_escape_string($enteredData['Email_Opt_Capt']) : "N");
	$emailOptMU = 
		(isset($enteredData['Email_Opt_MU']) ? mysql_real_escape_string($enteredData['Email_Opt_MU']) : "N");
	$terms = (isset($enteredData['Terms']) ? mysql_real_escape_string($enteredData['Terms']) : "N");
	$sql = "update player_account 
			set
			Email_Opt_Capt = '$emailOptCapt',
			Email_Opt_MU = '$emailOptMU', 
			Terms = '$terms' 
			where 
			Player_ID = $playerID";
	return $db->query($sql,"update");
}

function update_player_account_id($playerID, $enteredData) {
	$db = Database::getInstance();
	$shortName = trim(mysql_real_escape_string($enteredData['Short_Name']));
	$sql = "update player_account 
			set 
			Short_Name = '$shortName' 
			where 
			Player_ID = $playerID";
	return $db->query($sql,"update");
}

function update_player_account_password($playerID, $enteredData) {
	$db = Database::getInstance();
	$password = mysql_real_escape_string($enteredData['Password']);
	$hashPassword = sha1(sha1($password).”flash”);
	$sql = "update player_account 
			set 
			Password = '$hashPassword'    
			where 
			Player_ID = $playerID";
	return $db->query($sql,"update");
}

function update_player_account_plus($playerID, $enteredData) {
	$db = Database::getInstance();
	$email = trim(mysql_real_escape_string($enteredData['Email']));
	$emailOptCapt = 
		(isset($enteredData['Email_Opt_Capt']) ? mysql_real_escape_string($enteredData['Email_Opt_Capt']) : "N");
	$emailOptMU = 
		(isset($enteredData['Email_Opt_MU']) ? mysql_real_escape_string($enteredData['Email_Opt_MU']) : "N");
	$sql = "update player_account 
			set 
			Email = '$email', 
			Email_Opt_Capt = '$emailOptCapt',
			Email_Opt_MU = '$emailOptMU' 
			where 
			Player_ID = $playerID";		
	return $db->query($sql,"update");
}

function update_player_profile($playerID, $enteredData) {
	$db = Database::getInstance();
	$firstName = trim(mysql_real_escape_string($enteredData['First_Name']));
	$lastName = trim(mysql_real_escape_string($enteredData['Last_Name']));
	$address = trim(mysql_real_escape_string($enteredData['Address']));
	$city = trim(mysql_real_escape_string($enteredData['City']));
	$stateProv = mysql_real_escape_string($enteredData['State_Prov']);
	$postCode = trim(mysql_real_escape_string($enteredData['Post_Code']));
	$country = trim(mysql_real_escape_string($enteredData['Country']));
	$hPhone = trim(mysql_real_escape_string($enteredData['H_Phone']));
	$cPhone = trim(mysql_real_escape_string($enteredData['C_Phone']));
	$wPhone = trim(mysql_real_escape_string($enteredData['W_Phone']));
	$eContactName = trim(mysql_real_escape_string($enteredData['E_Contact_Name']));
	$eContactPhone = trim(mysql_real_escape_string($enteredData['E_Contact_Phone']));
	$gender = mysql_real_escape_string($enteredData['Gender']);
	$height = mysql_real_escape_string($enteredData['Height']);
	$tShirtSize = mysql_real_escape_string($enteredData['T_Shirt_Size']);
	$condition = mysql_real_escape_string($enteredData['Conditionx']);
	$skillLvl = mysql_real_escape_string($enteredData['Skill_Lvl']);
	$skillLvlDef = mysql_real_escape_string($enteredData['Skill_Lvl_Def']);
	$playLvl = mysql_real_escape_string(implode(',',$enteredData['Play_Lvl']));
	$yrsExp = mysql_real_escape_string($enteredData['Yr_Exp']);
	$buddyName = trim(mysql_real_escape_string($enteredData['Buddy_Name']));
	$upaCurMember = mysql_real_escape_string($enteredData['UPA_Cur_Member']);
	$upaNumber = trim(mysql_real_escape_string($enteredData['UPA_Number']));
	$student = (isset($enteredData['Student']) ? mysql_real_escape_string($enteredData['Student']) : "N");
	$over18 = mysql_real_escape_string($enteredData['Over18']);
	$sql = "update player_profile 
			set
			First_Name = '$firstName',
			Last_Name = '$lastName', 
			Address = '$address', 
			City = '$city', 
			State_Prov = '$stateProv',
			Post_Code = '$postCode',
			Country = '$country',
			H_Phone = '$hPhone', 
			C_Phone = '$cPhone', 
			W_Phone = '$wPhone', 
			E_Contact_Name = '$eContactName', 
			E_Contact_Phone = '$eContactPhone', 
			Gender = '$gender', 
			Height = '$height', 
			T_Shirt_Size = '$tShirtSize', 
			Conditionx = '$condition', 
			Skill_Lvl = '$skillLvl', 
			Skill_Lvl_Def = '$skillLvlDef',
			Play_Lvl = '$playLvl', 
			Yr_Exp = '$yrsExp',  
			Buddy_Name = '$buddyName', 
			UPA_Cur_Member = '$upaCurMember', 
			UPA_Number = '$upaNumber', 
			Student = '$student', 
			Over18 = '$over18' 
			where 
			Player_ID = $playerID";
	return $db->query($sql,"update");
}

function update_player_profile_upa_status($playerID, $status) {
	$db = Database::getInstance();
	$upaCurMember = mysql_real_escape_string($status['curUPAMember']);
	$sql = "update player_profile 
			set
			UPA_Cur_Member = '$upaCurMember' 
			where 
			Player_ID = $playerID";
	return $db->query($sql,"update");
}

function update_roster_player($eventID,$playerID,$enteredData) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$eventFee = trim(mysql_real_escape_string($enteredData['Event_Fee']));
	$tshirtFee = trim(mysql_real_escape_string($enteredData['TShirt_Fee']));
	$upaEventFee = trim(mysql_real_escape_string($enteredData['UPA_Event_Fee']));
	$discFee = trim(mysql_real_escape_string($enteredData['Disc_Fee']));
	$discCount = trim(mysql_real_escape_string($enteredData['Disc_Count']));
	$registered = trim(mysql_real_escape_string($enteredData['Registered']));
	$paymentStatus = trim(mysql_real_escape_string($enteredData['Payment_Status']));
	$paymentType = 
	trim(mysql_real_escape_string(((isset($enteredData['Payment_Type'])) ? $enteredData['Payment_Type']: "")));
	$sql = "update roster 
			set
			Event_Fee = $eventFee,
			TShirt_Fee = $tshirtFee,
			UPA_Event_Fee = $upaEventFee,
			Disc_Fee = $discFee,
			Disc_Count = $discCount,
			Registered = '$registered',  
			Payment_Status = '$paymentStatus', 
			Payment_Type = '$paymentType'
			where 
			Event_ID = $thisEventID and 
			Player_ID = $thisPlayerID";
	return $db->query($sql,"update");
}

function update_roster_player_as_paid($eventID,$playerID) {
	/** payment type 3 is Paypal */
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$sql = "update roster 
			set
			Payment_Status = 'Y', 
			Payment_Type = '3' 
			where 
			Event_ID = $thisEventID and 
			Player_ID = $thisPlayerID";
	return $db->query($sql,"update");
}

function update_roster_player_reset($eventID,$playerID,$enteredData) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$eventFee = trim(mysql_real_escape_string($enteredData['Event_Fee']));
	$tshirtFee = trim(mysql_real_escape_string($enteredData['TShirt_Fee']));
	$upaEventFee = trim(mysql_real_escape_string($enteredData['UPA_Event_Fee']));
	$discFee = trim(mysql_real_escape_string($enteredData['Disc_Fee']));
	$discCount = trim(mysql_real_escape_string($enteredData['Disc_Count']));
	$registered = trim(mysql_real_escape_string($enteredData['Registered']));
	$paymentStatus = trim(mysql_real_escape_string($enteredData['Payment_Status']));
	$paymentType = 
	trim(mysql_real_escape_string(((isset($enteredData['Payment_Type'])) ? $enteredData['Payment_Type']: "")));
	$sql = "update roster 
			set
			Team_ID = 0, 
			Event_Fee = $eventFee,
			TShirt_Fee = $tshirtFee,
			UPA_Event_Fee = $upaEventFee,
			Disc_Fee = $discFee,
			Disc_Count = $discCount,
			Registered = '$registered',  
			Payment_Status = '$paymentStatus', 
			Payment_Type = '$paymentType' 
			where 
			Event_ID = $thisEventID and 
			Player_ID = $thisPlayerID";
	return $db->query($sql,"update");
}

function update_roster_team_reset($eventID, $teamID) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisTeamID = (isset($teamID) and is_numeric($teamID)) ? $teamID : 0;
	$sql = "update roster 
			set
			Team_ID = 0 
			where 
			Event_ID = $thisEventID and 
			Team_ID = $thisTeamID";
	return $db->query($sql,"update");
}

function update_roster_team_players($eventID, $teamID, $playerIDArray) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisTeamID = (isset($teamID) and is_numeric($teamID)) ? $teamID : 0;
	$sql = "update roster 
			set
			Team_ID = $thisTeamID 
			where 
			Event_ID = $thisEventID and 
			Player_ID in ($playerIDArray)";
	return $db->query($sql,"update");
}

function update_team_profile($eventID, $teamID, $enteredData) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisTeamID = (isset($teamID) and is_numeric($teamID)) ? $teamID : 0;
	$teamName = trim(mysql_real_escape_string($enteredData['Team_Name']));
	$sql = "update team_profile 
			set
			Team_Name = '$teamName' 
			where 
			Event_ID = $thisEventID and 
			Team_ID = $thisTeamID";
	return $db->query($sql,"update");
}

function update_wait_list_assignment($eventID, $playerID) {
	$db = Database::getInstance();
	$thisEventID = (isset($eventID) and is_numeric($eventID)) ? $eventID : 0;
	$thisPlayerID = (isset($playerID) and is_numeric($playerID)) ? $playerID : 0;
	$sql = "update wait_list 
			set 
			Assigned = 'Y'  
			where 
			Event_ID = $thisEventID and 
			Player_ID = $thisPlayerID";
	return $db->query($sql,"update");
}
?>