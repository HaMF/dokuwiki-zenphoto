This Dokuwiki plugin automatically copies new users in a dokuwiki installation to a Zenphoto user database. When a user's details change, the changes are applied to Zenphotos user database too. It provides a single sign on mechanism as well. This plugin is based on `https://github.com/falstaff84/dokuwiki-zenphoto` which is unfortunately not maintained any more.

### Requirements

  * Zenphoto installation (tested with 1.4.4)
  * Dokuwiki installation (tested with 2013-05-10 "Weatherwax")
  * PHP MySQL-PDO


### Installation

Use `https://github.com/HaMF/dokuwiki-zenphoto-sso/tarball/stable` in the Dokuwiki Plugin Manager to download and install the current version of the plugin. Be sure to enable the openssl extension in your php.ini or downloading the file will fail. You can also follow the instructions on how to manually install plugins at `https://www.dokuwiki.org/plugin_installation_instructions#manual_instructions` with the URL above.


### Configuration

  * Add your Zenphoto MySQL details to the plugin's configuration variables.
  * Find the password hash seed in your zenphoto database and put it in the corresponding field of the configuration. You can use the following command to obtain the seed:
        `mysql -u root -p -D zenphoto -e "SELECT name, value FROM photos_options WHERE name = 'extra_auth_hash_text';"`
    replace "photos_" with the table prefix you chose for your zenphoto installation.
  * Mind chosing the correct path for your zenphoto installation which is the last part of the URL of the zenphoto installation leading/trailing slashes will be automatically added as appropriate.
  * Add any users you don't want to be copied to the Zenphoto database to the ignore users configuration field. It is a good idea to put the names of any    
    existing zenphoto users in this field separated by just a comma. Otherwise a user of your dokuwiki with the same name might overwrite the password of the user in the zenphoto database.


### Uninstall

Mind that uninstalling the plugin won't delete users created by the plugin as of now.


### Contribute

Feel free to fork, hack and post pull requests. Theres still stuff to do such as an option to remove the generated users upon plugin removal, generation of zenphoto accounts for existing dokuwiki users,… and probably tons of bugs. Also, this is GPLv3, so feel free to reuse the code, a short notice would be much appreciated. :)