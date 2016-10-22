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
/** page specific includes */

/** initialize variables */
$errorsSearch = array();

build_index_initial_page($errorsSearch);

function build_index_initial_page($errorsSearch) {
	display_wrappers($errorsSearch);
	?>
	<div id="content_wrapper">
		<p>
		<span class="bigGD1">If you're a player who wants to play in a game...</span><br/>
		Signing up to play in a ultimate frisbee league or hat tournament hosted by Mighty Ultimate Disc is easy as pie:
		<ol class="nbr">
			<li>First, you need to be a Member.  Click on the New Member button to become one today.</li>
			<li>Next, set up your Player Profile or, if you already have one, make sure that it's up to date. The purpose 
                of the profile is to allow event organizers to assess your skill level so that they can create balanced teams 
                for the event that you sign up for.</li>
			<li>Search through our listings and find a league or hat tournament that you want to sign up for by using the 
                "Find A Game" search tool.</li>
			<li>When you find a game that you like and if sign ups are still open, click on the "Sign Up" button.  You'll be 
                prompted to review your Player Profile and then asked several event specific questions. When you click the 
                "Register" button, you'll immediately see if you were added to the event roster. If the event organizer has 
                specified Paypal as a payment option, then players who have registered for the event can easily pay the event 
                organizer through Paypal. Otherwise, you pay your event fees when you show up at the first game. If you weren't 
                able to get on the active event roster, then you can choose to add yourself to the event's wait list. If room 
                becomes available on the roster, the event organizer will contact you to see if you still want to play. If so, 
                then they will add you to the roster.</li>
		</ol>
		</p>
		<p>
		For a complete list of what features are available to you as a player, check out our <a href="<?php LOCATION_SITE ?>features.php">features</a> page...
		</p>
	</div>
<?php
}

display_footer_wrapper();
?>