<?php
/*
Plugin Name: Chimera Woocommerce Gateway
Plugin URI:
Description: Extends WooCommerce by adding a Chimera Gateway
Version: 0.1.0
Tested up to: 0.1.0
Author: mosu-forge, SerHack, afterconnery
Author URI: https://monerointegrations.com/
*/
// This code isn't for Dark Net Markets, please report them to Authority!

defined( 'ABSPATH' ) || exit;

// Constants, you can edit these if you fork this repo
define('CHIMERA_GATEWAY_EXPLORER_URL', 'http://blockapi.chimeraproject.io:8080/');
define('CHIMERA_GATEWAY_ATOMIC_UNITS', 2);
define('CHIMERA_GATEWAY_ATOMIC_UNIT_THRESHOLD', 100); // Amount under in atomic units payment is valid
define('CHIMERA_GATEWAY_DIFFICULTY_TARGET', 60);

// Do not edit these constants
define('CHIMERA_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHIMERA_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHIMERA_GATEWAY_ATOMIC_UNITS_POW', pow(10, CHIMERA_GATEWAY_ATOMIC_UNITS));
define('CHIMERA_GATEWAY_ATOMIC_UNITS_SPRINTF', '%.'.CHIMERA_GATEWAY_ATOMIC_UNITS.'f');

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'chimera_init', 1);
function chimera_init() {

    // If the class doesn't exist (== WooCommerce isn't installed), return NULL
    if (!class_exists('WC_Payment_Gateway')) return;

    // If we made it this far, then include our Gateway Class
    require_once('include/class-chimera-gateway.php');

    // Create a new instance of the gateway so we have static variables set up
    new chimera_Gateway($add_action=false);

    // Include our Admin interface class
    require_once('include/admin/class-chimera-admin-interface.php');

    add_filter('woocommerce_payment_gateways', 'chimera_gateway');
    function chimera_gateway($methods) {
        $methods[] = 'Chimera_Gateway';
        return $methods;
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chimera_payment');
    function chimera_payment($links) {
        $plugin_links = array(
            '<a href="'.admin_url('admin.php?page=chimera_gateway_settings').'">'.__('Settings', 'chimera_gateway').'</a>'
        );
        return array_merge($plugin_links, $links);
    }

    add_filter('cron_schedules', 'chimera_cron_add_one_minute');
    function chimera_cron_add_one_minute($schedules) {
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display' => __('Once every minute', 'chimera_gateway')
        );
        return $schedules;
    }

    add_action('wp', 'chimera_activate_cron');
    function chimera_activate_cron() {
        if(!wp_next_scheduled('chimera_update_event')) {
            wp_schedule_event(time(), 'one_minute', 'chimera_update_event');
        }
    }

    add_action('chimera_update_event', 'chimera_update_event');
    function chimera_update_event() {
        Chimera_Gateway::do_update_event();
    }

    add_action('woocommerce_thankyou_'.Chimera_Gateway::get_id(), 'chimera_order_confirm_page');
    add_action('woocommerce_order_details_after_order_table', 'chimera_order_page');
    add_action('woocommerce_email_after_order_table', 'chimera_order_email');

    function chimera_order_confirm_page($order_id) {
        Chimera_Gateway::customer_order_page($order_id);
    }
    function chimera_order_page($order) {
        if(!is_wc_endpoint_url('order-received'))
            Chimera_Gateway::customer_order_page($order);
    }
    function chimera_order_email($order) {
        Chimera_Gateway::customer_order_email($order);
    }

    add_action('wc_ajax_chimera_gateway_payment_details', 'chimera_get_payment_details_ajax');
    function chimera_get_payment_details_ajax() {
        Chimera_Gateway::get_payment_details_ajax();
    }

    add_filter('woocommerce_currencies', 'chimera_add_currency');
    function chimera_add_currency($currencies) {
        $currencies['Chimera'] = __('Chimera', 'chimera_gateway');
        return $currencies;
    }

    add_filter('woocommerce_currency_symbol', 'chimera_add_currency_symbol', 10, 2);
    function chimera_add_currency_symbol($currency_symbol, $currency) {
        switch ($currency) {
        case 'Chimera':
            $currency_symbol = 'CMRA';
            break;
        }
        return $currency_symbol;
    }

    if(Chimera_Gateway::use_chimera_price()) {

        // This filter will replace all prices with amount in Chimera (live rates)
        add_filter('wc_price', 'chimera_live_price_format', 10, 3);
        function chimera_live_price_format($price_html, $price_float, $args) {
            if(!isset($args['currency']) || !$args['currency']) {
                global $woocommerce;
                $currency = strtoupper(get_woocommerce_currency());
            } else {
                $currency = strtoupper($args['currency']);
            }
            return Chimera_Gateway::convert_wc_price($price_float, $currency);
        }

        // These filters will replace the live rate with the exchange rate locked in for the order
        // We must be careful to hit all the hooks for price displays associated with an order,
        // else the exchange rate can change dynamically (which it should for an order)
        add_filter('woocommerce_order_formatted_line_subtotal', 'chimera_order_item_price_format', 10, 3);
        function chimera_order_item_price_format($price_html, $item, $order) {
            return Chimera_Gateway::convert_wc_price_order($price_html, $order);
        }

        add_filter('woocommerce_get_formatted_order_total', 'chimera_order_total_price_format', 10, 2);
        function chimera_order_total_price_format($price_html, $order) {
            return Chimera_Gateway::convert_wc_price_order($price_html, $order);
        }

        add_filter('woocommerce_get_order_item_totals', 'chimera_order_totals_price_format', 10, 3);
        function chimera_order_totals_price_format($total_rows, $order, $tax_display) {
            foreach($total_rows as &$row) {
                $price_html = $row['value'];
                $row['value'] = Chimera_Gateway::convert_wc_price_order($price_html, $order);
            }
            return $total_rows;
        }

    }

    add_action('wp_enqueue_scripts', 'chimera_enqueue_scripts');
    function chimera_enqueue_scripts() {
        if(Chimera_Gateway::use_chimera_price())
            wp_dequeue_script('wc-cart-fragments');
        if(Chimera_Gateway::use_qr_code())
            wp_enqueue_script('chimera-qr-code', CHIMERA_GATEWAY_PLUGIN_URL.'assets/js/qrcode.min.js');

        wp_enqueue_script('chimera-clipboard-js', CHIMERA_GATEWAY_PLUGIN_URL.'assets/js/clipboard.min.js');
        wp_enqueue_script('chimera-gateway', CHIMERA_GATEWAY_PLUGIN_URL.'assets/js/chimera-gateway-order-page.js');
        wp_enqueue_style('chimera-gateway', CHIMERA_GATEWAY_PLUGIN_URL.'assets/css/chimera-gateway-order-page.css');
    }

    // [chimera-price currency="USD"]
    // currency: BTC, GBP, etc
    // if no none, then default store currency
    function chimera_price_func( $atts ) {
        global  $woocommerce;
        $a = shortcode_atts( array(
            'currency' => get_woocommerce_currency()
        ), $atts );

        $currency = strtoupper($a['currency']);
        $rate = Chimera_Gateway::get_live_rate($currency);
        if($currency == 'BTC')
            $rate_formatted = sprintf('%.8f', $rate / 1e8);
        else
            $rate_formatted = sprintf('%.8f', $rate / 1e8);

        return "<span class=\"chimera-price\">1 CMRA = $rate_formatted $currency</span>";
    }
    add_shortcode('chimera-price', 'chimera_price_func');


    // [chimera-accepted-here]
    function chimera_accepted_func() {
        return '<img src="'.CHIMERA_GATEWAY_PLUGIN_URL.'assets/images/chimera-accepted-here.png" />';
    }
    add_shortcode('chimera-accepted-here', 'chimera_accepted_func');

}

register_deactivation_hook(__FILE__, 'chimera_deactivate');
function chimera_deactivate() {
    $timestamp = wp_next_scheduled('chimera_update_event');
    wp_unschedule_event($timestamp, 'chimera_update_event');
}

register_activation_hook(__FILE__, 'chimera_install');
function chimera_install() {
    global $wpdb;
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . "chimera_gateway_quotes";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               order_id BIGINT(20) UNSIGNED NOT NULL,
               payment_id VARCHAR(64) DEFAULT '' NOT NULL,
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               paid TINYINT NOT NULL DEFAULT 0,
               confirmed TINYINT NOT NULL DEFAULT 0,
               pending TINYINT NOT NULL DEFAULT 1,
               created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (order_id)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "chimera_gateway_quotes_txids";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               payment_id VARCHAR(64) DEFAULT '' NOT NULL,
               txid VARCHAR(64) DEFAULT '' NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               height MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
               PRIMARY KEY (id),
               UNIQUE KEY (payment_id, txid, amount)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "chimera_gateway_live_rates";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (currency)
               ) $charset_collate;";
        dbDelta($sql);
    }
}
