<?

// message board posting rating system v.1

// include the configuration file
require('config.inc.php');

// we don't accept get as a method of posting
if ($_SERVER[REQUEST_METHOD] == 'GET') {
  header("Location: $_SERVER[HTTP_REFERER]");
  exit();
}

// make sure we received all the form values
if (!isset($_POST[t]) || (isset($_POST[t]) && !is_numeric($_POST[t])) || !isset($_POST[id]) || (isset($_POST[id]) && !is_numeric($_POST[id])) || !isset($_POST[score]) || (isset($_POST[score]) && (!is_numeric($_POST[score]) || $_POST[score] < 0 || $_POST[score] > 5)) || !isset($_POST[type]) || (isset($_POST[type]) && $_POST[type] != '' && $_POST[type] != 'funny' && $_POST[type] != 'warn-g' && $_POST[type] != 'warn-n' && $_POST['type'] != 'nsfw')) {
  header("Location: $_SERVER[HTTP_REFERER]");
  exit();
}

// leave if the user didn't submit anything useful
if ($_POST[score] == 0 && $_POST[type] == '') {
  header("Location: $_SERVER[HTTP_REFERER]");
  exit();
}

$t = $_POST[t];
$id = $_POST[id];

// check to see if they've already voted for this one..
if (isset($_COOKIE['rate_'.$id.'_'.$t])) {
  sleep(1);
  header("Location: $_SERVER[HTTP_REFERER]");
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

// only alow people who've posted to flag
$query = "select count(*) as posts from posts_".date("mdy")." where ip = '$_SERVER[REMOTE_ADDR]'";
$result = mysql_query($query, $mysql_link);

if (mysql_result($result, "posts", 0) === 0) {
  //leave
  header("Location: $_SERVER[HTTP_REFERER]");
  exit();
}

// set the tablename ($t should always be the suffix, it is preset above or supplied by the post)
$tablename = $locations[flags_table];

$query = "select * from $tablename where id = '$id' and t = '$t' and type = '$_POST[type]' limit 1";
$result = mysql_query($query, $mysql_link);

// update the table
if (mysql_num_rows($result) > 0) {

  $vote = mysql_fetch_array($result);

  // delete trolls if they are the starter of the thread (votes are 1 because first vote is 0)
  if ($vote[type] == 'troll' && $vote[votes] >= 1) {

    $tablename = 'posts_'.$t;

    /*$thread = mysql_result(mysql_query("select thread from $tablename where id = '$id'", $mysql_link),0,'thread');
    $query = "delete from $tablename where id = '$id' or thread like '$thread%'";
    if ($thread != '' && $t == date('mdy'))
      mysql_query($query, $mysql_link);
     */

    header("Location: $locations[forum]");
    exit();

  }

  if ($_POST[score] > 0) $vote_score = ($vote[score] + $_POST[score]) / 2;
  else $vote_score = $vote[score];

  $query = "update $tablename set votes = votes + 1, score = '$vote_score' where id = '$id' and t = '$t' and type = '$_POST[type]'";
  mysql_query($query, $mysql_link);

  // update the real post to display in the main index

  $query = "select votes, type, score from $tablename where id = '$id' and t = '$t' order by votes desc limit 1";
  $result = mysql_query($query, $mysql_link);

  $vote = mysql_fetch_array($result);


  if ($vote[votes] >= $config[rate_threshold]) {

    $tablename = 'posts_' . $t;
    $query = "update $tablename set score = '$vote[score]', type = '$vote[type]' where id = '$id'";
    mysql_query($query, $mysql_link);

  }

// insert a new record
} else {

  $query = "insert into $tablename (id, t, votes, score, type) values ('$id', '$t', '1', '$_POST[score]', '$_POST[type]')";
  mysql_query($query, $mysql_link);

  if (1 >= $config[rate_threshold]) {

    $tablename = 'posts_' . $t;
    $query = "update $tablename set score = '$_POST[score]', type = '$_POST[type]' where id = '$id'";
    mysql_query($query, $mysql_link);

  }

}

// make it so they can't vote over and over
setcookie('rate_'.$id.'_'.$t,'true',0,'/');

header("Location: $_SERVER[HTTP_REFERER]");
exit();

?>
