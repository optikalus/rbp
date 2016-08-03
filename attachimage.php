<?

// message board script v.3

// include the configuration file
require('config.inc.php');

// establish a connection with the database or notify an admin with the error string
$mysql_link = mysql_connect($config[db_host],$config[db_user],$config[db_pass]) or error($config[db_errstr],$config[admin_email],"mysql_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysql_error());
mysql_select_db($config[db_name],$mysql_link) or error($config[db_errstr],$config[admin_email],"mysql_select_db($config[db_name])\n".mysql_error());

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title>Attach Image</title>
  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">
</head>

<body>

<br />

<form action='<?=$_SERVER[PHP_SELF]?>' method='post' enctype='multipart/form-data'>
<table align='center' border='0' cellpadding='0' cellspacing='0' class='main'>
<?

if (isset($_FILES[message_image_upload]) && $_FILES[message_image_upload][name] != '') {

  require('fileupload-class.php');

  $upload = new uploader('en');
  $upload->max_filesize($config[image_max_filesize]);
  $upload->max_image_size($config[image_max_image_size_width],$config[image_max_image_size_height]);
  $upload->upload('message_image_upload',$config[image_acceptable_file_types],$config[image_default_extension]);
  $upload->save_file($config[image_path],$config[image_overwrite_mode]);

  if (!$upload->error) {

?>
  <tr>
    <td align='right'>Image URL:</td>
    <td><a href='/<?=$config[image_path]?><?=$upload->file[name]?>' target='image'>right-click, copy shortcut</a></td>
  </tr>
<?

  } else {

?>
  <tr>
    <td align='center' colspan='2'><b><?=$upload->error?></b></td>
  </tr>
<?

  }

?>
  <tr>
    <td colspan='2'>&nbsp;</td>
  </tr>
  <tr>
    <td align='center' colspan='2'><input type='submit' value='Close Window' onclick='window.close(); return false;' /></td>
  </tr>
<?

} else {

?>
  <tr>
    <td align='right'>Attach Image:</td><td><input type='file' name='message_image_upload' size='35' /></td>
  </tr>
  <tr>
    <td colspan='2'>&nbsp;</td>
  </tr>
  <tr>
    <td align='center' colspan='2'><input type='submit' value='Attach Image' /></td>
  </tr>
<?

}

?>
</table>
</form>

</body>
</html>
