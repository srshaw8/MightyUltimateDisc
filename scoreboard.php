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

display_wrappers();
build_page();
display_footer_wrapper();

function build_page() {
?>
	<div id="content_wrapper">
		<p>
		<table cellspacing="0" class="reportRowDisplay">
			<tr>
				<th scope="col" class="reportRowDisplay">Team</th>
				<th scope="col" class="reportRowDisplay">Win</th>
				<th scope="col" class="reportRowDisplay">Loss</th>
				<th scope="col" class="reportRowDisplay">PF</th>
				<th scope="col" class="reportRowDisplay">PA</th>
			<tr>
				<td>White</td>
				<td>6</td>
				<td>1</td>
				<td>123</td>
				<td>71</td>
			</tr>
			<tr>
				<td>Gold</td>
				<td>3</td>
				<td>4</td>
				<td>87</td>
				<td>86</td>
			</tr>
			<tr>
				<td>Red</td>
				<td>3</td>
				<td>4</td>
				<td>79</td>
				<td>94</td>
			</tr>
			<tr>
				<td>Blue</td>
				<td>2</td>
				<td>5</td>
				<td>82</td>
				<td>100</td>
			</tr>
		</table>
		</p>
	</div>
<?php
}
?>