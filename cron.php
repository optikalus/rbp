<?php
// message board script v.3

// include the configuration file
require('config.inc.php');

// establish a connection with the database or notify an admin with the error string
$mysqli_link = mysqli_connect($config['db_host'],$config['db_user'],$config['db_pass'],$config['db_name']) or error($config['db_errstr'],$config['admin_email'],'mysqli_connect(' . $config['db_host'] . ',' . $config['db_user'] . ',' . $config['db_pass'] . ')' . "\n".mysqli_error());

// set the tablename ($t should always be the suffix, it is preset above or supplied by the post)
$tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

$query = 'select id,t from ' . $tablename . ' where transient = "y" and id = parent and date <= date_sub(now(), interval ' . $config['displaytime'] . ' second)';

$results = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
while ($parent = mysqli_fetch_array($results)) {
  $query = 'delete from ' . $tablename . ' where transient = "y" and t = "' . $parent['t'] . '" and parent = "' . $parent['id'] . '"';
  mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error($mysqli_link));
}

exit();

?>
