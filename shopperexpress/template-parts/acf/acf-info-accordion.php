<?php
$section_row = get_row_index();

$faq_items = array();

if ( have_rows( 'accordion' ) ) :
	?>
	<section class="section-info-accordion">
		<div class="container">
			<ul class="accordion-info" id="accordionInfo-<?php echo esc_attr( $section_row ); ?>">
				<?php
				while ( have_rows( 'accordion' ) ) :
					the_row();
					$row     = get_row_index();
					$heading = get_sub_field( 'heading' );
					$text    = get_sub_field( 'text' );
					if ( ! empty( $heading ) && ! empty( $text ) ) {
						$faq_items[] = array(
							'title' => $heading,
							'text'  => $text,
						);
					}

					$expanded  = 1 === $row ? 'true' : 'false';
					$collapsed = 1 !== $row ? ' collapsed' : '';
					?>
					<li>
						<div id="heading-<?php echo esc_attr( $row ); ?>">
							<h3>
								<button class="accordion-info-opener<?php echo $collapsed; ?>" type="button" data-toggle="collapse" data-target="#collapse-<?php echo esc_attr( $row ); ?>" aria-expanded="<?php echo $expanded; ?>" aria-controls="collapse-<?php echo esc_attr( $row ); ?>">
									<?php echo esc_html( $heading ); ?>
									<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="40px" viewBox="0 -960 960 960" width="40px" fill="#000000">
										<path
										d="m587-481.33-308-308q-12.33-12.34-12.17-30.17.17-17.83 12.5-30.83 13-13 30.84-13 17.83 0 30.83 13l321.67 322q10 10 14.66 22.33 4.67 12.33 4.67 24.67 0 12.33-4.67 24.66-4.66 12.34-14.66 22.34L340-111.67q-13 13-30.33 12.5-17.34-.5-30.34-13.5-12.33-13-12.66-30.5-.34-17.5 12.66-30.5L587-481.33Z"
										/>
									</svg>
								</button>
							</h3>
						</div>
						<div id="collapse-<?php echo esc_attr( $row ); ?>" class="collapse
						<?php
						if ( 1 === $row ) :
							?>
							show<?php endif; ?>" aria-labelledby="heading-<?php echo esc_attr( $row ); ?>" data-parent="#accordionInfo-<?php echo esc_attr( $section_row ); ?>">
							<div class="accordion-info-body">
								<?php echo wp_kses_post( $text ); ?>
							</div>
						</div>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php
			// JSON-LD FAQ Schema generation
			if ( ! empty( $faq_items ) ) :
				$faq_json = array(
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => array(),
				);
				foreach ( $faq_items as $item ) {
					$faq_json['mainEntity'][] = array(
						'@type'          => 'Question',
						'name'           => wp_strip_all_tags( $item['title'] ),
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => wp_strip_all_tags( $item['text'] ),
						),
					);
				}
				?>
				<script type="application/ld+json">
					<?php echo wp_json_encode( $faq_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
				</script>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>
