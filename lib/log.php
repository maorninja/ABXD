<?php
$logText = array
(
	// register/login actions
	'register' => 'New user: {user}',
	'login' => '{user} logged in',
	'logout' => '{user} logged out',
	'loginfail' => '{user} attempted to log in as {user2}',
	'loginfail2' => '{user} attempted to log in as user "{text}"',
	'lostpass' => '{user} requested a password reset for {user2}',
	'lostpass2' => '{user} successfully reset his password.',
	
	// post related actions
	'newreply' => 'New reply by {user} in {thread} ({forum}): {post}',
	'editpost' => '{user} edited {user2 s} post in {thread} ({forum}): {post}',
	'deletepost' => '{user} deleted {user2 s} post in {thread} ({forum}): {post}',
	'undeletepost' => '{user} undeleted {user2 s} post in {thread} ({forum}): {post}',
	
	// thread related actions
	'newthread' => 'New thread by {user}: {thread}',
	'editthread' => '{user} edited {user2 s} thread {thread} ({forum})',
	'movethread' => '{user} moved {user2 s} thread {thread} from {forum} to {forum2}',
	'stickthread' => '{user} sticked {user2 s} thread {thread} ({forum})',
	'unstickthread' => '{user} unsticked {user2 s} thread {thread m} ({forum})',
	'closethread' => '{user} closed {user2 s} thread {thread} ({forum})',
	'openthread' => '{user} opened {user2 s} thread {thread} ({forum})',
	'trashthread' => '{user} trashed {user2 s} thread {thread} from forum {forum}',
	'deletethread' => '{user} deleted {user2 s} thread {thread} from forum {forum}',
	
	
	// admin actions
	'edituser' => '{user} edited {user2 s} profile',
	'usercomment' => '{user} commented on {user2 s} profile',
	'pmsnoop' => '{user} read {user2 s} PM: {pm}',
	'editsettings' => '{user} edited the board\'s settings',
	'editplugsettings' => '{user} edited the settings of plugin {text}',
	'enableplugin' => '{user} enabled plugin {text}',
	'disableplugin' => '{user} disabled plugin {text}',
	//Add other log actions in here
);

// CONSIDER: most of the log texts if not all, are going to be like "{user} did action foo"
// take out the {user} part and put it in a separate column on log.php?

// TODO move the fields/callbacks from pages/log.php here and make everything use the same plugin bucket?
$bucket = 'log_texts'; include('lib/pluginloader.php');


/** 
	This function lists all the users that should receive this action as a notification.
	Returns an associative array where the user IDs are the keys.
**/
function logGetRecipients($type, $params)
{
	global $loguserid;
	
	//TODO CHECK SECURITY
	
	$q = "";
	$q .= parseQuery("SELECT * FROM {subscriptions} WHERE 1");
	
	foreach($params as $key => $val)
		$q .= parseQuery (" AND ($key = {0} OR $key = 0 OR $key = '' OR ($key = -1 AND recipient = -1))");

	$q = rawQuery($q);
	
	$res = array();
	while($sub = fetch($q))
	{
		$id = $sub["recipient"];
		
		if($id == -1)
		{
			foreach($sub as $key => $val)
			{
				if($key == "recipient") continue;
				if($val == -1)
					$id = $params[$key];
			}
		}
		
		//Don't send notifications to a user about what he did.
		if($id != $loguserid)
			$res[$id] = true;
	}
	
	return $res;
}

function logAction($type, $params)
{
	global $loguserid;
	
	if(!isset($params["user"]))
		$params["user"] = $loguserid;
	
	$params["type"] = $type;
	
	$fields = array();
	$values = array();
	
	foreach ($params as $field => $val)
	{
		$fields[] = $field;
		$values[] = $val;
	}
	
	$recipients = logGetRecipients($type, $params);
	$recipients[] = 0;
	
	foreach($recipients as $recipient => $val)
		Query("INSERT INTO {log} (recipient, date, ip, ".implode(',',$fields).")
			VALUES ({0}, {1}, {2}, {3c})",
			$recipient, time(), $_SERVER['REMOTE_ADDR'], $values);
	
	$bucket = 'logaction'; include('lib/pluginloader.php');
}



function doLogList($cond, $html=true)
{

	$log_fields = array
	(
		'user' => array('table' => 'users', 'key' => 'id', 'fields' => '_userfields'),
		'user2' => array('table' => 'users', 'key' => 'id', 'fields' => '_userfields'),
		'thread' => array('table' => 'threads', 'key' => 'id', 'fields' => 'id,title'),
		'post' => array('table' => 'posts', 'key' => 'id', 'fields' => 'id'),
		'forum' => array('table' => 'forums', 'key' => 'id', 'fields' => 'id,title'),
		'forum2' => array('table' => 'forums', 'key' => 'id', 'fields' => 'id,title'),
		'pm' => array('table' => 'pmsgs', 'key' => 'id', 'fields' => 'id'),
	);

	$bucket = 'log_fields'; include('lib/pluginloader.php');

	$joinfields = '';
	$joinstatements = '';
	foreach ($log_fields as $field=>$data)
	{
		$joinfields .= ", {$field}.({$data['fields']}) \n";
		$joinstatements .= "LEFT JOIN {{$data['table']}} AS {$field} ON l.{$field}!='0' AND {$field}.{$data['key']}=l.{$field} \n";
	}

	$logR = query("
					SELECT 
						l.*
						{$joinfields}
					FROM 
						{log} l
						{$joinstatements}
					WHERE $cond
					ORDER BY date DESC
					LIMIT 100"); //TODO Paging

	$res = array();
	
	while($item = Fetch($logR))
		$res[] = array(
			"text" => formatEvent($item, $html),
			"ip" => $item["ip"],
			"date" => $item["date"],
		);
	
	return $res;
}

function formatEvent($item, $html=true)
{
	global $logText, $me, $lastuser, $loguserid;
	$me = $loguserid;
	$lastuser = -1;
	
	if(!isset($logText[$item['type']]))
		return "[Unknown event: ".htmlspecialchars($item['type'])."]";
		
	$link = "";
	
	$callback = function($m) use ($item, &$link) 
	{
		$func = 'logFormat_'.$m[1];
		$option = array();
		$len = strlen($m[3]);
		for($i = 0; $i < $len; $i++)
			$option[$m[3][$i]] = true;

		if($option["l"])
		{
			$func2 = $func.'_link';
			$link = $func2($item, $option);
		}
		return $func($item, $option);
	};
	
	$event = $logText[$item['type']];
	$event = preg_replace_callback("@\{(\w+)( (\w+))?\}@", $callback, $event);
	$event = ucfirst($event);
	return $event;
}

function addLogInput($m)
{
}


function logFormat_user($data, $option)
{
	$userdata = getDataPrefix($data, 'user_');
	return formatUser($userdata, $data, $option);
}

function logFormat_user2($data, $option)
{
	$userdata = getDataPrefix($data, 'user2_');
	return formatUser($userdata, $data, $option);
}

function formatUser($userdata, $data, $option)
{
	global $me, $lastuser;
	$id = $userdata["id"];
	$possessive = $option["s"];

	if($id == $me) return $possessive ? "your" : "you";
	if($id == $lastuser)
	{
		if($userdata["sex"] == 1)
			return $possessive ? "her" : "her";
		else
			return $possessive ? "his" : "him";
	}
	else $lastuser = $id;
	
	if($id == 0)
		$res = "A guest from ".htmlspecialchars($data["ip"]);
	else
		$res = userLink($userdata);
		
	if($possessive)
		$res .= "'s";
	return $res;
}

function logFormat_text($data)
{
	return htmlspecialchars($data["text"]);
}

function logFormat_thread($data)
{
	$thread = getDataPrefix($data, "thread_");
	return makeThreadLink($thread);
}

function logFormat_post($data)
{
	return actionLinkTag('#'.$data['post_id'], 'post', $data['post_id']);
}

function logFormat_forum($data)
{
	return actionLinkTag($data['forum_title'], 'forum', $data['forum_id'], "", $data['forum_title']);
}

function logFormat_forum2($data)
{
	return actionLinkTag($data['forum2_title'], 'forum', $data['forum2_id'], "", $data['forum2_title']);
}

function logFormat_pm($data)
{
	return actionLinkTag('PM #'.$data['pm_id'], 'showprivate', $data['pm_id'], 'snoop=1');
}

