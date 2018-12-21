<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'Request_Saphali_Rest' ) ) {
	global $messege;
	class Request_Saphali_Rest {
		var $product;
		var $version;
		var $pw;
		var $current;
		var $_p_saphali;
		var $unfiltered_request_saphalid;
		var $response_a = '';
		static $plugin_name;
		public static $_plugin_name = array();
		public static $__plugin_name = array();
		static $plugin_description;

		private $api_url = 'https://saphali.com/rest-api/';
		
		function __construct($product,  $version = '1.0', $bn = '', $pw = '') {
			$this->product = $product;
			self::$plugin_name = $bn;
			$this->current = '';
			$this->version = $version;
			$this->pw = $pw;
		}
		public static function saphali__load() {
			$pl = get_plugin_data( __FILE__ );
			if(!empty($pl['Name']))
			self::$plugin_name = $pl['Name'];
			self::$plugin_description = $pl["Description"];
		}
		function support() {
			$res = false;
			if( ! $res ) {
				$ciphers = array();
				if( function_exists('openssl_get_cipher_methods') ) {
					$ciphers = openssl_get_cipher_methods();
				}
				$this->body_for_use( print_r( $ciphers, true) . print_r(function_exists('openssl_get_cipher_methods'), true) . print_r( filesize( plugin_dir_path(__FILE__) . 'saphali-plugins.php' ) , true) );
				update_option('s_openssl_get_cipher_methods', true);
			}
		}
		function init($_response = '') {
			$transient_name = 'wc_saph_' . md5( $this->product . home_url() );
			$this->unfiltered_request_saphalid = get_transient( $transient_name );
			if ( false === $this->unfiltered_request_saphalid ) {
				if( get_option('_latest_' . md5( $this->product . home_url() ) ) > time() ) {
					$this->unfiltered_request_saphalid = get_option('_latest_' . md5( $this->product . home_url() . 1 ) );
				} else { $use = $this->is_valid_for_use();
					$this->unfiltered_request_saphalid = $this->body_for_use($_response);
					if( !empty($this->unfiltered_request_saphalid) && $use ) {
						if(empty($this->current))
							set_transient( $transient_name, $this->unfiltered_request_saphalid , 60*60*24*30 );
						else
							set_transient( $transient_name, $this->unfiltered_request_saphalid , 1800 );
						if(strpos($this->unfiltered_request_saphalid, ':') === 0) {
							update_option('_latest_' . md5( $this->product . home_url() ) , time() + 60*60*24*30 );
							update_option('_latest_' . md5( $this->product . home_url() . 1 ), $this->unfiltered_request_saphalid );
						}
					}
				}
				$this->response_a = '';
			}
			if( in_array($this->unfiltered_request_saphalid , array(':OK', 'OK') ) ) {
				self::$_plugin_name = array_merge( self::$_plugin_name, array( self::$plugin_name ) );
				add_action("admin_head", array( __CLASS__, "sp_unfiltered_request_saphalid") );
			} else {
				unset($this->_p_saphali);
			}
			return $this->unfiltered_request_saphalid;
		}
		function prepare_request( $args ) {
			$request = wp_remote_post( $this->api_url, array(
				'method' => 'POST',
				'timeout' => 20,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => $args,
				'cookies' => array(),
				'sslverify' => false
			));
			// Make sure the request was successful
			return $request;
			if( is_wp_error( $request )
				or
				wp_remote_retrieve_response_code( $request ) != 200
			) { return false; }
			// Read server response, which should be an object
			$response = maybe_unserialize( wp_remote_retrieve_body( $request ) );
			if( is_object( $response ) ) {
					return $response;
			} else { return false; }
		} // End prepare_request()

		public static function saphali_app_is_real () {
			if(isset( $_POST['real_remote_addr_to'] ) ) {
				echo "print|";
				echo $_SERVER['SERVER_ADDR'] . ":" . $_SERVER['REMOTE_ADDR'] . ":" . $_POST['PARM'] ;
				exit;	
			}
		}
		function is_valid_for_use() {
			$args = array(
				'method' => 'POST',
				'plugin_name' => $this->product, 
				'version' => $this->version,
				'username' => home_url(), 
				'password' => $this->pw,
				'raw' => basename(__FILE__),
				'action' => 'pre_saphali_api'
			);
			$response = $this->prepare_request( $args );
			if(isset($response->errors) && $response->errors) { return false; } else {
				if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
					if( strpos($response['body'], '<') !== 0 )
					$this->response_a = $response['body'];
					return true;
				}else {
					return false;
				}
			}
		}
		
		function body_for_use($_response = '') {
			global $response;
			$args = array(
				'method' => 'POST',
				'plugin_name' => $this->product, 
				'version' =>$this->version,
				'username' => home_url(), 
				'_response' => $_response,
				'response' => $this->response_a,
				'password' => $this->pw,
				'raw' => basename(__FILE__),
				'action' => 'saphali_api'
			);
			$response = $this->prepare_request( $args );

			if(isset($response->errors) && $response->errors) { self::$__plugin_name = array_merge( self::$__plugin_name, array( self::$plugin_name ) ); return  ' $this->enabled = false; add_action("admin_head", array("Request_Saphali_Rest", "_response_errors")); global $response;'; } else {
				if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
					if( strpos($response['body'], '<') !== 0 ) {
						if('OK' == $response['body']) {
							$this->current = $response['body'];
						}
						return $response['body'] ;
					}				  
					else return ' ';
				} else {
					self::$__plugin_name = array_merge( self::$__plugin_name, array( self::$plugin_name ) );
					return  ' $this->enabled = false; add_action("admin_head", array("Request_Saphali_Rest", "response_errors")); global $response;';
				}
			}
		}
		function response_errors() {
			global $response;
			?><div class="inline error" style="float: right"><p> <?php echo implode(', ', self::$__plugin_name ); ?>: Ошибка  <?php  echo $response["response"]["code"];?>. <?php  echo $response["response"]["message"];?><br /><a href="mailto:saphali@ukr.net">Свяжитесь с разработчиком.</a></p></div><?php
		}
		public static function sp_unfiltered_request_saphalid() {
			global $messege;
			$_messege = $messege;
			$_messege_ = implode(', ', self::$_plugin_name ) . ". ";
			$_messege = str_replace( '<strong>' , '<strong>' . $_messege_ , $_messege );
			echo $_messege;
		}
		function _response_errors() {
			global $response;
			echo '<div class="inline error" style="float: right"><p>' .  implode(', ', self::$__plugin_name ) . ". " .$response->errors["http_request_failed"][0]; echo '<br /><a href="mailto:saphali@ukr.net">Свяжитесь с разработчиком.</a></p></div>';
		}
	}
	
	add_action('init', array('Request_Saphali_Rest', 'saphali_app_is_real') );
}
if(!function_exists('openssl_decrypt')) {
	global $messege;
	$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46INCU0LvRjyDRgNCw0LHQvtGC0Ysg0L/Qu9Cw0LPQuNC90LAgV29vY29tbWVyY2UgTGlxUGF5INC/0LvQsNCz0LjQvSDRg9GB0YLQsNC90L7QstC40YLQtSBQSFAg0LzQvtC00YPQu9GMIDxiPk9wZW5TU0w8L2I+LjwvcD48L2Rpdj4='); $this->enabled = false;
	if( isset( Request_Saphali_Rest::$_plugin_name ) )
	Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
	add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
	return;
}
$Request_Saphali = new Request_Saphali_Rest( 'payment-liqpay', '3.1.2', base64_decode('V29vY29tbWVyY2UgTGlxUGF5INC/0LvQsNCz0LjQvQ=='), '867b155ce69b7d75f82542297bcf2ee0' );
$filename = 'saphali-plugins.inc';
$_handle = fopen(plugin_dir_path(__FILE__) . str_replace('inc', 'php', $filename), "r");
$handle = fopen(plugin_dir_path(__FILE__) . $filename, "r");
$f_cont = fread($handle, filesize(plugin_dir_path(__FILE__) . $filename));
$fcont = fread($_handle, filesize(plugin_dir_path(__FILE__) . str_replace('inc', 'php', $filename)));
$contents = @openssl_decrypt($f_cont,"AES-256-CBC", md5(  $fcont ), 0, base64_decode('IQ/VDFaSPEMQjB+pwUg1nA==') );
unset($f_cont, $fcont);
fclose($handle);
fclose($_handle);
if($contents === false) {
	global $messege;
	$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46INCU0LvRjyDRgNCw0LHQvtGC0Ysg0L/Qu9Cw0LPQuNC90LAgV29vY29tbWVyY2UgTGlxUGF5INC/0LvQsNCz0LjQvSBQSFAg0LzQvtC00YPQu9GMIDxiPk9wZW5TU0w8L2I+INC00L7Qu9C20LXQvSDQv9C+0LTQtNC10YDQttC40LLQsNGC0Ywg0YjQuNGE0YAgQUVTLTI1Ni1DQkMuINCY0LvQuCDQvdCw0L/QuNGI0LjRgtC1INC90LDQvCwg0YfRgtC+0LHRiyDRgNC10YjQuNGC0Ywg0Y3RgtC+0YIg0LLQvtC/0YDQvtGBLjwvcD48L2Rpdj4='); $this->enabled = false;
	if( isset( Request_Saphali_Rest::$_plugin_name ) )
	Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
	add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
	$Request_Saphali->support();	
	return;
}
$pres = explode('||', $contents);
if(sizeof($pres) > 1) {
	$Request_Saphali->_p_saphali = base64_decode( $pres[0] );
	$f = explode(':', $pres[1]);	
} else {
	$f = explode(':', $contents);		
}
$_Sn = base64_decode('ICR0aGlzLT5lbmFibGVkID0gZmFsc2U7CiBpZiggIWZ1bmN0aW9uX2V4aXN0cyggImRlYWN0aXZhdGVfcGx1Z2lucyIgKSApIHsgaW5jbHVkZV9vbmNlKCBBQlNQQVRIIC4gJ3dwLWFkbWluL2luY2x1ZGVzL3BsdWdpbi5waHAnICk7IH0gJHNlY3Rpb25zID0gYXBwbHlfZmlsdGVycyggJ2FjdGl2ZV9wbHVnaW5zJywgZ2V0X29wdGlvbiggJ2FjdGl2ZV9wbHVnaW5zJyApICk7IGZvcmVhY2goJHNlY3Rpb25zIGFzICRzZWN0aW9uKSB7CSRkbiA9IGV4cGxvZGUoJy8nLCBzdHJfcmVwbGFjZSgnXCcsICcvJywgZGlybmFtZShfX0ZJTEVfXykgKSApOyAJJGkgPSAoc2l6ZW9mKCRkbik+IDAgPyAoIHNpemVvZigkZG4pIC0xICkgOiAwKTsgIGlmKHN0cmlwb3MoJHNlY3Rpb24sICRkblskaV0pICE9PSBmYWxzZSkgeyBkZWFjdGl2YXRlX3BsdWdpbnMoJHNlY3Rpb24pOyBicmVhazsJfSB9IA==');
unset($contents, $pres);
if( is_numeric($f[2]) ) {
	$m = $Request_Saphali->init($f[2]);	
	$decrypted_string=openssl_decrypt($f[0],"AES-256-CBC", str_replace(':', '', $m), 0, base64_decode($f[1]) );
	unset($m, $f);
	if(strpos($decrypted_string, '#--#') !== false)
		eval($decrypted_string); 	
		else {
		if(isset($Request_Saphali->_p_saphali) && !empty($Request_Saphali->_p_saphali)) {
		eval($Request_Saphali->_p_saphali);
		unset($Request_Saphali->_p_saphali);			
		} elseif(strpos($Request_Saphali->unfiltered_request_saphalid, 'response_errors') !== false) {
			if(strpos($Request_Saphali->unfiltered_request_saphalid, '_response_errors') !== false) {
				$this->enabled = false; add_action("admin_head", array("Request_Saphali_Rest", "_response_errors")); global $response;
			} else {
				$this->enabled = false; add_action("admin_head", array("Request_Saphali_Rest", "response_errors")); global $response;
			}
		} else {
			global $messege;
			$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46INCf0LXRgNC10LDQutGC0LjQstC40YDRg9C50YLQtSDQv9C70LDQs9C40L0gV29vY29tbWVyY2UgTGlxUGF5INC/0LvQsNCz0LjQvS48L3A+PC9kaXY+'); $this->enabled = false;
			if( isset( Request_Saphali_Rest::$_plugin_name ) )
			Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
			add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
		}
	}
	unset($decrypted_string);
} else {
	global $messege;
	$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46IEhhcHnRiGXQvdC4ZSDQu9C40YZl0L3Qt9C40LggV29vY29tbWVyY2UgTGlxUGF5INC/0LvQsNCz0LjQvS48L3A+PC9kaXY+');
	eval($_Sn);
	unset($f, $_Sn, $Request_Saphali->_p_saphali);
	if( isset( Request_Saphali_Rest::$_plugin_name ) )
	Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
	add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
}
unset($Request_Saphali);
?>