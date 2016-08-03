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

// error handling
$err_array = array();
if (isset($_SESSION[errors]))
  $err_array = unserialize($_SESSION[errors]);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title><?=$config[title]?> Registration</title>

  <script language="Javascript" type="text/javascript">
  <!--
  function isFilled(f){
    var L_Msg_Text='Please enter a Name and Subject.';
    if (f.message_author.value == '' || f.message_subject.value == '') {
      alert(L_Msg_Text);
      return false;
    } else {
      disableForm(f);
      return true;
    }
  }

  function disableForm(theform) {
    if (document.all || document.getElementById) {
      for (i = 0; i < theform.length; i++) {
        var tempobj = theform.elements[i];
        if (tempobj.type.toLowerCase() == "submit" || tempobj.type.toLowerCase() == "reset")
          tempobj.disabled = true;
      }
      return true;
    } else {
      return false;
    }
  }

  //-->
  </script>

  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">

</head>

<body class='body'>

<?

  if (isset($err_array)) {
    print "<!--\n";
    foreach ($err_array as $key => $val) {
      print "$key -> $val\n";
    }
    print "-->\n";
  }

?>

<form action='register.php' method='post'>
<table align='center'>
  <tr>
    <td class='borderoutline'>
<table border='0' cellpadding='4' cellspacing='0' class='main'>
  <tr class='title'>
    <td colspan='2'>Registration</td>
  </tr>
  <tr<? if (isset($err_array[username])) print " style='background-color: #ff9999'"; ?>>
    <td>Username: </td>
    <td><input type='text' name='username' size='22' maxlength='255' value='' /></td>
  </tr>
  <tr<? if (isset($err_array[password])) print " style='background-color: #ff9999'"; ?>>
    <td>Password: </td>
    <td><input type='password' name='password_a' size='22' maxlength='255' value='' /></td>
  </tr>
  <tr<? if (isset($err_array[password])) print " style='background-color: #ff9999'"; ?>>
    <td></td>
    <td><input type='password' name='password_b' size='22' maxlength='255' value='' /></td>
  </tr>
  <tr<? if (isset($err_array[email])) print " style='background-color: #ff9999'"; ?>>
    <td>Email Address: </td>
    <td><input type='text' name='email' size='22' maxlength='255' value='' /></td>
  </tr>
  <tr>
  <tr>
    <td colspan='2' align='center'><input type='submit' value='Register' /> <input type='reset' /></td>
  </tr>
</table>
    </td>
  </tr>
</table>
</form>
<? if (isset($err_array[general])) print "<span class='error'>$err_array[general]</span>\n"; ?>
</body>
</html>
