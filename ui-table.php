<?php
/*
Ad Logger
AJAX table display code
http://www.reviewmylife.co.uk/
*/

include_once('../../../wp-config.php');
include_once('../../../wp-load.php');
include_once('../../../wp-includes/wp-db.php');

check_ajax_referer('Ad-Logger', 'security');

if (!adlog_table_exists()){
	echo '<font color="red"><b>Error: Logging database table does not exist! Try clicking "Create log DB"</b></font><br />';
	exit();
}

$table_width_html = "";
adlog_results_table();

function adlog_results_table(){
	global $wpdb;
	$adlog_table = adlog_table_name();
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $adlog_table");
	$offset = adlog_get_param('offset', 6);
	$query = adlog_get_param('query', 50);
	$subquery = adlog_get_param('subquery', 50);
	$filter = adlog_get_param('filter', 350); //256 but allow extra space for base 64
	$filter = substr(base64_decode($filter), 0, 256);
	
	$columns = array('', 'date', 'type', 'size', 'unit', 'page', 'referrer', 'ip', 'browser', 'src', 'debug');
	if (!in_array(strtolower($subquery), $columns)){
		exit ("<b>Problem with subquery param: $subquery</b>");
	}

	adlog_debug_log("QUERY_STRING: ".$_SERVER['QUERY_STRING']);
	
	$ops = adlog_options();
	$rows_per_page = $ops['ui_num_rows_per_page'];

	if ($count > 1){
		$num_pages = ceil($count / $rows_per_page);
	} else {
		$num_pages = 1;
	}

	$page = 0;
	if (isset($offset)){
		$page = $offset;
		if ($page < 0) $page = 0;
		if ($page >= $num_pages) $page = $num_pages-1;		
	}
	$offset = $rows_per_page * $page;

	if (adlog_ticked('debug_mode')) $debugquery = ", Debug";
	if (adlog_ticked('ui_show_size')) $sizequery = ", Size";
	if (adlog_ticked('ui_show_unit')) $unitquery = ", Unit";
	if (adlog_ticked('ui_show_referrer')) $referrerquery = ", Referrer";
	if (adlog_ticked('ui_show_ip')) $ipquery = ", IP";
	if (adlog_ticked('ui_show_browser')) $browserquery = ", Browser";
	if (adlog_ticked('ui_show_src')) $sourcequery = ", Src";

	global $table_width_html;
	$table_width_html = "";
	$sql = "";
	if ($query == 'IPCount'){
		$sql = "SELECT COUNT(*) AS Clicks, IP FROM " . $adlog_table . " GROUP BY IP ORDER BY Clicks DESC LIMIT $rows_per_page OFFSET $offset";
	} else if ($query == 'PageCount'){
		$sql = "SELECT COUNT(*) AS Clicks, Page FROM " . $adlog_table . " GROUP BY Page ORDER BY Clicks DESC LIMIT $rows_per_page OFFSET $offset";
	} else if ($query == 'ReferrerCount'){
		$sql = "SELECT COUNT(*) AS Clicks, Referrer FROM " . $adlog_table . " GROUP BY Referrer ORDER BY Clicks DESC LIMIT $rows_per_page OFFSET $offset";
	} else if ($query == 'BrowserCount'){
		$sql = "SELECT COUNT(*) AS Clicks, Browser FROM " . $adlog_table . " GROUP BY Browser ORDER BY Clicks DESC LIMIT $rows_per_page OFFSET $offset";
	} else if ($query == 'TypeCount'){
		$sql = "SELECT COUNT(*) AS Clicks, Type FROM " . $adlog_table . " GROUP BY Type ORDER BY Clicks DESC LIMIT $rows_per_page OFFSET $offset";
	} else {
		$table_width_html = " width='100%'";
		$where = "";
		if (strlen($subquery) > 0){
			$where = "WHERE $subquery='$filter'";
		}
		$sql = "SELECT ID, Type, Date $sizequery $unitquery, Page $referrerquery $ipquery $browserquery $sourcequery $debugquery FROM " . $adlog_table . " $where ORDER BY ID DESC LIMIT $rows_per_page OFFSET $offset";
	}
	?>
	
	<div style="width:16px;height:16px;float:left"><?php echo '<img src="'.plugins_url().'/ad-logger/images/loader.gif" width="16" height="16" style="display:none" id="loader" />'; ?></div>
	&nbsp;Navigation: 
	<?php
		if ($page == 0){
			echo '&lt;&lt; First &lt; Previous ';
		} else {
			echo '<a href="" onclick="showTable(0);return false;">&lt;&lt; First</a> <a href="" onclick="showTable(--iPage);return false;">&lt; Previous</a> ';
		}
		if ($page == $num_pages-1){
			echo 'Next &gt; Last &gt;&gt;';
		} else {
			echo '<a href="" onclick="showTable(++iPage);return false;">Next &gt;</a> <a href="" onclick="showTable(<?php echo $num_pages-1; ?>);return false;">Last &gt;&gt;</a>';
		}
	?>
	 | <a href="" onclick="showTable(-1);return false;">Refresh &#x21bb;</a>
	| Reports: 
	<a href="" onclick="showTable(0, 'default', '');return false;">All logs</a>
	<a href="" onclick="showTable(0, 'TypeCount');return false;">Type</a>
	<a href="" onclick="showTable(0, 'IPCount');return false;">IPs</a>
	<a href="" onclick="showTable(0, 'PageCount');return false;">Pages</a>
	<a href="" onclick="showTable(0, 'ReferrerCount');return false;">Referrers</a>
	<a href="" onclick="showTable(0, 'BrowserCount');return false;">Browsers</a>
	<br />
	
	<?php
	adlog_print_table($sql);
	if ($query == 'default' && empty($subquery)){
		echo "Number of records: $count Page: ".($page+1)."/$num_pages";
	} else {
		echo "Page: ".($page+1);
	}
	echo " | Click on the '&#x21d3;' or 'Type' icon to filter the results. Click on '<a href=\"\" onclick=\"showTable(0, 'default', '');return false;\">All logs</a>' to reset.<br />";
}


function adlog_print_table($query) {
	$ops = adlog_options();
	adlog_debug_log("Query: $query");
	global $wpdb;
	$results = $wpdb->get_results($query);
	if ($results === false) die("Error with: $query<br />Msg: ".$wpdb->last_error);
	
	echo '
	<style type="text/css">
	table.adlogger {
		border: 1px
	}
	table.adlogger th {
		background-color: red;
	}	
	</style>';
	
	global $table_width_html;
	echo "<table class='adlogger' $table_width_html>";
	echo '<tr>';
	
	$column_names = array();
	
	// Output headers
	$columns = $wpdb->get_col_info('name');
	foreach($columns as $column){
		echo "<th>$column</th>";
		$column_names[] = $column;
	}
	echo '</tr>';

	if (count($results)== 0) die("</table><b>The logging table is currently empty.</b><br /><p>If you have just installed the plugin and want to try it with some example data you can add some test data from the 'Testing the plugin' section below. Then click the 'Refresh' link above the table to see the new data.</p><br />");
	
	// Output data
	$count = 1;
	foreach ($results as $row) {
		if ($count % 2 == 0){
			echo '<tr style="background-color:#cccccc">';
		} else {
			echo "<tr>";
		}
		$col_num = 0;
		foreach ($row as $column) {
			if ($column_names[$col_num] == 'ID'){
				echo "<td style='background-color:#ffffff'>$column";
			} else if ($column_names[$col_num] == 'Type'){
			} else if ($column_names[$col_num] == 'IP'){
				$ip = long2ip($column);
				if ($ip == "0.0.0.0"){
					echo "<td>0.0.0.0 (could not retrieve IP)";
				} else {
					echo "<td><a href='http://whatismyipaddress.com/ip/$ip' target='_new'>$ip</a>";
				}
			} else if ($column_names[$col_num] == 'Page' || $column_names[$col_num] == 'Referrer'){
				echo "<td><a href='$column' target='_new'>$column</a>";
			} else if ($column_names[$col_num] == 'Browser') {
				echo "<td><a href='http://user-agent-string.info/rpc/c_example_rpcxml.php?uas=$column' target='_new'>$column</a>";
			} else if ($column_names[$col_num] == 'Debug'){
				echo "<td><font color='green'>$column</font>";
			} else {
				echo "<td>$column";
			}
			if ($column_names[$col_num] == 'Type'){
				echo "<td style='background-color:#ffffff'>";
			}
			if ($column_names[$col_num] != 'ID' && $column_names[$col_num] != 'Date' && $column_names[$col_num] != 'Clicks'){
				$encoded = base64_encode($column); // because some web servers reject the request if a get parameter has a URL from another site in it
				echo "<a href='' onclick=\"showTable(0, 'default', '".$column_names[$col_num]."', '$encoded');return false;\" style='text-decoration: none'>";
			}
			if ($column_names[$col_num] != 'ID' && $column_names[$col_num] != 'Date' && $column_names[$col_num] != 'Clicks' && $column_names[$col_num] != 'Type'){
				echo "&#x21d3;";
			}
			if ($column_names[$col_num] == 'Type'){
				echo "<img src='".plugins_url()."/ad-logger/images/";
				switch ($column){
					case 1:
						echo 'google.png';
						break;
					case 2:
						echo 'amazon.png';
						break;
					case 3:
						echo 'facebook.png';
						break;
					case 4:
						echo 'twitter.png';
						break;
					case 5:
						echo 'plusone.png';
						break;
					case 0:
					default:
						echo 'unknown.png';
				}
				echo "' width='16' height='16' />";
			}
			if ($column_names[$col_num] != 'ID' && $column_names[$col_num] != 'Date' && $column_names[$col_num] != 'Clicks'){
				echo "</a>";
			}
			echo "</td>";
			++$col_num;
		}
		echo "</tr>\n";
		++$count;
	}

	echo "</table>\n";
}

function adlog_debug_log($string){
	if (adlog_ticked('debug_mode')) echo "<font color='green'>DEBUGMODE: $string</font><br />";
}

function adlog_get_param($param, $max_length=128) {
	if(isset($_GET[$param])) {
		return mysql_real_escape_string(substr($_GET[$param],0,$max_length));
	}
	return "";
}

?>
