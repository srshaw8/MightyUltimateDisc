<?php
/**
 * @author Steven Shaw
 * @copyright 2008
 */

function build_checkout_paypal($eventInfo) {
?>
	<form action="https://www.<?php echo PAYPAL_ENV ?>paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_xclick"/>
		<input type="hidden" name="amount" value="<?php echo number_format(EVENT_SETUP_FEE, 2, '.', '') ?>"/>
		<input type="hidden" name="bn" value="PP-BuyNowBF"/>
		<input type="hidden" name="business" value="<?php echo MU_PAYPAL_EMAIL ?>"/>
		<input type="hidden" name="currency_code" value="USD"/>
		<input type="hidden" name="custom" value="<?php echo 'checkout:'.$eventInfo['Event_ID'].':0' ?>"/>
		<input type="hidden" name="item_name" value="<?php echo $eventInfo['Event_Name']." event" ?>"/>
		<input type="hidden" name="lc" value="US"/>
		<input type="hidden" name="no_shipping" value="0"/>
		<input type="hidden" name="no_note" value="1"/>
		<input type="hidden" name="notify_url" value="<?php echo IPN_RETURN_URL ?>"/>
		<input type="hidden" name="return" value="<?php echo SECURE_LOCATION_SITE."event_activate.php" ?>"/>
		<?php if (!IS_PROD) { ?><input type="hidden" name="test_ipn" value="1"/><?php }	?>
		<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but03.gif" border="0" 
		name="submit" alt="Make payments with PayPal - it's fast, free and secure!"/>
		<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"/>
	</form>
<?php
	return;  
}

function build_donate_paypal() {
?>
    <form action="https://www.<?php echo PAYPAL_ENV ?>paypal.com/us/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_xclick"/>
        <input type="hidden" name="business" value="<?php echo MU_PAYPAL_EMAIL ?>"/>
        <input type="hidden" name="item_name" value="Mighty Ultimate Disc Donation"/>
        <input type="hidden" name="currency_code" value="USD"/>
        <input type="hidden" name="custom" value="<?php echo 'donation:0'.':'.get_session_player_id()?>"/>
		<input type="hidden" name="no_shipping" value="0"/>
		<input type="hidden" name="no_note" value="1"/>
        <input type="hidden" name="notify_url" value="<?php echo IPN_RETURN_URL ?>"/>
        <input type="hidden" name="return" value="<?php echo LOCATION_SITE."thanks.php" ?>"/>
        <input type="hidden" name="cancel_return" value="<?php echo LOCATION_SITE."index.php" ?>"/>
        <input type="image" src="http://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" 
        name="submit" alt="Make payments with PayPal - it's fast, free and secure!"/>
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"/>
    </form>
 <?php  
}

function build_register_paypal($eventInfo, $feeTotal) {
?>
	<form action="https://www.<?php echo PAYPAL_ENV ?>paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_xclick"/>
		<input type="hidden" name="amount" value="<?php echo number_format($feeTotal, 2, '.', '') ?>"/>
		<input type="hidden" name="bn" value="PP-BuyNowBF"/>
		<input type="hidden" name="business" value="<?php echo $eventInfo['Payment_Account'] ?>"/>
		<input type="hidden" name="currency_code" value="USD"/>
		<input type="hidden" name="custom" value="<?php echo 'register:'.$eventInfo['Event_ID'].':'.get_session_player_id()?>"/>
		<input type="hidden" name="item_name" value="<?php echo $eventInfo['Payment_Item_Name'] ?>"/>
		<input type="hidden" name="lc" value="US"/>
		<input type="hidden" name="no_shipping" value="0"/>
		<input type="hidden" name="no_note" value="1"/>
		<input type="hidden" name="notify_url" value="<?php echo IPN_RETURN_URL ?>"/>
		<input type="hidden" name="return" value="<?php echo SECURE_LOCATION_SITE."registration_status.php" ?>"/>
		<?php if (!IS_PROD) { ?><input type="hidden" name="test_ipn" value="1"/><?php }	?>
		<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but03.gif" border="0" 
		name="submit" alt="Make payments with PayPal - it's fast, free and secure!"/>
		<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"/>
	</form>
<?php
	return;  
}

function build_register_check($eventInfo) {
	echo $eventInfo['Payment_Chk_Payee'] ?><br/>
	<?php echo $eventInfo['Payment_Chk_Address'] ?><br/>
	<?php echo $eventInfo['Payment_Chk_City'].", ".$eventInfo['Payment_Chk_State']." ".$eventInfo['Payment_Chk_Zip'] ?><br/>
<?php
}
?>