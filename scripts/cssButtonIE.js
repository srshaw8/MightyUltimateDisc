/*
* scaleable CSS button	courtesy of:
	http://www.filamentgroup.com/lab/update_styling_the_button_element_with_css_sliding_doors_now_with_image_spr/
*
* EXAMPLE JQUERY HOVER SCRIPT for toggling the hover state in browsers that don't support the ":hover" 
* pseudo class, like IE 6
*/
$(function(){
	$('.submitBtn').hover(
	// mouseover
		function(){ $(this).addClass('submitBtnHover'); },
			
		// mouseout
		function(){ $(this).removeClass('submitBtnHover'); }
	);	
});