<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package pizzaro
 */

$footer_version = pizzaro_get_footer_version();
   
if( isset($_GET['mobileapp']) && !empty($_GET['mobileapp'])) {} else { get_footer( $footer_version ); 
}?>