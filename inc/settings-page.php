<?php

final class LimitPostFieldsSettings
{
    private $page;
    private $menu_slug = 'limit-post-fields';
    private $valid_fields = array();

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'settings_assets'));
        add_action('admin_menu', array($this, 'settings_menu'));
        add_action('admin_init', array($this, 'settings_fields'));
    }

    public function settings_assets($page) {
        if ($page !== $this->page) { return; }

        wp_enqueue_style(
            'limit-post-fields-admin-css',
            LIMITPOSTFIELDS_URL . '/css/admin.css',
            null,
            '0.0.1'
        );
    }

    public function settings_menu()
    {
        $this->page = add_options_page('Limit Post Fields Settings', 'Limit Post Fields', 'manage_options', $this->menu_slug, array($this, 'settings_page'));
    }

    public function settings_page()
    {
        ?><h2>Limit Post Fields</h2>
        <form method="POST" action="options.php"><?php
            settings_fields($this->menu_slug);
            do_settings_sections($this->menu_slug);
            submit_button(); ?>
        </form><?php
    }

    public function settings_fields()
    {
        add_settings_section(
            'limitpostfields_settings_main',
            '',
            array($this, 'setting_section_header'),
            $this->menu_slug
        );

        $post_types = LimitPostFields::get_valid_post_types();

        foreach ($post_types as $post_type) {
            $pto = get_post_type_object($post_type);
            if (!$pto) { continue; } // the pto has disbanded

            $settings_field_name = LimitPostFields::get_settings_name($post_type);

            add_settings_field(
                $settings_field_name,
                $pto->label,
                array($this, "handle_settings_field_$post_type"),
                $this->menu_slug,
                'limitpostfields_settings_main'
            );
            register_setting($this->menu_slug, $settings_field_name);
        }
    }

    public function setting_section_header()
    { ?>
        <p>Field limits are in number of characters. Zero indicates no limit.</p><?php
    }

    public function __call($name, $args)
    {
        if (strpos($name, 'handle_settings_field_') !== false) {
            $post_type = preg_replace('/^handle_settings_field_/', '', $name);
            $this->post_type_field_handler($post_type);
        }
    }

    private function post_type_field_handler($name)
    {
        $div_id = "limitpostfields_field_type_$name";
        $valid_fields = LimitPostFields::get_valid_post_type_fields($name);
        $settings_field_name = LimitPostFields::get_settings_name($name);

        $saved_settings = get_option($settings_field_name);

        if (!isset($this->valid_fields[$name])) {
            $this->valid_fields[$name] = array();
        }

        $this->valid_fields[$name] = array_merge_recursive(
            $this->valid_fields[$name],
            $valid_fields
        );

        ?><div id="<?php echo $div_id; ?>">
            <div class="field-limits"><?php
                foreach ($this->valid_fields[$name] as $fields_type => $fields):
                    foreach ($fields as $field):
                    $value = isset($saved_settings[$field])
                           ? $saved_settings[$field]
                           : ''
                           ;
                    ?><div class="field-limit">
                        <label>
                            <span class="title"><?php echo $field; ?></span>
                            <input type="number" class="limit-input" name="<?php echo $settings_field_name; ?>[<?php echo $field; ?>]" step="1" min="0" placeholder="0" value="<?php echo $value; ?>">
                        </label>
                    </div><?php
                    endforeach;
                endforeach; ?>
            </div>
        </div><?php
    }
}

new LimitPostFieldsSettings;
