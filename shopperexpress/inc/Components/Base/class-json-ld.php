<?php
/**
 * WordPress JSON LD.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class JSON_LD
 *
 * @package App\Base\Component
 */
class JSON_LD implements Theme_Component {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'render_json' ) );
	}

	public function render_json(): void {

		$is_page_with_pt = is_page() && get_field( 'post_type' );
		$is_archive_pt   = is_post_type_archive();

		if ( ! $is_page_with_pt && ! $is_archive_pt ) {
			return;
		}

		echo $this->get_json();
	}

	/**
	 * Get JSON LD.
	 *
	 * @return string
	 */
	public function get_json(): string {
		ob_start();

		if ( is_post_type_archive() ) {

			$pt_object = get_queried_object();
			$post_type = $pt_object->name;
			$title     = $pt_object->labels->singular_name;
			$permalink = get_post_type_archive_link( $post_type );

		} else {

			$post_id   = get_the_ID();
			$post_type = get_field( 'post_type', $post_id );
			$title     = get_the_title( $post_id );
			$permalink = get_permalink( $post_id );

		}
		$i             = 1;
		$transient     = get_field( $post_type . '_transient', 'option' );
		$get_transient = get_transient( $transient );

		if ( $get_transient ) :
			$get_transient['vehicles'] = array_slice( $get_transient['vehicles'], 0, 24 );
			?>
		<script type="application/ld+json">
		{
			"@context": "https://schema.org",
			"@type": "CollectionPage",
			"@id": "<?php echo esc_url( $permalink ); ?>/#collection",
			"name": "<?php echo esc_html( $title ); ?>",
			"url": "<?php echo esc_url( $permalink ); ?>",
			"mainEntity": {
			"@type": "ItemList",
			"numberOfItems": <?php echo count( $get_transient['vehicles'] ); ?>,
			"itemListElement": [
			<?php foreach ( $get_transient['vehicles'] as $item ) : ?>
				{
				"@type": "ListItem",
				"position": <?php echo esc_attr( $i ); ?>,
				"url": "<?php echo esc_url( $item['link'] ); ?>",
				"item": {
					"@type": "ListItem",
					"name": "<?php echo esc_attr( $item['title'] ); ?>",
					"url": "<?php echo esc_url( $item['link'] ); ?>",
					<?php if ( ! empty( $item['photo'] ) ) : ?>
						"image": "<?php echo esc_url( $item['photo'] ); ?>",
					<?php endif; ?>
				<?php if ( ! empty( $item['terms']['make'][0] ) ) : ?>
					"brand": { "@type": "Brand", "name": "<?php echo esc_attr( $item['terms']['make'][0] ); ?>" },
				<?php endif; ?>
				<?php if ( ! empty( $item['terms']['model'][0] ) ) : ?>
					"model": "<?php echo esc_attr( $item['terms']['model'][0] ); ?>
					<?php
					if ( ! empty( $item['terms']['trim'][0] ) ) {
						echo esc_attr( $item['terms']['trim'][0] );
					}
					?>
					",
				<?php endif; ?>
				<?php if ( ! empty( $item['year'] ) ) : ?>
					"vehicleModelDate": "<?php echo esc_attr( $item['year'] ); ?>",
				<?php endif; ?>
				<?php if ( ! empty( $item['terms']['vin'][0] ) ) : ?>
					"vehicleIdentificationNumber": "<?php echo esc_attr( $item['terms']['vin'][0] ); ?>",
				<?php endif; ?>
					"offers": {
						"@type": "Offer",
				<?php if ( ! empty( $item['price'] ) ) : ?>
						"price": "<?php echo esc_attr( $item['price'] ); ?>",
				<?php endif; ?>
						"priceCurrency": "USD",
						"availability": "https://schema.org/InStock",
						"itemCondition": "https://schema.org/NewCondition",
						<?php if ( ! empty( $item['dealer_name'] ) ) : ?>
							"seller": {
								"@type": "AutoDealer",
								"name": "<?php echo esc_attr( $item['dealer_name'] ); ?>",
								"url": "<?php echo esc_url( $item['link'] ); ?>"
							}
						<?php endif ?>
						"url": "<?php echo esc_url( $item['link'] ); ?>"
						}
					}
				}
				<?php
				if ( $i < count( $get_transient['vehicles'] ) ) :
					?>
					,<?php endif; ?>
				<?php
				++$i;
			endforeach;
			?>
			]
			}
		}
		</script>
			<?php
		endif;
		$output = ob_get_clean();

		return $output;
	}
}
