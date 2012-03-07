<?php

function doPostBox($prefill)
{
	global $loguserid;
	
	//Checkboxes
	if($_POST['nopl'])
		$nopl = "checked=\"checked\"";
	if($_POST['nosm'])
		$nosm = "checked=\"checked\"";
	if($_POST['nobr'])
		$nobr = "checked=\"checked\"";

	//Mood selectors
	$moodSelects = array();
	if($_POST['mood'])
		$moodSelects[(int)$_POST['mood']] = "selected=\"selected\" ";

	$rMoods = Query("select mid, name from moodavatars where uid=".$loguserid." order by mid asc");

	$moodOptions = "<option ".$moodSelects[0]."value=\"0\">".__("[Default avatar]")."</option>\n";
	while($mood = Fetch($rMoods))
		$moodOptions .= "<option ".$moodSelects[$mood['mid']]." value=\"".$mood['mid']."\">".htmlspecialchars($mood['name'])."</option>";

	//The form.
	print "
		<tr class=\"cell0\">
			<td>
				<label for=\"text\">
					".__("Post")."
				</label>
			</td>
			<td>
				<textarea id=\"text\" name=\"text\" rows=\"16\" style=\"width: 98%;\">$prefill</textarea>
			</td>
		</tr>
		<tr class=\"cell2\">
			<td>Options</td>
			<td>
				<select size=\"1\" name=\"mood\">
					$moodOptions
				</select>
				<label>
					<input type=\"checkbox\" name=\"nopl\" $nopl />&nbsp;".__("Disable post layout", 1)."
				</label>
				<label>
					<input type=\"checkbox\" name=\"nosm\" $nosm />&nbsp;".__("Disable smilies", 1)."
				</label>
				<label>
					<input type=\"checkbox\" name=\"nobr\" $nobr />&nbsp;".__("Disable auto-<br>", 1)."
				</label>
			</td>
		</tr>";
	
	
	print "
		<script type=\"text/javascript\">
				window.addEventListener(\"load\",  hookUpControls, false);
		</script>
	";
}


