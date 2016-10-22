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
		If you can't find the answer that you're looking for here, 
		<a href="<?php LOCATION_SITE ?>contact.php">send</a> an email - we'd be happy to answer any 
		question...
		<br/><br/>
		<span class="bigGD1">Registration:</span><br/>
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<a href="#Reg1"><span class="bigGD2">
			Q: Are organizers automatically registered in the event that they create?
			</span></a>
			<br/>
			<a href="#Reg2"><span class="bigGD2">
			Q: As a player, who do I pay when I register for a hat tournament or league?
			</span></a>
			<br/>
			<a href="#Reg3"><span class="bigGD2">
			Q: How will I know that I've been taken off the wait list and added to an event roster or team?
			</span></a>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
		<p>
		<span class="bigGD1">Event Management:</span><br/>
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<a href="#Event1"><span class="bigGD2">Q: I don't want my captains to have to pay the event fees since they're helping out - can I give them a break on paying?</span></a>
			<br/>
			<a href="#Event2"><span class="bigGD2">Q: I sent an email out to my team using the Mighty Ultimate Disc email option, but not everybody got it - what gives?</span></a>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
		<p>
		<span class="bigGD1">Privacy:</span><br/>
		</p>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<a href="#Priv1"><span class="bigGD2">Q: Who can see my player profile information and email address?</span></a>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
		<p>
			<br/>
			<a name="Reg1"><span class="bigGD2">Q: Are organizers automatically registered in the event that they create?</span></a>
			<br/> 
			A: No, they need to go through the registration process just like any other player.  This also applies to players who will captain a team - these players first need to register for the event before being designated as a captain.  For the more popular tournaments or leagues that fill up fast, the organizer or captains may not be able to register before the gender limits are reached.  If, as an organizer, you think this may be the case, then you can get around this problem before the event's sign ups start by reducing the gender limits from their maximum to that minus the number of organizers and captains that absolutely need to register.  That way, if an organizer or captain ends up on the wait list, the organizer can remove those individuals from the wait list first to the active roster or a team.
			<br/><br/>
			<a name="Reg2"><span class="bigGD2">Q: As a player, who do I pay when I register for a hat tournament or league?</span></a>
			<br/> 
			A: Registered players pay the organizer of the event only.  In fact, for any problem that you experience with the event, you should contact the event organizer first.  However, if there is a website specific problem, then the event organizer should contact Mighty Ultimate Disc support. 
			<br/><br/>
			<a name="Reg3"><span class="bigGD2">Q: How will I know that I've been taken off the wait list and added to an event roster or team?</span></a>
			<br/>
			A: If you've added yourself to the wait list, then, ideally, the organizer should contact you via email or your home phone number to verify your interest to play.  If you agree to play, then the organizer will remove you from the wait list and add you to the active roster or a team.  Upon doing so, you will receive an automated email from Mighty Ultimate Disc stating your registered status.
			<br/><br/>
			<a name="Event1"><span class="bigGD2">Q: I don't want my captains to have to pay the event fees since they're helping out - can I give them a break on paying?</span></a>
			<br/>
			A: You sure can...  If you don't want certain players to pay event fees, then have them simply register for the event without paying.  From the report roster page, you can mark their entry as paid and their fees owed are zero.  
			<br/><br/>
			<a name="Event2"><span class="bigGD2">Q: I sent an email out to my team using the Mighty Ultimate Disc email option, but not everybody got it - what gives?</span></a>
			<br/>
			A: There are three possible causes for this problem.  First, the player may have indicated in their account setting that they didn't want to receive an email from the event's organizer or captain.  Second, the player's email account may have treated the email from Mighty Ultimate Disc as spam.  In this case, you could ask them to check their spam filter or folder. Finally, though a remote possibility, an error could have occurred while sending the email from the Mighty Ultimate Disc server.  If the first two cases are not true, then please contact Mighty Ultimate Disc support and we'll look into the problem.
			<br/><br/>
			<a name="Priv1"><span class="bigGD2">Q: Who can see my player profile information and email address?</span></a>
			<br/>
			A: Depends.... For any one event, individuals can assume one of three roles with varying degrees of authority. These roles are organizer, captain and player.  Depending on the role, the individual can view varying levels of personal information associated with other players who have registered for a particular event.
			<br/><br/>
			An individual who creates and manages an ultimate frisbee event is considered to be an organizer. In case an organizer needs to contact a player who has registered for their event, they are allowed to view the player's phone and emergency contact information.  Organizers can also view a player's ultimate skill self rating in order to conduct a draft of players onto teams. Additionally, organizers can view the email address and the home and cell phone numbers of any player on the wait list so as to facilitate the placement process of moving a player from the wait list to the active roster. Finally, for UPA sanctioned events, organizers will be able to view any player's home address, home phone number and email address as part of a report submitted to the UPA for sanctioning purposes.  Organizers also have the ability to send an email to all players on the event roster or to any team.  In both cases, only those players who have indicated through their account settings that it's acceptable to receive email from a captain or organizer will receive an email. For team emails, the email addresses of participating team members plus the organizer's email address will be visible to all in the email address line. For roster emails, seperate emails are sent to each player.  Therefore, no one player can see another player's email address.
			<br/><br/>
			Captains are designated by the organizer once they've registered for the organizer's event. Captains can view ultimate skill related profile data for drafting purposes and a player's home phone number in case they need to contact them. Captains can also send an email message to those team members who have indicated through their account settings that it's acceptable for the captain to send emails to them. By virtue of sending the email, team member email addresses plus the captain's will be visible to all in the email address line.
			<br/><br/>
			Players are individuals who have not been designated as a captain or organizer for a particular event.  Players can not view personal or profile information of other players. They can, however, see other players' email address if 1) a captain or organizer sends a team email message and 2) the other players for a team have indicated through their account settings that it's ok to receive emails from the captain or organizer.
		</p>
	</div>
<?php
}
?>