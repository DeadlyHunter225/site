<div class="wrap">
	<div id="icon-edit" class="icon32 icon32-posts-product"><br /></div>
	<h2 class="nav-tab-wrapper">
		<a class='nav-tab ex' onclick="return false;">Buy Now</a>
	</h2>
	<div id="export" class="tab_content">
	<h3 style="margin: 1em 0;"><a href="/wp-admin/admin.php?page=woo-saphali-buy-now" <?php if(!isset($_GET['order'])) echo 'class="active"' ?> >Настройки плагина "Купить в один клик"</a> | <a href="/wp-admin/admin.php?page=woo-saphali-buy-now&order" <?php if(isset($_GET['order'])) echo 'class="active"' ?> ><?php _e('Заказы', 'saphali-quick-order'); ?></a></h3>
<?php if(isset($_GET['order']) ) : 
	$_POST['pos'] = !isset($_POST['pos']) ? 0 : $_POST['pos'] ;
	$_POST['pos'] = isset($_GET['paged_n']) ? $_GET['paged_n'] : $_POST['pos'];
	?>
		<table class="wp-list-table widefat fixed posts">
		 <thead>
			<tr valign="top">
				<th scope="row" width="45px">
					<input type="checkbox" class="all-select" />
				</th>
				<th scope="row" width="45px">
					<label for="order"><?php _e('№ заказа', 'saphali-quick-order'); ?></label>
				</th>
				<th scope="row" width="80px">
					<label for="name"><?php _e('Имя', 'saphali-quick-order'); ?></label>
				</th>
				<th scope="row" width="112px">
					<label for="phone"><?php _e('Телефон', 'saphali-quick-order'); ?></label>
				</th>
				<th scope="row" width="120px">
					<label for="mail-to"><?php _e('E-mail', 'saphali-quick-order'); ?></label>
				</th>
				<th scope="row">
					<label for="products"><?php _e('Сведения о заказе', 'saphali-quick-order'); ?></label>
				</th>
				<th>
					<div><button class="delete-top button-secondary">Удалить выбранное</button></div>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			global $wpdb;
			$sql = " SELECT *
				  FROM {$wpdb->prefix}saphali_log_order WHERE is_quick = 0 ORDER BY id DESC
				" . ' LIMIT '. 50*($_POST['pos']) . ' , 50';
			$active_rows = $wpdb->get_results(
				$sql
			); 
			$count = $wpdb->get_row("SELECT COUNT(*) as all_count FROM {$wpdb->prefix}saphali_log_order WHERE is_quick = 0", OBJECT );
			$i=0;
			foreach($active_rows as $val) { 
			
			?>
			<tr valign="top" <?php if($i % 2 == 0) echo 'class="old"'; ?> >
				<th scope="row">
					<input type="checkbox" class="delete-item" name="delete[]" value="<?php echo $val->id; ?>" />
				</th>
				<th scope="row">
					<strong><?php echo $val->order_num; ?></strong>
				</th>
				<th scope="row">
					<?php echo $val->name; ?>
				</th>
				<th scope="row">
					<?php echo $val->phone; ?>
				</th>
				<th scope="row">
					<?php echo $val->email; ?>
				</th>
				<th scope="row">
					<?php echo '<center><u>Дата заказа: ' . $val->date . '</u></center> ' . $val->products; ?>
				</th>
				<th scope="row">
					<button name="delete" class="button-secondary" value="<?php echo $val->id; ?>">удалить</button>
				</th>
			</tr>
			<?php $i++; } ?>
		   </tbody>
		   <tfoot>
			<tr>
			<th colspan="7"> 
			<?php if(!$count->all_count) echo 'Пусто'; if( isset($count->all_count) && ceil($count->all_count/50) > 1) { ?><div class="clear nav">
Страница:
	<?php
	$i=0;
	
	for( $i; $i < ceil($count->all_count/50); $i++ ) { ?>
	<a href="./<? if(!empty($_SERVER["QUERY_STRING"])) { $str = explode('&paged_n', $_SERVER["QUERY_STRING"]); if(empty($str[0])) $str = $str[0]; else $str = $str[0] . '&'; } else $str = '';  if($i==0) echo '?' . str_replace('&order&', '&order', $str); else echo '?' . $str . 'paged_n=' . $i; ?>" <?php if( (!isset($_POST['pos']) && $i==0) || ( isset($_POST['pos']) && $_POST['pos'] == $i) ) echo 'style="font-weight: bold" selected="selected"'; ?>><?php echo $i+1; ?> </a>
               
	<?php } ?></div> 
 <?php 
} ?> <div style="float: right;"><button class="delete button-secondary">Удалить выбранное</button></div>
			</th>
		</tr>
	</tfoot>
</table>	
<style>
tr.old {
	 background: #bdd6e3 none repeat scroll 0 0;
}
</style>
<script>
jQuery("body").delegate('input.delete-item','click', function(event) {
	var c = false;
	jQuery('input.delete-item').each(function (i,e) {
		if( jQuery(this).is(":checked") ) {
			 c = true; return false;
		}
	});
	if(c) jQuery('button.delete, button.delete-top').show();
	else  jQuery('button.delete, button.delete-top').hide();
});
jQuery('button.delete, button.delete-top').hide();
jQuery("body").delegate('input.all-select','click', function(event) {
	var c = false;
	jQuery('input.delete-item').each(function (i,e) {
		if( jQuery(this).is(":checked") ) {
			jQuery(this).attr("checked", false);
		} else {
			c = true;
			jQuery(this).attr("checked", "checked");
		}
	});
	if(c) jQuery('button.delete, button.delete-top').show();
	else  jQuery('button.delete, button.delete-top').hide();
});
jQuery("body").delegate('button.delete, button.delete-top','click', function(event) {
	event.preventDefault();
	var f = new Array();
	var c = 0;
	jQuery('input.delete-item').each(function (i,e) {
		if( jQuery(this).is(":checked") ) {
			f[c] = e.value;
			c++;
		}
	});
	if(f.length == 0) { alert('Нужно выбрать по крайней мере один заказ.'); return;}
	if ( confirm("Вы подтверждаете удаление?")) {
		jQuery.getJSON(
			'<?php echo admin_url('admin-ajax.php');?>?action=saphali_delete_order_buy_new&security=<?php echo wp_create_nonce( "save-filds" );?>&orders='+f,
			function(data) {
				// Check money.js has finished loading:
				if ( typeof data !== "undefined" ) {
					if (data.result === true) {
						jQuery.each(data.id, function (i,e) {
							jQuery('input.delete-item[value="'+e+'"]').parent().parent().remove();
						});
					} else {
						alert('Запрос на удаление не выполнен. Возможно заказ уже удален.');
					}
				}
			}
		);
	}
});
jQuery("body").delegate('button[name="delete"]','click', function(event) {
	event.preventDefault();
	if ( confirm("Вы подтверждаете удаление?")) {
		jQuery.getJSON(
			'<?php echo admin_url('admin-ajax.php');?>?action=saphali_delete_order_buy_new&security=<?php echo wp_create_nonce( "save-filds" );?>&order='+jQuery(this).val(),
			function(data) {
				// Check money.js has finished loading:
				if ( typeof data !== "undefined" ) {
					if (data.result === true) {
						jQuery.each(data.id, function (i,e) {
							jQuery('input.delete-item[value="'+e+'"]').parent().parent().remove();
						});
					} else {
						alert('Запрос на удаление не выполнен. Возможно заказ уже удален.');
					}
				}
			}
		);
	}
});
</script>
	<?php else : ?>	

	<div class="wrap woocommerce">
	<form id="mainforfm" action="" method="post">
		<?php 
		if(isset($_POST['saphali_payments_ex'])) {
			
			if(!update_option('_saphali_post_admin_buy_now', $_POST['_saphali_post_admin_buy_now'] ))
			add_option('_saphali_post_admin_buy_now', $_POST['_saphali_post_admin_buy_now'] );
			
			if(!update_option('_saphali_mail_admin_callme', $_POST['_saphali_mail_admin_callme'] ))
			add_option('_saphali_mail_admin_callme', $_POST['_saphali_mail_admin_callme'] );
			
			if(!update_option('_saphali_post_admin_buy_now_p', $_POST['_saphali_post_admin_buy_now_p'] ))
			add_option('_saphali_post_admin_buy_now_p', $_POST['_saphali_post_admin_buy_now_p'] );
			
			if(!update_option('buy_now_array_page', $_POST['buy_now_array_page'] ))
			add_option('buy_now_array_page', $_POST['buy_now_array_page'] );
			
			if(!update_option('_saphali_post_admin_buy_now_bg_color', $_POST['_saphali_post_admin_buy_now_bg_color'] ))
			add_option('_saphali_post_admin_buy_now_bg_color', $_POST['_saphali_post_admin_buy_now_bg_color'] );
			$_POST['_saphali_catalog_botton'] = isset($_POST['_saphali_catalog_botton']) ? $_POST['_saphali_catalog_botton'] : '';
			$_POST['_saphali_not_botton_cb'] = isset($_POST['_saphali_not_botton_cb']) ? $_POST['_saphali_not_botton_cb'] : '';
			if(!update_option('_saphali_catalog_botton', $_POST['_saphali_catalog_botton'] ))
			add_option('_saphali_catalog_botton', $_POST['_saphali_catalog_botton'] );
			if(!update_option('_saphali_not_botton_cb', $_POST['_saphali_not_botton_cb'] ))
			add_option('_saphali_not_botton_cb', $_POST['_saphali_not_botton_cb'] );
			
			foreach($_POST['filials']['name'] as $k => $v) {
				if(!empty($v) && !empty($_POST['filials']['mail'][$k]))
				$post['filials'][] = array('name' => $v, 'mail' => $_POST['filials']['mail'][$k] );
			}
			$post['filials_anabled'] = isset($_POST['filials']['filials_anabled']) && $_POST['filials']['filials_anabled'] ? true : false;
			update_option('_saphali_filials_bn', $post );
		}
		$filials_bn = get_option('_saphali_filials_bn', array() );
		$select = get_option('_saphali_catalog_botton', '');
		$array_page = get_option('buy_now_array_page', array());
		$select_cb = get_option('_saphali_not_botton_cb', 1);
		$e_selected_f_bn = isset($filials_bn['filials_anabled']) && $filials_bn['filials_anabled'] ? ' checked="checked"': '';
		$e_selected = empty($select) ? '': ' checked="checked"';
		$e_selected_callback = empty($select_cb) ? '': ' checked="checked"';
		?>
		<table class="form-table">
			<thead>
			<tr><td colspan="2"></td></tr>
			</thead>
			<tbody>
				<tr valign="top" class="single_select_page">
					<th scope="row" class="titledesc">E-mail для уведомлений</th>
					<td class="forminp">
						<label for="filials_anabled"><input type="checkbox" id="filials_anabled" name="filials[filials_anabled]" value="1" <?php echo $e_selected_f_bn; ?> /> 
						Использовать филиалы (покупатель может выбрать филиал и заказ будет отправлен менеджеру этого филиала)</label>
						
						<div class="nofilials"><br /><input type="text" name="_saphali_mail_admin_callme" value="<?php  echo get_option( '_saphali_mail_admin_callme', get_the_author_meta('user_email', 1) ); ?>" /><br />
						На этот электронный адрес будут приходить письма.
						</div>
						<?php 
						
						echo '<table style="width:100%;max-width: 500px;" id="filials">';
						if( isset($filials_bn['filials']) && sizeof($filials_bn['filials']) > 0 ) {
							echo '<tbody>';
							$count = 0;
						foreach($filials_bn['filials'] as $filials) {
							if(!is_array($filials)) continue; 
							$count++;
							echo '
							<tr>
								<td style="width:50%;padding: 0;margin: 0; vertical-align: top"><input type="text" name="filials[name][]" value="' . $filials['name'] . '" /><br />
					Название филиала ('.$count.').</td>
								<td style="width:50%;padding: 0;margin: 0; vertical-align: top"><input type="text" name="filials[mail][]" value="' . $filials['mail'] . '" /><br />
					Электронный адрес менеджера филиала ('.$count.').</td>
							</tr>
							';
						} 
						echo '</tbody>';
						}
						else {
							echo '
							<tbody>
							<tr>
								<td style="width:50%;padding: 0;margin: 0; vertical-align: top"><input type="text" name="filials[name][]" value="" /><br />
					Название филиала (1).</td>
								<td style="width:50%;padding: 0;margin: 0; vertical-align: top"><input type="text" name="filials[mail][]" value="" /><br />
					Электронный адрес менеджера филиала (1).</td>
							</tr>
							</tbody>
							';
						}
						?><tfoot><tr><td colspan="2"><button class="add_filials">Добавить еще+</button></td></tr></tfoot><?php
						echo '</table>';
					?>
					</td>
				</tr>
				<tr valign="top" class="single_select_page">
					<th scope="row" class="titledesc">Стиль</th>
					<td class="forminp">
						<div class="color_box"><strong>Цвет формы</strong>
							<input type="text" class="colorpick" value="<?php  echo get_option( '_saphali_post_admin_buy_now_bg_color' , "#f7f6f7" ); ?>" id="_saphali_post_admin_buy_now_bg_color" name="_saphali_post_admin_buy_now_bg_color"> <div  style="display: none;position: absolute; z-index: 7; background: #fff;" class="colorpickdiv" id="colorPickerDiv_saphali_post_admin_buy_now_bg_color"></div>
						</div>
<script type="text/javascript">
				jQuery(window).load(function(){
					jQuery("body").delegate("button.add_filials", 'click', function(event){
						event.preventDefault();
						var _this = jQuery(this).parent().parent().parent().parent();
						_this.find("tbody tr:last").after('<tr>\
									<td style="width:50%;padding: 0;margin: 0; vertical-align: top"><input type="text" name="filials[name][]" value="" /><br />\
						Название филиала ('+ (_this.find("tbody tr") .length + 1) +').</td>\
									<td style="width:50%;padding: 0;margin: 0; vertical-align: top"><input type="text" name="filials[mail][]" value="" /><br />\
						Электронный адрес менеджера филиала ('+ (_this.find("tbody tr") .length + 1) +').</td>\
								</tr>');
					});
					jQuery("body").delegate("#filials_anabled", 'click', function(){
						if( jQuery(this).is(":checked") ) {
							jQuery("div.nofilials").hide();
							jQuery("table#filials").show();
						} else {
							jQuery("div.nofilials").show();
							jQuery("table#filials").hide();
						}
					});
					if( jQuery("#filials_anabled").is(":checked") ) {
						jQuery("div.nofilials").hide();
						jQuery("table#filials").show();
					} else {
						jQuery("div.nofilials").show();
						jQuery("table#filials").hide();
					}
					jQuery('.colorpick').each(function(){
						jQuery('.colorpickdiv', jQuery(this).parent()).farbtastic(this);
						jQuery(this).click(function() {
							if ( jQuery(this).val() == "" ) jQuery(this).val('#');
							jQuery('.colorpickdiv', jQuery(this).parent() ).show();
						});
					});
					jQuery(document).mousedown(function(){
						jQuery('.colorpickdiv').hide();
					});
				});
			</script>
					</td>
				</tr>
				<tr valign="top" class="single_select_page">
					<th scope="row" class="titledesc">Использовать поясняющую/поощряющую надпись в форме</th>
					<td class="forminp">
						<textarea name="_saphali_post_admin_buy_now" cols="60" placeholder="" /><?php  echo get_option( '_saphali_post_admin_buy_now', '' ); ?></textarea><br />
						Например, <em>"Заполните эту простую форму и наш представитель с Вами свяжется для уточнения условий оплаты и доставки!"</em>.
						<br/>
						<?php  $buy_now_pos = get_option( '_saphali_post_admin_buy_now_p', 2 ); ?>
						<strong>Отобразить ее в форме</strong>: внизу <input type="radio" name="_saphali_post_admin_buy_now_p" value="1" <?php  checked($buy_now_pos, 1); ?> /> вверху <input type="radio" name="_saphali_post_admin_buy_now_p" value="2" <?php  checked($buy_now_pos, 2); ?> />. 
					</td>
				</tr>
				<tr valign="top" class="single_select_page">
					<th scope="row" class="titledesc">Выводить кнопку в каталоге</th>
					<td class="forminp">
						<input type="checkbox" name="_saphali_catalog_botton" value="1" <?php echo $e_selected; ?> /><br />
						Выводит кнопку только для простых товаров (не для вариативных).
					</td>
				</tr>
				<tr valign="top" class="single_select_page">
					<th scope="row" class="titledesc">Страницы, на которых выводятся товары</th>
					<?php 
					$args = array(
    'depth'                 => 0,
    'child_of'              => 0,
    'selected'              => 0,
    'echo'                  => 0,
    'name'                  => 'buy_now_array_page[]',
    'id'                    => '', // string
    'show_option_none'      => null, // string
    'show_option_no_change' => null, // string
    'option_none_value'     => null, // string
);
					?>
					<td class="forminp">
						 <?php 
						 if(!empty($array_page))
						 foreach( $array_page as $page_id ) {
							$a_s[] = "value=\"$page_id\"";
							$a_r[] = "value=\"$page_id\" selected=\"selected\"";
						 }
						 
						 $wp_dropdown_pages = str_replace($a_s, $a_r, wp_dropdown_pages( $args ) ); echo str_replace('<select', '<select multiple="multiple" ',$wp_dropdown_pages ); ?> <br />
						Требуется, например, когда шорткодом выводить определенные товары
					</td>
				</tr>
				<tr valign="top" class="single_select_page">
					<th scope="row" class="titledesc">Не выводить кнопку, когда доступен под заказ (при установленном плагине Интерактив 3 в 1)</th>
					<td class="forminp">
						<input type="checkbox" name="_saphali_not_botton_cb" value="1" <?php echo $e_selected_callback; ?> /><br />
						Опция может быть затребована при активированном плагине - Интерактив 3 в 1.
					</td>
				</tr>
				</tbody>
		</table>

		
		<script type='text/javascript'>
			//	
		</script>

		<p><input type="submit"  value="Сохранить" name="saphali_payments_ex" class="button-primary" /></p>
	</form>
</div>
	<?php endif; ?>
	<style>
	h3 a.active {
		color: black;
		cursor: default;
		font-weight: bold;
		text-decoration: none;
	}
	tr.old th[scope="row"] {
		border-bottom: 2px solid #343333;
		border-top: 2px solid #343333;
	}
	tr.old:first-child th[scope="row"] {
		border-top: medium none;
	}
	tr:last-child.old th[scope="row"] {
		border-bottom: medium none;
	}
	</style>

</div>
</div>