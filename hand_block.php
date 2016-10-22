<?php
/**
 * @author Steve Shaw
 * @copyright 2008
 */
/** general includes */
//include_once('includes/includes.php');

//clear_selected_event();

//display_header_wrapper();
build_page();
//display_footer_wrapper();

function build_page() {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="keywords" content="ultimate frisbee, frisbee, registration, frisbee league,	ultimate league, hat tournament">		
		<title><?php echo ORG_NAME ?></title>
		<script language="JavaScript" type="text/javascript" src="scripts/page_functions.js"></script>
		<script language="JavaScript" type="text/javascript" src="scripts/jquery/jquery-1.3.2.min.js"></script>
		<script language="JavaScript" type="text/javascript" src="scripts/cssButtonIE.js"></script>
		
		<?php
		$curPage = "hand_block";
		if (strstr($curPage, "event_mgmt")) {
		?>
			<!-- for event date/time helper: mySQL format yyyy-mm-dd -->
			<script language="JavaScript" type="text/javascript" src="scripts/datetimepicker_css.js"></script>
			<!-- for owner assignment -->
			<script language="JavaScript" type="text/javascript" src="scripts/mkSelectBox.js"></script>
			<!-- for deleting stuff -->
			<link href="styles/lert.css" rel="stylesheet" type="text/css">
			<script language="JavaScript" type="text/javascript" src="scripts/lert.js"></script>			
		<?php
		}else if (strstr($curPage, "event_home_page")) {
		?>
			<!-- for RTE -->
			<script language="JavaScript" type="text/javascript" src="scripts/cbrte/html2xhtml.js"></script>
			<script language="JavaScript" type="text/javascript" src="scripts/cbrte/richtext_compressed.js"></script>
		<?php
		}else if (strstr($curPage, "team_mgmt")) {
		?>
			<!-- for player assignment -->
			<script language="JavaScript" type="text/javascript" src="scripts/mkSelectBox.js"></script>
			<!-- for deleting stuff -->
			<link href="styles/lert.css" rel="stylesheet" type="text/css">
			<script language="JavaScript" type="text/javascript" src="scripts/lert.js"></script>
		<?php
		}
		?>		
		<link rel="stylesheet" type="text/css" href="styles/layout.css">
		<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
	</head>
	<?php
	$dispOnload = "";
	/** set field focus depending on page	
	if (strstr($curPage, "index")) { 
		$dispOnload = " onload=\"setFocus('loginForm','Short_Name');\"";
	} else if (strstr($curPage, "player_profile")) { 
		$dispOnload = " onload=\"setFocus('selectionForm','First_Name');\"";
	}
	 */
	?>
	<body<?php echo $dispOnload;?>>
		<div id="page_wrapper">
			<div id="header_wrapper">
				<div id="header_inner_wrapper">
					<div id="header_image">
						<a href="index.php" onMouseOver="window.status=''; return true">
							<img src="images/headerMU.jpg" width="421" height="74" border="0">
						</a>
					</div>
				</div>
			</div>
			<div id="navbar_wrapper">
				<div id="navbar_menu_wrapper">
					<ul id="navbar_list">
						<li>
							<a href="index.php" onMouseOver="window.status=''; return true" 
							<?php
							if ((strstr($curPage, "index") or !strstr($curPage, "php")) and $homeDisp)  {
								echo "class=\"current\"";
							}
							?>
							><span>Home</span></a>
						</li>
						<li>
							<a href="features.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "features")) {
								echo "class=\"current\"";
							}
							?>
							><span>Features</span></a>
						</li>
						<li>
							<a href="faq.php" onMouseOver="window.status=''; return true" 
							<?php
							if (strstr($curPage, "faq")) {
								echo "class=\"current\"";
							}
							?>
							><span>FAQ</span></a>
						</li>
					</ul>
				</div>
			</div>
			<div id="header_gapper"></div>
	<div id="content_wrapper">
		<h3>Yikes... Hand Block!</h3>
		<p>
		Sorry for the inconvenience, but a major malfunction has occurred to MightyUltimate.  The administrator has been notified.<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
		</p>
	</div>
				<div id="footer_gapper">
				</div>
				<div id="footer_wrapper">
					<a href="about.php">About</a>&nbsp;&nbsp;|&nbsp;&nbsp;
					<a href="terms_of_use.php">Terms of Use</a>&nbsp;&nbsp;|&nbsp;&nbsp;
					<a href="privacy.php">Privacy Policy</a>&nbsp;&nbsp;|&nbsp;&nbsp;
					<a href="contact.php">Contact</a>
					<br/>
					<span class="smLB">Copyright &copy; 2009 Mighty Ultimate Disc. All Rights Reserved.</span>
				</div>
			</div>	
		</body>
	</html>
<?php
}
?>