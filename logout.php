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
if (!isset($mysql_link)) {
  $mysql_link = mysql_connect($config[db_host],$config[db_user],$config[db_pass]) or error($config[db_errstr],$config[admin_email],"mysql_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysql_error());
  mysql_select_db($config[db_name],$mysql_link) or error($config[db_errstr],$config[admin_email],"mysql_select_db($config[db_name])\n".mysql_error());
}

// validate the username / password
$query = "select username from $locations[auth_table] where username = '$_SESSION[username]' and password = '$_SESSION[password]'";
$result = mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

if (mysql_num_rows($result) == 1) {

  session_destroy();

  header("Location: $locations[forum]");
  exit();

} else {

  $error = 'Invalid username and/or password';
  require('login.inc.php');
  exit();

}

?>
