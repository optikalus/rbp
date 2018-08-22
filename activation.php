<?

// message board registration script v.1

// include the configuration file
require('config.inc.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title><?=$config[title]?> Activation</title>
  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">
</head>

<body class='body'>
<?

// start session (if not already started)
if (!ini_get('session.auto_start')) {
  session_name($config[session_name]);
  session_save_path($locations[session_path]);
  ini_set('session.gc_maxlifetime','604800');
  session_start();
}

// establish a connection with the database or notify an admin with the error string
if (!isset($mysqli_link)) {
  $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config[db_errstr],$config[admin_email],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());
}

if (!isset($_REQUEST[u]) || !isset($_REQUEST[key]) || !is_numeric($_REQUEST[u]) || strlen($_REQUEST[u]) < 1 || strlen($_REQUEST[key]) != 32)
  display_error('Invalid Input');

// validate user / key pair
$query = "select user_id from $locations[auth_users_table] where user_id = '$_REQUEST[u]' and activation_key = '$_REQUEST[key]' and queued = 'y'";
$result = mysqli_query($mysqli_link, $query);

if (mysqli_num_rows($result) != 1)
  display_error('Invalid activation key');

// activate the account
$query = "update $locations[auth_users_table] set queued = 'n', activation_key = '' where user_id = '$_REQUEST[u]' and activation_key = '$_REQUEST[key]'";
mysqli_query($mysqli_link, $query);

$headers = "From: \"".$config[title]."\" <".$config[admin_email].">\n";
$body = "User_ID $_REQUEST[u] successfully validated their registration.\n\n";
$body .= "Please activate this account if it looks legit.\n";

@mail($config[admin_email], 'Successful Registration', $body, $headers);

?>
<table>
  <tr>
    <td class='borderoutline'>
<table border='0' cellpadding='4' cellspacing='0'>
  <tr class='title'>
    <td>Validation Successful!</td>
  </tr>
  <tr class='main'>
    <td>
    Thank you for validating your registration! An administrator will activate your account shortly.<br /><br />
    <a href='<?=$locations[forum]?>'>Return to <?=$config[title]?></a>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>
</body>
</html>
<?

function display_error($error) {
?>
<table width='300'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellpadding='4' cellspacing='0'>
  <tr class='title'>
    <td>ERROR!</td>
  </tr>
  <tr class='main'>
    <td class='error'><?=$error?></td>
  </tr>
</table>
    </td>
  </tr>
</table>
</body>
</html>
<?
  exit();
}

?>
