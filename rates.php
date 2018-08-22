<?

// message board image browser v.3

// include the configuration file
require('config.inc.php');

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config[db_errstr],$config[admin_email],"mysqli_connect($config[db_host],$config[db_user],$config[db_pass])\n".mysqli_error());

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN'  'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
<head>
<title>Rankings : <?=$config[title]?></title>
<link rel='stylesheet' type='text/css' href='<?=$locations[css]?>' />
</head>

<body class='body'>

<form action='<?=$_SERVER[PHP_SELF]?>' method='get'>
<div class='main'>
Type:<br />
<select name='type'>
<option value=''></option>
<option value='stupid'>Stupid</option>
<option value='blog'>Blog</option>
<option value='funny'>Funny</option>
<option value='informative'>Informative</option>
<option value='interesting'>Interesting</option>
<option value='warn-g'>Warning - Gross</option>
<option value='warn-n'>Warning - Nudity</option>
<option value='nsfw'>NSFW</option>
<option value='troll'>Troll</option>
</select><br /><br />
Period:<br />
<select name='period'>
<option value='day'>Day</option>
<option value='week'>Week</option>
<option value='month'>Month</option>
<option value='year'>Year</option>
<option value='lastyear'>Last Year</option>
<option value='all'>All Time</option>
</select><br /><br />
Limit:<br />
<input type='text' name='limit' value='10' /><br /><br />
<input type='submit' /> <input type='reset' />
</div>
</form>

<br /><br />

<div class='main'>
<?

if (isset($_REQUEST[type]) && isset($_REQUEST[period]) && isset($_REQUEST[limit]) &&
   ($_REQUEST[type] == 'funny' || $_REQUEST[type] == 'informative' || $_REQUEST[type] == 'interesting' || $_REQUEST[type] == 'warn-n' || $_REQUEST[type] == 'warn-g' || $_REQUEST['type'] == 'nsfw' || $_REQUEST[type] == 'troll' || $_REQUEST[type] == '') &&
   ($_REQUEST[period] == 'day' || $_REQUEST[period] == 'week' || $_REQUEST[period] == 'month' || $_REQUEST[period] == 'year' || $_REQUEST[period] == 'lastyear' || $_REQUEST[period] == 'all') &&
   is_numeric($_REQUEST[limit]) && $_REQUEST[limit] > 0) {

  $t = null;
  // build 't' list
  if ($_REQUEST[period] == 'day')
    $t = " t = ".date('mdy');
  elseif ($_REQUEST[period] == 'week') {
    $t = " t in (";
    for ($i = 0; $i<7; $i++) {
      if ($i > 0)
	$t .= ',';
      $t .= date('mdy', (time() - (86400 * $i)));
    }
    $t .= ")";
  } elseif ($_REQUEST[period] == 'month')
    $t = " t like '".date('n')."__".date('y')."'";
  elseif ($_REQUEST[period] == 'year')
    $t = " t like '%".date('y')."'";
  elseif ($_REQUEST[period] == 'lastyear')
    $t = " t like '%".date('y', time() - (86400 * 365))."'";
  elseif ($_REQUEST[period] == 'all')
    $t = " t > 0";

  // type
  $type = null;
  if ($_REQUEST[type] != '')
    $type = " and type = '$_REQUEST[type]'";

  // query
  $query = "select * from $locations[flags_table] where $t $type order by votes desc, score desc limit $_REQUEST[limit]";
  $result = mysqli_query($mysqli_query, $query);

  if (mysqli_error())
    print "<!-- $query\n".mysqli_error()." -->\n";

  if (mysqli_num_rows($result) > 0) {

?>
<div>
<?

    while($post = mysqli_fetch_array($result)) {

?>
<a href='<?=$locations[forum]?>?d=<?=$post[id]?>&amp;t=<?=str_pad($post[t], 6, 0, STR_PAD_LEFT)?>'>http://<?=$_SERVER[SERVER_NAME]?><?=$locations[forum]?>?d=<?=$post[id]?>&amp;t=<?=str_pad($post[t], 6, 0, STR_PAD_LEFT)?></a> <?=$post[votes]?> votes, score: <?=$post[score]?> <?=$post[type]?><br />
<?
    }
?>
</div>
<?

  } else {

?>
<div>No Results</div>
<?

  }

}

?>
</div>


</body>
</html>
