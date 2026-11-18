<?php
/*
Plugin Name: RafaPayBD - Solana (SOL) & BDT Payment Gateway
Plugin URI: https://rafapaybd.com
Description: Accept instant Solana (SOL) and BDT payments on your WooCommerce store via RafaPayBD REST API.
Version: 1.0.0
Author: RafaPayBD
Author URI: https://rafapaybd.com
*/

if (!defined('ABSPATH')) exit;

add_filter('woocommerce_payment_gateways', 'rafapaybd_add_gateway_class');
function rafapaybd_add_gateway_class($gateways) {
    $gateways[] = 'WC_RafaPayBD_Gateway';
    return $gateways;
}

add_action('plugins_loaded', 'rafapaybd_init_gateway_class');
function rafapaybd_init_gateway_class() {
    class WC_RafaPayBD_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'rafapaybd';
            $this->method_title = 'RafaPayBD (Solana & BDT)';
            $this->method_description = 'Automated Solana (SOL) and BDT Payment Gateway.';
            $this->has_fields = false;
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title', 'Pay with Solana (SOL) / BDT');
            $this->api_key = $this->get_option('api_key');
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array('title' => 'Enable/Disable', 'type' => 'checkbox', 'label' => 'Enable RafaPayBD Gateway', 'default' => 'yes'),
                'title' => array('title' => 'Title', 'type' => 'text', 'default' => 'Pay with Solana (SOL) / BDT'),
                'api_key' => array('title' => 'Merchant API Key', 'type' => 'text', 'description' => 'Get your API key from https://rafapaybd.com')
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $response = wp_remote_post('https://rafapaybd.com', array(
                'headers' => array('Content-Type' => 'application/json', 'X-API-Key' => $this->api_key),
                'body' => json_encode(array(
                    'amount' => $order->get_total(),
                    'order_id' => (string)$order_id,
                    'webhook_url' => home_url('/?wc-api=rafapaybd_webhook')
                ))
            ));

            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['payment_url'])) {
                    return array('result' => 'success', 'redirect' => $body['payment_url']);
                }
            }
            wc_add_notice('Payment error: Unable to connect to RafaPayBD.', 'error');
            return;
        }
    }
}
