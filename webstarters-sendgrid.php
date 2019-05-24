<?php

/*
    Plugin Name: Webstarters SendGrid
    Plugin URI: https://webstarters.dk
    Description: A plugin from Webstarters for integration with SendGrid
    Version: 0.1.0
    Author: Webstarters
    Author URI: https://webstarters.dk
*/

// Don't allow direct access to this file.
if (! defined('ABSPATH')) {
    exit;
}

// Set up our constants.
if (! defined('WS_SENDGRID_BASE_PATH')) {
    define('WS_SENDGRID_BASE_PATH', plugin_dir_path(__FILE__));
}

if (! defined('WS_SENDGRID_BASE_URL')) {
    define('WS_SENDGRID_BASE_URL', plugin_dir_url(__FILE__));
}

// Initialize if requirements are met.
if (! version_compare(PHP_VERSION, '7.1', '>=')) {
    add_action('admin_notices', function () {
        /* translators: %2$s: PHP version */
        $message      = sprintf(esc_html__('%1$s requires PHP version %2$s+. The plugin will not initialize.', 'webstarters'), 'Webstarters SendGrid', '7.1');
        $html = sprintf('<div class="error">%s</div>', wpautop($message));
        echo wp_kses_post($html);
    });
} elseif (! version_compare(get_bloginfo('version'), '5.2', '>=')) {
    add_action('admin_notices', function () {
        /* translators: %2$s: WordPress version */
        $message      = sprintf(esc_html__('%1$s requires WordPress version %2$s+. The plugin will not initialize.', 'webstarters'), 'Webstarters SendGrid', '5.2');
        $html = sprintf('<div class="error">%s</div>', wpautop($message));
        echo wp_kses_post($html);
    });
} else {

    // Start engines.
    require_once(WS_SENDGRID_BASE_PATH.'/vendor/autoload.php');
    Webstarters\SendGrid\WS_SendGrid::init();

    if (! function_exists('sendgrid_mail')) {
        /**
         * Send mail through SendGrid.
         *
         * @param string       $to              Array or comma-separated list of email addresses to send message.
         * @param string       $subject         Email subject.
         * @param string       $message         Message contents.
         * @param string|array $headers         Optional. Additional headers.
         * @param string|array $attachments     Optional. Files to attach.
         * @param array        $templateData    Optional. Dynamic template data.
         * @param int          $templateId      Optional. Specify mail template.
         *
         * @return bool Whether the email contents were sent successfully.
         */
        function sendgrid_mail($to, $subject, $message, $headers = '', $attachments = [], $templateData = [], $templateId = null) {
            return Webstarters\SendGrid\WS_SendGrid::mail($to, $subject, $message, $headers, $attachments, $templateData, $templateId);
        }
    }

    if (! function_exists('wp_mail')) {
        /**
         * Overwrite the wp_mail function to send through SendGrid.
         *
         * @param string|array $to          Array or comma-separated list of email addresses to send message.
         * @param string       $subject     Email subject
         * @param string       $message     Message contents
         * @param string|array $headers     Optional. Additional headers.
         * @param string|array $attachments Optional. Files to attach.
         *
         * @return bool Whether the email contents were sent successfully.
         */
        function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
            return Webstarters\SendGrid\WS_SendGrid::mail($to, $subject, $message, $headers, $attachments);
        }
    }
}
