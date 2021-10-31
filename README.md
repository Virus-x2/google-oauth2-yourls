# google-oauth2-yourls
This plugin adds "via Google" button for Google OAuth v2 for YOURLS


Installation
------------
Download the plugin, move contents to plugins directory and rename to google-oauth2/
Download and configure Google APIs Client Library for PHP
See https://github.com/google/google-api-php-client for install instructions.
The code assumes that the API directory (vendor/) will reside inside the google-oauth2/ plugin directory
Create an OAuth 2.0 client ID, and download the resulting JSON file (client_secrets.json)
See https://developers.google.com/api-client-library/php/auth/web-app
place the client secrets file inside the google-oauth2/ plugin directory
This plugin allows you to filter access based on domain (i.e. only @mydomain.com addresses)
Edit $APPROVED_DOMAIN = array("mydomain.com", "myaltdomain.org");
If you want anyone with any Google account to get in, set to $APPROVED_DOMAIN = "ALL".

Additionally if you want to enable auto adding to specific role in Auth Manager Plus [https://github.com/joshp23/YOURLS-AuthMgrPlus] you should uncomment 2 lines:
  //global $amp_role_assignment;
  //$amp_role_assignment['contributor'][]=$email;

Features
--------
- Choose any google logged in account
- Support for Auth Manager Plus [https://github.com/joshp23/YOURLS-AuthMgrPlus] (Just use account email as username in AMP config)
- Logout


Any fedeback is valuable.
