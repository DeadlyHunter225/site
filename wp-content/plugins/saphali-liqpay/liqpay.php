<?php 

class liqpay  extends  WC_Payment_Gateway {
	/**
	 Метод оплаты
	 */
	private $xml;
	
	private $isLiqPay;
	
	/**
	 merchant ID
	 */
	
	private $LiqPaymID;
	/**
	 KEY 
	 */
	private $api_url = 'https://saphali.com/api';
	//private $_api_url = 'http://saphali.com/api';
	
	private $LiqPaymKey;

	/**
	 Url страницы, примающая данные об оплате (прием api)
	 */
	private $LiqPayUrlcall = '';
	
	/**
	 Url страницы, примающая пользователя после оплаты
	 */
	private $LiqPayUrl = '';
	private $unfiltered_request_saphalid;
	var $is_lang_liqpay_en;
	var $sandbox;
	var $only_cart;
	var $action;
	/**
	 URL к серверу API
	 */
	private $LiqPayApiUrl='https://www.liqpay.ua/api/checkout';
	
	public function __construct () {
		global $woocommerce;
		$this->icon = apply_filters('woocommerce_liqpay_icon', SAPHALI_PLUGIN_DIR_URL_LP . 'images/icons/liqpay.png');
		$this->LiqPayUrlcall = get_option('server_url');
		
		$this->LiqPayUrl = get_option('result_url');
		$this->LiqPayUrl = empty($this->LiqPayUrl) ? site_url("/") . '?wc-api=liqpay' : $this->LiqPayUrl;
		
		$this->LiqPaymID = get_option('merchant_id');
		
		$this->LiqPaymKey = base64_decode(strrev(get_option('signature')));
		
		$this->id = 'liqpay';
		$this->is_lang_liqpay_en = get_option('is_lang_liqpay_en', false);
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array( 'refunds' );
		$this->debug = $this->settings['debug'];
		$this->enabled = get_option('woocommerce_liqpay_enabled');
		$this->title = get_option('woocommerce_liqpay_title');
		$this->method_title = 'LiqPay';
		$this->form_submission_method =  $this->settings['form_submission_method'] == 'yes'  ? true: false;
		$this->description = $this->settings['description'];
		$this->only_cart = isset($this->settings['only_cart']) && $this->settings['only_cart'] == 'yes' ? true: false;
		$this->action = isset($this->settings['action']) ? $this->settings['action']: 'pay' ;
		$this->sandbox = ($this->settings['sandbox'] == 'yes') ? 1 : 0;
		if ($this->debug=='yes') { if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) $this->log = $woocommerce->logger(); else $this->log = new WC_Logger(); }
		
		if(!$this->title)
		update_option('woocommerce_liqpay_title', __('LiqPay', 'woocommerce') );
		include_once( plugin_dir_path(__FILE__) . 'saphali-plugins.php');
	}
	

	function init_form_fields() {
		$debug = __( 'Log LiqPay events, such as IPN requests, inside <code>woocommerce/logs/' . $this->id . '.txt</code>', 'themewoocommerce' );
		if ( !version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.2.0', '<' ) )
			$debug = str_replace( $this->id, $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ), $debug );
			elseif( function_exists('wc_get_log_file_path') ) {
				$debug = str_replace( 'woocommerce/logs/' . $this->id . '.txt', '<a href="/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '-log" target="_blank">' . wc_get_log_file_path( $this->id ) . '</a>' , $debug );
			}
		}
		$this->form_fields = array(
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __("Pay via LiqPay; you can pay with your credit card if you don't have a LiqPay account or terminal.", 'themewoocommerce')
						),
 			'form_submission_method' => array(
							'title' => __( 'Submission method', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Use form submission method.', 'woocommerce' ),
							'description' => __( 'Снимите здесь галочку, если Вы хотите чтобы перенаправление на сайт LiqPay происходил сразу же, после нажатия на кнопку "Оформить/Разместить заказ", минуя дополнительный этап перехода на страницу с формой.', 'woocommerce' ),
							'default' => 'yes'
						), 
			'sandbox' => array(
							'title' => __( 'Тестовый режим', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Включить тестовый режим', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Позволяет производить отладку. При этом деньги с карты не списываются.', 'themewoocommerce' ),
							'desc_tip'    => true,
						),
			'action' => array(
							'title' => __( 'Тип операции', 'themewoocommerce' ),
							'type' => 'select',
							'label' => __( 'Тип операции', 'themewoocommerce' ),
							'default' => 'pay',
							'options' => array('pay' => 'Платеж (без блокировки)', 'hold' => 'Блокировка средств' ),
							'description' => __( 'Позволяет выбрать продавцу 2 виды схем оплаты: 1) снятие средств сразу при совершении покупателем оплаты; 2) блокировка средств на счету отправителя при совершении покупателем оплаты. Завершение платежа (списание заблокированной суммы с покупателя) осуществляется, когда продавец переводит заказ в статус "Выполнен". Блокировка средств на карте клиента осуществляется на 30 дней', 'themewoocommerce' ),
							'desc_tip'    => true,
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'themewoocommerce' ),
							'default' => 'no',
							'description' => $debug,
						),
			'only_cart' => array(
							'title' => __( 'Оплачивать только картой', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Включить оплату только картой', 'themewoocommerce' ),
							'default' => 'no',
							//'description' => __( 'Включить оплату только картой', 'themewoocommerce' ),
						)
		);
	}
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order, please click the button below to pay with LiqPay.', 'themewoocommerce').'</p>';
		echo $this->generate_form( $order );
		
	}
	static function add_meta_boxes () {
		add_meta_box( 'saphali-wc-liqpay', __( 'Статус заказа', 'themewoocommerce' ), array( 'liqpay', 'create_box_content' ), 'shop_order', 'side', 'default' );
	}
	static function create_box_content ($order) {
		global $post_id;
		$pm = get_post_meta( $post_id, '_payment_method', true);
		if($pm != 'liqpay') { echo '<style>div#saphali-wc-liqpay.postbox {display: none; } </style>'; return;}
		?>
		<ul class="woocommerce-liqpay">
			<li><a href="<?php  echo wp_nonce_url( admin_url( 'admin-ajax.php?action=status_order_liq_pay&order_id=' . $post_id ), 'after_order_table' . $post_id );  ?>" class="button order-status-liqpay"><?php  _e('Узнать статус заказа', 'themewoocommerce'); ?></a>
		</li>
		</ul>
		<script>
		jQuery('body').delegate('a.order-status-liqpay', 'click', function(event) {
			event.preventDefault();
			var _this = jQuery(this);
			var href = jQuery(this).attr('href');
			_this.parent().find('.data').remove();
			_this.parent().parent().parent().block({'message': 'обработка запроса', css: {'opacity': ".9", 'background': '#fff'}});
			jQuery.getJSON(href, function(data){
				jQuery('a.order-status-liqpay').after('<div class="data">' + data.html + "</div>");
				_this.parent().parent().parent().unblock();
			});
		});
		</script>
		<?php 
	}
	static function acces_to_user_go($order_id) {
		$order = new WC_Order( $order_id );
		if($order->payment_method != 'liqpay') return;
		$LiqPaymID = get_option('merchant_id');
		$LiqPaymKey = base64_decode(strrev(get_option('signature')));
		$liqpay = new LiqPayApi($LiqPaymID, $LiqPaymKey);
		$_res = $liqpay->api("payment/status", array(
		  'version'       => '3',
		  'order_id'      => $order_id
		));
		if( !in_array( $_res->status , array('hold_wait') ) ) return;
			
		$res = $liqpay->api("payment/hold/completion", array(
		'version'       => '3',
		'amount'      => $order->order_total,
		'order_id'      => $order_id
		));
		$status_array = array('success' => 'успешный платеж', 'error' => 'Неуспешный платеж. Некорректно заполнены данные', 'failure' => 'неуспешный платеж', 'wait_secure' => 'платеж на проверке', 'wait_accept' => 'Деньги с клиента списаны, но магазин еще не прошел проверку', 'wait_lc' => 'Аккредитив. Деньги с клиента списаны, ожидается подтверждение доставки товара', 'processing' => 'Платеж обрабатывается', 'sandbox' => 'тестовый платеж', 'subscribed' => 'Подписка успешно оформлена', 'unsubscribed' => 'Подписка успешно деактивирована', 'reversed' => 'Возврат клиенту после списания');
		
		if(isset($res->err_code) && isset($res->err_description)) {
			$order->add_order_note( '<h4>Списание заблокированной суммы</h4>' . 'Ошибка ' . $res->err_code . ': заказ #'. str_replace(array('#', '№'),'', $order->get_order_number() ) .'. ' . ' - ' . $res->err_description . '.' );
		}else  {
			$info[] = 'Статус заказа: ' . $status_array[$res->status];
			$info[] = 'Сумма заказа: ' . $res->amount . ' ' . $res->currency;
			$order->add_order_note( '<h4>Списание заблокированной суммы прошло успешно</h4>' . implode('. ', $info) );
		}
	}
	static function after_order_table_ajax () {
		check_ajax_referer(  'after_order_table' . $_GET['order_id'] , '_wpnonce');
		$LiqPaymID = get_option('merchant_id');
		$LiqPaymKey = base64_decode(strrev(get_option('signature')));
		$liqpay = new LiqPayApi($LiqPaymID, $LiqPaymKey);
		$res = $liqpay->api("payment/status", array(
		  'version'       => '3',
		  'order_id'      => $_GET['order_id']
		));
		if( !(isset($res->status) && $res->status == 'error' ) ) {
			$status_array = array('success' => 'успешный платеж', 'failure' => 'неуспешный платеж', 'wait_secure' => 'платеж на проверке', 'wait_accept' => 'Деньги с клиента списаны, но магазин еще не прошел проверку', 'wait_lc' => 'Аккредитив. Деньги с клиента списаны, ожидается подтверждение доставки товара', 'processing' => 'Платеж обрабатывается', 'sandbox' => 'тестовый платеж', 'subscribed' => 'Подписка успешно оформлена', 'unsubscribed' => 'Подписка успешно деактивирована', 'reversed' => 'Возврат клиенту после списания');
			if( isset($res->err_code) ) 
			$info[] = 'Ошибка '.$res->err_code.': ' . $res->err_description;
			$info[] = 'Статус заказа: ' . $status_array[$res->status];
			$info[] = 'Сумма заказа: ' . $res->amount . ' ' . $res->currency;
			//$info[] = 'Валюта заказа: ' . $res->currency;
		} else {
			if(isset($res->description) && $res->description == 'payment_not_found' ) { $res->description = 'Платеж не найден'; $description = $res->description; }
			if( isset($res->err_code) ) { if($res->err_description == 'payment_not_found' ) $res->err_description = 'Платеж не найден'; $description = 'Ошибка '.$res->err_code.': ' . $res->err_description; }
			die( json_encode( array('html' => $description) ) );
		}
		die( json_encode( array('html' => implode(". \n", $info ) )) );
	}
	static function valid_order_statuses_for_payment($statuses, $order){
		if($order->payment_method != 'liqpay') return $statuses; 
        $name =  'woocommerce_payment_status_action_pay_button_controller';
        $option_value = get_option( 'woocommerce_payment_status_action_pay_button_controller', array() );
        if(!is_array($option_value))
          $option_value = array('pending', 'failed');
		if( !in_array('pending', $option_value) ) 
			 $option_value[] = 'pending';
        return $option_value;
      }
	function successful_request( $posted ) {
		if( isset($_POST['data']) && isset($_POST['signature']) ) {
			$sign = base64_encode(sha1($this->LiqPaymKey . $_POST['data'] . $this->LiqPaymKey,1));
			$data = json_decode( base64_decode ( $_POST['data'] ) );
			$order_id = $data->order_id;
			
			if($sign == $posted['signature']){
				$order = new WC_Order( $order_id );
				$status = $data->status;
				$transaction_id = $data->transaction_id;
				$sender_phone = $data->sender_phone;
				$pay_way = $data->type;
				if ( ! empty( $transaction_id ) && !in_array( $status, array('failure', 'delayed', 'success') ) ) {
					update_post_meta( $order_id, '_transaction_id', $transaction_id, true );
				}
				if($status=='success' ) {
					$order->payment_complete($transaction_id);
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполнена. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполнена.' );
				} elseif($status=='failure') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' отменена или завершилась неудачей.' );
					$order->update_status('failed', __('Awaiting cheque payment', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' отменена или завершилась неудачей. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='error') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' отменена или завершилась неудачей.' );
					$order->update_status('failed', __('Awaiting cheque payment', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' отменена или завершилась неудачей. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='wait_secure') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='wait_compensation') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется. Платеж успешный, будет зачислен в ежесуточной проводке.  Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='hold_wait') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется (удержание).' );
					$order->update_status('on-hold', __('Сумма успешно заблокирована на счету отправителя', 'themewoocommerce'));
					$order->add_order_note( 'Заказ #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' на резервировании. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '. Чтобы получить средства, которые заблокированы на счете покупателя (до 30 дней), необходимо перевести данный заказ в статус "Выполнен" ИЛИ на сайте LiqPay в истории платежей нажав кнопку "Завершить". А чтобы вернуть деньги покупателю, то на сайте LiqPay в истории платежей нажмите "Отменить" ИЛИ на данной странице заказа воспользуйтесь кнопкой "Возврат" -> "Возврат через LiqPay" (под перечнем товаров).'  );
				}elseif($status=='invoice_wait') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется. Инвойс создан успешно, ожидается оплата. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='cash_wait') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется. Ожидается оплата наличными в ТСО. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='wait_credit') {
					if ($this->debug=='yes') $this->log->add( $this->id, 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='delayed') {
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' заторможена (на удержании). Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='processing') {
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='wait_accept') {
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . '. Деньги с клиента списаны, но магазин еще не прошел проверку. Если магазин не пройдет активацию в течение 90 дней, платежи будут автоматически отменены. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($status=='wait_lc') {
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . '. Аккредитив. Деньги с клиента списаны, ожидается подтверждение доставки товара. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				} elseif($status=='sandbox') {
					$order->add_order_note( 'Оплата заказа #' . str_replace(array('#', '№'),'', $order->get_order_number() ) . ' прошла успешно в тестовом режиме (статус при этом прежний). Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				} elseif($status=='reversed') {
					$_status = 'wc-' === substr( $order->post_status, 0, 3 ) ? substr( $order->post_status, 3 ) : $order->post_status;
					if( $data->type != 'hold' && $_status != 'completed' ) {
						$order->update_status('failed', sprintf(__('Оплата не прошла, и производилась с телефона: %s. ID транзакции: %s.', 'themewoocommerce'), $sender_phone, $transaction_id) );
						$nete_user = sprintf('<p>Система оплаты LiqPay завернула платеж. Статус платежа: <strong>возврат клиенту после списания</strong>. ID транзакции: %s. Если есть вопросы, задавайте их на сайте <a href="https://www.liqpay.ua/">https://www.liqpay.ua</a> (справа кнопка “Помощь онлайн”). Проверить свой статус Вы также можете в чате, указав ID транзакции (Проверка платежа -&gt; указываем ID платежа -&gt; кнопка Отправить).</p><p>Вы можете оплатить данный платеж с помощью %s.</p>',  $transaction_id, 'другого метода оплаты.');
						$order->add_order_note( $nete_user, 1 );
					} else {
						if( $_status != 'completed' ) {
							$order->update_status('refunded', sprintf(__('Заблокированная сумма (%s) возвращена клиенту: %s. ID транзакции: %s.', 'themewoocommerce'), $data->refund_amount, $sender_phone, $transaction_id) );
							$nete_user = sprintf('<p>Заблокированная сумма с вашей банковской карты Вам возвращена. ID транзакции: %s.</p>',  $transaction_id );
						} else {
							$order->update_status('refunded', sprintf(__('Списанная сумма (%s) возвращена клиенту: %s. ID транзакции: %s.', 'themewoocommerce'), $data->refund_amount, $sender_phone, $transaction_id) );
							$nete_user = sprintf('<p>Списанная сумма с вашей банковской карты Вам возвращена. ID транзакции: %s.</p>',  $transaction_id );
						}
						
						$order->add_order_note( $nete_user, 1 );
					}
					
				}
				die("OK".$order_id);
			} else {
				if ($this->debug=='yes')
				$this->log->add( $this->id, 'Ответ от сервера. Ошибка: подпись (signature) не соответствует действительности. Заказ #' . $order_id  );
				die(); 
			}
		}
		exit;		
	}
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		//$payment_id = get_post_meta( $order_id, '_transaction_id', true );
		$liqpay = new LiqPayApi($this->LiqPaymID, $this->LiqPaymKey);
		
		$param = array(
		  'version'       => '3',
		  'order_id'      => $order_id
		);
		if(!$amount)
		$param['amount'] = str_replace(array(',', ' '), array('.', ''), $amount);
		else {
			$order = new WC_Order( $order_id );
			$param['amount'] = $order->order_total;
		}
			
	
		$refund = $liqpay->api("payment/refund", $param);

		// $this->log->add( $this->id, 'Refund Result: ' . print_r( $refund, true ) );
		
		if ( !isset($refund->status) || $refund->status == 'error' ) {
			
			$this->log->add( $this->id, 'Refund order id #'.$order_id.'. Код ошибки: ' . $refund->err_code . ' - '. $refund->err_description );
			return false;
		}
		$_res = $liqpay->api("payment/status", array(
		  'version'       => '3',
		  'order_id'      => $order_id
		));
		
		if ( 'reversed' == $refund->status ) {
			if($_res->status == 'success') $inf = 'Возмещение произойдет через некоторое время, т.к. заказ ранее был уже выполнен.';
			if(!isset($order)) $order = new WC_Order( $order_id );
			$order->add_order_note( sprintf( __( 'Refunded %s - Refund status: %s', 'woocommerce' ), $refund->wait_amount . $refund->currency, $refund->status ) . ". \n" . $reason . "\n\n" . $inf );
			return true;
		}
		
		if ( !in_array( $_res->status , array('reversed') ) ) {
			$this->log->add( $this->id, 'Refund order id #'.$order_id.' Неуспешно: ' . 'статус не поменялся на "Возврат". Возможно заказ был уже выполнен. Текущий статус: ' . $_res->status );
		}
		return false;
	}
	function check_callback_lp() {
		if ( strpos($_SERVER["REQUEST_URI"], 'order_results_go')!==false && strpos($_SERVER["REQUEST_URI"], 'wc-api=liqpay')!==false ) {
			
			error_log('LiqPay callback!');
			
			$_REQUEST = stripslashes_deep($_REQUEST);
			
			do_action("valid-liqpay-callback", $_REQUEST);
			
		}
		elseif(strpos($_SERVER["REQUEST_URI"], 'wc-api=liqpay')!==false)
		{
			if($_REQUEST["wc-api"] == $this->id) {
				$orderid=$_GET['order_id'];
				$liqpay = new LiqPayApi($this->LiqPaymID, $this->LiqPaymKey);
				$_res = $liqpay->api("payment/status", array(
				  'version'       => '3',
				  'order_id'      => $orderid
				));
				$order = new WC_Order( $orderid );
				if( in_array($_res->status, array('error', 'failure') ) && "err_api_ip" != $_res->err_code ) {
					wp_redirect(str_replace('&amp;', '&', $order->get_cancel_order_url()));
				} else {
					if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
					wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_view_order_page_id')))));	
				}
				exit; 
			}
		}

//echo add_query_arg('key', $order->order_key, add_query_arg('order', $inv_id, get_permalink(get_option('woocommerce_thanks_page_id'))));

	}
	
		public function admin_options()
		{
		//var_dump(iconv('utf-8','windows-1252//IGNORE','fg 5'));
		//$title = 'Конфигурация Privat24 и LiqPay';
		if (!empty($message)) { ?>
			<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php } ?> <table class="form-table">
			<thead><tr><th scope="row"><h3>LiqPay</h3></th></tr></thead>
						<tr>
							<td class="titledesc"><?php _e('Разрешить оплату LiqPay', 'woocommerce') ?>:</td>
							<td class="forminp">
								<select name="woocommerce_liqpay_enabled" id="woocommerce_liqpay_enabled" style="min-width:100px;">
									<option value="yes" <?php if (get_option('woocommerce_liqpay_enabled') == 'yes') echo 'selected="selected"'; ?> ><?php _e('Yes', 'woocommerce'); ?></option>
									<option value="no" <?php if (get_option('woocommerce_liqpay_enabled') == 'no' || get_option('woocommerce_liqpay_enabled') == '' ) echo 'selected="selected"'; ?> ><?php _e('No', 'woocommerce'); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','woocommerce') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'woocommerce') ?>:</td>
							<td class="forminp">
								<input class="input-text" type="text" name="woocommerce_liqpay_title" id="woocommerce_liqpay_title" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_liqpay_title')) echo $value; else echo 'liqpay'; ?>" />
							</td>
						</tr>
						<?php $this->generate_settings_html(); ?>
						<tr valign="top">
							<th scope="row">ID мерчанта (Публичный ключ)</th>
							<td>
								<input type="text" name="merchant_id" id="merchant_id_api_key" value="<?php echo $this->LiqPaymID; ?>" /> 

							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Подпись мерчанта (Приватный ключ)</th>
							<td>
								<input type="password" name="signature" id="signature_id_api_key" size='70' value="<?php echo $this->LiqPaymKey; ?>" /> 

							</td>
						</tr>	
						<?php 
						$url_pre = site_url("/?"); 
						?>
						<tr valign="top">
						<?php if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {$url_lp = 'order_results_lp=liqpay&wc-api=liqpay';} else $url_lp = 'wc-api=liqpay'; ?>
							<th scope="row">RESULT_URL (страница на которую вернется клиент)</th>
							<td>
								<input type="text" name="result_url" id="result_url_id_api_key" size='70' value="<?php $serv=get_option('result_url'); if(empty($serv))  echo $url_pre . $url_lp; else echo $serv; ?>" /> 

							</td>
						</tr>
						<tr valign="top">
							<th scope="row">SERVER_URL (страница на которую придет ответ от сервера)</th>
							<td>
								<input type="text" name="server_url" id="server_url_id_api_key" size='70' value="<?php $serv=get_option('server_url'); if(empty($serv))  echo $url_pre . $url_lp . '&order_results_go=1'; else echo $serv; ?>" /> 

							</td>
						</tr>
						<!--
						<tr valign="top">
							<th scope="row">Методы оплаты (LiqPay)</th>
							<td border='1px'>
								<label> C карты <input type="checkbox" name="card" id="card_api_key" value="1" <?php if(get_option('card', 1)) echo 'checked'; ?> /> </label>
								<label> C телефона <input type="checkbox" name="liqpayc" id="liqpay_api_key" value="1" <?php if(get_option('liqpayc', 1)) echo 'checked'; ?> /> </label>
								<label> Наличными  <input type="checkbox" name="delayed" id="delayed_api_key" value="1" <?php if(get_option('delayed', 1)) echo 'checked'; ?> /> </label>								
							</td>
						</tr> -->
						<tr valign="top">
							<th scope="row"><label for="is_lang_liqpay_en">Страница оплаты на английском языке</label></th>
							<td border='1px'>
								 <input type="checkbox" name="is_lang_liqpay_en" id="is_lang_liqpay_en" value="1" <?php if(get_option('is_lang_liqpay_en')) echo 'checked'; ?> /> 					
							</td>
						</tr>
						</table>
<?php

	}
		public function process_admin_options () {
			if($_POST['woocommerce_liqpay_title']) {
				update_option('merchant_id',$_POST['merchant_id']);
				update_option('signature',strrev(base64_encode($_POST['signature'])));
				update_option('result_url',$_POST['result_url']);
				update_option('server_url',$_POST['server_url']);
				
				
				
				if(isset($_POST['card']))
					{
						update_option('card',$_POST['card']);
					} else delete_option('card');
					
				if(isset($_POST['is_lang_liqpay_en']))
				{
					update_option('is_lang_liqpay_en', $_POST['is_lang_liqpay_en']);
				} else delete_option('is_lang_liqpay_en');
				
				if(isset($_POST['liqpayc']))
				{
					update_option('liqpayc',$_POST['liqpayc']);
				} else delete_option('liqpayc');
				if(isset($_POST['delayed'])) {
					update_option('delayed',$_POST['delayed']);
				} else delete_option('delayed');
				
				if(isset($_POST['woocommerce_liqpay_enabled'])) update_option('woocommerce_liqpay_enabled', woocommerce_clean($_POST['woocommerce_liqpay_enabled'])); else @delete_option('woocommerce_liqpay_enabled');
				if(isset($_POST['woocommerce_liqpay_title'])) update_option('woocommerce_liqpay_title', woocommerce_clean($_POST['woocommerce_liqpay_title'])); else @delete_option('woocommerce_liqpay_title');
				
					$this->validate_settings_fields();

					if ( count( $this->errors ) > 0 ) {
						$this->display_errors();
						return false;
					} else {
						if( version_compare( WOOCOMMERCE_VERSION, '2.6', '<' ) ){
							update_option( $this->plugin_id . $this->id . '_settings', $this->sanitized_fields );
							return true;
						}
						else {
							$post_data = $this->get_post_data();
							$form_fields = array_keys( $this->form_fields );
							foreach($form_fields as $array_keys) {
								$this->settings[ $array_keys ] = isset( $post_data['woocommerce_' . $this->id . '_' . $array_keys]) ?  $post_data['woocommerce_' . $this->id . '_' . $array_keys] : (isset( $post_data[$array_keys] ) ?  $post_data[$array_keys]  :  $this->form_fields[$array_keys]['default']);
								if( in_array($array_keys, array('enabled','debug','only_cart','sandbox','form_submission_method')) ) {
									$this->settings[ $array_keys ] = $this->get_field_value( $array_keys, $this->settings[ $array_keys ], $post_data ) ? 'yes' : 'no';
								}
							}
							update_option( $this->plugin_id . $this->id . '_settings', $this->settings );
						}
						return true;
					}
			}
		}
	function is_valid_for_use() {
		if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) )     $version = SAPHALI_PLUGIN_VERSION_ST; 
		elseif( defined( 'SAPHALI_PLUGIN_VERSION_LP' ) ) $version = SAPHALI_PLUGIN_VERSION_LP; else  $version ='1.0';
		$args = array(
			'method' => 'POST',
			'plugin_name' => "payment-liqpay", 
			'version' => $version,
			'username' => home_url(), 
			'password' => '1111',
			'action' => 'pre_saphali_api'
		);
		$response = $this->prepare_request( $args );
		if(isset($response->errors) && $response->errors) { return false; } else {
			if( ($response["response"]["code"] == 200 && $response["response"]["message"] == "OK")  || ($response["response"]["code"] == 200 && isset($response['body'])) ) {
				if( strpos($response['body'], '<') !== 0 )
				return true;
			}else {
				return false;
			}
		}
        return $is_valid_for_use;
    }
	function prepare_request( $args ) {
		$request = wp_remote_post( $this->api_url, array(
			'method' => 'POST',
			'timeout' => 45,
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
	public function generate_form( $order_id ) {
	    if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else
		$order = new WC_Order( $order_id );


		if ($this->debug=='yes') $this->log->add( $this->id, 'Создание платежной формы для заказа #' . $order_id . '.');
		$descRIPTION = $descRIPTION_ = '';
		
		$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );
		$count  = 0 ;
		foreach ( $order_items as $item_id => $item ) {
		
		$descRIPTION_ .= esc_attr( $item['name'] );
		$v = explode('.', WOOCOMMERCE_VERSION);
		if($v[0] >= 2) {
			if ( $metadata = $order->has_meta( $item_id )) {
						$_descRIPTION = '';
						$is_ = false;
						$is_count = 0;
						foreach ( $metadata as $meta ) {

							// Skip hidden core fields
							if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
								'_qty',
								'_tax_class',
								'_product_id',
								'_variation_id',
								'_line_subtotal',
								'_line_subtotal_tax',
								'_line_total',
								'_line_tax',
							) ) ) ) continue;

							// Handle serialised fields
							if ( is_serialized( $meta['meta_value'] ) ) {
								if ( is_serialized_string( $meta['meta_value'] ) ) {
									// this is a serialized string, so we should display it
									$meta['meta_value'] = maybe_unserialize( $meta['meta_value'] );
								} else {
									continue;
								}
							}
							$is_ = true;
							if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { global $woocommerce; $meta['meta_key'] = $woocommerce->attribute_label( $meta['meta_key'] );} else { $meta['meta_key'] = wc_attribute_label($meta['meta_key']);}
							if($is_count == 0)
							$_descRIPTION .= esc_attr(' ['.$meta['meta_key'] . ': ' . $meta['meta_value'] );
							else
							$_descRIPTION .= esc_attr(', '.$meta['meta_key'] . ': ' . $meta['meta_value'] );
							$is_count++;
						}
						if($is_count > 0)
						$_descRIPTION = $_descRIPTION. '] - '.$item['qty']. '';
						else $_descRIPTION = $_descRIPTION. ' - '.$item['qty']. '';
					}
					if(($count + 1) != count($order_items) && !empty($descRIPTION_)) $descRIPTION .=  $descRIPTION_.$_descRIPTION . ', '; else $descRIPTION .=  ''.$descRIPTION_.$_descRIPTION; 
					$count++;
					$descRIPTION_ = $_descRIPTION = '';
			}else {
			
				if ( $metadata = $item["item_meta"]) {
					$_descRIPTION = '';
					foreach($metadata as $k =>  $meta) {
						if($k == 0)
						$_descRIPTION .= esc_attr(' - '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '');
						else {
							$_descRIPTION .= esc_attr('; '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '');
						}
					}
				}
				if($item_id == 0)$descRIPTION = esc_attr( $item['name'] ) . $_descRIPTION .' ('.$item["qty"].')'; else
				$descRIPTION .= ', '. esc_attr( $item['name'] ) . $_descRIPTION .' ('.$item["qty"].')';
			}
		}
		$_descRIPTION_ = sprintf (__('Payment of the order #%s', 'themewoocommerce') , str_replace(array('#', '№'),'', $order->get_order_number() ) );
		global $sitepress;
		if(is_object($sitepress)) {
			if($sitepress->get_current_language() == "ru") {  $lang = 'ru'; $_descRIPTION_ = __('Оплата заказа ', 'themewoocommerce') . $order->get_order_number(); } else $lang = 'en';
		} else {
		if($this->is_lang_liqpay_en) { $lang = 'en'; } else $lang = 'ru';
		}
		if(get_woocommerce_currency() == "RUR") $get_woocommerce_currency = 'RUB'; else $get_woocommerce_currency = get_woocommerce_currency();
		$this->LiqPayUrl = $this->LiqPayUrl . "&order_id=" . $order_id;
		
		if(! $this->form_submission_method ) {
			$click_on = 'jQuery("input#submit_dibs_payment_form").trigger("click"); ';
		} else $click_on = '';
		
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { global $woocommerce; $woocommerce->add_inline_js(' jQuery("form#liqpayform").attr("action", jQuery("form#liqpayform").attr("action").replace(/^\/\//g,"https://") );
			'. $click_on);
		} else 
			wc_enqueue_js (' jQuery("form#liqpayform").attr("action", jQuery("form#liqpayform").attr("action").replace(/^\/\//g,"https://") ); '. $click_on);
	
		$liqpay = new LiqPayApi($this->LiqPaymID, $this->LiqPaymKey);
		$param = array(
		  'version'        => '3',
		  'amount'         => number_format( apply_filters( 'woocommerce_order_amount_total', (double) $order->order_total, $order ) , 2, '.', ''),
		  'currency'       => apply_filters('woocommerce_get_order_currency', $get_woocommerce_currency, $order),
		  'description'    => $_descRIPTION_,
		  'order_id'       => $order_id,
		  'result_url'     => $this->LiqPayUrl,
		  'server_url'     => $this->LiqPayUrlcall,
		  //'pay_way'        => 'card,liqpay,delayed,invoice,privat24',
		  //'type'      	   => 'buy',
		  'action'      	   => $this->action,
		  'info'      	   => $descRIPTION,
		  'language'       => $lang,
		  'order_id'       => $order_id,
		);
		if($this->sandbox) $param['sandbox'] = $this->sandbox;
		if($this->only_cart) $param['pay_way'] = 'card';
		 
		$html = $liqpay->cnb_form($param);
		echo $html . ' <a class="button cancel"  style="float: left;" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>';
	}
	
	
	function process_payment( $order_id ) {
		
		if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
		if ( 0 ) {
		if ($this->debug=='yes') $this->log->add( $this->id, 'Создание платежной формы для заказа #' . $order_id . '.');
			return array(
				'result' 	=> 'success',
				'redirect'	=> $liqpay_adr . $liqpay_args
			);
		} else {
			if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) )
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
		}
	}
	static public function plugin_manage_link( $actions, $plugin_file, $plugin_data, $context ) {
		if(version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) )
			$page  = 'woocommerce';
		elseif( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) $page  = 'woocommerce_settings';
		else $page  = 'wc-settings';
		
		return array_merge( array( 'configure' => '<a href="' . admin_url( 'admin.php?page='.$page.'&tab=checkout&section=liqpay' ) . '">' . __( 'Настройка', 'themewoocommerce' ) . '</a>' ), 
		$actions );
	}
}
?>