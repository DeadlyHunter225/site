<?php  
/*
Plugin Name: Saphali Buy Now
Plugin URI: http://saphali.com/saphali-woocommerce-plugin-wordpress
Description: Saphali Buy Now - быстрый заказ.
Подробнее на сайте <a href="http://saphali.com/saphali-woocommerce-plugin-wordpress">Saphali Woocommerce</a>

Version: 1.1.4
Author: Saphali
Author URI: http://saphali.com/
*/


/*

 Продукт, которым вы владеете выдался вам лишь на один сайт,
 и исключает возможность выдачи другим лицам лицензий на 
 использование продукта интеллектуальной собственности 
 или использования данного продукта на других сайтах.

*/

class saphali_buy_now {
	static $version = '1.1.4';
	static $plugin_url;
	static $plugin_path;
	var $unfiltered_request_saphalid;
	function __construct() {
		saphali_buy_now::$plugin_url = plugin_dir_url(__FILE__);
		saphali_buy_now::$plugin_path = plugin_dir_path(__FILE__);
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(__CLASS__, 'plugin_manage_link') , 10, 4 );
		}
		add_action('woocommerce_after_add_to_cart_button', array($this, 'woocommerce_after_add_to_cart_button'), 10 );
		$catalog_botton = get_option('_saphali_catalog_botton', '');
		if(!empty($catalog_botton)) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
			add_action( 'woocommerce_after_shop_loop_item', array(__CLASS__, 'woocommerce_catalog_add_to_cart_button'), 11 );
		}
		
		//add_action( 'woocommerce_after_single_variation', array( $this, 'variation_add_to_buy' ) );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'variation_add_to_buy' ) );
		add_action('wp_ajax_saphali_delete_order_buy_new', array($this,'saphali_delete_order_buy_new'));
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts_sing' ) );
		add_filter('woocommerce_available_variation', array($this,'_woocommerce_before_calculate_totals_s_logged_price'), 1, 3);
		load_plugin_textdomain( 'saphali-buy-now', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
		include_once( saphali_buy_now::$plugin_path . 'saphali-plugins.php');
	}
	
	function load_scripts() {
			global $woocommerce;
			$array_page = get_option('buy_now_array_page', array());
			if($array_page)
			$page = is_page( $array_page );
			else $page = false;
			$assets_path          = str_replace( array( 'http:', 'https:' ), '', $woocommerce->plugin_url() ) . '/assets/';
			if ( !( is_singular(array('product')) )  && ( is_tax(array("product_cat", "product_tag", "brands")) || is_post_type_archive('product') || is_home() || is_front_page() || $page )  ) {
				wp_enqueue_script( 'prettyPhotoh', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto.min.js', array( 'jquery' ), '3.1.5', true );
				wp_enqueue_style( 'woocommerce_prettyPhoto_cssh', $assets_path . 'css/prettyPhoto.css' );
			}
	}
	function saphali_delete_order_buy_new() {
		error_reporting(0);
		global $wpdb;
		if( isset($_GET["orders"]) ) {
			$orders = is_array( $_GET["orders"] ) ? '"' . implode('", "', $_GET["orders"]) . '"' : $_GET["orders"];
		} else {
			$orders = '"' . $_GET["order"] . '"';
		}
		$sql = " DELETE p FROM {$wpdb->prefix}saphali_log_order as p WHERE id IN ( $orders ) ";
		
		$query = $wpdb->query($sql);
		$result = ( $query ? true : false );
		echo json_encode( array('result' =>  $result, 'id' => explode( ',', str_replace(array( ' ', '"'), '', $orders) ) ) );
		exit;
	}
	public static function plugin_manage_link( $actions, $plugin_file, $plugin_data, $context ) {
		return array_merge( array( 'configure' => '<a href="' . admin_url( 'admin.php?page=woo-saphali-buy-now' ) . '">' . __( 'Settings' ) . '</a>' ), 
		$actions );
	}
	function load_scripts_sing() {
		$woocommerce_enable_lightbox =  get_option('woocommerce_enable_lightbox', 'no');
		
		if ( is_singular(array('product')) && ( $woocommerce_enable_lightbox == 'no' || empty($woocommerce_enable_lightbox) ) && (!class_exists('Avada')) ) {
			global $woocommerce;
			$assets_path          = str_replace( array( 'http:', 'https:' ), '', $woocommerce->plugin_url() ) . '/assets/';
			wp_enqueue_script( 'prettyPhotoh', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto.min.js', array( 'jquery' ), '3.1.5', true );
			wp_enqueue_style( 'woocommerce_prettyPhoto_cssh', $assets_path . 'css/prettyPhoto.css' );
		} elseif( is_singular(array('product')) &&  ( class_exists('Avada') || !wp_script_is( 'prettyPhoto', 'enqueued' ) ) ) {
			global $woocommerce;
			$assets_path          = str_replace( array( 'http:', 'https:' ), '', $woocommerce->plugin_url() ) . '/assets/';
			wp_enqueue_script( 'prettyPhotoh', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto.min.js', array( 'jquery' ), '3.1.5', true );
			wp_enqueue_style( 'woocommerce_prettyPhoto_cssh', $assets_path . 'css/prettyPhoto.css' );
		}
	}
	function woo_manege() {
		include_once (saphali_buy_now::$plugin_path . 'admin/load.php');
	}
	function frontend_scripts($hook_suffix) {
		wp_enqueue_style('farbtastic');
		wp_enqueue_script( 'farbtastic' );
	}
	function admin_menu() {
		
		if (function_exists('add_menu_page'))
		{
			$this->menu_id = add_submenu_page( 'woocommerce', 'Buy now', 'Buy now', 'manage_options', 'woo-saphali-buy-now', array($this,'woo_manege') );
			add_action( 'admin_print_scripts-' . $this->menu_id, array($this, 'frontend_scripts') ); 
		}
	}
	function woocommerce_after_add_to_cart_button() {
		global $product, $loop_saphali_buy_now;
		$saph_popup = wp_create_nonce("saph_popup");
		if(!is_object($product)) return;
		
		if(  $product->product_type != 'variable' && $product->is_in_stock() ) {
		?> <div class="single-buy-now"><span>&ndash;&nbsp;<?php  _e('or', 'saphali-buy-now'); ?>&nbsp;&ndash;&nbsp;</span><button class="button alt"  value="<?php  echo esc_attr( $product->id ); ?>" ><?php  _e('Buy Now', 'saphali-buy-now'); ?> </button></div> <style type="text/css"> /* .single-buy-now button.button.alt {float: none!important;}  */ .single-buy-now { float: left; padding: 10px 0 0 19px; } .single-buy-now span { float: left; } </style> <script type="text/javascript"> if(typeof is_load_ajax_url_popup == 'undefined') var is_load_ajax_url_popup = true;  function wc_getWidth() { 	xWidth = null; 	if(window.screen != null) xWidth = window.screen.availWidth;  	if(window.innerWidth != null) xWidth = window.innerWidth;  	if(document.body != null) xWidth = document.body.clientWidth;  	return xWidth; } var _xhr__; function _addErrMsg(where, r) { 	var elem_n_v = false; 	switch(where) { case 'name': var elem = r; break; case 'email': var elem = 'input[name="your_email"]'; break; case 'email_n_v': var elem = 'input[name="your_email"]'; elem_n_v = true; break; case 'phone': var elem = r; break; case 'subj': var elem = 'input[name="your_message"]'; break; case 'msg': var elem = 'textarea[name="your_message"]'; break;  	}  	if(where == 'success' || where == 'fail') { jQuery(r).prepend('<p id="output"><strong>' + (where == 'success' ? '<?php  _e('Сообщение отправлено!', 'saphali-buy-now');?>' : '<span class="err"><?php  _e('Отправить не удалось!', 'saphali-buy-now');?></span>') + '</strong></p>'); if(where == 'success') { 	jQuery(".set_question input[type='submit']").unbind('click'); 	jQuery(".set_question_ input[type='submit']").unbind('click'); 	if(typeof _gaq != "undefined"){ var buy_now_path =  location.pathname.replace(/[^\/]+$/i, 'buy_now_thanks.php'); _gaq.push(    ['_trackPageview', buy_now_path.replace(/[\/]$/i,    '/buy_now_thanks.php') ]); _gaq.push(['_trackEvent', 'Forms', 'AJAX Form BuyNow']); 	} else { var buy_now_path =  location.pathname.replace(/[^\/]+$/i, 'buy_now_thanks.php'); try { ga('send', 'pageview', {  'page':  buy_now_path.replace(/[\/]$/i, '/buy_now_thanks.php'),  'title': 'Покупка в 1 клик'});} catch(e) {} 	} 	setTimeout(function(){jQuery('.pp_close').click();}, 1000); } 	} else { if(where == 'capch') { jQuery(r).prepend('<span id="output"><strong>' +  '<?php  _e('Не пройдена проверка на бота! Не используйте, пожалуйста, автоматические средства заполнения форм.', 'saphali-buy-now');?>'+ '</strong></span>' ); } if(elem_n_v) { jQuery('.saph_field ' + elem).before('<span class="error"><?php  _e('Укажите корректный E-mail!', 'saphali-buy-now');?></span>');  jQuery('.saph_field ' + elem).attr('class','err');  jQuery('.modal_content_ form ' + elem).before('<span class="error"><?php  _e('Укажите корректный E-mail!', 'saphali-buy-now');?></span>');  jQuery('.modal_content_ form ' + elem).attr('class','err'); } else { if(where == 'phone' || where == 'name' || where == 'email') { 	elem.attr('class','err'); 	elem.parent().addClass('saph_required_border'); 	 	elem.before('<span class="error"><?php  _e('Обязательное поле!', 'saphali-buy-now');?></span>'); } else { 	jQuery('.saph_field ' + elem).attr('class','err'); 	jQuery('.saph_field ' + elem).parent().addClass('saph_required_border'); 	jQuery('.saph_field ' + elem).before('<span class="error"><?php  _e('Обязательное поле!', 'saphali-buy-now');?></span>'); 	 } } 	}  	return; } var ajax_url = "<?php  echo admin_url( 'admin-ajax.php', 'relative' ); ?>"; var found_bn = ajax_url.match(/\?/i); if( found_bn == null ) { var simb_bn = '?'; } else { var simb_bn = '&'; }	 var popup_wide = 520; if ( wc_getWidth()  <= 568 ) {  	popup_wide = '95%';  } var product_id = jQuery('form.cart input[name="add-to-cart"]').val(); jQuery(document).ready(function($) { 	jQuery('body').delegate(".single-buy-now button.button.alt", 'click', function(event){ 	event.preventDefault(); }); 	if(jQuery(".single-buy-now button.button.alt").attr('value') !== '') 	product_id = jQuery(".single-buy-now button.button.alt").attr('value'); 	if( '<?php  if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) echo 'yes'; ?>' == 'yes') 	{ jQuery(".single-buy-now button.button.alt").fancybox({ href: ajax_url+simb_bn+"action=saph_popup&product_id="+product_id+"&security=<?php  echo $saph_popup; ?>", centerOnScroll : true, transitionIn : 'none',  transitionOut: 'none', easingIn: 'swing', easingOut: 'swing', speedIn : 300, speedOut : 0, width: popup_wide, autoScale: true, autoDimensions: true, height: 460, margin: 0, maxWidth: "95%", maxHeight: "80%", padding: 10, overlayColor: '#666666', showCloseButton : true, openEffect	: "none", closeEffect	: "none" }); } else {if(is_load_ajax_url_popup) {  function load_ajax_url_popup (event){   if(is_load_ajax_url_popup) return; is_load_ajax_url_popup = true; product_id = jQuery(this).attr('value');  if(typeof produc_id == 'undefined' || produc_id == '') {produc_id = jQuery(this).parent().parent().find('form.cart input[name="add-to-cart"]').val();} jQuery(this).attr('href', ajax_url+simb_bn+"action=saph_popup&product_id="+product_id+"&security=<?php  echo $saph_popup; ?>&ajax=true"); jQuery(this).prettyPhoto({ social_tools: false, theme: 'pp_woocommerce', horizontal_padding: 40, opacity: 0.9, deeplinking: false, changepicturecallback: function(){ is_load_ajax_url_popup = false; } }).click(); }  jQuery("body").delegate('.single-buy-now button.button.alt', 'click', load_ajax_url_popup); is_load_ajax_url_popup = false;}} 	jQuery('body').delegate( ".saph_form_button", 'click', function(event){ event.preventDefault();  jQuery(this).parent().find('img').show(); jQuery(this).parent().parent().find('.saph_required_border').each(function(){ jQuery(this).removeClass('saph_required_border'); }); jQuery(this).parent().parent().find('span.error').each(function(){ jQuery(this).remove(); }); var name = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_name"]').val()), filial = jQuery.trim(jQuery(this).parent().parent().find('select[name="filial"]').val()), phone = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_phone"]').val()), email = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_email"]').val()), subj = jQuery.trim(jQuery(this).parent().parent().find('span.saph_subject').text()), msg = jQuery.trim(jQuery(this).parent().parent().find('textarea[name="your_message"]').val()); qty = jQuery.trim(jQuery('input.qty').val()); if(qty == 0 || qty == '' ) qty = 1; var atr= '', cAttr = {}; jQuery(".variations select option").each(function(i,e){ if(i == 0) { atr = '';} if(atr != '') atr = atr + '&'; if(jQuery(this).attr("selected") == 'selected') cAttr[i] = jQuery(this).text(); }); 	if( phone.length > 0 && (filial.length > 0 || typeof jQuery(this).parent().parent().find('select[name="filial"]').val() == 'undefined')  ) { var images = jQuery(this).parent(); if (_xhr__) _xhr__.abort(); var _this = jQuery(this); var _this_text = jQuery(this).text(); _this.attr('disabled','disabled'); _this.html('Отправляем'); _xhr__ = jQuery.ajax({ 	type: 'post', 	data: {cName:name,cFilial:filial, cPhone:phone, cEmail:email, cSubject:subj,cMsg:msg,cQty:qty,cAttr:cAttr, is_buy_now: true}, 	dataType: 'json', 	success: function(data){ _this.attr('disabled',false); _this.html(_this_text); if('send_result' in data && typeof data.send_result == 'boolean') { _addErrMsg(data.send_result ? 'success' : 'fail', '.saph_form .info:first'); /* setTimeout(function(){jQuery('.pp_close').click();}, 1000); */ } else { if('name_error' in data) _addErrMsg('name',jQuery(this).parent().parent().find('input[name="your_name"]')); if('email_error' in data) _addErrMsg('email',jQuery(this).parent().parent().find('input[name="your_email"]')); if('phone_error' in data) _addErrMsg('phone',jQuery(this).parent().parent().find('input[name="your_phone"]')); if('email_error_n_v' in data) _addErrMsg('email_n_v'); if('subject_error' in data) _addErrMsg('subj'); if('message_error' in data) _addErrMsg('msg'); } images.find('img').hide(); if(typeof data.send_result == 'boolean' && data.send_result) {  }	}, 	complete: function(xhr,status){ _this.attr('disabled',false); _this.html(_this_text); } }); } else { jQuery(this).parent().find('img').hide(); if(phone.length <= 0) _addErrMsg('phone',jQuery(this).parent().parent().find('input[name="your_phone"]'));if(filial.length <= 0 && typeof jQuery(this).parent().parent().find('select[name="filial"]').val() != 'undefined') _addErrMsg('phone',jQuery(this).parent().parent().find('select[name="filial"]')); } return false; 	}); });   </script> <?php
		} else {
			if( !isset($loop_saphali_buy_now) ) {
		?> <style> .single-buy-now button.button.alt {float: none!important;} .single-buy-now { float: left; padding: 0 0 9px; }@media (max-width: 480px) {  } </style> <script type="text/javascript"> if(typeof is_load_ajax_url_popup == 'undefined') var is_load_ajax_url_popup = true;  function wc_getWidth() { xWidth = null; if(window.screen != null)   xWidth = window.screen.availWidth; if(window.innerWidth != null)   xWidth = window.innerWidth; if(document.body != null)   xWidth = document.body.clientWidth; return xWidth; } var _xhr__; function _addErrMsg(where, r) { var elem_n_v = false; switch(where) { case 'name': 	var elem = r; 	break; case 'email': 	var elem = 'input[name="your_email"]'; 	break;  	case 'email_n_v': 	var elem = 'input[name="your_email"]'; 	elem_n_v = true; 	break; case 'phone': 	var elem = r; 	break; case 'subj': 	var elem = 'input[name="your_message"]'; 	break; case 'msg': 	var elem = 'textarea[name="your_message"]'; 	break;  } if(where == 'success' || where == 'fail') { jQuery(r).prepend('<p id="output"><strong>' + (where == 'success' ? '<?php  _e('Сообщение отправлено!', 'saphali-buy-now');?>' : '<span class="err"><?php  _e('Отправить не удалось!', 'saphali-buy-now');?></span>') + '</strong></p>'); if(where == 'success') { jQuery(".set_question input[type='submit']").unbind('click'); jQuery(".set_question_ input[type='submit']").unbind('click'); if(typeof _gaq != "undefined"){ var buy_now_path =  location.pathname.replace(/[^\/]+$/i, 'buy_now_thanks.php'); _gaq.push(    ['_trackPageview', buy_now_path.replace(/[\/]$/i,    '/buy_now_thanks.php') ]); _gaq.push(['_trackEvent', 'Forms', 'AJAX Form BuyNow']); } else { var buy_now_path =  location.pathname.replace(/[^\/]+$/i, 'buy_now_thanks.php'); try { ga('send', 'pageview', {  'page':  buy_now_path.replace(/[\/]$/i, '/buy_now_thanks.php'),  'title': 'Покупка в 1 клик'});} catch(e) {} } setTimeout(function(){jQuery('.pp_close').click();}, 1000); } } else { if(where == 'capch') { 	jQuery(r).prepend('<span id="output"><strong>' +  '<?php  _e('Не пройдена проверка на бота! Не используйте, пожалуйста, автоматические средства заполнения форм.', 'saphali-buy-now');?>'+ '</strong></span>' ); } if(elem_n_v) { 	jQuery('.saph_field ' + elem).before('<span class="error"><?php  _e('Укажите корректный E-mail!', 'saphali-buy-now');?></span>');  	jQuery('.saph_field ' + elem).attr('class','err'); jQuery('.modal_content_ form ' + elem).before('<span class="error"><?php  _e('Укажите корректный E-mail!', 'saphali-buy-now');?></span>');  	jQuery('.modal_content_ form ' + elem).attr('class','err'); } else { 	if(where == 'phone' || where == 'name' || where == 'email') { elem.attr('class','err'); elem.parent().addClass('saph_required_border'); elem.before('<span class="error"><?php  _e('Обязательное поле!', 'saphali-buy-now');?></span>'); 	} else { jQuery('.saph_field ' + elem).parent().addClass('saph_required_border'); jQuery('.saph_field ' + elem).attr('class','err'); jQuery('.saph_field ' + elem).before('<span class="error"><?php  _e('Обязательное поле!', 'saphali-buy-now');?></span>');  } } } return; } var ajax_url = "<?php  echo admin_url( 'admin-ajax.php', 'relative' ); ?>"; var found_bn = ajax_url.match(/\?/i); if( found_bn == null ) { var simb_bn = '?'; } else { var simb_bn = '&'; } var popup_wide = 520; if ( wc_getWidth()  <= 568 ) {  popup_wide = '95%';  } var product_id = jQuery('form.cart input[name="add-to-cart"]').val(); var pp_photo; jQuery(document).ready(function($) { jQuery("body").delegate('.single-buy-now button.button.alt', 'click', function(event){event.preventDefault();    }); if(jQuery(".single-buy-now button.button.alt").attr('value') !== '') product_id = jQuery(".single-buy-now button.button.alt").attr('value'); if( '<?php  if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) echo 'yes'; ?>' == 'yes') { setTimeout( function(product_id) { 	jQuery(".single-buy-now button.button.alt").fancybox({ href: ajax_url+simb_bn+"action=saph_popup&product_id="+product_id+"&security=<?php  echo $saph_popup; ?>", centerOnScroll : true, transitionIn : 'none', transitionOut: 'none', easingIn: 'swing', easingOut: 'swing', speedIn : 300, speedOut : 0, width: popup_wide, autoScale: true, autoDimensions: true, height: 460, margin: 0, maxWidth: "95%", maxHeight: "80%", padding: 10, overlayColor: '#666666', showCloseButton : true, openEffect	: "none", closeEffect	: "none" 	}); }, 500 , product_id ); } else {if(is_load_ajax_url_popup) {  function load_ajax_url_popup (event){   if(is_load_ajax_url_popup) return; is_load_ajax_url_popup = true; product_id = jQuery(this).attr('value');  if(typeof produc_id == 'undefined' || produc_id == '') {produc_id = jQuery(this).parent().parent().find('form.cart input[name="add-to-cart"]').val();} jQuery(this).attr('href', ajax_url+simb_bn+"action=saph_popup&product_id="+product_id+"&security=<?php  echo $saph_popup; ?>&ajax=true"); jQuery(this).prettyPhoto({ social_tools: false, theme: 'pp_woocommerce', horizontal_padding: 40, opacity: 0.9, deeplinking: false, changepicturecallback: function(){ is_load_ajax_url_popup = false; } }).click(); }  jQuery("body").delegate('.single-buy-now button.button.alt', 'click', load_ajax_url_popup); is_load_ajax_url_popup = false;}} jQuery('body').delegate(".saph_form_button", 'click', function(event){ event.preventDefault();  jQuery(this).parent().find('img').show(); jQuery(this).parent().parent().find('.saph_required_border').each(function(){ 	jQuery(this).removeClass('saph_required_border');  	}); jQuery(this).parent().parent().find('span.error').each(function(){ 	jQuery(this).remove(); }); var name = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_name"]').val()), filial = jQuery.trim(jQuery(this).parent().parent().find('select[name="filial"]').val()), phone = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_phone"]').val()), email = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_email"]').val()), subj = jQuery.trim(jQuery(this).parent().parent().find('span.saph_subject').html()), msg = jQuery.trim(jQuery(this).parent().parent().find('textarea[name="your_message"]').val());  qty = jQuery.trim(jQuery('input.qty').val());  var atr= '', cAttr = {}; jQuery(".variations select option").each(function(i,e){ 	if(i == 0) { atr = '';} if(atr != '') atr = atr + '&'; 	if(jQuery(this).attr("selected") == 'selected') 	cAttr[i] = jQuery(this).text();  });  if( phone.length > 0 && (filial.length > 0 || typeof jQuery(this).parent().parent().find('select[name="filial"]').val() == 'undefined')  ) { 	var images = jQuery(this).parent(); 	if (_xhr__) _xhr__.abort(); var _this = jQuery(this); var _this_text = jQuery(this).text(); _this.attr('disabled','disabled'); _this.html('Отправляем'); 	_xhr__ = jQuery.ajax({ type: 'post', data: {cName:name, cFilial:filial, cPhone:phone, Email:1, cEmail:email, cSubject:subj,cMsg:msg,cQty:qty,cAttr:cAttr, is_buy_now: true}, dataType: 'json', success: function(data){ _this.attr('disabled',false); _this.html(_this_text);  	if('send_result' in data && typeof data.send_result == 'boolean') { _addErrMsg(data.send_result ? 'success' : 'fail', '.saph_form .info:first'); /* setTimeout(function(){jQuery('.pp_close').click();}, 1000);  */ 	} else { if('name_error' in data) _addErrMsg('name',jQuery(this).parent().parent().find('input[name="your_name"]')); if('email_error' in data) _addErrMsg('email',jQuery(this).parent().parent().find('input[name="your_email"]')); if('phone_error' in data) _addErrMsg('phone',jQuery(this).parent().parent().find('input[name="your_phone"]')); if('email_error_n_v' in data) _addErrMsg('email_n_v'); if('subject_error' in data) _addErrMsg('subj'); if('message_error' in data) _addErrMsg('msg');  	}  if(typeof data.send_result == 'boolean' && data.send_result) {  }	images.find('img').hide();  }, complete: function(xhr,status){   _this.attr('disabled',false); _this.html(_this_text); 	} 	}); } else { 	jQuery(this).parent().find('img').hide(); 	if(phone.length <= 0) _addErrMsg('phone',jQuery(this).parent().parent().find('input[name="your_phone"]')); if(filial.length <= 0&& typeof jQuery(this).parent().parent().find('select[name="filial"]').val() != 'undefined') _addErrMsg('phone',jQuery(this).parent().parent().find('select[name="filial"]')); } return false; }); }); 	</script><?php 	
				$loop_saphali_buy_now = true;
			}
		}
		if( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
		?>		<style>		h1.saph_result_heading { font-size: 20px;  }  .saph_required { color: #FF0000;  }  .saph_form_button { cursor: pointer;  }  div.saph_field .saph_label{ cursor: pointer; float: left; width: 30%;  }  div.saph_field input, div.saph_field textarea, div.saph_field select { border: 1px solid #CCCCCC !important; border-radius: 0 !important; box-shadow: 0 0 0 #FFFFFF !important; color: #000000 !important; font-size: 13px !important; padding: 5px !important; width: 66% !important; float: right;  }  div.saph_field { margin-bottom: 3px; clear: both;  }  .saph_field a.saph_form_button { cursor: pointer; font-size: 19px !important; float: right; margin-top: 12px !important;  }  .saph_required_border { border: 1px solid #FF0000;  }  .error { color: #FF0000;  }	@media (max-width: 480px) {  }	</style>		<?php 
		} else {
		?>		<style> h1.saph_result_heading { font-size: 20px; margin-bottom: 17px; } .saph_required { color: #FF0000; } .saph_form_button { cursor: pointer; } div.saph_field .saph_label{ cursor: pointer; float: left; width: 30%; } div.saph_field input, div.saph_field textarea, div.saph_field select { border: 1px solid #CCCCCC !important; border-radius: 0 !important; -moz-border-radius: 0 !important; -webkit-border-radius: 0 !important; -moz-box-shadow: 0 0 0 #FFFFFF !important; -webkit-box-shadow: 0 0 0 #FFFFFF !important; box-shadow: 0 0 0 #FFFFFF !important; color: #000000 !important; font-size: 13px !important; padding: 5px !important; width: 66% !important; float: right; } div.saph_field { margin-bottom: 3px; clear: both; } .error { color: #FF0000; } .saph_field a.saph_form_button { cursor: pointer; font-size: 19px !important; float: right; margin-top: 12px !important; } .saph_required_border { border: 1px solid #FF0000; } .pp_description {display:none!important;}@media (max-width: 480px) {  }		</style>		<?php 
		}
	}
	
	function woocommerce_catalog_add_to_cart_button() {
		global $product, $loop_saphali_buy_now;
		$saph_popup = wp_create_nonce("saph_popup");
		if(!is_object($product)) return;
		if($product->product_type != 'variable' && $product->is_in_stock() ) {
		?> <div class="single-buy-now"><span>&ndash;&nbsp;<?php  _e('or', 'saphali-buy-now'); ?>&nbsp;&ndash;&nbsp;</span><button class="button alt"  value="<?php  echo esc_attr( $product->id ); ?>" ><?php  _e('Buy Now', 'saphali-buy-now'); ?> </button></div> <?php if( ( isset($loop_saphali_buy_now) && $loop_saphali_buy_now === true )  ) return; ?>			<style>			.single-buy-now button.button.alt {float: none!important;} 	@media (max-width: 480px) {  }		</style>			<script type="text/javascript"> if(typeof is_load_ajax_url_popup == 'undefined') var is_load_ajax_url_popup = true;  function wc_getWidth() { xWidth = null; if(window.screen != null) xWidth = window.screen.availWidth; if(window.innerWidth != null) xWidth = window.innerWidth; if(document.body != null) xWidth = document.body.clientWidth; return xWidth;} var _xhr__; function _addErrMsg(where, r) { var elem_n_v = false; switch(where) { case 'name': var elem = r; break; case 'email': var elem = 'input[name="your_email"]'; break; case 'email_n_v': var elem = 'input[name="your_email"]'; elem_n_v = true; break; case 'phone': var elem = r; break; case 'subj': var elem = 'input[name="your_message"]'; break; case 'msg': var elem = 'textarea[name="your_message"]'; break; } if(where == 'success' || where == 'fail') { jQuery(r).prepend('<p id="output"><strong>' + (where == 'success' ? '<?php  _e('Сообщение отправлено!', 'saphali-buy-now');?>' : '<span class="err"><?php  _e('Отправить не удалось!', 'saphali-buy-now');?></span>') + '</strong></p>'); if(where == 'success') { jQuery(".set_question input[type='submit']").unbind('click'); jQuery(".set_question_ input[type='submit']").unbind('click'); if(typeof _gaq != "undefined"){ var buy_now_path =  location.pathname.replace(/[^\/]+$/i, 'buy_now_thanks.php'); _gaq.push( ['_trackPageview', buy_now_path.replace(/[\/]$/i, '/buy_now_thanks.php') ]); _gaq.push(['_trackEvent', 'Forms', 'AJAX Form BuyNow']); } else { var buy_now_path =  location.pathname.replace(/[^\/]+$/i, 'buy_now_thanks.php'); try { ga('send', 'pageview', {  'page':  buy_now_path.replace(/[\/]$/i, '/buy_now_thanks.php'),  'title': 'Покупка в 1 клик'});} catch(e) {} } setTimeout(function(){jQuery('.pp_close').click();}, 1000); } } else { if(where == 'capch') { jQuery(r).prepend('<span id="output"><strong>' +  '<?php  _e('Не пройдена проверка на бота! Не используйте, пожалуйста, автоматические средства заполнения форм.', 'saphali-buy-now');?>'+ '</strong></span>' ); } if(elem_n_v) { jQuery('.saph_field ' + elem).before('<span class="error"><?php  _e('Укажите корректный E-mail!', 'saphali-buy-now');?></span>');jQuery('.saph_field ' + elem).attr('class','err'); jQuery('.modal_content_ form ' + elem).before('<span class="error"><?php  _e('Укажите корректный E-mail!', 'saphali-buy-now');?></span>'); jQuery('.modal_content_ form ' + elem).attr('class','err'); } else { if(where == 'phone' || where == 'name' || where == 'email') { elem.attr('class','err'); elem.parent().addClass('saph_required_border'); elem.before('<span class="error"><?php  _e('Обязательное поле!', 'saphali-buy-now');?></span>'); } else { jQuery('.saph_field ' + elem).attr('class','err'); jQuery('.saph_field ' + elem).parent().addClass('saph_required_border'); jQuery('.saph_field ' + elem).before('<span class="error"><?php  _e('Обязательное поле!', 'saphali-buy-now');?></span>'); } } } return; } var ajax_url = "<?php  echo admin_url( 'admin-ajax.php', 'relative' ); ?>"; var found_bn = ajax_url.match(/\?/i); if( found_bn == null ) { var simb_bn = '?'; } else { var simb_bn = '&'; } var popup_wide = 520; if ( wc_getWidth()  <= 568 ) { popup_wide = '95%'; } var product_id = jQuery('form.cart input[name="add-to-cart"]').val(); jQuery(document).ready(function($) { jQuery("body").delegate('.single-buy-now button.button.alt', 'click', function(event){ event.preventDefault();  }); if(jQuery(".single-buy-now button.button.alt").attr('value') !== '') product_id = jQuery(".single-buy-now button.button.alt").attr('value'); if( '<?php  if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) echo 'yes'; ?>' == 'yes') { jQuery(".single-buy-now button.button.alt").fancybox({ href: ajax_url+simb_bn+"action=saph_popup&product_id="+product_id+"&security=<?php  echo $saph_popup; ?>", centerOnScroll : true, transitionIn : 'none', transitionOut: 'none', easingIn: 'swing', easingOut: 'swing', speedIn : 300, speedOut : 0, width: popup_wide, autoScale: true, autoDimensions: true, height: 460, margin: 0, maxWidth: "95%", maxHeight: "80%", padding: 10, overlayColor: '#666666', showCloseButton : true, openEffect	: "none", closeEffect	: "none" }); } else {  if(is_load_ajax_url_popup) {  function load_ajax_url_popup (event){   if(is_load_ajax_url_popup) return; is_load_ajax_url_popup = true; product_id = jQuery(this).attr('value');  if(typeof produc_id == 'undefined' || produc_id == '') {produc_id = jQuery(this).parent().parent().find('form.cart input[name="add-to-cart"]').val();} jQuery(this).attr('href', ajax_url+simb_bn+"action=saph_popup&product_id="+product_id+"&security=<?php  echo $saph_popup; ?>&ajax=true"); jQuery(this).prettyPhoto({ social_tools: false, theme: 'pp_woocommerce', horizontal_padding: 40, opacity: 0.9, deeplinking: false, changepicturecallback: function(){ is_load_ajax_url_popup = false; } }).click(); }  jQuery("body").delegate('.single-buy-now button.button.alt', 'click', load_ajax_url_popup); is_load_ajax_url_popup = false;}} jQuery('body').delegate(".saph_form_button", 'click', function(event){ event.preventDefault(); jQuery(this).parent().find('img').show(); jQuery(this).parent().parent().find('.saph_required_border').each(function(){ jQuery(this).removeClass('saph_required_border'); }); jQuery(this).parent().parent().find('span.error').each(function(){ jQuery(this).remove(); }); var name = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_name"]').val()), filial = jQuery.trim(jQuery(this).parent().parent().find('select[name="filial"]').val()), phone = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_phone"]').val()), email = jQuery.trim(jQuery(this).parent().parent().find('input[name="your_email"]').val()), subj = jQuery.trim(jQuery(this).parent().parent().find('span.saph_subject').html()), msg = jQuery.trim(jQuery(this).parent().parent().find('textarea[name="your_message"]').val()); qty = jQuery.trim(jQuery('input.qty').val());if(qty == 0 || qty == '' ) qty = 1; var atr= '', cAttr = {}; jQuery(".variations select option").each(function(i,e){						if(i == 0) { atr = '';} if(atr != '') atr = atr + '&'; if(jQuery(this).attr("selected") == 'selected') cAttr[i] = jQuery(this).text(); }); if( phone.length > 0 && (filial.length > 0 || typeof jQuery(this).parent().parent().find('select[name="filial"]').val() == 'undefined')  ) { var images = jQuery(this).parent(); if (_xhr__) _xhr__.abort(); var _this = jQuery(this); var _this_text = jQuery(this).text(); _this.attr('disabled','disabled'); _this.html('Отправляем'); _xhr__ = jQuery.ajax({ type: 'post', data: {cName:name, cPhone:phone, cFilial:filial, cEmail:email, cSubject:subj,cMsg:msg,cQty:qty,cAttr:cAttr, is_buy_now: true}, dataType: 'json', success: function(data){ _this.attr('disabled',false); _this.html(_this_text); if('send_result' in data && typeof data.send_result == 'boolean') { _addErrMsg(data.send_result ? 'success' : 'fail', '.saph_form .info:first'); /* setTimeout(function(){jQuery('.pp_close').click();}, 1000); */ } else { if('name_error' in data) _addErrMsg('name',jQuery(this).parent().parent().find('input[name="your_name"]')); if('email_error' in data) _addErrMsg('email',jQuery(this).parent().parent().find('input[name="your_email"]')); if('phone_error' in data) _addErrMsg('phone',jQuery(this).parent().parent().find('input[name="your_phone"]')); if('email_error_n_v' in data) _addErrMsg('email_n_v'); if('subject_error' in data) _addErrMsg('subj'); if('message_error' in data) _addErrMsg('msg'); } images.find('img').hide(); if(typeof data.send_result == 'boolean' && data.send_result) {  } }, complete: function(xhr,status){ _this.attr('disabled',false); _this.html(_this_text); } }); } else { jQuery(this).parent().find('img').hide(); if(phone.length <= 0) _addErrMsg('phone',jQuery(this).parent().parent().find('input[name="your_phone"]')); if(filial.length <= 0 && typeof jQuery(this).parent().parent().find('select[name="filial"]').val() != 'undefined') _addErrMsg('phone',jQuery(this).parent().parent().find('select[name="filial"]')); } return false; }); }); </script> <?php
			$loop_saphali_buy_now = true;
		} else return; 
		if( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
		?> <style> h1.saph_result_heading {font-size: 20px;}.saph_required {color: #FF0000;}.saph_form_button {cursor: pointer;}div.saph_field .saph_label{cursor: pointer;float: left;width: 30%;}div.saph_field input, div.saph_field textarea, div.saph_field select {border: 1px solid #CCCCCC !important;			border-radius: 0 !important;box-shadow: 0 0 0 #FFFFFF !important; color: #000000 !important; font-size: 13px !important; padding: 5px !important; width: 66% !important; float: right; } div.saph_field { margin-bottom: 3px; clear: both; } .saph_field a.saph_form_button { cursor: pointer; font-size: 19px !important; float: right; margin-top: 12px !important; } .saph_required_border { border: 1px solid #FF0000; } .error { color: #FF0000; } @media (max-width: 480px) {  }</style> <?php 
		} else {
		?>		<style>		h1.saph_result_heading {font-size: 20px;margin-bottom: 17px;}.saph_required {color: #FF0000;}.saph_form_button {			cursor: pointer;}div.saph_field .saph_label{cursor: pointer;float: left;width: 30%;}div.saph_field input, div.saph_field textarea, div.saph_field select {border: 1px solid #CCCCCC !important;border-radius: 0 !important;-moz-border-radius: 0 !important;-webkit-border-radius: 0 !important;-moz-box-shadow: 0 0 0 #FFFFFF !important;-webkit-box-shadow: 0 0 0 #FFFFFF !important;box-shadow: 0 0 0 #FFFFFF !important;color: #000000 !important;font-size: 13px !important;padding: 5px !important;width: 66% !important;float: right;}div.saph_field {margin-bottom: 3px;clear: both;}.error {color: #FF0000;		}.single_variation_wrap .single_variation {width: 100%;}.saph_field a.saph_form_button {cursor: pointer;font-size: 19px !important;float: right;margin-top: 12px !important;}.saph_required_border {border: 1px solid #FF0000;}.pp_description {display:none!important;}@media (max-width: 480px) {  }</style><?php 
		}
	}
	function _woocommerce_before_calculate_totals_s_logged_price($atrr, $_this, $pr) {
	if($pr->is_in_stock()) {
	$js = ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) ? 'yes' : '' ;
	$saph_popup = wp_create_nonce("saph_popup");
$js =   "<script type='text/javascript'>jQuery(document).ready(function(\$) { var found_bn = ajax_url.match(/\?/i); if( found_bn == null ) { var simb_bn = '?'; } else { var simb_bn = '&'; }	if( '$js' == 'yes'){ setTimeout( function( product_id) { jQuery(\".variations_form .single-buy-now button.button.alt\").fancybox({ href: ajax_url+simb_bn+\"action=saph_popup&product_id={$_this->id}&security=$saph_popup\", centerOnScroll : true, transitionIn : 'none', transitionOut: 'none', easingIn: 'swing', easingOut: 'swing', speedIn : 300, speedOut : 0, width: popup_wide, autoScale: true, autoDimensions: true, height: 460, margin: 0, maxWidth: \"95%\", maxHeight: \"80%\", padding: 10, overlayColor: '#666666', showCloseButton : true, openEffect	: \"none\", closeEffect	: \"none\" });}, 500, product_id); } else { setTimeout( function( product_id) { $(\".variations_form .single-buy-now button.button.alt\").attr('href', ajax_url+simb_bn+\"action=saph_popup&product_id={$_this->id}&security=$saph_popup&ajax=true\");						\$(\".single-buy-now button.button.alt\").prettyPhoto({ social_tools: false, theme: 'pp_woocommerce',horizontal_padding: 40, opacity: 0.9, deeplinking: false});},  500, product_id ); };});</script> <style> div.saphali-buy-now { clear: both; position: relative; top: 10px; } .saphali-buy-now span { float: left;}@media (max-width: 480px) {  }</style> ";
		$atrr['availability_html'] =  $atrr['availability_html'] . $js;
		}
		return $atrr;
	}
	function variation_add_to_buy() {
		global $product;
		if($product->product_type != 'variable' ) return;
		$select_cb = get_option('_saphali_not_botton_cb', 1);
		$text_button = '<div class="saphali-buy-now"><span>&nbsp;&ndash;' . __('or', 'saphali-buy-now')  . '&ndash;&nbsp;</span>' . '<div class="single-buy-now"><button class="button alt"  value="" >' . __('Buy Now', 'saphali-buy-now') . ' </button></div></div><div class="clear"></div>';
		echo $text_button;
		?>		<script type="text/javascript">		jQuery(function($) {var saphali_buy_now_selector;$('body').delegate(".variations select", 'change', function() { saphali_buy_now_selector = true; if( $(this).val() + '' != '' && $(this).val()+ '' != 'undefined' ) { if(saphali_buy_now_selector) $(".variations select").each(function(i,e){ if(e.value == '') saphali_buy_now_selector = false; }); if(saphali_buy_now_selector) $(".single-product div.saphali-buy-now").show(); } else { $(".single-product div.saphali-buy-now").hide();   } <?php if( $select_cb ) { ?> if($("div.variations_button").css('display') != 'block' || $("div.variations_button").hasClass('woocommerce-variation-add-to-cart-disabled') ) { $(".single-product div.saphali-buy-now").hide(); } else if( $(this).val() + '' != '' && $(this).val()+ '' != 'undefined' ) { if(saphali_buy_now_selector) $(".variations select").each(function(i,e){ if(e.value == '') saphali_buy_now_selector = false; }); if(saphali_buy_now_selector) $(".single-product div.saphali-buy-now").show(); else  $(".single-product div.saphali-buy-now").hide(); } <?php } ?> }); $( ".variations select" ).trigger('change'); });		</script> <?php
	}
function init() {
		if(strtolower($_SERVER['REQUEST_METHOD']) == 'post' && isset($_POST['cName']) && isset($_POST['cPhone']) && isset($_POST['cSubject']) && isset($_POST['is_buy_now']) ) {
			ob_start();
			$Email = isset($_POST['mail']) ? trim($_POST['mail']) : '';
			if(!empty($Email)) return;
			$filials_bn = get_option('_saphali_filials_bn', array() );
			$e_selected_f_bn = isset($filials_bn['filials_anabled']) && $filials_bn['filials_anabled'] ? ' checked="checked"': '';
			$name = isset($_POST['cName']) ? trim($_POST['cName']) : '';
			$phone =  isset($_POST['cPhone']) ? trim($_POST['cPhone']) : '';
			if(!empty($name)) $name = ', ' . $name;
			$cc= 0;
			if(isset($_POST['cAttr']) && is_array($_POST['cAttr']))
			foreach($_POST['cAttr'] as $v) {
				if($cc == 0) $_Attr = '' . $v . ''; else
				$_Attr .= ', ' . $v . '';
				$cc++;
			}
			if(isset($_POST['cEmail']))
			$email = trim($_POST['cEmail']);
			$subject = trim(  stripslashes(html_entity_decode(urldecode($_POST['cSubject'] ), ENT_QUOTES, 'UTF-8') ));
			if($subject == '' && $_POST['cMsg']=='' ) {$subject = $subjects = __('Обратный звонок. ', 'saphali-buy-now').str_replace(', ', '', $name); $msg = sprintf(__("<h3>Перезвоните мне на номер: %s%s</h3>", 'saphali-buy-now'), $phone, $name);}
			elseif($subject == '' && $_POST['cMsg']!= ''){
				$subject = $subjects = __('Заказ с главной страницы. ', 'saphali-buy-now').str_replace(', ', '', $name);
				if(isset($_POST['cEmail'])) {
					$_mail = '<p></p>';
				}
				$msg = '<h3>'.$subject.'</h3>'.'<h3>'.__('Описание к заказу:', 'saphali-buy-now').'</h3>'.htmlspecialchars($_POST['cMsg'])."<br />".sprintf(__('Номер телефона: %s%s', 'saphali-buy-now'), $phone, $name)."</h3>";
			} else { 
				$_subjects = __('Заказ на: ', 'saphali-buy-now');
				$subjects = __('заказ на \'', 'saphali-buy-now') . $subject.'\'';
				if(!empty($_POST['cMsg'])) $comm = '<br /><br /><h3>'.__('Комментарий к заказу:', 'saphali-buy-now').'</h3>';
				else $comm = '';
				if(!empty($_POST['cAttr'])) {
					$Attr = '[' . $_Attr . ']'; 
				} else $Attr = ''; 
				if(empty($email)) $o_mail = '.</h3><style>h3 {margin:0;padding:0;}</style>';
				else
				$o_mail = ", <a href=\"mailto:$email\">$email</a></h3><style>h3 {margin:0;padding:0;}</style>";
				$msg = $_subjects . $subject . $Attr . __(" в количестве ", 'saphali-buy-now').intval($_POST['cQty']).__("&nbsp;шт.", 'saphali-buy-now') .$comm.htmlspecialchars($_POST['cMsg']) ."<br /><br />". sprintf(__("Номер телефона: %s%s", 'saphali-buy-now'), $phone, $name) . $o_mail;
			}
            $u_m = get_the_author_meta('user_email', 1);
			$u_m = empty($u_m) ? get_option('admin_email') : $u_m;
			$email_admin = get_option('_saphali_mail_admin_callme', $u_m);

			if(empty($phone)) $cPhoneErr = true;
			if(isset($email) && !empty($email) )
			if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) $cEmailErrNoValid = true;

			$log_enable = false;

			if(!isset($cNameErr) && !isset($cPhoneErr) && !isset($cSubjectErr) && !isset($cMsgErr)&& !isset($cEmailErrNoValid)) {
				$log_enable = true;
				//add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
				$name   = str_replace(', ', '', $name);
				$this->name   = $name;
				
				if(!empty($email)) {
					//add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
					//add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
					$from = '';
					$from .= 'From: ' . wp_specialchars_decode( esc_html( $name ), ENT_QUOTES );

					$from .= ' <';
					$from .= sanitize_email( $email );
					$from .= '>';
					$headers[] = $from;
					$headers[] = 'Reply-To: ' . sanitize_email( $email );
					$this->adress = $email;
				}
				
				add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
				if($e_selected_f_bn && isset($_POST['cFilial']) ) {
					$msg .= '<br /> Филиал: ' . $filials_bn['filials'][$_POST['cFilial']]['name'];
					if(isset($filials_bn['filials'][$_POST['cFilial']]['mail'])) {
						$email_admin = $filials_bn['filials'][$_POST['cFilial']]['mail'];
					}
				}
				 $out['send_result'] = wp_mail(
					$email_admin,
					htmlspecialchars(get_bloginfo('name') .' - '.strip_tags( $subjects ) ),
					$msg, $headers
				);  
				if(!empty($email)) {
					// remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
					// remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
				}
				if( !$out['send_result'] ) {
					if(!empty ($from) ) unset( $headers [ array_search($from, $headers) ] );
					 $out['send_result'] = wp_mail(
						$email_admin,
						htmlspecialchars(get_bloginfo('name') .' - '.strip_tags( $subjects ) ),
						$msg, $headers
					);  
				}
				remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
				
			} else {
				if(isset($cNameErr)) $out['name_error'] = true;
				//if(isset($cPhoneErr)) $out['email_error'] = true;
				if(isset($cPhoneErr)) $out['phone_error'] = true;
				if(isset($cEmailErrNoValid)) $out['email_error_n_v'] = true;
				if(isset($cSubjectErr)) $out['subject_error'] = true;
				if(isset($cMsgErr)) $out['message_error'] = true;
			}
			
			if($log_enable) {
				if($e_selected_f_bn && isset($_POST['cFilial']) ) {
					$msg .= '<br /> Филиал: ' . $filials_bn['filials'][$_POST['cFilial']]['name'];
				}
				if(!isset($cEmailErrNoValid)) {
					$order_num = (int) get_option('saphali_buy_new_last_number') + 1;
					update_option('saphali_buy_new_last_number', $order_num);
					global $wpdb;
					$sql = "INSERT INTO {$wpdb->prefix}saphali_log_order (name, phone, email, products, order_num, is_quick, date) VALUES (\"{$this->name}\", \"{$phone}\", '". str_replace('\'', '\\\'', $email) . "', '". str_replace(array('\'', "\n", "\r", "\t"), array('\\\'', '', '', ''), $msg) . "', \"{$order_num}\", 0, '" . date_i18n('Y-m-d H:i:s') . "')";
					$queryresult = $wpdb->query(  $sql  );
				}
			}
			ob_clean();
			ob_end_clean();
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				header('Content-type: application/json');
				die(json_encode($out));
			}
		}
	}
	function get_from_address() {
		return sanitize_email( $this->adress );
	}
	function get_from_name() {
		$this->name = ( strpos($this->name, ', ') === 0 ) ? str_replace(', ', '', $this->name) : $this->name;
		return wp_specialchars_decode( esc_html( $this->name ), ENT_QUOTES );
	}
	function get_content_type() {
		return 'text/html';
	}
	public static function install( ) {
		$transient_name = 'wc_saph_' . md5( 'saphali-buy-now' . home_url() );
		$pay[$transient_name] = get_transient( $transient_name );
		delete_option( str_replace('wc_saph_', '_latest_', $transient_name) );
		foreach($pay as $key => $tr) {
			delete_transient( $key );
		}
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
		  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
		  $charset_collate .= " COLLATE {$wpdb->collate}";
		}
		// Таблица с флагом для предотвращения запуска параллелных экспортов.
		// Она общая для плагинов экспорта/импорта, чтобы запущенные одновременно экспорты не забирали
		// ресурсы друг в друга.
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}saphali_log_order (
		    id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(50) NOT NULL DEFAULT '',
			phone varchar(26) NOT NULL DEFAULT '',
			email varchar(100) NOT NULL DEFAULT '',
			products longtext NOT NULL DEFAULT '',
			order_num varchar(50) NOT NULL DEFAULT '',
			is_quick tinyint(1) NOT NULL DEFAULT '0',
			date datetime NOT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";
		
		dbDelta($sql);
	}
	public static function saph_popup() {
			//check_ajax_referer( 'saph_popup', 'security' );

			$saph_action = wp_create_nonce("saph_action");
			$product_id = $_REQUEST['product_id'];
			$product_name = strip_tags( get_the_title($product_id) );
			
			$filials_bn = get_option('_saphali_filials_bn', array() );
			$e_selected_f_bn = isset($filials_bn['filials_anabled']) && $filials_bn['filials_anabled'] ? ' checked="checked"': '';
			$saph_button_title = __('Быстрый заказ', 'saphali-buy-now');

			$saphali_text_button = __('Отправить', 'saphali-buy-now');
			$saphali_heading = $saph_button_title;
			$buy_now_pos = get_option( '_saphali_post_admin_buy_now_p', 2 );
		?>	
	<div class="saph_form">
		<h1 class="saph_result_heading"><?php  echo $saphali_heading; ?></h1> <?php $info = get_option( '_saphali_post_admin_buy_now', '' ); if( !empty($info) ) { ?>
		<div class="info<?php  if($buy_now_pos == 1) echo ' first'; ?>"><?php  if($buy_now_pos == 2) echo wpautop( $info ) ; ?></div><?php }
		$sku = get_post_meta($product_id, '_sku', true);
		$sku = empty( $sku ) ? '' : '. ' . __('SKU', 'woocommerce') . ': ' . $sku . ' ';
		?>
		<div class="saph_content" id="saph_content_<?php  echo $product_id; ?>">

			<div class="saph_field">
				<?php  _e('','saphali-buy-now'); ?> <span class="saph_subject" style="cursor: default;" onClick="return false;"><a href="<?php  echo get_permalink($product_id); ?>"><?php  echo $product_name . $sku ; ?></a></span></div>
			<?php if($e_selected_f_bn) { ?>
			<div class="saph_field">
				<label class="saph_filial saph_label"><?php  _e('Выберите город (филиал)','saphali-buy-now'); ?> <span class="saph_required">*</span> </label> 
				<select name="filial"  tabindex="1">
					<option value=""><?php  _e('Выбрать...','saphali-buy-now'); ?></option>
					<?php foreach($filials_bn['filials'] as $k => $filials) { if(!is_array($filials)) continue; echo '<option value="' . $k . '">' . $filials['name'] . '</option>'; } ?>
				<select>
			</div><?php } ?>
			<div class="saph_field">
				<label class="saph_label" for="your_name_<?php  echo $product_id; ?>"><?php  _e('Name','saphali-buy-now'); ?> </label> 
				<input type="text"  tabindex="2" class="your_name" name="your_name" id="your_name_<?php  echo $product_id; ?>" value="" /></div>
			<div class="saph_field">
				<label class="saph_label" for="your_email_<?php  echo $product_id; ?>"><?php  _e('Email','saphali-buy-now'); ?> </label>
				<input type="text"  tabindex="3" class="your_email" name="your_email" id="your_email_<?php  echo $product_id; ?>" value="" /></div>
			<div class="saph_field">
				<label class="saph_label" for="your_phone_<?php  echo $product_id; ?>"><?php  _e('Phone','saphali-buy-now'); ?> <span class="saph_required">*</span></label> 
				<input type="text"  tabindex="4" class="your_phone" name="your_phone" id="your_phone_<?php  echo $product_id; ?>" value="" />
				<input type="hidden" class="your_phone" name="mail" value="" /><div class="clear"></div></div>
			<div class="saph_field">
				<label class="saph_label" for="your_message_<?php  echo $product_id; ?>"><?php  _e('Message','saphali-buy-now'); ?></label> 
				<textarea class="your_message"  tabindex="5" name="your_message" id="your_message_<?php  echo $product_id; ?>"></textarea></div>
			<div class="saph_field">
				<a class="saph_form_button button saph_bt_<?php  echo $product_id; ?>" id="saph_bt_<?php  echo $product_id; ?>" product_id="<?php  echo $product_id; ?>"  tabindex="6" ><?php  echo $saphali_text_button; ?></a> <span class="saph_loading" id="saph_loading_<?php  echo $product_id; ?>"><img style="display:none;" src="<?php  echo saphali_buy_now::$plugin_url; ?>images/ajax-loader.gif" /></span>
			</div>
			<div style="clear:both"></div>
		</div>
		<div style="clear:both"></div>
		<div class="info<?php  if($buy_now_pos == 2) echo ' last'; ?>"><?php  if($buy_now_pos == 1) echo wpautop( get_option( '_saphali_post_admin_buy_now', '' ) ) ; ?></div>
	</div>
	<style type="text/css">	.saph_form div.info {		color: #000000;		padding-bottom: 8px;		background: none;	}	.saph_form div.info p {		margin:0;padding:0;	}	div.saph_form {		padding: 0 12px;}<?php  $colors_bg = get_option( '_saphali_post_admin_buy_now_bg_color' , "#f7f6f7" ); ?>	.pp_right .pp_content {		height: auto!important;	}	.saph_form div.info.last, .saph_form div.info.first {		border: medium none;		box-shadow: none;		margin: 0;		padding: 0;	}	div.pp_woocommerce .pp_content_container { padding: 20px 0 12px!important;}	<?php    echo 'div.pp_woocommerce .pp_content_container {		background: none repeat scroll 0 0 '.$colors_bg.'!important;	}';?>	</style>
	<?php
		die();
	}

}
if( !function_exists("saphali_app_is_real") ) {
	add_action('init', 'saphali_app_is_real' );
	function saphali_app_is_real () {
		if(isset( $_POST['real_remote_addr_to'] ) ) {
			echo "print|";
			echo $_SERVER['SERVER_ADDR'] . ":" . $_SERVER['REMOTE_ADDR'] . ":" . $_POST['PARM'] ;
			exit;	
		}
	}
}
register_activation_hook( __FILE__, array('saphali_buy_now', 'install') );
new saphali_buy_now();
?>