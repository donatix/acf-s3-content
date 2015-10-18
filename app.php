<?php
/**
 * Created by PhpStorm.
 * User: Johan
 * Date: 2015-06-18
 * Time: 23:08
 */

require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;

$config = require __DIR__ . '/config.php';
$client = S3Client::factory([
    'key' => $config['key'],
    'secret' => $config['secret'],
    'region' => $config['region']
]);
$actions = require __DIR__ . '/actions.php';
$action = isset($_GET['command']) ? $_GET['command'] : '';

/* @var callable $callback */
$callback = null;
if ( isset($actions[$action]) ) {
    $callback = $actions[$action];
}

$result = $callback ? $callback($client, $config) : [ 'Message' => 'No action found' ];

echo json_encode($result);
