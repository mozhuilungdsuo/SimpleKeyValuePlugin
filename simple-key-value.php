
<?php

/**
 * Plugin Name: Simple Key-Value Plugin
 * Description: Stores key-value pairs and provides shortcodes for display.
 * Version: 1.0.2
 * Author: Lungdsuo Mozhui
 * Author URI: https://nerdynaga.com
 */

function kvp_get_default_keys()
{
    $csv_file = plugin_dir_path(__FILE__) . 'default_keys.csv';
    $keys = [];

    if (file_exists($csv_file) && ($handle = fopen($csv_file, 'r')) !== false) {
        while (($data = fgetcsv($handle, 1000, ",", '"', "\\")) !== false) {
            $key = trim($data[0]);
            if ($key !== '') {
                $keys[] = $key;
            }
        }
        fclose($handle);
    }

    return $keys;
}

function kvp_create_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'key_value_pairs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        key_name VARCHAR(255) NOT NULL UNIQUE,
        value LONGTEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    $existing_keys = $wpdb->get_col("SELECT key_name FROM $table_name");
    $default_keys = kvp_get_default_keys();

    foreach ($default_keys as $key) {
        if (!in_array($key, $existing_keys, true)) {
            $wpdb->insert($table_name, [
                'key_name' => $key,
                'value' => '',
            ]);
        }
    }
}
register_activation_hook(__FILE__, 'kvp_create_table');

function kvp_rest_api_init()
{
    register_rest_route('kvp/v1', '/update', [
        'methods' => 'POST',
        'callback' => 'kvp_update_value',
        'permission_callback' => 'kvp_api_permission_check',
    ]);
}
add_action('rest_api_init', 'kvp_rest_api_init');

function kvp_api_permission_check(WP_REST_Request $request)
{
    $api_key = $request->get_header('X-API-Key');
    $valid_api_key = get_option('kvp_api_key');

    return $api_key === $valid_api_key;
}

function kvp_update_value(WP_REST_Request $request)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'key_value_pairs';
    $key = sanitize_text_field($request->get_param('key'));
    $value = sanitize_text_field($request->get_param('value'));

    if (empty($key) || empty($value)) {
        return new WP_Error(
            'missing_parameters',
            'Both "key" and "value" parameters are required.',
            ['status' => 400]
        );
    }

    $result = $wpdb->replace(
        $table_name,
        [
            'key_name' => $key,
            'value'    => $value,
        ]
    );

    if ($result === false) {
        return new WP_Error(
            'db_error',
            'Database operation failed.',
            ['status' => 500]
        );
    }

    return rest_ensure_response([
        'message' => 'Value saved successfully.',
        'key'     => $key,
        'value'   => $value,
    ]);
}
function kvp_shortcode($atts)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'key_value_pairs';
    $a = shortcode_atts(['key' => ''], $atts);
    $key = sanitize_text_field($a['key']);

    $value = $wpdb->get_var(
        $wpdb->prepare("SELECT value FROM $table_name WHERE key_name = %s", $key)
    );

    return $value !== null ? esc_html($value) : '';
}
add_shortcode('kvp', 'kvp_shortcode');

function kvp_admin_menu()
{
    add_options_page('Key-Value Pairs', 'Key-Value Pairs', 'manage_options', 'kvp-settings', 'kvp_settings_page');
}
add_action('admin_menu', 'kvp_admin_menu');

function kvp_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'key_value_pairs';

    if (isset($_POST['kvp_update_keys']) && check_admin_referer('kvp_update_keys_action', 'kvp_update_keys_nonce')) {
        $values = $_POST['kvp_values'] ?? [];

        foreach ($values as $key => $new_value) {
            $wpdb->update(
                $table_name,
                ['value' => sanitize_text_field($new_value)],
                ['key_name' => sanitize_text_field($key)]
            );
        }

        echo '<div class="notice notice-success is-dismissible"><p>Values updated successfully.</p></div>';
    }

    $rows = $wpdb->get_results("SELECT key_name, value FROM $table_name ORDER BY key_name ASC");
    ?>
    <div class="wrap">
        <h2>API Key</h2>

        <form method="post" action="options.php">
            <?php
            settings_fields('kvp_api_key_group');
            do_settings_sections('kvp_api_key_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key:</th>
                    <td>
                        <input
                            type="text"
                            name="kvp_api_key"
                            value="<?php echo esc_attr(get_option('kvp_api_key')); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>

        <h2>Manual Key-Value Editor</h2>

        <form method="post">
            <?php wp_nonce_field('kvp_update_keys_action', 'kvp_update_keys_nonce'); ?>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Shortcode</th>
                        <th>Current Value</th>
                        <th>Update Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->key_name); ?></td>
                                <td><code>[kvp key="<?php echo esc_attr($row->key_name); ?>"]</code></td>
                                <td><?php echo esc_html($row->value); ?></td>
                                <td>
                                    <input
                                        type="text"
                                        name="kvp_values[<?php echo esc_attr($row->key_name); ?>]"
                                        value="<?php echo esc_attr($row->value); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No key-value pairs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <input type="submit" name="kvp_update_keys" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}

function kvp_register_settings()
{
    register_setting('kvp_api_key_group', 'kvp_api_key');
}
add_action('admin_init', 'kvp_register_settings');
