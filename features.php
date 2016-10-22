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

clear_selected_event();

display_wrappers();
build_page();
display_footer_wrapper();

function build_page() {
?>
	<div id="content_wrapper">
		<p>
Mighty Ultimate Disc is a website by ultimate players for ultimate players.  The site allows players to view ultimate frisbee hat tournaments, leagues, and pickup games in the U.S. and, soon, Canada.  For those who want to play in a hat tournament or league, the site provides a quick and easy signup process.  The site also offers enterprising players the ability to create and manage their own hat tournaments and leagues.  Access to available features is determined by the individual's role in a particular event.  An individual can be a player, a captain and/or an organizer.  Here's a breakdown of the features you can expect to see depending on your role.
		<br/><br/>
		<span class="bigGD1">What Players can do:</span>
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<ul>
				<li>View leagues, hat tournaments, and pickup games across the U.S. and, soon, Canada.</li>
				<li>Create and maintain a player profile that includes a self assessment of their playing skills.  This is used by event organizers to help them create balanced league and hat tournament teams.</li> 
				<li>Sign up for an ultimate league or hat tournament event in their own backyard or across the country through a quick and easy process, 24/7.</li>
				<li>Add their name to a wait list if the league or hat tournament roster is full.</li>
				<li>View real time position on the event wait list as other players are added or removed.</li>
				<li>Verify UPA status, sign up with UPA, or pay a one time UPA event fee for UPA sanctioned hat tournaments and leagues.</li>
				<li>Pay all league or hat tournament fees online through Paypal if accepted as a form of payment by the event organizer.</li>
				<li>View players and captains assigned to each team in the league or hat tournament.</li>
				<li>Update their account settings including player ID, password and email address.</li>
			</ul>
			<br/>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
		<p>
		<span class="bigGD1">What Captains can do:</span> ...everything a player can do, plus...
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<ul>
				<li>View and download a Draft Report of players to be assigned to teams.</li>
				<li>View and download a UPA Forms Report of recommended forms required for UPA sanctioned events in order to ease the process of collecting UPA forms from players.</li>
				<li>Send text based email to players on their team.</li>
			</ul>
			<br/>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
		<p>		
		<span class="bigGD1">What Organizers can do:</span> ...everything a player and a captain can do, plus...
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<ul> 
				<li>Set up and manage hat tournaments and leagues.</li>
				<li>Set registration and play window dates, gender limits, size of total player pool, and number of teams.</li>
				<li>Set up event fees, which can include the purchase of discs and/or t-shirts.</li>
				<li>Accept up to three forms of payment from registered players: cash, check or Paypal.</li>
				<li>If you have a Paypal account, receive online payments via Paypal from registered players.</li>
				<li>View automatic update of player payment status after player successfully pays event organizer via Paypal.</li>
				<li>Manually update player payment status and type for those players who pay via cash or check.</li>
				<li>Manually edit fees owed by registered player, if required. For instance, if a player volunteers to act as team captain, then you may want them to participate in your event at no cost.</li>
				<li>Specify up to two other registered players with organizer status to help share the work of running your event.</li>
				<li>Create teams and assign registered players based on skills and experience.</li>
				<li>Designate any number of registered players as team captains.</li>
				<li>Manage a wait list by adding wait listed players to event or team roster.</li>
				<li>Create an event home page so that you can provide any additional information not captured by the profile.</li>
				<li>Designate your event as UPA sanctioned, if applicable.</li>
				<li>For UPA sponsored events, use the UPA Submission Report to submit your event's roster to the UPA to meet their sanctioning requirements. This report is available online and also as a downloadable Excel spreadsheet.</li>
				<li>View and download a Roster Report of all registered players including: payment status, payment type, upa status, t-shirt size, # of discs ordered and fee breakdown.</li>
				<li>View a Summary Report that lists: the total number of registered and wait listed players, the breakdown of total fees owed and fees marked as paid, and a breakdown of t-shirt and disc orders.</li>
				<li>Send special announcements or news to all event participants via an email.</li>
				<li>Receive all of these features for a low, affordable event setup fee.</li>
			</ul>
			<br/>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
	</div>
<?php
}
?>