<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
	<?php do_action('woocommerce_before_cart_table');
	
	//echo $_COOKIE['StockId'];
	?>

	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead>
			<tr>
				<th class="product-remove">&nbsp;</th>
				<th class="product-thumbnail">&nbsp;</th>
				<th class="product-name"><?php esc_html_e('Блюдо', 'woocommerce'); ?></th>
				<th class="product-price"><?php esc_html_e('Price', 'woocommerce'); ?></th>
				<th class="product-quantity"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
				<th class="product-subtotal"><?php esc_html_e('Блюд на сумму', 'woocommerce'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action('woocommerce_before_cart_contents'); ?>

			<?php
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				$_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
				$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

				if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
					$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
					$parent_class = (isset($cart_item['supplements_ids']) && $cart_item['supplements_ids']) ? ' cart-parent-item ' : ''; // класс основного товара
					$sub_class = (isset($cart_item['parent_key']) && $cart_item['parent_key']) ? ' cart-sub-item ' : ''; // класс доптовара
					$fullProduct = wc_get_product($cart_item['product_id']);
					$upsellIds = $fullProduct->get_upsell_ids();


					if (in_array($upsellIds, $product_id)) continue;
			?>

			
					<div class='cart-pre' style='display:none'>
						<?
						/*
						echo "<pre>";
						print_r($upsellIds);
						print_r($cart_item['supplements_ids']);
						echo 'cart_item_data=';
						print_r($cart_item['cart_item_data']);
						echo 'parent_key=';
						print_r($cart_item['parent_key']);

						echo "</pre>";
						*/
						?>
					</div>
			

					<tr  id="cart_itemid<?=$cart_item['product_id'];?>"  class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key));
																echo $parent_class;
																echo $sub_class; ?>">

						<td class="product-remove">
							<?php
							echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'woocommerce_cart_item_remove_link',
								sprintf(
									'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
									esc_url(wc_get_cart_remove_url($cart_item_key)),
									esc_html__('Remove this item', 'woocommerce'),
									esc_attr($product_id),
									esc_attr($_product->get_sku())
								),
								$cart_item_key
							);
							?>
						</td>

						<td class="product-thumbnail">
							<?php
							$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

							if (!$product_permalink) {
								echo $thumbnail; // PHPCS: XSS ok.
							} else {
								printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
							}
							?>
						</td>

						<td class="product-name" data-title="<?php esc_attr_e('Блюдо', 'woocommerce'); ?>">
							<?php
							if (!$product_permalink) {
								echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;');
							} else {
								echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
							}

							do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

							// Meta data.
							echo wc_get_formatted_cart_item_data($cart_item); // PHPCS: XSS ok.

							// Backorder notification.
							if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
								echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
							}
							?>
						</td>

						<td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
							<?php
							echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.
							?>
						</td>
						
						<? if( empty($cart_item['parent_key']) )// если обычный товар
							{
						?>
							<td class="product-quantity" data-is_parent="<? echo(!empty($cart_item['supplements_ids']) ? 'true' : 'false'); ?>" data-product_key="<? echo $cart_item_key; ?>" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
								<?php
								if ($_product->is_sold_individually()) {
									$product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
								} else { ?>
									<?php $product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $_product->get_max_purchase_quantity(),
											'min_value'    => '0',
											'product_name' => $_product->get_name(),
										),
										$_product,
										false
									); ?>
								<?php }
									echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.
								?>
							</td>
							<td class="product-subtotal" data-title="<?php esc_attr_e('Блюд на сумму', 'woocommerce'); ?>">
								<?php
								if(empty($cart_item['supplements_ids'])){
									echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // PHPCS: XSS ok.
								}else{
									/* если товар с допами, суммируем цену основного товара и допов */
									$prod_data = json_decode( apply_filters('woocommerce_cart_item_price', $cart_item['data'], $cart_item, $cart_item_key), true);
									$price = !empty($prod_data['sale_price']) ? $prod_data['sale_price'] : $prod_data['regular_price'];
									$main_product_price =  $price * $cart_item['quantity'];
									$dops_price = 0;
									foreach (WC()->cart->get_cart() as $cart_item_key2 => $cart_item2) {
										if(empty($cart_item2['parent_key']) || $cart_item2['parent_key'] != $cart_item_key ){
											continue;
											//echo WC()->cart->get_product_price_num($_product);
											

											// echo  $cart_item['quantity'];
											// print_R($cart_item);
										}
										$prod_data2 = json_decode( apply_filters('woocommerce_cart_item_price', $cart_item2['data'], $cart_item2, $cart_item_key2), true);
										$price2 = !empty($prod_data2['sale_price']) ? $prod_data2['sale_price'] : $prod_data2['regular_price'];
										$dop_product   = apply_filters('woocommerce_cart_item_product', $cart_item2['data'], $cart_item2, $cart_item_key2);
										$dops_price+= $price2 * $cart_item2['quantity'];

									}
									//echo $main_product_price.'__'.$dops_price.'|';
									echo apply_filters('woocommerce_cart_item_subtotal', ($main_product_price+$dops_price).'&nbsp;<span class="woocommerce-Price-currencySymbol">₽</span>', $cart_item, $cart_item_key); // PHPCS: XSS ok.
								}
								?>
							</td>

						<? 
							}
							else
							{ //шаблон для допов 
						?>
								<td class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
									<?php
									if ($_product->is_sold_individually()) {
										$product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
									} else { ?>
										<?php $product_quantity = woocommerce_quantity_input(
											array(
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $_product->get_max_purchase_quantity(),
												'min_value'    => '0',
												'product_name' => $_product->get_name(),
											),
											$_product,
											false
										); ?>
									<?php }  ?>
									<!---->
									<div class="qib-container-dop">
										<span class="cart-dops-qty"><? echo $cart_item['quantity'].'&nbspшт.'; ?></span>
									</div>
									<div class="dop-product-qty"  data-qty_parent="<? echo $cart_item['parent_key']; ?>" style="display: none;">
										<? echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.?>
									</div>
								</td>
								<td class="product-subtotal" data-title="<?php esc_attr_e('Блюд на сумму', 'woocommerce'); ?>">
									<?php
									//echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // PHPCS: XSS ok.
									?>
								</td>
						<? } ?>


					</tr>
			<?php
				}
			}
			?>


			<?php do_action('woocommerce_cart_contents'); ?>
			
			<? include $_SERVER['DOCUMENT_ROOT'].'/wp-content/themes/pizzaro/inc/woocommerce/pizzaro-cart-gifts.php'; ?>

			<tr>
				<td colspan="6" class="actions actions-column">

					<?php if (wc_coupons_enabled()) { ?>
						<div class="coupon">
							<label for="coupon_code"><?php esc_html_e('Coupon:', 'woocommerce'); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Промокод', 'woocommerce'); ?>" /> <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_attr_e('Apply coupon', 'woocommerce'); ?></button>
							<?php do_action('woocommerce_cart_coupon'); ?>
						</div>
					<?php } ?>

					<button type="submit" class="button" name="update_cart"  value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>
					
					<button type="button" class="modal-btn" data-reload-promos >
						<div>					
							<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
								viewBox="0 0 299.34 299.34" style="enable-background:new 0 0 299.34 299.34;" xml:space="preserve">
							<g>
								<g>
									<path d="M282.868,56.803H204.27l6.877-6.158c0.505-0.452,0.957-0.958,1.35-1.509c10.05-14.091,6.762-33.731-7.331-43.781
										c-6.72-4.792-15.185-6.475-23.225-4.612c-8.041,1.861-14.906,7.09-18.837,14.345l-13.434,24.799l-13.434-24.799
										c-3.931-7.256-10.797-12.486-18.837-14.346c-8.042-1.862-16.507-0.18-23.225,4.612c-14.091,10.05-17.38,29.69-7.331,43.781
										c0.394,0.552,0.847,1.058,1.35,1.509l6.877,6.158H16.474c-5.07,0-9.18,4.11-9.18,9.18v58.204c0,5.07,4.11,9.18,9.18,9.18h2.432
										V290.16c0,5.07,4.11,9.18,9.18,9.18h243.17c5.07,0,9.18-4.11,9.18-9.18V133.366h2.432c5.07,0,9.18-4.11,9.18-9.18V65.983
										C292.048,60.913,287.937,56.803,282.868,56.803z M179.248,23.833c1.446-2.671,3.873-4.519,6.833-5.204
										c0.802-0.186,1.605-0.277,2.401-0.277c2.142,0,4.221,0.664,6.024,1.95c2.834,2.021,4.71,5.023,5.285,8.457
										c0.525,3.14-0.096,6.293-1.751,8.977l-21.295,19.067h-15.357L179.248,23.833z M99.549,28.76c0.574-3.432,2.451-6.436,5.286-8.457
										c2.473-1.764,5.463-2.359,8.425-1.673c2.959,0.684,5.386,2.533,6.833,5.204l17.86,32.969h-15.357l-21.296-19.067
										C99.645,35.053,99.024,31.898,99.549,28.76z M115.508,280.981H37.265V133.367h78.243V280.981z M165.472,280.981h-31.604V133.367
										h31.604V280.981z M262.076,280.981h-78.243V133.367h78.243V280.981z M273.688,115.007H25.653V75.163h248.034V115.007z"/>
							</g>
							</svg>
						</div>		
						Выбрать бонус
					</button>
					<?php do_action('woocommerce_cart_actions'); ?>


					<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
				</td>
			</tr>

			<?php do_action('woocommerce_after_cart_contents'); ?>

		</tbody>
	</table>
	<?php do_action('woocommerce_before_cart_collaterals'); ?>
	<div class="cart-collaterals">
		<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action('woocommerce_cart_collaterals');
		?>


		<?php do_action('woocommerce_cart_actions'); ?>
		<button type="submit" class="button displayonmobile" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>

		<button type="button" class="modal-btn" data-reload-promos >
			<div>					
				<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
					viewBox="0 0 299.34 299.34" style="enable-background:new 0 0 299.34 299.34;" xml:space="preserve">
				<g>
					<g>
						<path d="M282.868,56.803H204.27l6.877-6.158c0.505-0.452,0.957-0.958,1.35-1.509c10.05-14.091,6.762-33.731-7.331-43.781
							c-6.72-4.792-15.185-6.475-23.225-4.612c-8.041,1.861-14.906,7.09-18.837,14.345l-13.434,24.799l-13.434-24.799
							c-3.931-7.256-10.797-12.486-18.837-14.346c-8.042-1.862-16.507-0.18-23.225,4.612c-14.091,10.05-17.38,29.69-7.331,43.781
							c0.394,0.552,0.847,1.058,1.35,1.509l6.877,6.158H16.474c-5.07,0-9.18,4.11-9.18,9.18v58.204c0,5.07,4.11,9.18,9.18,9.18h2.432
							V290.16c0,5.07,4.11,9.18,9.18,9.18h243.17c5.07,0,9.18-4.11,9.18-9.18V133.366h2.432c5.07,0,9.18-4.11,9.18-9.18V65.983
							C292.048,60.913,287.937,56.803,282.868,56.803z M179.248,23.833c1.446-2.671,3.873-4.519,6.833-5.204
							c0.802-0.186,1.605-0.277,2.401-0.277c2.142,0,4.221,0.664,6.024,1.95c2.834,2.021,4.71,5.023,5.285,8.457
							c0.525,3.14-0.096,6.293-1.751,8.977l-21.295,19.067h-15.357L179.248,23.833z M99.549,28.76c0.574-3.432,2.451-6.436,5.286-8.457
							c2.473-1.764,5.463-2.359,8.425-1.673c2.959,0.684,5.386,2.533,6.833,5.204l17.86,32.969h-15.357l-21.296-19.067
							C99.645,35.053,99.024,31.898,99.549,28.76z M115.508,280.981H37.265V133.367h78.243V280.981z M165.472,280.981h-31.604V133.367
							h31.604V280.981z M262.076,280.981h-78.243V133.367h78.243V280.981z M273.688,115.007H25.653V75.163h248.034V115.007z"/>
				</g>
				</svg>
			</div>		
			Выбрать бонус
		</button>
	</div>
	<?php do_action('woocommerce_after_cart_table'); ?>
</form>

<style>

	.swal2-popup, .swal2-modal, .swal2-container{
		font-size: 18px;
	}
	.actions-column .modal-btn{
		display: inline-flex;
		gap: 10px;
	}
	.actions-column .modal-btn svg{
		vertical-align: inherit;
		fill: #fff;
		height: 14px;
	}

	.modal-btn{
		background-color: #EE6213;
		color: #fff;
		display: block;
		padding: 11px 40px;
		opacity: 0.8;
		transition: all 0.5s;
		font-weight: 400;
	}
	.cart-modal-wrapper{
		text-align: center;
		text-align: center;
    	margin-top: 15px;
    	/* margin-bottom: 15px; */
	}
	.cart-modal-wrapper h3{
		margin: 0;
	}	
	.cart-modals-dialog{
		width: 30em;
		height: auto;
		min-height: 10em;
		display: flex;
		flex-direction: column;
		align-items: center;
	}
	.cart-modals-dialog .modal-footer, .modal-content-contener{
		width: 100%;
		text-align: center;
		padding: 10px 5px;
	}

	.cart-modals-dialog .modal-footer-buttons{
		display: flex;
		justify-content: space-around;
		align-items: center;
		flex-direction: column;
		gap: 5px;	
	}
	.auto_coupon_one_confirm .modal-footer-buttons{
		flex-direction: column;
		gap: 5px;		
	}
	.modal-footer-buttons button{
		min-width: 70%;

	}
	.cart-collaterals .modal-btn{
		display: none;
	}
	@media (max-width: 425px){
		.cart-collaterals .modal-btn{
			display: inline-flex;
			gap: 10px;
			padding: 11px 32px;
		}
		.cart-collaterals{
			text-align: center;
		}
		.cart-collaterals .modal-btn svg{
			vertical-align: inherit;
			fill: #fff;
			height: 14px;
		}
	}
</style>
<!--Выбор акции или автокупона -->
<div class="select-address-start-mod cart-modals-dialog" id="promo_or_auto_coupon_confirm" style="display:none">

	<div class="promo_or_auto_coupon_confirm cart-modal-wrapper">
		<!--<h3>Внимание!</h3>-->
		<div class="modal-content-contener">
		</div>
		<div class="modal-footer">
			<div class="modal-footer-buttons">
			</div>
		</div>	
	</div>

</div>
<!--Выбор автокупона -->
<div class="select-address-start-mod cart-modals-dialog" id="auto_coupon_one_confirm" style="display:none">

	<div class="auto_coupon_one_confirm cart-modal-wrapper">
		<!--<h3>Внимание!</h3>-->
		<div class="modal-content-contener">
		</div>
		<div class="modal-footer">
			<div class="modal-footer-buttons"></div>
		</div>	
	</div>

</div>
<!--Сообщение про купон-->
<div class="select-address-start-mod cart-modals-dialog" id="coupon_error" style="display:none">

	<div class="coupon_error cart-modal-wrapper">
		<!--<h3>Внимание!</h3>-->
		<div class="modal-content-contener"></div>

	</div>
</div>
<!--Промокод с чем то конфликтует-->
<div class="select-address-start-mod cart-modals-dialog" id="coupon_conflict" style="display:none">

	<div class="coupon_conflict cart-modal-wrapper">
		<!--<h3>Внимание!</h3>-->
		<div class="modal-content-contener"></div>
		<div class="modal-footer">
			<div class="modal-footer-buttons"></div>
		</div>	
	</div>
</div>


<?php do_action('woocommerce_after_cart'); ?>

<form></form>
<script>
	$(document).ready(function(){
		$cart_has_bonus = false;
		$user_take_bonus = false;
		$user_is_login = '<?=is_user_logged_in()?>';


 
		if($user_is_login == ""){
			//alert(1);
			setTimeout(function() { $('.userhead').click(); }, 300);
		//	return;
		}else{
			load_promos_data();
		}


	
		$(document).on('click', '[data-acept-promo-or-coupon]', function(e){
			var type = ($(this).data('acept-promo-or-coupon'));
			
			if(type == 'promo') set_default_promo();
			if(type == 'coupon') set_default_autocoupons();

			close_modal('');
		});

		$(document).on('click', '[data-acept-promo]', function(e){
			set_default_promo();

			close_modal('');
		});		
		
		$(document).on('click', '[data-acept-autocoupon]', function(e){
			var code = ($(this).data('acept-autocoupon'));
			
			if(code) set_default_autocoupon_one(code);

			close_modal('');
		});		

		$(document).on('click', '[data-acept-coupon]', function(e){
			// var code = ($(this).data('acept-coupon'));
			
			// if(code) set_default_autocoupon_one(code);

			// close_modal('');

			cart_form_submit();
			$.fancybox.close();
		});		

		$(document).on('click', '[data-reload-promos]', function(e){
			load_promos_data()
		});		
		
		function cart_form_submit(){
			$form = $('form.woocommerce-cart-form');
			$form.submit();
		}

		$(document).on('click', '[name="apply_coupon"]', function(e){
				e.preventDefault();
				e.stopPropagation();
				
				if($user_is_login == ""){
					$('.userhead').click();
					return;
				}


				var $button = $(this),
				$form = $('.woocommerce-cart-form'),
				$code = $('[name="coupon_code"]').val();
				
				//alert($code);
				//check_rules
				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					data: {
						action: 'check_rules',
						coupon: $code,
					},
					type: 'post',
					dataType: 'json',
					//data: $("#callback_form").serialize(),
					beforeSend: function() {
						$button.text('Проверка');
					},
					complete: function() {
						//$('#callback_form_btn').button('reset');
						$button.text('Применить');
					},
					success: function(json) {
						//$('.alert-success, .alert-danger').remove(); 
						console.warn(json);

						if(json.coupon_check == false){

							swal_coupon_error('Купон не действителен');
							return;
						}else if(json.referal_coupon == true){ //на рефральный купон действует исключение
							cart_form_submit();
							return;
						}else if( json.has_applied_coupons && json.aplied_coupons_list.indexOf( $code ) != -1 ){
							swal_coupon_error('Купон уже применён');
							return;

						}else if( json.has_applied_coupons && !json.coupons_summing ){
							swal_coupon_error('У Вас уже есть применённый промокод');
							return;
						}

						//автокупоны применены и не сумируются с купонами
						if(!json['coupons_and_promo_summing'] && json['auto_apply_coupons_list'].length){
							var $message = 'Ваш Промокод не суммируется, выберите нужный';
							var $buttons = make_autocoupons_buttons(json['all_avaible_auto_coupons']);
							if(!json['coupons_and_promo_summing'] && json['apply_promos_list'].length){
								$buttons+= make_promos_buttons(json['all_avaible_promos']);
							}
							$buttons+=`<button class="promo modal-btn" data-acept-coupon="${$code}">${$code}</button>`;

							swal_coupon_conflict($message, $buttons);
						}

						//акции применены и не сумируются с купонами
						else
						if(!json['coupons_and_promo_summing'] && json['apply_promos_list'].length){
							var $message = 'Ваш Промокод не суммируется, выберите нужный';
							var $buttons = make_promos_buttons(json['all_avaible_promos']);

							if(!json['coupons_and_promo_summing'] && json['all_avaible_auto_coupons'].length){
								$buttons+= make_autocoupons_buttons(json['all_avaible_auto_coupons']);
							}

							$buttons+=`<button class="promo modal-btn" data-acept-coupon="${$code}">${$code}</button>`
							
								

							swal_coupon_conflict($message, $buttons);
						}
						else
						{
							cart_form_submit();
						}

					}
				});

		});

		function swal_coupon_conflict($message, $buttons){ 
			$('#coupon_conflict .modal-content-contener').html( $message );

			$('#coupon_conflict .modal-footer-buttons').html( $buttons )

			show_modal('coupon_conflict');
		}

		function swal_coupon_error($message){

			$('#coupon_error .modal-content-contener').html( $message );

			show_modal('coupon_error');
		}

		function swal_promo_or_auto_coupon_confirm($all_avaible_promos, $all_avaible_auto_coupons, $coupons = []){
			console.error($all_avaible_promos);
			$('#promo_or_auto_coupon_confirm .modal-content-contener').html( 
				`Выберите свой бонус:`
			)

			var $buttons = make_autocoupons_buttons($all_avaible_auto_coupons)
			$buttons+= make_promos_buttons( $all_avaible_promos )
			$buttons+= make_coupons_buttons( $coupons )

			$('#promo_or_auto_coupon_confirm .modal-footer-buttons').html( $buttons )
			
			show_modal('promo_or_auto_coupon_confirm')
		}

		function make_autocoupons_buttons($data){
			return $data.map( coupon => `<button class="promo modal-btn" data-acept-autocoupon="${coupon}">${coupon}</button>`).join('');
		}

		function make_promos_buttons($data){
			return $data.map( promo => `<button class="promo modal-btn" data-acept-promo>${promo.title}</button>` ).join('');
		}	

		function make_coupons_buttons($data){
			return $data.map( code => `<button class="promo modal-btn" data-acept-coupon="${code}" >${code}</button>` ).join('');
		}				

		function swal_auto_coupon_confirm($all_avaible_auto_coupons){

			$('#auto_coupon_one_confirm .modal-content-contener').html( 
				"Выберите себе купон:"
			)
			
			$('#auto_coupon_one_confirm .modal-footer-buttons').html( 
				make_autocoupons_buttons( $all_avaible_auto_coupons )
			)

			show_modal('auto_coupon_one_confirm')
						
		}
		

		function set_default_promo(){
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'set_auto_coupons_and_promo_priority',
					priority: 'promo',					
				},
				type: 'post',
				dataType: 'json',
				//data: $("#callback_form").serialize(),
				beforeSend: function() {
					//$button.text('Проверка');
				},
				complete: function() {
					//$('#callback_form_btn').button('reset');
					//$button.text('Применить');
				},
				success: function(json) {
					$form = $('form.woocommerce-cart-form');
					$form.find('input[type="number"]:first').change();
				}
			});
		}
		
		function set_default_autocoupons(){
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'set_auto_coupons_and_promo_priority',
					priority: 'auto_coupons',					
				},
				type: 'post',
				dataType: 'json',
				//data: $("#callback_form").serialize(),
				beforeSend: function() {
					//$button.text('Проверка');
				},
				complete: function() {
					//$('#callback_form_btn').button('reset');
					//$button.text('Применить');
				},
				success: function(json) {
					//cart_form_submit();
				}
			});
		}

		function set_default_autocoupon_one($coupon){

			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'set_auto_coupon_one',
					coupon: $coupon,					
				},
				type: 'post',
				dataType: 'json',

				success: function(json) {
					$form = $('form.woocommerce-cart-form');
					$form.find('input[type="number"]:first').change();
				}
			});
		}

		function load_promos_data(){
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'check_aplied_auto_coupons_and_promos',
				},
				type: 'post',
				dataType: 'json',
				//data: $("#callback_form").serialize(),
				beforeSend: function() {
					$('[data-reload-promos]').prop("disabled", "disabled")
					//$('[data-reload-promos]').text('Проверка');
				},
				complete: function() {
					$('[data-reload-promos]').prop("disabled", false);
					//$('[data-reload-promos]').text('Выбрать бонус');
					//$('#callback_form_btn').button('reset');
					//$button.text('Применить');
				},
				success: function(json) {
					//$('.alert-success, .alert-danger').remove(); 
					console.warn('check_aplied_auto_coupons_and_promos', json);
					//проверяем есть ли не применённые автокупоны/акции
					if(	
						!json['auto_coupons_and_promo_summing'] 
						&& ( json['auto_apply_coupons_list'].length || json['apply_promos_list'].length) 
						&& ( json['all_avaible_auto_coupons'].length && json['all_avaible_promos'].length)
						){
							if( json['all_avaible_promos'].length + json['all_avaible_auto_coupons'].length > 1)
								swal_promo_or_auto_coupon_confirm(json['all_avaible_promos'], json['all_avaible_auto_coupons']);
					}
					else
						if(
							!json['auto_coupons_summing'] 
							&& ( json['all_avaible_auto_coupons'].length ) 
							&& ( json['auto_apply_coupons_list'].length )
							){
								if( json['all_avaible_auto_coupons'].length > 1)
									swal_auto_coupon_confirm( json['all_avaible_auto_coupons'] );
							
						}
					else{
						if(
							!json['auto_coupons_and_promo_summing']
							&&(!json['coupons_and_auto_coupons_summing']&&json['all_avaible_auto_coupons'].length)
							&& !json['auto_coupons_summing']
							&& ( json['all_avaible_auto_coupons'].length && json['all_avaible_promos'].length)
							
						){
							if( json['all_avaible_promos'].length + json['all_avaible_auto_coupons'].length + json['aplied_coupons_list'].length > 1)
								swal_promo_or_auto_coupon_confirm(json['all_avaible_promos'], json['all_avaible_auto_coupons'], json['aplied_coupons_list']);
						}
					}

				}
			});
		}

	});
</script>