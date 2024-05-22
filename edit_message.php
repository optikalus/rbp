<?php

// message board script v.3

// include the configuration file
require('config.inc.php');
require('datfile.inc.php');

$debug = false;

/*
if ($_SERVER['REMOTE_ADDR'] == '10.1.0.216')
  $debug = true;
*/

$debug_strings = array();


// we don't accept get as a method of posting
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    header('Location: ' . $locations['forum']);
    exit();
}

$remote_addr = $_SERVER['REMOTE_ADDR'];

// no posts w/ <a href
if (stristr($_POST['message_body'], '<a href') || stristr($_POST['message_subject'], '<a href') || stristr($_POST['message_body'], '[url=')) {
    sleep(1);
    header('Location: ' . $locations['forum']);
    exit;
}

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],'mysqli_connect(' . $config['db_host'] . ',' . $config['db_user'] . ',' . $config['db_pass'] . ')' . "\n".mysqli_error());


// preset the variables from the post
$message_author = break_html($_POST['message_author']);
$message_author_email = break_html($_POST['message_author_email']);
$message_subject = break_html($_POST['message_subject']);
$message_body = clear_html(break_html($_POST['message_body']));

// setup the table rotation scheme
if ($config['rotate_tables'] == 'daily')
    $t = date('mdy');
elseif ($config['rotate_tables'] == 'weekly')
    $t = strftime('%y%W');
elseif ($config['rotate_tables'] == 'monthly')
    $t = date('my');
elseif ($config['rotate_tables'] == 'yearly')
    $t = date('Y');
else
    $t = date('mdy');

if (isset($_POST['thread']) && preg_match('/^[0-9.]+$/',$_POST['thread']) &&
    isset($_POST['t']) && is_numeric($_POST['t']) &&
    isset($_POST['parent']) && is_numeric($_POST['parent'])) {

    $thread = $_POST['thread'];
    $parent = $_POST['parent'];
    $t = $_POST['t'];
}

// leave if the user didn't submit anything useful
if (strlen($message_author) < 1 || strlen($message_subject) < 1 ||
    preg_match("/^\s+$/",$message_author) || preg_match("/^\s+$/",$message_subject) ||
    preg_match("/\[url\=/",$message_subject)) {

    header('Location: ' . $locations['forum']);
    exit();
}

// handle authentication if necessary
$authenticated = false;
if ($config['auth_required'] == true || isset($_COOKIE['auth'])) {

    // begin a session
    session_start();

    if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
        $query = 'select username from ' . $locations['auth_table'] . ' where username = "' . $_SESSION['username'] . '" and password = "' . $_SESSION['password'] . '"';
        $result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
        if (mysqli_num_rows($result) != 1) {
            // destroy the erroneous session
            session_destroy();
            // leave
            header('Location: ' . $locations['login']);
            exit();
        } else {
            $authenticated = true;
        }
    } else {
        // leave
        header('Location: ' . $locations['login']);
        exit();
    }

}

// set the tablename ($t should always be the suffix, it is preset above or supplied by the post)
$tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);


// query for the post
$query = "select now() as now, date as raw_date, date_format(date,'%m/%d/%Y - %l:%i:%s %p') as formatted_date from $tablename".
    " where id = " . $_POST['d'] . (!$config['rotate_tables'] ? " and t = '" . $_POST['t'] . "'" : "");


$result = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error($mysqli_link));

if (mysqli_num_rows($result) == 1) {
    $post = mysqli_fetch_array($result);

    $dateNow = new \DateTime($post['now']);
    $datePosted = new \DateTime($post['raw_date']);
    $edit_time_safe = ($dateNow->getTimestamp() - $datePosted->getTimestamp() < $config['edit_time_limit']);
}

if (!$edit_time_safe || !can_edit($_POST['d'], $_POST['t'])) {
    header('Location: ' . $locations['forum'] . '?error=2');
    exit();
}


// update the post
$query = 'update ' . $tablename . ' set message_author="' . escape(alter_username($message_author)) . '", 
    message_author_email="'. escape($message_author_email)  .'", message_subject="'. escape($message_subject)  .'", 
    message_body="'. escape($message_body . "\n\n--\nThis posted was edited on ". $post['formatted_date']) .'" where id = ' . $_POST['d'] . (!$config['rotate_tables'] ? ' and t = "' . $_POST['t'] . '"' : '');

array_push($debug_strings, $query);

$msc = microtime(true);
mysqli_query($mysqli_link, $query) or error(
    $config['db_errstr'],
    $config['admin_email'],
    $query . "\n" . mysqli_error($mysqli_link)
);
$msc = microtime(true) - $msc;

array_push($debug_strings, " took " . ($msc * 1000) . " ms<br />");


write_dat_file();


header('Location: ' . $locations['forum']);
exit(0);

// this function breaks HTML tags
function break_html($str) {

    $bad = array('&','>','<');
    $good = array('&amp;','&gt;','&lt;');

    return(str_replace($bad,$good,$str));

}

// this function specifies allowed HTML tags
function clear_html($str) {

    $bad = array('&lt;b&gt;','&lt;/b&gt;','[b]','[/b]','&lt;i&gt;','&lt;/i&gt;','&lt;pre&gt;','&lt;/pre&gt;','[pre]','[/pre]','[code]','[/code]','_EMBEDAMP_','_EMBEDLT_','_EMBEDGT_');
    $good = array('<b>','</b>','<b>','</b>','<i>','</i>','<pre>','</pre>','<pre>','</pre>','<pre>','</pre>','&','<','>');

    return(str_replace($bad,$good,$str));

}