<?php
/**
 * This controller processes a SAML response and deals with
 * user matching, creation and authentication
 * Upon successful authentication it redirects to the URL 
 * the user was trying to access.
 * Upon failure it redirects to the Maestrano access
 * unauthorized page
 *
 */

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));

error_reporting(E_ALL);

require MAESTRANO_ROOT . '/app/init/auth.php';

session_start();

// Get Maestrano Service
$maestrano = MaestranoService::getInstance();

// Options variable
if (!isset($opts)) {
  $opts = array();
}

// Check where we should redirect the user
// after successful login
if (isset($_SESSION['mno_previous_url'])) {
  $after_signin_url = $_SESSION['mno_previous_url'];
} else {
  $after_signin_url = "/";
}
$samlResponse = new OneLogin_Saml_Response($maestrano->getSettings()->getSamlSettings(), $_POST['SAMLResponse']);

try {
    if ($samlResponse->isValid()) {
        
        // Get Maestrano User
        $sso_user = new MnoSsoUser($samlResponse, $_SESSION, $opts);
        
        // Try to match the user with a local one
        $sso_user->matchLocal();
        
        // If user was not matched then attempt
        // to create a new local user
        if (is_null($sso_user->local_id)) {
          $sso_user->createLocalUserOrDenyAccess();
        }
        
        // If user is matched then sign it in
        // Refuse access otherwise
        if ($sso_user->local_id) {
          $sso_user->signIn();
          header("Location: " . $after_signin_url);
        } else {
          header("Location: " . $maestrano->getSsoUnauthorizedUrl());
        }
    }
    else {
        echo 'There was an error during the authentication process.<br/>';
        echo 'Please try again. If issue persists please contact support@maestrano.com';
    }
}
catch (Exception $e) {
    echo 'There was an error during the authentication process.<br/>';
    echo 'Please try again. If issue persists please contact support@maestrano.com';
    echo $e;
}
