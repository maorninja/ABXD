<?php

//Returns the new Post ID, or -1 if failed.
function doPost($tid, $postingAs, $post, $options=0, $mood=0)
{
		$thread = Fetch(Query("select * from threads where id=$tid limit 1"));
		$postingAsUser = Fetch(Query("select * from users where id=$postingAs limit 1"));

		//TODO
		if($thread['lastposter']==$postingAs && $thread['lastpostdate']>=time()-86400 && $postingAsUser['powerlevel']<3)
			Kill(__("You can't double post until it's been at least one day."));

		
		$qUsers = "users update set posts=".($postingAsUser['posts']+1).", lastposttime=".time()." where id=".$postingAs." limit 1";
		$rUsers = Query($qUsers);

		$qPosts = "insert into posts (thread, user, date, ip, num, options, mood) values (".$tid.",".$postingAs.",".time().",'".$_SERVER['REMOTE_ADDR']."',".($postingAsUser['posts']+1).", ".$options.", ".(int)$_POST['mood'].")";
		$rPosts = Query($qPosts);
		$pid = mysql_insert_id();

		$post = mysql_real_escape_string($post);
		$qPostsText = "insert into posts_text (pid,text) values (".$pid.",'".$post."')";
		$rPostsText = Query($qPostsText);

		$qFora = "update forums set numposts=".($forum['numposts']+1).", lastpostdate=".time().", lastpostuser=".$postingAs.", lastpostid=".$pid." where id=".$thread["fid"]." limit 1";
		$rFora = Query($qFora);

		$qThreads = "update threads set lastposter=".$postingAs.", lastpostdate=".time().", replies=".($thread['replies']+1).", lastpostid=".$pid.$mod." where id=".$tid." limit 1";
		$rThreads = Query($qThreads);

//		CheckYearling(1);
		Report("New reply by [b]".$postingAsUser['name']."[/] in [b]".$thread['title']."[/] (".$forum['title'].") -> [g]#HERE#?pid=".$pid, $isHidden);	
		
		return $pid;
}
