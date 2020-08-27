<?php

/**
 * Plugin Name: Mercado Pago payments for WooCommerce
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: Configure the payment options and accept payments with cards, ticket and money of Mercado Pago account.
 * Version: 4.3.0
 * Author: Mercado Pago
 * Author URI: https://developers.mercadopago.com/
 * Text Domain: woocommerce-mercadopago
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 4.3.0
 * @package MercadoPago
 * @category Core
 * @author Mercado Pago
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WC_MERCADOPAGO_BASENAME')) {
    define('WC_MERCADOPAGO_BASENAME', plugin_basename(__FILE__));
}

if (!function_exists('is_plugin_active')) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Load plugin text domain.
 *
 * Need to require here before test for PHP version.
 *
 * @since 3.0.1
 */
function woocommerce_mercadopago_load_plugin_textdomain()
{
    $text_domain = 'woocommerce-mercadopago';
    $locale = apply_filters('plugin_locale', get_locale(), $text_domain);

    $original_language_file = dirname(__FILE__) . '/i18n/languages/woocommerce-mercadopago-' . $locale . '.mo';

    // Unload the translation for the text domain of the plugin
    unload_textdomain($text_domain);
    // Load first the override file
    load_textdomain($text_domain, $original_language_file);
}

add_action( 'plugins_loaded', 'woocommerce_mercadopago_load_plugin_textdomain' );

if (!class_exists('WC_WooMercadoPago_Init')) {
    include_once dirname(__FILE__) . '/includes/module/WC_WooMercadoPago_Init.php';
    add_action('plugins_loaded', array('WC_WooMercadoPago_Init', 'woocommerce_mercadopago_init'));
}
