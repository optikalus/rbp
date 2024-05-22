<?php

function write_dat_file() {
    global $locations, $config, $insert_id, $message_author, $message_subject, $debug_strings, $mysqli_link;

// Check for existing $datfile.lock
    if (file_exists($locations['datfile'] . '.lock')) {
        for ($i = 0; $i < 3; $i++) {
            sleep(1);
            if (!file_exists($locations['datfile'] . '.lock'))
                break;
        }
    }

// don't allow user to abort
    ignore_user_abort(true);

// create a lock
    touch($locations['datfile'] . '.lock');

// increment the counter
    $count = 0;

    if (file_exists($locations['counter'])) {
        // read old count
        $fp = fopen($locations['counter'],'r');
        $count = fread($fp, filesize($locations['counter']));
        fclose($fp);
    }

// reset the counter if it's mtime is not today (new day)
    if (file_exists($locations['counter']) && date('m d y') != date('m d y', filemtime($locations['counter'])))
        $count = 0;

// write count + 1
    $fp = fopen($locations['counter'],'w');
    fwrite($fp,$count + 1);
    fclose($fp);

// update the last post
    $fp = fopen($locations['lastpost'],'w');
    fwrite($fp,"$insert_id\n$t\n".alter_username(deescape($message_author))."\n".deescape($message_subject)."\n");
    fclose($fp);

// create the forum file
    $fp = fopen($locations['datfile'],'w') or error('Unable to open ' . $locations['datfile'] . ' for writing',$config['admin_email'],'Unable to open ' . $locations['datfile'] . ' for writing');

// create the forum_lite file
    $fp_lite = fopen($locations['datfile_lite'],'w') or error('Unable to open ' . $locations['datfile_lite'] . ' for writing',$config['admin_email'],'Unable to open ' . $locations['datfile_lite'] . ' for writing');

// create the forum_json file
//$fp_json = fopen($locations['jsonfile'],'w') or error('Unable to open ' . $locations['jsonfile'] . ' for writing',$config['admin_email'],'Unable to open ' . $locations['jsonfile'] . ' for writing');

    $fp_banned = fopen($locations['datfile_banned'], 'w') or error('Unable to open ' . $locations['datfile_banned'] . ' for writing', $config['admin_email'], 'Unable to open ' . $locations['datfile_banned'] . ' for writing');

    $fp_lite_banned = fopen($locations['datfile_lite_banned'],'w') or error('Unable to open ' . $locations['datfile_lite_banned'] . ' for writing',$config['admin_email'],'Unable to open ' . $locations['datfile_lite_banned'] . ' for writing');

// re-setup the table rotation scheme
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

// reset the table name
    $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

// query for the rows to output
    $query = 'select ' . $tablename . '.id, ' . $tablename . '.parent, ' . $tablename . '.thread, ' . $tablename . '.message_author, ' . $tablename . '.message_subject, ' .
        'date_format(' . $tablename . '.date,"%m/%d/%Y - %l:%i:%s %p") as date, date_format(' . $tablename . '.date, "%l:%i:%s %p") as date_sm, "' . $t . '" as t, ' .
        $tablename . '.link, ' . $tablename . '.image, ' . $tablename . '.video, ifnull(' . $tablename . '.score, "null") as score, ifnull(' . $tablename . '.type, "null") as type, ' . $tablename . '.banned, ' .
        'case when ' . $tablename . '.message_body = "" then "n" else "y" end as body, ' . $tablename . '.message_body ' .
        'from ' . $tablename . ' ' .
        'where unix_timestamp(' . $tablename . '.date) > (unix_timestamp(now()) - ' . $config['displaytime'] . ') ' .
        (!$config['rotate_tables'] ? ' and t = ' . $t . ' ' : '') .
        'order by ' . $tablename . '.parent desc, ' . $tablename . '.thread asc limit ' . $config['maxrows'];

// see if we need to bond another table
    if (($config['rotate_tables'] == 'daily' && date('mdy',time() - $config['displaytime']) != date('mdy')) ||
        ($config['rotate_tables'] == 'weekly' && strftime('%y%W',time() - $config['displaytime']) != strftime('%y%W')) ||
        ($config['rotate_tables'] == 'monthly' && date('my',time() - $config['displaytime']) != date('my')) ||
        ($config['rotate_tables'] == 'yearly' && date('Y',time() - $config['displaytime']) != date('Y')) ||
        (date('mdy',time() - $config['displaytime']) != date('mdy'))) {

        if ($config['rotate_tables'] == 'daily')
            $t = date('mdy', time() - $config['displaytime']);
        elseif ($config['rotate_tables'] == 'weekly')
            $t = strftime('%y%W', time() - $config['displaytime']);
        elseif ($config['rotate_tables'] == 'monthly')
            $t = date('my', time() - $config['displaytime']);
        elseif ($config['rotate_tables'] == 'yearly')
            $t = date('Y', time() - $config['displaytime']);
        else
            $t = date('mdy', time() - $config['displaytime']);

        $tablename = ($config['rotate_tables'] ? $locations['posts_table'].'_'.$t : $locations['posts_table']);

        $query = '(' . $query . ') union (' .
            'select ' . $tablename . '.id,' . $tablename . '.parent,' . $tablename . '.thread,' . $tablename . '.message_author,' . $tablename . '.message_subject, ' .
            'date_format(' . $tablename . '.date,"%m/%d/%Y - %l:%i:%s %p") as date, date_format(' . $tablename . '.date, "%l:%i:%s %p") as date_sm, "' . $t . '" as t, ' .
            $tablename . '.link, ' . $tablename . '.image, ' . $tablename . '.video, ifnull(' . $tablename . '.score, "null") as score, ifnull(' . $tablename . '.type, "null") as type, ' . $tablename . '.banned, ' .
            'case when ' . $tablename . '.message_body = "" then "n" else "y" end as body, ' . $tablename . '.message_body ' .
            'from ' . $tablename . ' ' .
            'where unix_timestamp(' . $tablename . '.date) > (unix_timestamp(now()) - ' . $config['displaytime'] . ') ' .
            (!$config['rotate_tables'] ? ' and t = ' . $t . ' ' : '') .
            'order by ' . $tablename . '.parent desc,' . $tablename . '.thread asc limit ' . $config['maxrows'] . ' ' .
            ') order by t desc, parent desc, thread asc limit ' . $config['maxrows'];
    }

    array_push($debug_strings, $query);

    $msc = microtime(true);
    $results = mysqli_query($mysqli_link, $query) or error($config['db_errstr'],$config['admin_email'],$query."\n".mysqli_error());
    $msc = microtime(true) - $msc;

    array_push($debug_strings, " took " . ($msc * 1000) . " ms<br />");

    if (mysqli_num_rows($results) == 0)
        error('',$config['admin_email'],'ERROR: No rows to output');

    $lastthread = array();
    $lastnormalthread = null;
    $lastlitethread = null;

// preset thread count
    $threads = 0;

//$json = array();

    $sizes = array();
    $sizes[0] = 'xx-small';
    $sizes[1] = 'x-small';
    $sizes[2] = 'small';
    $sizes[3] = 'medium';
    $sizes[4] = 'large';
    $sizes[5] = 'x-large';
    $sizes[6] = 'xx-large';

    $msc = microtime(true);
    while ($posts = mysqli_fetch_array($results)) {

        // test to see if current row is a new thread, increment $threads if so
        if (array_shift(explode('.',$posts['thread'])) != $lastthread[0]) {

            // ignore rows that are new threads and are not the parent
            if (count(explode('.',$posts['thread'])) != 1)
                continue;
            else
                $threads++;

            // don't feed the trolls
            $parent_author = null;

        }

        // find difference between these arrays, returns an array
        if ($threads <= $config['maxthreads']) {
            $thread_count = count(array_diff($lastthread,explode('.',$posts['thread'])));
            fputs($fp,str_repeat("</li></ul>",$thread_count));
            fputs($fp_banned,str_repeat("</li></ul>",$thread_count));
        }
        if ($threads <= $config['maxthreadslite']) {
            fputs($fp_lite,str_repeat("</li></ul>",count(array_diff($lastthread,explode('.',$posts['thread'])))));
            fputs($fp_lite_banned,str_repeat("</li></ul>",count(array_diff($lastthread,explode('.',$posts['thread'])))));
        }

        $lastthread = explode('.',$posts['thread']);

        if ($threads == $config['maxthreadslite'])
            $lastlitethread = $lastthread;

        if ($threads == $config['maxthreads'])
            $lastnormalthread = $lastthread;


        // build the rate string (i.e. "Warning - Gross")
        $display_rate = null;
        if ($posts['score'] != 'null' || ($posts['type'] != 'null' && $posts['type'] != '')) {
            switch ($posts['type']) {
                case 'warn-g':
                    $posts['type'] = "<b style='color: red; font-size: larger'>Warning</b> - Gross";
                    break;
                case 'warn-n':
                    $posts['type'] = "<b style='color: red; font-size: larger'>Warning</b> - Nudity";
                    break;
                case 'nsfw':
                    $posts['type'] = "<b style='color: red; font-size: larger'>NSFW</b>";
                    break;
            }

            $display_rate = " - <span style='font-size: smaller'>( ";
            if ($posts['score'] != 'null') $display_rate .= $posts['score'];
            if ($posts['score'] != 'null' && $posts['type'] != 'null' && $posts['type'] != '') $display_rate .= ', ' . ucfirst($posts['type']);
            if ($posts['score'] == 'null') $display_rate .= ucfirst($posts['type']);
            $display_rate .= ' )</span>';
        }

        if ($config['always_display_date_full'])
            $display_date = ' - ' . $posts['date'];
        elseif ($config['always_display_date_small'])
            $display_date = ' - ' . $posts['date_sm'];
        else
        {
            // only show the date for the first post of the thread
            if ($posts['id'] == $posts['parent']) $display_date = ' - ' . $posts['date_sm'];
            else $display_date = null;
        }

        if ($threads <= $config['maxthreads']) {

            if ($posts['banned'] == 'y')
            {
                fputs($fp, '<ul style="display: none"><li>');
                fputs($fp_banned,
                    '<ul><li><a href="?d=' . $posts['id'] . '&amp;t=' . $posts['t'] . '" title="' . $posts['date'] . '">' . $posts['message_subject'] . '</a> ' .
                    options($posts['link'],$posts['video'],$posts['image'],$posts['body'],$posts['message_author']) .
                    ' - <b>' . $posts['message_author'] . '</b>' . $display_date . $display_rate
                );
            }
            else
            {
                fputs($fp,
                    '<ul><li><a href="?d=' . $posts['id'] . '&amp;t=' . $posts['t'] . '" title="' . $posts['date'] . '">' . $posts['message_subject'] . '</a> ' .
                    options($posts['link'],$posts['video'],$posts['image'],$posts['body'],$posts['message_author']) .
                    ' - <b>' . $posts['message_author'] . '</b>' . $display_date . $display_rate
                );
                fputs($fp_banned,
                    '<ul><li><a href="?d=' . $posts['id'] . '&amp;t=' . $posts['t'] . '" title="' . $posts['date'] . '">' . $posts['message_subject'] . '</a> ' .
                    options($posts['link'],$posts['video'],$posts['image'],$posts['body'],$posts['message_author']) .
                    ' - <b>' . $posts['message_author'] . '</b>' . $display_date . $display_rate
                );
            }

            $thispost = array(
                't' => $posts['t'],
                'id' => $posts['id'],
                'thread' => $posts['thread'],
                'parent' => $posts['parent'],
                'message_author' => $posts['message_author'],
                'message_author_email' => $posts['message_author_email'],
                'message_subject' => $posts['message_subject'],
                'date' => $posts['date'],
                'link' => $posts['link'],
                'video' => $posts['video'],
                'image' => $posts['image'],
                'message_body' => $posts['message_body'],
            );

            /*    if ($posts['link'] == 'y') {
                  $query = 'select link_url, link_title from ' . $locations['links_table'] . ' where t = "' . $posts['t'] . '" and id = "' . $posts['id'] . '"';
                  $jsonlinkresult = mysqli_query($mysqli_link, $query);
                  $thislinks = array();
                  while ($thislink = mysqli_fetch_array($jsonlinkresult)) {
                array_push($thislinks, array('link_url' => $thislink['link_url'], 'link_title' => $thislink['link_title']));
                  }
                  array_push($thispost, array('links' => $thislinks));
                }

                if ($posts['image'] == 'y') {
                  $query = 'select image_url from ' . $locations['images_table'] . ' where t = "' . $posts['t'] . '" and id = "' . $posts['id'] . '"';
                  $jsonimageresult = mysqli_query($mysqli_link, $query);
                  $thisimages = array();
                  while ($thisimage = mysqli_fetch_array($jsonimageresult)) {
                array_push($thisimages, array('image_url' => $thisimage['image_url']));
                  }
                  array_push($thispost, array('images' => $thisimages));
                }

                array_push($json, $thispost);
            */
        }

        if ($threads <= $config['maxthreadslite']) {
            if ($posts['banned'] == 'y')
            {
                fputs($fp_lite, '<ul style="display: none"><li>');
                fputs($fp_lite_banned,
                    '<ul><li><a href="?d=' . $posts['id'] . '&amp;t=' . $posts['t'] . '">' . $posts['message_subject'] . '</a> ' .
                    options($posts['link'],$posts['video'],$posts['image'],$posts['body'],$posts['message_author']) .
                    ' - <b>' . $posts['message_author'] . '</b>' . $display_date . $display_rate
                );
            }
            else
            {
                fputs($fp_lite,
                    '<ul><li><a href="?d=' . $posts['id'] . '&amp;t=' . $posts['t'] . '">' . $posts['message_subject'] . '</a> ' .
                    options($posts['link'],$posts['video'],$posts['image'],$posts['body'],$posts['message_author']) .
                    ' - <b>' . $posts['message_author'] . '</b>' . $display_date . $display_rate
                );
                fputs($fp_lite_banned,
                    '<ul><li><a href="?d=' . $posts['id'] . '&amp;t=' . $posts['t'] . '">' . $posts['message_subject'] . '</a> ' .
                    options($posts['link'],$posts['video'],$posts['image'],$posts['body'],$posts['message_author']) .
                    ' - <b>' . $posts['message_author'] . '</b>' . $display_date . $display_rate
                );
            }
        }
    }

//fputs($fp_json, json_encode($json));

    if (is_array($lastnormalthread)) {
        fputs($fp,str_repeat('</li></ul>',count($lastnormalthread)));
        fputs($fp_banned,str_repeat('</li></ul>',count($lastnormalthread)));
    } else {
        fputs($fp,str_repeat('</li></ul>',count($lastthread)));
        fputs($fp_banned,str_repeat('</li></ul>',count($lastthread)));
    }

    if (is_array($lastlitethread)) {
        fputs($fp_lite,str_repeat('</li></ul>',count($lastlitethread)));
        fputs($fp_lite_banned,str_repeat('</li></ul>',count($lastlitethread)));
    } else {
        fputs($fp_lite,str_repeat('</li></ul>',count($lastthread)));
        fputs($fp_lite_banned,str_repeat('</li></ul>',count($lastthread)));
    }

// close the forum file
    fclose($fp);
    fclose($fp_banned);
    fclose($fp_lite);
    fclose($fp_lite_banned);
//fclose($fp_json);

// remove the lock
    unlink($locations['datfile'] . '.lock');
}