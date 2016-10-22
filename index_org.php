<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
/** general includes */
include_once('locator.php');
if (IS_LOCAL) {
    include_once('includes/includes.php');
} else if (IS_TEST) {
    include_once('../../../data/includes_test/includes.php');
} else {
    include_once('../../../data/includes_prod/includes.php');
}

/** initialize variables */
$errorsSearch = array();

build_index_initial_page($errorsSearch);

function build_index_initial_page($errorsSearch) {
	display_wrappers($errorsSearch);
	?>
	<div id="content_wrapper">
		<p>
		<span class="bigGD1">If you want to organize your own ultimate league or hat tournament...</span><br/>
At a minimum, you need to be a Member.  After logging in as a Member, you create an Event Profile from the main menu.  
The information in the profile is used to run the online registration process for your event. For example, we'll need:
		<ul>
			<li>event location</li>
			<li>registration start and end time</li>
			<li>start and end time of event play</li>
			<li>number of men/women players</li>
			<li>cost of event t-shirts and discs, if applicable</li>
			<li>forms of payment to be accepted by you: cash, check or Paypal</li>
			<li>whether the event is UPA sponsored or not</li>
		</ul>
		</p>
		<p>
After creating an Event Profile, you'll need to publish your event so that any visitor to Mighty Ultimate Disc will be able to 
view and sign up for your event once the scheduled sign up start time occurs. 
		<br/><br/>
Mighty Ultimate Disc provides a number of online tools and features to help you run your event efficiently.  You can: 
		<ul>
			<li>create an event home page so that you can provide any additional information not captured by the profile.  The home page will be displayed to anyone who clicks your event's link from the search results page.  So cool!!</li>
			<li>create teams and easily assign players and captains from the roster of registered players.</li>
			<li>utilize a Draft Report which includes player skill levels and experience to create balanced teams. This report is available online and also as a downloadable Excel spreadsheet.</li>
			<li>use your Paypal account to have players pay you online.  Once a player pays you through Paypal, the event roster will automatically be updated so that you know who has paid and who hasn't.</li>
			<li>manage a wait list if more players than anticipated sign up for your event.</li>
			<li>send special announcements or news to all event participants via an email.</li>
			<li>for UPA sponsored events, refer to a UPA Forms Report to see a list of recommended forms to be signed by and collected from players. This report is available online and also as a downloadable Excel spreadsheet.</li>
			<li>for UPA sponsored events, use the UPA Submission Report to submit your event's roster to the UPA to meet their sanctioning requirements. This report is available online and also as a downloadable Excel spreadsheet.</li>
			<li>keep track of the overall number of players who have registered or wait listed for your event and who has or hasn't paid you with the Summary Report.  Additionally, if applicable, this report shows the total number of discs ordered and the number and sizes of t-shirts ordered. This report is available online only.</li>
		</ul>
		</p>
		<p>
See our <a href="<?php LOCATION_SITE ?>features.php">features</a> page for full details...
		<br/><br/>
So, what would you pay for all of this? $100? $50? $19.99? How does FREE sound? You kidding me? Nope... There are no setup fees, 
no hidden fees, no per player fees, nuthin... nada.. though donations are accepted with a smile!    
		</p>
	</div>
<?php
}

display_footer_wrapper();
?>