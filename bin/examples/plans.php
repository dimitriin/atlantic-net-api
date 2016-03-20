<?php
/**
 * Fetch all plans and single by name and platform
 */
use Dimitriin\AtlanticNet\API\Client;

require_once(__DIR__ . "/../../vendor/autoload.php");
/**
 * @var Client $client
 */
$client = require("client.php");
print_r($plans = $client->planList());
print_r("\n");
print_r($plans = $client->planList(Client::PLAN_L, Client::PLATFORM_LINUX));
print_r("\n");
$plan = reset($plans);
print_r($client->plan($plan['plan_name'], $plan['platform']));