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

session_start();

add_action( 'plugins_loaded', 'wc_ameria_payment_gateway_pretty_init', 11 );

function wc_ameria_payment_gateway_pretty_init() {

    class WC_Ameria_Payment_Gateway_Pretty extends WC_Payment_Gateway {

        public function __construct() {
          $this->id = 'WC_Ameria_Payment_Gateway_Pretty';
          $this->has_fields = false;
          $this->title = 'Ameria Payment Gateway';
          $this->method_title = 'Ameria Payment Gateway';
          $this->method_description = "Ameria Payment Gateway Description";
          $this->notify_url = str_replace( 'https:', 'http:', home_url( '/wc-api/WC_Ameria_Payment_Gateway_Pretty' )  );

          // var_dump($this->notify_url); die;
          add_action( 'woocommerce_api_wc_ameria_payment_gateway_pretty', array( $this, 'wapgp_response' ) );


          $this->init_form_fields();
          $this->init_settings();
        }

        public function wapgp_response ($param) {
          // echo '<pre>';
          // print_r($_POST);
          // print_r($_SESSION); die;

          try{

                    $options = array( 
                      'soap_version'    => SOAP_1_1, 
                      'exceptions'      => true, 
                      'trace'           => 1, 
                      'wdsl_local_copy' => true
                    );

                    $client = new SoapClient("https://testpayments.ameriabank.am/webservice/PaymentService.svc?wsdl", $options);

                     // Set parameters

                    $parms['paymentfields']['ClientID'] = '5EB8D352-C999-4851-AC4A-E676BD588E33'; // clientID from Ameriabank
                    $parms['paymentfields']['Description'] = $_SESSION['order_description'];
                    $parms['paymentfields'] ['OrderID']= $_POST['orderID'];
                    $parms['paymentfields'] ['Password']= "lazY2k"; // password from Ameriabank
                    $parms['paymentfields'] ['PaymentAmount']= (int)$_SESSION['cart_total']; // payment amount of your Order
                    $parms['paymentfields'] ['Username']= "3d19541048"; // username from Ameriabank

                    // Call web service PassMember methord and print response

                    $webService = $client->GetPaymentFields($parms);

                    // echo($webService->GetPaymentFieldsResult->amount." ");
                    // echo($webService->GetPaymentFieldsResult->respcode." ");
                    // echo($webService->GetPaymentFieldsResult ->cardnumber." ");
                    // echo($webService->GetPaymentFieldsResult ->paymenttype." ");
                    // echo($webService->GetPaymentFieldsResult ->authcode." ");
                      //
                      
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
                         else
                         {
                          // Error
                          // Rediract to Exception Page
                          echo "<script type='text/javascript'>\n";
                          echo "window.location.replace(document.getElementsByTagName('base')[0].href+"."'".$langs_id."'"."+'/error.html');";
                          echo "</script>";
                         }
                       }
                       else
                       {
                          $continue = true;


                        // you can print your check or call Ameriabank check example
                        // echo   '<iframe id="idIframe" src="https://testpayments.ameriabank.am/forms/frm_checkprint.aspx?lang=am&paymentid='.$_POST['paymentid'].'" width="560px" height="820px" frameborder="0"></iframe>';

                       }

                        if($continue) {

                          $order = wc_get_order( $_SESSION['order_id'] );
                          $order->payment_complete();
                          
                          $thankyou = $this->get_return_url( $order ); 

                          echo $thankyou; die;
                        } else {
                          //error
                        }

                    }
                    else
                    {
                        // error page
        

                    }
           } catch (Exception $e) {

                 echo 'Caught exception:',  $e->getMessage(), "\n";

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



        public function process_payment($order_id) {
          // Get order info by order_id         
          // 

            $order = wc_get_order( $order_id );
              try{

                  $options = array( 
                    'soap_version'    => SOAP_1_1, 
                    'exceptions'      => true, 
                    'trace'           => 1, 
                    'wdsl_local_copy' => true
                  );

                  $client = new SoapClient("https://testpayments.ameriabank.am/webservice/PaymentService.svc?wsdl", $options);

                  $last_insert_id = 374010; //Must be an integer type

                  $this->paymentAmount = 1; //$order->get_total();
                  $_SESSION['cart_total'] = $this->paymentAmount;
                  $_SESSION['order_description'] = $order_description = 'Description';
                  $_SESSION['order_id'] = $order_id;
                  // Set parameters
                  $parms['paymentfields']['ClientID'] = '5EB8D352-C999-4851-AC4A-E676BD588E33'; // clientID from Ameriabank
                  $parms['paymentfields']['Description'] = $order_description;
                  $parms['paymentfields'] ['OrderID']= $last_insert_id;// orderID wich must be unique for every transaction;
                  $parms['paymentfields'] ['Password']= "lazY2k"; // password from Ameriabank

                  $parms['paymentfields'] ['PaymentAmount']= 1; // payment amount of your Order
                  $parms['paymentfields'] ['Username']= "3d19541048"; // username from Ameriabank
                  $parms['paymentfields'] ['backURL']= $this->notify_url;  // your backurl after transaction rediracted to this url

                  // Call web service PassMember methord and print response

                  $webService = $client-> GetPaymentID($parms);

                  if($webService->GetPaymentIDResult->Respcode == '1' && $webService->GetPaymentIDResult->Respmessage =='OK')
                  {           

                    // Mark as on-hold (we're awaiting the payment)
                    $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );
                            
                    // Reduce stock levels
                    $order->reduce_order_stock();
                            
                    // Remove cart
                    WC()->cart->empty_cart();

                    //rediract to Ameriabank server or you can use iFrame to show on your page
                    
                    $ameriaRedirectUri = "https://testpayments.ameriabank.am/forms/frm_paymentstype.aspx?clientid=5EB8D352-C999-4851-AC4A-E676BD588E33&clienturl={$this->notify_url}&lang=am&paymentid={$webService->GetPaymentIDResult->PaymentID}";
                  

                    return array(
                        'result'    => 'success',
                        'redirect'  => $ameriaRedirectUri
                    );                      

                  }
                  else
                  {
                    wc_add_notice( __('Error processing checkout. Please contact administrator.','payment'), 'error' );
                    // Add note to the order for your reference
                    $customer_order->add_order_note( __('Error processing checkout. Please contact administrator.','payment') );

                    // Errors for developer
                    // echo($webService->GetPaymentIDResult->Respcode." ");
                    // echo($webService->GetPaymentIDResult->Respmessage." ");
                    // echo($webService->GetPaymentIDResult->PaymentID." ");
                    //   echo 'Error';
                  }

                } catch (Exception $e) {

                     wc_add_notice( __('Error processing checkout. Please contact administrator.','payment'), 'error' );
                    // Add note to the order for your reference
                    $customer_order->add_order_note( __('Error processing checkout. Please contact administrator.','payment') );

                }           
        }

        /**
       * Process the payment and return the result
       **/
        public function process_paymentasdfadf( $order_id ) {
            
            $order = wc_get_order( $order_id );
                    
            // Mark as on-hold (we're awaiting the payment)
            // $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );
                    
            // Reduce stock levels
            // $order->reduce_order_stock();
                    
            // Remove cart
            //WC()->cart->empty_cart();
                    
            // Return thankyou redirect
            // $order = new WC_Order($order_id);

            // echo '<pre>';
            $backUrl = $this->notify_url; 


            var_dump($this->notify_url); die;

            // return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));             
             
            // return array(
            //     'result'    => 'success',
            //     'redirect'  => $this->get_return_url( $order )
            // );

            return array(
                'result'    => 'success',
                'redirect'  => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))
            );            
        }



    } // end \WC_Ameria_Payment_Gateway_Pretty class
}

function wc_ameria_payment_gateway_pretty_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Ameria_Payment_Gateway_Pretty';
    return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_ameria_payment_gateway_pretty_add_to_gateways' );