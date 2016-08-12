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
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attach Image</title>
  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
  <script src="https://code.jquery.com/jquery-3.1.0.min.js" integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s=" crossorigin="anonymous"></script>
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  <script src="clipboard.min.js" type="text/javascript"></script>
</head>

<body>

<div class="container-fluid">

<form action='<?=$_SERVER[PHP_SELF]?>' method='post' enctype='multipart/form-data'>
<?

if (isset($_FILES[message_image_upload]) && $_FILES[message_image_upload][name] != '') {

  require('fileupload-class.php');

  $upload = new uploader('en');
  $upload->max_filesize($config[image_max_filesize]);
  //$upload->max_image_size($config[image_max_image_size_width],$config[image_max_image_size_height]);
  $upload->upload('message_image_upload',$config[image_acceptable_file_types],$config[image_default_extension]);
  $upload->save_file($config[image_path],$config[image_overwrite_mode]);

  if (!$upload->error) {

?>
  <div class="form-group">
    <label for="url">Image URL:</label>
    <div class="input-group">
      <input type="text" id="image_url" class="form-control" value="<? echo 'http://' , $_SERVER['SERVER_NAME'] , '/' , $config['image_path'] , $upload->file['name']; ?>" readonly/>
      <div class="input-group-btn">
        <button class="btn btn-default" type="button" data-clipboard-target="#image_url">Copy</button>
      </div>
    </div>
  </div>
<?

  } else {

?>
  <div class="alert alert-danger"><? echo $upload->error; ?></div>
<?

  }

?>
  <button type="button" class="btn btn-default"  onclick="window.close(); return false">Close</button>
<?

} else {

?>
  <div class="form-group">
    <label for="file">Attach Image:</label>
    <input type='file' class="form-control" id="message_image_upload" name="message_image_upload" />
  </div>
  <button type="submit" class="btn btn-default">Attach Image</button>
<?

}

?>
</form>

</div>

<script>
  new Clipboard('.btn');
</script>

</body>
</html>
