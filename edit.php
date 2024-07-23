<?php
// message board script v3

// include the configuration file
require('config.inc.php');

// make sure the user doesn't cache this page
//header('Cache-control: private, no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

$remote_addr = $_SERVER['REMOTE_ADDR'];

if (!$config['edit_mode_enabled']) {
    // leave
    header("Location: " . $locations['forum']);
    exit();
}

// handle authentication if necessary
if ($config['auth_required'] == true || isset($_REQUEST['needauth'])) {

    // establish a connection with the database or notify an admin with the error string
    if (!isset($mysqli_link)) {
        $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],"mysqli_connect(" . $config['db_host'] . "," . $config['db_user'] . "," . $config['db_pass'] . ")\n".mysqli_error());
    }

    // begin a session
    session_start();

    if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
        $query = "select username from " . $locations['auth_table'] . " where username = '" . $_SESSION['username'] . "' and password = '" . $_SESSION['password'] . "'";
        $result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
        if (mysqli_num_rows($result) != 1) {
            // destroy the erroneous session
            session_destroy();
            // leave
            header("Location: " . $locations['login']);
            exit();
        }
    } else {
        // leave
        header("Location: " . $locations['login']);
        exit();
    }

} elseif ($config['auth_post_required'] == true && !isset($_REQUEST['nocache'])) {

    // start session (if not already started)
    if (!ini_get('session.auto_start')) {
        session_cache_limiter('private_no_cache');
        session_name($config['session_name']);
        session_save_path($locations['session_path']);
        ini_set('session.gc_maxlifetime','604800');
        session_start();
    }

    // error handling
    $err_array = array();
    $val_array = array();
    if (isset($_SESSION['errors'])) {
        $err_array = unserialize($_SESSION['errors']);
        $val_array = unserialize($_SESSION['values']);
        unset($_SESSION['errors']);
        unset($_SESSION['values']);
    }

    if (isset($_REQUEST['logout'])) {

        session_destroy();
        header("Location: " . $locations['forum']);
        exit();

    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=.5, shrink-to-fit=no">
    <title><?=$config['title']?></title>


	<script language="Javascript" type="text/javascript">
	const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;

	if (currentTheme) {
		document.documentElement.setAttribute('data-theme', currentTheme);
	}
	</script>
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

        window.onload = function() {
            var seconds = document.getElementById('seconds');
            updateSeconds(seconds.getAttribute('data-seconds'));
        }

        function updateSeconds(value) {
            if (value <= 0) {
                var submit = document.getElementsByClassName('formsubmit')[0]
                submit.setAttribute('disabled', true);
                submit.setAttribute('value', 'Time Limit Expired');
                return;
            }
            var seconds = document.getElementById('seconds');
            if (value > 60) {
                seconds.innerHTML = Math.ceil((value/60)) + ' minute' + (Math.ceil(value/60) == 1 ? '' : 's');
            } else {
                seconds.innerHTML = value + ' seconds';
            }
            setTimeout(function() {
                updateSeconds(value - 1);
            }, 1000);
        }

        //-->
    </script>

    <link rel="stylesheet" type="text/css" href="<?=$locations['css']?>" />

</head>

<body class='body'>
<?
if (!isset($_GET['d']) && !is_numeric($_GET['d']) || !isset($_GET['t']) && !is_numeric($_GET['t'])) {
    print "Invalid request.";
    exit();
}

// set up the DB connection
if (!isset($mysqli_link)) {
    $mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],"mysqli_connect(" . $config['db_host'] . "," . $config['db_user'] . "," . $config['db_pass'] . ")\n".mysqli_error());
}

// preset the table name
$tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$_GET['t'] : $locations['posts_table']);

// query for the post
$query = 'select ' . $tablename . '.id, ' . $tablename . '.message_author, ' . $tablename . '.message_author_email, ' .
    $tablename . '.message_subject, ' . $tablename . '.message_body, now() as now, ' . $tablename . '.date as raw_date, date_format(' . $tablename . '.date,"%m/%d/%Y - %l:%i:%s %p") as date, ' .
    $tablename . '.ip from ' . $tablename .
    ' where id = ' . $_GET['d'] . (!$config['rotate_tables'] ? ' and t = "' . $_GET['t'] . '"' : '');


$result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error($mysqli_link));

if (mysqli_num_rows($result) != 1) {
    print "Error finding post to edit.";
    exit();
}

$post = mysqli_fetch_array($result);

$dateNow = new \DateTime($post['now']);
$datePosted = new \DateTime($post['raw_date']);
$edit_time_safe = ($dateNow->getTimestamp() - $datePosted->getTimestamp() < $config['edit_time_limit']);

if (!$edit_time_safe) { ?>
    Time limit to edit this post expired.
    <?
} elseif ($edit_time_safe && can_edit($post['id'], $_GET['t'])) {
    ?>
    <table width='100%' border='0' cellpadding='0' cellspacing='0'>
        <tr>
            <td valign='top'>
                <table width='100%' border='0' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td class='borderoutline'>
                            <table width='100%' border='0' cellpadding='4' cellspacing='1'>
                                <tr class='titlelarge'>
                                    <td colspan='2'>Edit Your Post</td>
                                </tr>
                                <tr class='main'>
                                    <td colspan='2'>
                                        <table width='100%'>
                                            <tr class='main'>
                                                <td colspan='2'>
                                                    <form action='<?=$locations['edit']?>' method='post' onsubmit='return isFilled(this);'>

                                                        <table class='main'>
                                                            <tr>
                                                                <td>
                                                                    <table class='main'>
                                                                        <tr>
                                                                            <td align='right' valign='top'>&nbsp;</td>
                                                                            <td>You have <span id="seconds" data-seconds="<?= $config['edit_time_limit'] - ($dateNow->getTimestamp() - $datePosted->getTimestamp())  ?>"></span> left to edit this post.</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align='right' valign='top'><u>N</u>ame: </td>
                                                                            <td><input type='text' name='message_author' value='<? print htmlspecialchars(stripslashes(stripslashes($post['message_author'])), ENT_QUOTES); ?>' size='50' maxlength='50' accesskey='n' class='forminput' /></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align='right' valign='top'>E-mail: </td>
                                                                            <td><input type='text' name='message_author_email' value='<? print htmlspecialchars(stripslashes(stripslashes($post['message_author_email'])), ENT_QUOTES); ?>' size='50' maxlength='50' class='forminput' /></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align='right' valign='top'><u>S</u>ubject: </td>
                                                                            <td><input type='text' name='message_subject' value='<? print htmlspecialchars(stripslashes(stripslashes($post['message_subject'])), ENT_QUOTES); ?>' size='50' maxlength='150' accesskey='s' class='forminput' /></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align='right' valign='top'><u>M</u>essage: </td>
                                                                            <td><textarea cols='45' rows='10' name='message_body' accesskey='m' class='forminput'><? print htmlspecialchars(stripslashes(stripslashes($post['message_body'])), ENT_QUOTES); ?></textarea></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>&nbsp;</td>
                                                                            <td>
                                                                                <input type='submit' value='Update Post' class='formsubmit' />
                                                                                <? print "<input type='hidden' name='t' value='" . $_GET['t'] . "' /><input type='hidden' name='d' value='" . $_GET['d'] . "' />\n"; ?>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </form>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
<? } else { ?>
    Unable to edit this post.
<? } ?>
</body>
</html>
