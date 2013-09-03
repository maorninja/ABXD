<?php

if($loguser['powerlevel'] < 3)
	Kill(__("Access denied."));

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Admin"), "admin"));
$crumbs->add(new PipeMenuLinkEntry(__("Log"), "log"));
makeBreadcrumbs($crumbs);

$list = doLogList(parseQuery("recipient={0}", (int)$_GET["id"]));

echo "
	<table class=\"outline margin\">
		<tr class=\"header1\">
			<th>
				".__("Time")."
			</th>
			<th>
				".__("Event")."
			</th>
			<th>
				".__("IP")."
			</th>
		</tr>";

foreach($list as $item)
{
	$cellClass = ($cellClass + 1) % 2;
	echo "
		<tr>
			<td class=\"cell2\">
				".str_replace(" ", "&nbsp;", TimeUnits(time() - $item['date']))."
			</td>
			<td class=\"cell$cellClass\">
				".$item["text"]."
			</td>
			<td class=\"cell$cellClass\">
				".formatIP($item["ip"])."
			</td>
		</tr>";
}

echo "</table>";

