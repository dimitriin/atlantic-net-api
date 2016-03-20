<?php
/**
 * Get list of all instances
 */
use Dimitriin\AtlanticNet\API\Client;

require_once(__DIR__ . "/../../vendor/autoload.php");
$client = require("client.php");
/**
 * @var Client $client
 */

print_r($client->instanceList());