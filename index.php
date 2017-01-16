<?php
/*
  Plugin Name: WooCommerce Ameria Payment Gateway Pretty
  Plugin URI: 
  Description: 
  Author: Aram Dekart
  Version: 1.0
  Requires at least: WP 4.1.0
  Tested up to: WP 4.7
  Text Domain: woocommerce-ameria-payment-gateway-pretty
  Domain Path: /languages
  Forum URI: #
  Author URI: 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'wc_ameria_payment_gateway_pretty_init', 11 );

function wc_ameria_payment_gateway_pretty_init() {

    class WC_Ameria_Payment_Gateway_Pretty extends WC_Payment_Gateway {

        public function __construct() {
          $this->id = 'WC_Ameria_Payment_Gateway_Pretty';
          $this->has_fields = false;
          $this->title = 'Ameria Payment Gateway';
          $this->method_title = 'Ameria Payment Gateway';
          $this->method_description = "Ameria Payment Gateway Description";
          // $this->notify_url = str_replace( 'https:', 'http:', home_url( '/wc-api/WC_Ameria_Payment_Gateway_Pretty' )  );

          // var_dump($this->notify_url); die;
          add_action( 'woocommerce_api_wc_ameria_payment_gateway_pretty', array( $this, 'wapgp_response' ) );


          $this->init_form_fields();
          $this->init_settings();
        }

        public function wapgp_response () {
          echo 'die'; die;
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {
              
            $this->form_fields = apply_filters( 'wc_ameria_payment_gateway_pretty_form_fields', array(
                  
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'wc_ameria_payment_gateway_pretty' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Ameriabank Payment', 'wc_ameria_payment_gateway_pretty' ),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title'       => __( 'Title', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => __( 'Ameriabank Payment', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __( 'Description', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => __( 'Please remit payment to Store Name upon pickup or delivery.', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),

                'instructions' => array(
                    'title'       => __( 'Instructions', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            ) );
        }



    } // end \WC_Ameria_Payment_Gateway_Pretty class
}

function wc_ameria_payment_gateway_pretty_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Ameria_Payment_Gateway_Pretty';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_ameria_payment_gateway_pretty_add_to_gateways' );