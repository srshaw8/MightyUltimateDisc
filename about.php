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
For too long, it's been difficult for players to easily signup for ultimate hat tournaments and leagues. And forget about trying to run one - it's just plain hard to properly manage these events without any automated support. How many times have you been to a hat tournament where the organizer is spending precious time feverishly hacking together teams that are hopefully *balanced*? The lack of common, effective tools can make running a hat tournament or league a haphazard affair for even the most diligent event organizers.
<br/><br/>
To date, regional bodies have stepped in to address this problem by either contracting for outside programming help or sourcing it for free from dedicated volunteers. Contracting can be an expensive proposition and requires a large player population to subsidize the cost. Volunteer work is a less expensive route to go, but requires having access to a number of folks who have the skills and, more importantly, the time to make it all happen. 
<br/><br/>
Here's where Mighty Ultimate Disc comes in... First, we equip event organizers with the necessary online tools to create and run their own hat tournament or league - a leveling of the playing field, so to speak. Second, we provide the ability for anybody to sign up for an ultimate frisbee hat tournament or league hosted in the United States.  We think we hit the mark in making the process simple and effective, yet powerful and valuable. Because let's face it, we'd all much rather spend our time doing the thing that's most important:<br/><span class="bigGD1">Play Ultimate!</span>
		</p>
	</div>
<?php
}
?>