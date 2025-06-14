<?php
/*
Plugin Name: Stormglass Astronomy Monthly
Description: Display sunrise, sunset, moonrise, moonset, and moon phase (with graphics) for a coordinate using Stormglass API, with 30-day caching, admin settings, and widget support.
Version: 1.5
Author: Marcus Hazel-McGown - MM0ZIF
Author URI: https://mm0zif.radio
Plugin URI: https://github.com/mm0zif-wx
Text Domain: stormglass-astronomy
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Load text domain for translations
add_action('plugins_loaded', function() {
    load_plugin_textdomain('stormglass-astronomy', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
$options = get_option('stormglass_settings');
error_log('Options: ' . print_r($options, true));
// 1. Admin Settings
add_action('admin_menu', function() {
    add_options_page(
        __('Stormglass Astronomy Settings', 'stormglass-astronomy'),
        __('Stormglass Astronomy', 'stormglass-astronomy'),
        'manage_options',
        'stormglass-astronomy',
        'stormglass_astronomy_settings_page'
    );
});

add_action('admin_init', function() {
    register_setting('stormglass-astronomy', 'stormglass_api_key', [
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('stormglass-astronomy', 'stormglass_lat', [
        'sanitize_callback' => 'floatval',
    ]);
    register_setting('stormglass-astronomy', 'stormglass_lon', [
        'sanitize_callback' => 'floatval',
    ]);
});

function stormglass_astronomy_settings_page() {
    // Handle cache clearing
    if (isset($_POST['clear_cache']) && check_admin_referer('stormglass_clear_cache')) {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_stormglass_astronomy_%'");
        echo '<div class="notice notice-success"><p>' . __('Cache cleared successfully.', 'stormglass-astronomy') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php _e('Stormglass Astronomy Settings', 'stormglass-astronomy'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('stormglass-astronomy');
            do_settings_sections('stormglass-astronomy');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="stormglass_api_key"><?php _e('Stormglass API Key', 'stormglass-astronomy'); ?></label></th>
                    <td><input type="text" id="stormglass_api_key" name="stormglass_api_key" value="<?php echo esc_attr(get_option('stormglass_api_key')); ?>" size="60" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stormglass_lat"><?php _e('Latitude', 'stormglass-astronomy'); ?></label></th>
                    <td><input type="number" step="any" id="stormglass_lat" name="stormglass_lat" value="<?php echo esc_attr(get_option('stormglass_lat')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stormglass_lon"><?php _e('Longitude', 'stormglass-astronomy'); ?></label></th>
                    <td><input type="number" step="any" id="stormglass_lon" name="stormglass_lon" value="<?php echo esc_attr(get_option('stormglass_lon')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <form method="post">
            <?php wp_nonce_field('stormglass_clear_cache'); ?>
            <input type="submit" name="clear_cache" class="button button-secondary" value="<?php _e('Clear Cache', 'stormglass-astronomy'); ?>" />
        </form>
    </div>
    <?php
}

// 2. Enqueue Styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'stormglass-astronomy',
        plugins_url('css/style.css', __FILE__),
        [],
        '1.5'
    );
});

// 3. Moon Phase Icon Mapping
function stormglass_get_moon_phase_icon($phase) {
    // Normalize phase to lowercase and remove spaces for matching
    $phase = strtolower(str_replace(' ', '', $phase));
    $icons = [
        'newmoon' => '🌑',
        'waxingcrescent' => '🌒',
        'firstquarter' => '🌓',
        'waxinggibbous' => '🌔',
        'fullmoon' => '🌕',
        'waninggibbous' => '🌖',
        'lastquarter' => '🌗',
        'waningcrescent' => '🌘',
    ];
    // Debug: Log the phase to check if it matches
    error_log('Stormglass Moon Phase: ' . $phase);
    return isset($icons[$phase]) ? $icons[$phase] : '';
}

// 4. Shortcode
add_shortcode('stormglass_astronomy', function($user_atts) { // MODIFIED: $atts to $user_atts
    // Default values for attributes (re-applying from subtask 8)
    $default_values = [
        'start' => '',  // Optional. Specific start date (YYYY-MM-DD). Overrides 'period'.
        'end' => '',    // Optional. Specific end date (YYYY-MM-DD). Overrides 'period'.
        'fields' => 'all', // Optional. Comma-separated fields (e.g., date,sunrise,sunset).
        'period' => '', // Optional. Set to '30days' for a rolling 30-day window from the current date. Ignored if 'start' or 'end' are set.
    ];
    // Merge user-supplied attributes (from $user_atts) with defaults (re-applying from subtask 8)
    $atts = shortcode_atts($default_values, $user_atts, 'stormglass_astronomy');

    $api_key = get_option('stormglass_api_key');
    $lat = get_option('stormglass_lat');
    $lon = get_option('stormglass_lon');

    if (empty($api_key) || !is_numeric($lat) || !is_numeric($lon)) {
        return '<div class="stormglass-error">' . __('Stormglass API key, latitude, or longitude not set or invalid.', 'stormglass-astronomy') . '</div>';
    }
    if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        return '<div class="stormglass-error">' . __('Invalid latitude or longitude values.', 'stormglass-astronomy') . '</div>';
    }

    // New date logic from subtask 8 (re-applying)
    $start_date_for_api_str;
    $end_date_for_api_str;

    if (!empty($user_atts['start']) && !empty($user_atts['end'])) {
        $start_date_for_api_str = $user_atts['start'];
        $end_date_for_api_str = $user_atts['end'];
    }
    else if (!empty($user_atts['start'])) {
        $start_date_for_api_str = $user_atts['start'];
        $end_date_for_api_str = date('Y-m-t', strtotime($start_date_for_api_str));
    }
    else if (!empty($user_atts['end'])) {
        $end_date_for_api_str = $user_atts['end'];
        $start_date_for_api_str = date('Y-m-01', strtotime($end_date_for_api_str));
    }
    else if (isset($atts['period']) && $atts['period'] === '30days') {
        $current_date = new DateTime();
        $start_date_for_api_str = $current_date->format('Y-m-d');
        $end_date_for_api_str = (clone $current_date)->modify('+29 days')->format('Y-m-d');
    }
    else {
        $start_date_for_api_str = date('Y-m-01');
        $end_date_for_api_str = date('Y-m-t');
    }

    $start = date('Y-m-d', strtotime($start_date_for_api_str));
    $end = date('Y-m-d', strtotime($end_date_for_api_str));

    // Debug logging from subtask 8 (re-applying)
    error_log('[Stormglass Plugin] Raw User Atts: ' . print_r($user_atts, true));
    error_log('[Stormglass Plugin] Processed Atts (merged with defaults): ' . print_r($atts, true));
    error_log('[Stormglass Plugin] Final API Dates - Start: ' . $start . ', End: ' . $end . ', Period from $atts: ' . $atts['period']);
    if (strtotime($end) < strtotime($start)) {
        return '<div class="stormglass-error">' . __('End date cannot be before start date.', 'stormglass-astronomy') . '</div>';
    }

    // AGGRESSIVE DIAGNOSTIC FOR DEFAULT SHORTCODE CASE (from current subtask)
    $astronomy_data = null;
    $astronomy_data_retrieved_from_cache = null;

    if (empty($user_atts['start']) && empty($user_atts['end']) && (empty($atts['period']) || $atts['period'] !== '30days')) {
        $current_php_month_start = date('Y-m-01');
        $current_php_month_end = date('Y-m-t');
        error_log('[Stormglass Plugin] AGGRESSIVE DEBUG: Default shortcode. PHP month start: ' . $current_php_month_start . ', end: ' . $current_php_month_end);

        $start = date('Y-m-d', strtotime($current_php_month_start)); // Override $start
        $end = date('Y-m-d', strtotime($current_php_month_end));     // Override $end

        error_log('[Stormglass Plugin] AGGRESSIVE DEBUG: Overridden $start for API: ' . $start . ', $end for API: ' . $end);

        $astronomy_data = false;
        $astronomy_data_retrieved_from_cache = false;
        error_log('[Stormglass Plugin] AGGRESSIVE DEBUG: Cache explicitly bypassed.');
    }
    // END OF AGGRESSIVE DIAGNOSTIC

    $cache_key = 'stormglass_astronomy_' . md5($lat . ',' . $lon . '_' . $start . '_' . $end . '_' . $atts['fields']); // Re-applying $atts['fields']
    // Debug: Log the cache key
    error_log('Stormglass: Cache key - ' . $cache_key);

    // New cache retrieval logic:
    if ($astronomy_data_retrieved_from_cache === null) {
        $astronomy_data = get_transient($cache_key);
        $astronomy_data_retrieved_from_cache = ($astronomy_data !== false);
    }
    error_log('[Stormglass Plugin] Cache status: ' . ($astronomy_data_retrieved_from_cache ? 'HIT' : 'MISS/BYPASSED') . ', Type: ' . gettype($astronomy_data));

    if (false === $astronomy_data) {
        // Debug: Log API request
        error_log('Stormglass: Fetching new API data for ' . $start . ' to ' . $end);
        $url = "https://api.stormglass.io/v2/astronomy/point?lat={$lat}&lng={$lon}&start={$start}&end={$end}";
        error_log('[Stormglass Plugin] Constructing API URL: ' . $url); // DIAGNOSTIC
        $response = wp_remote_get($url, [
            'headers' => ['Authorization' => $api_key],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return '<div class="stormglass-error">' . __('API request failed: ', 'stormglass-astronomy') . esc_html($response->get_error_message()) . '</div>';
        }

        $body = wp_remote_retrieve_body($response);
        error_log('[Stormglass Plugin] Raw API Response Body: ' . $body); // DIAGNOSTIC
        $json = json_decode($body, true);

        if (isset($json['errors'])) {
            return '<div class="stormglass-error">' . __('API error: ', 'stormglass-astronomy') . esc_html(json_encode($json['errors'])) . '</div>';
        }
        if (!isset($json['data']) || !is_array($json['data'])) {
            return '<div class="stormglass-error">' . __('Invalid API response.', 'stormglass-astronomy') . '</div>';
        }

        $astronomy_data = $json['data'];
        // Debug: Log number of days returned
        error_log('Stormglass: API returned ' . count($astronomy_data) . ' days');
        set_transient($cache_key, $astronomy_data, 60 * 60 * 24 * 30);
    }

    $fields = array_map('trim', explode(',', $atts['fields']));
    $valid_fields = ['date', 'sunrise', 'sunset', 'moonrise', 'moonset', 'moonphase'];
    if ($atts['fields'] !== 'all') {
        $fields = array_intersect($fields, $valid_fields);
        if (empty($fields)) {
            return '<div class="stormglass-error">' . __('No valid fields specified.', 'stormglass-astronomy') . '</div>';
        }
    } else {
        $fields = $valid_fields;
    }

    $output = '<table class="stormglass-astronomy-table"><thead><tr>';
    foreach ($fields as $field) {
        $output .= '<th>' . esc_html(ucfirst(str_replace('moonphase', 'moon phase', $field))) . '</th>';
    }
    $output .= '</tr></thead><tbody>';

    foreach ($astronomy_data as $row) {
        $output .= '<tr>';
        foreach ($fields as $field) {
            $value = '-';
            switch ($field) {
                case 'date':
                    $value = !empty($row['time']) ? esc_html(date('d/m/Y', strtotime($row['time']))) : '-';
                    break;
                case 'sunrise':
                    $value = !empty($row['sunrise']) ? esc_html(date('H:i', strtotime($row['sunrise']))) : '-';
                    break;
                case 'sunset':
                    $value = !empty($row['sunset']) ? esc_html(date('H:i', strtotime($row['sunset']))) : '-';
                    break;
                case 'moonrise':
                    $value = !empty($row['moonrise']) ? esc_html(date('H:i', strtotime($row['moonrise']))) : '-';
                    break;
                case 'moonset':
                    $value = !empty($row['moonset']) ? esc_html(date('H:i', strtotime($row['moonset']))) : '-';
                    break;
                case 'moonphase':
                    $phase_text = isset($row['moonPhase']['current']['text']) ? ucfirst($row['moonPhase']['current']['text']) : '-';
                    $phase_icon = stormglass_get_moon_phase_icon($row['moonPhase']['current']['text'] ?? '');
                    $value = $phase_icon ? wp_kses_post($phase_icon . ' ' . esc_html($phase_text)) : esc_html($phase_text);
                    break;
            }
            $output .= '<td class="' . ($field === 'moonphase' ? 'moon-phase' : '') . '">' . $value . '</td>';
        }
        $output .= '</tr>';
    }
    $output .= '</tbody></table>';
    return $output;
});

// 5. Widget
class Stormglass_Astronomy_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'stormglass_astronomy_widget',
            __('Stormglass Astronomy', 'stormglass-astronomy'),
            ['description' => __('Displays astronomy data from Stormglass API.', 'stormglass-astronomy')]
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        $shortcode = '[stormglass_astronomy';
        if (!empty($instance['start'])) {
            $shortcode .= ' start="' . esc_attr($instance['start']) . '"';
        }
        if (!empty($instance['end'])) {
            $shortcode .= ' end="' . esc_attr($instance['end']) . '"';
        }
        if (!empty($instance['fields'])) {
            $shortcode .= ' fields="' . esc_attr($instance['fields']) . '"';
        }
        $shortcode .= ']';
        echo do_shortcode($shortcode);
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $start = !empty($instance['start']) ? $instance['start'] : '';
        $end = !empty($instance['end']) ? $instance['end'] : '';
        $fields = !empty($instance['fields']) ? $instance['fields'] : 'all';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'stormglass-astronomy'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('start')); ?>"><?php _e('Start Date (YYYY-MM-DD):', 'stormglass-astronomy'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('start')); ?>" name="<?php echo esc_attr($this->get_field_name('start')); ?>" type="text" value="<?php echo esc_attr($start); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('end')); ?>"><?php _e('End Date (YYYY-MM-DD):', 'stormglass-astronomy'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('end')); ?>" name="<?php echo esc_attr($this->get_field_name('end')); ?>" type="text" value="<?php echo esc_attr($end); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('fields')); ?>"><?php _e('Fields (comma-separated, e.g., date,sunrise,sunset):', 'stormglass-astronomy'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('fields')); ?>" name="<?php echo esc_attr($this->get_field_name('fields')); ?>" type="text" value="<?php echo esc_attr($fields); ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['start'] = !empty($new_instance['start']) ? sanitize_text_field($new_instance['start']) : '';
        $instance['end'] = !empty($new_instance['end']) ? sanitize_text_field($new_instance['end']) : '';
        $instance['fields'] = !empty($new_instance['fields']) ? sanitize_text_field($new_instance['fields']) : 'all';
        return $instance;
    }
}

add_action('widgets_init', function() {
    register_widget('Stormglass_Astronomy_Widget');
});