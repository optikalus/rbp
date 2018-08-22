<?

// message board registration script v.1

// include the configuration file
require('config.inc.php');

// start session (if not already started)
if (!ini_get('session.auto_start')) {
  session_name($config[session_name]);
  session_save_path($locations[session_path]);
  ini_set('session.gc_maxlifetime','604800');
  session_start();
}

$errors = array();

// establish a connection with the database or notify an admin with the error string
if (!isset($mysqli_link)) {
  $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config[db_errstr],$config[admin_email],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());
}

if (!isset($_POST[username]) || !isset($_POST[password_a]) || !isset($_POST[password_b]) || !isset($_POST[password]))
  error_redirect(array('general' => 'Invalid Input'));

if ($_POST[password_a] != $_POST[password_b]) {
  $errors[general] .= '<br />Passwords do not match';
  $errors[password] = true;
}

if (strlen($_POST[password_a]) < 4) {
  $errors[general] .= '<br />Passwords must be at least 4 characters long';
  $errors[password] = true;
} elseif (strlen($_POST[password_a]) > 255) {
  $errors[general] .= '<br />Passwords cannot exceed 255 characters';
  $errors[password] = true;
}

if (strlen($_POST[username]) < 1 || strlen($_POST[username]) > 255) {

  $errors[general] .= '<br />Invalid username';
  $errors[username] = true;

} else {

  $query = "select user_id from $locations[auth_users_table] where username = '$_POST[username]'";
  $result = mysqli_query($mysqli_link, $query);

  if (mysqli_num_rows($result) != 1) {
    $errors[general] .= '<br />Username does not exist';
    $errors[username] = true;
  }

}

$query = "select user_id from $locations[auth_users_table] where username = '$_POST[username]' and password = md5('$_POST[password]') and active = 'y' and queued = 'n'";
$result = mysqli_query($mysqli_link, $query);

if (mysqli_num_rows($result) != 1) {
  $errors[general] .= '<br />Old password incorrect';
  $errors[password] = true;
}

// bail on errors
if (count($errors) > 0)
  error_redirect($errors);

// update the account
$query = "update $locations[auth_users_table] set password = md5('$_POST[password_a]') where username = '$_POST[username]'";
mysqli_query($mysqli_link, $query);

header("Location: passwordupdated.php");
exit();

?>
