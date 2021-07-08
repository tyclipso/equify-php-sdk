<?php
/*
 * API example configuration file. Replace with your own configuration.
 */
use equifySDK\Request;

require_once(__DIR__.'/../../../autoload.php');
Request::setDefaultAPIHost('https://staging.equify.de');
// The API path. Has to start and end with a slash
Request::setDefaultAPIPath('/v2/');
// Replace with your API key and secret
Request::setDefaultAPIKey('');
Request::setDefaultAPISecret('');
// HTTP basic auth - enable if you are testing with a private equify server that uses basic auth
// Request::setDefaultAuthData('username', 'password');
