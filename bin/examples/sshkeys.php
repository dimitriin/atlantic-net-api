<?php
/**
 * List of added ssh keys
 */
use Dimitriin\AtlanticNet\API\Client;

require_once(__DIR__ . "/../../vendor/autoload.php");
/**
 * @var Client $client
 */
$client = require("client.php");
print_r($client->sshKeyList());
print_r("\n");
