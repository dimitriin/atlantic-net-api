<?php
/**
 * Get all available images and image description by image ID
 */
require_once(__DIR__ . "/../../vendor/autoload.php");
$client = require("client.php");
print_r($images = $client->imageList());
$image = reset($images);
print_r("\n");
print_r($client->image($image['imageid']));