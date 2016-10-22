<?php
/**
 * @author Steven Shaw
 * @copyright 2008
 */
function display_errors($errors){
	if (isset($errors['app'])) {
	?>
		<span class="error">
		<?php
		$errorList = explode(";", $errors['app']);
		foreach ($errorList as $thisError) {
			echo $thisError."<br/>";
		}
		echo "<br/>";
		?>
		</span>	
	<?php			
	}
}

function error_add($errors, $thisError) {
 	if (array_key_exists('app', $errors)) {
		$existMessage = $errors['app'];
		$existMessage = $existMessage.";".$thisError;
		$errors['app'] = $existMessage;
	} else {
		$errors['app'] = $thisError;
	}
	return $errors;
}
?>