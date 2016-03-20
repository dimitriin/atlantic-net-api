<?php
/**
 * For creation client you should specify api and api private keys
 */
define("AN_API_KEY", "");
define("AN_API_PRIVATE_KEY", "");
if( !defined("AN_API_KEY") || !AN_API_KEY || !defined("AN_API_PRIVATE_KEY") || !AN_API_PRIVATE_KEY ) {
    exit("Please, specify in " . __FILE__ . " api key and api private key\n");
}
return new Dimitriin\AtlanticNet\API\Client(AN_API_KEY, AN_API_PRIVATE_KEY);