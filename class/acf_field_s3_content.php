<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 16-02-28
 * Time: 20:08
 */

class acf_field_s3_content extends acf_field {

    // vars
    var $settings, // will hold info such as dir / path
        $defaults; // will hold default field options


    /**
     * @var string
     */
    private $bucket;

    /*
    *  __construct
    *
    *  Set name / label needed for actions / filters
    *
    *  @since	3.6
    *  @date	23/01/13
    */

    /**
     * @param string $bucket
     */
    function __construct($bucket)
    {

        $this->bucket = $bucket;

        // vars
        $this->name = 's3_content';
        $this->label = __('S3 Content');
        $this->category = __("Basic",'acf'); // Basic, Content, Choice, etc
        $this->defaults = array(
            // add default here to merge into your field.
            // This makes life easy when creating the field options as you don't need to use any if( isset('') ) logic. eg:
            //'preview_size' => 'thumbnail'
        );


        // do not delete!
        parent::__construct();


        // settings
        $this->settings = array(
            'path' => apply_filters('acf/helpers/get_path', realpath(__DIR__ . '/../plugin.php')),
            'dir' => apply_filters('acf/helpers/get_dir', realpath(__DIR__ . '/../plugin.php')),
            'version' => '1.2.0',
        );
    }


    /*
    *  create_options()
    *
    *  Create extra options for your field. This is rendered when editing a field.
    *  The value of $field['name'] can be used (like below) to save extra data to the $field
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field	- an array holding all the field's data
    */

    function create_options( $field )
    {
        // defaults?
        /*
        $field = array_merge($this->defaults, $field);
        */

        // key is needed in the field names to correctly save the data
        $key = $field['name'];


        // Create Field Options HTML
        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e("Preview Size",'acf'); ?></label>
                <p class="description"><?php _e("Thumbnail is advised",'acf'); ?></p>
            </td>
            <td>

                <?php

                /*
                do_action('acf/create_field', array(
                    'type'		=>	'radio',
                    'name'		=>	'fields['.$key.'][preview_size]',
                    'value'		=> false,
                    //'value'		=>	$field['preview_size'],
                    'layout'	=>	'horizontal',
                    'choices'	=>	array(
                        'thumbnail' => __('Thumbnail'),
                        'something_else' => __('Something Else'),
                    )
                ));
                */

                ?>
            </td>
        </tr>
        <?php

    }


    /*
    *  create_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param	$field - an array holding all the field's data
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    */

    function create_field( $field )
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


    /*
    *  input_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
    *  Use this action to add CSS + JavaScript to assist your create_field() action.
    *
    *  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    */

    function input_admin_enqueue_scripts()
    {
        // Note: This function can be removed if not used

        // register ACF scripts
        wp_register_script( 'promise-queue', $this->settings['dir'] . 'vendor/helmutschneider/s3-js-upload/src/js/PromiseQueue.js', array('jquery'), $this->settings['version'] );
        wp_register_script( 's3-proxy', $this->settings['dir'] . 'vendor/helmutschneider/s3-js-upload/src/js/S3Proxy.js', array('jquery'), $this->settings['version'] );
        wp_register_script( 's3-file-uploader', $this->settings['dir'] . 'vendor/helmutschneider/s3-js-upload/src/js/S3FileUploader.js', array('jquery', 's3-proxy', 'promise-queue'), $this->settings['version'] );
        wp_register_script( 'acf-s3_content', $this->settings['dir'] . 'js/input.js', array('acf-input', 's3-file-uploader', 'underscore'), $this->settings['version'], true );

        wp_register_style( 'acf-s3_content', $this->settings['dir'] . 'css/input.css' );

        // scripts
        wp_enqueue_script(array(
            'acf-s3_content',
        ));

        // styles
        wp_enqueue_style(array(
            'acf-s3_content',
        ));


    }


    /*
    *  input_admin_head()
    *
    *  This action is called in the admin_head action on the edit screen where your field is created.
    *  Use this action to add CSS and JavaScript to assist your create_field() action.
    *
    *  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    */

    function input_admin_head()
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


    /*
    *  field_group_admin_enqueue_scripts()
    *
    *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
    *  Use this action to add CSS + JavaScript to assist your create_field_options() action.
    *
    *  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    */

    function field_group_admin_enqueue_scripts()
    {
        // Note: This function can be removed if not used
    }


    /*
    *  field_group_admin_head()
    *
    *  This action is called in the admin_head action on the edit screen where your field is edited.
    *  Use this action to add CSS and JavaScript to assist your create_field_options() action.
    *
    *  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    */

    function field_group_admin_head()
    {
        // Note: This function can be removed if not used
    }


    /*
    *  load_value()
    *
        *  This filter is applied to the $value after it is loaded from the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value - the value found in the database
    *  @param	$post_id - the $post_id from which the value was loaded
    *  @param	$field - the field array holding all the field options
    *
    *  @return	$value - the value to be saved in the database
    */

    function load_value( $value, $post_id, $field )
    {
        // Note: This function can be removed if not used
        return $value;
    }


    /*
    *  update_value()
    *
    *  This filter is applied to the $value before it is updated in the db
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value - the value which will be saved in the database
    *  @param	$post_id - the $post_id of which the value will be saved
    *  @param	$field - the field array holding all the field options
    *
    *  @return	$value - the modified value
    */

    function update_value( $value, $post_id, $field )
    {
        //var_dump($valye)
        // Note: This function can be removed if not used
        return $value;
    }


    /*
    *  format_value()
    *
    *  This filter is applied to the $value after it is loaded from the db and before it is passed to the create_field action
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value	- the value which was loaded from the database
    *  @param	$post_id - the $post_id from which the value was loaded
    *  @param	$field	- the field array holding all the field options
    *
    *  @return	$value	- the modified value
    */

    function format_value( $value, $post_id, $field )
    {
        // defaults?
        /*
        $field = array_merge($this->defaults, $field);
        */

        // perhaps use $field['preview_size'] to alter the $value?


        // Note: This function can be removed if not used
        return $value;
    }


    /*
    *  format_value_for_api()
    *
    *  This filter is applied to the $value after it is loaded from the db and before it is passed back to the API functions such as the_field
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$value	- the value which was loaded from the database
    *  @param	$post_id - the $post_id from which the value was loaded
    *  @param	$field	- the field array holding all the field options
    *
    *  @return	$value	- the modified value
    */

    function format_value_for_api( $value, $post_id, $field )
    {
        // defaults?
        /*
        $field = array_merge($this->defaults, $field);
        */

        // perhaps use $field['preview_size'] to alter the $value?


        // Note: This function can be removed if not used
        return $value;
    }


    /*
    *  load_field()
    *
    *  This filter is applied to the $field after it is loaded from the database
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field - the field array holding all the field options
    *
    *  @return	$field - the field array holding all the field options
    */

    function load_field( $field )
    {
        // Note: This function can be removed if not used
        return $field;
    }


    /*
    *  update_field()
    *
    *  This filter is applied to the $field before it is saved to the database
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field - the field array holding all the field options
    *  @param	$post_id - the field group ID (post_type = acf)
    *
    *  @return	$field - the modified field
    */

    function update_field( $field, $post_id )
    {
        // Note: This function can be removed if not used
        return $field;
    }


}
