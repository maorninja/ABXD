<?php


function getrules($file)
{
	$css = file_get_contents($file);
	$cssrules = split("}", $css);
	$cssclean = array();
	foreach($cssrules as $rule)
	{
		$rule = trim($rule);
		if($rule != "")
			$cssclean[] = $rule."\n}\n";
	}

	return $cssclean;
}

function checkTheme2($theme)
{
	$a = getrules("common.css");
	$b = getrules("../themes/".$theme."/style.css");
	$removed = 0;
	$result = "";
	foreach($b as $rule)
	{
		if(array_search($rule, $a))
			$removed++;
		else
			$result .= $rule."\n";
	}

	print "$theme: Removed ".$removed."\n";
	file_put_contents("../themes/".$theme."/style.css", $result);
}

function checkTheme($theme)
{
	$css = file_get_contents("../themes/".$theme."/style.css");
	$css = preg_replace("/outline:[^\n]*\n/", "", $css, -1, $ct);
	print $theme.": ".$ct."\n";
	file_put_contents("../themes/".$theme."/style.css", $css);
}

$dir = "../themes/";
$themeList = "";
$themes = array();

// Open a known directory, and proceed to read its contents
if (is_dir($dir))
{
    if ($dh = opendir($dir))
    {
        while (($file = readdir($dh)) !== false)
        {
            if(filetype($dir . $file) != "dir") continue;
            if($file == ".." || $file == ".") continue;
            checkTheme($file);
        }
    }
}
