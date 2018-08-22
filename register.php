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

if (!isset($_POST[username]) || !isset($_POST[password_a]) || !isset($_POST[password_b]) || !isset($_POST[email]))
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

  if (mysqli_num_rows($result) == 1) {
    $errors[general] .= '<br />Username already in use';
    $errors[username] = true;
  }

}

if (strlen($_POST[email]) > 255 || !eregi("^[-a-z0-9_]+[-a-z0-9_.]*@[-a-z0-9_]+\.[-a-z0-9_.]+$", $_POST[email])) {
  $errors[general] .= '<br />Invalid Email Address';
  $errors[email] = true;
} else {

  $query = "select user_id from $locations[auth_users_table] where email = '$_POST[email]'";
  $result = mysqli_query($mysqli_link, $query);

  if (mysqli_num_rows($result) > 0) {
    $errors[general] .= '<br />Email Address already in use';
    $errors[email] = true;
  }


}

// bail on errors
if (count($errors) > 0)
  error_redirect($errors);

$activation_key = md5($_POST[username].time());

// add the account
$query = "insert into $locations[auth_users_table] (username, password, email, activation_key) values ('$_POST[username]', md5('$_POST[password_a]'), '$_POST[email]', '$activation_key')";
mysqli_query($mysqli_link, $query);

$user_id = mysqli_insert_id($mysqli_link);

// send validation email
$headers = "From: \"".$config[title]."\" <".$config[admin_email].">\n";
$body = "Welcome to $config[title].\n\n";
$body .= "Please keep this email for your records. Your account information is as follows:\n\n";
$body .= "----------------------------\n";
$body .= "Username: $_POST[username]\n";
$body .= "Password: <secret>\n";
$body .= "----------------------------\n\n";
$body .= "Your account is currently inactive. You cannot use it until you visit the following link:\n\n";
$body .= "http://$_SERVER[SERVER_NAME]/activation.php?u=$user_id&key=$activation_key\n\n";
$body .= "Please do not forget your password as it has been encrypted in our database and we cannot retrieve it for you. However, should you forget your password you can request a new one which will be activated in the same way as this account.\n\n";
$body .= "Thank you for registering.\n";

@mail($_POST[email],'Welcome to ' . $config[title],$body,$headers);

header("Location: thanks.php");
exit();

?>
