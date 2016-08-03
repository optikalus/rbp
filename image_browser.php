<?

// message board image browser v.3

// include the configuration file
require('config.inc.php');

// overwrite the title
$config[title] = "Riceboy Image Browser";

// establish a connection with the database or notify an admin with the error string
$mysql_link = mysql_connect($config[db_host],$config[db_user],$config[db_pass]) or error($config[db_errstr],$config[admin_email],"mysql_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysql_error());
mysql_select_db($config[db_name],$mysql_link) or error($config[db_errstr],$config[admin_email],"mysql_select_db($config[db_name])\n".mysql_error());

// handle authentication if necessary
if ($config[auth_required] == true) {

  // begin a session
  session_start();

  if (isset($_SESSION[username]) && isset($_SESSION[password])) {
    $query = "select username from $locations[auth_table] where username = '$_SESSION[username]' and password = '$_SESSION[password]'";
    $result = mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());
    if (mysql_num_rows($result) != 1) {
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
</head>

<body class='body'>

<?

// setup the table rotation scheme
if ($config[rotate_tables] == 'daily')
  $t = date('mdy');
elseif ($config[rotate_tables] == 'weekly')
  $t = strftime('%y%W');
elseif ($config[rotate_tables] == 'monthly')
  $t = date('my');
elseif ($config[rotate_tables] == 'yearly')
  $t = date('Y');
else
  $t = date('mdy');

$tablename = $locations[posts_table].'_'.$t;

$query = "select $tablename.id,$tablename.message_author,$tablename.message_subject,".
	 "date_format($tablename.date,'%m/%d/%Y - %l:%i:%s %p') as date,$locations[images_table].image_url ".
	 "from $tablename,$locations[images_table] ".
	 "where $locations[images_table].t = '$t' and $locations[images_table].id = $tablename.id ".
	 "order by $tablename.date desc";

$result = mysql_query($query,$mysql_link) or error($config[db_errstr],$config[admin_email],$query."\n".mysql_error());

print "<!-- $query -->\n";

if (mysql_num_rows($result) == 0) {

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

  if (isset($_REQUEST[page]) && is_numeric($_REQUEST[page])) {
    if ($_REQUEST[page] <= mysql_num_rows($result))
      mysql_data_seek($result, $_REQUEST[page] - 1);
  } else {
    $_REQUEST[page] = 1;
  }

  $count = 0;
  while ($row = mysql_fetch_array($result)) {

    if ($count == 0) {

?>
<table width='100%' border='0' cellspacing='0' cellpadding='0'>
  <tr>
    <td class='borderoutline'>
<table width='100%' border='0' cellspacing='1' cellpadding='4'>
  <tr class='titlelarge'>
    <td colspan='2'><?=$row[message_subject]?></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<?

      $prevpage = $_REQUEST[page] - 1;
      $nextpage = $_REQUEST[page] + 1;

      if ($prevpage == 0 && $nextpage > mysql_num_rows($result))
	print "[Previous Image | Next Image]<br />\n";
      elseif ($prevpage == 0)
	print "[Previous Image | <a href='$locations[image_browser]?page=$nextpage'>Next Image</a>]<br />\n";
      elseif ($nextpage > mysql_num_rows($result))
	print "[<a href='$locations[image_browser]?page=$prevpage'>Previous Image</a> | Next Image]<br />\n";
      else
	print "[<a href='$locations[image_browser]?page=$prevpage'>Previous Image</a> | <a href='$locations[image_browser]?page=$nextpage'>Next Image</a>]<br />\n";

?>
    <a href='<?=$locations[forum]?>'>Back to the Forum</a>
    </td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
    Posted by <? print "$row[message_author] on $row[date]"; ?>
    <a href='<? print "$locations[forum]?d=$row[id]&amp;t=$t"; ?>'>(See Complete Post)</a>
    <hr />
    <img src='<?=$row[image_url]?>' border='0' alt=''>
    </td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
    <blockquote><br />
<?

      print "<a href='$locations[image_browser]?page=$_REQUEST[page]'>$row[message_subject]</a> - $row[message_author] ($row[date])<br />\n";
    } else {
      $temppage = $_REQUEST[page] + $count;
      print "<a href='$locations[image_browser]?page=$temppage'>$row[message_subject]</a> - $row[message_author] ($row[date])<br />\n";
    }

    $count++;

  }

}
?>
    </blockquote>
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>

</body>
</html>
