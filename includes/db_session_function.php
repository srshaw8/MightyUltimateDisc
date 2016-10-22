<?php

/**
 *  A class to handle sessions by using a mySQL database for session related data storage providing better
 *  security then the default session handler used by PHP.
 *
 *  To prevent session hijacking, don't forget to use the {@link regenerate_id} method whenever you do a
 *  privilege change in your application
 *
 *  <i>Before usage, make sure you use the session_data.sql file from the <b>install</b> folder to set up the table
 *  used by the class</i>
 *
 *  After instantiating the class, use sessions as you would normally
 *
 *  This class is an adaptation of John Herren's code from the "Trick out your session handler" article
 *  ({@link http://devzone.zend.com/node/view/id/141}) and Chris Shiflett's code from Chapter 8, 
 *  Shared Hosting - Pg 78-80, of his book - "Essential PHP Security" ({@link http://phpsecurity.org/code/ch08-2})
 *
 *  Note that the class assumes that there is an active connection to a mySQL database and it does not attempt 
 *  to create one. This is due to the fact that, usually, there is a config file that holds the database 
 *  connection related information and another class, or function that handles database connection. If this is 
 *  not how you do it, you can easily adapt the code by putting the database connection related code in the 
 *  "open" method of the class.
 *
 *  See the documentation for more info.
 *
 *  Read the LICENSE file, provided with the package, to find out how you can use this PHP script.
 *
 *  If you don't find this file, please write an email to noname at nivelzero dot ro and you will be sent a copy 
 *  of the license file
 *
 *  For more resources visit {@link http://stefangabos.blogspot.com}
 *
 *  @author     Stefan Gabos <ix@nivelzero.ro>
 *  @version    1.0.6 (last revision: October 01, 2007)
 *  @copyright  (c) 2006 - 2007 Stefan Gabos
 *  @package    dbSession
 *  @example    example.php
*/

final class dbSession {
    /**
     *  Constructor of class
     *
     *  Initializes the class and starts a new session
     *
     *  There is no need to call start_session() after instantiating this class
     *
     *  @param  integer     $gc_maxlifetime     (optional) the number of seconds after which data will be seen 
	 * 											as 'garbage' and cleaned up on the next run of the gc 
	 * 											(garbage collection) routine. Default is specified in php.ini file
     *
     *  @param  integer     $gc_probability     (optional) used in conjunction with gc_divisor, is used to 
	 * 											manage probability that the gc routine is started. the 
	 * 											probability is expressed by the formula:
     * 		                                         probability = $gc_probability / $gc_divisor
     *                                          So if $gc_probability is 1 and $gc_divisor is 100 means that 
	 * 											there is a 1% chance the the gc routine will be called on each 
	 * 											request.  Default is specified in php.ini file
     *
     *  @param  integer     $gc_divisor         (optional) used in conjunction with gc_probability, is used 
	 * 											to manage probability that the gc routine is started. the 
	 * 											probability is expressed by the formula:
          *                                          probability = $gc_probability / $gc_divisor
     *                                          So if $gc_probability is 1 and $gc_divisor is 100 means that 
	 * 											there is a 1% chance the the gc routine will be called on 
	 * 											each request.  Default is specified in php.ini file
     *
     *  @param  string      $securityCode       (optional) the value of this argument is appended to the 
	 * 											HTTP_USER_AGENT before creating the md5 hash out of it. this way 
	 * 											we'll try to prevent HTTP_USER_AGENT spoofing. Default is 
	 * 											'sEcUr1tY_c0dE'
     *
     *  @param  string      $tableName          (optional) You can change the name of that table by setting 
	 * 											this property. Default is 'session_data'
     *
     *  @return void
     */
    private static $instance;
     
    private function __construct($gc_maxlifetime, $securityCode) {
		/** if $gc_maxlifetime is specified and is an integer number */
        if ($gc_maxlifetime != "" && is_integer($gc_maxlifetime)) {
        	$this->sessionLifetime = $gc_maxlifetime;
        } else {
        	$this->sessionLifetime = 1200;
        }
        /** we'll use this later on in order to try to prevent HTTP_USER_AGENT spoofing */
        $this->securityCode = $securityCode;

        /** register the new handler */
        session_set_save_handler(
            array(&$this, 'open'),
            array(&$this, 'close'),
            array(&$this, 'read'),
            array(&$this, 'write'),
            array(&$this, 'destroy'),
            array(&$this, 'gc')
        );
        register_shutdown_function('session_write_close');

        /** start the session */
        session_start();
        
        /** regen the ID in case someone is trying to hijack the session */
        if (!isset($_SESSION['initiated'])) {
			$this->regenerate_id();
   			$_SESSION['initiated'] = true;
		}
	}

    public static function getInstance() {
        if(!self::$instance instanceof self) {
			$gc_maxlifetime = 1200;
			$securityCode = "P1ayU1timate!";
            self::$instance = new dbSession($gc_maxlifetime, $securityCode);
        }
        return self::$instance;
    }

    /**
     *  Deletes all data related to the session
     *
     *  @since 1.0.1
     *
     *  @return void
     */
    function stop() {
        $this->regenerate_id();
        session_unset();
        session_destroy();
    }

    /**
     *  Regenerates the session id.
     *
     *  <b>Call this method whenever you do a privilege change!</b>
     *
     *  @return void
     */
    function regenerate_id() {
        /** saves the old session's id */
        $oldSessionID = session_id();

        /** this function will create a new session, with a new id and containing the data from the old session
         *  but will not delete the old session
         */
        session_regenerate_id();

        /** because the session_regenerate_id() function does not delete the old session,
         * we have to delete it manually */
        $this->destroy($oldSessionID);
    }

    /**
     *  Get the number of online users
     *
     *  @return integer     number of users currently online
     */
    function get_users_online() {
        /** call the garbage collector */
        $this->gc($this->sessionLifetime);
        return get_session_users_online();
    }

    /**
     *  Custom open() function
     *
     *  @access private
     */
    function open($save_path, $session_name) {
        return true;
    }

    /**
     *  Custom close() function
     *
     *  @access private
     */
    function close() {
         return true;
    }

    /**
     *  Custom read() function
     *
     *  @access private
     */
    function read($session_id) {
        /** reads session data associated with the session id
         * but only
         * - if the HTTP_USER_AGENT is the same as the one who had previously written to this session AND
         * - if session has not expired 
		 */
        $result = get_session_data($session_id, $this->securityCode);

        /** if anything was found */
        if (is_resource($result) && @mysql_num_rows($result) > 0) {
            /** return found data */
            $fields = @mysql_fetch_assoc($result);
            /** don't bother with the unserialization - PHP handles this automatically */
            return $fields["Session_Data"];
        }
        /** if there was an error return an empty string - this HAS to be an empty string */
        return "";
    }

    /**
     *  Custom write() function
     *
     *  @access private
     */
    function write($session_id, $session_data) {
        /** insert OR update session's data - this is how it works:
         * first it tries to insert a new row in the database BUT if session_id is already in the database then just
         * update session_data and session_expire for that specific session_id
         * read more here http://dev.mysql.com/doc/refman/4.1/en/insert-on-duplicate.html
         */
        $result = insert_session_data($session_id, $session_data, $this->securityCode, $this->sessionLifetime);
		
		$retVal = false;
        /** if anything happened */
        if ($result) {
            /** note that after this type of queries, mysql_affected_rows() returns
             * - 1 if the row was inserted
             * - 2 if the row was updated
             */

            /** if the row was updated */
            if (@mysql_affected_rows() > 1) {
                /** return TRUE */
                $retVal = true;
            /** if the row was inserted */
            } else {
                /** return an empty string */
                $retVal = "";
            }
        }
		/** need to kill this connection - seems that php 
         *  was trying to insert a record into the SESSION_DATA table after all of the other 
         *  db processing on the page had completed and the existing db connection closed.
         *  Don't know why this session insert activity happened after everything else had been 
		 *  completed.   Yeah... this is hokey...
		 */
		close_db();
        
        /** if something went wrong, return false */
        return $retVal;
    }

    /**
     *  Custom destroy() function
     *
     *  @access private
     */
    function destroy($session_id) {
        /** deletes the current session id from the database */
        return delete_session_data($session_id);
	}

    /**
     *  Custom gc() function (garbage collector)
     *
     *  @access private
     */
    function gc() {
        /** it deletes expired sessions from database */
        $result = delete_session_expired($this->sessionLifetime);
    }
}
?>