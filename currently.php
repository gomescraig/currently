<?php
/*
Plugin Name: Currently
Description: The "Currently" plugin is designed to display the current work status based on configured work hours or vacation mode in WordPress.
Version: 1.0
Author: Craig Gomes
Author URI: https://craiggomes.com
License: GPL v2 or later
*/

// Register shortcode
add_shortcode('currently', 'work_status_shortcode');

// Function to display work status
function work_status_shortcode() {
    // Check if vacation mode is enabled
    $vacation_mode_enabled = get_option('work_status_vacation_mode', false);

    if ($vacation_mode_enabled) {
        // Display vacation mode text
        $vacation_text = get_option('work_status_vacation_text', '🏝️ On Vacation');
        return "<div class='work-status-container'>$vacation_text</div>";
    } else {
        // Check work status based on configured work hours
        $work_status = calculate_work_status();

        // Output appropriate message with customized text
        if ($work_status) {
            $working_text = get_option('work_status_working_text', '👨🏻‍💻 Working');
            return "<div class='work-status-container'>Currently <span style='color: green;'>$working_text</span></div>";
        } else {
            $away_text = get_option('work_status_away_text', '🏃🏻 Away');
            return "<div class='work-status-container'>Currently <span style='color: red;'>$away_text</span></div>";
        }
    }
}

// Add menu item in the admin dashboard
function work_status_menu() {
    add_menu_page('Currently', 'Currently', 'manage_options', 'currently', 'work_status_settings_page', 'dashicons-clock');
}
add_action('admin_menu', 'work_status_menu');

// Create settings page
function work_status_settings_page() {
    ?>
    <div class="wrap">
        <h2>Currently Settings</h2>
        <p>The "Currently" plugin is designed to display the current work status based on configured work hours or vacation mode in WordPress.</p>
        <p>Developed by Craig Gomes | <a href="https://craiggomes.com">Visit Blog</a> | <a href="https://pixelvise.com">Need a Website? Visit Pixelvise</a></p>
        <p> Insert shortcode <strong>[currently]</strong> wherever you want to display your status.</p>
        <form method="post" action="options.php">
            <?php
            settings_fields('work_status_settings');
            do_settings_sections('work_status_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and initialize settings
function work_status_settings_init() {
    // Register settings
    register_setting('work_status_settings', 'work_status_hours', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_work_hours',
    ));
    register_setting('work_status_settings', 'work_status_working_text', 'sanitize_text_field');
    register_setting('work_status_settings', 'work_status_away_text', 'sanitize_text_field');
    register_setting('work_status_settings', 'work_status_vacation_mode', 'sanitize_checkbox');
    register_setting('work_status_settings', 'work_status_vacation_text', 'sanitize_text_field');

    // Add section and fields
    add_settings_section('work_status_section', 'Work Hours', 'work_status_section_callback', 'work_status_settings');

    // Add fields for each day of the week
    $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    foreach ($days as $day) {
        add_settings_field('work_status_' . strtolower($day) . '_field', $day, 'work_status_day_field_callback', 'work_status_settings', 'work_status_section', array('day' => strtolower($day)));
    }

    // Add fields for working and away text
    add_settings_field('work_status_working_text_field', 'Working Text', 'work_status_working_text_field_callback', 'work_status_settings', 'work_status_section');
    add_settings_field('work_status_away_text_field', 'Away Text', 'work_status_away_text_field_callback', 'work_status_settings', 'work_status_section');

    // Add field for vacation mode
    add_settings_field('work_status_vacation_mode_field', 'Vacation Mode', 'work_status_vacation_mode_field_callback', 'work_status_settings', 'work_status_section');

    // Add field for vacation text
    add_settings_field('work_status_vacation_text_field', 'Vacation Text', 'work_status_vacation_text_field_callback', 'work_status_settings', 'work_status_section');
}
add_action('admin_init', 'work_status_settings_init');

// Section callback
function work_status_section_callback() {
    echo 'Set your work hours below:';
}

// Field callback for each day
function work_status_day_field_callback($args) {
    $day = $args['day'];
    $work_status_hours = get_option('work_status_hours');
    $day_hours = isset($work_status_hours[$day]) ? $work_status_hours[$day] : array('start_hour' => '', 'start_minute' => '', 'end_hour' => '', 'end_minute' => '');
    ?>
    <label>Start Time</label>
    <select name="work_status_hours[<?php echo $day; ?>][start_hour]">
        <?php
        for ($hour = 0; $hour < 24; $hour++) {
            printf('<option value="%02d" %s>%02d</option>', $hour, selected($day_hours['start_hour'], str_pad($hour, 2, '0', STR_PAD_LEFT), false), $hour);
        }
        ?>
    </select>
    <select name="work_status_hours[<?php echo $day; ?>][start_minute]">
        <?php
        for ($minute = 0; $minute < 60; $minute++) {
            printf('<option value="%02d" %s>%02d</option>', $minute, selected($day_hours['start_minute'], str_pad($minute, 2, '0', STR_PAD_LEFT), false), $minute);
        }
        ?>
    </select>
    <label>End Time</label>
    <select name="work_status_hours[<?php echo $day; ?>][end_hour]">
        <?php
        for ($hour = 0; $hour < 24; $hour++) {
            printf('<option value="%02d" %s>%02d</option>', $hour, selected($day_hours['end_hour'], str_pad($hour, 2, '0', STR_PAD_LEFT), false), $hour);
        }
        ?>
    </select>
    <select name="work_status_hours[<?php echo $day; ?>][end_minute]">
        <?php
        for ($minute = 0; $minute < 60; $minute++) {
            printf('<option value="%02d" %s>%02d</option>', $minute, selected($day_hours['end_minute'], str_pad($minute, 2, '0', STR_PAD_LEFT), false), $minute);
        }
        ?>
    </select>
    <?php
}

// Field callback for working text
function work_status_working_text_field_callback() {
    $working_text = get_option('work_status_working_text', '👨🏻‍💻 Working');
    echo "<input type='text' name='work_status_working_text' value='" . esc_attr($working_text) . "' />";
}

// Field callback for away text
function work_status_away_text_field_callback() {
    $away_text = get_option('work_status_away_text', '🏃🏻 Away');
    echo "<input type='text' name='work_status_away_text' value='" . esc_attr($away_text) . "' />";
}

// Field callback for vacation mode
function work_status_vacation_mode_field_callback() {
    $vacation_mode_enabled = get_option('work_status_vacation_mode', false);
    echo "<input type='checkbox' name='work_status_vacation_mode' value='1' " . checked($vacation_mode_enabled, true, false) . " />";
}

// Field callback for vacation text
function work_status_vacation_text_field_callback() {
    $vacation_text = get_option('work_status_vacation_text', '🏝️ On Vacation');
    echo "<input type='text' name='work_status_vacation_text' value='" . esc_attr($vacation_text) . "' />";
}

// Sanitize checkbox
function sanitize_checkbox($input) {
    return isset($input) ? true : false;
}

// Sanitize work hours
function sanitize_work_hours($input) {
    // Validate and sanitize input
    $sanitized_hours = array();
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

    foreach ($days as $day) {
        $day_hours = isset($input[$day]) ? $input[$day] : array('start_hour' => '', 'start_minute' => '', 'end_hour' => '', 'end_minute' => '');
        $start_hour = sanitize_text_field($day_hours['start_hour']);
        $start_minute = sanitize_text_field($day_hours['start_minute']);
        $end_hour = sanitize_text_field($day_hours['end_hour']);
        $end_minute = sanitize_text_field($day_hours['end_minute']);

        // Validate time format
        if (preg_match('/^(0[0-9]|1[0-9]|2[0-3])$/', $start_hour) && preg_match('/^[0-5]?[0-9]$/', $start_minute) && preg_match('/^(0[0-9]|1[0-9]|2[0-3])$/', $end_hour) && preg_match('/^[0-5]?[0-9]$/', $end_minute)) {
            $sanitized_hours[$day] = array('start_hour' => $start_hour, 'start_minute' => $start_minute, 'end_hour' => $end_hour, 'end_minute' => $end_minute);
        }
    }

    return $sanitized_hours;
}

// Calculate work status based on configured work hours
function calculate_work_status() {
    $current_time = current_time('H:i');
    $current_day = strtolower(date('l'));
    $work_status_hours = get_option('work_status_hours');

    if (isset($work_status_hours[$current_day])) {
        $start_time = strtotime($work_status_hours[$current_day]['start_hour'] . ':' . $work_status_hours[$current_day]['start_minute']);
        $end_time = strtotime($work_status_hours[$current_day]['end_hour'] . ':' . $work_status_hours[$current_day]['end_minute']);
        $current_time_unix = strtotime($current_time);

        if ($current_time_unix >= $start_time && $current_time_unix <= $end_time) {
            return true; // Currently Working
        }
    }

    return false; // Currently Away
}

// Add links on plugin page
function currently_plugin_links($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $custom_links = array(
            '<a href="https://pixelvise.com" target="_blank">Visit Pixelvise</a>',
            '<a href="admin.php?page=currently">Currently Settings</a>',
        );

        $links = array_merge($links, $custom_links);
    }
    return $links;
}
add_filter('plugin_row_meta', 'currently_plugin_links', 10, 2);
?>
