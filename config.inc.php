<?

// preset configuration arrays
$locations = array();
$config = array();

// news item
$config['newsitem'] = <<<EoF
<b>News Item</b> - <span class='newsdate'>DATE HERE</span><br />
<div class='newstext'>
flava
</div>
<br />
EoF;

// preset database connection information
$config['db_user'] = '';
$config['db_pass'] = '';
$config['db_host'] = '';
$config['db_name'] = '';

// error the user sees when there is a problem connecting to the database
$config['db_errstr'] = 'There was a problem connecting to the database server, please try again later.';

// name of the forum script
$locations['forum'] = '/forum.php';
$locations['post'] = '/post_message.php';
$locations['search'] = '/search.php';
$locations['faq'] = '/faq.php';
$locations['image_browser'] = '/image_browser.php';
$locations['attachimage'] = '/attachimage.php';
$locations['css'] = '/styles.css';
$locations['css-dark'] = '/styles-dark.css';
$locations['login'] = '/login.php';
$locations['logout'] = '/logout.php';
$locations['session_path'] = '../sessions';
$locations['admin'] = '/admin.php';
$locations['edit'] = '/edit_message.php';

// name of the forum threads
$locations['datfile'] = 'forum.dat';
$locations['datfile_neat'] = 'forum_neat.dat';
$locations['datfile_banned'] = 'forum_banned.dat';
$locations['datfile_lite'] = 'forum_lite.dat';
$locations['datfile_lite_banned'] = 'forum_lite_banned.dat';
$locations['xmlfile'] = 'forum.xml';
$locations['jsonfile'] = 'forum.json';

// post counter
$locations['counter'] = 'counter.dat';

// last post tracker
$locations['lastpost'] = 'lastpost.dat';

// table name information
$locations['posts_table'] = 'posts';
$locations['images_table'] = 'rbp_images';
$locations['links_table'] = 'rbp_links';
$locations['auth_table'] = 'rbp_auth';
$locations['auth_posts_table'] = 'rbp_auth_posts';
$locations['flags_table'] = 'rbp_flags';
$locations['auth_users_table'] = 'rbp_auth_users';

// basic forum configuration
$config['maxrows'] = 250; // max. number of posts to display 
$config['displaytime'] = 60*60*2; // amount of time to display posts
$config['displaytimelite'] = 60*30; // amount of time to display posts in lite-mode
$config['maxthreads'] = 30; // max. number of thread to display in normal mode
$config['maxthreadslite'] = 15; // max. number of threads to display in lite-mode
$config['admin_email'] = 'your@email'; // email contact which gets notified if there is a problem
$config['auth_required'] = false; // require authentication to browse the forum
$config['auth_post_required'] = false; // require authentication to post to the forum
$config['rotate_tables'] = false; // how often should we rotate the post tables - daily, weekly, monthly
$config['show_users'] = false; // show list of accounts
$config['allow_proxy'] = true; // allow proxy posting or not
$config['allow_tor'] = true; // allow proxy posting or not
$config['rate_threshold'] = 1; // number of votes before showing a rating
$config['session_name'] = 'rbp_iD';
$config['always_display_date_full'] = false;
$config['always_display_date_small'] = false;
$config['require_captcha'] = false;

// recaptcha
$config['recaptcha_url'] = '';
$config['recaptcha_key'] = '';
$config['recaptcha_secret'] = '';

// security tokens
$config['fb-client-access-token'] = 'Authorization: Bearer AppID|ClientToken';

// proxy users
$config['proxy_users'] = array();

// editing
$config['edit_mode_enabled'] = true;
$config['edit_time_limit'] = 60*5; # seconds allowed to edit post for
$config['edit_secure_value'] = 'set random phrase here';

// image uploading configuration
$config['image_max_filesize'] = 500000;
$config['image_max_image_size_width'] = 3200;
$config['image_max_image_size_height'] = 3200;
$config['image_acceptable_file_types'] = 'image/png|image/gif|image/jpeg|image/pjpeg';
$config['image_default_extension'] = '.jpg';
$config['image_overwrite_mode'] = 2; // 1: overwrite, 2: create with incremental extension, 3: do nothing
$config['image_path'] = 'attachments/';

$config['referrallocation'] = "/cgi-bin/referral.pl"; // Location of the referral-beating script

$config['logo'] = 'longboard.gif';
$config['title'] = "Message Board"; # Name of the message board

$config['guidelines'] = <<<EoF
Since by nature this page attracts people of many different interests within the umbrella of automobile performance (drag racing, autocross, road race, rally, etc.) and people of many different tastes (Ford rules!, Chevy rules, East Coast rules, Mopar rules, etc.) please respect the differing views of others. This message board is a discussion about cars... rice and non-rice... domestic rice, asian rice, euro-rice, or real performance cars. Your opinions are welcome, as are any questions you have about cars or car parts.<br /><br />
Racial slurs and personal threats, impersonating another user, and any other action deemed not appropriate will be deleted. Anyone who violates these rules may be banned at the sole discretion of RF, especially if you deny it when there is proof that you did it. For more on what is or isn't appropriate, take a look at the Message Board FAQ.<br /><br />
It's ok to state your opinion about what is better and why and to debate it with others, but please do not make any unsubstatiated, unintelligent claims. Don't just jump in and say "All you guys suck, my (Brand X) car will blow all you guys away", unless you can prove that your car superior in every way to every car ever made (don't even try it, it can't be done). Debating is encouraged here, stupid statements are not. So if you are going to say something, be prepared to defend it because there are many people who will no doubt disagree with you.<br /><br />
<br /><br />
EoF;

// banning
// $banned[usernames][''] = true;

// banned usernames
$banned['usernames'] = array();


// banned ips
#$banned[ips][''] = true;


// this function modifies names of special members
function alter_username($message_author) {

  // admin
  global $authenticated;
  if ($authenticated === true && isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    return('<font color="#800000"><i>'.$message_author.'</i></font>');
  } else {
    return($message_author);
  }

}

// debugging
function error($displayerr,$admin_email,$errstr) {

  if ($displayerr != '')
    print $displayerr;

  $headers = "From: \"Script Debugging SubSystem\" <$admin_email>\n";

  #print "$admin_email<br />$errstr<br />$headers\n";

  print "<!-- $errstr -->\n";

  #@mail($admin_email,"Error in $_SERVER[SCRIPT_FILENAME]",$errstr,$headers);

  exit(0);

}

// this function returns the at-a-glance info of the post
function options($link,$video,$image,$body,$name=NULL) {

  $option = '[';

  if ($body == 'n') $option .= 'nm:';
  if ($link == 'y') $option .= ':link:';
  if ($video == 'y') $option .= ':video:';
  if ($image == 'y') $option .= ':pic';

  $option .= ']';

  $option = preg_replace('/:/','',preg_replace('/::/',' | ', $option));
  if ($option == '[]') $option = '';

  return($option);

}

// sql escaping
function escape($str) {
  if (!get_magic_quotes_gpc())
    return(addslashes($str));
  else
    return($str);
}
function deescape($str) {
  if (!get_magic_quotes_gpc())
    return($str);
  else
    return(stripslashes($str));
}

function error_redirect($errors) {

  // set session variables for what they posted to cause the error (rebuild the form from those variables)
  $_SESSION['values'] = serialize($_POST);
  $_SESSION['errors'] = serialize($errors);

  header("Location: $_SERVER[HTTP_REFERER]");
  exit();

}

function can_edit($id, $t) {
  global $config;

  return sha1($id . '_' . $t . '_' . $config['edit_secure_value'] . $_SERVER['HTTP_USER_AGENT']) === $_COOKIE['cookie_edit'];
}
?>
