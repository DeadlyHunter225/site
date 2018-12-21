<?php
/*
Plugin Name: woo sub-category thumb
Plugin URI: plugins.googlestack.com
Description: This plugin will show subcategory thumbnail on parent category page .
Version: 1.0
Author: raficsedu
Author URI: raficsedu.googlestack.com
*/

add_action( 'woocommerce_before_shop_loop' , 'wsct_subcategory_thumb' );
function wsct_subcategory_thumb(){
	if(is_product_category()){
		//$terms = get_the_terms( $post->ID, 'product_cat' );
		global $wp_query;
		// get the query object
		$cat_obj = $wp_query->get_queried_object();
  
			$args = array(
			   'hierarchical' => 1,
			   'show_option_none' => '',
			   'hide_empty' => 0,
			   'parent' => $cat_obj->term_id,
			   'taxonomy' => 'product_cat'
			);
			$subcats = get_categories($args);
			if(!empty($subcats)){
				echo '<div class="wsct_wrapper">';
				echo '<div class="wsct_title"><h3>Sub-Categories</h3></div>';
				foreach ($subcats as $sc) {
					$category_thumbnail = get_woocommerce_term_meta($sc->term_id, 'thumbnail_id', true);
					$image = wp_get_attachment_url($category_thumbnail);
					if($image==''){
						$image = plugins_url( 'skin/images/No_image.png', __FILE__ );
					}
					$link = get_term_link( $sc->slug, $sc->taxonomy );
					echo '<div class="wsct_single_cat">';
					echo '<div class="product-image">
						<a href="'.$link.'">
							<div class="inner"><img src="'.$image.'" alt="Placeholder" class="woocommerce-placeholder wp-post-image"></div>       
						</a>
					</div>';
					echo '<div class="wsct_cat_title"><a href="'. $link .'">'.$sc->name.'</a></div>';
					echo '</div>';
				}
				echo '</div>';
			}
	}
}

add_action( 'wp_enqueue_scripts', 'wsct_scripts' );
function wsct_scripts(){
	wp_enqueue_style( 'wsct_style', plugins_url('skin/css/wsct_style.css', __FILE__) );
}
?>