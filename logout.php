<?

// message board script v.3

// include the configuration file
require('config.inc.php');

// begin a session
session_start();

// make sure we received all the session values
if (!isset($_SESSION[username]) || !isset($_SESSION[password])) {
  $error = 'Username and password required';
  require('login.inc.php');
  exit();
}

// establish a connection with the database or notify an admin with the error string
if (!isset($mysqli_link)) {
  $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config[db_errstr],$config[admin_email],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());
}

// validate the username / password
$query = "select username from $locations[auth_table] where username = '$_SESSION[username]' and password = '$_SESSION[password]'";
$result = mysqli_query($mysqli_link, $query) or error($config[db_errstr],$config[admin_email],$query."\n".mysqli_error());

if (mysqli_num_rows($result) == 1) {

  session_destroy();

  header("Location: $locations[forum]");
  exit();

} else {

  $error = 'Invalid username and/or password';
  require('login.inc.php');
  exit();

}

?>
