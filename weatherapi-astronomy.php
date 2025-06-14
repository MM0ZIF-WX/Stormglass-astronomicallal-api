<?php
/*
Plugin Name: WeatherAPI Astronomy
Description: Displays monthly astronomical data (sunrise, sunset, moonrise, moonset, moon phase) using WeatherAPI.com.
Version: 1.0.0
Author: AI Assistant
Author URI: https://example.com
Plugin URI: https://example.com/weatherapi-astronomy
Text Domain: weatherapi-astronomy
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Plugin constants (or use options for settings)
// Example: define('WEATHERAPI_ASTRONOMY_DEFAULT_API_KEY', 'YOUR_API_KEY'); // Will be replaced by options
// Example: define('WEATHERAPI_ASTRONOMY_DEFAULT_LOCATION', 'USER_LOCATION'); // e.g., 'London' or 'latitude,longitude'

// Main plugin functions / class will begin here

/**
 * Add admin menu item for the plugin settings.
 */
function waa_admin_menu() {
    add_options_page(
        __('WeatherAPI Astronomy Settings', 'weatherapi-astronomy'), // Page title
        __('WeatherAPI Astronomy', 'weatherapi-astronomy'),    // Menu title
        'manage_options',                                       // Capability
        'weatherapi-astronomy-settings',                        // Menu slug
        'waa_render_settings_page'                              // Callback function to render the page
    );
}
add_action('admin_menu', 'waa_admin_menu');

/**
 * Register plugin settings.
 */
function waa_register_settings() {
    // Register settings group
    register_setting(
        'waa_settings_group',                                 // Option group
        'weatherapi_astronomy_api_key',                       // Option name
        ['sanitize_callback' => 'sanitize_text_field']        // Sanitize callback
    );
    register_setting(
        'waa_settings_group',
        'weatherapi_astronomy_latitude',
        ['sanitize_callback' => 'floatval']
    );
    register_setting(
        'waa_settings_group',
        'weatherapi_astronomy_longitude',
        ['sanitize_callback' => 'floatval']
    );

    // Add settings section
    add_settings_section(
        'waa_general_settings_section',                       // ID
        __('API & Location Settings', 'weatherapi-astronomy'), // Title
        null,                                                 // Callback (optional)
        'weatherapi-astronomy-settings'                       // Page slug
    );

    // Add settings fields
    add_settings_field(
        'weatherapi_astronomy_api_key',                       // ID
        __('WeatherAPI.com API Key', 'weatherapi-astronomy'), // Title
        'waa_render_api_key_field',                           // Callback to render HTML
        'weatherapi-astronomy-settings',                       // Page slug
        'waa_general_settings_section'                        // Section ID
    );
    add_settings_field(
        'weatherapi_astronomy_latitude',
        __('Latitude', 'weatherapi-astronomy'),
        'waa_render_latitude_field',
        'weatherapi-astronomy-settings',
        'waa_general_settings_section'
    );
    add_settings_field(
        'weatherapi_astronomy_longitude',
        __('Longitude', 'weatherapi-astronomy'),
        'waa_render_longitude_field',
        'weatherapi-astronomy-settings',
        'waa_general_settings_section'
    );
}
add_action('admin_init', 'waa_register_settings');

/**
 * Render the API Key input field.
 */
function waa_render_api_key_field() {
    $api_key = get_option('weatherapi_astronomy_api_key', '');
    echo "<input type='text' name='weatherapi_astronomy_api_key' value='" . esc_attr($api_key) . "' class='regular-text'>";
}

/**
 * Render the Latitude input field.
 */
function waa_render_latitude_field() {
    $latitude = get_option('weatherapi_astronomy_latitude', '');
    echo "<input type='number' step='any' name='weatherapi_astronomy_latitude' value='" . esc_attr($latitude) . "' class='regular-text'>";
}

/**
 * Render the Longitude input field.
 */
function waa_render_longitude_field() {
    $longitude = get_option('weatherapi_astronomy_longitude', '');
    echo "<input type='number' step='any' name='weatherapi_astronomy_longitude' value='" . esc_attr($longitude) . "' class='regular-text'>";
}

/**
 * Render the main settings page HTML.
 */
function waa_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('WeatherAPI Astronomy Settings', 'weatherapi-astronomy'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('waa_settings_group');
            do_settings_sections('weatherapi-astronomy-settings');
            submit_button();
            ?>
        </form>
        <hr>
        <h2><?php _e('Clear Cache', 'weatherapi-astronomy'); ?></h2>
        <form method="post" action="">
            <input type="hidden" name="waa_action" value="clear_cache">
            <?php wp_nonce_field('waa_clear_cache_nonce', 'waa_clear_cache_nonce_field'); ?>
            <?php submit_button(__('Clear Astronomy Cache', 'weatherapi-astronomy'), 'delete', 'waa_clear_cache_button', false, ['id' => 'waa_clear_cache_button']); ?>
        </form>
    </div>
    <?php
}

/**
 * Fetches daily astronomy data from WeatherAPI.com.
 *
 * @param string $date      Date in YYYY-MM-DD format.
 * @param string $api_key   WeatherAPI.com API key.
 * @param float|string $latitude Latitude.
 * @param float|string $longitude Longitude.
 * @return object|false The 'astro' object on success, false on failure.
 */
function waa_fetch_daily_astronomy_data($date, $api_key, $latitude, $longitude) {
    if (empty($api_key) || empty($latitude) || empty($longitude) || empty($date)) {
        error_log('WeatherAPI Astronomy: Missing required parameters for API call (date, api_key, lat, or lon).');
        return false;
    }

    $query_args = [
        'key' => $api_key,
        'q'   => trim((string)$latitude) . ',' . trim((string)$longitude), // Ensure lat/lon are strings for trim
        'dt'  => $date,
    ];
    $api_url = add_query_arg($query_args, 'http://api.weatherapi.com/v1/astronomy.json');

    error_log('WeatherAPI Astronomy: Fetching URL: ' . $api_url);

    $response = wp_remote_get($api_url, ['timeout' => 15]);

    if (is_wp_error($response)) {
        error_log('WeatherAPI Astronomy: API request failed. WP_Error: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        error_log('WeatherAPI Astronomy: API request error. Code: ' . $response_code . '. Body: ' . $response_body);
        return false;
    }

    $data = json_decode($response_body);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data->astronomy) || !isset($data->astronomy->astro)) {
        error_log('WeatherAPI Astronomy: Failed to decode JSON response or expected data structure missing. Response: ' . $response_body);
        return false;
    }

    return $data->astronomy->astro;
}

/**
 * Fetches and caches monthly astronomy data for a given year and month.
 *
 * @param int $year         Year (e.g., 2024).
 * @param int $month        Month (1-12).
 * @param string $api_key   WeatherAPI.com API key.
 * @param float|string $latitude Latitude.
 * @param float|string $longitude Longitude.
 * @return array An array of daily astronomy data, keyed by date string (YYYY-MM-DD).
 */
function waa_get_monthly_astronomy_data($year, $month, $api_key, $latitude, $longitude) {
    // Sanitize lat/lon for cache key
    $lat_key = str_replace(['.', ','], '_', (string)$latitude);
    $lon_key = str_replace(['.', ','], '_', (string)$longitude);
    $transient_key = 'waa_month_astro_' . $year . '_' . $month . '_' . $lat_key . '_' . $lon_key;

    $cached_data = get_transient($transient_key);
    if (false !== $cached_data && is_array($cached_data)) {
        error_log('WeatherAPI Astronomy: Monthly data loaded from cache for ' . $year . '-' . $month);
        return $cached_data;
    }

    error_log('WeatherAPI Astronomy: No valid cache found for ' . $year . '-' . $month . '. Fetching from API.');
    $monthly_data = [];
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    for ($day = 1; $day <= $days_in_month; $day++) {
        $current_date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $daily_astro_data = waa_fetch_daily_astronomy_data($current_date_str, $api_key, $latitude, $longitude);

        if ($daily_astro_data) {
            $monthly_data[$current_date_str] = $daily_astro_data;
        } else {
            $monthly_data[$current_date_str] = null;
            error_log('WeatherAPI Astronomy: Failed to fetch data for ' . $current_date_str);
        }
    }

    if (!empty($monthly_data)) {
        set_transient($transient_key, $monthly_data, 12 * HOUR_IN_SECONDS);
        error_log('WeatherAPI Astronomy: Monthly data fetched and cached for ' . $year . '-' . $month);
    } else {
        error_log('WeatherAPI Astronomy: No data fetched for ' . $year . '-' . $month . '. Not caching empty result.');
    }
    return $monthly_data;
}

/**
 * Handles the clear cache action from the admin settings page.
 */
function waa_handle_clear_cache_action() {
    if (isset($_POST['waa_action']) && $_POST['waa_action'] === 'clear_cache') {
        if (!isset($_POST['waa_clear_cache_nonce_field']) || !wp_verify_nonce($_POST['waa_clear_cache_nonce_field'], 'waa_clear_cache_nonce')) {
            wp_die(__('Nonce verification failed!', 'weatherapi-astronomy'));
        }
        global $wpdb;
        $prefix = '_transient_waa_month_astro_';
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, $prefix . '%'));

        $prefix_timeout = '_transient_timeout_waa_month_astro_';
        $sql_timeout = "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql_timeout, $prefix_timeout . '%'));

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('WeatherAPI Astronomy cache cleared.', 'weatherapi-astronomy') . '</p></div>';
        });
        error_log('WeatherAPI Astronomy: Cache cleared via admin button.');
    }
}
add_action('admin_init', 'waa_handle_clear_cache_action');

/**
 * Shortcode callback function to render the astronomy data table.
 *
 * @param array $atts Shortcode attributes (currently unused, for future expansion).
 * @return string HTML output for the astronomy table or error message.
 */
function waa_shortcode_render_astronomy_table($atts) {
    $moon_phase_icons = [
        'New Moon'         => '🌑',
        'Waxing Crescent'  => '🌒',
        'First Quarter'    => '🌓',
        'Waxing Gibbous'   => '🌔',
        'Full Moon'        => '🌕',
        'Waning Gibbous'   => '🌖',
        'Last Quarter'     => '🌗',
        'Waning Crescent'  => '🌘',
    ];

    // For now, ignore attributes, use current month.
    // $atts = shortcode_atts([], $atts, 'weatherapi_astronomy');

    $api_key = get_option('weatherapi_astronomy_api_key');
    $latitude = get_option('weatherapi_astronomy_latitude');
    $longitude = get_option('weatherapi_astronomy_longitude');

    if (empty($api_key) || empty($latitude) || empty($longitude)) {
        return '<p>' . __('WeatherAPI Astronomy: API key and location coordinates must be configured in settings.', 'weatherapi-astronomy') . '</p>';
    }

    $year = (int)date('Y');
    $month = (int)date('n');

    error_log("WeatherAPI Astronomy Shortcode: Targeting month={$month}, year={$year} for lat={$latitude}, lon={$longitude}");

    $monthly_data = waa_get_monthly_astronomy_data($year, $month, $api_key, $latitude, $longitude);

    if (empty($monthly_data)) {
        return '<p>' . __('WeatherAPI Astronomy: Could not retrieve astronomy data for the current month.', 'weatherapi-astronomy') . '</p>';
    }

    ob_start();
    ?>
    <div class="weatherapi-astronomy-wrapper">
    <table class="weatherapi-astronomy-table">
        <thead>
            <tr>
                <th><?php _e('Date', 'weatherapi-astronomy'); ?></th>
                <th><?php _e('Sunrise', 'weatherapi-astronomy'); ?></th>
                <th><?php _e('Sunset', 'weatherapi-astronomy'); ?></th>
                <th><?php _e('Moonrise', 'weatherapi-astronomy'); ?></th>
                <th><?php _e('Moonset', 'weatherapi-astronomy'); ?></th>
                <th><?php _e('Moon Phase', 'weatherapi-astronomy'); ?></th>
                <th><?php _e('Illumination', 'weatherapi-astronomy'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly_data as $date_key => $day_data) : ?>
                <tr>
                    <td><?php echo esc_html(date('d/m/Y', strtotime($date_key))); ?></td>
                    <?php if ($day_data && is_object($day_data)) : ?>
                        <td><?php echo esc_html($day_data->sunrise); ?></td>
                        <td><?php echo esc_html($day_data->sunset); ?></td>
                        <td><?php echo esc_html($day_data->moonrise); ?></td>
                        <td><?php echo esc_html($day_data->moonset); ?></td>
                        <td>
                            <?php
                            $phase_text = $day_data->moon_phase;
                            $icon = isset($moon_phase_icons[$phase_text]) ? $moon_phase_icons[$phase_text] . ' ' : '';
                            echo esc_html($icon . $phase_text);
                            ?>
                        </td>
                        <td><?php echo esc_html($day_data->moon_illumination); ?>%</td>
                    <?php else : ?>
                        <td colspan="6"><?php _e('Data unavailable for this day.', 'weatherapi-astronomy'); ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Registers the shortcode(s) for the plugin.
 */
function waa_register_shortcodes() {
    add_shortcode('weatherapi_astronomy', 'waa_shortcode_render_astronomy_table');
}
add_action('init', 'waa_register_shortcodes');

/**
 * Enqueue plugin stylesheet for the frontend.
 */
function waa_enqueue_styles() {
    wp_enqueue_style(
        'weatherapi-astronomy-style', // Handle
        plugin_dir_url(__FILE__) . 'css/style.css', // Source
        [], // Dependencies
        '1.0.0' // Version
    );
}
add_action('wp_enqueue_scripts', 'waa_enqueue_styles');

// WordPress Widget for WeatherAPI Astronomy
class WAA_Astronomy_Widget extends WP_Widget {

    // Constructor
    public function __construct() {
        parent::__construct(
            'waa_astronomy_widget', // Base ID
            __('WeatherAPI Astronomy', 'weatherapi-astronomy'), // Name
            ['description' => __('Displays monthly astronomy data from WeatherAPI.com.', 'weatherapi-astronomy')] // Args
        );
    }

    // Frontend display of widget
    public function widget($args, $instance) {
        echo $args['before_widget'];
        $title = apply_filters('widget_title', empty($instance['title']) ? __('Astronomy Data', 'weatherapi-astronomy') : $instance['title']);
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        if (function_exists('waa_shortcode_render_astronomy_table')) {
            echo waa_shortcode_render_astronomy_table([]);
        } else {
            echo __('Error: Astronomy display function not found.', 'weatherapi-astronomy');
        }

        echo $args['after_widget'];
    }

    // Backend widget form
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Astronomy Data', 'weatherapi-astronomy');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'weatherapi-astronomy'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    // Sanitize and save widget options
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

// Register the Widget
function waa_register_astronomy_widget() {
    register_widget('WAA_Astronomy_Widget');
}
add_action('widgets_init', 'waa_register_astronomy_widget');

?>
