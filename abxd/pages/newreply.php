<?php
//  AcmlmBoard XD - Reply submission/preview page
//  Access: users

$title = __("New reply");

if(isset($_POST['id']))
	$_GET['id'] = $_POST['id'];

if(!isset($_GET['id']))
	Kill(__("Thread ID unspecified."));

$tid = (int)$_GET['id'];
AssertForbidden("viewThread", $tid);
AssertForbidden("makeReply", $tid);

if($loguser['powerlevel'] < 0)
	Kill(__("You're banned. You can't post."));

$qThread = "select * from threads where id=".$tid;
$rThread = Query($qThread);
if(NumRows($rThread))
{
	$thread = Fetch($rThread);
	$fid = $thread['forum'];
}
else
	Kill(__("Unknown thread ID."));
$thread['title'] = strip_tags($thread['title']);
$tags = ParseThreadTags($thread['title']);
$titleandtags = $thread['title']."<TAGS>".$tags;

$qFora = "select * from forums where id=".$fid;
$rFora = Query($qFora);
if(NumRows($rFora))
	$forum = Fetch($rFora);
else
	Kill("Unknown forum ID.");
$fid = $forum['id'];
AssertForbidden("viewForum", $fid);

$isHidden = (int)($forum['minpower'] > 0);

if($forum['minpowerreply'] > $loguser['powerlevel'])
	Kill(__("Your power is not enough."));

if($thread['closed'] && $loguser['powerlevel'] < 3)
	Kill(__("This thread is locked."));

$OnlineUsersFid = $fid;

MakeCrumbs(array(__("Main")=>"./", $forum['title']=>actionLink("forum", $fid), $titleandtags=>actionLink("thread", $tid), __("New reply")=>""), $links);

if(!$thread['sticky'] && $warnMonths > 0 && $thread['lastpostdate'] < time() - (2592000 * $warnMonths))
	Alert(__("You are about to bump an old thread. This is usually a very bad idea. Please think about what you are about to do before you press the Post button."));

if($_POST['text'] && CheckTableBreaks($_POST['text']))
{
	$_POST['action'] = "";
	Alert(__("This post would break the board layout."), ("I'm sorry, Dave."));
}

if($_POST['text'] && $_POST['action'] != __("Preview"))
{
	$words = explode(" ", trim($_POST['text']));
	$wordCount = count($words);
	if($wordCount < $minWords)
	{
		$_POST['action'] = "";
		Alert(__("Your post is too short to have any real meaning. Try a little harder."), __("I'm sorry, Dave."));
	}
}

$ninja = FetchResult("select id from posts where thread=".$tid." order by date desc limit 0, 1",0,0);
if($_POST['action'] && isset($_POST['ninja']) && $_POST['ninja'] != $ninja)
{
	$_POST['action'] == __("Preview");
	Alert(__("You got ninja'd. You might want to review the post made while you were typing before you submit yours."));
}

if($_POST['text'] && $_POST['action'] == __("Post"))
{
	$lastPost = time() - $loguser['lastposttime'];
	if($lastPost < $minSeconds)
	{
		$_POST['action'] = "";
		Alert(__("You're going too damn fast! Slow down a little."), __("Hold your horses."));
	}
}

$postingAs = $loguserid;
$postingAsUser = $loguser;

if($_POST['action'] == __("Post"))
{
	if($postingAs == 0)
		Kill(__("You must be logged in to post."));

	if($_POST['text'])
	{
		$post = mysql_real_escape_string($_POST['text']);

		$options = 0;
		if($_POST['nopl']) $options |= 1;
		if($_POST['nosm']) $options |= 2;
		if($_POST['nobr']) $options |= 4;
		
		if(CanMod($loguserid, $forum['id']))
		{
			if($_POST['lock'])
				$mod.= ", closed = 1";
			else if($_POST['unlock'])
				$mod.= ", closed = 0";
			if($_POST['stick'])
				$mod.= ", sticky = 1";
			else if($_POST['unstick'])
				$mod.= ", sticky = 0";
		}

		if($thread['lastposter']==$postingAs && $thread['lastpostdate']>=time()-86400 && $postingAsUser['powerlevel']<3)
			Kill(__("You can't double post until it's been at least one day."));

		$qUsers = "users update set posts=".($postingAsUser['posts']+1).", lastposttime=".time()." where id=".$postingAs." limit 1";
		$rUsers = Query($qUsers);

		//$pid = FetchResult("SELECT id+1 FROM posts WHERE (SELECT COUNT(*) FROM posts p2 WHERE p2.id=posts.id+1)=0 ORDER BY id ASC LIMIT 1");
		//if($pid < 1) $pid = 1;

		$qPosts = "insert into posts (thread, user, date, ip, num, options, mood) values (".$tid.",".$postingAs.",".time().",'".$_SERVER['REMOTE_ADDR']."',".($postingAsUser['posts']+1).", ".$options.", ".(int)$_POST['mood'].")";
		$rPosts = Query($qPosts);
		$pid = mysql_insert_id();

		$qPostsText = "insert into posts_text (pid,text) values (".$pid.",'".$post."')";
		$rPostsText = Query($qPostsText);

		$qFora = "update forums set numposts=".($forum['numposts']+1).", lastpostdate=".time().", lastpostuser=".$postingAs.", lastpostid=".$pid." where id=".$fid." limit 1";
		$rFora = Query($qFora);

		$qThreads = "update threads set lastposter=".$postingAs.", lastpostdate=".time().", replies=".($thread['replies']+1).", lastpostid=".$pid.$mod." where id=".$tid." limit 1";
		$rThreads = Query($qThreads);

//		CheckYearling(1);
		Report("New reply by [b]".$postingAsUser['name']."[/] in [b]".$thread['title']."[/] (".$forum['title'].") -> [g]#HERE#?pid=".$pid, $isHidden);

		die(header("Location: ".actionLink("thread", 0, "pid=".$pid."#".$pid)));
		//Redirect(__("Posted!"), "thread.php?pid=".$pid."#".$pid, __("the thread"));
		exit();
	}
	else
		Alert(__("Enter a message and try again."), __("Your post is empty."));
}

if($_POST['text'])
	$prefill = $_POST['text'];

if($_POST['action'] == __("Preview"))
{
	if($_POST['text'])
	{
		$layoutblocked = $postingAsUser['globalblock'];
		if ($postingAs != $loguserid)
			$layoutblocked = $layoutblocked || FetchResult("SELECT COUNT(*) FROM blockedlayouts WHERE user=".$postingAs." AND blockee=".$loguserid);
		$previewPost['layoutblocked'] = $layoutblocked;
		
		$previewPost['text'] = $prefill;
		$previewPost['num'] = $postingAsUser['posts']+1;
		$previewPost['posts'] = $postingAsUser['posts']+1;
		$previewPost['id'] = "???";
		$previewPost['uid'] = $postingAs;
		$copies = explode(",","title,name,displayname,picture,sex,powerlevel,avatar,postheader,signature,signsep,regdate,lastactivity,lastposttime,rankset");
		foreach($copies as $toCopy)
			$previewPost[$toCopy] = $postingAsUser[$toCopy];
		$previewPost['mood'] = (int)$_POST['mood'];
		$previewPost['options'] = 0;
		if($_POST['nopl']) $previewPost['options'] |= 1;
		if($_POST['nosm']) $previewPost['options'] |= 2;
		if($_POST['nobr']) $previewPost['options'] |= 4;
		MakePost($previewPost, POST_SAMPLE, array('forcepostnum'=>1, 'metatext'=>__("Preview")));
	}
	else
		Alert(__("Enter a message and try again."), __("Your post is empty."));
}

if(!$_POST['text'])
	$_POST['text'] = $post['text'];
if($_POST['text'])
	$prefill = htmlspecialchars($_POST['text']);

if($_GET['link'])
{
	$prefill = ">>".(int)$_GET['link']."\r\n\r\n";
}
else if($_GET['quote'])
{
	$qQuote = "	select 
					p.id, p.deleted, pt.text,
					f.minpower,
					u.name poster
				from posts p
					left join posts_text pt on pt.pid = p.id and pt.revision = p.currentrevision 
					left join threads t on t.id=p.thread
					left join forums f on f.id=t.forum
					left join users u on u.id=p.user
				where p.id=".(int)$_GET['quote'];
	$rQuote = Query($qQuote);
	
	if(NumRows($rQuote))
	{
		$quote = Fetch($rQuote);

		//SPY CHECK!
		//Do we need to translate this line? It's not even displayed in its true form ._.
		if($quote['minpower'] > $postingAsUser['powerlevel'])
			$quote['text'] = str_rot13("Pools closed due to not enough power. Prosecutors will be violated.");
			
		if ($quote['deleted'])
			$quote['text'] = __("Post is deleted");

		$prefill = "[quote=\"".htmlspecialchars($quote['poster'])."\" id=\"".$quote['id']."\"]".htmlspecialchars($quote['text'])."[/quote]";
		$prefill = str_replace("/me", "[b]* ".htmlspecialchars($quote['poster'])."[/b]", $prefill);
	}
}


$ninja = FetchResult("select id from posts where thread=".$tid." order by date desc limit 0, 1",0,0);

if(CanMod($loguserid, $fid))
{
	$mod = "\n\n<!-- Mod options -->\n";
	//print $thread['closed'];
	if(!$thread['closed'])
		$mod .= "<label><input type=\"checkbox\" name=\"lock\">&nbsp;".__("Close thread", 1)."</label>\n";
	else
		$mod .= "<label><input type=\"checkbox\" name=\"unlock\">&nbsp;".__("Open thread", 1)."</label>\n";
	if(!$thread['sticky'])
		$mod .= "<label><input type=\"checkbox\" name=\"stick\">&nbsp;".__("Sticky", 1)."</label>\n";
	else
		$mod .= "<label><input type=\"checkbox\" name=\"unstick\">&nbsp;".__("Unstick", 1)."</label>\n";
	$mod .= "\n\n";
}
else
	$mod = "";
	
print "
	<table style=\"width: 100%;\">
		<tr>
			<td style=\"vertical-align: top; border: none;\">
				<form action=\"".actionLink("newreply")."\" method=\"post\">
					<input type=\"hidden\" name=\"ninja\" value=\"$ninja\" />
					<input type=\"hidden\" name=\"id\" value=\"$tid\" />
					<table class=\"outline margin width100\">
						<tr class=\"header1\">
							<th colspan=\"2\">
								".__("New reply")."
							</th>
						</tr>";

doPostBox($prefill, $buttons);

if($mod)
	print "<tr class=\"cell0\"><td></td><td>$mod</td></tr>";

$buttons = "
	<input type=\"submit\" name=\"action\" value=\"".__("Post")."\" /> 
	<input type=\"submit\" name=\"action\" value=\"".__("Preview")."\" />";
print "<tr class=\"cell0\"><td></td><td>$buttons</td></tr>";

print "
			</table>
		</form>
	</td>
	<td style=\"width: 20%; vertical-align: top; border: none;\">";

DoSmileyBar();
DoPostHelp();

print "
			</td>
		</tr>
	</table>
";

$qPosts = "select ";
$qPosts .=
	"posts.id, posts.date, posts.num, posts.deleted, posts.options, posts.mood, posts.ip, posts_text.text, posts_text.text, posts_text.revision, users.id as uid, users.name, users.displayname, users.rankset, users.powerlevel, users.sex, users.posts";
$qPosts .= 
	" from posts left join posts_text on posts_text.pid = posts.id and posts_text.revision = posts.currentrevision left join users on users.id = posts.user";
$qPosts .= " where thread=".$tid." and deleted=0 order by date desc limit 0, 20";

$rPosts = Query($qPosts);
if(NumRows($rPosts))
{
	$posts = "";
	while($post = Fetch($rPosts))
	{
		$cellClass = ($cellClass+1) % 2;

		$poster = $post;
		$poster['id'] = $post['uid'];

		$nosm = $post['options'] & 2;
		$nobr = $post['options'] & 4;

		$posts .= Format(
"
		<tr>
			<td class=\"cell2\" style=\"width: 15%; vertical-align: top;\">
				{1}
			</td>
			<td class=\"cell{0}\">
				<button style=\"float: right;\" onclick=\"insertQuote({2});\">".__("Quote")."</button>
				<button style=\"float: right;\" onclick=\"insertChanLink({2});\">".__("Link")."</button>
				{3}
			</td>
		</tr>
",	$cellClass, UserLink($poster), $post['id'], CleanUpPost($post['text'], $poster['name'], $nosm, $nobr));
	}
	Write(
"
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"2\">".__("Thread review")."</th>
		</tr>
		{0}
	</table>
",	$posts);
}
?>
