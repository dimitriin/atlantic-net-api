<?php
/**
 * Server workflow:
 * 1. Get imageId
 * 2. Get plan name
 * 3. Run instance with imageId, plan, location and wait till it will start
 * 4. Get instance description
 * 5. Stop and delete instance
 */
use Dimitriin\AtlanticNet\API\Client;

require_once(__DIR__ . "/../../vendor/autoload.php");
$client = require("client.php");
/**
 * @var Client $client
 */
$image = $client->imageByName('CentOS', '7.2', Client::ARCH_X86_64);
print_r($image);
print_r("\n");
$plan = $client->plan(Client::PLAN_S, Client::PLATFORM_LINUX);
print_r($plan);
print_r("\n");
$runData = $client->runInstanceSync('atlantic-test', $image['imageid'], $plan['plan_name'], Client::LOCATION_EUWEST1);
print_r($runData);
print_r("\n");
$instance = $client->instance($runData['instanceid']);
print_r($instance);
print_r("\n");
$terminate = $client->terminateInstance($runData['instanceid']);
print_r($terminate);
print_r("\n");
$instance = $client->instance($runData['instanceid']);
var_dump($instance);