<?php

/*
Plugin Name: ACF: S3 Content
Description: Adds a new field type that allows media to be uploaded to AWS S3
Version: 1.2.0
Author: Johan Björk
Author URI: mailto:johanimon@gmail.com
*/

require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use HelmutSchneider\AmazonS3\Proxy;

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
	return new S3Client([
		'credentials' => [
			'key' => $config['key'],
			'secret' => $config['secret'],
		],
		'region' => $config['region'],
		'version' => 'latest',
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

/**
 * Scans a location in S3 and updates the linked files in a post
 *
 * @param string $fieldKey acf field key
 * @param int $postId post id to link to
 * @param string $baseKey base key to scan in s3
 * @return string[] keys to the linked files
 */
function acf_s3_relink($fieldKey, $postId, $baseKey) {
	$config = acf_s3_get_config();
	$s3 = acf_s3_get_client($config);

	// make sure the key only ends with a slash if we're not at the root
	$baseKey = ltrim(trim($baseKey, '/') . '/', '/');
	$data = $s3->listObjects([
		'Bucket' => $config['bucket'],
		'Prefix' => $baseKey,
	])->toArray();

	$contents = isset($data['Contents']) ? $data['Contents'] : [];

	// if directories have been created manually on S3, empty "ghost files" will
	// appear with the same key as the base key. Remove them.
	$contents = array_filter($contents, function($it) use ($baseKey) {
		return $it['Key'] !== $baseKey;
	});

	// if elements have been removed by the filter there might be holes in the array.
	// this can cause json_encode to return an object instead of an array.
	$contents = array_values($contents);
	
	$items = array_map(function($it) { return $it['Key']; }, $contents);

	update_field($fieldKey, $items, $postId);

	return $items;
}

function getJsonBody() {
	$data = file_get_contents('php://input');
	return json_decode($data, true);
}

add_action('acf/register_fields', function() {
	$config = acf_s3_get_config();
	new acf_field_s3_content($config['bucket']);
});

add_action('wp_ajax_acf-s3_content_action', function() {
	$config = acf_s3_get_config();
	$client = acf_s3_get_client($config);
	$action = isset($_GET['command']) ? $_GET['command'] : '';
	$proxy = new Proxy($client, $config['bucket']);
	$body = getJsonBody();
	$out = [];
	switch ($action) {
		case 'createMultipartUpload':
			$out = $proxy->createMultipartUpload($body['Key'], $body['ContentType']);
			break;
		case 'abortMultipartUpload':
			$out = $proxy->abortMultipartUpload($body['Key'], $body['UploadId']);
			break;
		case 'completeMultipartUpload':
			$out = $proxy->completeMultipartUpload($body['Key'], $body['Parts'], $body['UploadId']);
			break;
		case 'listMultipartUploads':
			$out = $proxy->listMultipartUploads();
			break;
		case 'signUploadPart':
			$out = $proxy->signUploadPart($body['Key'], $body['PartNumber'], $body['UploadId']);
			break;
		case 'deleteObject':
			$out = $proxy->deleteObject($body['Key']);
			break;
		default:
			throw new Exception('No matching action found');
	}

	echo json_encode($out);

	die();
});

add_action('wp_ajax_acf-s3_update_field', function() {
	$body = getJsonBody();
	$key = $body['key'];
	$value = $body['value'];
	$postId = $body['post_id'];
	update_field($key, $value, $postId);
	die();
});

add_action('wp_ajax_acf-s3_relink', function() {
	$body = getJsonBody();
	$key = $body['key'];
	$postId = $body['post_id'];
	$path = $body['base_key'];

	$items = acf_s3_relink($key, $postId, $path);

	echo json_encode($items);

	die();
});

