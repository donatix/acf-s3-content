<?php

/*
Plugin Name: ACF: S3 Content
Description: Adds a new field type that allows media to be uploaded to AWS S3
Version: 1.0.0
Author: Johan Björk
Author URI: mailto:johanimon@gmail.com
*/

require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;

// 1. set text domain
// Reference: https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
load_plugin_textdomain( 'acf-s3_content', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );

// 2. Include field type for ACF5
// $version = 5 and can be ignored until ACF6 exists

/*
function include_field_types_s3_content( $version ) {

	include_once('acf-s3_content-v5.php');

}

add_action('acf/include_field_types', 'include_field_types_s3_content');
*/

/**
 * @param string[] $config
 * @return S3Client
 */
function acf_s3_get_client($config) {
	return S3Client::factory([
		'key' => $config['key'],
		'secret' => $config['secret'],
		'region' => $config['region']
	]);
}

function acf_s3_get_config() {
	return require __DIR__ . '/config.php';
}

/**
 * @param string $fieldKey
 * @param mixed $postId
 * @return acf_s3_item[]
 */
function acf_s3_get_field($fieldKey, $postId = false) {
	$names = get_field($fieldKey, $postId, false);
	$conf = acf_s3_get_config();

	if ( !is_array($names) ) {
		$names = [];
	}

	return array_map(function($n) use ($conf) {
		return new acf_s3_item($conf['bucket'], $n);
	}, $names);
}

add_action('acf/register_fields', function() {
	$config = acf_s3_get_config();
	new acf_field_s3_content($config['bucket']);
});

add_action('wp_ajax_acf-s3_content_action', function() {
	$config = acf_s3_get_config();
	$client = acf_s3_get_client($config);
	$actions = require __DIR__ . '/actions.php';
	$action = isset($_GET['command']) ? $_GET['command'] : '';

	/* @var callable $callback */
	$callback = null;
	if ( isset($actions[$action]) ) {
		$callback = $actions[$action];
	}

	$result = $callback ? $callback($client, $config) : [ 'Message' => 'No action found' ];

	echo json_encode($result);

	die();
});

add_action('wp_ajax_acf-s3_update_field', function() {
	$key = $_POST['key'];
	$value = $_POST['value'];
	$postId = $_POST['post_id'];
	update_field($key, $value, $postId);
	die();
});

add_action('wp_ajax_acf-s3_relink', function() {
	$key = $_POST['key'];
	$postId = $_POST['post_id'];
	$path = $_POST['base_key'];
	var_dump([$key, $postId, $path]);
	die();

});

