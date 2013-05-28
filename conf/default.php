<?php

$conf['zenphoto_path'] = "/photos/";
$conf['mysql_user'] = "";
$conf['mysql_password'] = "";
$conf['mysql_host'] = "localhost";
$conf['mysql_database'] = "zenphoto";
$conf['mysql_prefix'] = "photos_";
$conf['user_password_hash'] = 'can be found in the zenphoto options table as "extra_auth_hash_text"';
$conf['synchronize_users'] = 1;
$conf['zenphoto_permissions'] = 'USER_RIGHTS, OVERVIEW_RIGHTS, UPLOAD_RIGHTS, ALBUM_RIGHTS, VIEW_FULLIMAGE_RIGHTS, VIEW_GALLERY_RIGHTS';
$conf['zp_hash_method'] = 'pbkdf2';
$conf['single_sign_on'] = 1;
$conf['ignored_users'] = 'admin';
$conf['upload_albums'] = '';
$conf['groups'] = array('user');

