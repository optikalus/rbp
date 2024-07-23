<?

// message board image browser v.3

// include the configuration file
require('config.inc.php');

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],'mysqli_connect(' . $config['db_host'] . ',' . $config['db_user'] . ',' . $config['db_pass'] . ')' . "\n".mysqli_error());

$gawkerstyle = (preg_match("/^http:\/\/rbpgawker.f0e.net/", $_SERVER['HTTP_REFERER']) ? true : false);

// handle authentication if necessary
if ($config['auth_required'] == true) {

  // begin a session
  session_start();

  if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    $query = 'select username from ' . $locations['auth_table'] . ' where username = "' . $_SESSION['username'] . '" and password = "' . $_SESSION['password'] . '"';
    $result = mysqli_query($mysqli_link,$query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
    if (mysqli_num_rows($result) != 1) {
      // destroy the erroneous session
      session_destroy();
      // leave
      header('Location: ' . $locations['login']);
      exit();
    }
  } else {
    // leave
    header('Location: ' . $locations['login']);
    exit();
  }

}

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN'
  'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<?

// set the depth of the search
if (isset($_REQUEST['daterange']) && is_numeric($_REQUEST['daterange']) && $_REQUEST['daterange'] > 0 && ($_REQUEST['daterange'] <= 730 || $_REQUEST['daterange'] == 999999))
  $daterange = $_REQUEST['daterange'];
else
  $daterange = 7;

?>
  <title>Search : <?=$config['title']?></title>
	<script language="Javascript" type="text/javascript">
	const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;

	if (currentTheme) {
		document.documentElement.setAttribute('data-theme', currentTheme);
	}
	</script>
  <script language='JavaScript' type='text/javascript'>
  <!--

    function isFilled(f) {
      var L_Msg_Text='Please enter a keyword.';
      if (f.keyword.value == '') {
        alert(L_Msg_Text);
        return false;
      } else {
        return true;
      }
    }

    function isFilled2(f) {
      var L_Msg_Text='Please enter a search criteria.';
      var str1 = f.message_author.value
      var str2 = f.message_subject.value
      var str3 = f.message_body.value
      var str4 = f.score.value
      var str5 = f.type.value

      if (str1 == '' && str2 == '' && str3 == '' && str4 == '' && str5 == '') {
        alert(L_Msg_Text);
        return false;
      } else {
        return true;
      }
    }

  //-->
  </script>

  <link rel="stylesheet" type="text/css" href="<?=$locations['css']?>">

</head>

<body class='body'>

<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
  <tr>
    <td class='borderoutline'>

<table width='100%' border='0' cellspacing='1' cellpadding='4' align='center'>
  <tr class='title'>
    <td colspan='2'><b>Search the forum..</b></td>
  </tr>
  <tr class='main'>
    <td colspan='2'><a href='<?=$locations['forum']?>'><b>[Back to <?=$config['title']?>]</b></a></td>
  </tr>
  <tr class='main'>
    <td class='info'>
      <form action='<?=$_SERVER['PHP_SELF']?>' method='get' onsubmit='return isFilled(this);'>
      <b>Standard Search</b><br />
      Keyword: <input type='text' name='keyword' value='<?=stripslashes($_REQUEST['keyword'])?>' size='20' maxlength='50' class='forminput' />
      <input type='submit' value='Find It' class='formsubmit' />
      </form>
    </td>
    <td>
      <form action='<?=$_SERVER['PHP_SELF']?>' method='get' onsubmit='return isFilled2(this);'>
      <b>Advanced Search</b><br />
      Author: <input type='text' name='message_author' value='<?=stripslashes($_REQUEST['message_author'])?>' size='25' maxlength='50' class='forminput' /><br />
      Subject: <input type='text' name='message_subject' value='<?=stripslashes($_REQUEST['message_subject'])?>' size='25' maxlength='50' class='forminput' /><br />
      Message: <input type='text' name='message_body' value='<?=stripslashes($_REQUEST['message_body'])?>' size='25' maxlength='50' class='forminput' /><br />
      With Image: <input type='checkbox' name='with_image'<? if (isset($_REQUEST['with_image'])) print " checked='checked'"; ?> class='forminput' /> 
      With Video: <input type='checkbox' name='with_video'<? if (isset($_REQUEST['with_video'])) print " checked='checked'"; ?> class='forminput' />
      With Link: <input type='checkbox' name='with_link'<? if (isset($_REQUEST['with_link'])) print " checked='checked'"; ?> class='forminput' /><br />
      Search within...
      <select name='daterange' size='1' class='smallselect'>
	<option value='1' <? if ($daterange == 1) print " selected='selected'"; ?>>today's posts</option>
	<option value='7' <? if ($daterange == 7) print " selected='selected'"; ?>>last week</option>
	<option value='30' <? if ($daterange == 30) print " selected='selected'"; ?>>last month</option>
	<option value='60' <? if ($daterange == 60) print " selected='selected'"; ?>>last 60 days</option>
	<option value='180' <? if ($daterange == 180) print " selected='selected'"; ?>>last 180 days</option>
	<option value='365' <? if ($daterange == 365) print " selected='selected'"; ?>>last 365 days</option>
	<option value='999999' <? if ($daterange == 999999) print " selected='selected'"; ?>>forever</option>
      </select>
      <input type='submit' value='Find It' class='formsubmit' /><br />
      Score:
      <select name='score' size='1' class='smallselect'>
      <option value=''></option>
      <option value='0'>0</option>
      <option value='1'>1</option>
      <option value='2'>2</option>
      <option value='3'>3</option>
      <option value='4'>4</option>
      <option value='5'>5</option>
      </select>
      Type:
      <select name='type' size='1' class='smallselect'>
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
      </select>
      </form>
  </tr>
  <tr class='title'>
    <td colspan='2'><b>Search Results</b></td>
  </tr>
  <tr class='main'>
    <td colspan='2'>
<?

// prevent hax0ring
$query = null;
$where = null;

if (isset($_REQUEST['keyword']) || isset($_REQUEST['finduser']) ||
   (isset($_REQUEST['message_author']) && isset($_REQUEST['message_body']) && isset($_REQUEST['message_subject']))) {

  mysqli_query($mysqli_link, "set profiling = 1;");

  if (isset($_REQUEST['keyword']))
    $where = '(message_author like "%' . escape($_REQUEST['keyword']) . '%" or message_subject like "%' . escape($_REQUEST['keyword']) . '%" or message_body like "%' . escape($_REQUEST['keyword']) . '%")';
  elseif (isset($_REQUEST['finduser']))
    $where = 'ip like "' . escape($_REQUEST['finduser']) . '"';
  elseif (isset($_REQUEST['message_author']) && isset($_REQUEST['message_subject']) && isset($_REQUEST['message_body'])) {

    if ($_REQUEST['message_author'] != '') {
      if (isset($where)) $where .= ' and ';
      $where .= 'message_author like "%' . escape($_REQUEST['message_author']) . '%"';
    }

    if ($_REQUEST['message_subject'] != '') {
      if (isset($where))  $where .= ' and ';
      $where .= 'message_subject like "%' . escape($_REQUEST['message_subject']) . '%"';
    }

    if ($_REQUEST['message_body'] != '') {
      if (isset($where)) $where .= ' and ';
      $where .= 'message_body like "%' . escape($_REQUEST['message_body']) . '%"';
    }

    if (isset($_REQUEST['with_image'])) {
      if (isset($where)) $where .= ' and ';
      $where .= 'image = "y"';
    }

    if (isset($_REQUEST['with_video'])) {
      if (isset($where)) $where .= ' and ';
      $where .= 'video = "y"';
    }

    if (isset($_REQUEST['with_link'])) {
      if (isset($where)) $where .= ' and ';
      $where .= 'link = "y"';
    }

    if (isset($_REQUEST['score']) && $_REQUEST['score'] != '' && is_numeric($_REQUEST['score'])) {
      if (isset($where)) $where .= ' and ';
      $where .= 'score >= "' . $_REQUEST['score'] . '" and score <= "' . $_REQUEST['score'] . '" + 1';
    }

    if (isset($_REQUEST['type']) && $_REQUEST['type'] != '') {
      if (isset($where)) $where .= ' and ';
      $where .= 'type = "' . $_REQUEST['type'] . '"';
    }

    if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '' && is_numeric($_REQUEST['user_id'])) {
      if (isset($where)) $where .= ' and ';
      $where .= 'user_id = "' . $_REQUEST['user_id'] . '"';
    }

  }

  $numloops = 0;

  // setup the table rotation scheme
  if ($config['rotate_tables'] == 'daily')
    $numloops = $daterange;
  elseif ($config['rotate_tables'] == 'weekly')
    $numloops = $daterange / 7;
  elseif ($config['rotate_tables'] == 'monthly')
    $numloops = $daterange / 32;
  elseif ($config['rotate_tables'] == 'yearly')
    $numloops = $daterange / 365;
  else
    $numloops = 1;

  for ($i = 0; $i < $numloops; $i++) {

    // setup the table rotation scheme
    if ($config['rotate_tables'] == 'daily')
      $t = date('mdy', time() - ($i * 86400));
    elseif ($config['rotate_tables'] == 'weekly')
      $t = strftime('%y%W', time() - ($i * 86400 * 7));
    elseif ($config['rotate_tables'] == 'monthly')
      $t = date('my', time() - ($i * 86400 * 32));
    elseif ($config['rotate_tables'] == 'yearly')
      $t = date('Y', time() - ($i * 86400 * 365));
    else
      $t = date('mdy', time() - ($i * 86400));

    $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

    $query .= '(select id,parent,message_author,message_subject,date_format(date,"%m/%d/%Y - %l:%i:%s %p") as date, date as date2, ' . (!$config['rotate_tables'] ? 't' : '"' . $t . '"') . ' as t ' .
	      'from ' . $tablename . ' where (' . $where . ') ' .
	      ($daterange != 999999 ? 'and date >= date_sub(curdate(), interval ' . $daterange . ' day) ' : '') .
	      'and message_author not in ("wot","burtle","adamgeek","myc187","thepeekay","pkx","fj","the doug","ratbert","bdev","loki","ratvespa") ' .
	      'and message_body not like "%adamgeek%" and message_body not like "%burtle%" order by date2 desc)';

  }

  if ($query != null) {

    $result = mysqli_query($mysqli_link,$query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
    $exec_time_result = mysqli_query($mysqli_link,'SELECT query_id, SUM(duration) FROM information_schema.profiling GROUP BY query_id ORDER BY query_id DESC LIMIT 1;');
    print "Your search returned <b>".mysqli_num_rows($result)."</b> record(s) in " . mysqli_fetch_array($exec_time_result)[1] . " seconds.<br /><br />\n";
    print "<ul>\n";

    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_array($result)) {
	if ($gawkerstyle === true)
	  print '<li><a href="http://rbpgawker.f0e.net/#!' . $row['t'] . $row['parent'] . '">' . $row['message_subject'] . '</a> - <b>' . $row['message_author'] . '</b> - ' . $row['date'] . '</li>' . "\n";
	else
	  print '<li><a href="' . $locations['forum'] . '?d=' . $row['id'] . '&amp;t=' . $row['t'] . '">' . $row['message_subject'] . '</a> - <b>' . $row['message_author'] . '</b> - ' . $row['date'] . '</li>' . "\n";
      }
    }

    print "</ul>\n";

  }

}

?>
    </td>
  </tr>
</table>

    </td>
  </tr>
</table>

</body>
</html>
