<?php
declare(strict_types=1);

/*
Plugin Name: ACF: S3 Content
Description: Adds a new field type that allows media to be uploaded to AWS S3
Version: 2.0.0
Author: Johan Björk
Author URI: mailto:johanimon@gmail.com
*/
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use HelmutSchneider\AcfS3\S3Proxy;
use HelmutSchneider\AcfS3\S3Item;
use HelmutSchneider\AcfS3\S3Field;

load_plugin_textdomain('acf-s3_content', false, dirname(plugin_basename(__FILE__)) . '/lang/');

const ACF_S3_OPTIONS = [
    'acf_s3_region' => 'S3 Region',
    'acf_s3_bucket' => 'S3 Bucket',
    'acf_s3_key' => 'S3 Access Key',
    'acf_s3_secret' => 'S3 Access Secret',
];

/**
 * @param string[] $config
 * @return S3Client
 */
function acf_s3_get_client(array $config)
{
    return new S3Client([
        'credentials' => [
            'key' => $config['acf_s3_key'],
            'secret' => $config['acf_s3_secret'],
        ],
        'region' => $config['acf_s3_region'],
        'version' => 'latest',
    ]);
}

/**
 * @return string[]
 */
function acf_s3_get_config(): array
{
    /* @var array|null $config */
    static $config = null;
    if ($config === null) {
        $config = [];
        foreach (ACF_S3_OPTIONS as $key => $name) {
            $config[$key] = get_option($key, '');
        }
    }
    return $config;
}

/**
 * @param string $fieldKey
 * @param int $postId
 * @return S3Item[]
 */
function acf_s3_get_field(string $fieldKey, int $postId)
{
    $names = get_field($fieldKey, $postId, false);
    $conf = acf_s3_get_config();

    if (!is_array($names)) {
        $names = [];
    }

    return array_map(function ($n) use ($conf) {
        return new S3Item($conf['acf_s3_bucket'], $n);
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
function acf_s3_relink(string $fieldKey, int $postId, string $baseKey): array
{
    $config = acf_s3_get_config();
    $s3 = acf_s3_get_client($config);

    // make sure the key only ends with a slash if we're not at the root
    $baseKey = ltrim(trim($baseKey, '/') . '/', '/');
    $data = $s3->listObjects([
        'Bucket' => $config['acf_s3_bucket'],
        'Prefix' => $baseKey,
    ])->toArray();

    $contents = isset($data['Contents']) ? $data['Contents'] : [];

    // if directories have been created manually on S3, empty "ghost files" will
    // appear with the same key as the base key. Remove them.
    $contents = array_filter($contents, function ($it) use ($baseKey) {
        return $it['Key'] !== $baseKey;
    });

    // if elements have been removed by the filter there might be holes in the array.
    // this can cause json_encode to return an object instead of an array.
    $contents = array_values($contents);

    $items = array_map(function ($it) {
        return $it['Key'];
    }, $contents);

    update_field($fieldKey, $items, $postId);

    return $items;
}

/**
 * @return mixed
 */
function getJsonBody()
{
    $data = file_get_contents('php://input');
    return json_decode($data, true);
}

// v5
add_action('acf/include_fields', function () {
    $config = acf_s3_get_config();
    new S3Field($config['acf_s3_bucket']);
});

add_action('wp_ajax_acf-s3_content_action', function () {
    $config = acf_s3_get_config();
    $client = acf_s3_get_client($config);
    $action = isset($_GET['command']) ? $_GET['command'] : '';
    $proxy = new S3Proxy($client, $config['acf_s3_bucket']);
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

add_action('wp_ajax_acf-s3_update_field', function () {
    $body = getJsonBody();
    $key = $body['key'];
    $value = $body['value'];
    $postId = $body['post_id'];
    update_field($key, $value, $postId);
    die();
});

add_action('wp_ajax_acf-s3_relink', function () {
    $body = getJsonBody();
    $key = $body['key'];
    $postId = $body['post_id'];
    $path = $body['base_key'];

    $items = acf_s3_relink($key, $postId, $path);

    echo json_encode($items);

    die();
});

/**
 * @param array $args
 * @return void
 */
function create_input(array $args): void
{
    $template = <<<EOD
<input class="regular-text"
       type="%s"
       id="%s"
       name="%s"
       value="%s" />
EOD;
    echo sprintf(
        $template,
        $args['type'] ?? 'text',
        $args['name'],
        $args['name'],
        $args['value']
    );
}

add_action('admin_init', function () {
    $group = 'acf_s3_content';
    $fields = ACF_S3_OPTIONS;

    // remove api token when deactivating plugin
    register_deactivation_hook(__FILE__, function () use ($fields) {
        foreach ($fields as $key => $name) {
            delete_option($key);
        }
    });

    add_settings_section($group, 'ACF S3 Content', '', 'general');

    foreach ($fields as $key => $name) {
        add_settings_field($key, $name, 'create_input', 'general', $group, [
            'name' => $key,
            'value' => get_option($key)
        ]);
        register_setting('general', $key);
    }
});

// this is a vastly simplified version of the native
// "playlist" shortcode. it only supports audio files.
add_shortcode('s3_playlist', function ($attr) {
    global $content_width;
    $post = get_post();

    static $instance = 0;
    $instance++;

    $config = acf_s3_get_config();
    $atts = shortcode_atts([
        'type' => 'audio',
        'order' => 'ASC',
        'orderby' => 'menu_order ID',
        'id' => $post ? $post->ID : 0,
        'include' => '',
        'exclude' => '',
        'style' => 'light',
        'tracklist' => true,
        'tracknumbers' => true,
        'images' => false,
        'artists' => false,
        // we default to the configured bucket.
        // the user can override it if they want.
        'bucket' => $config['acf_s3_bucket'],
        'key' => '',
    ], $attr, 'playlist');

    $outer = 22; // default padding and border of wrapper
    $default_width = 640;
    $theme_width = empty($content_width)
        ? $default_width
        : ($content_width - $outer);

    $data = [
        'type' => 'audio',
        'tracklist' => wp_validate_boolean($atts['tracklist']),
        'tracknumbers' => wp_validate_boolean($atts['tracknumbers']),
        'images' => wp_validate_boolean($atts['images']),
        'artists' => wp_validate_boolean($atts['artists']),
    ];

    $client = acf_s3_get_client($config);
    $proxy = new S3Proxy($client, $atts['bucket']);
    $result = $proxy->listObjects([
        'Prefix' => $atts['key'],
    ]);

    $contents = $result['Contents'];
    $tracks = [];
    foreach ($contents as $item) {
        $key = $item['Key'];

        // require the matched keys to have some kind
        // of file ending. this is mostly used to remove
        // directories from the result.
        if (preg_match('/\.(\w+)$/', $key) === 0) {
            continue;
        }

        $url = $proxy->getObjectUrl($item['Key']);
        $ftype = wp_check_filetype($url, wp_get_mime_types());
        $tracks[] = [
            'src' => $url,
            'type' => $ftype['type'],

            // the key is an absolute path from the root
            // so let's use basename to remove some
            // extraneous characters.
            'title' => basename($item['Key']),
            'meta' => [],
        ];
    }
    $data['tracks'] = $tracks;

    $safe_style = esc_attr($atts['style']);

    ob_start();
    if (1 === $instance) {
        do_action('wp_playlist_scripts', $atts['type'], $atts['style']);
    }
    ?>
    <div class="wp-playlist wp-audio-playlist wp-playlist-<?php echo $safe_style; ?>">
        <audio controls="controls" preload="none" width="<?= (int)$theme_width; ?>">
        </audio>
        <script type="application/json" class="wp-playlist-script">
            <?php echo wp_json_encode($data); ?>

        </script>
    </div>
    <?php
    return ob_get_clean();
});
