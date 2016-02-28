<?php

/*
Plugin Name: ACF: S3 Content
Description: Adds a new field type that allows media to be uploaded to AWS S3
Version: 1.0.0
Author: Johan Björk
Author URI: mailto:johanimon@gmail.com
*/



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


// 3. Include field type for ACF4
function register_fields_s3_content() {
	include_once('acf-s3_content-v4.php');
}

add_action('acf/register_fields', 'register_fields_s3_content');

add_action('wp_ajax_acf-s3_content_action', function() {

	require __DIR__ . '/app.php';

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
