<?

// message board script v.3

// include the configuration file
require('config.inc.php');

// begin a session
session_start();

if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
  header('Location: ' . $locations['forum']);
  exit();
}

// make sure we received all the form values
if (!isset($_POST[username]) || !isset($_POST[password])) {
  require('login.inc.php');
  exit();
}

// establish a connection with the database or notify an admin with the error string
if (!isset($mysqli_link)) {
  $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config[db_errstr],$config[admin_email],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());
}

// validate the username / password
$query = "select username, level from $locations[auth_table] where username = '".escape($_POST[username])."' and password = md5('".escape($_POST[password])."')";
$results = mysqli_query($mysqli_link, $query) or error($config[db_errstr],$config[admin_email],$query."\n".mysqli_error());

if (mysqli_num_rows($results) == 1) {

  $result = mysqli_fetch_array($results);
  $query = "update $locations[auth_table] set last_login = now() where username = '".escape($_POST[username])."'";
  mysqli_query($mysqli_link, $query) or error($config[db_errstr],$config[admin_email],$query."\n".mysqli_error());

  $_SESSION[username] = $_POST[username];
  $_SESSION[password] = md5($_POST[password]);
  $_SESSION[level] = $result['level'];

  // add cookie to validate session
  setcookie('auth', true, 0, '/');

  if (isset($_POST[heading])) {
    header("Location: $_POST[heading]");
    exit();
  } else {
    header("Location: $locations[forum]");
    exit();
  }

} else {

  $error = 'Invalid username and/or password';
  require('login.inc.php');
  exit();

}

?>
