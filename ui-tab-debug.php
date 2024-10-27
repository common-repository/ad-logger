<?php
/*
Ad Logger for WordPress
http://www.advancedhtml.co.uk/
*/

if (!is_admin()) return;

function adlog_tab_debug(){
	$ops = adlog_options();
	adlog_debug_information();
}

function adlog_debug_information(){
	$stored_options = adlog_options();
	$default_options = adlog_default_options();
	?>
	<h4>Settings dump from database (all in 'adlog_options' option)</h4>
	<table border="1" style="width:610px; table-layout:fixed; word-wrap:break-word;">
	<tr><td><b>Name</b></td><td><b>Stored</b></td><td><b>Default</b></td></tr>
	<?php
	$main_length = 0;
	if ($stored_options !== false){
		$count = 0;
		foreach ($stored_options as $key => $value){
			if ($count % 2 == 0){
				echo '<tr style="background-color:#cccccc"><td>';
			} else {
				echo '<tr><td>';
			}
			echo "$key";
			$main_length += sizeof($key);
			$value = htmlspecialchars($value);
			$main_length += sizeof($value);
			$default = $default_options[$key];
			echo "</td><td>";
			if ($value != $default) echo '<font color="blue">';
			echo $value;
			if ($value != $default) echo '</font>';
			echo "</td><td>";
			echo $default;
			echo "</td></tr>";
			$count++;
		}
	} else {
		echo "<br />No options in database!";
	}
	echo '</table>';
	
	echo '<h4>Other settings</h4><blockquote>';
	
	echo 'ADLOG_PATH='.ADLOG_PATH.'<br />';
	echo 'plugins_url()='.plugins_url().'<br />';
	echo 'Plugin version='.adlog_get_version().'<br />';
	echo 'Main settings length='.$main_length.' chars<br />';
	echo '</blockquote>';
	
	?>
	<input type="submit" name="adlog_action" value="<?php _e('Reset settings to default', 'adlog') ?>" />	
	<input type="submit" name="adlog_action" value="<?php _e('Delete settings', 'adlog') ?>" />
	<input type="submit" name="adlog_action" value="<?php _e('Reset Notice', 'adlog') ?>" />
	<?php
}

?>