<?php
/*
  Plugin Name: WooCommerce Ameria Payment Gateway Pretty
  Plugin URI (base): https://github.com/uptimex/WooCommerce-Ameria-Payment-Gateway-Pretty
  Plugin URI: https://github.com/davitp/ameria-gateway-wc
  Description: WooCommerce payment gateway using Ameriabank third-party platform (on ARCA)
  Author: Davit Petrosyan
  Forked From: Aram Dekart (https://github.com/uptimex)
  Author URI: https://github.com/davitp
  Version: 1.0.0
  Requires at least: WP 4.7.1
  Tested up to: WP 4.7.1
  Text Domain: ameria-gateway-wc
  Domain Path: /languages
  Forum URI: #
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Session start, probably can be solved putting the plugin in init function
session_start();

// Initiate the plugin after plugins loaded
add_action( 'plugins_loaded', 'ameria_gateway_init', 11 );

function ameria_gateway_init() {
    class Ameria_Gateway extends WC_Payment_Gateway {
        public function __construct() {
          // Descriptive parameters for gateway
          $this->id = 'Ameria_Gateway';
          $this->has_fields = false;
          $this->method_title = 'Ameriabank Payment Gateway';
          $this->method_description = "Payment via Ameriabank third party payment system.";
          $this->notify_url = str_replace( 'https:', 'http:', home_url( '/wc-api/'. $this->id )  );

          // Initalize form fields and settings
          $this->init_form_fields();
          $this->init_settings();

		      $this->title = $this->settings['title'];
          $this->description = $this->settings['description'];
          $this->order_button_text = __($this->settings['buttontext'], 'Ameria_Gateway' );

          // Hook into gateway action, clears buffer and return -1 and exits, prevented by redirect to thank you page
          add_action( 'woocommerce_api_Ameria_Gateway', array( $this, 'wapgp_response' ) );

          // Get testmode. Getting exactly here to avoid default empty issue.
          $this->testmode = ($this->get_option('testmode')) ? 'test' : '';
          $this->serviceUrl = 'https://'. $this->testmode . 'payments.ameriabank.am/'

          // This line forces update settings on payment page
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        // Hook into gateway action, being call on return from Ameriabank third party gateway
        public function wapgp_response ($param) {
          global $woocommerce;

          // Store session information within variables
          $order_id = $_SESSION['order_id'];
          $order_description = $_SESSION['order_description'];
          $cart_total = $_SESSION['cart_total'];
          $ameria_order_id = (int) $_SESSION['ameria_order_id'];

          // Unset the session sensitive information to avoid bugs on later use
          unset($_SESSION['order_id']);
          unset($_SESSION['order_description']);
          unset($_SESSION['cart_total']);
          unset($_SESSION['ameria_order_id'])

          try{
            $options = array(
              'soap_version'    => SOAP_1_1,
              'exceptions'      => true,
              'trace'           => 1,
              'wdsl_local_copy' => true
            );

            $client = new SoapClient($this->serviceUrl .'webservice/PaymentService.svc?wsdl', $options);

             // Set parameters for Ameriabank
            $parms['paymentfields'] ['ClientID'] = $this->get_option('client_id'); // clientID from Ameriabank
            $parms['paymentfields'] ['Description'] = $order_description;
            $parms['paymentfields'] ['OrderID']= $ameria_order_id;
            $parms['paymentfields'] ['Username']= $this->get_option('username'); // username from Ameriabank
            $parms['paymentfields'] ['Password']= $this->get_option('password'); // password from Ameriabank
            $parms['paymentfields'] ['PaymentAmount']= (int)$cart_total; // payment amount of your Order

            // Call web service PassMember method
            $webService = $client->GetPaymentFields($parms);
            $continue = false;

            if($webService->GetPaymentFieldsResult->respcode == '00')
            {
              if($webService->GetPaymentFieldsResult ->paymenttype == '1')
              {
                $webService1 = $client->Confirmation($parms);
                if($webService1->ConfirmationResult->Respcode == '00')
                {
                  $continue = true;
                  // you can print your check or call Ameriabank check example
                  // echo   '<iframe id="idIframe" src="https://testpayments.ameriabank.am/forms/frm_checkprint.aspx?lang=am&paymentid='.$_POST['paymentid'].'" width="560px" height="820px" frameborder="0"></iframe>';
                }
              }
              else
              {
                $continue = true;
                // you can print your check or call Ameriabank check example
                // echo   '<iframe id="idIframe" src="https://testpayments.ameriabank.am/forms/frm_checkprint.aspx?lang=am&paymentid='.$_POST['paymentid'].'" width="560px" height="820px" frameborder="0"></iframe>';
              }
            }
           } catch (Exception $e) {
              // Catching an exception, not really being used, predefined $continue is false
          }

          // If is okay, code will put the order completed and redirect to thank you page
          // If not okay, code will redirect to the error page
          if($continue) {
            // Get order by order_id
            $order = wc_get_order( $order_id );

            // Set payment comleted
            $order->payment_complete();

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Get successfull payd page
            $thankyou = $this->get_return_url( $order );

            // Redirect to success page
            wp_redirect($thankyou); die;

          } else {
            // Set error notice
            wc_add_notice( __('Problem with the payment. Please contact site administrator.', 'wapgp'), 'error' );

            // Get checkout url using global $woocommerce
            $checkout_url = $woocommerce->cart->get_checkout_url();

            // Redirect to the checkout page
            wp_redirect($checkout_url);

            die;
          }
        }

        /**
         * Initialize Gateway Settings Form Fields
        */
        public function init_form_fields() {
            $this->form_fields = apply_filters( 'Ameria_Gateway_form_fields', array(
              'enabled' => array(
                  'title'   => __( 'Enable/Disable', 'Ameria_Gateway' ),
                  'type'    => 'checkbox',
                  'label'   => __( 'Enable Ameriabank Payment', 'Ameria_Gateway' ),
                  'default' => 'yes'
                ),
              'testmode' => array(
                  'title'       => __( 'Test mode', 'Ameria_Gateway' ),
                  'type'        => 'checkbox',
                  'label'   => __( 'If enabled you can test', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                  'default' => 'yes'
                ),
              'title' => array(
                  'title'       => __( 'Title', 'Ameria_Gateway' ),
                  'type'        => 'text',
                  'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'Ameria_Gateway' ),
                  'default'     => __( 'Ameriabank Payment', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                ),
              'buttontext' => array(
                  'title'       => __( 'Button Text', 'Ameria_Gateway' ),
                  'type'        => 'text',
                  'description' => __( 'This controls the title for the button, during checkout.', 'Ameria_Gateway' ),
                  'default'     => __( 'Pay with visa, mastercard, arca', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                ),
              'description' => array(
                  'title'       => __( 'Description', 'Ameria_Gateway' ),
                  'type'        => 'textarea',
                  'description' => __( 'Payment method description that the customer will see on your checkout.', 'Ameria_Gateway' ),
                  'default'     => __( 'Thank you for using our website.', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                ),
              'ameria_order_id' => array(
                    'title'       => __( 'Starting Order Id', 'Ameria_Gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Starting Order Id must be unique in every single order. And increment after every order.', 'Ameria_Gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
              'client_id' => array(
                  'title'       => __( 'Client ID', 'Ameria_Gateway' ),
                  'type'        => 'text',
                  'description' => __( 'This is clinet ID setting for using Ameriabank vpos service.', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                ),
              'username' => array(
                  'title'       => __( 'Username', 'Ameria_Gateway' ),
                  'type'        => 'text',
                  'description' => __( 'This is username setting for using Ameriabank vpos service.', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                ),
              'password' => array(
                  'title'       => __( 'Password', 'Ameria_Gateway' ),
                  'type'        => 'password',
                  'description' => __( 'This is password setting for using Ameriabank vpos service.', 'Ameria_Gateway' ),
                  'desc_tip'    => true,
                ),
              )
            );
        }


        /**
       * Process the payment and return the result
       **/
        public function process_payment($order_id) {
            // Get order info by order_id
            $order = wc_get_order( $order_id );

            try {
                $options = array(
                  'soap_version'    => SOAP_1_1,
                  'exceptions'      => true,
                  'trace'           => 1,
                  'wdsl_local_copy' => true
                );

                $client = new SoapClient($this->serviceUrl. 'webservice/PaymentService.svc?wsdl', $options);

                // Get last insert id
                $last_insert_id = $this->get_option('ameria_order_id') + 1;

					      // Change an option of ameria order_id, increment to have unique one
					      $opt_array = get_option('woocommerce_' . $this->id . '_settings');
					      $opt_array['ameria_order_id'] = $last_insert_id;
					      update_option($this->get_option_key(),$opt_array);

                // Get order total
                $this->paymentAmount = $order->get_total();

                // Save different information in Session for later testing
                $_SESSION['cart_total'] = $this->paymentAmount;
                $_SESSION['order_description'] = $order_description = $this->get_option('description');
                $_SESSION['order_id'] = (int)$order_id;
                $_SESSION['ameria_order_id'] = $last_insert_id;

                // Set parameters for ameriabank request
                $parms['paymentfields'] ['ClientID'] = $this->get_option('client_id'); // clientID from Ameriabank
                $parms['paymentfields'] ['Description'] = $order_description;
                $parms['paymentfields'] ['OrderID']= $last_insert_id; // orderID wich must be unique for every transaction;
                $parms['paymentfields'] ['Username']= $this->get_option('username'); // username from Ameriabank
                $parms['paymentfields'] ['Password']= $this->get_option('password'); // password from Ameriabank
                $parms['paymentfields'] ['PaymentAmount'] = $this->paymentAmount; // payment amount of your Order
                $parms['paymentfields'] ['backURL']= $this->notify_url;  // your backurl after transaction rediracted to this url

                // Call web service PassMember methord and print response
                $webService = $client->GetPaymentID($parms);

                if($webService->GetPaymentIDResult->Respcode == '1' && $webService->GetPaymentIDResult->Respmessage =='OK')
                {
                  // Mark as on-hold (we're awaiting the payment)
                  $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );

                  //rediract to Ameriabank server or you can use iFrame to show on your page
                  $ameriaRedirectUri = $this->serviceUrl . "forms/frm_paymentstype.aspx?clientid=" . $this->get_option('client_id') . "clienturl={$this->notify_url}&lang=am&paymentid={$webService->GetPaymentIDResult->PaymentID}";

                  // Return success and third party(ameriabank) redirect uri
                  return array(
                      'result'    => 'success',
                      'redirect'  => $ameriaRedirectUri
                  );
                }
                else
                {
                  wc_add_notice( __('Error processing checkout. Please contact administrator.','payment'), 'error' );
                }

              } catch (Exception $e) {
                wc_add_notice( __('Error processing checkout. Please contact administrator.','payment'), 'error' );
              }
        }
    } // end \Ameria_Gateway class
}

function Ameria_Gateway_add_to_gateways( $gateways ) {
    $gateways[] = 'Ameria_Gateway';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'Ameria_Gateway_add_to_gateways' );
