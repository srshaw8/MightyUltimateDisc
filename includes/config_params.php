<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */

/** Note: SITE_ROOT and environment flag are set in locator.php **/

/** define logging settings */
define("LOG_FILE", SITE_ROOT."/html/logs/mightyLog");
/** turn on automatic error logging */
ini_set('log_errors', true);
/** set log file */
date_default_timezone_set('America/Los_Angeles');
define("LOG_FILE_NAME_DATE", "Y-m-d");
ini_set('error_log', LOG_FILE.date(LOG_FILE_NAME_DATE, time()).".log");

/** define database, paypal, and error logging settings */
if (IS_LOCAL) {
	define("LOCATION_SITE", "http://mightyultimate.local/");
	define("SECURE_LOCATION_SITE", "http://mightyultimate.local/");
	define("DB_HOST", "localhost");
	define("DB_NAME", "");
	define("DB_USER", "");
	define("DB_PASS", "");
	define("PAYPAL_ENV", "");
	define("IPN_RETURN_URL",SECURE_LOCATION_SITE."paypal/IPNHandler.php");
	define("MU_PAYPAL_EMAIL","");
	ini_set('display_errors', false);
	ini_set('error_reporting', E_ALL);
} else if (IS_TEST) { 
	define("LOCATION_SITE", "http://test.mightyultimate.com/");
	define("SECURE_LOCATION_SITE", "https://test.mightyultimate.com/");
	//define("DB_HOST", $_ENV['DATABASE_SERVER']);
	/** need to explicitly get host name this way because $_ENV is only good for public web 
     *  pages which does no good when this page is called by cron for the rss feed processing
	 */
	define("DB_HOST", "");
	define("DB_NAME", "");
	define("DB_USER", "");
	define("DB_PASS", "");
	define("PAYPAL_ENV", "");
	define("IPN_RETURN_URL",SECURE_LOCATION_SITE."paypal/IPNHandler.php");
	define("MU_PAYPAL_EMAIL","");
	ini_set('display_errors', true);
	ini_set('error_reporting', E_ALL ^ E_NOTICE);
} else {
	define("LOCATION_SITE", "http://mightyultimate.com/");
	define("SECURE_LOCATION_SITE", "https://mightyultimate.com/");
	//define("DB_HOST", $_ENV['DATABASE_SERVER']);
	/** need to explicitly get host name this way because $_ENV is only good for public web 
     *  pages which does no good when this page is called by cron for the rss feed processing
	 */
	define("DB_HOST", "");
	define("DB_NAME", "");
	define("DB_USER", "");
	define("DB_PASS", "");
	define("PAYPAL_ENV", "");
	define("IPN_RETURN_URL",SECURE_LOCATION_SITE."paypal/IPNHandler.php");
	define("MU_PAYPAL_EMAIL","");
	ini_set('display_errors', false);
}

/** define event setup fee that MU charges to organizers */
define("EVENT_SETUP_FEE", 10.00);

/** define email settings */
define("EMAIL_INFO_ADDRESS", "info@mightyultimate.com");
define("EMAIL_SUPPORT_ADDRESS", "support@mightyultimate.com");
define("EMAIL_DIRECTOR_ADDRESS", "director@mightyultimate.com");
define("EMAIL_LEGAL_ADDRESS", "legal@mightyultimate.com");
define("EMAIL_WORK_ADDRESS", "");

/** define general settings */
define("ORG_NAME", "Mighty Ultimate Disc");
/** define timezone used to store sensitive datetime values */
define("GMT_TIMEZONE_ID", "Europe/London");
?>