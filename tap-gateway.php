<?php
/*
 * Plugin Name: WooCommerce Tap Payment Gateway
 * Plugin URI: https://tap.com/woocommerce/payment-gateway-plugin.html
 * Description: Take credit card payments on your store.
 * Author: Waqas Zeeshan
 * Author URI: http://tap.company
 * Version: 1.0.1


 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'tap_add_gateway_class' );
function tap_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Tap_Gateway'; // your class name is here
	return $gateways;
}


add_action( 'plugins_loaded', 'tap_init_gateway_class' );
define('tap_imgdir', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');

function tap_init_gateway_class() {
	class WC_Tap_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {

			$this->id = 'tap'; // payment gateway plugin ID
			$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true; // in case you need a custom credit card form
			$this->method_title = 'Tap Gateway';
			$this->method_description = 'Description of Tap payment gateway'; // will be displayed on 		the options page
			$this->supports = array(
				'products',
				//'tokenization',
				'refunds'
			);
 
			// Method with all the options fields
			$this->init_form_fields();
 
			// Load the settings.
			$this->init_settings();
			$this->icon = tap_imgdir . 'logo.png';
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->testmode = 'yes' === $this->get_option( 'testmode' );
			$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
			$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );

			$this->payment_mode = $this->get_option('payment_mode');
			$this->save_card = $this->get_option('save_card');
			?>

			<?php
			// This action hook saves the settings
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ), 11);
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			
			add_action( 'woocommerce_receipt_tap', array( $this, 'tap_checkout_receipt_page' ) );
			add_action( 'woocommerce_thankyou_tap', array( $this, 'tap_thank_you_page' ) );

 		}

 		public function tap_thank_you_page($order_id) {
 			global $woocommerce;
 			$order 	= new WC_Order( $order_id );
 			if (!empty($_GET['tap_id']) && $this->payment_mode == 'charge') {
 				$curl = curl_init();
		 		curl_setopt_array($curl, array(
	  			CURLOPT_URL => "https://api.tap.company/v2/charges/".$_GET['tap_id'],
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => array(
					    "authorization: Bearer ".$this->private_key,
					    "content-type: application/json"
			  		),
				));
				$response = curl_exec($curl);
				$response = json_decode($response);
				if ($response->status == 'CAPTURED') {
					$order->update_status('processing');
					$order->payment_complete($_GET['tap_id']);
					add_post_meta( $order->id, '_transaction_id', $_GET['tap_id'], true );
					$order->add_order_note(sanitize_text_field($_GET['tap_id']));
					$order->payment_complete($_GET['tap_id']);
					$woocommerce->cart->empty_cart(); 
				}
				else {
					$order->update_status('pending');
				}
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
					echo "cURL Error #:" . $err;
				} else {
					echo $response->code;
				}
 			}
 			
 			if (!empty($_GET['tap_id']) && $this->payment_mode == 'authorize') {
 				$curl = curl_init();
		 		curl_setopt_array($curl, array(
	  			CURLOPT_URL => "https://api.tap.company/v2/authorize/".$_GET['tap_id'],
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => array(
					    "authorization: Bearer ".$this->private_key,
					    "content-type: application/json"
			  		),
				));
				$response = curl_exec($curl);
				$response = json_decode($response);
				if ($response->status == 'AUTHORIZED') {
					$order->update_status('pending');
					$order->add_post_meta( $order_id, '_transaction_id', $_GET['tap_id'], true );
					$order->add_order_note(sanitize_text_field($_GET['tap_id']));
					$woocommerce->cart->empty_cart(); 
				}
				else {
					$order->update_status('pending');
				}
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
					echo "cURL Error #:" . $err;
				} else {
					echo $response->code;
				}
 			}
 		}

 		public function tap_checkout_receipt_page($order_id) {
 			$order = wc_get_order( $order_id );
 			
 			echo '<div id="tap_root"></div>';
 			echo '<input type="hidden" id="tap_end_url" value="' . $this->get_return_url($order) . '" />';
 			echo '<input type="hidden" id="chg" value="' . $this->payment_mode . '" />';
 			echo '<input type="hidden" id="save_card" value="' . $this->save_card . '" />';
 			echo '<input type="hidden" id="publishable_key" value="' . $this->publishable_key . '" />';
 			echo '<input type="hidden" id="payment_mode" value="' . $this->payment_mode . '" />';
 			echo '<input type="hidden" id="amount" value="' . $order->total . '" />';
 			echo '<input type="hidden" id="currency" value="' . $order->currency . '" />';
 			echo '<input type="hidden" id="billing_first_name" value="' . $order->billing_first_name . '" />';
 			echo '<input type="hidden" id="billing_last_name" value="' . $order->billing_last_name . '" />';
 			echo '<input type="hidden" id="billing_email" value="' . $order->billing_email . '" />';
 			echo '<input type="hidden" id="billing_phone" value="' . $order->billing_phone . '" />';
 			echo '<input type="hidden" id="customer_user_id" value="' . get_current_user_id() . '" />';
 			echo '<input type="button" value="Place order by Tap" onclick="goSell.openLightBox()" />';
 		}
 
 		public function init_form_fields(){
		$this->form_fields = array(
							'enabled' => array(
								'title'       => 'Enable/Disable',
								'label'       => 'Enable Tap Gateway',
								'type'        => 'checkbox',
								'description' => '',
								'default'     => 'no'
							),
							'title' => array(
								'title'       => 'Title',
								'type'        => 'text',
								'description' => 'This controls the title which the user sees during checkout.',
								'default'     => 'Credit Card',
								'desc_tip'    => true,
							),
							'description' => array(
								'title'       => 'Description',
								'type'        => 'textarea',
								'description' => 'This controls the description which the user sees during checkout.',
								'default'     => 'Pay with your credit card via Tap payment gateway. On clicking Pay by Tap button popup will load.' ,
							),
							'testmode' => array(
								'title'       => 'Test mode',
								'label'       => 'Enable Test Mode',
								'type'        => 'checkbox',
								'description' => 'Place the payment gateway in test mode using test API keys.',
								'default'     => 'yes',
								'desc_tip'    => true,
							),
							'test_publishable_key' => array(
								'title'       => 'Test Publishable Key',
								'type'        => 'text'
							),
							'test_private_key' => array(
								'title'       => 'Test Private Key',
								'type'        => 'password',
							),
							'publishable_key' => array(
								'title'       => 'Live Publishable Key',
								'type'        => 'text'
							),
							'private_key' => array(
								'title'       => 'Live Private Key',
								'type'        => 'password'
							),
							'payment_mode' => array(
		                    	'title'       => 'Payment Mode',
		                    	'type'        => 'select',
		                    	'class'       => 'wc-enhanced-select',
		                    	'default'     => '',
		                    	'desc_tip'    => true,
		                    	'options'     => array(
									'charge'       => 'Charge',
									'authorize'    => 'Authorize',
			 					)
                			),
                			'save_card' => array(
								'title'       => 'Save Cards',
								'label'       => 'Enable Tap Gateway',
								'type'        => 'checkbox',
								'description' => '',
								'default'     => 'no'
							),
			);
 
	 	}


	 			
	 	public function payment_scripts() {
	 		//wp_dequeue_style( 'woocommerce_frontend_styles' );
	 		wp_register_style( 'tap_payment',  plugins_url('tap-payment.css', __FILE__));
	 		wp_enqueue_style('tap_payment');
	 		wp_register_style( 'tap_style', '//goSellJSLib.b-cdn.net/v1.4/css/gosell.css' );
	 		wp_register_style( 'tap_icon', '//goSellJSLib.b-cdn.net/v1.4/imgs/tap-favicon.ico' );
			wp_enqueue_style('tap_style');
			wp_enqueue_style('tap_icon');
			wp_enqueue_script( 'tap_js', '//goSellJSLib.b-cdn.net/v1.4/js/gosell.js', array('jquery') );

			//wp_enqueue_style( 'tap_style', '//goSellJSLib.b-cdn.net/v1.3/css/gosell.css' );
			wp_register_script( 'woocommerce_tap', plugins_url( 'tap.js', __FILE__ ), 'gosell');
			wp_enqueue_style( 'tap-payment', plugins_url( 'tap-payment.css', __FILE__ ) );
			wp_enqueue_script( 'woocommerce_tap' );
			
	 	}





 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
			global $woocommerce;
			$customer_user_id = get_current_user_id();
			$currency = get_woocommerce_currency();
			$amount = $woocommerce->cart->total;
			$mode = $this->payment_mode;
 			if ( $this->description ) {
				if ( $this->testmode ) {
					$this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers mentioned in documentation';
					$this->description  = trim( $this->description );
				}
				echo wpautop( wp_kses_post( $this->description ) );
			}
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
			
			do_action( 'woocommerce_credit_card_form_start', $this->id );
			?>		

 			<div id="tap_root">
 			</div>
 			<?php
			do_action( 'woocommerce_credit_card_form_end', $this->id );
			echo '<div class="clear"></div></fieldset>';
		}

		public function process_payment( $order_id ) {
			global $woocommerce;
			$order 	= new WC_Order( $order_id );
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);

			//Success URL 
	 	}

	 	public function process_refund( $order_id, $amount = null, $reason ) {
	 		global $post, $woocommerce;
	 		$order = wc_get_order( $order_id );
	 		$args = array(
			'order_id' => $post->ID,
			);
			$transID = $order->get_transaction_id($args);
			var_dump($transID);exit;
			$notes = wc_get_order_notes($order_id);
			$transaction_id	 = $notes[0]->content;
	 		$currency = $order->currency;
	 		$refund_url = 'https://api.tap.company/v2/refunds';
	 		$refund_request['charge_id'] = $transaction_id;
	 		$refund_request['amount'] = $amount;
	 		$refund_request['currency'] = $currency;
	 		$refund_request['description'] = "Test Description";
	 		$refund_request['reason'] = $reason;
	 		$json_request = json_encode($refund_request);
	 		$curl = curl_init();
	 		curl_setopt_array($curl, array(
  			CURLOPT_URL => "https://api.tap.company/v2/refunds",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $json_request,
				CURLOPT_HTTPHEADER => array(
				    "authorization: Bearer ".$this->private_key,
				    "content-type: application/json"
			  	),
			));

			$response = curl_exec($curl);
			$response = json_decode($response);
			if ($response->id) {
				return true;
			}
			else {
				return false;
			}
			$err = curl_error($curl);
			curl_close($curl);

			if ($err) {
			  echo "cURL Error #:" . $err;
			} else {
			  echo $response;
			}
		}
 	}
}


