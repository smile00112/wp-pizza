<?php
defined( 'ABSPATH' ) || exit;

global $product;

$supplements_html = '';
$supplements_required = 0;

$custom_fields = get_fields($product->get_id()); // все допполя

$supplements_required = (isset($custom_fields['supplements_required']) && $custom_fields['supplements_required'] ) ? 1 : 0;

$supplements = $custom_fields[ 'supplements' ]; // повторитель supplements
$products_ids = array_column( $supplements, 'products' ); // id товаров не сгруппированые
$prod_ids = array(); // сгруппированые id товаров
foreach ( $products_ids as $pr_ids ) {
  $prod_ids = array_merge( $prod_ids, $pr_ids );
}
$prod_ids = array_unique( $prod_ids ); // id товаров без дублей
$products = wc_get_products( array( 'include' => $prod_ids, 'limit' => count( $prod_ids ) ) ); // список товаров
$prod = array(); // массив с значениями из товаров
foreach ( $products as $p ) {
  $prod[ $p->get_id() ] = array( 'name' => $p->get_name(), 'price' => $p->get_price(), 'price_html' => $p->get_price_html(), 'product' => $p );
}

$i = 0;
foreach ( $supplements as $supp ) {
  $i++;
  $group_required = $supplements_required;
  if($supp['supplement_required'] == 'yes')
    $group_required = true;

  $supplements_html .= '<div class="supp_content">';
  //if( $supp['show_title'] ) 
  $supplements_html .= "<h4 class=\"supp-h\">{$supp['title']}</h4>";
  if( ($supp[ 'type' ] == 'chekbox' || $supp[ 'quantity' ] == 'multiple' ) && $supp['quantity_max'] > 0 )
    $supplements_html .= "<h5 class=\"supp-h_max\">Не более {$supp['quantity_max']} шт., осталось <span data-max_ostatok=\"{$supp['quantity_max']}\">{$supp['quantity_max']}</span> шт.</h5>";
  
  $supplements_html .= '<div class="supplements  supplements-' . $supp[ 'quantity' ] . ' supplements-' . $supp[ 'type' ] . '" data-quantity="' . $supp[ 'quantity' ] . '" data-quantity _max="' . $supp[ 'quantity_max' ] . '"  data-type="' . $supp[ 'type' ] . '" data-group_requred="'.$group_required.'" >';
    //switch ( $supp[ 'type' ] ) {
    switch ( $supp[ 'quantity' ] ) {      
      case 'multiple':
        $inputs = '';
        foreach ( $supp[ 'products' ] as $vp ) {
          $inputs .= '
          <div class="supp-div" data-prod="' . $vp . '" data-price="' . $prod[ $vp ][ 'price' ] . '" >

            <label class="checkbox-ios supp-label" style="" for="cb_' .$product->get_id(). '_' . $i . '_' . $vp . '">

            <div class="supp-quantity woocommerce-cart-form__cart-item" data-prod="' . $vp . '">' . woocommerce_quantity_input( array( 'min_value' => 0, 'max_value' => $supp['quantity_max'] /*$prod[ $vp ][ 'product' ]->get_stock_quantity()*/, 'input_value' => 0, ), $prod[ $vp ][ 'product' ], false ) . '</div>

            <input class="checkbox-other supp-checkbox" type="'.($supp[ 'type' ]=='chekbox' ? 'checkbox' : 'radio').'" name="cb_' .$product->get_id(). '_' . $i . '" id="cb_' .$product->get_id(). '_' . $i . '_' . $vp . '" value="' . $vp . '">
            <span class="checkbox-ios-switch"></span>

            <span class="supp-name">' . $prod[ $vp ][ 'name' ] . '</span> 
            <div class="supp-price">+' . $prod[ $vp ][ 'price_html' ] . '</div>
            </label>
          </div>
    ';
      }
      $supplements_html .=  $inputs;
      break;

    case 'once':
      $inputs = '';
      foreach ( $supp[ 'products' ] as $vp ) {
        
        $inputs .= '
        <div class="supp-div" data-prod="' . $vp . '" data-price="' . $prod[ $vp ][ 'price' ] . '" >
        <label class="checkbox-ios supp-label" for="cb_' .$product->get_id(). '_' . $i . '_' . $vp . '">
          <div class="supp-quantity woocommerce-cart-form__cart-item" style="display:none">' . woocommerce_quantity_input( array( 'min_value' => 0, 'max_value' => 999, 'input_value' => 0, 'product_name' => $supp['title'] ), null, false ) . '</div>
          <input type="'.($supp[ 'type' ]=='chekbox' ? 'checkbox' : 'radio').'" class="supp-radio checkbox-other" name="cb_' . $i . '" id="cb_' .$product->get_id(). '_' . $i . '_' . $vp . '" data-id="' .$product->get_id(). '_' . $i . '_' . $vp . '" data-price="' . $prod[ $vp ][ 'price' ] . '" value="' . $vp . '" style="display:none;">
          <span class="checkbox-ios-switch"></span>
          <span class="supp-name">' . $prod[ $vp ][ 'name' ] . '</span> 
          <div class="supp-price">' . ($prod[ $vp ][ 'price_html' ] ? '+'.$prod[ $vp ][ 'price_html' ] : '') . '</div>
        </label> 
        </div>
  ';
      }
      $supplements_html .= $inputs ;

      break;

    case 'list':
      $option = '';
      foreach ( $supp[ 'products' ] as $vp ) {
        $option .= '<option value="' . $vp . '"  data-price="' . $prod[ $vp ][ 'price' ] . '" >' . $prod[ $vp ][ 'name' ] . ' ' . $prod[ $vp ][ 'price_html' ] . '</option>';
      }
      $supplements_html .= '<select class="supp-select"><option value="">---</option>' . $option . '</select>
      <div class="supp-quantity woocommerce-cart-form__cart-item">' . woocommerce_quantity_input( array( 'min_value' => 0, 'input_value' => 0, 'max_value' => 999, 'product_name' => $supp['title'] ), null, false ) . '</div>';
      break;
  }
$supplements_html .=  '</div>';
$supplements_html .=  '</div>';

}

wp_reset_postdata();
?>

<div class="custom-product--modal-inner">
  <div class="custom_product--modal-header">
    <h3>Выберите опции</h3>
    <div class="custom-product--modal-close custom-product--modal-close-v2">&times;</div>
  </div>

  <div class="custom-product--modal-content" style="overflow: hidden;">
    <div class="inner-block">
      <?php echo $supplements_html; ?>
    </div>  
  </div>
  <!--<div class="custom-product--modal-clones" data-product_id="<?php echo $product->get_id(); ?>"></div>-->
  
  <div class="custom-product--modal-clones" data-product_id="<?php echo $product->get_id(); ?>">
    <div class="product-counter" data-price="<?php echo $product->get_price(); ?>">
      <div class="minus changer"><i class="fa fa-minus"></i></div>
      <input type="number" value="1" min="1">
      <div class="plus changer"><i class="fa fa-plus"></i></div>
    </div>
    <button class="custom-product--modal-add-to-cart ajax_add_to_cart add_to_cart_button" data-product_id="<?php echo $product->get_id(); ?>" data-variation_id="0" data-quantity="1" data-supplements="{}">Добавить в корзину за <span class="summ"><?php echo ($product->is_on_sale() ? $product->get_sale_price() : $product->get_price()); ?></span> <i class="fa fa-rub"></i></button>
  </div>

</div>

<style>
   .supplements-multiple input[type="radio"]{
   display: none;
  }
</style>