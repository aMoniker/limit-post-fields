<?php
/*
Plugin Name: Limit Post Fields
Version: 0.0.1
Description: Limit the length of post fields, meta fields, and ACF fields.
Author: Jim Greenleaf
Author URI: http://jim.greenle.af
Plugin URI: TBD
Text Domain: limit-post-fields
Domain Path: /languages
License: AGPL3 http://www.gnu.org/licenses/agpl.html
*/

define('LIMITPOSTFIELDS_DIR', dirname(__FILE__));
define('LIMITPOSTFIELDS_URL', plugin_dir_url(__FILE__));

class LimitPostFields
{
    private $settings;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'edit_page_assets'));
    }

    public function edit_page_assets($hook)
    {
        if (!in_array($hook, array('post-new.php', 'post.php', 'edit.php'))) {
            return;
        }

        global $post;
        if (!$post || !in_array($post->post_type, self::get_valid_post_types())) {
            return;
        }

        $this->settings = get_option(self::get_settings_name($post->post_type));

        wp_enqueue_style(
            'limitpostfields-edit-page-jqueryui-progressbar-css',
            LIMITPOSTFIELDS_URL . '/css/jquery-ui-progressbar.min.css',
            null,
            '0.0.1'
        );

        wp_enqueue_style(
            'limitpostfields-edit-page-css',
            LIMITPOSTFIELDS_URL . '/css/edit-page.css',
            null,
            '0.0.1'
        );

        wp_enqueue_script(
            'limitpostfields-class-js',
            LIMITPOSTFIELDS_URL . '/js/class.js',
            null,
            '0.0.1'
        );

        wp_enqueue_script(
            'limitpostfields-edit-page-js',
            LIMITPOSTFIELDS_URL . '/js/edit-page.js',
            array('jquery', 'jquery-ui-progressbar', 'limitpostfields-class-js'),
            '0.0.1'
        );

        add_action('admin_footer', array($this, 'edit_page_footer_js'));
    }

    public function edit_page_footer_js()
    {
        ?><script type="text/javascript">
            window.limitpostfields = window.limitpostfields || {};
            window.limitpostfields.settings = <?php
                echo json_encode($this->settings);
            ?>;
        </script><?php
    }

    public static function get_rules()
    {
        return array();
    }

    public static function set_rules()
    {
        
    }

    public static function get_valid_post_types() {
        // you can completely override this function with the following filter
        // the function should return an array of post_type names
        // which field limiting should be available for
        $post_types = apply_filters('limitpostfields/validposttypes/override', array());
        if ($post_types) { return $post_types; }

        // find the defined post types
        $post_type_args = apply_filters('limitpostfields/validposttypes/args', array());
        $available_post_types = array_values(get_post_types($post_type_args));

        // define which post types are considered valid for this plugin
        $valid_post_types = apply_filters(
            'limitpostfields/validposttypes/validtypes',
            array('post', 'page')
        );

        // return an array of post types, minus the invalid types above
        $post_types = array_intersect($available_post_types, $valid_post_types);
        return apply_filters(
            'limitpostfields/validposttypes/returntypes',
            $post_types
        );
    }

    public static function get_valid_post_type_fields($post_type)
    {
        // you can completely override this function with the following filter
        // the function should return an array of post_field types
        // mapped to post_field names
        // var_dump() the normal output of this function to see what it looks like
        $valid_post_type_fields = apply_filters(
            'limitpostfields/validposttypefields/override',
            array(),
            $post_type
        );
        if ($valid_post_type_fields) { return $valid_post_type_fields; }

        if (!$post_type) { return $valid_post_type_fields; }

        $post_labels = array(
            'title' => 'Title',
            'editor' => 'Content',
            'excerpt' => 'Excerpt',
        );

        // add the builtin post fields (title, editor, excerpt)
        $builtin_fields = apply_filters(
            'limitpostfields/validposttypefields/builtin',
            array('title', 'editor', 'excerpt'),
            $post_type
        );
        foreach ($builtin_fields as $builtin_field) {
            if (!post_type_supports($post_type, $builtin_field)) { continue; }

            if (!isset($valid_post_type_fields['builtin'])) {
                $valid_post_type_fields['builtin'] = array();
            }

            $valid_post_type_fields['builtin'][$builtin_field] = $post_labels[$builtin_field];
        }

        // add any custom meta boxes that have been added
        // TODO - is this worth it? custom meta boxes can change quite a bit...

        // add any applicable ACF fields
        $acf_field_groups = apply_filters(
            'acf/location/match_field_groups',
            array(),
            array('post_type' => $post_type)
        );

        $valid_acf_fields = array();
        foreach ($acf_field_groups as $acf_id) {
            // $post = get_post($acf_id);
            $meta = get_metadata('post', $acf_id);

            foreach ($meta as $key => $value) {
                if (!preg_match('/^field_/', $key)) { continue; }
                if (!$value || !is_array($value))   { continue; }

                $value = maybe_unserialize($value[0]);

                if (!is_array($value)) { continue; }
                if (!isset($value['key']) || !isset($value['type'])) { continue; }

                if (in_array($value['type'], array(
                    'text', 'textarea', 'wysiwyg',
                ))) {
                    $valid_acf_fields[$value['key']] = $value['label'];
                }
            }
        }

        if ($valid_acf_fields) {
            $valid_post_type_fields['acf'] = $valid_acf_fields;
        }

        return $valid_post_type_fields;
    }

    public static function get_settings_name($post_type)
    {
        return "limitsettingsfields_posttype_$post_type";
    }
}

new LimitPostFields;

include_once LIMITPOSTFIELDS_DIR . '/inc/settings-page.php';
