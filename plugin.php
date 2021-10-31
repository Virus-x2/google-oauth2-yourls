<?php
/*
Plugin Name: Google OAuth2
Plugin URI: https://github.com/Virus-x2/google-oauth2-yourls
Description: This plugin adds authentation against Google
Version: 1.0
Author: Virus_x2
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) {
    die();
}

/* Assumes that you have already downloaded and installed the
 * Google APIs Client Library for PHP and it's in the same directory.
 * See https://github.com/google/google-api-php-client for install instructions.
 * Include your composer dependencies:
 */
require_once __DIR__ . '/vendor/autoload.php';

yourls_add_filter('logout', 'goauth_logout');
yourls_add_filter('is_valid_user', 'goauth_init');
yourls_add_filter('html_footer', 'goauth_add_button');
yourls_add_filter('is_valid_user', 'goauth_validate_user');
yourls_add_filter('logout_link', 'goauth_logout_link');

function goauth_logout() {
    if (version_compare(phpversion(), '5.4.0', '<')) {
        if (session_id() == '') {
            session_start();
        }
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    $google_redirect_url = yourls_admin_url();
    
    $gClient = new Google_Client();
    $gClient->setAuthConfigFile(dirname(__FILE__) . '/client_secrets.json');
    $gClient->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
    $gClient->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
    $gClient->setAccessType('offline');
    $gClient->setRedirectUri($google_redirect_url);
    
    $google_oauthV2 = new Google_Service_Oauth2($gClient);
    
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') 
    {
        unset($_SESSION['authloout']);
        $gClient->revokeToken($_SESSION['token']);
        unset($_SESSION['token']);
        header('Location: ' . filter_var($google_redirect_url, FILTER_SANITIZE_URL)); //redirect user back to page
    }
}

function goauth_init() {
    $google_redirect_url = yourls_admin_url();

    if (version_compare(phpversion(), '5.4.0', '<')) {
        if (session_id() == '') {
            session_start();
        }
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    $gClient = new Google_Client();
    $gClient->setAuthConfigFile(dirname(__FILE__) . '/client_secrets.json');
    $gClient->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
    $gClient->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
    $gClient->setAccessType('offline');
    $gClient->setRedirectUri($google_redirect_url);
    
    $google_oauthV2 = new Google_Service_Oauth2($gClient);
    
    if (isset($_GET['code'])) 
    {
        $gClient->authenticate($_GET['code']);
        $_SESSION['token'] = $gClient->getAccessToken();
        header('Location: ' . filter_var($google_redirect_url, FILTER_SANITIZE_URL));
        return;
    }
    
    if (isset($_SESSION['token'])) 
    {
        $gClient->setAccessToken($_SESSION['token']);
    }
        
    if ($gClient->getAccessToken()) // Sign in
    {
        //For logged in user, get details from google using access token
        try {
            $user = $google_oauthV2->userinfo->get();
            $user_id              = $user['id'];
            $user_name            = filter_var($user['givenName'], FILTER_SANITIZE_SPECIAL_CHARS);
            $email                = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
            $profile_url          = filter_var($user['link'], FILTER_VALIDATE_URL);
            $profile_image_url    = filter_var($user['picture'], FILTER_VALIDATE_URL);
            $personMarkup         = "$email<div><img src='$profile_image_url?sz=50'></div>";
            $_SESSION['token']    = $gClient->getAccessToken();
    
            // Uncomment the line below to set only approved domains to be able to login
            // $APPROVED_DOMAINS = array("gmail.com", "googlemail.com");
            $APPROVED_DOMAINS = "ALL";
            $user_domain = substr(strrchr($email, "@"), 1);
            if (!($APPROVED_DOMAINS === 'ALL' || in_array($user_domain, $APPROVED_DOMAINS))) {
              $_SESSION['authlogin_error'] = "This Domain is not approved";
              throw new Exception("This Domain is not approved");
            }
            /*
            Enable support for Auth Manager Plus [https://github.com/joshp23/YOURLS-AuthMgrPlus] uncomment next 2 lines
            if needed add auto-role-assignment 'administrator', 'editor', 'contributor'
            */
            //global $amp_role_assignment;
            //$amp_role_assignment['contributor'][]=$email;
            yourls_set_user($email);
            global $yourls_user_passwords;
            if (!isset($yourls_user_passwords[$email])) $yourls_user_passwords[$email] = 'phpass:' . sha1($email);
            unset($_SESSION['authlogin']);
            $_SESSION['authlogout'] =
                '<a href="'.$profile_url.'" target="_blank"><img src="'.$profile_image_url.'?sz=13" /></a> <b>' .
                $user_name . '</b> (<a class="logout" href="?action=logout">Logout</a>)';
        } catch (Exception $e) {
            // The user revoke the permission for this App! Therefore reset session token	
            unset($_SESSION['token']);
            header('Location: ' . filter_var($google_redirect_url, FILTER_SANITIZE_URL));
        }
    }
    else // Sign up
    {
        //For Guest user, get google login url
        $gClient->setApprovalPrompt("force");
        $authUrl = $gClient->createAuthUrl();
        
        unset($_SESSION['authlogout']);
        $_SESSION['authlogin'] = '<p align=center><a class="login" href="'.$authUrl.'"><img src="https://developers.google.com/identity/images/btn_google_signin_light_normal_web.png" /></a></p>';
    }
}

function goauth_validate_user($is_validated) {
    return (defined( 'YOURLS_USER' ) ? true : $is_validated);
}

function goauth_add_button() {
    if (isset($_SESSION['authlogin_error'])) {
        echo '<p class="error">' . $_SESSION['authlogin_error'] . '</p>';
    }
  echo '<script> var loginform = $("#login"); if(loginform)loginform.append("' . str_replace("\"","\\\"",$_SESSION['authlogin']) . '");</script>';
}

function goauth_logout_link($current_link) {
    if (isset($_SESSION['authlogout'])) {
	return $_SESSION['authlogout'];
    } else {
	return $current_link;
    }
}

