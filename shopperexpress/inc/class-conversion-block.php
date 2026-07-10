<?php
/**
 * Conversion Block
 *
 * @package Shopperexpress
 */

class ConversionBlock {

	public $vin;
	public $post_type;
	public $post_id;

	/**
	 * ConversionBlock constructor.
	 *
	 * @param string $vin
	 * @param string $post_type
	 * @param string $post_id
	 */
	public function __construct( $vin = '', $post_type = '', $post_id = '' ) {
		$this->vin       = $vin;
		$this->post_type = $post_type;
		$this->post_id   = $post_id;
	}

	/**
	 * Render the Conversion Block.
	 *
	 * @return string
	 */
	public function render() {

		ob_start();
		get_template_part(
			'template-parts/ConversionBlock',
			null,
			array(
				'vin'      => $this->vin,
				'location' => $this->getLocation(),
				'post_id'  => $this->post_id,
			)
		);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Get the location.
	 *
	 * @return string
	 */
	public function getLocation() {

		switch ( $this->post_type ) {
			case 'offers':
				$output = 'service-offers_';
				break;

			case 'finance-offers':
				$output = 'finance-offers_';
				break;

			case 'lease-offers':
				$output = 'lease-offers_';
				break;

			case 'conditional-offers':
				$output = 'conditional-offers_';
				break;

			default:
				$output = null;
				break;
		}

		if ( is_single() ) {
			$output = $output . 'single_';
		}

		return $output;
	}
}

add_action(
	'wp_head',
	function () {

		if ( is_singular( array( 'offers', 'used-listings', 'listings', 'conditional-offers', 'lease-offers', 'finance-offers' ) ) || is_page_template( 'pages/template-saved.php' ) || is_page_template( 'pages/template-srp.php' ) || is_post_type_archive( array( 'offers', 'used-listings', 'listings', 'conditional-offers', 'lease-offers', 'finance-offers' ) ) ) :
			$ConversionBlock = new ConversionBlock( 0, get_post_type() );

			$font_styling = $font_size_1 = $weight_1 = $font_size_2 = $weight_2 = $font_size_3 = $weight_3 = $font_size_4 = $weight_4 = $font_size_5 = $weight_5 = $primary_color = $color_2 = $color_3 = $text_color = null;

			while ( have_rows( $ConversionBlock->getLocation() . 'colors', 'options' ) ) :
				the_row();
				$primary_color          = get_sub_field( 'primary_color' );
				$color_2                = get_sub_field( 'color_2' );
				$color_3                = get_sub_field( 'color_3' );
				$text_color             = get_sub_field( 'text_color' );
				$new_style_color_widget = get_sub_field( 'new_style_color_widget' );
			endwhile;

			while ( have_rows( $ConversionBlock->getLocation() . 'fonts', 'options' ) ) :
				the_row();
				$font_styling = get_sub_field( 'font_styling' );
				$font_size_1  = get_sub_field( 'font_size_1' );
				$weight_1     = get_sub_field( 'weight_1' );
				$font_size_2  = get_sub_field( 'font_size_2' );
				$weight_2     = get_sub_field( 'weight_2' );
				$font_size_3  = get_sub_field( 'font_size_3' );
				$weight_3     = get_sub_field( 'weight_3' );
				$font_size_4  = get_sub_field( 'font_size_4' );
				$weight_4     = get_sub_field( 'weight_4' );
				$font_size_5  = get_sub_field( 'font_size_5' );
				$weight_5     = get_sub_field( 'weight_5' );
			endwhile;
			?>
		<style>
			:root {
				--mini-w-color: <?php echo esc_attr( $new_style_color_widget ); ?>;
			}

			.block_popup {
				font-family: <?php echo esc_html( $font_styling ); ?>;
			}

			.widget--btn__price-sub,
			.widget--btn__price-sup {
				font-size: <?php echo esc_html( $font_size_5 ); ?>px;
				font-weight: <?php echo esc_html( $weight_5 ); ?>
			}

			.widget--btn__num {
				font-size: <?php echo esc_html( $font_size_4 ); ?>px;
				font-weight: <?php echo esc_html( $weight_4 ); ?>
			}

			.fonttype3 {
				font-size: <?php echo esc_html( $font_size_3 ); ?>px;
				font-weight: <?php echo esc_html( $weight_3 ); ?>;
				color: <?php echo $text_color; ?>;
			}

			.fonttype1 {
				font-size: <?php echo esc_html( $font_size_1 ); ?>px;
				font-weight: <?php echo esc_html( $weight_1 ); ?>
			}

			.fonttype2 {
				font-size: <?php echo esc_html( $font_size_2 ); ?>px;
				font-weight: <?php echo esc_html( $weight_2 ); ?>
			}

			.showWidget,
			.showWidget button {
				color: <?php echo $text_color; ?>;
				font-family: <?php echo esc_html( $font_styling ); ?>, sans-serif;
			}

			.widget--btn__body {
				background-color: <?php echo $color_3; ?>;
			}

			.widget--buttons__item,
			.widget--btn__footer {
				background-color: <?php echo esc_html( $primary_color ); ?> !important;
				font-family: <?php echo esc_html( $font_styling ); ?>, sans-serif;
			}

			.widget--buttons__item:hover,
			.widget--buttons__item:focus,
			.paymentbtn:hover .widget--btn__footer,
			.paymentbtn:focus .widget--btn__footer {
				background-color: <?php echo $color_2; ?> !important;
			}

			.widget--buttons__holder a {
				color: <?php echo esc_html( $text_color ); ?>;
			}

			.widget--buttons__holder a:hover,
			.widget--buttons__holder a:focus {
				background: <?php echo esc_html( $color_2 ); ?> !important;
				border-color: <?php echo esc_html( $color_2 ); ?> !important;
			}

			.reverse-button {
				background-color: <?php echo esc_html( $primary_color ); ?> !important;
				color: <?php echo esc_html( $text_color ); ?> !important;
			}

			.fonttype4 {
				font-size: <?php echo esc_html( $font_size_4 ); ?>px;
				font-weight: <?php echo esc_html( $weight_4 ); ?>;
			}

			.fonttype5 {
				font-size: <?php echo esc_html( $font_size_5 ); ?>px;
				font-weight: <?php echo esc_html( $weight_5 ); ?>;
			}
		</style>
			<?php
	endif;
	}
);
