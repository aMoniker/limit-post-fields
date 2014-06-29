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

            $valid_post_type_fields['builtin'][] = $builtin_field;
        }

        // add any custom meta boxes that have been added
        // TODO - is this worth it? custom meta boxes can change quite a bit...

        // add any applicable ACF fields
        // TODO - this is more difficult than I thought since there are so
        //        many rules that have to be taken into account for ACF field visibility

        return $valid_post_type_fields;
    }
}

include_once LIMITPOSTFIELDS_DIR . '/inc/settings-page.php';
