<?php

if ($loguser['powerlevel'] > 1)
{
	$general['appearance']['items'][] = "color";
	$general['appearance']['items'][] = "hascolor";
	
	$fields['color'] = array(
		"caption" => "Name color",
		"type" => "color",
	);
	$fields['hascolor'] = array(
		"caption" => "Enable color",
		"type" => "checkbox",
	);
}


