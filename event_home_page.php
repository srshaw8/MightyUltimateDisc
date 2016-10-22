<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
/** general includes */
include_once('locator.php');
if (IS_LOCAL) {
    include_once('includes/includes.php');
    //require_once("includes/htmlpurifier/library/HTMLPurifier.auto.php");
    require_once("includes/htmlpurifier/HTMLPurifier.standalone.php");
} else if (IS_TEST) {
    include_once('../../../data/includes_test/includes.php');
    //require_once("includes/htmlpurifier/library/HTMLPurifier.auto.php");
    require_once("../../../data/includes_test/htmlpurifier/HTMLPurifier.standalone.php");
} else {
    include_once('../../../data/includes_prod/includes.php');
    //require_once("includes/htmlpurifier/library/HTMLPurifier.auto.php");
    require_once("../../../data/includes_prod/htmlpurifier/HTMLPurifier.standalone.php");
}

if (check_authorization()) {
	/** variable declarations */
	$action = "";
	$processAction = "";
	$enteredData = array();
	$errors = array();
	$enteredData = array();
	$playerID = get_session_player_id();
	$eventID = get_session_event_mgmt();
	$eventName = get_session_event_name(); 
	$eventRole = get_session_player_role();

	$thisProcessAction = isset($_POST['ProcessAction']) ? $_POST['ProcessAction'] : "";
	$processAction = cleanAction($thisProcessAction);
	if ($processAction == "Edit") {  /** action from detail page */
		$action = "eventEdit";
	} else if ($processAction == "Save Home Page") {  /** action from detail page */
		$action = "eventSave";
	} else {	
		$action = "eventView";  /** default action */
	}

	if ($eventID <> "" and is_numeric($eventID)) {
		if (check_owner_authorization() or check_admin_authorization()) {
			if ($action == "eventView" or $action == "eventEdit") {
				$enteredData = get_event_home_page($eventID);
				build_event_home_page_detail_page($errors, $enteredData, $action);
			} else if ($action == "eventSave") {
				$enteredData = get_data_entered($_POST);
				$errors = validate($action, $enteredData);
				if (empty($errors)){
					/** clean the html */
					$config = HTMLPurifier_Config::createDefault();
					$config->set('HTML','AllowedElements', array('a','b','br','div','hr','i','li','p','ol','span','ul'));
					$config->set('HTML','AllowedAttributes', array('a.href','a.target','div.style','span.style'));	
					$config->set('Attr','AllowedFrameTargets','_blank,_self');
					$purifier = new HTMLPurifier($config);
					$clean_html = $purifier->purify($enteredData['Home_Page_Text']);
					$enteredData['Home_Page_Text'] = $clean_html;
			    			
					/** check if event home page already exists to see if you need to insert or update */
					if (!get_event_home_page($eventID)) {
						if (!insert_event_home_page($eventID, $enteredData)) {
							log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
								"Failed to insert event home page.");
							$errors = error_add($errors, "An error occurred while creating the event's home page.");
							build_event_home_page_detail_page($errors, $enteredData, $action);
						} else {
							$action = "eventView";
							$enteredData = get_event_home_page($eventID);
							build_event_home_page_detail_page($errors, $enteredData, $action);
						}
					} else {
						if (!update_event_home_page($eventID, $enteredData)) {
							log_entry(Logger::EVENTP,Logger::ERROR,$eventID,$playerID,
								"Failed to update event home page.");
							$errors = error_add($errors, "An error occurred while saving your event's home page.");
							build_event_home_page_detail_page($errors, $enteredData, $action);
						} else {
							$action = "eventView";
							$enteredData = get_event_home_page($eventID);
							build_event_home_page_detail_page($errors, $enteredData, $action);				
						}
					}
				} else {
					$enteredData = get_event_home_page($eventID);
					build_event_home_page_detail_page($errors, $enteredData, $action);
				}
			}
		} else {
			log_entry(Logger::EVENTP,Logger::WARN,$eventID,$playerID,
				"Non-authorized player tried to peek at event home page.");
			$errors = error_add($errors, "Sorry, your access to this page is not authorized.");
			build_event_home_page_detail_page($errors, $enteredData, $action);
		}
	} else {
		clear_selected_event();
		redirect_page("index.php");
	}	
} else {
	display_non_authorization();
}

function build_event_home_page_detail_page($errors, $enteredData, $action) {
	display_wrappers();
?>
	<div id="content_wrapper">
		<?php
		build_event_navbar("all");
		?>
		<div id="event_wrapper">
		<br/>
		<?php
		$isOwner = check_owner_authorization();
		$isAdmin = check_admin_authorization();
		if ($isOwner or $isAdmin) {		
			display_errors($errors);
			if ($action == "eventEdit" or $action == "eventSave") {
				editForm($errors, $enteredData, $action);
			} else {
				displayForm($enteredData, $action);
			}
		} else {
			display_errors($errors);
		}
		?>
		</div>
	</div>
<?php
}
	
function editForm($errors, $enteredData, $action) {
?>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
		<form method="post" name="selectionForm" action="event_home_page.php" onsubmit="return submitForm();" 
			class="box">
			<table class="default">
				<tr>
					<td class="titleRL">Publish Home Page?</td>
					<td class="entryRL">
						<input type="radio" 
								name="Publish_Home_Page"
								value="Y" 
								<?php if (strstr($enteredData['Publish_Home_Page'],"Y")) { 
									print "checked"; } ?>>Yes
						<input type="radio" 
								name="Publish_Home_Page"
								value="N" 
								<?php if (strstr($enteredData['Publish_Home_Page'],"N") or 
										!check_value_is_set($enteredData['Publish_Home_Page'])) { 
									print "checked"; } ?>>No
					</td>
				</tr>
				<tr>
					<td colspan="2" class="titleRL"><?php echo get_session_event_name(); ?> Home Page</td>
				</tr>
				<tr>
					<td colspan="2">
						<script language="JavaScript" type="text/javascript">
							<!--
							function submitForm() {
								//make sure hidden and iframe values are in sync for all rtes before submitting form
								updateRTEs();
								return true;
							}
							
							//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML, encHTML)
							initRTE("scripts/cbrte/images/", "scripts/cbrte/", "", true);
							//-->
						</script>
						<noscript><p><b>Javascript must be enabled to use this form.</b></p></noscript>
						<script language="JavaScript" type="text/javascript">
							<!--
							//build new richTextEditor
							var Home_Page_Text = new richTextEditor('Home_Page_Text');
							<?php
							if (check_value_is_set($enteredData['Home_Page_Text'])) {
								/** format content for preloading */
								$content = rteSafe($enteredData['Home_Page_Text']);
							} else {
								/** default text would go here if you necessary... */ 
								$content = "";
								$content = rteSafe($content);
							}
							?>
							Home_Page_Text.html = '<?php echo $content;?>';
							//Home_Page_Text.toggleSrc = false;
							Home_Page_Text.width = 580;
							Home_Page_Text.height = 400;
							Home_Page_Text.toolbar1 = false;
							Home_Page_Text.cmdForeColor = false;
							Home_Page_Text.cmdHiliteColor = false;
							Home_Page_Text.cmdInsertImage = false;
							Home_Page_Text.cmdInsertSpecialChars = false;
							Home_Page_Text.cmdInsertTable = false;
							Home_Page_Text.cmdSpellcheck = true;
							Home_Page_Text.cmdUndo = true;
							Home_Page_Text.build();
							//-->
						</script>
					</td>
				</tr>
				<?php 
				if (isset($errors['Home_Page_Text'])) {
				?>
					<tr><td colspan="2" class="error"><?php echo $errors['Home_Page_Text'] ?></td></tr>
				<?php
				}
				?>
				<?php
				$buttonLabel1 = "Save Home Page";
				$buttonLabel2 = "Cancel";
				?>
				<tr>
					<td colspan="2" class="dispRC">
						<button type="submit" value="<?php echo $buttonLabel1 ?>" class="submitBtn" 
							name="ProcessAction">
							<span><?php echo $buttonLabel1 ?></span>
						</button>
						&nbsp;&nbsp;
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
}  

function displayForm($enteredData, $action) {
?>
	<div id="xsnazzy">
	<b class="xtop"><b class="xb1"></b><b class="xb2"></b><b class="xb3"></b><b class="xb4"></b></b>
	<div class="xboxcontent">
		<form method="post" name="selectionForm" action="event_home_page.php" class="box">
			<table class="default">
				<tr>
					<td class="titleRL">Publish Home Page?</td>
					<td class="entryRL">
					<?php
					if (strstr($enteredData['Publish_Home_Page'],"Y")) {
						echo "Yes";
					} else {
						echo "No";
					} 
					?>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="titleRL"><?php echo get_session_event_name(); ?> Home Page</td>
				</tr>
				<tr>
					<td colspan="2" class="entrySL">
					<?php 
					if (check_value_is_set($enteredData['Home_Page_Text'])) {
						echo stripslashes($enteredData['Home_Page_Text']);
					} else {
						echo "No text has been entered for your home page.";
					}
					?>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="dispRC">
						<button type="submit" value="Edit" class="submitBtn" 
							name="ProcessAction">
							<span>Edit</span>
						</button>
					</td>
				</tr>
			</table>
		</form>	
	</div>
	<b class="xbottom"><b class="xb4"></b><b class="xb3"></b><b class="xb2"></b><b class="xb1"></b></b>
	</div>
<?php
}

function rteSafe($strText) {
	//returns safe code for preloading in the RTE
	$tmpString = $strText;
	
	//convert all types of single quotes
	$tmpString = str_replace(chr(145), chr(39), $tmpString);
	$tmpString = str_replace(chr(146), chr(39), $tmpString);
	$tmpString = str_replace("'", "&#39;", $tmpString);
	
	//convert all types of double quotes
	$tmpString = str_replace(chr(147), chr(34), $tmpString);
	$tmpString = str_replace(chr(148), chr(34), $tmpString);
	//	$tmpString = str_replace("\"", "\"", $tmpString);
	
	//replace carriage returns & line feeds
	$tmpString = str_replace(chr(10), " ", $tmpString);
	$tmpString = str_replace(chr(13), " ", $tmpString);
	
	return $tmpString;
}

display_footer_wrapper();
?>