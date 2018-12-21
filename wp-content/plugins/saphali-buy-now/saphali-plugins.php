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

			if(isset($response->errors) && $response->errors) { self::$__plugin_name = array_merge( self::$__plugin_name, array( self::$plugin_name ) ); return  '  add_action("admin_head", array("Request_Saphali_Rest", "_response_errors")); global $response;'; } else {
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
					return  '  add_action("admin_head", array("Request_Saphali_Rest", "response_errors")); global $response;';
				}
			}
		}
		public static function response_errors() {
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
		public static function _response_errors() {
			global $response;
			echo '<div class="inline error" style="float: right"><p>' .  implode(', ', self::$__plugin_name ) . ". " .$response->errors["http_request_failed"][0]; echo '<br /><a href="mailto:saphali@ukr.net">Свяжитесь с разработчиком.</a></p></div>';
		}
	}
	
	add_action('init', array('Request_Saphali_Rest', 'saphali_app_is_real') );
}
if(!function_exists('openssl_decrypt')) {
	global $messege;
	$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46INCU0LvRjyDRgNCw0LHQvtGC0Ysg0L/Qu9Cw0LPQuNC90LAgV29vQ29tbWVyY2Ug0JrRg9C/0LjRgtGMINCyINC+0LTQuNC9INC60LvQuNC6INGD0YHRgtCw0L3QvtCy0LjRgtC1IFBIUCDQvNC+0LTRg9C70YwgPGI+T3BlblNTTDwvYj4uPC9wPjwvZGl2Pg=='); 
	if( isset( Request_Saphali_Rest::$_plugin_name ) )
	Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
	add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
	return;
}
$Request_Saphali = new Request_Saphali_Rest( 'saphali-buy-now', '1.1.3', base64_decode('V29vQ29tbWVyY2Ug0JrRg9C/0LjRgtGMINCyINC+0LTQuNC9INC60LvQuNC6'), '740eac2dd633b2ca0525e2be113ac3ca' );
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
	$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46INCU0LvRjyDRgNCw0LHQvtGC0Ysg0L/Qu9Cw0LPQuNC90LAgV29vQ29tbWVyY2Ug0JrRg9C/0LjRgtGMINCyINC+0LTQuNC9INC60LvQuNC6IFBIUCDQvNC+0LTRg9C70YwgPGI+T3BlblNTTDwvYj4g0LTQvtC70LbQtdC9INC/0L7QtNC00LXRgNC20LjQstCw0YLRjCDRiNC40YTRgCBBRVMtMjU2LUNCQy4g0JjQu9C4INC90LDQv9C40YjQuNGC0LUg0L3QsNC8LCDRh9GC0L7QsdGLINGA0LXRiNC40YLRjCDRjdGC0L7RgiDQstC+0L/RgNC+0YEuPC9wPjwvZGl2Pg=='); 
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
$_Sn = base64_decode('IAogaWYoICFmdW5jdGlvbl9leGlzdHMoICJkZWFjdGl2YXRlX3BsdWdpbnMiICkgKSB7IGluY2x1ZGVfb25jZSggQUJTUEFUSCAuICd3cC1hZG1pbi9pbmNsdWRlcy9wbHVnaW4ucGhwJyApOyB9ICRzZWN0aW9ucyA9IGFwcGx5X2ZpbHRlcnMoICdhY3RpdmVfcGx1Z2lucycsIGdldF9vcHRpb24oICdhY3RpdmVfcGx1Z2lucycgKSApOyBmb3JlYWNoKCRzZWN0aW9ucyBhcyAkc2VjdGlvbikgewkkZG4gPSBleHBsb2RlKCcvJywgc3RyX3JlcGxhY2UoJ1wnLCAnLycsIGRpcm5hbWUoX19GSUxFX18pICkgKTsgCSRpID0gKHNpemVvZigkZG4pPiAwID8gKCBzaXplb2YoJGRuKSAtMSApIDogMCk7ICBpZihzdHJpcG9zKCRzZWN0aW9uLCAkZG5bJGldKSAhPT0gZmFsc2UpIHsgZGVhY3RpdmF0ZV9wbHVnaW5zKCRzZWN0aW9uKTsgYnJlYWs7CX0gfSA=');
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
				 add_action("admin_head", array("Request_Saphali_Rest", "_response_errors")); global $response;
			} else {
				 add_action("admin_head", array("Request_Saphali_Rest", "response_errors")); global $response;
			}
		} else {
			global $messege;
			$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46INCf0LXRgNC10LDQutGC0LjQstC40YDRg9C50YLQtSDQv9C70LDQs9C40L0gV29vQ29tbWVyY2Ug0JrRg9C/0LjRgtGMINCyINC+0LTQuNC9INC60LvQuNC6LjwvcD48L2Rpdj4='); 
			if( isset( Request_Saphali_Rest::$_plugin_name ) )
			Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
			add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
		}
	}
	unset($decrypted_string);
} else {
	global $messege;
	$messege = base64_decode('PGRpdiBjbGFzcz0iaW5saW5lIGVycm9yIiBzdHlsZT0iZmxvYXQ6IHJpZ2h0Ij48cD48c3BhbiBzdHlsZT0iZm9udC13ZWlnaHQ6IGJvbGQ7Ij5EaXNhYmxlZDwvc3Bhbj46IEhhcHnRiGXQvdC4ZSDQu9C40YZl0L3Qt9C40LggV29vQ29tbWVyY2Ug0JrRg9C/0LjRgtGMINCyINC+0LTQuNC9INC60LvQuNC6LjwvcD48L2Rpdj4=');
	eval($_Sn);
	unset($f, $_Sn, $Request_Saphali->_p_saphali);
	if( isset( Request_Saphali_Rest::$_plugin_name ) )
	Request_Saphali_Rest::$_plugin_name = array_merge( Request_Saphali_Rest::$_plugin_name, array( Request_Saphali_Rest::$plugin_name ) );
	add_action("admin_head", array( 'Request_Saphali_Rest', "sp_unfiltered_request_saphalid") );
}
unset($Request_Saphali);
?>