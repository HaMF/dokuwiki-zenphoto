<?php

$meta['zenphoto_path'] = array('string');
$meta['mysql_user'] = array('string');
$meta['mysql_password'] = array('string');
$meta['mysql_host'] = array('string');
$meta['mysql_database'] = array('string');
$meta['mysql_prefix'] = array('string');
$meta['user_password_hash'] = array('string');
$meta['synchronize_users'] = array('onoff');

/* rightset as from zenphoto lib-auth.php
  define gettext() as dummy function so we can just copy and paste for new versions of zenphoto.
  you also need to copy it to action.php for the time being
  FIXME: figure out how to do the localization correctly, use the numerical values right away and translate them to something usable*/
$rightsset = array(	'NO_RIGHTS' => array('value'=>1,'name'=>gettext('No rights'),'set'=>'','display'=>false,'hint'=>''),
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

$meta['zenphoto_permissions'] = array('multicheckbox','_choices' => array_keys($rightsset));

$meta['zp_hash_method'] = array('multichoice', '_choices' => array('pbkdf2', 'sha1', 'md5'));

$meta['single_sign_on'] = array('onoff');
