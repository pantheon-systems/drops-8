# SUMMARY

The simplesamlphp_auth module makes it possible for Drupal to support SAML for 
authentication of users. The module will auto-provision user accounts into 
Drupal if you want it to. It can also dynamically assign Drupal roles based on
identity attribute values.

# PREREQUISITES

1. You must have SimpleSAMLphp installed and configured as a working service 
point (SP) as the module uses your local SimpleSAMLphp SP for the SAML support. 
If you install the simplesamlphp_auth module with Composer support, you could 
use the codebase that will be placed in your 
docroot/vendor/simplesamlphp/simplesamlphp directory (see "Installation" below).

You can also download and install SimpleSAMLphp separately. For more 
information on installing and configuring SimpleSAMLphp as an SP visit: 
http://www.simplesamlphp.org.

IMPORTANT: Your SP must be configured to use something other than phpsession for
session storage (in config/config.php set store.type => 'memcache' or 'sql').

To use memcache session handling you must have memcached installed on your 
server and PHP must have the memcache extension. For more information on 
installing the memcache extension for PHP visit: 
http://www.php.net/manual/en/memcache.installation.php

If you are on a shared host or a machine that you cannot install memcache on 
then consider using the sql handler (store.type => 'sql').

Make sure your SimpleSAMLphp installation has a correctly configured "config" 
and "metadata" folder, and an appropriate vhost configuration. See 
http://www.simplesamlphp.org for more information.
   
2. You must have installed the ExternalAuth module 
(https://www.drupal.org/project/externalauth). See README.txt in the 
ExternalAuth module for installation instructions.
   
3. It is recommended to have Composer Manager 
(https://www.drupal.org/project/composer_manager) module installed and allow it 
to download the simplesamlphp libraries. See README.txt in the composer_manager
module for installation instructions. If Composer is not an option for you, see
the installation instructions below to link your SimpleSAMLphp instance with 
Drupal through settings.php.
 
# INSTALLATION

The Drupal simplesamlphp_auth module will need to connect to a working 
SimpleSAMLphp instance. This can be done in two ways - depending on your setup:

## INSTALLATION WITH COMPOSER MANAGER

Make sure you have the composer_manager module installed according to its 
README.txt.

1. Download the simplesamlphp_auth module
2. Uncompress it
3. Move it to the appropriate modules directory (usually, /modules)
4. Run the "composer drupal-update" command (see Prerequisites above)
5. The SimpleSAMLphp library will now be installed in your 
docroot/vendor/simplesamlphp/simplesamlphp directory.
Configure the library (see http://www.simplesamlphp.org) by adding the 'config'
and 'metadata' directories with appropriate settings. If is recommended to 
symlink those from your already installed SimpleSAMLphp instance or from 
another location where they are saved.
6. Go to the Drupal module administration page for your site
7. Enable the module
8. Configure the module (see below)

## INSTALLATION WITHOUT COMPOSER

1. Make sure you have a working SimpleSAMLphp installation. It needs to be a 
standalone installation, which has a "vendor" folder in the root of the project.
2. Download the simplesamlphp_auth module
3. Uncompress it
4. Move it to the appropriate modules directory (usually, /modules)
5. In your settings.php file, add the location of your SimpleSAMLphp 
installation (no trailing slashes):

   e.g.:
   $settings['simplesamlphp_dir'] = '/var/www/simplesamlphp';

6. Go to the Drupal module administration page for your site
7. Enable the module
8. Configure the module (see below)

# UPGRADING

The Drupal 8 version of this module provides a tested upgrade path from Drupal 
6.x-2.x, Drupal 7.x-2.x and Drupal 7.x-3.x branches, through the Migrate API in
Drupal 8. Other branches might have a working upgrade path, but are untested.

In order to upgrade the SimpleSAMLphp settings from your Drupal 6 or Drupal 7 
website, follow these instructions:
  - Install and enable the simplesamlphp_auth module as described above.
  - Activate the Migrate and Migrate Drupal core modules and perform your
  upgrade migration. 
  See https://www.drupal.org/upgrade/migrate for more information about this
  process.
  - Some settings did not exist in earlier versions of this module. They will
  remain in the default state after migration.
  - The setting "Activate authentication via SimpleSAMLphp" will always be
  migrated as deactivated, to avoid being locked out of your website after
  migration. Please check the migrated configuration thoroughly, and after
  validation of the settings, activate authentication via SimpleSAMLphp 
  manually.

# CONFIGURATION

## Basic configuration

The configuration of the module is fairly straight forward. You will need to
know the names of the attributes that your SP will be making available to the
module in order to map them into Drupal.

An additional step is required to allow access to SimpleSAMLphp paths within the
.htaccess for the Drupal 8 version of this module. Add in the lines below at the
appropriate place within the Drupal 8 .htaccess or the configuration will 
cause permission denied errors.

  # Copy and adapt this rule to directly execute PHP files in contributed or
  # custom modules or to run another PHP application in the same directory.
  RewriteCond %{REQUEST_URI} !/core/modules/statistics/statistics.php$
+ # Allow access to simplesaml paths
+ RewriteCond %{REQUEST_URI} !^/simplesaml
  # Deny access to any other PHP files that do not match the rules above.
  RewriteRule "^.+/.*\.php$" - [F]

## Linking SAML-authenticated users to Drupal users
  
  - If you don't have pre-existing Drupal users, you should make sure the
  checkbox "Register users" is enabled.
  When a user is correctly authenticated via the IdP, a Drupal user will 
  automatically be created linked to the SAML authname. Upon following 
  successful SAML authentications, the created Drupal user will be loaded and
  logged in. 
  
  - If you have pre-existing Drupal users, you can link them with SAML accounts
  upon successful SAML authentication.
  You can do so by enabling the option "Automatically enable SAML authentication
  for existing users upon successful login". If a user successfully 
  authenticates via SAML, the provided SAML authname is checked against 
  available Drupal usernames. If a match is found, the pre-existing Drupal user
  is linked to the authenticated SAML identity. You can also match existing
  users on different Drupal fields and SAML attributes. See 
  hook_simplesamlphp_auth_existing_user in simplesamlphp_auth.api.php for
  details.
  
  - Alternatively, you can link specific Drupal users to SAML accounts by
  checking the checkbox "Enable this user to leverage SAML authentication" upon
  user registration or user editing. In that case the Drupal username
  (by default) will be added to the authmap table. This allows a SAML 
  authenticated user with an authname identical to the one in the authmap table
  to be logged in as that Drupal user. If the stored authname to match on
  shouldn't be the Drupal username in your use case, you can implement 
  hook_simplesamphp_auth_account_authname_alter() - see 
  simplesamlphp_auth.api.php.
  
  - If you wish to limit which users can authenticate with Drupal, you can:
    - allow only access to SAML users within specific roles - see 
    hook_simplesamlphp_auth_allow_login in simplesamlphp_auth.api.php
    - disable the "Register users" option, enable the option "Automatically 
    enable SAML authentication for existing users upon successful login" and 
    register Drupal accounts for the users you wish to allow. Make sure their
    username matches the SAML authname attribute, or link them based on another
    field (see paragraphs above).

# TROUBLESHOOTING

* Installation fails:
  - The most common reason for things not working is the SP session storage type
   is still set to phpsession.
  - If that's set up correctly, make sure your Drupal installation can connect
  to a working SimpleSAMLphp instance. See "INSTALLATION" above.
  
* Usernames are not correctly synchronized or result in errors:
  - Drupal usernames need to be unique. This is enforced in code and on database
  level. Based on your settings, it's possible that this is not enforced in the
  SimpleSAMLphp attributes that are received, or that a pre-existing Drupal
  user already exists with the same username. Here's how you can fix this 
  problem:
    - It is recommended to set the SimpleSAMLphp Auth setting "SimpleSAMLphp 
    attribute to be used as username for the user" to a SAML attribute that is
    unique, preferably the same as "SimpleSAMLphp attribute to be used as unique 
    identifier for the user". This might make the Drupal username less 
    human-readable, and might not be how you want to visually represent the user
    on your website. You can fix this by altering how the account's name is 
    displayed by using hook_user_format_name_alter().
    - In case of pre-existing users with the same username, you should verify if
    the pre-existing Drupal user and SAML-authenticated users are one and the
    same person. If so, make sure you have enabled the setting "Automatically 
    enable SAML authentication for existing users upon successful login". This
    will make sure the existing Drupal user will be linked to the 
    SAML-autenticated user, and not result in errors. Though you should make
    sure that this action is valid, and won't result in SAML-authenticated users
    taking over accounts of other pre-existing Drupal users.
