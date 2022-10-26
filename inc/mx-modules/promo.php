<?php

////акции  (срабатывают при добавлении товара в корзину)
function get_all_active_stocks(){
	$args = array(
		'posts_per_page'   => -1,
		'orderby'          => 'title',
		'order'            => 'asc',
		'post_type'        => 'promo',
		'post_status'      => 'publish',
		'meta_key'         => '',
		'meta_value'  =>'',
	);
	$stocks = [];
	$promos = get_posts( $args );
	foreach ($promos as &$post) {
		
		$stock_type = get_post_meta($post->ID, 'stock_type', true);
		$sklad = get_post_meta($post->ID, 'sklad_promo', true);
		switch($stock_type){
			case 'one_category_product_free':
				$categoryes_data = [];
				$i=0;
				$code = get_post_meta($post->ID, 'stock_code', true);
				while( $category = get_post_meta($post->ID, 'free_product_fields_'.$i.'_category', true) ){
					//$category = get_post_meta($post->ID, 'free_product_fields_'.$i.'_category', true);
					$product_min_quantity = get_post_meta($post->ID, 'free_product_fields_'.$i.'_product_min_count', true);
					$replay = get_post_meta($post->ID, 'free_product_fields_'.$i.'_replay', true);					
					$categoryes_data[]=[
						'category_id' => $category,
						'product_min_quantity' => $product_min_quantity,
						'replay' => $replay,						
					];
					$i++;
				}
				$stocks[]=[
					'ID' => $post->ID,
					'post_title' => $post->post_title,
					'post_status' => $post->post_status,
					'post_date' => $post->post_date,
					'stock_data'=>[
						'code' => $code,
						'type' => $stock_type,
						'sklad' => $sklad,						
						'categoryes_data' => $categoryes_data,
					]
				];
			break;
		}
	}

	return $stocks;
}

/*API получение всех акций */
add_action( 'rest_api_init', function () {
	register_rest_route( 'systeminfo/v1', '/stocks', array( //регистрация маршрута
		'methods'             => 'GET',
		'callback'            => 'get_all_active_stocks'
	) );
});

/* Отключаем купоны, если есть активная акция */
add_filter( 'woocommerce_coupons_enabled', 'truemisha_coupon_field_on_checkout' );
 function truemisha_coupon_field_on_checkout( $enabled ) {
	//echo '___'.WC()->session->get('active_promo');

	// if( !is_admin() && ! defined( 'DOING_AJAX' ) )
	// {  
	// 	if( WC()->session->get('active_promo') ) {
	// 		$enabled = false; // купоны отключены
	// 		WC()->cart->remove_coupons( );//Удалим всё применённые купоны
	// 	}
	// }
	
	return $enabled;
}

/* обработка акций */
add_action('woocommerce_cart_calculate_fees' , 'custom_item_discount', 10, 1);
function custom_item_discount( $cart_object  ){
	/* АКЦИИ */
	WC()->session->set('apply_promos_list', []) ;
	WC()->session->set('active_promo', false);
	/* Получим настройки взаимодействия купонов и акций*/
	$promo_summing = get_field('promo_summing', 'option');
	$coupons_summing = get_field('coupons_summing', 'option');
	$coupons_and_promo_summing = get_field('coupons_and_promo_summing', 'option');
	$auto_coupons_and_promo_summing = get_field('auto_coupons_and_promo_summing', 'option');
	$auto_coupons_and_promo_priority = WC()->session->get('auto_coupons_and_promo_priority');
	$auto_apply_coupons_list = WC()->session->get('auto_apply_coupons_list');
	$auto_coupons_summing = get_field('auto_coupons_summing', 'option') == "true" ? true : false ;

	if( WC()->session->get('disable_promo') == true ) return false;
// if(!empty($_GET['debugg']))
// echo count( get_avaible_auto_coupons( $auto_coupons_summing ) ).'__='.$auto_coupons_and_promo_priority;
	if( $auto_coupons_and_promo_priority == 'auto_coupons' && count( get_avaible_auto_coupons( $auto_coupons_summing ) ) > 0 ){
// if(!empty($_GET['debugg']))
// echo 'promo_cansel';
		return false;
	}

	$avaible_promos = get_avaible_promos( $promo_summing, $cart_object );

	if($avaible_promos){
		foreach($avaible_promos as $stock){
			$cart_object->add_fee( 'Акция &laquo;'.$stock['title'].'&raquo;', $stock['price'], true, 'standard' );
		}
		WC()->session->set('active_promo', true);
	}
	WC()->session->set('apply_promos_list', $avaible_promos) ;
	
}

function get_avaible_promos( $promo_summing , $cart_object){
		/* Собираем данные для оценки */
		$data_for_stocks = [];
		$added_promos = [];

		foreach( $cart_object->get_cart_contents() as $cart_item ){
			$product_cats = get_the_terms( $cart_item['product_id'], 'product_cat' );
			foreach($product_cats as $pc){
				if(!$data_for_stocks['categories_min_price'][$pc->term_id] || ($data_for_stocks['categories_min_price'][$pc->term_id] > $cart_item['data']->price))
					$data_for_stocks['categories_min_price'][$pc->term_id] = $cart_item['data']->price;
	
				$data_for_stocks['categories_quantity'][$pc->term_id]+= $cart_item['quantity'];
			}
	
		}
	
		/* Перебираем акции смотрим, выполняются ли условия */
		$stocks = get_all_active_stocks();
		$fee_added = false;
	
		/* Проверим привязку акции к складу */
		foreach($stocks as $stock){
			if(!empty( $stock['stock_data']['sklad'] )){
				$StockId = ( $_COOKIE['StockId'] ) ? intval( $_COOKIE['StockId'] ) : null; // Id склада из куки
				if( !in_array($StockId, $stock['stock_data']['sklad']) ){
					continue;
				}
			}
	
			switch($stock['stock_data']['type']){
				case 'one_category_product_free':
					$make_stock = false;
					$min_price = 0;
					foreach($stock['stock_data']['categoryes_data'] as $single_stock_data){
						$category_quantity = $data_for_stocks['categories_quantity'][$single_stock_data['category_id']];
	
						if(!empty($category_quantity) && $category_quantity >= $single_stock_data['product_min_quantity']){
							$make_stock = true;
							$min_price = $data_for_stocks['categories_min_price'][$single_stock_data['category_id']];
							
							if($make_stock && $min_price){ 
							
								if($single_stock_data['replay']){
									$stock_price = ($min_price*-1)*floor( $category_quantity / $single_stock_data['product_min_quantity'] );
								}else
									$stock_price = $min_price*-1;
	
								if( !$fee_added || ($fee_added &&  $promo_summing!=='false') ){
									//$cart_object->add_fee( 'Акция &laquo;'.$stock['post_title'].'&raquo;', $stock_price, true, 'standard' );
									$added_promos[]=[ 'title' => $stock['post_title'], 'price' => $stock_price ];
									$fee_added = true;
								}
							}
						}
					}
				break;
			}
		}

	return $added_promos;
}

