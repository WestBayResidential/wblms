<?php // $Id: undel_user.php,v 1.0.0.0 2012/08/10 11:45:00 plariv Exp$

    require_once('../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/user/filters/lib.php');

    $undelete     = optional_param('undelete', 0, PARAM_INT);
    $confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
    $confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
    $sort         = optional_param('sort', 'name', PARAM_ALPHA);
    $dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
    $page         = optional_param('page', 0, PARAM_INT);
    $perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
    $ru           = optional_param('ru', '2', PARAM_INT);            // show remote users
    $lu           = optional_param('lu', '2', PARAM_INT);            // show local users
    $acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)


    admin_externalpage_setup('undeleteusers');

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $site = get_site();

    if (!has_capability('moodle/user:update', $sitecontext) and !has_capability('moodle/user:delete', $sitecontext)) {
        error('You do not have the required permission to edit/delete/undelete users.');
    }

    $strundelete = "Restore";
    $strshowdeletedusers = "Show deleted users";

    if (empty($CFG->loginhttps)) {
        $securewwwroot = $CFG->wwwroot;
    } else {
        $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
    }

    admin_externalpage_print_header();

    if ($confirmuser and confirm_sesskey()) {
        if (!$user = get_record('user', 'id', $confirmuser)) {
            error("No such user!", '', true);
        }

        $auth = get_auth_plugin($user->auth);

        $result = $auth->user_confirm(addslashes($user->username), addslashes($user->secret));

        if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
            notify(get_string('userconfirmed', '', fullname($user, true)) );
        } else {
            notify(get_string('usernotconfirmed', '', fullname($user, true)));
        }

    } else if ($undelete and confirm_sesskey()) {   // Undelete a selected user, after confirmation

        if (!has_capability('moodle/user:delete', $sitecontext)) {
            error('You do not have the required permission to undelete a user.');
        }

        if (!$user = get_record('user', 'id', $undelete)) {
            error("No such user!", '', true);
        }

        if ($confirm != md5($undelete)) {
            $fullname = fullname($user, true);
            print_heading(get_string('undeleteuser', 'admin'));
            $optionsyes = array('undelete'=>$undelete, 'confirm'=>md5($undelete), 'sesskey'=>sesskey());
            notice_yesno('Are you sure you want to restore ' . $fullname . ' ?', 'undel_user.php', 'undel_user.php', $optionsyes, NULL, 'post', 'get');
            admin_externalpage_print_footer();
            die;
        } else if (data_submitted() and $user->deleted) {
            if (undelete_user($user)) {
                notify(get_string('undeletedactivity', '', fullname($user, true)) );
            } else {
                notify(get_string('restorednot', '', fullname($user, true)));
            }
        }
    } 

    // create the user filter form
    $ufiltering = new user_filtering();

    // Carry on with the user listing

    $columns = array("firstname", "lastname", "city", "country", "lastaccess");
    foreach ($columns as $column) {
        $string[$column] = get_string("$column");
        if ($sort != $column) {
            $columnicon = "";
            if ($column == "lastaccess") {
                $columndir = "DESC";
            } else {
                $columndir = "ASC";
            }
        } else {
            $columndir = $dir == "ASC" ? "DESC":"ASC";
            if ($column == "lastaccess") {
                $columnicon = $dir == "ASC" ? "up":"down";
            } else {
                $columnicon = $dir == "ASC" ? "down":"up";
            }
            $columnicon = " <img src=\"$CFG->pixpath/t/$columnicon.gif\" alt=\"\" />";

        }
        $$column = "<a href=\"undel_user.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
    }

    if ($sort == "name") {
        $sort = "firstname";
    }

    $extrasql = $ufiltering->get_sql_filter();
    $users = get_deletedusers_listing(true, $sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql);
    $delusercount = get_deletedusers_listing(false, $sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql);
    $alphabet = explode(',', get_string('alphabet'));
    $strall = get_string('all');
    print_paging_bar($delusercount, $page, $perpage,
            "undel_user.php?sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;");
    flush();

    if (!$users) {
        $match = array();
        print_heading("No deleted users found");
        $table = NULL;

    } else {
        $countries = get_list_of_countries();
        foreach ($users as $key => $user) {
            if (!empty($user->country)) {
                $users[$key]->country = $countries[$user->country];
            }
        }
        if ($sort == "country") {  // Need to resort by full country name, not code
            foreach ($users as $user) {
                $susers[$user->id] = $user->country;
            }
            asort($susers);
            foreach ($susers as $key => $value) {
                $nusers[] = $users[$key];
            }
            $users = $nusers;
        }
        $mainadmin = get_admin();
        $override = new object();
        $override->firstname = 'firstname';
        $override->lastname = 'lastname';
        $fullnamelanguage = get_string('fullnamedisplay', '', $override);
        if (($CFG->fullnamedisplay == 'firstname lastname') or
            ($CFG->fullnamedisplay == 'firstname') or
            ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
            $fullnamedisplay = "$firstname / $lastname";
        } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname') 
            $fullnamedisplay = "$lastname / $firstname";
        }
        $table->head = array ($fullnamedisplay, $city, $country, $lastaccess, "", "", "");
        $table->align = array ("left", "left", "left", "left", "center", "center", "center");
        $table->width = "95%";
        foreach ($users as $user) {
            if ($user->username == 'guest') {
                continue; // do not display dummy new user and guest here
            }

            if ($user->id == $USER->id) {
                $undeletebutton = "";
            } else {
                if (has_capability('moodle/user:delete', $sitecontext)) {
                    $undeletebutton = "<a href=\"undel_user.php?undelete=$user->id&amp;sesskey=$USER->sesskey\">$strundelete</a>";
                } else {
                    $undeletebutton ="";
                }
            }

            if ($user->lastaccess) {
                $strlastaccess = format_time(time() - $user->lastaccess);
            } else {
                $strlastaccess = get_string('never');
            }
            $fullname = fullname($user, true);

            $table->data[] = array ("<a href=\"../user/view.php?id=$user->id&amp;course=$site->id\">$fullname</a>",
                                "$user->city",
                                "$user->country",
                                $strlastaccess,
                                "",
                                $undeletebutton,
                                "");
        }
    }

    // add filters
    $ufiltering->display_add();
    $ufiltering->display_active();

    if (!empty($table)) {
        print_table($table);
        print_paging_bar($usercount, $page, $perpage,
                         "undel_user.php?sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;");
    }

    admin_externalpage_print_footer();

    /**
     * get_deletedusers_listing retrieves the user records of deleted employees
     * in order to "undelete" select employee records
     * 
     * shortdesc (optional)
     *
     * longdesc
     *
     * @uses $CFG
     * @param boolean $get  If true, return recordset; if false, return count of recordset
     * @param string $sort ?
     * @param string $dir ?
     * @param int $categoryid ?
     * @param int $categoryid ?
     * @param string $search ?
     * @param string $firstinitial ?
     * @param string $lastinitial ?
     * @returnobject {@link $USER} records
     * @todo Finish documenting this function
     */

  function get_deletedusers_listing($get, $sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='') {

    global $CFG;

    $LIKE      = sql_ilike();
    $fullname  = sql_fullname();
    $select = "deleted = '1'";

    if (!empty($search)) {
        $search = trim($search);
        $select .= " AND ($fullname $LIKE '%$search%' OR email $LIKE '%$search%' OR username='$search') ";
    }

    if ($firstinitial) {
        $select .= ' AND firstname '. $LIKE .' \''. $firstinitial .'%\' ';
    }

    if ($lastinitial) {
        $select .= ' AND lastname '. $LIKE .' \''. $lastinitial .'%\' ';
    }

    if ($extraselect) {
        $select .= " AND $extraselect ";
    }

    if ($sort) {
        $sort = ' ORDER BY '. $sort .' '. $dir;
    }

    // WARNING: This query will return DELETED USERS
    if ($get) {
      return get_records_sql("SELECT id, username, firstname, lastname, city, country, lastaccess, confirmed, mnethostid
                                FROM {$CFG->prefix}user
                              WHERE $select $sort", $page, $recordsperpage);
    } else {
      return count_records_select('user', $select);
    }
  }
  
  /**
  * Reverses deleted flag of user record in user table and notifies the auth plugin.
  * @param object $user       Userobject before restoral (or 'undelete') (without system magic quotes)
  * @return boolean success
  */
  function undelete_user($user) {
    global $CFG;
    require_once($CFG->libdir.'/grouplib.php');
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/message/lib.php');

    begin_sql();

    // workaround for bulk deletes of users with the same email address
    $delname = addslashes("$user->email.".time());
    while (record_exists('user', 'username', $delname)) { // no need to use mnethostid here
        $delname++;
    }

    // Reverse the internal user record as "undeleted".
    // That name will show up on the 'user' list, and requires the administrator
    // to reset the username, email address, and password using the normal 
    // 'add/edit user' pages.
    $updateuser = new object();
    $updateuser->id           = $user->id;
    $updateuser->deleted      = 0;
    $updateuser->username     = 'xxxx';                      // Clear out delete-modified username
    $updateuser->email        = 'anyone@westbayri.org';      // Clear out MD5 hash marking deleted record
    $updateuser->idnumber     = '';                          // Clear this field to free it up
    $updateuser->timemodified = time();

    if (update_record('user', $updateuser)) {
        commit_sql();
        // Notify auth plugin - do not block the delete even when plugin fails
//        $authplugin = get_auth_plugin($user->auth);
//        $authplugin->user_delete($user);
//        events_trigger('user_deleted', $user);
        return true;

    } else {
        rollback_sql();
        return false;
    }
  }
?>
