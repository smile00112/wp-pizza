<?php

////автоматическое применение купонов, если в корзины выполнено необходимое условие

//add_action( 'woocommerce_before_cart', 'auto_apply_matched_coupons' );
function auto_apply_matched_coupons() {
  
    //$coupon_code = 'testauto'; 
	
	$coupon_codes = array();
	$coupons = get_all_coupons();
	foreach($coupons as $item){
		$coupon_code = $item->post_title;
		array_push( $coupon_codes, $coupon_code );
	}
  
    /*if ( WC()->cart->has_discount( $coupon_code ) ) return;
  
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$autocoupon = array( 28930 ); //product ID
		
		if ( in_array( $cart_item['product_id'], $autocoupon ) ) {   
			WC()->cart->apply_coupon( $coupon_code );
			wc_print_notices();
		}
    }*/
	
	//$cart_subtotal = WC()->cart->subtotal;
	//debug_to_file('cart subtotal_: '.$cart_subtotal);
}


function get_all_coupons(){
	$args = array(
		'posts_per_page'   => -1,
		'orderby'          => 'title',
		'order'            => 'asc',
		'post_type'        => 'shop_coupon',
		'post_status'      => 'publish',
		'meta_key'         => 'coupon-autoapply',
		//'meta_value'  =>'',
	);
	
	$coupons = get_posts( $args );
	
	return $coupons;
}

function get_avaible_auto_coupons( $auto_coupons_summing = true ){
	//$coupon_codes = array();
	$coupons = get_all_coupons();
	$coupons_list = [];

	$mystring = 'abc';
	$find_code   = 'auto_';
	$pos = strpos($mystring, $find_code);
	//if(!WC()->session->get('active_promo')) //если нет активных акций
	foreach($coupons as $item){
		// if( $auto_coupons_summing == false && count($coupons_list) >= 1 ){
		// 	break;
		// }
		$coupon_code = $item->post_title;
		$coupon_id = $item->ID;
		$is_auto_on = get_post_meta($coupon_id, 'coupon-autoapply', true);
		$type_auto_coupon = get_post_meta($coupon_id, 'coupon-type-cond', true);
		if($is_auto_on[0] == 'yes'){

			$the_coupon = new WC_Coupon( $coupon_code );
			if ( $the_coupon->is_valid() ) {
				//array_push( $coupon_codes, $coupon_code ); //если у купона включено Автокупон - Да, значит обрабатываем его
				if($type_auto_coupon == 'cartsum'){
					if(	condition_cart_sum($coupon_code, $coupon_id) ){
						array_push( $coupons_list, $coupon_code );
					}else 
						WC()->cart->remove_coupon( $coupon_code );
				}elseif($type_auto_coupon == 'prodcombo'){
					
					if(	condition_cart_prod($coupon_code, $coupon_id) ){
						array_push( $coupons_list, $coupon_code );
					}else 
						WC()->cart->remove_coupon( $coupon_code );					
				}elseif($type_auto_coupon == 'delivery'){
					if( condition_delivery($coupon_code, $coupon_id) ){
						array_push( $coupons_list, $coupon_code );
					}else
						WC()->cart->remove_coupon( $coupon_code );
				}
			}

		}

	}
	return $coupons_list;
}

/////////условия для автокупонов

//достигнута сумма корзины
function condition_cart_sum($coupon_code, $coupon_id){
	//if ( WC()->cart->has_discount( $coupon_code ) ) return;
	
	$cart_subtotal = WC()->cart->subtotal; //debug_to_file('cart subtotal: '.$cart_subtotal);
	$coupon_cart_sum = intval(get_post_meta($coupon_id, 'coupon-cond-cart-sum', true));
	
	if($cart_subtotal >= $coupon_cart_sum){
		//WC()->cart->apply_coupon( $coupon_code );
		return true;
	}

	return false;
}

//товары в корзине
function condition_cart_prod($coupon_code, $coupon_id){ //debug_to_file('coupon_id: '. $coupon_id);

	$coupon_prod_list = array();
	$aply = true;
	$prod_list = get_post_meta($coupon_id, 'product_ids', true);
	if($prod_list){
		$prod_list = explode(',', $prod_list);
		$temp_prod_qty = 1;
		foreach($prod_list as $prod){
			array_push( $coupon_prod_list, [$prod => $temp_prod_qty] );
		}
	}

	$find_coupon_products = 0;
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ){ debug_to_file('cart prod id: '.$cart_item['product_id']); //проход по товарам корзины
		foreach($coupon_prod_list as $item_prod){ //проход по массиву товар => количество
			if ( array_key_exists( $cart_item['product_id'], $item_prod ) ){ 
				$coupon_prod_qty = $item_prod[$cart_item['product_id']]; 
				if(intval($coupon_prod_qty) > $cart_item['quantity']){ 
					//debug_to_file('qty menee');
					$aply = false;
				}else{
					$find_coupon_products++;
				}
			}
		}
	}

	if($aply && (count($coupon_prod_list) == $find_coupon_products)){
		//WC()->cart->apply_coupon( $coupon_code );
		return true;

	}

	return false;
}

//тип доставки
function condition_delivery($coupon_code, $coupon_id){ 
	//WC()->cart->remove_coupon($coupon_code);
	$shipping_ids = wc_get_chosen_shipping_method_ids(); // debug_to_file($shipping_ids);
	if($shipping_ids[0] == 'local_pickup'){
		//WC()->cart->apply_coupon( $coupon_code );
		return true;
	}

	return false;
}

///////////////////


add_action('woocommerce_thankyou', 'clear_coupons_cache');
function clear_coupons_cache(){

	WC()->session->set('disable_auto_coupons', false);
	WC()->session->set('disable_promos', false);
	WC()->session->set('apply_auto_coupon', false);	
	WC()->session->set('auto_apply_coupons_list', []);
	WC()->session->set('apply_promos_list', []);
	WC()->session->set('auto_coupons_and_promo_priority', '');
	WC()->session->set('auto_coupons_summing_chousen', '');	
	WC()->session->set('is_aplied_coupons', false);
	WC()->session->set('aplied_coupons_list', false);

}



//WC()->session->set('apply_auto_coupon', false);
$_SESSION['apply_auto_coupon'] = false;
$_SESSION['auto_applied_coupons'] = '';
//add_action( 'wp_footer', test_coupon_code() );
add_action( 'woocommerce_before_cart', 'apply_auto_coupon' ); //автокупоны на странице Корзина
add_action( 'woocommerce_before_checkout_form', 'apply_auto_coupon' ); //автокупоны на странице Оформление
add_action( 'woocommerce_review_order_after_cart_contents', 'apply_auto_coupon' ); //автокупоны при обновлении Тип доставки
add_action( 'woocommerce_review_order_before_shipping', 'remove_shipping_coupon' ); //сбрасываем автокупон доставки при обновлении Тип доставки
 
function remove_shipping_coupon(){
	$coupons = get_all_coupons();
	foreach($coupons as $item){
		$coupon_code = $item->post_title;
		$coupon_id = $item->ID;
		$is_auto_on = get_post_meta($coupon_id, 'coupon-autoapply', true);
		$type_auto_coupon = get_post_meta($coupon_id, 'coupon-type-cond', true);
		if($is_auto_on[0] == 'yes'){
			if($type_auto_coupon == 'delivery'){
				WC()->cart->remove_coupon('самзаберу');
			}

		}
	}
	
}

function cart_has_aplied_coupons($cart){
	$ac = $cart->get_applied_coupons();
	$ac_new = [];
	foreach($ac as $c){
		if(strpos($c, 'promo-') === false){
			$ac_new[] = $c;
		}
	}
	//$ac_new = count( array_values($ac_new) ) ? array_values( $ac_new) : [];
	return [
		'is' => $ac_new ? true : false,
		'list' => $ac_new
	];
}


function apply_auto_coupon(){
	WC()->session->set('auto_apply_coupons_list', []) ;
	if( WC()->session->get('disable_auto_coupons') == true ) return false;
	
	//WC()->cart->remove_coupons();

	//if($_SESSION['apply_auto_coupon']) return false;
	$_SESSION['apply_auto_coupon'] = true;
	$_SESSION['auto_applied_coupons'] = [];

	$auto_coupons_summing = get_field('auto_coupons_summing', 'option') == "true" ? true : false ;
	$auto_coupons_and_promo_summing = get_field('auto_coupons_and_promo_summing', 'option') == "true" ? true : false ;
	$coupons_and_auto_coupons_summing = get_field('coupons_and_auto_coupons_summing', 'option') == "true" ? true : false ;

	//Получаем все доступные автокупоны
	$_SESSION['auto_applied_coupons'] = get_avaible_auto_coupons(); 
	//print_R($_SESSION['auto_applied_coupons']);
	/* Если авто купоны и акции не суммируются и есть активные акции */
	$auto_coupons_and_promo_priority = WC()->session->get('auto_coupons_and_promo_priority');

	//Чистим выбранный автокупон, если его нет среди достуных
	if(!in_array( WC()->session->get('auto_coupons_summing_chousen'), $_SESSION['auto_applied_coupons'] ))
		WC()->session->set('auto_coupons_summing_chousen', '');	

	$auto_coupons_summing_chousen = WC()->session->get('auto_coupons_summing_chousen');

	
	// print_r(WC()->session->get('auto_coupons_and_promo_priority'));
	//  print_r(WC()->session->get('auto_applied_coupons'));
	//  print_r(WC()->session->get('aplied_coupons_list'));

	//смотрим применённые акции и из суммирование с купонами
	if(!$auto_coupons_and_promo_summing && count(WC()->session->get('apply_promos_list')) > 0){
		if( $auto_coupons_and_promo_priority != 'auto_coupons'){
			if($_SESSION['auto_applied_coupons']){
				foreach($_SESSION['auto_applied_coupons'] as $coupon_code){
					WC()->cart->remove_coupon($coupon_code);
				}
			}
			return false;
		}else{
			//echo '__'.(empty($auto_coupons_and_promo_priority) || $auto_coupons_and_promo_priority == 'promo');
		}
	}else {
		//echo !$auto_coupons_and_promo_summing; echo count(WC()->session->get('apply_promos_list'));
	}
			

	//print_r($_SESSION['auto_applied_coupons']);
	//удаляем автокупоны из корзины 
	if($_SESSION['auto_applied_coupons']){
		foreach($_SESSION['auto_applied_coupons'] as $coupon_code){
			WC()->cart->remove_coupon( $coupon_code );
		}
	}

	/* После отмены автокупонов, смотрим есть ли в корзине применённые купоны */
	$has_applied_coupons = cart_has_aplied_coupons(WC()->cart);
	// echo 'has_applied_coupons';
	// print_r($has_applied_coupons);
	WC()->session->set('is_aplied_coupons', $has_applied_coupons['is']) ;
	WC()->session->set('aplied_coupons_list', $has_applied_coupons['list']) ;


	//Применяем автокупоны к корзине
	if($_SESSION['auto_applied_coupons']){
		$real_aplyed_coupons = [];
		//echo '$auto_coupons_summing_chousen='.$auto_coupons_summing_chousen.'|';
		foreach($_SESSION['auto_applied_coupons'] as $coupon_code){
			//echo 1;
			//Если есть применённый купон и нет суммирования с автокупонами то всё) 
			if( $has_applied_coupons['is'] && !$coupons_and_auto_coupons_summing){
				break;
			}
			//echo 2;
			//echo (!$auto_coupons_and_promo_summing ) ."___". ( $auto_coupons_summing_chousen != $coupon_code ) ."___". ( !empty($auto_coupons_summing_chousen) ).'<br>';
			if(!$auto_coupons_and_promo_summing && ($auto_coupons_summing_chousen != $coupon_code && !empty($auto_coupons_summing_chousen) ))
				continue;
			//echo 3;
			$aplyed = WC()->cart->apply_coupon( $coupon_code );
			if($aplyed){
				//echo 4;
				$real_aplyed_coupons[]=$coupon_code;
				//Применили купон и если они не суммируются, прерываем цыкл
				if(!$auto_coupons_and_promo_summing)
					break;
			}
		}
	}

	if (isset(WC()->session)) {
		WC()->session->set('auto_apply_coupons_list',$real_aplyed_coupons) ;
		WC()->session->set('apply_auto_coupon', (count($real_aplyed_coupons) ? true : false) ) ;
	}
	//debug_to_file($coupon_codes);
	//print_R($_SESSION['auto_applied_coupons']);
}

add_action('rest_api_init', function () {
	register_rest_route('systeminfo/v1/coupons', '/autoapply', array( 
		'methods' => 'GET',
		'callback' => 'apply_auto_coupon',
	));
});

/* Модификация API ответа по купонам (меняем ключи товаров) */
add_action( 'rest_api_init', function () {
    register_rest_field( 'shop_coupon', '_wc_free_gift_coupon_data_mod', array(
        'get_callback' => function( $coupon  ) {
			$meta = get_post_meta($coupon['id'], '_wc_free_gift_coupon_data');
			if(count($meta) == 1) $meta = $meta[0];
            return array_values($meta);
        },
        'update_callback' => function( $karma, $comment_obj ) {
           
            return true;
        },
        'schema' => array(
            'description' => __( 'Free Coupon Products list (mod).' ),
            'type'        => 'array'
        ),
    ) );
} );


function apply_auto_coupon_test(){
	
	return json_encode($_GET);


}


add_action('rest_api_init', function () {
	 
	register_rest_route('wc/v3/coupons', '/autoapply', array(  
		'methods' => 'POST',
		'callback' => 'get_auto_coupons_and_promos_by_cart_summ',
	));
});


/* Отключаем купоны, если есть активная акция */
// add_filter( 'woocommerce_coupons_enabled', 'truemisha_coupon_field_on_checkout' );
//  function truemisha_coupon_field_on_checkout( $enabled ) {
// 	//echo '___'.WC()->session->get('active_promo');
// 	if( !is_admin() )
// 	{  
// 		if( WC()->session->get('active_promo') ) {
// 			$enabled = false; // купоны отключены
// 		}
// 	}
	
// 	return $enabled;

// }

/* Получаем корзину по API и отдаём автокупоны, которые можно к ней применить */
function get_auto_coupons_and_promos_by_cart_summ( WP_REST_Request $request  ){

	$couponsList = [];
	$stocksList = [];

	$coupon_active = $promo_active = false;
	$parameters = $request->get_params();
	
	$customer_id = $parameters['customer_id'] ? $parameters['customer_id'] : 0;
	$cart_total = $parameters['cart_total'];
	$delivery_type  = $parameters['delivery_type'] ? $parameters['delivery_type'] : '';

	
	//$cart_items = json_decode($parameters['cart_items'], true);
	$cart_items = $parameters['cart_items'];
	// print_r($cart_items);

	if(!$cart_total || empty($cart_items)){
		return [];
	}

	/* Получим настройки взаимодействия купонов и акций*/
	$promo_summing = get_field('promo_summing', 'option');
	$coupons_summing = get_field('coupons_summing', 'option');
	$coupons_and_promo_summing = get_field('coupons_and_promo_summing', 'option');



	$items_count = 0;
	$itemsList = [];
	$fake_cart = [];
	$data_for_stocks = [];


	/* Проходим по товарам считаем основные параметры*/
	//$min_price_product_id = 0;
	$cart_to_flutter = [];
	foreach($cart_items as $product){
		$product_id = $product['product']['id'];
		$quantity = $product['quantity'];
		$product_price = $product['price'];
		$variation_id = $product['variation'] ? $product['variation']['id'] : 0;
		if($variation_id){
			$product_price = $product['variation']['price'];
		}

		if(!$product_id || !$quantity) continue;

		$product_obj = new WC_Product($product_id);
		$product_price = $product_obj->get_price();
		

		$cart_to_flutter[]=[
			"product_id" =>$product_id,
			"quantity" => $quantity,
			"variation_id" => $variation_id
		];

		$items_count+=$quantity;
		$itemsList[]=$product_id;
		$fake_cart[]=[
			'product_id' => $product_id,
			'quantity' => $quantity,
		];

		/* Для акций строим cтату по категориям мин ценам*/
		$product_cats = get_the_terms( $product_id, 'product_cat' );
		
		foreach($product_cats as $pc){
			if(!$data_for_stocks['categories_min_price'][$pc->term_id] || ($data_for_stocks['categories_min_price'][$pc->term_id] > $product_price)){
				$data_for_stocks['categories_min_price'][$pc->term_id] = $product_price;
				$data_for_stocks['categories_min_price_product_id'][$pc->term_id] = $product_id;
				//$min_price_product_id = $product['id'];
			}

			$data_for_stocks['categories_quantity'][$pc->term_id]+= $product['quantity'];

		}
	}


	/* Перебираем акции */
	/* Перебираем акции смотрим, выполняются ли условия */
	$stocks = get_all_active_stocks();
	foreach($stocks as &$stock){
		switch($stock['stock_data']['type']){
			case 'one_category_product_free':
				$make_stock = false;
				$min_price = 0;
				foreach($stock['stock_data']['categoryes_data'] as $single_stock_data){
					$category_quantity = $data_for_stocks['categories_quantity'][$single_stock_data['category_id']];

					if(!empty($category_quantity) && $category_quantity >= $single_stock_data['product_min_quantity']){
						$make_stock = true;
						$min_price = $data_for_stocks['categories_min_price'][$single_stock_data['category_id']];
						$min_price_product_id = $data_for_stocks['categories_min_price_product_id'][$single_stock_data['category_id']];

						//echo $single_stock_data['category_id'];
						if($make_stock && $min_price){ 
						
	
							if($single_stock_data['replay']){
								$stock_price = ($min_price)*floor( $category_quantity / $single_stock_data['product_min_quantity'] );
							}else
								$stock_price = $min_price; 

							$promo_active = true;
							$request = new WP_REST_Request('GET', '/wc/v3/products/'.$min_price_product_id);
							$result = rest_get_server()->dispatch($request);
							$stock['fee_price'] =  intval($stock_price);
							$stock['fee_quantity'] =  floor( $category_quantity / $single_stock_data['product_min_quantity'] );

							$stock['free_product_info'] =  $result->data;

							if( count($stocksList) &&  $promo_summing!=='false' ){
								$stocksList[] = $stock;
							}
							else{
								$stocksList[] = $stock;
							}

							//$promosList[] = 
							// $cart_object->add_fee( 'Акция &laquo;'.$stock['post_title'].'&raquo;', $stock_price, true, 'standard' );
							// WC()->session->set('active_promo', true);
						}//else echo $category_quantity.'___'.$min_price;
					}
				}
			break;
		}
	}
	
	if( !count($stocksList) || (count($stocksList) &&  $coupons_and_promo_summing == 'true') ){

		/* Перебираем купоны */
		$coupon_codes = array();
		$coupons = get_all_coupons();
		$mystring = 'abc';
		$find_code   = 'auto_';
		$pos = strpos($mystring, $find_code);
		foreach($coupons as $item){
		
			$coupon_code = $item->post_title;
			$coupon_id = $item->ID;
			$is_auto_on = get_post_meta($coupon_id, 'coupon-autoapply', true);
			$type_auto_coupon = get_post_meta($coupon_id, 'coupon-type-cond', true);
			$type_coupon_origin = get_post_meta($coupon_id, 'discount_type', true);

			if($is_auto_on[0] == 'yes'){

				// print_R($queue_request->data);
				// exit;


				//echo $item->post_title. '--'.$type_auto_coupon.'--'.$type_coupon_origin.'      ';
				//array_push( $coupon_codes, $coupon_code ); //если у купона включено Автокупон - Да, значит обрабатываем его
				/* ТИП 1  (дублируется c condition_cart_sum() )*/
				if($type_auto_coupon == 'cartsum'){
					
					$cart_subtotal = $cart_total; //WC()->cart->subtotal; //debug_to_file('cart subtotal: '.$cart_subtotal);
					$coupon_cart_sum = intval(get_post_meta( $coupon_id, 'coupon-cond-cart-sum', true));
					if($cart_subtotal >= $coupon_cart_sum){
						array_push( $coupon_codes, $coupon_id );  //Добавляем купон
					}
				}
				/* ТИП 2  (дублируется c condition_cart_prod() )*/  
				elseif($type_auto_coupon == 'prodcombo'){
					
					$coupon_prod_list = array();
					$prod_list = get_post_meta($coupon_id, 'product_ids', true);
					if($prod_list){
						$prod_list = explode(',', $prod_list);
						$temp_prod_qty = 1;
						foreach($prod_list as $prod){
							array_push( $coupon_prod_list, [$prod => $temp_prod_qty] );
						}
				
					}
					// for($i = 0; $i < 10; $i++){
					// 	$coupon_meta_key = 'coupon-products-list_'.$i.'_coupon-products';
					// 	$temp_prod = get_post_meta($coupon_id, $coupon_meta_key, true); //debug_to_file($temp_prod);
					// 	$temp_prod_qty = get_post_meta($coupon_id, 'coupon-products-list_'.$i.'_coupon-products-qty', true);
					// 	$tmp_arr = [$temp_prod => $temp_prod_qty];
					// 	if($temp_prod != '') array_push($coupon_prod_list, $tmp_arr);
					// }
					
					//print_r( $fake_cart );
					foreach ( $fake_cart as $cart_item ){ //проход по товарам корзины
						foreach($coupon_prod_list as $item_prod){ //проход по массиву товар => количество
							if ( array_key_exists( $cart_item['product_id'], $item_prod ) ){
								$coupon_prod_qty = $item_prod[$cart_item['product_id']]; //кол-во товара, заданое в условии купона
								//debug_to_file('cart qty: '.$cart_item['quantity']);
								if(intval($coupon_prod_qty) <= $cart_item['quantity']){
									//return false;
									array_push( $coupon_codes, $coupon_id );  //Добавляем купон
								}
									
									//debug_to_file($cart_item);
							}
							//else return false;
						}
					}
					//WC()->cart->apply_coupon( $coupon_code );
					//return true;
				}
				/* ТИП 3  (дублируется c condition_delivery() )*/
				elseif($type_auto_coupon == 'delivery') {
					if($delivery_type == 'local_pickup'){
						array_push( $coupon_codes, $coupon_id );
					}
				
				}
				/* ТИП 4  первый заказ*/
				elseif($type_auto_coupon == 'first_order') {
					$args = array(
						'customer_id' => $customer_id,
						'status'=> array( 'wc-processing', 'wc-making', 'wc-done',  'wc-kurier', 'wc-completed' ),
					);
					$orders = wc_get_orders( $args );
					if ( count( $orders ) == 0 ) {
						array_push( $coupon_codes, $coupon_id );
					}
				}
				else{
					/* БЕЗ ТИПА */
					if($type_coupon_origin=='free_gift'){
						$coupon_product_ids = get_post_meta($coupon_id, 'product_ids', true);
						$coupon_product_ids = explode(',', $coupon_product_ids);
						if(empty($coupon_product_ids)) continue;

						$coupon_add = true;
						/* Проверяем наличие всех товаров купона в переданной корзине */
						foreach ( $coupon_product_ids as $coupon_product ){
							if(!in_array($coupon_product, $itemsList))
								$coupon_add = false;
						}
						if($coupon_add)
							array_push( $coupon_codes, $coupon_id );  //Добавляем купон
					}
					//echo 'type_auto_coupon___'.$type_auto_coupon.'__'.$coupon_id;

				}
			}
			//debug_to_file($is_auto_on);
		}

		foreach($coupon_codes as $coupon_id){
			foreach($coupons  as &$coupon){
				if( $coupon->ID == $coupon_id){
					$coupon_code = $coupon->post_title;
					$flutter_data_coupon = [];
					//print_r($woocommerce->get('coupons')); //https://testo.dolinger-app.ru
					// echo $coupon->post_title.'==';
					// //echo $coupon->get_code();
					// echo $coupon_id.'--';					
					// echo $customer_id.'-';
					// echo $coupon_code;
					// echo '___';			
					//print_r($cart_to_flutter);
						
						$request = new WP_REST_Request('GET', '/wc/v3/coupons/'.$coupon_id);
						$result = rest_get_server()->dispatch($request);


						//Проверяем еще раз купон через API flutter
						if( $customer_id && $coupon_code){
							$queue_request = new WP_REST_Request('POST', '/api/flutter_woo/coupon');
							$queue_request->add_header('Content-Type', 'application/json');
							$queue_request->set_body(wp_json_encode(["line_items" => $cart_to_flutter, "coupon_code" => $coupon_code, "customer_id" => $customer_id]));
							$queue_request = rest_do_request($queue_request);
							
							//print_r($queue_request->data);

							//Ошибка приминения купона
							if($queue_request->data['data']['status'] == 400){
								continue;
							}
							
							$flutter_data_coupon = $queue_request->data['coupon'];
							unset($queue_request);
						}



					if( !count($couponsList) || (count($couponsList) /*&&  $coupons_summing!=='false'*/) ){

						$coupon_code = $result->data['code'];
					
						/* делаем запрос на скидку для купона*/
						if($result->data['discount_type'] == 'percent'){
							$queue_request = new WP_REST_Request('POST', '/api/flutter_woo/coupon');
							$queue_request->add_header('Content-Type', 'application/json');
							$queue_request->set_body(wp_json_encode(["line_items" => $cart_to_flutter, "coupon_code" => $coupon_code, "customer_id" => $customer_id]));
							$queue_request = rest_do_request($queue_request);
							$result->data['cart_discount'] = floatval($queue_request->data['discount']);
						}



						//echo 'discount=';	print_r($queue_request->data['discount']);
						//exit;
						if(!empty(($result->data['id'])))
							$couponsList[] = $result->data;
					}

				}
			}
		}

	}

	/* Предполагаемые бонусы для корзины */
	$cart_bounces = 0;
	if($customer_id && $fake_cart){
		if(get_option( 'rs_disable_point_if_coupon' ) == 'no')
			$cart_bounces = get_bonuces_for_cart($fake_cart, $customer_id);
	}
	//debug_to_file(date("D M j G:i:s T Y").'--_______--'.print_r( $parameters, true));
	return ['stocksList'=>$stocksList, 'couponsList' => $couponsList, 'cart_bonuses' => intval($cart_bounces)];

}

add_action('rest_api_init', function () {
	register_rest_route('systeminfo/v1/coupons', '/seettings', array( 
		'methods' => 'GET',
		'callback' => 'rest_coupon_seettings',
	));
});
function rest_coupon_seettings(){
	/* Получим настройки взаимодействия купонов и акций*/
	$promo_summing = get_field('promo_summing', 'option');
	$coupons_summing = get_field('coupons_summing', 'option');
	$coupons_and_promo_summing = get_field('coupons_and_promo_summing', 'option');
	$auto_coupons_summing = get_field('auto_coupons_summing', 'option');
	$auto_coupons_and_promo_summing = get_field('auto_coupons_and_promo_summing', 'option');
	$coupons_and_auto_coupons_summing = get_field('coupons_and_auto_coupons_summing', 'option');

	$app_use_bon_with_promo = get_field('app_use_bon_with_promo', 'option');
	$app_use_bon_with_auto_promo = get_field('app_use_bon_with_auto_promo', 'option');
	$app_use_bon_with_akcii = get_field('app_use_bon_with_akcii', 'option');
	return [
		'promo_summing' => $promo_summing=="true" ? true : false,
		'coupons_summing' => $coupons_summing=="true" ? true : false,
		'coupons_and_promo_summing' => $coupons_and_promo_summing=="true" ? true : false,
		'auto_coupons_summing' => $auto_coupons_summing=="true" ? true : false,
		'auto_coupons_and_promo_summing' => $auto_coupons_and_promo_summing=="true" ? true : false,
		'coupons_and_auto_coupons_summing' => $coupons_and_auto_coupons_summing=="true" ? true : false,

		'app_use_bon_with_promo' => $app_use_bon_with_promo=="no" ? true : false,
		'app_use_bon_with_auto_promo' => $app_use_bon_with_auto_promo=="no" ? true : false,
		'app_use_bon_with_akcii' => $app_use_bon_with_akcii=="no" ? true : false,
	];
}

function get_bonuces_for_cart($cart, $user_id){

	
	$user = new WP_User( $user_id );
	$user_login = $user->user_login;

	wp_set_current_user($user_id, $user->user_login);
	wp_set_auth_cookie($user_id);
	//do_action('wp_login', $user_login);

	global $woocommerce;	
	$active_methods   = array();
	$values = array ('country' => 'RU',
					'amount'  => 0);
					
	WC()->frontend_includes();

	WC()->session = new WC_Session_Handler();
	WC()->session->init();
	WC()->customer = new WC_Customer( $user_id, true );
	WC()->cart = new WC_Cart();
	WC()->cart->empty_cart();

	// $totalrewardpoints = global_variable_points() ;
	// WC()->session->set( 'rewardpoints' , $totalrewardpoints ) ;
	
	foreach($cart as $cart_item){
		WC()->cart->add_to_cart($cart_item['product_id'], $cart_item['quantity']);
	}

	//print_R( WC()->cart->get_totals() );
	//echo $CartTotalPoints = get_reward_points_based_on_cart_total( WC()->cart->total ) ;
	$RSF = new RSFrontendAssets();
	//echo $RSF->total_points_in_cart();
	$points_for_products  = $RSF->original_points_for_product() ;
	

	return array_sum($points_for_products);
}

add_action('wp_ajax_check_rules', 'check_rules');
add_action('wp_ajax_nopriv_check_rules', 'check_rules');
function check_rules() {
	$coupon_code = $_POST['coupon'];
	$coupon_check = false;
	if($coupon_code){
		$the_coupon = new WC_Coupon( $coupon_code );
		$coupon_check = $the_coupon->is_valid();
	}
	if(!$coupon_check) 
		echo json_encode(['coupon_check' => $coupon_check ]);
	else
		if(strpos($coupon_code, 'promo-') !== false) //реферальные промокоды не проверяем на всякое
			echo json_encode(['referal_coupon' => true ]);
	else	
		check_aplied_auto_coupons_and_promos();

	die(); // Alway at the end (to avoid server error 500)
}

add_action('wp_ajax_check_aplied_auto_coupons_and_promos', 'check_aplied_auto_coupons_and_promos');
add_action('wp_ajax_nopriv_check_aplied_auto_coupons_and_promos', 'check_aplied_auto_coupons_and_promos');
function check_aplied_auto_coupons_and_promos() {
	
	session_start();
	$auto_coupons_summing = get_field('auto_coupons_summing', 'option') == "true" ? true : false ;
	$promo_summing = get_field('promo_summing', 'option') == "true" ? true : false ;
	$coupons_summing = get_field('coupons_summing', 'option') == "true" ? true : false ;
	$coupons_and_promo_summing = get_field('coupons_and_promo_summing', 'option') == "true" ? true : false ;
	$auto_coupons_and_promo_summing = get_field('auto_coupons_and_promo_summing', 'option') == "true" ? true : false ;
	$coupons_and_auto_coupons_summing = get_field('coupons_and_auto_coupons_summing', 'option') == "true" ? true : false ;

	$auto_coupons_and_promo_priority = WC()->session->get('auto_coupons_and_promo_priority');
	$has_applied_coupons = WC()->session->get('is_aplied_coupons') ;
	$aplied_coupons_list = WC()->session->get('aplied_coupons_list') ;
	
	if (isset(WC()->session)) {
		$aplied_coupons = WC()->session->get('auto_apply_coupons_list');
	}
	if (isset(WC()->session)) {
		$aplied_promos = WC()->session->get('apply_promos_list');
	}
	$all_avaible_auto_coupons = get_avaible_auto_coupons();
	$all_avaible_promos = get_avaible_promos($promo_summing, WC()->cart);


	echo json_encode([
		'auto_apply_coupons_list' => $aplied_coupons ?:[],
		'apply_promos_list' => $aplied_promos ?:[],
		'all_avaible_auto_coupons' => $all_avaible_auto_coupons,
		'all_avaible_promos' => $all_avaible_promos,
		'has_applied_coupons' => $has_applied_coupons,
		'aplied_coupons_list' => $aplied_coupons_list,
		
		'auto_coupons_and_promo_priority' => $auto_coupons_and_promo_priority,
		'auto_coupons_summing' => $auto_coupons_summing,
		'coupons_summing' => $coupons_summing,
		'coupons_and_promo_summing' => $coupons_and_promo_summing,
		'auto_coupons_and_promo_summing' => $auto_coupons_and_promo_summing,
		'coupons_and_auto_coupons_summing' => $coupons_and_auto_coupons_summing,
	]);

	die(); // Alway at the end (to avoid server error 500)
}

add_action('wp_ajax_set_auto_coupons_and_promo_priority', 'set_auto_coupons_and_promo_priority');
add_action('wp_ajax_nopriv_set_auto_coupons_and_promo_priority', 'set_auto_coupons_and_promo_priority');
function set_auto_coupons_and_promo_priority() {
	session_start();

	WC()->session->set('auto_coupons_and_promo_priority', $_POST['priority']);

	$aplied_coupons_list = WC()->session->get('aplied_coupons_list') ;
	$coupons_and_promo_summing = get_field('coupons_and_promo_summing', 'option') == "true" ? true : false ;

	if($aplied_coupons_list && !$coupons_and_promo_summing)
		foreach($aplied_coupons_list as $coupon){
			if(strpos($coupon, 'promo-') == false){ //реферальные промокоды не удаляем
				WC()->cart->remove_coupon( $coupon );
			}
		}

	echo json_encode([
		'success' => WC()->session->get('auto_coupons_and_promo_priority'),
	]);

	die(); // Alway at the end (to avoid server error 500)
}

add_action('wp_ajax_set_auto_coupon_one', 'set_auto_coupon_one');
add_action('wp_ajax_nopriv_set_auto_coupon_one', 'set_auto_coupon_one');
function set_auto_coupon_one() {
	session_start();

	WC()->session->set('auto_coupons_summing_chousen', $_POST['coupon']);
	WC()->session->set('auto_coupons_and_promo_priority', 'auto_coupons');
	
	$aplied_coupons_list = WC()->session->get('aplied_coupons_list') ;
	$coupons_and_auto_coupons_summing = get_field('coupons_and_auto_coupons_summing', 'option') == "true" ? true : false ;
	if($aplied_coupons_list && !$coupons_and_auto_coupons_summing)
		foreach($aplied_coupons_list as $coupon){
			WC()->cart->remove_coupon( $coupon );
		}
		
	echo json_encode([
		'success' => WC()->session->get('auto_coupons_summing_chousen'),
	]);

	die(); // Alway at the end (to avoid server error 500)
}