<?

// message board script v.3

// include the configuration file
require('config.inc.php');

// we don't accept get as a method of posting
if ($_SERVER[REQUEST_METHOD] == 'GET') {

  $message = "Invalid request 'GET', bailing\n\n".
	     "POST:\n".
	     print_r($_POST, true).
	     "\n\n".
	     "GET:\n".
	     print_r($_GET, true).
	     "\n\n".
	     "SERVER:\n".
	     print_r($_SERVER, true).
	     "\n\n".
	     "COOKIE:\n".
	     print_r($_COOKIE, true).
	     "\n\n";
  #mail($config[admin_email],"Error in $_SERVER[SCRIPT_FILENAME]",$message,"From: \"Script Debugging SubSystem\" <$config[admin_email]>\n");
  header("Location: $locations[forum]");
  exit();
}

// make sure we received all the form values
if (!isset($_POST[message_author]) || !isset($_POST[message_author_email]) ||
    !isset($_POST[message_subject]) || !isset($_POST[message_body]) ||
    !isset($_POST[message_link_url]) || !isset($_POST[message_link_title]) ||
    !isset($_POST[message_image_url])) {

  $message = "Did not receive all the required form values\n\n".
	     "POST:\n".
	     print_r($_POST, true).
	     "\n\n".
	     "GET:\n".
	     print_r($_GET, true).
	     "\n\n".
	     "SERVER:\n".
	     print_r($_SERVER, true).
	     "\n\n".
	     "COOKIE:\n".
	     print_r($_COOKIE, true).
	     "\n\n";

    print "eat poop";

  #mail($config[admin_email],"Error in $_SERVER[SCRIPT_FILENAME]",$message,"From: \"Script Debugging SubSystem\" <$config[admin_email]>\n");
  #header("Location: $locations[forum]");
  exit();

}

$remote_addr = $_SERVER[REMOTE_ADDR];

// check banned list
#if (isset($_COOKIE[vanilla])) ban();

// protect john
if (strtolower($_POST[message_author_email]) == 'jrjr@snip.net') ban();
if (stristr(strtolower($_POST[message_author]), 'goodwork')) ban();

if (isset($banned) && is_array($banned)) {

  if (stristr(strtolower($_POST[message_author]), 'guy')) ban();

  if (in_array(strtolower($_POST[message_author]), $banned[usernames])) ban();

  if (isset($banned[ips])) {
    foreach ($banned[ips] as $ip => $value) {
      if (strpos($remote_addr, $ip) === 0 && strtolower($_POST[message_author]) != 'epicutioner') ban();
    }
  }

}

// check for proxy posting
if (isset($_SERVER[HTTP_COMING_FROM]) ||
    isset($_SERVER[HTTP_X_COMING_FROM]) ||
    isset($_SERVER[HTTP_FORWARDED]) ||
    isset($_SERVER[HTTP_X_FORWARDED]) ||
    isset($_SERVER[HTTP_FORWARDED_FOR]) ||
    isset($_SERVER[HTTP_X_FORWARDED_FOR]) ||
    isset($_SERVER[HTTP_VIA])) {

  /*$proxylog = fopen("/home/bryan/rbp/html/proxylog.php","a");
  fwrite($proxylog, print_r($_SERVER,true) . "\n\n");
  fclose($proxylog);
   */
  if ($config[allow_proxy] === false) {

    if (!in_array(strtolower($_POST[message_author]),$config[proxy_users])) {

    #mail($config[admin_email],"Error in $_SERVER[SCRIPT_FILENAME]","Proxy post denied from $_POST[message_author] [$_SERVER[REMOTE_ADDR]\n","From: \"Script Debugging SubSystem\" <$config[admin_email]>\n");
      print "Posting via proxy has been DISABLED.";
      exit();

    }

  }

}

if ($config['allow_tor'] === false) {

  $octets = array();
  preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $_SERVER['REMOTE_ADDR'], $octets);

  $host = $octets[4] . "." . $octets[3] . "." . $octets[2] . "." . $octets[1] . ".80.210.204.180.66.ip-port.exitlist.torproject.org";
  if (gethostbyname($host) == '127.0.0.2') {
    $_POST['message_author'] = 'Charles';
    mail('optikal@f0e.net',"Error in $_SERVER[SCRIPT_FILENAME]","TOR post denied from $_POST[message_author] [$_SERVER[REMOTE_ADDR]\n\n$_POST[message_subject]\n$_POST[message_body]\n","From: \"Script Debugging SubSystem\" <$config[admin_email]>\n");
      #print "Posting via TOR has been DISABLED.";
      #exit();
  }

}

// image verification
if (!isset($_COOKIE[cookie_name]) && !isset($_SERVER['HTTP_X_IS_APP'])) {
  require_once("b2evo_captcha.config.php");
  require_once("b2evo_captcha.class.php");
  $captcha = new b2evo_captcha($CAPTCHA_CONFIG);
  if (!$captcha->validate_submit($_POST["captcha_image"], $_POST["captcha_verify"])) {
    print "Invalid image verification response. Please <a href='#' onclick='history.go(-1)'>try again</a>.\n";
    exit();
  }
}

// no posts w/ <a href
if (stristr($_POST[message_body], '<a href') || stristr($_POST[message_subject], '<a href') || stristr($_POST[message_body], '[url=')) {
  sleep(1);
  header("Location: $locations[forum]");
  exit;
}

$video = 'n';

// preset the variables from the post
$message_author = break_html($_POST[message_author]);
$message_author_email = break_html($_POST[message_author_email]);
$message_subject = break_html($_POST[message_subject]);
$message_body = clear_html(break_html($_POST[message_body]));
$message_link_url = $_POST[message_link_url];
$message_link_title = $_POST[message_link_title];
$message_image_url = $_POST[message_image_url];

// handle bitches
#if ($_SERVER[REMOTE_ADDR] == '24.44.141.87' || $_SERVER[REMOTE_ADDR] == '75.83.244.135' || $_SERVER[REMOTE_ADDR] == '85.214.73.63' || isset($_COOKIE['pussy'])) {
#  $words = array("arf","woof","meow","oink","gobble","cluck","rabble","blah");
#  $message_subject = preg_replace("/\w+/", $words[rand(0, sizeof($words))], $message_subject);
#  $message_body = preg_replace("/\w+/", $words[rand(0, sizeof($words))], $message_body);
#}

#if ($_SERVER['REMOTE_ADDR'] == '70.233.176.76' || isset($_COOKIE['cody'])) {
#  setcookie('cody', '1', time()+(60*60*24*30),'/');
#  $message_subject = '&lt;random bullshit&gt;';
#  $message_body = '';
#}

$message_subject = preg_replace('/\bswine flu\b/i', 'balls', $message_subject); 
#if ($_SERVER[REMOTE_ADDR] == '75.83.244.135') {
#  $message_subject = preg_replace(array('/\bcharles\b/i', '/\Charl4s\b/i', '/\bchuck\b/i', '/\bc\s*h\s*a\s*r\s*l\s*e\s*s\b/i'), array('pussy','pussy','pussy','pussy'), $message_subject);
#  $message_subject = preg_replace('/ - .+$/',' - pussy', $message_subject);
#}

$thread = null;
$parent = null;

// setup the table rotation scheme
if ($config[rotate_tables] == 'daily')
  $t = date('mdy');
elseif ($config[rotate_tables] == 'weekly')
  $t = strftime('%y%W');
elseif ($config[rotate_tables] == 'monthly')
  $t = date('my');
elseif ($config[rotate_tables] == 'yearly')
  $t = date('Y');
else
  $t = date('mdy');

if (isset($_POST[thread]) && preg_match('/^[0-9.]+$/',$_POST[thread]) &&
    isset($_POST[t]) && is_numeric($_POST[t]) &&
    isset($_POST[parent]) && is_numeric($_POST[parent])) {

    $thread = $_POST[thread];
    $parent = $_POST[parent];
    $t = $_POST[t];

}

// leave if the user didn't submit anything useful
if (strlen($message_author) < 1 || strlen($message_subject) < 1 ||
    preg_match("/^\s+$/",$message_author) || preg_match("/^\s+$/",$message_subject) ||
    preg_match("/\[url\=/",$message_subject)) {

  #mail($config[admin_email],"Error in $_SERVER[SCRIPT_FILENAME]","Received '$message_author' as message_author -- too short\n","From: \"Script Debugging SubSystem\" <$config[admin_email]>\n");
  header("Location: $locations[forum]");
  exit();

}

// establish a connection with the database or notify an admin with the error string
$mysql_link = mysql_connect($config[db_host],$config[db_user],$config[db_pass]) or error($config[db_errstr],$config[admin_email],"mysql_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysql_error());
mysql_select_db($config[db_name],$mysql_link) or error($config[db_errstr],$config[admin_email],"mysql_select_db($config[db_name])\n".mysql_error());

// handle authentication if necessary
$authenticated = false;
if ($config[auth_required] == true || isset($_COOKIE[auth])) {

  // begin a session
  session_start();

  if (isset($_SESSION[username]) && isset($_SESSION[password])) {
    $query = "select username from $locations[auth_table] where username = '$_SESSION[username]' and password = '$_SESSION[password]'";
    $result = mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());
    if (mysql_num_rows($result) != 1) {
      // destroy the erroneous session
      session_destroy();
      // leave
      header("Location: $locations[login]");
      exit();
    } else {
      $authenticated = true;
    }
  } else {
    // leave
    header("Location: $locations[login]");
    exit();
  }

}

// authentication
if ($config[auth_post_required] == true) {

  // start session (if not already started)
  if (!ini_get('session.auto_start')) {
    session_name($config[session_name]);
    session_save_path($locations[session_path]);
    ini_set('session.gc_maxlifetime','604800');
    session_start();
  }

  if ((!isset($_SESSION[uid]) || !isset($_SESSION[authkey])) && (!isset($_POST[username]) || !isset($_POST[password])))
    error_redirect(array('authentication' => true, 'general' => 'You must log in to post'));

  if (isset($_POST[username]) && isset($_POST[password])) {

    $query = "select user_id, md5(concat(user_id, username, password)) as authkey from $locations[auth_users_table] where username = '$_POST[username]' and password = md5('$_POST[password]') and active = 'y' and queued = 'n'";
    $result = mysql_query($query, $mysql_link);

    // authorized
    if (mysql_num_rows($result) === 1) {

      $_SESSION[uid] = mysql_result($result, 0, 'user_id');
      $_SESSION[authkey] = mysql_result($result, 0, 'authkey');

      $query = "update $locations[auth_users_table] set last_login = now(), post_count = post_count + 1 where user_id = '$_SESSION[uid]'";
      mysql_query($query, $mysql_link);

    // not authorized
    } else {

      error_redirect(array('authentication' => true, 'general' => 'Invalid Username/Password'));

    }

  } else {

    $query = "select user_id, md5(concat(user_id, username, password)) as authkey from $locations[auth_users_table] where user_id = '$_SESSION[uid]' and active = 'y' and queued = 'n'";
    $result = mysql_query($query, $mysql_link);

    if (mysql_num_rows($result) === 1) {

      if (mysql_result($result, 0, 'authkey') != $_SESSION[authkey]) {

	// invalid session
	session_destroy();
	error_redirect(array('authentication' => true, 'general' => 'Invalid Session'));

      } else {

	$query = "update $locations[auth_users_table] set last_login = now(), post_count = post_count + 1 where user_id = '$_SESSION[uid]'";
	mysql_query($query, $mysql_link);

      }

    } else {

      // invalid session
      session_destroy();
      error_redirect(array('authentication' => true, 'general' => 'Invalid Session'));

    }

  }

}

// set the tablename ($t should always be the suffix, it is preset above or supplied by the post)
$tablename = $locations[posts_table].'_'.$t;

// forums cancer

/*
// did troll try to change their IP?
if ($_COOKIE['trolololol']) {
  $query = "select ip from trolls where ip = '" . $remote_addr . "'";
  $result = mysql_query($query, $mysql_link);
  if (mysql_num_rows($result) == 0) {
    $query = "insert into trolls (ip, name, first_troll, last_troll, num_fed, fed_num) values ('" . $remote_addr . "', '" . $message_author . "', now(), now(), 1, 0)";
    mysql_query($query, $mysql_link);
  }
}

// is thread a troll?
if ($thread != '') {
  $query = "select ip from $tablename where thread = '" . $thread . "' and message_author = '<font color=\"teal\">Troll</font>'";
  $result = mysql_query($query, $mysql_link);
  if (mysql_num_rows($result) > 0) {
    $query = "select ip from trolls where ip = '" . mysql_result($result, 0, 'ip') . "'";
    $trollres = mysql_query($query, $mysql_link);
    if (mysql_num_rows($trollres) > 0) {
      // don't feed the trolls!
      $query = "update trolls set fed_num = fed_num + 1 where ip = '" . mysql_result($result, 0, 'ip') . "'";
      mysql_query($query, $mysql_link);
      $query = "select * from trolls where ip = '" . $remote_addr . "'";
      $result = mysql_query($query, $mysql_link);
      if (mysql_num_rows($result) > 0) {
	$query = "update trolls set num_fed = num_fed + 1, last_troll = now() where ip = '" . $remote_addr . "'";
	mysql_query($query, $mysql_link);
      } else {
	$query = "insert into trolls (ip, name, first_troll, last_troll, num_fed, fed_num) values ('" . $remote_addr . "', '" . $message_author . "', now(), now(), 1, 0)";
        mysql_query($query, $mysql_link);
      }
    } // end troll
    setcookie('trolololol','true',time()+(60*60*24*30),'/');
    $_COOKIE['trolololol'] = 'true';
  } // end thread lookup
} // end thread
*/
$user_id = null;
if (isset($_SESSION[uid]))
  $user_id = $_SESSION[uid];

// insert the post
$query = "insert into $tablename (message_author,message_author_email,message_subject,message_body,date,ip,user_id) ".
	 "values ('".escape(alter_username($message_author))."','".escape($message_author_email)."','".escape($message_subject)."','".escape($message_body)."',now(),'$remote_addr',nullif('$user_id',''))";
mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

$insert_id = mysql_insert_id();

// preset the link/image enum
$link = 'n';
$image = 'n';

// insert the auth_post id
if ($authenticated === true && isset($_SESSION[level]) && $_SESSION[level] == 'admin') {
  $query = "insert into $locations[auth_posts_table] (id, t) values ('$insert_id', '$t')";
  mysql_query($query, $mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());
}

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

      if (preg_match('/youtube\.com\/watch\?v=(.+)/', $link_url, $youtube_video_id)) {

	//$embed_string = '<br /><object width="640" height="385"><param name="movie" value="http://www.youtube.com/v/'.escape($youtube_video_id[1]).'&rel=1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/'.escape($youtube_video_id[1]).'&rel=1" type="application/x-shockwave-flash" wmode="transparent" width="640" height="385"></embed></object><br />';

        // append embed string to bottom of message
	//$query = "update $tablename set message_body = concat(message_body, '$embed_string') where id = '$insert_id'";
	//mysql_query($query, $mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

	$video = 'y';

      }

      $query = "insert into $locations[links_table] (id,t,link_url,link_title) ".
	       "values ($insert_id,$t,'".escape($link_url)."','".escape($link_title)."')";
      mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

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
      if (isset($_POST[use_referral]))
	$image_url = "$config[referrallocation]?url=$image_url";

      $query = "insert into $locations[images_table] (id,t,image_url) ".
	       "values ($insert_id,$t,'".escape($image_url)."')";
      mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

      $image = 'y';

    }

  }

}

// set the parent and thread values
$query = "update $tablename set ".
	 "parent = case when '$parent' = '' then $insert_id else '$parent' end, ".
	 "thread = case when '$thread' = '' then lpad($insert_id,5,'0') else concat('$thread','.',lpad($insert_id,5,'0')) end, ".
	 "link = '$link', video = '$video', image = '$image' ".
	 "where id = $insert_id";
mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

// account for warning preset
if (isset($_POST[warning]) && $_POST[warning] != '' && ($_POST[warning] == 'warn-g' || $_POST[warning] == 'warn-n' || $_POST['warning'] == 'nsfw')) {

  $query = "insert into $locations[flags_table] (id, t, votes, score, type) values ('$insert_id', '$t', 5, 0, '$_POST[warning]')";
  mysql_query($query, $mysql_link);

  $query = "update $tablename set type = '$_POST[warning]' where id = '$insert_id'";
  mysql_query($query, $mysql_link);

}

// give the user a cookie for such a good post
setcookie('cookie_name',$_POST[message_author],time()+(60*60*24*30),'/');
setcookie('cookie_email',$_POST[message_author_email],time()+(60*60*24*30),'/');

// now for the hard part

// Check for existing $datfile.lock 
if (file_exists("$locations[datfile].lock")) {
  for ($i = 0; $i < 3; $i++) {
    sleep(1);
    if (!file_exists("$locations[datfile].lock"))
      break;
  }
}

// don't allow user to abort
ignore_user_abort(true);

// create a lock
touch("$locations[datfile].lock");

/*
// increment the counter
$count = 0;

if (file_exists($locations[counter])) {
  // read old count
  $fp = fopen($locations[counter],'r');
  $count = fread($fp, filesize($locations[counter]));
  fclose($fp);
}

// reset the counter if it's mtime is not today (new day)
if (file_exists($locations[counter]) && date('m d y') != date('m d y', filemtime($locations[counter])))
  $count = 0;
*/
// write count + 1
$fp = fopen($locations[counter],'w');
fwrite($fp,$insert_id);
fclose($fp);

// update the last post
$fp = fopen($locations[lastpost],'w');
fwrite($fp,"$insert_id\n$t\n".alter_username(deescape($message_author))."\n".deescape($message_subject)."\n");
fclose($fp);

// create the forum file
$fp = fopen($locations[datfile],'w') or error("Unable to open $locations[datfile] for writing",$config[admin_email],"Unable to open $locations[datfile] for writing");

// create the forum_lite file
$fp_lite = fopen($locations[datfile_lite],'w') or error("Unable to open $locations[datfile_lite] for writing",$config[admin_email],"Unable to open $locations[datfile_lite] for writing");

// create the forum_neat file
$fp_neat = fopen($locations[datfile_neat],'w') or error("Unable to open $locations[datfile_neat] for writing",$config[admin_email],"Unable to open $locations[datfile_neat] for writing");

// create the forum_neat file
$fp_json = fopen($locations[jsonfile],'w') or error("Unable to open $locations[jsonfile] for writing",$config[admin_email],"Unable to open $locations[jsonfile] for writing");

// re-setup the table rotation scheme
if ($config[rotate_tables] == 'daily')
  $t = date('mdy');
elseif ($config[rotate_tables] == 'weekly')
  $t = strftime('%y%W');
elseif ($config[rotate_tables] == 'monthly')
  $t = date('my');
elseif ($config[rotate_tables] == 'yearly')
  $t = date('Y');
else
  $t = date('mdy');

// reset the table name
$tablename = $locations[posts_table].'_'.$t;

// query for the rows to output
$query = "select $tablename.id,$tablename.parent,$tablename.thread,$tablename.message_author,$tablename.message_subject, ".
	 "date_format($tablename.date,'%m/%d/%Y - %l:%i:%s %p') as date, date_format($tablename.date, '%l:%i:%s %p') as date_sm, '$t' as t, ".
	 "$tablename.link, $tablename.image, $tablename.video, ifnull($tablename.score, 'null') as score, ifnull($tablename.type, 'null') as type, ".
	 "case when $tablename.message_body = '' then 'n' else 'y' end as body, $tablename.message_body ".
	 "from $tablename ".
	 "where unix_timestamp($tablename.date) > (unix_timestamp(now()) - $config[displaytime]) ".
	 "order by $tablename.parent desc,$tablename.thread asc limit $config[maxrows]";

// see if we need to bond another table
if (($config[rotate_tables] == 'daily' && date('mdy',time() - $config[displaytime]) != date('mdy')) ||
    ($config[rotate_tables] == 'weekly' && strftime('%y%W',time() - $config[displaytime]) != strftime('%y%W')) ||
    ($config[rotate_tables] == 'monthly' && date('my',time() - $config[displaytime]) != date('my')) ||
    ($config[rotate_tables] == 'yearly' && date('Y',time() - $config[displaytime]) != date('Y')) ||
    (date('mdy',time() - $config[displaytime]) != date('mdy'))) {

  if ($config[rotate_tables] == 'daily')
    $t = date('mdy', time() - $config[displaytime]);
  elseif ($config[rotate_tables] == 'weekly')
    $t = strftime('%y%W', time() - $config[displaytime]);
  elseif ($config[rotate_tables] == 'monthly')
    $t = date('my', time() - $config[displaytime]);
  elseif ($config[rotate_tables] == 'yearly')
    $t = date('Y', time() - $config[displaytime]);
  else
    $t = date('mdy', time() - $config[displaytime]);

  $tablename = $locations[posts_table].'_'.$t;

  $query = "(".$query.") union (".
	   "select $tablename.id,$tablename.parent,$tablename.thread,$tablename.message_author,$tablename.message_subject, ".
	   "date_format($tablename.date,'%m/%d/%Y - %l:%i:%s %p') as date, date_format($tablename.date, '%l:%i:%s %p') as date_sm, '$t' as t, ".
	   "$tablename.link, $tablename.image, $tablename.video, ifnull($tablename.score, 'null') as score, ifnull($tablename.type, 'null') as type, ".
	   "case when $tablename.message_body = '' then 'n' else 'y' end as body, $tablename.message_body ".
	   "from $tablename ".
	   "where unix_timestamp($tablename.date) > (unix_timestamp(now()) - $config[displaytime]) ".
	   "order by $tablename.parent desc,$tablename.thread asc limit $config[maxrows]".
	   ") order by t desc, parent desc, thread asc limit $config[maxrows]";
}

$results = mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

if (mysql_num_rows($results) == 0)
  error('',$config[admin_email],'ERROR: No rows to output');

$lastthread = array();
$lastnormalthread = null;
$lastlitethread = null;

// preset thread count
$threads = 0;

$json = array();

$sizes = array();
$sizes[0] = 'xx-small';
$sizes[1] = 'x-small';
$sizes[2] = 'small';
$sizes[3] = 'medium';
$sizes[4] = 'large';
$sizes[5] = 'x-large';
$sizes[6] = 'xx-large';

while ($posts = mysql_fetch_array($results)) {

  // test to see if current row is a new thread, increment $threads if so
  if (array_shift(explode('.',$posts[thread])) != $lastthread[0]) {

    // ignore rows that are new threads and are not the parent
    if (count(explode('.',$posts[thread])) != 1)
      continue;
    else
      $threads++;

    // don't feed the trolls
    $parent_author = null;

  }

  // find difference between these arrays, returns an array
  if ($threads <= $config[maxthreads]) {
    fputs($fp,str_repeat("</li></ul>",count(array_diff($lastthread,explode('.',$posts[thread])))));
    fputs($fp_neat,str_repeat("</li></ul>",count(array_diff($lastthread,explode('.',$posts[thread])))));
/*    for ($i = 1; $i <= count(array_diff($lastthread,explode('.', $posts[thread]))); $i++) {
      $xml->endElement();
    }*/
  }
  if ($threads <= $config[maxthreadslite])
    fputs($fp_lite,str_repeat("</li></ul>",count(array_diff($lastthread,explode('.',$posts[thread])))));

  $lastthread = explode('.',$posts[thread]);

  if ($threads == $config[maxthreadslite])
    $lastlitethread = $lastthread;

  if ($threads == $config[maxthreads])
    $lastnormalthread = $lastthread;


  // build the rate string (i.e. "Warning - Gross")
  $display_rate = null;
  if ($posts[score] != 'null' || ($posts[type] != 'null' && $posts[type] != '')) {
    switch ($posts[type]) {
	case 'warn-g':
	  $posts[type] = "<b style='color: red; font-size: larger'>Warning</b> - Gross";
	  break;
	case 'warn-n':
	  $posts[type] = "<b style='color: red; font-size: larger'>Warning</b> - Nudity";
	  break;
	case 'nsfw':
	  $posts[type] = "<b style='color: red; font-size: larger'>NSFW</b>";
	  break;
	  }

    $display_rate = " - <span style='font-size: smaller'>( ";
    if ($posts[score] != 'null') $display_rate .= $posts[score];
    if ($posts[score] != 'null' && $posts[type] != 'null' && $posts[type] != '') $display_rate .= ', ' . ucfirst($posts[type]);
    if ($posts[score] == 'null') $display_rate .= ucfirst($posts[type]);
    $display_rate .= ' )</span>';
  }

  // only show the date for the first post of the thread
  if ($posts[id] == $posts[parent]) $display_date = ' - ' . $posts[date_sm];
  else $display_date = null;

  if ($threads <= $config[maxthreads]) {

    // don't feed the trolls
    if ($posts[id] == $posts[parent] && $posts[message_author] == 'nasirichampang')
      $parent_author = 'troll';

    if ($parent_author == 'troll' && $posts[message_author] != 'nasirichampang')
      $posts['message_subject'] = "Aren't you banned yet?";

    fputs($fp,
	  "<ul><li><a href='$locations[forum]?d=$posts[id]&amp;t=$posts[t]' title='$posts[date]'>$posts[message_subject]</a> ".
	  options($posts[link],$posts[video],$posts[image],$posts[body],$posts[message_author]).
	  " - <b>$posts[message_author]</b>$display_date$display_rate"
	  );

# April 1st, 2006
    fputs($fp_neat,
	  "<ul><li><a href='$locations[forum]?d=$posts[id]&amp;t=$posts[t]' title='$posts[date]'><span style='font-size: ".$sizes[rand(0,6)]."'>$posts[message_subject]</span></a> ".
	  "<span style='font-size: ".$sizes[rand(0,6)]."'>".options($posts[link],$posts[video],$posts[image],$posts[body],$posts[message_author])."</span>".
	  " - <b><span style='font-size: ".$sizes[rand(0,6)]."'>$posts[message_author]</span></b><span style='font-size: ".$sizes[rand(0,6)]."'>$display_date</span>$display_rate"
	  );

    $thispost = array(
	't' => $posts['t'],
	'id' => $posts['id'],
	'thread' => $posts['thread'],
	'parent' => $posts['parent'],
	'message_author' => $posts['message_author'],
	'message_author_email' => $posts['message_author_email'],
	'message_subject' => $posts['message_subject'],
	'date' => $posts['date'],
	'link' => $posts['link'],
	'video' => $posts['video'],
	'image' => $posts['image'],
	'message_body' => $posts['message_body'],
    );

    if ($posts['link'] == 'y') {
      $query = "select link_url, link_title from $locations[links_table] where t = '$posts[t]' and id = '$posts[id]'";
      $jsonlinkresult = mysql_query($query, $mysql_link);
      $thislinks = array();
      while ($thislink = mysql_fetch_array($jsonlinkresult)) {
	array_push($thislinks, array('link_url' => $thislink['link_url'], 'link_title' => $thislink['link_title']));
      }
      array_push($thispost, array('links' => $thislinks));
    }

    if ($posts['image'] == 'y') {
      $query = "select image_url from $locations[images_table] where t = '$posts[t]' and id = '$posts[id]'";
      $jsonimageresult = mysql_query($query, $mysql_link);
      $thisimages = array();
      while ($thisimage = mysql_fetch_array($jsonimageresult)) {
	array_push($thisimages, array('image_url' => $thisimage['image_url']));
      }
      array_push($thispost, array('images' => $thisimages));
    }

    array_push($json, $thispost);
/*
    $xml->startElement('thread');
    $xml->writeAttribute('t', $posts['t']);
    $xml->writeAttribute('id', $posts['id']);
    $xml->writeAttribute('message_author', $posts['message_author']);
    if ($posts['message_author_email'] != '') {
      $xml->writeAttribute('message_author_email', $posts['message_author_email']);
    }
    $xml->writeAttribute('message_subject', $posts['message_subject']);
    $xml->writeAttribute('date', $posts['date']);

    if ($posts[link] == 'y')
      $xml->writeAttribute('link', 'true');

    if ($posts[video] == 'y')
      $xml->writeAttribute('video', 'true');

    if ($posts[image] == 'y')
      $xml->writeAttribute('image', 'true');

    if ($posts['message_body'] != '') {
      $xml->startElement('message_body');
      $xml->writeCData($posts['message_body']);
      $xml->endElement();
    }
*/

  }

  if ($threads <= $config[maxthreadslite]) {
    fputs($fp_lite,
	  "<ul><li><a href='$locations[forum]?d=$posts[id]&amp;t=$posts[t]'>$posts[message_subject]</a> ".
	  options($posts[link],$posts[video],$posts[image],$posts[body],$posts[message_author]).
	  " - <b>$posts[message_author]</b>$display_date$display_rate"
	  );
  }

}

fputs($fp_json, json_encode($json));

if (is_array($lastnormalthread)) {
  fputs($fp,str_repeat("</li></ul>",count($lastnormalthread)));
  fputs($fp_neat,str_repeat("</li></ul>",count($lastnormalthread)));
  for ($i = 1; $i <= count($lastnormalthread); $i++) {
    //$xml->endElement();
  }
} else {
  fputs($fp,str_repeat("</li></ul>",count($lastthread)));
  fputs($fp_neat,str_repeat("</li></ul>",count($lastthread)));
  for ($i = 1; $i <= count($lastthread); $i++) {
    //$xml->endElement();
  }
}

if (is_array($lastlitethread))
  fputs($fp_lite,str_repeat("</li></ul>",count($lastlitethread)));
else
  fputs($fp_lite,str_repeat("</li></ul>",count($lastthread)));

// close the forum file
fclose($fp);
fclose($fp_neat);
fclose($fp_lite);
fclose($fp_json);

/*
$xml->endElement();
$xml->flush();
*/

// remove the lock
unlink("$locations[datfile].lock");

// we're done!
if (!isset($_POST["beta"])) {
  header("Location: $locations[forum]");
} else {
  print "OK";
}
exit(0);

// this function checks a url for basic validity
function check_url($url) {
  $parsed_url = array();
  $parsed_url = parse_url($url);
  if (!($parsed_url[scheme] && $parsed_url[host]) || $parsed_url[scheme] == 'file')
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
  global $locations, $config;
  setcookie('chocolate',time(),time() * (60*60*24*365),'/');
  #sleep(10);
  #mail($config[admin_email],"Error in $_SERVER[SCRIPT_FILENAME]","User '$_POST[message_author]' is banned [$_SERVER[REMOTE_ADDR]\n","From: \"Script Debugging SubSystem\" <$config[admin_email]>\n");
  header("Location: $locations[forum]");
  exit();
}

?>
