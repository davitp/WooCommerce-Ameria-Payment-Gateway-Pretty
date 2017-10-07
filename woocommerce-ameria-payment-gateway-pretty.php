<?php
/*
  Plugin Name: WooCommerce Ameria Payment Gateway Pretty
  Plugin URI: https://github.com/uptimex/WooCommerce-Ameria-Payment-Gateway-Pretty
  Description: WooCommerce payment gateway using Ameriabank third-party platform (on ARCA)
  Author: Aram Dekart
  Author URI: https://github.com/uptimex
  Version: 1.0.0
  Requires at least: WP 4.7.1
  Tested up to: WP 4.7.1
  Text Domain: woocommerce-ameria-payment-gateway-pretty
  Domain Path: /languages
  Forum URI: #
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Session start, probably can be solved putting the plugin in init function
session_start();

// Initiate the plugin after plugins loaded
add_action( 'plugins_loaded', 'wc_ameria_payment_gateway_pretty_init', 11 );

function wc_ameria_payment_gateway_pretty_init() {

    class WC_Ameria_Payment_Gateway_Pretty extends WC_Payment_Gateway {

        public function __construct() {
		
			

          // Descriptive parameters for gateway
          $this->id = 'WC_Ameria_Payment_Gateway_Pretty';
          $this->has_fields = false;
          
          
		  
          $this->method_title = 'Ameriabank Payment Gateway';
		
          $this->method_description = "Payment via Ameriabank third party payment system.";
          $this->notify_url = str_replace( 'https:', 'http:', home_url( '/wc-api/'. $this->id )  );
          
          // Initalize form fields and settings
          $this->init_form_fields();
          $this->init_settings();
		  
		  $this->title = $this->settings['title'];
		  $this->description = $this->settings['description'];
		  
		  
		  $this->order_button_text = __($this->settings['buttontext'], 'wc_ameria_payment_gateway_pretty' );
		  
          // Hook into gateway action, clears buffer and return -1 and exits, prevented by redirect to thank you page
          add_action( 'woocommerce_api_wc_ameria_payment_gateway_pretty', array( $this, 'wapgp_response' ) );		  


          // Get testmode. Getting exactly here to avoid default empty issue.
          $this->testmode = ($this->get_option('testmode')) ? 'test' : '';

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

          // Unset the session sensitive information to avoid bugs on later use
          unset($_SESSION['order_id']);
          unset($_SESSION['order_description']);
          unset($_SESSION['cart_total']);

          try{

                    $options = array( 
                      'soap_version'    => SOAP_1_1, 
                      'exceptions'      => true, 
                      'trace'           => 1, 
                      'wdsl_local_copy' => true
                    );

                    $client = new SoapClient("https://" . $this->testmode . "payments.ameriabank.am/webservice/PaymentService.svc?wsdl", $options);

                     // Set parameters for Ameriabank
                    $parms['paymentfields']['ClientID'] = $this->get_option('client_id'); // clientID from Ameriabank
                    $parms['paymentfields']['Description'] = $order_description;
                    $parms['paymentfields'] ['OrderID']= $_POST['orderID'];
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
                           //echo   '<iframe id="idIframe" src="https://testpayments.ameriabank.am/forms/frm_checkprint.aspx?lang=am&paymentid='.$_POST['paymentid'].'" width="560px" height="820px" frameborder="0"></iframe>';
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

          // End Send Receive Code //
          

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

                'testmode' => array(
                    'title'       => __( 'Test mode', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'checkbox',
                    'label'   => __( 'If enabled you can test', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                    'default' => 'yes'
                ),                 

                'title' => array(
                    'title'       => __( 'Title', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => __( 'Ameriabank Payment', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),
				
                'buttontext' => array(
                    'title'       => __( 'Button Text', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the button, during checkout.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => __( 'Pay with visa, mastercard, arca', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),				

                'description' => array(
                    'title'       => __( 'Description', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => __( 'Thank you for using our website.', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),
                'ameria_order_id' => array(
                    'title'       => __( 'Starting Order Id', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'text',
                    'description' => __( 'Starting Order Id must be unique in every single order. And increment after every order.', 'wc_ameria_payment_gateway_pretty' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),

                'client_id' => array(
                    'title'       => __( 'Client ID', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'text',
                    'description' => __( 'This is clinet ID setting for using Ameriabank vpos service.', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),                

                'username' => array(
                    'title'       => __( 'Username', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'text',
                    'description' => __( 'This is username setting for using Ameriabank vpos service.', 'wc_ameria_payment_gateway_pretty' ),
                    'desc_tip'    => true,
                ),
                'password' => array(
                    'title'       => __( 'Password', 'wc_ameria_payment_gateway_pretty' ),
                    'type'        => 'password',
                    'description' => __( 'This is password setting for using Ameriabank vpos service.', 'wc_ameria_payment_gateway_pretty' ),
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
              // var_dump($this->testmode);
              // var_dump($this->get_option('client_id'));
              // var_dump($this->get_option('username'));
              // var_dump($this->get_option('password'));
              // var_dump($this->get_option('description'));
              // var_dump($this->get_option('ameria_order_id'));
              //   die;
              try{

                  $options = array( 
                    'soap_version'    => SOAP_1_1, 
                    'exceptions'      => true, 
                    'trace'           => 1, 
                    'wdsl_local_copy' => true
                  );

                  $client = new SoapClient("https://" . $this->testmode . "payments.ameriabank.am/webservice/PaymentService.svc?wsdl", $options);


                  // Get last insert id
                  $last_insert_id = $this->get_option('ameria_order_id'); //374012; //Must be an integer type

					// Change an option of ameria order_id, increment to have unique one
					$opt_array = get_option('woocommerce_' . $this->id . '_settings');
					$opt_array['ameria_order_id'] += 1;
					update_option($this->get_option_key(),$opt_array);

                  // Get order total
                  $this->paymentAmount = $order->get_total();
				  
			  
                  
                  // Save different information in Session for later testing
                  $_SESSION['cart_total'] = $this->paymentAmount;
                  $_SESSION['order_description'] = $order_description = $this->get_option('description');
                  $_SESSION['order_id'] = (int)$order_id;

                  // Set parameters for ameriabank request
                  $parms['paymentfields']['ClientID'] = $this->get_option('client_id'); // clientID from Ameriabank
                  $parms['paymentfields']['Description'] = $order_description;
                  $parms['paymentfields'] ['OrderID']= $last_insert_id+1;// orderID wich must be unique for every transaction;
                  $parms['paymentfields'] ['Username']= $this->get_option('username'); // username from Ameriabank
                  $parms['paymentfields'] ['Password']= $this->get_option('password'); // password from Ameriabank

                  $parms['paymentfields'] ['PaymentAmount'] = $this->paymentAmount; // payment amount of your Order
                  $parms['paymentfields'] ['backURL']= $this->notify_url;  // your backurl after transaction rediracted to this url

                  // out($parms); die;

                  // Call web service PassMember methord and print response
                  $webService = $client->GetPaymentID($parms);

                  if($webService->GetPaymentIDResult->Respcode == '1' && $webService->GetPaymentIDResult->Respmessage =='OK')
                  {           

                    // Mark as on-hold (we're awaiting the payment)
                    $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );

                    //rediract to Ameriabank server or you can use iFrame to show on your page
                    
                    $ameriaRedirectUri = "https://" . $this->testmode . "payments.ameriabank.am/forms/frm_paymentstype.aspx?clientid=" . $this->get_option('client_id') . "&clienturl={$this->notify_url}&lang=am&paymentid={$webService->GetPaymentIDResult->PaymentID}";
                  
                    // Return success and third party(ameriabank) redirect uri
                    return array(
                        'result'    => 'success',
                        'redirect'  => $ameriaRedirectUri
                    );                      

                  }
                  else
                  {
                    wc_add_notice( __('Error processing checkout. Please contact administrator.','payment'), 'error' );
                    // Add note to the order for your reference
                    //$customer_order->add_order_note( __('Error processing checkout. Please contact administrator.','payment') );

                    // Errors for developer
                    // echo($webService->GetPaymentIDResult->Respcode." ");
                    // echo($webService->GetPaymentIDResult->Respmessage." ");
                    // echo($webService->GetPaymentIDResult->PaymentID." ");
                    //   echo 'Error';
                  }

                } catch (Exception $e) {

                     wc_add_notice( __('Error processing checkout. Please contact administrator.','payment'), 'error' );
                    // Add note to the order for your reference
                    //$customer_order->add_order_note( __('Error processing checkout. Please contact administrator.','payment') );

                }           
        }


    } // end \WC_Ameria_Payment_Gateway_Pretty class
}

function wc_ameria_payment_gateway_pretty_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Ameria_Payment_Gateway_Pretty';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_ameria_payment_gateway_pretty_add_to_gateways' );


