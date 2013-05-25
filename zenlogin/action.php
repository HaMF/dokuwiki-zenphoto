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

    class action_plugin_zenlogin extends DokuWiki_Action_Plugin {
        var $cookie_name;
        var $zp_path;
        var $zp_mysql_user;
        var $zp_mysql_pass;
        var $zp_mysql_host;
        var $zp_mysql_database;
        var $zp_mysql_prefix;
        var $zp_userpass_hash; // This hash value could be found on zenphoto admin/options/general tab
        var $zp_rights;

        function action_plugin_zenlogin() {
            $this->cookie_name = 'zp_user_auth';
            $this->zp_path = $this->getConf('zenphoto_path');
            $this->zp_mysql_user = $this->getConf('mysql_user');
            $this->zp_mysql_pass = $this->getConf('mysql_password');
            $this->zp_mysql_host = $this->getConf('mysql_host');
            $this->zp_mysql_database = $this->getConf('mysql_database');
            $this->zp_mysql_prefix = $this->getConf('mysql_prefix');
            $this->zp_userpass_hash = $this->getConf('user_password_hash');
            $this->zp_rights = self::getNumericRights($this->getConf('zenphoto_permissions'));
        }


        static function getNumericRights($zenphoto_permissions) {
            $right_to_numeric = function($v, $search_key) {
                $rightsset = array( 'NO_RIGHTS' => array('value'=>1,'name'=>gettext('No rights'),'set'=>'','display'=>false,'hint'=>''),
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

                return $v + $rightsset[$search_key]['value'];
            };

            $rights = split(",", $zenphoto_permissions);
            
            return array_reduce($rights, $right_to_numeric);
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
         * Calculates password hash the zenphoto way
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        static function zenphoto_hashpw($user, $password) {
            return md5($user.$password.$this->zp_userpass_hash);
        }

        /**
         * Get user-id in zenphoto by user name
         *
         * @param string username
         */

        function zenphoto_getUserId($username) {
            try {
                $dbh = new PDO('mysql:host='.$this->zp_mysql_host.';port=9306;dbname='.$this->zp_mysql_database.';', $this->zp_mysql_user, $this->zp_mysql_pass);
            } catch (PDOException $e) {
                print "Error!: " . $e->getMessage() . "<br/>";
                die();
            }

            $select_query = $dbh->prepare("SELECT id FROM administrators WHERE user = :user");
            $select_query->bindParam(":user", $username);
            $select_data = $select_query->fetch();

            print_r($select_data);
            return $select_data['id'];
        }

        /**
         * Set cookie to login zenphoto as well
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function zenphoto_login($user, $password, $sticky=true) {
            if($this->getConf('single_sign_on'))
            {
                $userid = $this->zenphoto_getUserId($user);
                $pwhash = $this->zenphoto_hashpw($user, $password);
                if($sticky)
                    setcookie($this->cookie_name, $pwhash . "." . $userid, time()+(60*60*24*365), $this->zp_path); // 1 year, Dokuwiki default
                else
                    setcookie($this->cookie_name, $pwhash . "." . $userid, null, $this->zp_path); // browser close
            }
        }

        /**
         * Set cookie to logout zenphoto as well
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function zenphoto_logout() {
            if($this->getConf('single_sign_on'))
              setcookie($this->cookie_name, '', time()-31536000, $this->zp_path);
        }

        /**
         * Check if user is still logged in just before headers are sent (to be able to delete the cookie)
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function event_headers_send(&$event, $param) {
            // No userlogin, might be a logout 
            if($_SERVER['REMOTE_USER'] == "")
                $this->zenphoto_logout();
        }


        /**
         * Set cookie to login zenphoto as well
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function event_login(&$event, $param) {
            // Check if user is set (this is only the case if we just pressed login, while the session is running the event happens but no user is set)
            if($event->data['user'] != "")
                $this->zenphoto_login($event->data['user'], $event->data['password'], $event->data['sticky'] == 1);
        }

        /**
         * Update user information in zenphoto as well
         *
         * @author Stefan Agner <stefan@agner.ch>
         */
        function event_userchange(&$event, $param) {
            // Connect to zenphoto database...
            $con = mysql_connect($this->zp_mysql_host,$this->zp_mysql_user,$this->zp_mysql_pass);
            if (!$con)
            {
                die('Could not connect: ' . mysql_error());
            }

            mysql_select_db($this->zp_mysql_database, $con);

            if($event->data['type'] == 'create' && $event->data['modification_result'])
            {
                $user = $event->data['params'][0];
                $pass = self::zenphoto_hashpw($user, $event->data['params'][1]);
                $name = $event->data['params'][2];
                $email = $event->data['params'][3];
                $custom_data = "User generated by DokuWiki zenlogin Plug-In.";
                mysql_query("INSERT INTO ".$this->zp_mysql_prefix."administrators (user, pass, passhash, name, email, rights, valid, custom_data) ".
                            "VALUES ('".$user."', '".$pass."', '0', '".$name."', '".$email."', ".$this->zp_rights.", 1, '".$custom_data."')", $con);
            }
            else if($event->data['type'] == 'modify' && $event->data['modification_result'])
            {
                // params is an array, [0] ==> Username, [1] ==> Fields
                $user = $event->data['params'][0]; 
                if(isset($event->data['params'][1]["name"]))
                {
                    $name = $event->data['params'][1]["name"];
                    mysql_query("UPDATE ".$this->zp_mysql_prefix."administrators SET name = '".$name."' WHERE user = '".$user."'", $con);
                }

                if(isset($event->data['params'][1]["mail"]))
                {
                    $email = $event->data['params'][1]["mail"];
                    mysql_query("UPDATE ".$this->zp_mysql_prefix."administrators SET email = '".$email."' WHERE user = '".$user."'", $con);
                }

                if(isset($event->data['params'][1]["pass"]))
                {
                    // Change the password with new hash
                    $pass = self::zenphoto_hashpw($user, $event->data['params'][1]["pass"]);
                    mysql_query("UPDATE ".$this->zp_mysql_prefix."administrators SET pass = '".$pass."' WHERE user = '".$user."'", $con);

                    // Also change the cookie for zenphoto
                    $this->zenphoto_login($user, $event->data['params'][1]["pass"]);
                }
            }
            else if($event->data['type'] == 'delete' && $event->data['modification_result'] > 0)
            {
                // params is an array, [0] ==> List of users to delete (array)

                // Modification result contains number of deleted users
                for($i = 0; $i < $event->data['modification_result'];$i++)
                {
                    $user = $event->data['params'][0][$i];
                    mysql_query("DELETE FROM ".$this->zp_mysql_prefix."administrators WHERE user = '".$user."'", $con);
                }
            }
            mysql_close($con);
            
        }


    }
