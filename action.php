<?php
    /**
     * Synchronize dokuwiki user database with zenphoto database
     *
     * The plugin hooks into user creation and modification as well
     * as log on and log off events and relays them to a zenphoto 
     * instance.
     * 
     * @author     Hannes Maier-Flaig <hamfbohne@gmail.com>
     * @author     Stefan Agner <falstaff@deheime.ch>
     */
     
    if(!defined('DOKU_INC')) die();
    if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
    require_once DOKU_PLUGIN.'action.php';

    class action_plugin_zenphotosso extends DokuWiki_Action_Plugin {
        var $_auth;
        var $dbh;
        var $ignored_users;
        var $groups;
        var $zp_cookie_name;
        var $zp_path;
        var $zp_mysql_user;
        var $zp_mysql_pass;
        var $zp_mysql_host;
        var $zp_mysql_database;
        var $zp_mysql_prefix;
        var $zp_userpass_hash; // This hash value can be found in the zenphoto options database with key "extra_auth_hash_text"
        var $zp_rights;
        var $zp_hash_method;
        var $zp_albums;


        function action_plugin_zenphotosso() {
            $this->zp_cookie_name = 'zp_user_auth';
            $this->zp_path = '/' . trim($this->getConf('zenphoto_path'), '/') . '/';
            $this->zp_mysql_user = $this->getConf('mysql_user');
            $this->zp_mysql_pass = $this->getConf('mysql_password');
            $this->zp_mysql_host = $this->getConf('mysql_host');
            $this->zp_mysql_database = $this->getConf('mysql_database');
            $this->zp_mysql_prefix = $this->getConf('mysql_prefix');
            $this->zp_userpass_hash = $this->getConf('user_password_hash');
            $this->zp_hash_method = self::getNumericHashMethod($this->getConf('zp_hash_method'));
            $this->zp_rights = self::getNumericRights($this->getConf('zenphoto_permissions'));
            $this->zp_albums = explode(",", $this->getConf('upload_albums'));
            $this->ignored_users = explode(",", $this->getConf('ignored_users'));
            $this->groups = explode(",", $this->getConf('groups'));

            $this->setupLocale();

            global $auth;        
            $this->_auth = & $auth;
        }


        /** 
         * Connect to ZP mysql database and store database connection handle in class variable 
         * 
         * @return PDO databasehandle | bool false on error
         */
        function getDatabaseHandle() {
            if (isset($this->dbh) ) {
                return $this->dbh;
            }

            try {
                $this->dbh = new PDO('mysql:host='.$this->zp_mysql_host.';port=9306;dbname='.$this->zp_mysql_database.';', $this->zp_mysql_user, $this->zp_mysql_pass);
                return $this->dbh;
            } catch (PDOException $e) {
                dbglog("Error!: " . $e->getMessage());
                return false;
            }
        }

        /**
         * Convert a human readable string (config option) for the hash algorithm to 
         * numeric representation as used by ZP
         * 
         * @param string hash_algorithm (md5, sha1, pbkdf2)
         * @return integer hash_algorithm (0, 1, 2)
         */  
        static function getNumericHashMethod($zp_hash_method) {
            $hash_methods = array('md5' => 0, 'sha1' => 1, 'pbkdf2' => 2);

            return $hash_methods[$zp_hash_method];
        }

        /**
         * Convert list of human readable rights strings to one numeric value
         * (additive with values as defined in rightsset) as stored in the database.
         * 
         * @param array zenphoto_permissions array of strings as in ZP rightsset
         * @return array zenphoto_permissions array of integers
         */
        static function getNumericRights($zenphoto_permissions) {
            $right_to_numeric = function($v, $search_key) {
                return $v + self::getRightsset()[$search_key]['value'];
            };

            $rights = explode(",", $zenphoto_permissions);
            
            return array_reduce($rights, $right_to_numeric);
        }
            
        /**
         * Return ZP rightsset that lists user rights and corresponding
         * numerical representation 
         *
         * @return array rightsset
         */
        static function getRightsset() {
            return array( 'NO_RIGHTS' => array('value'=>1,'name'=>gettext('No rights'),'set'=>'','display'=>false,'hint'=>''),
                        'OVERVIEW_RIGHTS' => array('value'=>pow(2,2),'name'=>gettext('Overview'),'set'=>gettext('General'),'display'=>true,'hint'=>gettext('Users with this right may view the admin overview page.')),
                        'USER_RIGHTS' => array('value'=>pow(2,3),'name'=>gettext('User'),'set'=>gettext('General'),'display'=>true,'hint'=>gettext('Users must have this right to change their credentials.')),

                        'VIEW_GALLERY_RIGHTS' => array('value'=>pow(2,5),'name'=>gettext('View gallery'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('Users with this right may view otherwise protected generic gallery pages.')),
                        'VIEW_SEARCH_RIGHTS' => array('value'=>pow(2,6),'name'=>gettext('View search'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('Users with this right may view search pages even if password protected.')),
                        'VIEW_FULLIMAGE_RIGHTS' => array('value'=>pow(2,7),'name'=>gettext('View fullimage'),'set'=>gettext('Albums'),'display'=>true,'hint'=>gettext('Users with this right may view all full sized (raw) images.')),
                        'ALL_NEWS_RIGHTS' => array('value'=>pow(2,8),'name'=>gettext('Access all'),'set'=>gettext('News'),'display'=>true,'hint'=>gettext('Users with this right have access to all zenpage news articles.')),
                        'ALL_PAGES_RIGHTS' => array('value'=>pow(2,9),'name'=>gettext('Access all'),'set'=>gettext('Pages'),'display'=>true,'hint'=>gettext('Users with this right have access to all zenpage pages.')),
                        'ALL_ALBUMS_RIGHTS' => array('value'=>pow(2,10),'name'=>gettext('Access all'),'set'=>gettext('Albums'),'display'=>true,'hint'=>gettext('Users with this right have access to all albums.')),
                        'VIEW_UNPUBLISHED_RIGHTS' => array('value'=>pow(2,11),'name'=>gettext('View unpublished'),'set'=>gettext('Albums'),'display'=>true,'hint'=>gettext('Users with this right will see all unpublished items.')),

                        'POST_COMMENT_RIGHTS'=> array('value'=>pow(2,13),'name'=>gettext('Post comments'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('When the comment_form plugin is used for comments and its "Only members can comment" option is set, only users with this right may post comments.')),
                        'COMMENT_RIGHTS' => array('value'=>pow(2,14),'name'=>gettext('Comments'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('Users with this right may make comments tab changes.')),
                        'UPLOAD_RIGHTS' => array('value'=>pow(2,15),'name'=>gettext('Upload'),'set'=>gettext('Albums'),'display'=>true,'hint'=>gettext('Users with this right may upload to the albums for which they have management rights.')),

                        'ZENPAGE_NEWS_RIGHTS' => array('value'=>pow(2,17),'name'=>gettext('News'),'set'=>gettext('News'),'display'=>false,'hint'=>gettext('Users with this right may edit and manage Zenpage articles and categories.')),
                        'ZENPAGE_PAGES_RIGHTS' => array('value'=>pow(2,18),'name'=>gettext('Pages'),'set'=>gettext('Pages'),'display'=>false,'hint'=>gettext('Users with this right may edit and manage Zenpage pages.')),
                        'FILES_RIGHTS' => array('value'=>pow(2,19),'name'=>gettext('Files'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('Allows the user access to the "filemanager" located on the upload: files sub-tab.')),
                        'ALBUM_RIGHTS' => array('value'=>pow(2,20),'name'=>gettext('Albums'),'set'=>gettext('Albums'),'display'=>false,'hint'=>gettext('Users with this right may access the "albums" tab to make changes.')),

                        'MANAGE_ALL_NEWS_RIGHTS' => array('value'=>pow(2,21),'name'=>gettext('Manage all'),'set'=>gettext('News'),'display'=>true,'hint'=>gettext('Users who do not have "Admin" rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any Zenpage news article or category.')),
                        'MANAGE_ALL_PAGES_RIGHTS' => array('value'=>pow(2,22),'name'=>gettext('Manage all'),'set'=>gettext('Pages'),'display'=>true,'hint'=>gettext('Users who do not have "Admin" rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any Zenpage page.')),
                        'MANAGE_ALL_ALBUM_RIGHTS' => array('value'=>pow(2,23),'name'=>gettext('Manage all'),'set'=>gettext('Albums'),'display'=>true,'hint'=>gettext('Users who do not have "Admin" rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any album in the gallery.')),

                        'THEMES_RIGHTS' => array('value'=>pow(2,26),'name'=>gettext('Themes'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('Users with this right may make themes related changes. These are limited to the themes associated with albums checked in their managed albums list.')),

                        'TAGS_RIGHTS' => array('value'=>pow(2,28),'name'=>gettext('Tags'),'set'=>gettext('Gallery'),'display'=>true,'hint'=>gettext('Users with this right may make additions and changes to the set of tags.')),
                        'OPTIONS_RIGHTS' => array('value'=>pow(2,29),'name'=>gettext('Options'),'set'=>gettext('General'),'display'=>true,'hint'=>gettext('Users with this right may make changes on the options tabs.')),
                        'ADMIN_RIGHTS' => array('value'=>pow(2,30),'name'=>gettext('Admin'),'set'=>gettext('General'),'display'=>true,'hint'=>gettext('The master privilege. A user with "Admin" can do anything. (No matter what his other rights might indicate!)')));
        }


        /**
         * Register its handlers with the DokuWiki's event controller
         */
        function register(&$controller) {

            $controller->register_hook('AUTH_LOGIN_CHECK', 'BEFORE', $this,
                                       'event_login');
            $controller->register_hook('AUTH_USER_CHANGE', 'AFTER', $this,
                                       'event_userchange');
            $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this,
                                       'event_headers_send');
        }

        /**
         * Check if user is still logged in just before headers are sent (to be able to delete the cookie)
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function event_headers_send(&$event, $param) {
            // No userlogin, might be a logout 
            if($_SERVER['REMOTE_USER'] == "") {
                $this->zenphoto_logout();
            }
        }

        /**
         * Hook into login event and log in to zenphoto too
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function event_login(&$event, $param) {
            // Check if user is set (this is only the case if we just pressed login, while the session is running the event happens but no user is set)
            if($event->data['user'] != "" && ! in_array($event->data['user'], $this->ignored_users)) {
                $this->zenphoto_login($event->data['user'], $event->data['password'], $event->data['sticky'] == 1);
            }
        }

        /**
         * Create/Update user information in zenphoto database
         */
        function event_userchange(&$event, $param) {
            if (in_array($event->data['params'][0], $this->ignored_users)) {
                return false;
            }

            if ( ! $dbh = $this->getDatabaseHandle()) {
                return false;
            }

            /* check wether user belongs to a group that is supposed to be synced to zenphoto */
            if (count($this->groups)) {
                global $conf;
                if ($event->data['type'] == 'create' && ! array_key_exists(4, $event->data['params']) && $event->data['modification_result']) { /* new user signed up, so default group */
                    $newGroups = array($conf['defaultgroup']);
                } elseif ($event->data['type'] == 'create' && array_key_exists(4, $event->data['params']) && $event->data['modification_result']) { /* new user created in admin interface */
                    if( ! isset($event->data['params'][4])) { /* group has not been set explicitly so its the default group */
                        $newGroups = array($conf['defaultgroup']);
                    } else { /* group has been specified explicitly */
                        $newGroups = $event->data['params'][4];
                    }
                } elseif ($event->data['type'] == 'modify' && isset($event->data['params'][1]["grps"]) && $event->data['modification_result']) { /* user has been modified and groups changed */
                    $newGroups = $event->data['params'][1]["grps"];
                } elseif ($event->data['type'] == 'modify' && ! isset($event->data['params'][1]["grps"]) && $event->data['modification_result']) { /* user has been modified but groups didn't changed */
                    $username = $event->data['params'][0];
                    $user = $this->_auth->retrieveUsers(0, 1, array("user" => $username));
                    $newGroups = $user[$username]["grps"];
                } else {
                    $newGroups = array();
                }

                if ( ! count(array_intersect($newGroups, $this->groups))
                    && $event->data['type'] != 'delete'
                    && ! ($event->data['type'] == 'modify' && $event->data['modification_result'] && isset($event->data['params'][1]["grps"]))) {
                    return false;
                }
            }

            /* create or modify zenphoto user */
            if($event->data['type'] == 'create' && $event->data['modification_result'])
            {
                $this->zenphoto_createUser($event->data['params'][0], $event->data['params'][1], $event->data['params'][2], $event->data['params'][3]);
                $this->zenphoto_grantAlbumRights($event->data['params'][0]);

            }
            else if ($event->data['type'] == 'modify' && $event->data['modification_result'])
            {
                $username = $event->data['params'][0];
                if (isset($event->data['params'][1]["grps"]))
                {
                    if ((count($this->groups) == 0 || count(array_intersect($newGroups, $this->groups))) && isset($event->data['params'][1]["pass"]))
                    {
                        /* doesn't work nicely because $user[pass] is a hash, give the user appropriate feedback if he didn't also change the password*/
                        $user = $this->_auth->retrieveUsers(0, 1, array("user" => $username));
                        $this->zenphoto_createUser($username, $event->data['params'][1]['pass'], $user[$username]['name'], $user[$username]['email']);
                        $this->zenphoto_grantAlbumRights($username);
                    } else {
                        $this->zenphoto_deleteUser($username);
                        return true;
                    }
                }
                if (isset($event->data['params'][1]["name"]) && $this->zenphoto_userExists($username))
                {
                    $update_query =  $dbh->prepare("UPDATE ".$this->zp_mysql_prefix."administrators SET name = :name WHERE user = :user;");
                    $update_query->bindParam(":name", $event->data['params'][1]["name"]);
                    $update_query->bindParam(":user", $username);
                    $update_query->execute();

                    $this->zenphoto_grantAlbumRights($username);
                }

                if (isset($event->data['params'][1]["mail"]) && $this->zenphoto_userExists($username))
                {
                    $update_query =  $dbh->prepare("UPDATE ".$this->zp_mysql_prefix."administrators SET email = :email WHERE user = :user;");
                    $update_query->bindParam(":email", $event->data['params'][1]["mail"]);
                    $update_query->bindParam(":user", $username);
                    $update_query->execute();

                    $this->zenphoto_grantAlbumRights($username);
                }

                if (isset($event->data['params'][1]["pass"]) && $this->zenphoto_userExists($username))
                {
                    $update_query =  $dbh->prepare("UPDATE ".$this->zp_mysql_prefix."administrators SET pass = :pass, passhash = :passhash WHERE user = :user;");
                    $update_query->bindParam(":pass", $this->zenphoto_hashpw($username, $event->data['params'][1]["pass"]));
                    $update_query->bindParam(":passhash", $this->zp_hash_method);
                    $update_query->bindParam(":user", $username);
                    $update_query->execute();

                    $this->zenphoto_login($username, $event->data['params'][1]["pass"]);

                    $this->zenphoto_grantAlbumRights($username);
                }
            }
            else if($event->data['type'] == 'delete' && $event->data['modification_result'] > 0)
            {
                // $data['params'][0] => List of users to delete (array)
                foreach( $event->data['params'][0] as $user) {
                    $this->zenphoto_deleteAlbumRightsForUser($user);
                    $this->zenphoto_deleteUser($user);
                }
            }
        }


        /** PBKDF2 Implementation (described in RFC 2898)
         *  from zenphoto. licensed under te gpl 2.0
         *
         *  @param string p password
         *  @param string s salt
         *  @param int c iteration count (use 1000 or higher)
         *  @param int kl derived key length
         *  @param string a hash algorithm
         *
         *  @return string derived key
        */ 
        static function pbkdf2($p, $s, $c=1000, $kl=32, $a = 'sha256') {
                        $hl = strlen(hash($a, null, true)); # Hash length
                        $kb = ceil($kl / $hl);              # Key blocks to compute
                        $dk = '';                           # Derived key
                        # Create key
                        for ( $block = 1; $block <= $kb; $block ++ ) {
                                        # Initial hash for this block
                                        $ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);
                                        # Perform block iterations
                                       for ( $i = 1; $i < $c; $i ++ )
                                                        # XOR each iterate
                                                        $ib ^= ($b = hash_hmac($a, $b, $p, true));
                                        $dk .= $ib; # Append iterated block
                 }
                        # Return derived key of correct length
                        return substr($dk, 0, $kl);
        }

        /**
         * Get the correct password hashing algorithm for a user with given username
         *
         *  @param string user username
         * 
         *  @return (integer >= 2) 2=pbkdf2 1=sha1 0=md5 
         */
        function zenphoto_getUserHashMethod($username) {
            if ($dbh = $this->getDatabaseHandle()) {
                $select_query = $dbh->prepare('SELECT user, passhash  FROM  '. $this->zp_mysql_prefix . 'administrators  WHERE user = :user;');
                $select_query->bindParam(':user', $username);
                $select_query->execute();
                
                $hashmethod = $select_query->fetchColumn(1);
                if ($hashmethod === FALSE) {
                    $hashmethod = $this->zp_hash_method;
                }

                return $hashmethod;
            }
        }

        /**
         * Calculates password hash with the user dependend algorithm
         *
         *  @param string user
         *  @param string password
         * 
         *  @return string derived hash with seed conf zp_userpass_hash
         */
        function zenphoto_hashpw($username, $password) {
            switch ($this->zenphoto_getUserHashMethod($username)) {
                case 2:
                    return base64_encode(self::pbkdf2($password,$username.$this->zp_userpass_hash));
                case 1:
                    return sha1($username.$password.$this->zp_userpass_hash);
                case 0:
                    return md5($username.$password.$this->zp_userpass_hash);
            }
        }

        /**
         * Get user-id in zenphoto by username
         *
         * @param string username
         * @param integer userid | bool false
         */

        function zenphoto_getUserId($username) {
            if ($dbh = $this->getDatabaseHandle()) {
                $select_query = $dbh->prepare("SELECT id FROM " . $this->zp_mysql_prefix . "administrators WHERE user = :user");
                $select_query->bindParam(":user", $username);
                $select_query->execute();
                $select_data = $select_query->fetch();

                return $select_data['id'];
            } else {
                return false;
            }
                
        }

        /**
         * Set zenphoto session cookie (log in to zenphoto)
         *
         * @param string user username
         * @param string password user's password
         * @param bool sticky lifetime of set cookie (1a if true)
         * 
         * @return void
         */
        function zenphoto_login($username, $password, $sticky=true) {
            if($this->getConf('single_sign_on'))
            {
                $userid = $this->zenphoto_getUserId($username);
                if( ! $userid) {
                    return false;
                }
                $pwhash = $this->zenphoto_hashpw($user, $password);
                if($sticky)
                    setcookie($this->zp_cookie_name, $pwhash . "." . $userid, time()+(60*60*24*365), $this->zp_path); // 1 year, Dokuwiki default
                else
                    setcookie($this->zp_cookie_name, $pwhash . "." . $userid, null, $this->zp_path); // browser close
            }
        }

        /**
         * Remove zenphoto session cookie (log out)
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function zenphoto_logout() {
            if($this->getConf('single_sign_on'))
              setcookie($this->zp_cookie_name, '', time()-31536000, $this->zp_path);
        }

        /**
         * Grant upload (and view) rights to the albums mentioned in conf(upload_albums)
         *
         * @param string username
         * @return bool false on failure
         */
        function zenphoto_grantAlbumRights($username) {
            $userid = $this->zenphoto_getUserId($username);
            if( ! $userid) {
                return false;
            }
            
            if(count($this->zp_albums) == 0) {
                return false;
            }


            if ($dbh = $this->getDatabaseHandle()) {
                $placeholders = str_repeat('?,', count($this->zp_albums) - 1) . '?';
                $select_query = $dbh->prepare('SELECT id, title FROM  '. $this->zp_mysql_prefix . 'albums  WHERE title IN ('.$placeholders.');');
                $select_query->setFetchMode(PDO::FETCH_ASSOC);
                $select_query->execute($this->zp_albums);
                foreach ($select_query as $result) {
                    /* check if the connection between user and album already exists */
                    $sao_query = $dbh->query('SELECT COUNT(*) ' .
                        'FROM  '. $this->zp_mysql_prefix . 'admin_to_object ' .
                        'WHERE adminid = ' . $userid . ' AND objectid = ' . $result["id"] . ' AND type = "albums";');
                    $entryexists = $sao_query->fetchColumn();
                    if ($entryexists) {
                        continue;
                    }

                    /* create the connection between user and album */
                    $insert_query = $dbh->prepare('INSERT INTO '. $this->zp_mysql_prefix . 'admin_to_object (adminid, objectid, type, edit) ' .
                        'VALUES (:userid, :albumid, "albums", 32763);'); //grant full rights
                    $insert_query->bindParam(':userid', $userid);
                    $insert_query->bindValue(':albumid', $result["id"]);
                    $insert_query->execute();
                }
            } else {
                return false;
            }
        }

        /**
         * Grant upload (and view) rights to the albums mentioned in conf(upload_albums)
         *
         * @param string username
         * @return bool false on failure
         */
        function zenphoto_deleteAlbumRightsForUser($username) {
            $userid = $this->zenphoto_getUserId($username);
            if( ! $userid) {
                return false;
            }
            
            if(count($this->zp_albums) == 0) {
                return false;
            }


            if ($dbh = $this->getDatabaseHandle()) {
                $placeholders = str_repeat('?,', count($this->zp_albums) - 1) . '?';
                $select_query = $dbh->prepare('SELECT id, title FROM  '. $this->zp_mysql_prefix . 'albums  WHERE title IN ('.$placeholders.');');
                $select_query->setFetchMode(PDO::FETCH_ASSOC);
                $select_query->execute($this->zp_albums);
                foreach ($select_query as $result) {
                    /* check if the connection between user and album already exists */
                    $delete_query = $dbh->query('DELETE ' .
                        'FROM  '. $this->zp_mysql_prefix . 'admin_to_object ' .
                        'WHERE adminid = ' . $userid . ' AND objectid = ' . $result["id"] . ' AND type = "albums";');
                    return $delete_query->execute();
                }
            } else {
                return false;
            }
        }

        /** Check if user with $username exists in zenphoto database
         * @param string username
         * @return integer 0 if does not exist, userid otherwise
         */
        function zenphoto_userExists($username) {
            return $this->zenphoto_getUserId($username);
        }

        /** Delete user with $username in zenphoto database
         * @param string username
         * @return bool
         */
        function zenphoto_deleteUser($username) {
            if ( ! $dbh = $this->getDatabaseHandle()) {
                return false;
            }

            $delete_query = $dbh->prepare("DELETE FROM ".$this->zp_mysql_prefix."administrators WHERE user = :user;");
            $delete_query->bindParam(":user", $username);
            return $delete_query->execute();
        }

        /** 
         * Create user in zenphoto database according to details of dokuwiki user with $username 
         * 
         * @param string username
         * @param string password
         * @param string name
         * @param string email
         * @return bool
         */
        function zenphoto_createUser($username, $password, $name, $email) {
            if ($this->zenphoto_userExists($username)) {
                return false;
            }
            if ( ! $dbh = $this->getDatabaseHandle()) {
                return false;
            }
            $create_query = $dbh->prepare("INSERT INTO ".$this->zp_mysql_prefix."administrators (user, pass, passhash, name, email, rights, valid, custom_data) ".
                            "VALUES (:user, :pass, :passhash, :name, :email, :rights, :valid, :custom);");
            $create_query->bindParam(':user',     $username);
            $create_query->bindParam(':pass',     $this->zenphoto_hashpw($username, $password));
            $create_query->bindParam(':passhash', $this->zp_hash_method);
            $create_query->bindParam(':name',     $name);
            $create_query->bindParam(':email',    $email);
            $create_query->bindParam(':rights',   $this->zp_rights);
            $create_query->bindValue(':valid',    1);
            $create_query->bindValue(':custom',   "User generated by DokuWiki zenphotosso Plug-In.");
            return $create_query->execute();
        }
    }
