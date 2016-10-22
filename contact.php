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

$playerID = get_session_player_id();
$emailAddr = "";
$enteredData = false;
$actionResult = "";
$errors = array();

if (isset($playerID)) {
	$account = get_player_account($playerID);
	$emailAddr = $account['Email'];
} else {
	$playerID = 0;
}

$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
$processAction = cleanAction($thisProcessAction);
switch ($processAction) {
    case "Send Email":  /** action from email page */
		$enteredData = get_data_entered($_POST);
		$action = "contactEmail";
		$errors = validate($action, $enteredData);
		
		if (empty($errors)){
			$emailFromClean = strip_tags($enteredData['From']);
			$subjectClean = strip_tags($enteredData['Subject']);
			$messageClean = strip_tags($enteredData['Message']);
			$sendCopy = strip_tags($enteredData['Send_Copy']);
			if (!sendEmailGeneral($emailFromClean,$sendCopy,$subjectClean,$messageClean)) {
				$emailAddr = $enteredData['From'];
				log_entry(Logger::GEN,Logger::WARN,0,$playerID,
						"An error occurred while sending email to MU.");
				$errors = error_add($errors, "An error occurred while sending your email to Mighty Ultimate Disc.");
				build_page($errors,$emailAddr,$enteredData,$actionResult);
			} else {
				$actionResult = "success";
				build_page($errors,$emailAddr,$enteredData,$actionResult);
			}
		} else {
			$emailAddr = $enteredData['From'];
			build_page($errors,$emailAddr,$enteredData,$actionResult);
		}
        break;
    case "Cancel": 
        redirect_page("index.php");
        break;
    default:
    	build_page($errors,$emailAddr,$enteredData,$actionResult);
}

$enteredData = false;

function build_page($errors,$emailAddr,$enteredData,$actionResult) {
	display_wrappers();
?>
	<div id="content_wrapper">
	<?php
	if ($actionResult == "") {
	?>
		<p>
		If you'd like to report a problem with the website, or would like to contribute an idea on how to make 
		the site better, or ask any old general question, drop us a line -	we'd like to hear from you...
		</p>
		<br/>
		<?php
		display_errors($errors);
		?>
		<div id="xsnazzy">
		<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
		<div class="xboxcontent">
			<form method="post" name="selectionForm" action="contact.php" class="box">
				<table class="default">
					<tr>
						<td></td>
						<td>
							<span class="smGD">* required entry</span>
						</td>
					</tr>
					<tr>
						<td class="titleRL">Your Email Address<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="text" 
								name="From"
								value="<?php echo $emailAddr ?>" 
								size="30" 
								maxlength="40" 
								tabindex="3"> 
						</td>
					</tr>
					<?php 
					if (isset($errors['From'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['From'] ?></td></tr>				
					<?php			
					}
					?>
					<tr>
						<td class="titleRL">Subject<span class="req">*</span></td>
						<td class="entryRL">
							<input 	type="text" 
								name="Subject"
								value="<?php echo (isset($enteredData['Subject'])) ? $enteredData['Subject'] : ""; ?>" 
								size="30" 
								maxlength="40" 
								tabindex="4"> 
						</td>
					</tr>
					<?php 
					if (isset($errors['Subject'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Subject'] ?></td></tr>				
					<?php			
					}
					?>
					<tr>
						<td class="titleRL">Send Copy to Your Email?</td>
						<td class="entryRL">
							<input type="checkbox" 
									name="Send_Copy"
									value="Y" 
									tabindex="5"
									>Yes
						</td>
					</tr>
					<tr>
						<td class="titleRL" colspan="2">
							Message<span class="req">*</span>
						</td>
					</tr>
					<tr>
						<td class="entryRL" colspan="2">
							 <textarea name="Message" rows="10" cols="65" tabindex="6"><?php echo (isset($enteredData['Message'])) ? $enteredData['Message'] : ""; ?></textarea>
						</td>
					</tr>
					<?php 
					if (isset($errors['Message'])) {
					?>
						<tr><td></td><td class="error"><?php echo $errors['Message'] ?></td></tr>				
					<?php			
					}
					?>
					<?php	
					$buttonLabel1 = "Send Email";
					$buttonLabel2 = "Cancel";
					?>
					<tr>
						<td class="dispRC" colspan="2">
							<button type="submit" value="<?php echo $buttonLabel1 ?>" class="submitBtn" 
								name="ProcessAction">
								<span><?php echo $buttonLabel1 ?></span>
							</button>
							&nbsp;
							<button type="submit" value="<?php echo $buttonLabel2 ?>" class="submitBtn" 
								name="ProcessAction">
								<span><?php echo $buttonLabel2 ?></span>
							</button>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
		</div>
	<?php
	} else {
	?>
	Super!  Thanks for the email.  We'll reply to you as soon as we can.
	<br/><br/>
	- Mighty Ultimate Disc Support	
	<?php
	}
	?>		
	</div>
<?php
}

display_footer_wrapper();
?>