<?php
/*
Plugin Name: Currently
Description: The "Currently" plugin is designed to display the current work status based on configured work hours or vacation mode in WordPress.
Version: 1.0.1
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
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];
    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo plugin_dir_url( __FILE__ ) . 'currently-styles.css'; ?>">
    <div class="wrap">
        <div id="currently-header">
            <div class="header-left">
                <img id="currently-logo" src="<?php echo plugin_dir_url(__FILE__) . 'img/currently-logo.png'; ?>" alt="Currently Logo">
                <div id="currently-info">
                    <p>The "Currently" plugin is designed to display the current work status based on configured work hours or vacation mode in WordPress.</p>
                    <p>Developed by Craig Gomes | <a href="https://craiggomes.com">Visit Blog</a> | <a href="https://pixelvise.com">Need a Website? Visit Pixelvise</a></p>
                    <p>Insert shortcode <strong>[currently]</strong> wherever you want to display your status.</p>
                </div>
            </div>
            <div class="header-right">
                <h1>Currently Settings</h1>
                <h2>Version <?php echo $plugin_version; ?></h2>
                <!-- Display the output of the currently shortcode -->
                <div class="currently-shortcode-output">
                    <strong><?php echo do_shortcode('[currently]'); ?></strong>
                </div>
            </div>
        </div>
        <div id="currently-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#general-tab" class="nav-tab nav-tab-active">General</a>
                <a href="#vacation-tab" class="nav-tab">Vacation</a>
                <a href="#advanced-tab" class="nav-tab">Advanced</a>
            </h2>
            <div id="general-tab" class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('work_status_general_settings');
                    do_settings_sections('work_status_general_settings');
                    ?>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                    </p>
                </form>
            </div>
            <div id="vacation-tab" class="tab-content" style="display:none;">
                <?php work_status_vacation_tab_content(); ?>
            </div>
            <div id="advanced-tab" class="tab-content" style="display:none;">
                <!-- Add advanced settings here -->
            </div>
        </div>
    </div>
    <script>
        // JavaScript/jQuery for tab functionality
        jQuery(document).ready(function($) {
            $('.nav-tab-wrapper a').click(function(event) {
                event.preventDefault();
                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                var selected_tab = $(this).attr('href');
                $(selected_tab).show();
            });

            // Ensure the initially selected tab is shown
            var initialTab = window.location.hash || $('.nav-tab-wrapper a:first').attr('href');
            $(initialTab).show();
            $('.nav-tab-wrapper a[href="' + initialTab + '"]').addClass('nav-tab-active');
        });
    </script>
    <?php
}

// Add fields for vacation settings in the vacation tab
function work_status_vacation_tab_content() {
    ?>
    <form method="post" action="options.php">
        <?php
        settings_fields('work_status_vacation_settings');
        do_settings_sections('work_status_vacation_settings');
        ?>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </p>
    </form>
    <?php
}

// Register and initialize settings for general tab
function work_status_general_settings_init() {
    // Register settings for work hours
    register_setting('work_status_general_settings', 'work_status_hours', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_work_hours',
    ));

    // Register settings for working text
    register_setting('work_status_general_settings', 'work_status_working_text', 'sanitize_text_field');

    // Register settings for away text
    register_setting('work_status_general_settings', 'work_status_away_text', 'sanitize_text_field');

    // Add section and fields for general settings
    add_settings_section('work_status_general_section', 'General Settings', 'work_status_general_section_callback', 'work_status_general_settings');

    // Add field for each day's work hours
    $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    foreach ($days as $day) {
        add_settings_field(
            'work_status_' . strtolower($day) . '_field',
            $day . ' Work Hours',
            'work_status_day_field_callback',
            'work_status_general_settings',
            'work_status_general_section',
            array('day' => strtolower($day))
        );
    }

    // Add fields for working text and away text
    add_settings_field(
        'work_status_working_text_field',
        'Working Text',
        'work_status_working_text_field_callback',
        'work_status_general_settings',
        'work_status_general_section'
    );

    add_settings_field(
        'work_status_away_text_field',
        'Away Text',
        'work_status_away_text_field_callback',
        'work_status_general_settings',
        'work_status_general_section'
    );
}
// Register and initialize settings for vacation tab
function work_status_vacation_settings_init() {
    // Register settings for vacation mode
    register_setting('work_status_vacation_settings', 'work_status_vacation_mode', 'sanitize_vacation_mode');

    // Register settings for vacation text
    register_setting('work_status_vacation_settings', 'work_status_vacation_text', 'sanitize_text_field');

    // Add section and fields for vacation settings
    add_settings_section('work_status_vacation_section', 'Vacation Settings', 'work_status_vacation_section_callback', 'work_status_vacation_settings');

    // Add field for vacation mode
    add_settings_field(
        'work_status_vacation_mode_field',
        'Enable Vacation Mode',
        'work_status_vacation_mode_field_callback',
        'work_status_vacation_settings',
        'work_status_vacation_section'
    );

    // Add field for vacation text
    add_settings_field(
        'work_status_vacation_text_field',
        'Vacation Text',
        'work_status_vacation_text_field_callback',
        'work_status_vacation_settings',
        'work_status_vacation_section'
    );
}

add_action('admin_init', 'work_status_vacation_settings_init');

// Section callback for vacation settings
function work_status_vacation_section_callback() {
    echo 'Set your vacation settings below:';
}

// Field callback for vacation mode
function work_status_vacation_mode_field_callback() {
    $vacation_mode_enabled = get_option('work_status_vacation_mode', false);
    echo "<input type='checkbox' name='work_status_vacation_mode' value='1' " . checked(1, $vacation_mode_enabled, false) . " />";
}

// Field callback for vacation text
function work_status_vacation_text_field_callback() {
    $vacation_text = get_option('work_status_vacation_text', '🏝️ On Vacation');
    echo "<input type='text' name='work_status_vacation_text' value='" . esc_attr($vacation_text) . "' />";
}


add_action('admin_init', 'work_status_general_settings_init');

// Section callback for general settings
function work_status_general_section_callback() {
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
