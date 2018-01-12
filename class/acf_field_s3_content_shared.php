<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2018-01-12
 * Time: 11:29
 */

class acf_field_s3_content_shared
{

    /**
     * @var string
     */
    private $bucket;

    /**
     * acf_field_s3_content_shared constructor.
     * @param string $bucket
     */
    function __construct($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * @param array $field
     */
    public function renderField($field)
    {
        // defaults?
        /*
        $field = array_merge($this->defaults, $field);
        */

        // perhaps use $field['preview_size'] to alter the markup?


        // create Field HTML

        $values = is_array($field['value']) ? $field['value'] : [];

        $files = array_map(function($name) {
            return ['name' => $name, 'uploaded' => true];
        }, $values);

        // reset array keys
        $files = array_values($files);

        ?>

        <div class="clearfix">
            <div style="float: left;">
                Base key: <span class="acf-s3-base-key"></span>
            </div>
            <div style="float:right;">
                <button class="button acf-s3-relink">Relink</button>
            </div>
        </div>

        <br />

        <div class="acf-s3-files"
             data-post-id="<?php echo get_the_ID();?>"
             data-files="<?php echo htmlspecialchars(json_encode($files), ENT_QUOTES); ?>">

        </div>

        <br />

        <input type="file" id="acf-s3-file-select" />

        <?php
    }

    public function renderAdminHead()
    {
        // Note: This function can be removed if not used
        ?>

        <script type="text/template" id="acf-s3-file-template">

            <% for (var i = 0; i < files.length; i++) { %>

            <div class="acf-s3-file" data-id="<%= i %>">

                <div class="progress<% if (files[i].uploaded) { %> done<% } %>"></div>

                <div class="content">

                    <div class="name">

                        <% if (files[i].uploaded) { %>

                        <a href="https://<?php echo $this->bucket; ?>.s3.amazonaws.com/<%= encodeURI(files[i].name) %>"
                           target="_blank">
                            <%= files[i].name %>
                        </a>

                        <% } else { %>

                        <%= files[i].name %>

                        <% } %>

                    </div>

                    <div class="actions">
                        <% if ( !files[i].uploaded ) { %>
                        <a class="acf-s3-upload" style="float: right;">Upload</a>
                        <% } %>

                        <a class="acf-s3-delete" style="float: right;">Delete</a>
                    </div>

                    <div class="clear"></div>

                </div>
            </div>

            <% } %>
        </script>

        <?php
    }

    public function enqueueAssets()
    {
        $baseUrl = plugin_dir_url(__DIR__);
        wp_register_script( 'promise-queue', $baseUrl . 'vendor/helmutschneider/s3-js-upload/src/js/PromiseQueue.js', array('jquery'), $this->settings['version'] );
        wp_register_script( 's3-proxy', $baseUrl . 'vendor/helmutschneider/s3-js-upload/src/js/S3Proxy.js', array('jquery'), $this->settings['version'] );
        wp_register_script( 's3-file-uploader', $baseUrl . 'vendor/helmutschneider/s3-js-upload/src/js/S3FileUploader.js', array('jquery', 's3-proxy', 'promise-queue'), $this->settings['version'] );
        wp_register_script( 'acf-s3_content', $baseUrl . 'js/input.js', array('acf-input', 's3-file-uploader', 'underscore'), $this->settings['version'], true );

        wp_register_style( 'acf-s3_content', $baseUrl . 'css/input.css' );

        // scripts
        wp_enqueue_script(array(
            'acf-s3_content',
        ));

        // styles
        wp_enqueue_style(array(
            'acf-s3_content',
        ));
    }

}
