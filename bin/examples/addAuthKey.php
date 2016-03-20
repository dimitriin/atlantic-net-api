<?php
/**
 * 1. Run instance
 * 2. Generate ssh key pair
 * 3. Add public key to server
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
$pair = $client->generateSshKeyPair();
print_r($pair);
print_r("\n");
$client->waitSshConnection($runData['ip_address']);
var_dump($client->addAuthorizedKey($runData['ip_address'], $runData['username'], $runData['password'], $pair['publicKey']));
print_r("\n");
$terminate = $client->terminateInstance($runData['instanceid']);
print_r($terminate);
print_r("\n");
$instance = $client->instance($runData['instanceid']);
var_dump($instance);