<?php
/**
 * Accordion template
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

$page_id       = ! empty( $args['page_id'] ) ? $args['page_id'] : 'options';
$get_post_type = ! empty( $args['post_type'] ) ? $args['post_type'] : '';
$get_type      = ! empty( $args['type'] ) ? $args['type'] : 'single';
$accordion     = array();

if ( 'random' === $get_type ) {

	$get_post_type = sanitize_title( $get_post_type );

	$possible_values = array(
		$get_post_type,
		ucwords( str_replace( '-', ' ', $get_post_type ) ),
		ucfirst( str_replace( '-', ' ', $get_post_type ) ),
		strtoupper( str_replace( '-', ' ', $get_post_type ) ),
	);

	$query = new WP_Query(
		array(
			'post_type'      => 'faq',
			'posts_per_page' => 3,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'rand',
			'meta_query'     => array(
				array(
					'key'     => 'type',
					'value'   => $possible_values,
					'compare' => 'IN',
				),
			),
		)
	);

	if ( $query->posts ) {
		foreach ( $query->posts as $item ) {
			$get_title = get_the_title( $item );
			$get_text  = get_the_content( false, false, $item );
			if ( $get_title || $get_text ) {
				$accordion[] = array(
					'title' => $get_title,
					'text'  => $get_text,
				);
			}
		}
		wp_reset_postdata();
	}
} else {
	while ( have_rows( 'accordion_srp', $page_id ) ) :
		the_row();
		$get_title = get_sub_field( 'title' );
		$get_text  = get_sub_field( 'text' );
		if ( $get_title || $get_text ) {
			$accordion[] = array(
				'title' => $get_title,
				'text'  => $get_text,
			);
		}
	endwhile;
}

if ( $accordion ) :
	?>
	<div class="accordion-wrapp">
		<?php if ( 'random' === $get_type ) : ?>
			<div class="container">
				<div class="heading">
					<h3><?php esc_html_e( 'Frequently Asked Questions', 'shopperexpress' ); ?></h3>
				</div>
			<?php endif; ?>
			<ul class="accordion-info" id="accordionInfo" itemscope itemtype="https://schema.org/FAQPage">
				<?php
				foreach ( $accordion as $row => $item ) :
					$get_title = $item['title'];
					$get_text  = $item['text'];
					if ( $get_title || $get_text ) :
						?>
						<li itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
							<div id="heading<?php echo esc_attr( $row ); ?>">
								<h2>
									<button
										itemprop="name"
										class="accordion-info-opener
									<?php
									if ( 0 !== $row ) :
										?>
										collapsed<?php endif; ?>"
										type="button"
										data-toggle="collapse"
										data-target="#collapse<?php echo esc_attr( $row ); ?>"
										aria-expanded="
									<?php if ( 0 !== $row ) : ?>
										true
										<?php
									else :
										?>
											false<?php endif; ?>"
										aria-controls="collapse<?php echo esc_attr( $row ); ?>">
										<?php echo esc_html( $get_title ); ?>
										<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="40px" viewBox="0 -960 960 960" width="40px" fill="#000000">
											<path
												d="m587-481.33-308-308q-12.33-12.34-12.17-30.17.17-17.83 12.5-30.83 13-13 30.84-13 17.83 0 30.83 13l321.67 322q10 10 14.66 22.33 4.67 12.33 4.67 24.67 0 12.33-4.67 24.66-4.66 12.34-14.66 22.34L340-111.67q-13 13-30.33 12.5-17.34-.5-30.34-13.5-12.33-13-12.66-30.5-.34-17.5 12.66-30.5L587-481.33Z" />
										</svg>
									</button>
								</h2>
							</div>
							<div
								itemscope
								itemprop="acceptedAnswer"
								itemtype="https://schema.org/Answer"
								id="collapse<?php echo esc_attr( $row ); ?>"
								class="collapse
							<?php
							if ( 0 === $row ) :
								?>
								show<?php endif; ?>"
								aria-labelledby="heading<?php echo esc_attr( $row ); ?>"
								data-parent="#accordionInfo">
								<div itemprop="text" class="accordion-info-body">
									<?php echo wp_kses_post( $get_text ); ?>
								</div>
							</div>
						</li>
						<?php
					endif;
				endforeach;
				?>
			</ul>
			<?php if ( 'random' === $get_type ) : ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
