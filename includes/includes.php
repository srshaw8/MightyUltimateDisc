<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
function include_relative($file) {
	$bt = debug_backtrace();
	$old = getcwd();
	//echo "directory location of inputted file: ".dirname($bt[0]['file']);
	chdir(dirname($bt[0]['file']));
	include($file);
	chdir($old);
}  

/** this include needs to be first */
include_relative("config_params.php");
/** other includes */
include_relative("utility_general_functions.php");
include_relative("log_functions.php");
include_relative("email_functions.php");
include_relative("db_functions.php");
include_relative("db_session_function.php");
include_relative("security_functions.php");
include_relative("utility_event_functions.php");
include_relative("error_functions.php");
include_relative("page_general_display.php");
include_relative("validate_data.php");
?>