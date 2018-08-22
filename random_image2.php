<?

// message board image browser v.3.hong
// added new line between each image for better formatting when large images appear

// include the configuration file
require('config.inc.php');

// overwrite the title
$config[title] = "Riceboy Image Browser";

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config[db_errstr],$config[admin_email],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());

// handle authentication if necessary
if ($config[auth_required] == true) {

  // begin a session
  session_start();

  if (isset($_SESSION[username]) && isset($_SESSION[password])) {
    $query = "select username from $locations[auth_table] where username = '$_SESSION[username]' and password = '$_SESSION[password]'";
    $result = mysqli_query($mysqli_link, $query) or error($config[db_errstr],$config[admin_email],$query."\n".mysqli_error());
    if (mysqli_num_rows($result) != 1) {
      // destroy the erroneous session
      session_destroy();
      // leave
      header("Location: $locations[login]");
      exit();
    }
  } else {
    // leave
    header("Location: $locations[login]");
    exit();
  }

}

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN'
  'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
<head>
  <title><?=$config[title]?></title>
  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">

  <style type="text/css">
  img {
	float: left;
  }
  img:hover {
    width: auto;
    height: auto;
  }
  </style>

</head>

<body class='body'>

<?

$query = 'select * from ' . $locations['images_table'] . ' order by rand() limit 50';

$result = mysqli_query($mysqli_link, $query) or error($config[db_errstr],$config[admin_email],$query."\n".mysqli_error());

if (mysqli_num_rows($result) == 0) {

?>
<table width='100%' border='0' cellspacing='0' cellpadding='0'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellspacing='1' cellpadding='4'>
  <tr class='titlelarge'>
    <td colspan='2'>No images to display.</td>
  </tr>
  <tr class='main'>
    <td colspan='2'><a href='<?=$locations[forum]?>'>Back to the Forum</a></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
    <blockquote>
<?

} else {

?>
<table width='100%' border='0' cellspacing='0' cellpadding='0'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellspacing='1' cellpadding='4'>
  <tr class='main'>
    <td colspan='2'>
    <a href='<?=$locations[forum]?>'>Back to the Forum</a>
    </td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<?

  while ($row = mysqli_fetch_array($result)) {

?>
    <a href='<? print "$locations[forum]?d=$row[id]&amp;t=" . (strlen($row[t]) == 5 ? '0' . $row[t] : $row[t]); ?>'><img src='<?=$row[image_url]?>' border='0' alt=''></a>
    <br clear="left">
<?

  }

}
?>
    </td>
  </tr>
    </blockquote>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>

</body>
</html>
