<?php
/**
 * Template Name: Blank Page Template
 *
 * @package pizzaro
 */
remove_action( 'pizzaro_content_top', 'pizzaro_breadcrumb', 10 );

if(empty( $_GET['mobile_app'] ))
get_header( 'blank' ); 



if(!empty( $_GET['mobile_app'] )){
	?>
		<style>
		@font-face {
			font-family: 'Tahoma';
			src: url('Tahoma-Bold.eot');
			src: local('Tahoma Bold'), local('Tahoma-Bold'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma-Bold.eot?#iefix') format('embedded-opentype'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma-Bold.woff2') format('woff2'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma-Bold.woff') format('woff'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma-Bold.ttf') format('truetype');
			font-weight: bold;
			font-style: normal;
		}
	
		@font-face {
			font-family: 'Tahoma';
			src: url('Tahoma.eot');
			src: local('Tahoma'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma.eot?#iefix') format('embedded-opentype'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma.woff2') format('woff2'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma.woff') format('woff'),
				url('/wp-content/themes/pizzaro/assets/fonts/Tahoma.ttf') format('truetype');
			font-weight: normal;
			font-style: normal;
		}
		.entry-content, .kc_text_block{
			font-family: Tahoma;
			padding:15px; 
			font-size:4.5vw;
		} 
	
		
		</style>
	<?
	}
	
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php while ( have_posts() ) : the_post();

				do_action( 'pizzaro_page_before' );

				get_template_part( 'content', 'page' );

				/**
				 * Functions hooked in to pizzaro_page_after action
				 *
				 * @hooked pizzaro_display_comments - 10
				 */
				do_action( 'pizzaro_page_after' );

			endwhile; // End of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
if(empty( $_GET['mobile_app'] ))
get_footer( 'blank' );