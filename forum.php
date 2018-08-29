<?php

// 04/01/11
/*if ($_REQUEST['d'] && $_REQUEST['t']) {
  header('Location: http://rbpgawker.f0e.net/#!' . $_REQUEST['t'] . $_REQUEST['d'] . '/');
  exit();
} else {
  header('Location: http://rbpgawker.f0e.net/');
  exit();
}*/


// message board script v3

// include the configuration file
require('config.inc.php');

// make sure the user doesn't cache this page
//header('Cache-control: private, no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

// handle authentication if necessary
if ($config['auth_required'] == true || isset($_REQUEST['needauth'])) {

  // establish a connection with the database or notify an admin with the error string
  if (!isset($mysqli_link)) {
    $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],"mysqli_connect(" . $config['db_host'] . "," . $config['db_user'] . "," . $config['db_pass'] . ")\n".mysqli_error());
  }

  // begin a session
  session_start();

  if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    $query = "select username from " . $locations['auth_table'] . " where username = '" . $_SESSION['username'] . "' and password = '" . $_SESSION['password'] . "'";
    $result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
    if (mysqli_num_rows($result) != 1) {
      // destroy the erroneous session
      session_destroy();
      // leave
      header("Location: " . $locations['login']);
      exit();
    }
  } else {
    // leave
    header("Location: " . $locations['login']);
    exit();
  }

} elseif ($config['auth_post_required'] == true && !isset($_REQUEST['nocache'])) {

  // start session (if not already started)
  if (!ini_get('session.auto_start')) {
    session_cache_limiter('private_no_cache');
    session_name($config['session_name']);
    session_save_path($locations['session_path']);
    ini_set('session.gc_maxlifetime','604800');
    session_start();
  }

  // error handling
  $err_array = array();
  $val_array = array();
  if (isset($_SESSION['errors'])) {
    $err_array = unserialize($_SESSION['errors']);
    $val_array = unserialize($_SESSION['values']);
    unset($_SESSION['errors']);
    unset($_SESSION['values']);
  }

  if (isset($_REQUEST['logout'])) {

    session_destroy();
    header("Location: " . $locations['forum']);
    exit();

  }

}

// handle lite mode
if (isset($_GET['display_mode']) && $_GET['display_mode'] == 1) {

  // make display_mode sticky
  setcookie('display_mode','1',0,'/');

} elseif (isset($_GET['display_mode']) && $_GET['display_mode'] == 2) {

  // delete the display_mode cookie
  setcookie('display_mode','',0,'/');

  // make sure its really gone
  $_COOKIE['display_mode'] = 0;

} 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">        
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=.5, maximum-scale=1.0" />
  <title><?=$config['title']?></title>

  <script language="Javascript" type="text/javascript">
  <!--
  function isFilled(f){
    var L_Msg_Text='Please enter a Name and Subject.';
    if (f.message_author.value == '' || f.message_subject.value == '') {
      alert(L_Msg_Text);
      return false;
    } else {
      disableForm(f);
      return true;
    }
  }

  function disableForm(theform) {
    if (document.all || document.getElementById) {
      for (i = 0; i < theform.length; i++) {
	var tempobj = theform.elements[i];
	if (tempobj.type.toLowerCase() == "submit" || tempobj.type.toLowerCase() == "reset")
	  tempobj.disabled = true;
      }
      return true;
    } else {
      return false;
    }
  }

  function hidden_links(num) {
    var hidden_links_text = "<table class='main'>";
    for (i = 1; i < num.value; i++) {
      hidden_links_text = hidden_links_text + "<tr><td width='100' align='right' valign='top'>Link URL: </td><td><input type='text' name='message_link_url[]' value='' placeholder='http://' size='50' maxlength='255' class='forminput' /></td></tr><tr><td align='right' valign='top'>Link Title: </td><td><input type='text' name='message_link_title[]' value='' size='50' maxlength='75' class='forminput' /></td></tr>";
    }
    hidden_links_text = hidden_links_text + "</table>\n";
    document.getElementById('hidden_links_text').innerHTML = hidden_links_text;
  }

  function hidden_images(num) {
    var hidden_images_text = "<table class='main'>";
    for (i = 1; i < num.value; i++) {
      hidden_images_text = hidden_images_text + "<tr><td width='100' align='right' valign='top'>Image URL: </td><td><input type='text' name='message_image_url[]' value='' placeholder='http://' size='50' maxlength='255' class='forminput' /></td></tr>";
    }
    hidden_images_text = hidden_images_text + "</table>\n";
    document.getElementById('hidden_images_text').innerHTML = hidden_images_text;
  }

  //-->
  </script>

  <link rel="stylesheet" type="text/css" href="<?=$locations['css']?>" />

</head>

<body class='body'>

<?

// display the thread
if (isset($_GET['d']) && is_numeric($_GET['d']) && isset($_GET['t']) && is_numeric($_GET['t'])) {

  // set up the DB connection
  if (!isset($mysqli_link)) {
    $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],"mysqli_connect(" . $config['db_host'] . "," . $config['db_user'] . "," . $config['db_pass'] . ")\n".mysqli_error());
  }

  // preset the table name
  $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$_GET['t'] : $locations['posts_table']);

  // query for the post
  $query = "select $tablename.id, $tablename.parent, $tablename.message_author, $tablename.message_author_email, ".
	   "$tablename.message_subject, $tablename.message_body, date_format($tablename.date,'%m/%d/%Y - %l:%i:%s %p') as date, ".
	   "$tablename.ip, $tablename.thread, $tablename.link, $tablename.image, $tablename.video ".
	   "from $tablename ".
	   "where id = " . $_GET['d'] . (!$config['rotate_tables'] ? ' and t = "' . $_GET['t'] . '"' : '');

  $result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

  if (mysqli_num_rows($result) == 1) {

    $post = mysqli_fetch_array($result);

?>
<table width='100%' border='0' cellpadding='0' cellspacing='0'>
  <tr>
    <td valign='top'>
<table width='100%' border='0' cellpadding='0' cellspacing='0'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellpadding='4' cellspacing='1'>
  <tr class='titlelarge'>
    <td colspan='2'><?=$post['message_subject']?></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
    <a href='#follow_ups'><b>[Follow Ups]</b></a>
    <a href='#post_follow_up'><b>[Post Follow Up]</b></a>
    <a href='<?=$locations['forum']?>'><b>[<?=$config['title']?>]</b></a>
    <a href='<?=$locations['search']?>?finduser=<?=$post['ip']?>'><b>[Other posts by <?=$post['message_author']?>]</b></a>
    </td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<table width='100%'>
  <tr valign='top'>
    <td>
    Posted by <?=$post['message_author']?>
<?

    if (strlen($post['message_author_email']) > 0)
      print " &lt;<a href='mailto:" . $post['message_author_email'] . "'>" . $post['message_author_email'] . "</a>&gt;";

    print " on " . $post['date'] . "<br />\n";

    // add authentication tag
    $query = "select '1' from " . $locations['auth_posts_table'] . " where id = '" . $_GET['d'] . "' and t = '" . $_GET['t'] . "'";
    $result = mysqli_query($mysqli_link, $query);

    if (mysqli_num_rows($result) == 1)
      print "<b>This post has been authenticated.</b><br />\n";

    print "</td><td align='right'>\n";

    // add flagging stuff here:

    $query = "select score, type from " . $locations['flags_table'] . " where id = '" . $post['id'] . "' and t = '" . $_GET['t'] . "' order by votes desc limit 1";
    $votes_res = mysqli_query($mysqli_link, $query);

    $vote_cur = null;
    $vote_type_preset = null;

    if (mysqli_num_rows($votes_res) > 0) {
      $votes = mysqli_fetch_array($votes_res);
      $vote_type_preset = $votes['type'];
      $votes['type'] = ucfirst($votes['type']);
      switch($votes['type']) {
        case 'Warn-g':
          $votes['type'] = "<b style='color: red; font-size: larger'>Warning</b> - Gross";
          break;
        case 'Warn-n':
          $votes['type'] = "<b style='color: red; font-size: larger'>Warning</b> - Nudity";
          break;
      }
      $vote_cur = null;
      if ($votes['score'] != '' && $votes['type'] != '')
	$vote_cur = $votes['score'] . ', ' . $votes['type'];
      elseif ($votes['score'] != '' && $votes['type'] == '')
	$vote_cur = $votes['score'];
      elseif ($votes['score'] == '' && $votes['type'] != '')
	$vote_cur = $votes['type'];
    }
    unset($votes_res);
    unset($votes);


?>
<form action='rate.php' method='post'>
<input type='hidden' name='id' value='<?=$post['id']?>' />
<input type='hidden' name='t' value='<?=$_GET['t']?>' />
Score:
<select name='score' style='font-size: smaller'>
<option value='0'>0</option>
<option value='1'>1</option>
<option value='2'>2</option>
<option value='3'>3</option>
<option value='4'>4</option>
<option value='5'>5</option>
</select>
Type:
<select name='type' style='font-size: smaller'>
<option value=''></option>
<option value='funny'<? if ($vote_type_preset == 'funny') print ' selected'; ?>>Funny</option>
<option value='warn-g'<? if ($vote_type_preset == 'warn-g') print ' selected'; ?>>Warning - Gross</option>
<option value='warn-n'<? if ($vote_type_preset == 'warn-n') print ' selected'; ?>>Warning - Nudity</option>
<option value='nsfw'<? if ($vote_type_preset == 'nsfw') print ' selected'; ?>>NSFW</option>
</select>
<input type='submit' value='Flag' style='font-size: smaller' />
<br />
<? if (isset($vote_cur)) { ?>
<span style='font-size: smaller'>( currently: <?=$vote_cur?> )<span>
<? } ?>
</form>
<?
    print "</td></tr></table>\n";

    // add reply to info.
    if ($post['id'] != $post['parent']) {

      // remove the last id from the thread, and return the last one left
      $reply_ids = explode('.',$post['thread']);
      $reply_id = $reply_ids[count($reply_ids) - 2];
      unset($reply_ids);

      $query = "select $tablename.id, $tablename.message_author, $tablename.message_subject, ".
	       "date_format($tablename.date,'%m/%d/%Y - %l:%i:%s %p') as date ".
	       "from $tablename where $tablename.id = '$reply_id'" . (!$config['rotate_tables'] ? ' and t = "' . $_GET['t'] . '"' : '');

      $reply = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

      if (mysqli_num_rows($reply) == 1) {
	$reply = mysqli_fetch_array($reply);
	print "In Reply to: <a href='" . $locations['forum'] . "?d=" . $reply['id'] . "&amp;t=" . $_GET['t'] . "'>" . $reply['message_subject'] . "</a> posted by " . $reply['message_author'] . " on " . $reply['date'] . "<br />\n";
      }

    }

    // display the body
    print "<hr /><br />\n";
    print nl2br($post['message_body']);
    print "\n<br /><br />\n";

    // display the images
    if ($post['image'] == 'y') {

      $query = "select " . $locations['images_table'] . ".image_url ".
	       "from " . $locations['images_table'] . " " .
	       "where " . $locations['images_table'] . ".id = '" . $post['id'] . "' and " . $locations['images_table'] . ".t = '" . $_GET['t'] . "'";

      $images = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

      if (mysqli_num_rows($images) > 0) {
	while ($image = mysqli_fetch_array($images)) {
	  if (strlen($image['image_url']) > 0) {
	    $gfycat_data_id = null;
		if (preg_match('/gfycat\.com\/\w*/', $image['image_url'])) {
    $apiurl = 'https://api.gfycat.com/v1/oembed?url=';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL,($apiurl . $image['image_url']));
		$result=curl_exec($ch);
		curl_close($ch);
		$json = json_decode($result);
		if (isset($json))
		{
			print '<div style="max-height:' . $json->height . 'px; max-width:' . $json->width . 'px">' . $json->html . '</div>';
			print '<br />';
		}
	  } elseif (preg_match('/\.mp4$/i', $image['image_url'])) {
	      echo "<video autoplay='' loop='' muted=''><source src='" , $image['image_url'] , "' type='video/mp4'></video><br /><a href='" , $image['image_url'] , "'>source</a><br /><br />\n";
      } elseif (preg_match('/imgur.com\/(.+?)\.(gifv|webm)$/i', $image['image_url'], $gifv_filename)) {
        echo "<blockquote class='imgur-embed-pub' lang='en' data-id='" , $gifv_filename[1] , "'></blockquote><script async src='//s.imgur.com/min/embed.js' charset='utf-8'></script><br /><br />\n";
	    } elseif (preg_match('/\.webm$/i', $image['image_url'])) {
	      echo "<video autoplay='' loop='' muted=''><source src='" , $image['image_url'] , "' type='video/webm'></video><br /><a href='" , $image['image_url'] , "'>source</a><br /><br />\n";
	    } elseif (preg_match('/^(.+?)\.gifv$/i', $image['image_url'], $gifv_filename)) {
	      echo "<video autoplay='' loop='' muted=''><source src='" , $gifv_filename[1] , ".webm' type='video/webm'></video><br /><a href='", $image['image_url'] ,"'>source</a><br /><br />\n";
      } elseif (preg_match('/^https?:\/\/rbp\.f0e\.net\/(.+)$/i', $image['image_url'], $rel_image)) {
        echo "<img src='//rbp.f0e.net/" , $rel_image[1] , "' alt ='' /><br /><br />\n";
	    } else
	      echo "<img src='" . $image['image_url'] . "' alt='' /><br /><br />\n";
	  }
	}
      }

    }

    print "<br />\n";

    // display the links
    if ($post['link'] == 'y') {

      $query = "select " . $locations['links_table'] . ".link_url, " . $locations['links_table'] . ".link_title ".
	       "from " . $locations['links_table'] . " ".
	       "where " . $locations['links_table'] . ".id = '" . $post['id'] . "' and " . $locations['links_table'] . ".t = '" . $_GET['t'] . "'";

      $links = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

      if (mysqli_num_rows($links) > 0) {
	while ($link = mysqli_fetch_array($links)) {
	  if (strlen($link['link_url']) > 0) {
	    $youtube_video_id = null;
	    $vimeo_video_id = null;
		$xkcd_data_id = null;
		$twitter_url = null;
      if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $link['link_url'], $youtube_video_id)) {
	      print '<iframe id="ytplayer" type="text/html" width="640" height="390" src="https://www.youtube.com/embed/'.escape($youtube_video_id[1]).'?autoplay=0" frameborder="0" allowfullscreen></iframe><br />';
	    } elseif (preg_match('/vimeo\.com\/(\d+)/', $link['link_url'], $vimeo_video_id)) {
	      echo '<iframe src="//player.vimeo.com/video/' , $vimeo_video_id[1] , '?color=f1732f" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe><br />';
      } elseif (preg_match('/^(https?:\/\/(mobile.)?twitter\.com\/[?:#!\/]?\w+\/status[es]?\/\d+).*$/', $link['link_url'], $twitter_url)) {
        $apiurl = 'https://publish.twitter.com/oembed?url=';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,($apiurl . urlencode($twitter_url[1])));
        $result=curl_exec($ch);
        curl_close($ch);
        $json = json_decode($result);
        if (isset($json))
        {
          print $json->html;
          print '<br />';
        }
      } elseif (preg_match('/(https?:\/\/)(www.)?((instagram.com\/p\/)|(instagr.am\/p\/))/', $link['link_url'])) {
        $apiurl = 'https://api.instagram.com/oembed?url=';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL,($apiurl . $link['link_url']));
        $result=curl_exec($ch);
        curl_close($ch);
        $json = json_decode($result);
        if (isset($json))
        {
          print $json->html;
          print '<br />';
        }
      }	elseif (preg_match('/(http[s]*:\/\/[www.]*xkcd\.com\/)([\d]*)/',  $link['link_url'] , $xkcd_data_id)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL,('http://xkcd.com/' . $xkcd_data_id[2] . '/info.0.json'));
		$result=curl_exec($ch);
		curl_close($ch);
		$json = json_decode($result);
		if (isset($json))
		{
			print 'Title: ' . $json->title . '<br />';
			print '<img src="' . $json->img . '" />' . '<br />';
			print 'Alt: ' . $json->alt . '<br />';
		}
	  }	elseif (preg_match('/https?:\/\/.+facebook\.com\/.+/',  $link['link_url'])) {
		$ch = curl_init();
		$ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.16 (KHTML, like Gecko) \ Chrome/24.0.1304.0 Safari/537.16';
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL,('https://www.facebook.com/plugins/post/oembed.json/?url=' . urlencode($link['link_url'])));
		$result=curl_exec($ch);
		curl_close($ch);
		$json = json_decode($result);
		if (isset($json))
		{
			print $json->html;
			print '<br />';
		}
	  }
	    print "<a href='" . preg_replace('/\'/', '&#039;', $link['link_url']) . "' target='" . $post['id'] . "." . $_GET['t'] . "'>";
	    if (strlen($link['link_title']) > 0)
	      print $link['link_title'];
	    else
	      print $link['link_url'];
	    print "</a><br />\n";

	  }
	}
      }

      print "<br />\n";

    }

?>
    </td>
  </tr>
  <tr class='title'>
    <td colspan='2'><a name='follow_ups'>Follow Ups</a></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<?

    $query = "select $tablename.id, $tablename.message_author, $tablename.message_subject, $tablename.thread, ".
	     "$tablename.link, $tablename.image, $tablename.video, ifnull($tablename.score, 'null') as score, ifnull($tablename.type, 'null') as type, ".
	     "case when $tablename.message_body = '' then 'n' else 'y' end as body, ".
	     "date_format($tablename.date,'%m/%d/%Y - %l:%i:%s %p') as date ".
	     "from $tablename where $tablename.parent = '" . $post['parent'] . "' and $tablename.thread like '" . $post['thread'] . ".%' " . (!$config['rotate_tables'] ? ' and t = "' . $_GET['t'] . '"' : '') . ' ' .
	     "order by $tablename.parent desc,$tablename.thread asc";

    $replies = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

    if (mysqli_num_rows($replies) > 0) {

      print "<ul>\n";

      $lastthread = array();
      while ($reply = mysqli_fetch_array($replies)) {

	// find difference between these arrays, returns an array
  	print str_repeat('</li></ul>',count(array_diff($lastthread,explode('.',$reply['thread']))));

	$lastthread = explode('.',$reply['thread']);

        $display_rate = null;
        if ($reply['score'] != 'null' || ($reply['type'] != 'null' && $reply['type'] != '')) {

          switch ($reply['type']) {
            case 'warn-g':
              $reply['type'] = "<b style='color: red; font-size: larger'>Warning</b> - Gross";
              break;
            case 'warn-n':
              $reply['type'] = "<b style='color: red; font-size: larger'>Warning</b> - Nudity";
              break;
            case 'nsfw':
              $reply['type'] = "<b style='color: red; font-size: larger'>NSFW</b>";
              break;
          }

          $display_rate = " - <span style='font-size: smaller'>( ";
          if ($reply['score'] != 'null') $display_rate .= $reply['score'];
          if ($reply['score'] != 'null' && $reply['type'] != 'null' && $reply['type'] != '') $display_rate .= ', ' . ucfirst($reply['type']);
          if ($reply['score'] == 'null') $display_rate .= ucfirst($reply['type']);
          $display_rate .= ' )</span>';
        }

	print "<ul><li><a href='" . $locations['forum'] . "?d=" . $reply['id'] . "&amp;t=" . $_GET['t'] ."'>" . $reply['message_subject'] . "</a> ".
	      options($reply['link'],$reply['video'],$reply['image'],$reply['body'],$reply['message_author']).
	      " - <b>" . $reply['message_author'] . "</b> - " . $reply['date'] . " $display_rate\n";

      }

      print str_repeat('</li></ul>',count($lastthread) - 1);
      print "</ul>\n";

    }

?>
    </td>
  </tr>
  <tr class='title'>
    <td colspan='2'><a name='post_follow_up'>Post a Follow Up</a></td>
  </tr>
<?

    display_form($post['parent'],$_GET['t'],$post['thread']);

  } else {

    // display error
    print "Post not found\n";

  }

} elseif ((isset($_GET['display_mode']) && $_GET['display_mode'] == 1) ||
	 (isset($_COOKIE['display_mode']) && $_COOKIE['display_mode'] == 1)) {

  // add return to full mode and refresh link
  print "(<a href='forum.php'>Refresh</a>)\n";
  print "(<a href='" . $locations['search'] . "'>Search</a>)\n";
  print "(<a href='" . $locations['forum'] . "?display_mode=2'>Return to Full Mode</a>)\n";

  // Check for existing $datfile.lock
  if (file_exists($locations['datfile'] . ".lock")) {
    for ($i = 0; $i < 3; $i++) {
      sleep(1);
      if (!file_exists($locations['datfile'] . ".lock"))
	break;
    }
  }

  // grab the shortened datfile
  include($locations['datfile_lite']);
  display_form();

} else {

  // grab the normal datfile
  display_header();

  // Check for existing $datfile.lock
  if (file_exists($locations['datfile'] . ".lock")) {
    for ($i = 0; $i < 3; $i++) {
      sleep(1);
      if (!file_exists($locations['datfile'] . ".lock"))
	break;
    }
  }

  readfile($locations['datfile']);

  display_form();

  if ($config['show_users'] === true) {

    // establish a connection with the database or notify an admin with the error string
    if (!isset($mysqli_link)) {
      $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],"mysqli_connect(" . $config['db_host'] . "," . $config['db_user'] . "," . $config['db_pass'] . ")\n".mysqli_error());
    }

    $query = "select username from " . $locations['auth_table'] . " order by username";
    $users = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

    if (mysqli_num_rows($users) > 0) {

      print "<br />Current user list: ";

      $i = 1;
      while ($user = mysqli_fetch_array($users)) {
	print " " . $user['username'];
	if ($i != mysqli_num_rows($users)) print ',';
	$i++;
      }

    }

  }

}

function display_header() {

  global $locations;
  global $config;

?>
<table width='100%' border='0' cellpadding='0' cellspacing='0' id='boardheader'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellpadding='4' cellspacing='1'>
  <tr class='title'>
    <td colspan='2'><?=$config['title']?></td>
  </tr>
  <tr class='main'>
    <td class='menu'>
    <a href='#recent_messages'><b>Read Messages</b></a><br />
    <a href='#post_a_message'><b>Post a Message</b></a><br />
    <a href='<?=$locations['search']?>'><b>Search</b></a><br />
    <a href='<?=$locations['image_browser']?>'><b>Image Browser</b></a><br />
<? if ($config['auth_required'] == true) { print "<a href='" . $locations['logout'] . "'><b>Logout</b></a><br />"; } ?>
    </td>
    <td rowspan='2' class='info'>
    <div align='center'><a href='http://www.angryhosting.com'><img src='<?=$config['logo']?>' alt='Message Board provided by AngryHosting.com!' border='0' /></a></div>
    <br /><b>Forum Guidelines</b><br />
<?=$config['guidelines']?>
    </td>
  </tr>
  <tr class='main'>
    <td class='menu'>
      <!-- NEWS ITEMS -->
      <?=$config['newsitem']?>
    </td>    
  </tr>
  <tr class='title'>
    <td colspan='2'><a name='recent_messages'>Messages</a></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<?
  print "Now showing messages started from ".date('m/d/Y h:i:s A', time() - $config['displaytime'])." - ".date('m/d/Y h:i:s A')."\n";
?>
    (<a href='<?=$locations['forum']?>?display_mode=1'>Go to Lite Mode</a>)<br /><br />
<?

  $count = 0;
  // read the counter
  if (file_exists($locations['counter'])) {
    $fp = fopen($locations['counter'],'r');
    $count = fread($fp,filesize($locations['counter']));
    fclose($fp);
  }
  print "Total posts today: <b>$count</b><br />\n";

  if (file_exists($locations['lastpost'])) {
    $fp = fopen($locations['lastpost'],'r');
    list ($last_id,$last_t,$last_author,$last_subject) = explode("\n",fread($fp,filesize($locations['lastpost'])));
    print "Newest post: <a href='" . $locations['forum'] . "?d=$last_id&amp;t=$last_t'>$last_subject</a> - <b>$last_author</b><br />\n";
  }

?>
    <div align='center'>
    [<a href='#post_a_message'><b>Post a Message</b></a>]
    [<a href='<?=$locations['faq']?>' target='faq'><b>Message Board FAQ</b></a>]
    [<a href='<?=$locations['search']?>'><b>Search</b></a>]
    [<a href='<?=$locations['image_browser']?>'><b>Image Browser</b></a>]
<? if ($config['auth_required'] == true) { print "[<a href='" . $locations['logout'] . "'><b>Logout</b></a>]"; } ?>
<? if ($config['auth_post_required'] == true && isset($_SESSION['uid']) && isset($_SESSION['authkey'])) { print "[<a href='" . $locations['forum'] . "?logout'><b>Logout</b></a>] [<a href='" . $locations['admin'] . "' onclick=\"window.open('" . $locations['admin'] . "' , 'admin' , 'toolbar=no, directories=no, location=no, resizable=no, status=yes, menubar=yes, scrollbars=no, width=300, height=200'); return false\"><b>Account Admin</b></a>]"; } ?>
    [<a href='<?=$locations['forum']?>'><b>Refresh</b></a>]
    </div>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>
<br />

<?
    if ($config['kricestatus'] == 1) {
    echo $config['krice'];
    }
?>

<!-- ROAR START
<?
  $roarsize = rand(10,25);
  if ($roarsize == 24) {
    $roarsize = 100;}
?>

<div align='center' style='font-size: <?=$roarsize;?>px; font-family:"Times New Roman"; 
color:red;'>roar</div>

 ROAR END -->
<br />
<?

}

function display_form($parent=null,$t=null,$thread=null) {

  global $config, $locations, $err_array, $val_array;


  if (!isset($parent) || !isset($t) || !isset($thread)) {

?>

<table width='100%' border='0' cellpadding='0' cellspacing='0'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellpadding='4' cellspacing='1'>
  <tr class='title'>
    <td colspan='2'><a name='post_a_message'>Post a Message</a></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<form action='<?=$locations['post']?>' method='post' onsubmit='return isFilled(this);'>
<?

  } else {

?>
  <tr class='main'>
    <td colspan='2'>
<form action='<?=$locations['post']?>' method='post' onsubmit='return isFilled(this);'>
<?

  }

?>
<table class='main'>
  <tr>
    <td>
<table class='main'>
  <tr>
    <td align='right' valign='top'><u>N</u>ame: </td>
    <td><input type='text' name='message_author' value='<? if (isset($val_array['message_author'])) print htmlspecialchars(stripslashes(stripslashes($val_array['message_author'])), ENT_QUOTES); elseif (isset($_COOKIE['cookie_name'])) print htmlspecialchars(stripslashes(stripslashes($_COOKIE['cookie_name'])), ENT_QUOTES); ?>' size='50' maxlength='50' accesskey='n' class='forminput' /></td>
  </tr>
  <tr>
    <td align='right' valign='top'>E-mail: </td>
    <td><input type='text' name='message_author_email' value='<? if (isset($val_array['message_author_email'])) print htmlspecialchars(stripslashes(stripslashes($val_array['message_author_email'])), ENT_QUOTES); elseif (isset($_COOKIE['cookie_email'])) print htmlspecialchars(stripslashes(stripslashes($_COOKIE['cookie_email'])), ENT_QUOTES); ?>' size='50' maxlength='50' class='forminput' /></td>
  </tr>
  <tr>
    <td align='right' valign='top'><u>S</u>ubject: </td>
    <td><input type='text' name='message_subject' value='<? if (isset($val_array['message_subject'])) print htmlspecialchars(stripslashes(stripslashes($val_array['message_subject'])), ENT_QUOTES); ?>' size='50' maxlength='150' accesskey='s' class='forminput' /></td>
  </tr>
  <tr>
    <td align='right' valign='top'><u>M</u>essage: </td>
    <td><textarea cols='45' rows='10' name='message_body' accesskey='m' class='forminput'><? if (isset($val_array['message_body'])) print htmlspecialchars(stripslashes(stripslashes($val_array['message_body'])), ENT_QUOTES); ?></textarea></td>
  </tr>
  <tr>
    <td width='100' align='right' valign='top'>Link URL: </td>
    <td>
    <input type='text' name='message_link_url[]' value='' placeholder='http://' size='50' maxlength='255' class='forminput' />
    <select name='num_links' onchange='hidden_links(this);' class='smallselect'><option value='1'>1</option><option value='2'>2</option><option value='3'>3</option><option value='4'>4</option><option value='5'>5</option><option value='6'>6</option><option value='7'>7</option><option value='8'>8</option><option value='9'>9</option><option value='10'>10</option></select>
    </td>
  </tr>
  <tr>
    <td align='right' valign='top'>Link Title: </td>
    <td><input type='text' name='message_link_title[]' value='' size='50' maxlength='75' class='forminput' /></td>
  </tr>
</table>
<span id='hidden_links_text'></span>
<table class='main'>
  <tr>
    <td width='100' align='right' valign='top'>Image URL: </td>
    <td>
    <input type='text' name='message_image_url[]' value='' placeholder='http://' size='50' maxlength='255' class='forminput' />
    <select name='num_images' onchange='hidden_images(this);' class='smallselect'><option value='1'>1</option><option value='2'>2</option><option value='3'>3</option><option value='4'>4</option><option value='5'>5</option><option value='6'>6</option><option value='7'>7</option><option value='8'>8</option><option value='9'>9</option><option value='10'>10</option></select>
    </td>
  </tr>
</table>
<span id='hidden_images_text'></span>
<table class='main'>
  <tr>
    <td width='100' align='right' valign='top'>Attach Image: </td>
    <td><a href='<?=$locations['attachimage']?>' onclick="window.open('<?=$locations['attachimage']?>' , 'attachimg2' , 'toolbar=no, directories=no, location=no, resizable=no, status=yes, menubar=yes, scrollbars=no, width=500, height=300'); return false">Click</a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
    <input type='submit' value='Submit' class='formsubmit' /><!-- <input type='button' value='Cancel' onclick='history.back();' class='formsubmit' /> -->
<?

  if (isset($parent) && isset($t) && isset($thread))
    print "<input type='hidden' name='parent' value='$parent' /><input type='hidden' name='t' value='$t' /><input type='hidden' name='thread' value='$thread' />\n";

?>
    </td>
  </tr>
  <tr>
    <td align='right' valign='top'>Use Script?</td>
    <td><input type='checkbox' name='use_referral' class='forminput' /> (Script only works with URLs ending in .jpg or .gif)</td>
  </tr>
  <tr>
    <td align='right' valign='top'>Warning:</td>
    <td><select name='warning' style='font-size: smaller'><option value=''>None</option><option value='warn-g'>Warning - Gross</option><option value='warn-n'>Warning - Nudity</option><option value="nsfw">NSFW</option></select></td>
  </tr>
<?
  if (!isset($_COOKIE['cookie_name']) && $config['require_captcha'] === true) {
?>
  <tr>
    <td align="right" valign="top">Vaildate:</td>
    <td><div class="g-recaptcha" data-sitekey="<?=$config['recaptcha_key']?>"></div></td>
    <script src='https://www.google.com/recaptcha/api.js'></script>
  </tr>
<? } ?>
</table>
    </td>
    <td valign='top'>
<table border='0' cellpadding='0' cellspacing='0' class='borderoutline'>
  <tr>
    <td>
<? if ($config['auth_post_required'] === true && (!isset($_SESSION['uid']) || !isset($_SESSION['authkey']))) { ?>
<table cellpadding='4' cellspacing='1'>
  <tr class='title'>
    <td colspan='2'>Authentication Required</td>
  </tr>
  <tr class='main'>
    <td>
<table class='main'>
  <tr<? if (isset($err_array['authentication'])) print " style='background-color: #ff9999'"; ?>>
    <td><u>U</u>sername: </td>
    <td><input type='text' name='username' size='16' value='<? if (isset($val_array['username'])) print htmlspecialchars(stripslashes(stripslashes($val_array['username'])), ENT_QUOTES); ?>' accesskey='u' /></td>
  </tr>
  <tr<? if (isset($err_array['authentication'])) print " style='background-color: #ff9999'"; ?>>
    <td><u>P</u>assword: </td>
    <td><input type='password' name='password' size='16' value='' accesskey='p' /></td>
  </tr>
  <tr>
    <td colspan='2'><a href="registration.php" onclick="window.open('registration.php' , 'register' , 'toolbar=no, directories=no, location=no, resizable=no, status=yes, menubar=yes, scrollbars=no, width=300, height=200'); return false">Not registered?</a><? if (isset($err_array['authentication']) && isset($err_array['general'])) print "<br /><span class='error'>" . $err_array['general'] . "</span>"; ?></td>
  </tr>
</table>
    </td>
  </tr>
</table>
<? } ?>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>
</form>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>
<?

  if (isset($parent) && isset($t) && isset($thread)) {

?>
    </td>
  </tr>
</table>
<?

  }

}

?>

</body>
</html>
