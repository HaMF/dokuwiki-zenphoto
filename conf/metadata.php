<?php

$meta['zenphoto_path'] = array('string');
$meta['mysql_user'] = array('string');
$meta['mysql_password'] = array('string');
$meta['mysql_host'] = array('string');
$meta['mysql_database'] = array('string');
$meta['mysql_prefix'] = array('string');
$meta['user_password_hash'] = array('string');
$meta['synchronize_users'] = array('onoff');
$meta['zenphoto_permissions'] = array('multicheckbox','_choices' => array_keys(action_plugin_zenphotosso::getRightsset()));
$meta['zp_hash_method'] = array('multichoice', '_choices' => array('pbkdf2', 'sha1', 'md5'));
$meta['single_sign_on'] = array('onoff');
$meta['ignored_users'] = array('string');
$meta['upload_albums'] = array('string');
$meta['groups'] = array('string');
