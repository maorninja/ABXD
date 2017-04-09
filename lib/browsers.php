<?php

//$isFirefox = FALSE;
//$isIE6 = FALSE;

//$lastKnownBrowser = $_SERVER['HTTP_USER_AGENT'];
$lastKnownBrowser = "Something";

//Opera/9.80 (iPhone; Opera Mini/5.0.0176/764; U; en) Presto/2.4.15

$knownBrowsers = array
(
	"MSIE" => "Internet Explorer",
	"Opera Tablet" => "Opera Mobile (tablet)",
	"Opera Mobile" => "Opera Mobile",
	"Opera Mini" => "Opera Mini", //Opera/9.80 (J2ME/MIDP; Opera Mini/4.2.18887/764; U; nl) Presto/2.4.15
	'iPod' => 'iPod',
	'iPad' => 'iPad',
	'iPhone' => 'iPhone',
	"Nintendo Wii" => "Wii Internet Channel", //Opera/9.30 (Nintendo Wii; U; ; 3642; nl)
	"Nintendo DSi" => "Nintendo DSi Browser", //Opera/9.50 (Nintendo DSi; Opera/507; U; en-US)
	"Nitro" => "Nintendo DS Browser",
	"Nintendo 3DS" => "Nintendo 3DS",
	"Opera" => "Opera",
	"MozillaDeveloperPreview" => "Firefox (Development build)",
	"Firefox" => "Firefox",
	"dwb" => "DWB",
	"Chrome" => "Chrome",
	"Android" => "Android",
	"Midori" => "Midori",
	"Safari" => "Safari",
	"Konqueror" => "Konqueror",
	"Mozilla" => "Mozilla",
	"Lynx" => "Lynx",
	"ELinks" => "ELinks",
	"Links" => "Links",
	"Nokia" => "Nokia mobile",
);

$knownOSes = array
(
	"HTC_" => "HTC mobile",
	"Series 60" => "S60",
	"Android" => "Android",
	"Windows 4.0" => "Windows 95",
	"Windows 4.1" => "Windows 98",
	"Windows 4.9" => "Windows ME",
	"Windows NT 5.0" => "Windows NT",
	"Windows NT 5.1" => "Windows XP",
	"Windows NT 5.2" => "Windows XP 64",
	"Windows NT 6.0" => "Windows Vista",
	"Windows NT 6.1" => "Windows 7",
	"Windows NT 6.2" => "Windows 8",
	"Windows Mobile" => "Windows Mobile",
	"FreeBSD" => "FreeBSD",
	"Ubuntu" => "Ubuntu",
	"Linux" => "GNU/Linux %",
	"Mac OS X" => "Mac OS X %",
	"iPhone" => "iPhone",
	"iPad" => "iPad",
	"BlackBerry" => "BlackBerry",
	"Nintendo Wii" => "Nintendo Wii",
	"Nitro" => "Nintendo DS",
);

$mobileBrowsers = array('Opera Tablet', 'Opera Mobile', 'Opera Mini', 'Nintendo DSi', 'Nitro', 'Nintendo 3DS', 'Android', 'Nokia', 'iPod', 'iPad', 'iPhone');
$mobileLayout = false;

$ua = $_SERVER['HTTP_USER_AGENT'];

foreach($knownBrowsers as $code => $name)
{
	if (strpos($ua, $code) !== FALSE)
	{
		$versionStart = strpos($ua, $code) + strlen($code);
		if ($code != "dwb") $version = GetVersion($ua, $versionStart);

		//Opera Mini wasn't detected properly because of the Opera 10 hack.
		if (strpos($ua, "Opera/9.80") !== FALSE && $code != "Opera Mini" || $code == "Safari" && strpos($ua, "Version/") !== FALSE)
			$version = substr($ua, strpos($ua, "Version/") + 8);
			
		if (in_array($code, $mobileBrowsers)) $mobileLayout = true;

		$lastKnownBrowser = $name." ".$version;
		break;
	}
}

if ($_COOKIE['forcelayout'] == 1) $mobileLayout = true;
else if ($_COOKIE['forcelayout'] == -1) $mobileLayout = false;

$oldAndroid = false;
if ($name == 'Android' && $version[0] == '2') $oldAndroid = true;

$browserName = $name;
$browserVers = (float)$version;

$os = "";
foreach($knownOSes as $code => $name)
{
	if (strpos($ua, "X11")) $suffix = " (X11)";
	else if (strpos($ua, "textmode")) $suffix = " (text mode)";
	if (strpos($ua, $code) !== FALSE)

	{
		$os = $name;

		if(strpos($name, "%") !== FALSE)
		{
			$versionStart = strpos($ua, $code) + strlen($code);
			$version = GetVersion($ua, $versionStart);
			$os = str_replace("%", $version, $os);
		}
		//If we're using the default Android browser, just report the version of Android being used ~Nina
		$lkbhax = explode(' ', $lastKnownBrowser);
		if ($lkbhax[0] == "Android") break;
		if (isset($suffix)) $os = $os . $suffix;
		$lastKnownBrowser = format(__("{0} on {1}"), $lastKnownBrowser, $os);
		break;
	}
}

$lastKnownBrowser .= "<!-- ".htmlspecialchars($ua)." -->";

function GetVersion($ua, $versionStart)
{
	$numDots = 0;
	$version = "";
	if (strpos($ua, "Linux")) {
		for ($i = ++$versionStart; $i < strlen($ua); $i++) {
			if ($ua[$i] === " ")
				break;
			else if ($ua[$i] != ";") $version .= $ua[$i];
		}
	} else {
		for($i = $versionStart; $i < strlen($ua); $i++)
		{
			$ch = $ua[$i];
			if($ch == ';')
				break;
			if($ch == '_' && strpos($ua, "Mac OS X"))
				$ch = '.';
			if($ch == '.')
			{
				$numDots++;
				if($numDots == 3)
					break;
				$version .= '.';
			}
			else if(strpos("0123456789.-", $ch) !== FALSE)
				$version .= $ch;
		}
	}
	return $version;
}

?>
