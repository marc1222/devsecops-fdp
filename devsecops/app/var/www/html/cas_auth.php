<?php

// Load the settings from the central config file
require_once 'config.php';
// Load the CAS lib
require_once $phpcas_path . '/CAS.php';

// Enable debugging
phpCAS::setDebug();
// Enable verbose error messages. Disable in production!
//phpCAS::setVerbose(true);

// Initialize phpCAS
#phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
phpCAS::client(SAML_VERSION_1_1, $cas_host, $cas_port, $cas_context);

// For production use set the CA certificate that is the issuer of the cert
// on the CAS server and uncomment the line below
// phpCAS::setCasServerCACert($cas_server_ca_cert_path);

// For quick testing you can disable SSL validation of the CAS server.
// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
phpCAS::setNoCasServerValidation();

// force CAS authentication
phpCAS::forceAuthentication();

// at this step, the user has been authenticated by the CAS server
// and the user's login name can be read with phpCAS::getUser().

// logout if desired
if (isset($_REQUEST['logout'])) {
	phpCAS::logout();
}

// for this test, simply print that the authentication was successfull
$user = phpCAS::getUser();

// Implement SSO login condition to allow only specific values of SSO attribute "unit"
$login = false;
do_SSO_login(phpCAS::getAttributes());

function do_SSO_login($attr) {
    global $login;
    if (is_array($attr) and array_key_exists('unit', $attr)) {
        $units = $attr['unit'];
        if (!is_array($units)) $units = Array($units);
        foreach ($units as $unit) {
            $lower_unit = strtolower($unit);
            if (strcmp($lower_unit, 'your_site') === 0 or strcmp($lower_unit, 'ithinkdomain') === 0 ) $login = true;
        }
    }
}

?>
