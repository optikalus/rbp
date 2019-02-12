<?

// message board posting rating system v.1

// include the configuration file
require('config.inc.php');

// we don't accept get as a method of posting
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit();
}

// make sure we received all the form values
if (!isset($_POST['t']) || (isset($_POST['t']) && !is_numeric($_POST['t'])) || !isset($_POST['id']) || (isset($_POST['id']) && !is_numeric($_POST['id'])) || !isset($_POST['score']) || (isset($_POST['score']) && (!is_numeric($_POST['score']) || $_POST['score'] < 0 || $_POST['score'] > 5)) || !isset($_POST['type']) || (isset($_POST['type']) && $_POST['type'] != '' && $_POST['type'] != 'funny' && $_POST['type'] != 'warn-g' && $_POST['type'] != 'warn-n' && $_POST['type'] != 'nsfw')) {
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit();
}

// leave if the user didn't submit anything useful
if ($_POST['score'] == 0 && $_POST['type'] == '') {
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit();
}

$t = $_POST['t'];
$id = $_POST['id'];

// check to see if they've already voted for this one..
if (isset($_COOKIE['rate_'.$id.'_'.$t])) {
  sleep(1);
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit();
}

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());

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

$tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

// only alow people who've posted to flag
$query = 'select count(*) as posts from ' . $tablename . ' where ip = "' . $_SERVER['REMOTE_ADDR'] . '"';
$result = mysqli_query($mysqli_link, $query);

$count = mysqli_fetch_array($result);
if ($count['posts'] === 0) {
  //leave
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit();
}

// set the tablename ($t should always be the suffix, it is preset above or supplied by the post)
$tablename = $locations['flags_table'];

$query = 'select * from ' . $tablename . ' where id = "' . $id . '" and t = "' . $t . '"  and type = "' . $_POST['type'] . '" limit 1';
$result = mysqli_query($mysqli_link, $query);

// update the table
if (mysqli_num_rows($result) > 0) {

  $vote = mysqli_fetch_array($result);

  // delete trolls if they are the starter of the thread (votes are 1 because first vote is 0)
  if ($vote['type'] == 'troll' && $vote['votes'] >= 1) {

    $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

    /*$thread = mysqli_result(mysqli_query("select thread from $tablename where id = '$id'", $mysqli_link),0,'thread');
    $query = "delete from $tablename where id = '$id' or thread like '$thread%'";
    if ($thread != '' && $t == date('mdy'))
      mysqli_query($mysqli_link, $query);
     */

    header('Location: ' . $locations['forum']);
    exit();

  }

  if ($_POST['score'] > 0) $vote_score = ($vote['score'] + $_POST['score']) / 2;
  else $vote_score = $vote['score'];

  $query = 'update ' . $tablename . ' set votes = votes + 1, score = "' . $vote_score . '" where id = "' . $id . '" and t = "' . $t . '" and type = "' . $_POST['type'] . '"';
  mysqli_query($mysqli_link, $query);

  // update the real post to display in the main index

  $query = 'select votes, type, score from ' . $tablename . ' where id = "' . $id . '" and t = "' . $t . '" order by votes desc limit 1';
  $result = mysqli_query($mysqli_link, $query);

  $vote = mysqli_fetch_array($result);


  if ($vote['votes'] >= $config['rate_threshold']) {

    $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);
    $query = 'update ' . $tablename . ' set score = "' . $vote['score'] . '", type = "' . $vote['type'] . '" where id = "' . $id . '"' . (!$config['rotate_tables'] ? ' and t = "' . $t . '"' : '');
    mysqli_query($mysqli_link, $query);

  }

// insert a new record
} else {

  $query = 'insert into ' . $tablename . ' (id, t, votes, score, type) values ("' . $id . '", "' . $t . '", "1", "' . $_POST['score'] . '", "' . $_POST['type'] . '")';
  mysqli_query($mysqli_link, $query);

  if (1 >= $config['rate_threshold']) {

    $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);
    $query = 'update ' . $tablename . ' set score = "' . $_POST['score'] . '", type = "' . $_POST['type'] . '" where id = "' . $id . '"' . (!$config['rotate_tables'] ? ' and t = "' . $t . '"' : '');
    mysqli_query($mysqli_link, $query);

  }

}

// make it so they can't vote over and over
setcookie('rate_'.$id.'_'.$t,'true',0,'/');

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();

?>
