<?

// message board registration script v.1

// include the configuration file
require('config.inc.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title><?=$config[title]?> Update Successful</title>
  <link rel="stylesheet" type="text/css" href="<?=$locations[css]?>">
</head>

<body class='body'>

<table>
  <tr>
    <td class='borderoutline'>
<table border='0' cellpadding='4' cellspacing='0'>
  <tr class='title'>
    <td>Account update successful!</td>
  </tr>
  <tr class='main'>
    <td align='center'>
    <br /><a href="#" onclick="window.close()">Close Window</a><br /><br />
    </td>
  </tr>
</table>
    </td>
  </tr>
</table>

</body>
</html>
