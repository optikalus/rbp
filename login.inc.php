<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title><?=$config[title]?></title>
  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">
</head>
<body>

<br /><br /><br />
<br /><br /><br />

<form action='<?=$locations[login]?>' method='post'>
<table align='center' border='0' cellpadding='2' cellspacing='0' class='main'>
<?
  if (isset($error)) {
?>
  <tr>
    <td colspan='2' align='center' class='error'><?=$error?></td>
  </tr>
<?
  }
?>
  <tr>
    <td align='right'><u>N</u>ame: </td>
    <td><input type='text' name='username' value='' size='30' maxlength='50' accesskey='n' /></td>
  </tr>
  <tr>
    <td align='right'><u>P</u>assword: </td>
    <td><input type='password' name='password' value='' size='30' maxlength='50' accesskey='p' /></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
    <input type='submit' name='login' value='Authenticate' class='smallselect' /> <input type='reset' class='smallselect' />
    <? if (isset($_SERVER[HTTP_REFERER])) { ?><input type='hidden' name='heading' value='<?=$_SERVER[HTTP_REFERER]?>' /><? } ?>
    </td>
  </tr>
</table>
</form>

</body>
</html>
