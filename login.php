<?

// message board script v.3

// include the configuration file
require('config.inc.php');

// make sure we received all the form values
if (!isset($_POST[username]) || !isset($_POST[password])) {
  require('login.inc.php');
  exit();
}

// establish a connection with the database or notify an admin with the error string
if (!isset($mysql_link)) {
  $mysql_link = mysql_connect($config[db_host],$config[db_user],$config[db_pass]) or error($config[db_errstr],$config[admin_email],"mysql_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysql_error());
  mysql_select_db($config[db_name],$mysql_link) or error($config[db_errstr],$config[admin_email],"mysql_select_db($config[db_name])\n".mysql_error());
}

// begin a session
session_start();

// validate the username / password
$query = "select username, level from $locations[auth_table] where username = '".escape($_POST[username])."' and password = md5('".escape($_POST[password])."')";
$result = mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

if (mysql_num_rows($result) == 1) {

  $query = "update $locations[auth_table] set last_login = now() where username = '".escape($_POST[username])."'";
  mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

  $_SESSION[username] = $_POST[username];
  $_SESSION[password] = md5($_POST[password]);
  $_SESSION[level] = mysql_result($result, 0, 'level');

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
