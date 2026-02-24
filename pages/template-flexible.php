<?php
/*
Template Name: Flexible Page Template
*/
get_header();

$is_new_home_page_styles = get_field( 'new_home_page_styles' );
?>
<div 
<?php
if ( ! $is_new_home_page_styles ) {
	echo 'id="page-container"';}
?>
<?php
if ( get_field( 'page_style' ) == 2 ) :
	?>
	class="trade-value-page"<?php endif; ?>>
	<?php
	$i = 1;
	while ( have_rows( 'content_blocks' ) ) :
		the_row();
		if ( $i == get_row_index() ) {
			get_template_part( 'template-parts/acf/acf', get_row_layout() );
			++$i;
		}
	endwhile;
	?>
	</div>
<?php get_footer(); ?>
