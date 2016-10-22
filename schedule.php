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
				<th scope="col" class="reportRowDisplay">Day</th>
				<th scope="col" class="reportRowDisplay">Matchup</th>
				<th scope="col" class="reportRowDisplay">Score</th>
				<th scope="col" class="reportRowDisplay">Field</th>
			<tr>
				<td>6/12</td>
				<td>Red vs. White</td>
				<td>14-13</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Blue vs. Gold</td>
				<td>13-11</td>
				<td>2</td>
			</tr>
			<tr>
				<td>6/19</td>
				<td>Red vs. Blue</td>
				<td>15-12</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>White vs. Gold</td>
				<td>15-11</td>
				<td>2</td>
			</tr>
			<tr>
				<td>6/26</td>
				<td>Red vs. Gold</td>
				<td>4-15</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Blue vs. White</td>
				<td>8-15</td>
				<td>2</td>
			</tr>		
			<tr>
				<td>7/3</td>
				<td>Blue vs. Gold</td>
				<td>8-15</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Red vs. White</td>
				<td>9-15</td>
				<td>2</td>
			</tr>
			<tr>
				<td>7/10</td>
				<td>White vs. Gold</td>
				<td>15-9</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Red vs. Blue</td>
				<td>12-15</td>
				<td>2</td>
			</tr>
			<tr>
				<td>7/17</td>
				<td>Blue vs. White</td>
				<td>10-15</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Red vs. Gold</td>
				<td>15-9</td>
				<td>2</td>
			</tr>		<tr>
				<td>7/24</td>
				<td>Red vs. White</td>
				<td>10-15</td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Blue vs. Gold</td>
				<td>16-17</td>
				<td>2</td>
			</tr>		<tr>
				<td>7/31</td>
				<td>Red vs. Blue</td>
				<td></td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>White vs. Gold</td>
				<td></td>
				<td>2</td>
			</tr>		<tr>
				<td>8/7</td>
				<td>Red vs. Gold</td>
				<td></td>
				<td>1</td>
			</tr>
			<tr>
				<td></td>
				<td>Blue vs. White</td>
				<td></td>
				<td>2</td>
			</tr>
		</table>
		</p>
	</div>
<?php
}
?>