<?php

/**
 * @author Steve Shaw
 * @copyright 2010
 * 
 * This page determines what environment you are in.  It must be on every public display page and 
 * must be the first thing included on the page. 
 */

/** SITE_ROOT contains the full path to the mightyultimate folder */
define("SITE_ROOT", dirname(__FILE__));
// @locally: SITE_ROOT = G:\DataLocal\mightyultimate 
// echo "siteroot: ".SITE_ROOT."<br/>"; //= /nfs/c04/h02/mnt/63137/domains/mightyultimate.com/html
// echo "database server: ".$_ENV['DATABASE_SERVER']."<br/>"; = internal-db.s63137.gridserver.com

/** define database, paypal, and error logging settings */
if (strstr(strtoupper(SITE_ROOT), "DATALOCAL")) {
	define("IS_LOCAL", true);
	define("IS_TEST", false);
	define("IS_PROD", false);
} else if (strstr(strtoupper(SITE_ROOT), "TEST")) { 
    define("IS_LOCAL", false);
    define("IS_TEST", true);
	define("IS_PROD", false);
} else {
	define("IS_LOCAL", false);
    define("IS_TEST", false);
	define("IS_PROD", true);
}

?>