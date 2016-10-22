<?php 
/**
 * @author Steve Shaw
 * @copyright 2009
 */
/** general includes */
$rss_site_root = dirname(__FILE__);
// @locally: SITE_ROOT = G:\DataLocal\mightyultimate 
// echo "siteroot: ".$rss_site_root."<br/>"; = /nfs/c04/h02/mnt/63137/data
$rss_is_local = false;
$rss_is_test = false;
$rss_is_prod = false;

if (strstr(strtoupper($rss_site_root), "DATALOCAL")) {
	$rss_is_local = true;
} else if (strstr(strtoupper($rss_site_root), "FEED_TEST")) { 
	$rss_is_test = true;
} else {
    $rss_is_prod = true;
}

if ($rss_is_local) {
    include_once('../../locator.php');
	include_once('../../includes/simplepie.inc');
	include_once('../../includes/includes.php');
} else if ($rss_is_test) {
    include_once('domains/test.mightyultimate.com/html/locator.php');
	include_once('data/includes_test/simplepie.inc');
	include_once('data/includes_test/includes.php');
} else {
    include_once('domains/mightyultimate.com/html/locator.php');
	include_once('data/includes_prod/simplepie.inc');
	include_once('data/includes_prod/includes.php');
}

//log_entry(Logger::RSS,Logger::ERROR,0,0,"SiteRoot: ".$rss_site_root);

$deleteTime = get_current_gmt_time();
$feed = new SimplePie();
$feed->set_feed_url("http://pickupultimate.com/rss/all_games");
$feed->init();

//echo "# of items: ".$feed->get_item_quantity()."<br/><br/>";

$ch = curl_init();

foreach($feed->get_items() as $item) {
	//echo $item->get_content()."<br/>";
	$lat = $item->get_latitude();
	$long = $item->get_longitude();
	$link = $item->get_link();
	$gameName = $item->get_title();
	//echo "lat: ".$lat."<br/>";
	//echo "long: ".$long."<br/>";
	//echo "link: ".$link."<br/>";
	//echo "gameName: ".$gameName."<br/>";
	/** check if game already exists in table */
	if (get_event_profile_location($lat,$long)) {
		/** if game exists, update its timestamp to indicate its freshness */
		if(!update_event_profile_time($lat,$long)) {
			log_entry(Logger::RSS,Logger::ERROR,0,0,"Update of RSS game timestamp failed>> lat:".$lat." long: ".$long);
		}
	} else {
		/** if game does not exist, then do look up to geoplugin for location meta data */
		//http://ws.geonames.org/extendedFindNearby?lat=29.5282470012&lng=-95.0704264641
		//http://www.geoplugin.net/extras/location.gp?lat=48.4440060548&long=-123.3322620392&format=php
		curl_setopt($ch,CURLOPT_URL,'http://www.geoplugin.net/extras/location.gp?lat='.$lat.'&long='.$long.'&format=php');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		$geoplugin = unserialize(curl_exec($ch));
		if(!$geoplugin) {
			log_entry(Logger::RSS,Logger::ERROR,0,0,"Geoplugin barfed: #".curl_errno($ch)." Msg: ".curl_error($ch));
		}

		$countryCd = $geoplugin['geoplugin_countryCode'];
		/** if game is in US, convert state name to state code */
		if ($countryCd == "US") {
			$stateProv = get_state_code($geoplugin['geoplugin_region']);
		} else {
			$stateProv = $geoplugin['geoplugin_region'];
		}
		$city = $geoplugin['geoplugin_place'];   
		if(!insert_event_profile_pickup($lat,$long,$countryCd,$stateProv,$city,$gameName,$link)) {
			log_entry(Logger::RSS,Logger::ERROR,0,0,"Insert of RSS game data failed>> lat:".$lat." long: ".$long);
		}
	}
}
curl_close($ch);

/** delete pickup games that were not updated during this run */
if(!delete_event_pickup($deleteTime)) {
	log_entry(Logger::RSS,Logger::ERROR,0,0,"Deletion of old game data failed.");
} else {
	log_entry(Logger::RSS,Logger::INFO,0,0,"Processing of pickup RSS feed was successful.");
}
?>