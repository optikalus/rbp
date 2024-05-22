<?php

// message board script v.3

// include the configuration file
require('config.inc.php');
require('datfile.inc.php');

// we don't accept get as a method of posting
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  header('Location: ' . $locations['forum']);
  exit();
}

// make sure we received all the form values
if (!isset($_POST['message_author']) || !isset($_POST['message_author_email']) ||
    !isset($_POST['message_subject']) || !isset($_POST['message_body']) ||
    !isset($_POST['message_link_url']) || !isset($_POST['message_link_title']) ||
    !isset($_POST['message_image_url'])) {

  print 'Did not receive all POST values.';
  exit();
}

$remote_addr = $_SERVER['REMOTE_ADDR'];
$banned_user = 'n';

// check banned list
if (isset($banned) && is_array($banned)) {

  if (isset($_COOKIE['chocolate'])) ban();

  if (isset($banned['usernames']) && isset($banned['usernames'][strtolower($_POST['message_author'])])) ban();

  //if (in_array($_SERVER['HTTP_USER_AGENT'], $banned['agents'])) ban();

  if (isset($banned['agents'])) {
    foreach ($banned['agents'] as $agent => $value) {
      if (strpos($_SERVER['HTTP_USER_AGENT'], $agent) === 0) ban();
    }
  }

  if (isset($banned['ips'])) {
    foreach ($banned['ips'] as $ip => $value) {
      if (strpos($remote_addr, $ip) === 0 && strtolower($_POST['message_author']) != 'epicutioner') ban();
    }
  }

}

// check for proxy posting
if (isset($_SERVER['HTTP_COMING_FROM']) ||
    isset($_SERVER['HTTP_X_COMING_FROM']) ||
    isset($_SERVER['HTTP_FORWARDED']) ||
    isset($_SERVER['HTTP_X_FORWARDED']) ||
    isset($_SERVER['HTTP_FORWARDED_FOR']) ||
    isset($_SERVER['HTTP_X_FORWARDED_FOR']) ||
    isset($_SERVER['HTTP_VIA'])) {

  if ($config['allow_proxy'] === false) {

    if (!in_array(strtolower($_POST['message_author']),$config['proxy_users'])) {

      print "Posting via proxy has been DISABLED.";
      exit();

    }
  }
}

if ($config['allow_tor'] === false) {

  if (exec("egrep '^" . $_SERVER['REMOTE_ADDR'] . "$' " . $config['tor_exits'])) ban();

  /*
  $octets = array();
  preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $_SERVER['REMOTE_ADDR'], $octets);

  $host = $octets[4] . "." . $octets[3] . "." . $octets[2] . "." . $octets[1] . ".443.209.204.180.66.ip-port.exitlist.torproject.org";
  if (gethostbyname($host) == '127.0.0.2') {
    $_POST['message_author'] = 'Troll';
  }
  */
}

// image verification
if (!isset($_COOKIE['cookie_name']) && !isset($_SERVER['HTTP_X_IS_APP']) && $config['require_captcha'] === true) {

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $config['recaptcha_url'] . '?secret=' . $config['recaptcha_secret'] . '&response=' . $_POST['g-recaptcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);

  $verify = curl_exec($ch);
  curl_close($ch);

  $result = json_decode($verify, true);
  if (!$result['success'])
  {
    print "Please <a href='#' onClick='history.go(-1)'>go back</a> and re-enter your reCAPTCHA.";
    exit();
  }
}

// no posts w/ <a href
if (stristr($_POST['message_body'], '<a href') || stristr($_POST['message_subject'], '<a href') || stristr($_POST['message_body'], '[url=')) {
  sleep(1);
  header('Location: ' . $locations['forum']);
  exit;
}

$video = 'n';

// preset the variables from the post
$message_author = break_html($_POST['message_author']);
$message_author_email = break_html($_POST['message_author_email']);
$message_subject = break_html($_POST['message_subject']);
$message_body = clear_html(break_html($_POST['message_body']));
$message_link_url = $_POST['message_link_url'];
$message_link_title = $_POST['message_link_title'];
$message_image_url = $_POST['message_image_url'];

$thread = null;
$parent = null;

// setup the table rotation scheme
if ($config['rotate_tables'] == 'daily')
  $t = date('mdy');
elseif ($config['rotate_tables'] == 'weekly')
  $t = strftime('%y%W');
elseif ($config['rotate_tables'] == 'monthly')
  $t = date('my');
elseif ($config['rotate_tables'] == 'yearly')
  $t = date('Y');
else
  $t = date('mdy');

if (isset($_POST['thread']) && preg_match('/^[0-9.]+$/',$_POST['thread']) &&
    isset($_POST['t']) && is_numeric($_POST['t']) &&
    isset($_POST['parent']) && is_numeric($_POST['parent'])) {

    $thread = $_POST['thread'];
    $parent = $_POST['parent'];
    $t = $_POST['t'];
}

// leave if the user didn't submit anything useful
if (strlen($message_author) < 1 || strlen($message_subject) < 1 ||
    preg_match("/^\s+$/",$message_author) || preg_match("/^\s+$/",$message_subject) ||
    preg_match("/\[url\=/",$message_subject)) {

  header('Location: ' . $locations['forum']);
  exit();
}

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],'mysqli_connect(' . $config['db_host'] . ',' . $config['db_user'] . ',' . $config['db_pass'] . ')' . "\n".mysqli_error());

// handle authentication if necessary
$authenticated = false;
if ($config['auth_required'] == true || isset($_COOKIE['auth'])) {

  // begin a session
  session_start();

  if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    $query = 'select username from ' . $locations['auth_table'] . ' where username = "' . $_SESSION['username'] . '" and password = "' . $_SESSION['password'] . '"';
    $result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
    if (mysqli_num_rows($result) != 1) {
      // destroy the erroneous session
      session_destroy();
      // leave
      header('Location: ' . $locations['login']);
      exit();
    } else {
      $authenticated = true;
    }
  } else {
    // leave
    header('Location: ' . $locations['login']);
    exit();
  }

}

// authentication
if ($config['auth_post_required'] == true) {

  // start session (if not already started)
  if (!ini_get('session.auto_start')) {
    session_name($config['session_name']);
    session_save_path($locations['session_path']);
    ini_set('session.gc_maxlifetime','604800');
    session_start();
  }

  if ((!isset($_SESSION['uid']) || !isset($_SESSION['authkey'])) && (!isset($_POST['username']) || !isset($_POST['password'])))
    error_redirect(array('authentication' => true, 'general' => 'You must log in to post'));

  if (isset($_POST['username']) && isset($_POST['password'])) {

    $query = 'select user_id, md5(concat(user_id, username, password)) as authkey from ' . $locations['auth_users_table'] . ' where username = "' . $_POST['username'] . '" and password = md5("' . $_POST['password'] . '") and active = "y" and queued = "n"';
    $result = mysqli_query($mysqli_link, $query);

    // authorized
    if (mysqli_num_rows($result) === 1) {

      $auth = mysqli_fetch_array($result);
      $_SESSION['uid'] = $auth['user_id'];
      $_SESSION['authkey'] = $auth['authkey'];

      $query = 'update ' . $locations['auth_users_table'] . ' set last_login = now(), post_count = post_count + 1 where user_id = "' . $_SESSION['uid'] . '"';
      mysqli_query($mysqli_link, $query);

    // not authorized
    } else {

      error_redirect(array('authentication' => true, 'general' => 'Invalid Username/Password'));

    }

  } else {

    $query = 'select user_id, md5(concat(user_id, username, password)) as authkey from ' . $locations['auth_users_table'] . ' where user_id = "' . $_SESSION['uid'] . '" and active = "y" and queued = "n"';
    $result = mysqli_query($mysqli_link, $query);

    if (mysqli_num_rows($result) === 1) {

      $auth = mysqli_fetch_array($result);
      if ($auth['authkey'] != $_SESSION['authkey']) {

	// invalid session
	session_destroy();
	error_redirect(array('authentication' => true, 'general' => 'Invalid Session'));

      } else {

	$query = 'update ' . $locations['auth_users_table'] . ' set last_login = now(), post_count = post_count + 1 where user_id = "' . $_SESSION['uid'] . '"';
	mysqli_query($mysqli_link, $query);

      }

    } else {

      // invalid session
      session_destroy();
      error_redirect(array('authentication' => true, 'general' => 'Invalid Session'));

    }
  }
}

// set the tablename ($t should always be the suffix, it is preset above or supplied by the post)
$tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

$user_id = null;
if (isset($_SESSION['uid']))
  $user_id = $_SESSION['uid'];

// deny spam
if ($_POST['warning'] == 'bot') {
  sleep(1);
  header('Location: ' . $locations['forum']);
  exit;
}

// check to see if new username attempting to post something with a flag
if (!isset($_COOKIE['cookie_name']) && isset($_POST['warning']) && $_POST['warning'] != '' && ($_POST['warning'] == 'warn-g' || $_POST['warning'] == 'warn-n' || $_POST['warning'] == 'nsfw')) {
  $query = 'select distinct message_author from ' . $tablename . ' where message_author = "' . escape(alter_username($message_author)) . '" and banned = "n"';
  $result = mysqli_query($mysqli_link, $query);
  if (mysqli_num_rows($result) === 0) {
    ban();
    sleep(1);
    header('Location: ' . $locations['forum']);
    exit;
  }
}

$debug = false;

/*
if ($_SERVER['REMOTE_ADDR'] == '10.1.0.216')
  $debug = true;
 */

$debug_strings = array();

// manual auto incrementation
$query = 'select case when max(id) is null then 1 else max(id) + 1 end as id from ' . $tablename . ' where t = "' . $t . '"';
$result = mysqli_query($mysqli_link, $query);

// authorized
if (mysqli_num_rows($result) === 1) {
  $res_id = mysqli_fetch_array($result);
  $id = $res_id['id'];
} else {
  $id = 1;
}

// handle transient
$transient = 'n';
if (isset($_POST['transient']))
  $transient = 'y';

// insert the post
$query = 'insert into ' . $tablename . ' (id,' . (!$config['rotate_tables'] ? 't,' : '') . 'message_author,message_author_email,message_subject,message_body,date,ip,user_id,banned,transient) ' .
	 'values (' . $id . ',' . (!$config['rotate_tables'] ? $t . ',' : '') . '"' . escape(alter_username($message_author)) . '","' . escape($message_author_email) . '","' . escape($message_subject) . '","' . escape($message_body) . '",now(),"' . $remote_addr . '",nullif("' . $user_id . '",""),"' . $banned_user . '","' . $transient . '")';

array_push($debug_strings, $query);

$msc = microtime(true);
mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error($mysqli_link));
$msc = microtime(true) - $msc;

array_push($debug_strings, " took " . ($msc * 1000) . " ms<br />");

$insert_id = mysqli_insert_id($mysqli_link);

// preset the link/image enum
$link = 'n';
$image = 'n';

// insert the link url
if (isset($message_link_url) && is_array($message_link_url)) {

  foreach ($message_link_url as $num => $link_url) {

    $link_url = break_html($link_url);
    $link_title = break_html($message_link_title[$num]);

    // check link url
    if ($link_url != 'http://' && check_url($link_url)) {

      // embed youtube videos
      $youtube_video_id = null;
      $embed_string = null;

      if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $link_url, $youtube_video_id)) {

	$video = 'y';

      }

      $query = 'insert into ' . $locations['links_table'] . ' (id,t,link_url,link_title) ' .
	       'values (' . $insert_id . ',' . $t . ',"' . escape($link_url) . '","' . escape($link_title) . '")';
      mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

      $link = 'y';

    }
  }
}

// insert the image url
if (isset($message_image_url) && is_array($message_image_url)) {

  foreach ($message_image_url as $num => $image_url) {

    $image_url = break_html($image_url);

    // check image url
    if ($image_url != 'http://' && check_url($image_url)) {

      // prepend the referral location to the image url if requested
      if (isset($_POST['use_referral']))
	$image_url = $config['referrallocation'] . '?url=' . $image_url;

      $query = 'insert into ' . $locations['images_table'] . ' (id,t,image_url) ' .
	       'values (' . $insert_id . ',' . $t . ',"' . escape($image_url) . '")';
      mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());

      $image = 'y';

    }
  }
}

// set the parent and thread values
$query = 'update ' . $tablename . ' set ' .
	 'parent = case when "' . $parent . '" = "" then ' . $insert_id . ' else "' . $parent . '" end, ' .
	 'thread = case when "' . $thread . '" = "" then lpad(' . $insert_id . ',5,"0") else concat("' . $thread . '",".",lpad(' . $insert_id . ',5,"0")) end, ' .
	 'link = "' . $link . '", video = "' . $video . '", image = "' . $image . '" ' .
	 'where id = ' . $insert_id . (!$config['rotate_tables'] ? ' and t = ' . $t : '');

array_push($debug_strings, $query);

$msc = microtime(true);
mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
$msc = microtime(true) - $msc;

array_push($debug_strings, " took " . ($msc * 1000) . " ms<br />");

// account for warning preset
if (isset($_POST['warning']) && $_POST['warning'] != '' && ($_POST['warning'] == 'warn-g' || $_POST['warning'] == 'warn-n' || $_POST['warning'] == 'nsfw')) {

  $query = 'insert into ' . $locations['flags_table'] . ' (id, t, votes, score, type) values ("' . $insert_id . '", "' . $t . '", 5, 0, "' . $_POST['warning'] . '")';
  mysqli_query($mysqli_link, $query);

  $query = 'update ' . $tablename . ' set type = "' . $_POST['warning'] . '" where id = "' . $insert_id . '"' . (!$config['rotate_tables'] ? ' and t = "' . $t . '"' : '');
  mysqli_query($mysqli_link, $query);

}

// give the user a cookie for such a good post
setcookie('cookie_name',$_POST['message_author'],time()+(60*60*24*30),'/');
setcookie('cookie_email',$_POST['message_author_email'],time()+(60*60*24*30),'/');

if ($config['edit_mode_enabled']) {
    setcookie('cookie_edit', sha1($insert_id . '_' . $t . '_' . $config['edit_secure_value'] . $_SERVER['HTTP_USER_AGENT']), time() + ($config['edit_time_limit']), '/', null, null, true);
}

// now for the hard part

write_dat_file();


// transient cleanup
$query = 'select id from ' . $tablename . ' where t = "' . $t . '" and transient = "y" and id = parent and date <= date_sub(now(), interval ' . $config['displaytime'] . ' second)';
array_push($debug_strings, $query);
$result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
while ($parent = mysqli_fetch_array($result)) {
  $query = 'delete from ' . $tablename . ' where t = "' . $t . '" and parent = "' . $parent['id'] . '"';
  array_push($debug_strings, $query);
  mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error($mysqli_link));
}

$msc = microtime(true) - $msc;
array_push($debug_strings, "dat file processing took " . ($msc * 1000) . " ms<br />");
if ($debug === true) {
  print_r($debug_strings);
  exit();
}

// we're done!
if (!isset($_POST['beta'])) {
  header('Location: ' . $locations['forum']);
} else {
  print 'OK';
}
exit(0);

// this function checks a url for basic validity
function check_url($url) {
  $parsed_url = array();
  $parsed_url = parse_url($url);
  if (!($parsed_url['scheme'] && $parsed_url['host']) || $parsed_url['scheme'] == 'file')
    return false;
  else
    return true;
}

// this function breaks HTML tags
function break_html($str) {

  $bad = array('&','>','<');
  $good = array('&amp;','&gt;','&lt;');

  return(str_replace($bad,$good,$str));

}

// this function specifies allowed HTML tags
function clear_html($str) {

  $bad = array('&lt;b&gt;','&lt;/b&gt;','[b]','[/b]','&lt;i&gt;','&lt;/i&gt;','&lt;pre&gt;','&lt;/pre&gt;','[pre]','[/pre]','[code]','[/code]','_EMBEDAMP_','_EMBEDLT_','_EMBEDGT_');
  $good = array('<b>','</b>','<b>','</b>','<i>','</i>','<pre>','</pre>','<pre>','</pre>','<pre>','</pre>','&','<','>');

  return(str_replace($bad,$good,$str));

}

// process embeded videos
function parse_embed($text) {

  global $video;

  require_once('simple_html_dom.php');

  $valid_embed_domains = '';
  foreach (array('youtube.com','vimeo.com','ted.com','veoh.com','viddler.com','qik.com','revision3.com','hulu.com','mtvnservices.com','cbs.com','nbc.com','dailymotion.com') as $domain)
    $valid_embed_domains .= $domain . '|';
  $valid_embed_domains = rtrim($valid_embed_domains, '|');

  $dom = str_get_html($text);

  foreach ($dom->find('object') as $embed_object) {

    $sources = array();
    $cleaned_embed = '';

    if ($embed_object->getAttribute('src'))
      array_push($sources, $embed_object->getAttribute('src'));

    foreach ($embed_object->find('embed') as $embed_object_embed) {
      if ($embed_object_embed->getAttribute('src'))
        array_push($sources, $embed_object_embed->getAttribute('src'));
    }

    // NBC
    if ($embed_object->getAttribute('data'))
      array_push($sources, $embed_object->getAttribute('data'));

    if ($sources) {

      $match = true;

      foreach ($sources as $source) {
        if (preg_match("/http[s]*:\/\/.*?($valid_embed_domains)\//i", $source, $matches) === 0)
          $match = false;
      }

      if ($match === true) {
        $cleaned_embed = str_replace(array('&','>','<'), array('_EMBEDAMP_','_EMBEDGT_','_EMBEDLT_'), $embed_object->outertext);
	$video = 'y';
      } else
        $cleaned_embed = '[EMBED ERROR: INVALID SOURCE]';

    } else {

      $cleaned_embed = '[EMBED ERROR: INVALID SOURCE SYNTAX]';

    }

    $embed_object->outertext = $cleaned_embed;

  }

  // find leftover embeds
  foreach ($dom->find('embed') as $embed_object) {

    $sources = array();
    $cleaned_embed = '';

    if ($embed_object->getAttribute('src'))
      array_push($sources, $embed_object->getAttribute('src'));

    if ($sources) {

      $match = true;

      foreach ($sources as $source) {
        if (preg_match("/http[s]*:\/\/.*?($valid_embed_domains)\//i", $source) === 0)
          $match = false;
      }

      if ($match === true) {
        $cleaned_embed = str_replace(array('&','>','<'), array('_EMBEDAMP_','_EMBEDGT_','_EMBEDLT_'), $embed_object->outertext);
	$video = 'y';
      } else
        $cleaned_embed = '[EMBED ERROR: INVALID SOURCE]';

    } else {

      $cleaned_embed = '[EMBED ERROR: INVALID SOURCE SYNTAX]';

    }

    $embed_object->outertext = $cleaned_embed;

  }

  return($dom);

}

// this function handles banning of users
function ban() {
  global $locations, $config, $banned_user;
  setcookie('chocolate',time(),time() + (60*60*24*365),'/');
  //header('Location: ' . $locations['forum']);
  //exit();
  $banned_user = 'y';
}

?>
