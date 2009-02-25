<h2>Applications report</h2>
<table cellspacing="10">
  <tr>
    <th><?php print _('Per server'); ?></th>
	<th><?php print _('Per day'); ?></th>
	<th><?php print _('Per application'); ?></th>
  </tr>

  <tr>
    <td style="vertical-align: top">
<?php
foreach ($per_server as $fqdn => $server_data) {
	echo "      <h4>Server: $fqdn</h4>\n";
	foreach ($server_data as $app_id => $app_data) {
		$app_id_string = $app_id;
		if (isset($applications_info[$app_id]))
			$app_id_string = $applications_info[$app_id]->getAttribute('name');
		echo "      <h5>$app_id_string</h5>\n";
		echo "      <ul>\n";
		echo "        <li>Used {$app_data['use_count']} time(s)</li>\n";
		echo "        <li>Maximum simultaneous use: {$app_data['max_use']}</li>\n";
		echo "      </ul>\n";
	}
}
?>
    </td>

    <td style="vertical-align: top">
<?php
foreach ($per_day as $day => $day_data) {
	echo "      <h4>Day: $day</h4>\n";
	foreach ($day_data as $app_id => $app_data) {
		$app_id_string = $app_id;
		if (isset($applications_info[$app_id]))
			$app_id_string = $applications_info[$app_id]->getAttribute('name');
		echo "      <h5>$app_id_string</h5>\n";
		echo "      <ul>\n";
		echo "        <li>Used {$app_data['use_count']} time(s)</li>\n";
		echo "      </ul>\n";
	}
}
?>
    </td>

	<td style="vertical-align: top">
<?php
echo "      <ul>\n";
foreach ($per_app as $app_id => $data) {
	$app_id_string = $app_id;
	if (isset($applications_info[$app_id]))
		$app_id_string = $applications_info[$app_id]->getAttribute('name');
	echo "        <li>$app_id_string used {$data['use_count']} time(s)</li>\n";
}
?>
    </td>
  </tr>
</table>
