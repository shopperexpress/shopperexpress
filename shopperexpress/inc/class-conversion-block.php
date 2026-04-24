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

	public function __construct( $vin = '', $post_type = '', $post_id = '' ) {
		$this->vin       = $vin;
		$this->post_type = $post_type;
		$this->post_id   = $post_id;
	}

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
				$primary_color = get_sub_field( 'primary_color' );
				$color_2       = get_sub_field( 'color_2' );
				$color_3       = get_sub_field( 'color_3' );
				$text_color    = get_sub_field( 'text_color' );
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
			a:hover {
				text-decoration: none;
			}

			@-webkit-keyframes animate-loading {
				0% {
					transform: rotate(0deg);
				}

				100% {
					transform: rotate(360deg);
				}
			}

			@keyframes animate-loading {
				0% {
					transform: rotate(0deg);
				}

				100% {
					transform: rotate(360deg);
				}
			}

			/* @media (min-width: 1024px) { .mobile-button { display: none !important; }}@media (max-width: 1023px) { .desktop-button { display: none !important;}} */

			.mobile-button {
				display: none !important;
			}

			.desktop-button {
				display: block;
			}

			@media only screen and (min-device-width: 767px) and (-webkit-min-device-pixel-ratio: 2) {
				.desktop-button {
					display: block !important;
				}

				.mobile-button {
					display: none !important;
				}
			}

			@media only screen and (max-device-width: 767px) and (-webkit-min-device-pixel-ratio: 2) {
				.mobile-button {
					display: block !important;
				}

				.desktop-button {
					display: none !important;
				}
			}

			.widget--btn {
				-webkit-transition: box-shadow .15s ease-in-out;
				transition: box-shadow .15s ease-in-out;
				box-sizing: border-box;
				display: block;
				width: 100%;
				color: #fff;
				border: none;
				padding: 0;
				border-radius: 5px;
				overflow: hidden;
				cursor: pointer;
			}

			.widget--btn:focus {
				outline: none;
			}

			.widget--btn * {
				box-sizing: border-box;
				display: block;
			}

			.widget--btn__body {
				-webkit-transition: background-color .15s ease-in-out;
				transition: background-color .15s ease-in-out;
				text-align: center;
				padding: 6px 0 9px;
			}

			.widget--btn__row {
				display: -webkit-box;
				display: -ms-flexbox;
				display: flex;
				-webkit-box-pack: center;
				-ms-flex-pack: center;
				justify-content: center;
				padding: 0 0 6px;
			}

			.widget--btn__col {
				padding: 0 10px;
				-webkit-box-flex: 1;
				-ms-flex-positive: 1;
				flex-grow: 1;
			}

			.widget--btn__col .widget--btn__text {
				padding-bottom: 7px;
			}

			.widget--btn__text {
				display: block;
				color: #bfbfbf !important;
			}

			.widget--btn__price {
				display: -webkit-box;
				display: -ms-flexbox;
				display: flex;
				-webkit-box-pack: center;
				-ms-flex-pack: center;
				justify-content: center;
				line-height: 1;
			}

			.widget--btn__price-sup {
				-ms-flex-item-align: start;
				align-self: flex-start;
				display: inline-block;
				line-height: 1;
			}

			.widget--btn__price-sub {
				-ms-flex-item-align: end;
				align-self: flex-end;
				display: inline-block;
				line-height: 1.4;
			}

			.widget--btn__footer {
				background-color: #c12c1f;
				padding: 9px 13px;
				text-transform: uppercase;
				line-height: 1;
			}

			.widget--btn__footer-icon {
				width: 17px;
				height: auto;
				-ms-flex-negative: 0;
				flex-shrink: 0;
				margin-right: 10px;
			}

			@media (max-width:480px) {
				.widget--btn__num {
					font-size: 36px !important;
				}
			}

			img.widget--buttons__icon {
				transition: transform .7s ease-in-out;
			}

			.iconhover:hover img.widget--buttons__icon {
				transform: rotate(360deg);
			}

			.blockopopup_active {
				display: block !important;
			}

			.block_popup {
				text-transform: capitalize;
				font-family: <?php echo $font_styling; ?>;
				position: absolute;
				right: 1%;
				background: #fff;
				font-weight: 500;
				z-index: 2;
				top: 12px;
				max-width: 188px;
				width: 100%;
				padding: 10px;
				border-radius: 10px;
				display: none;
			}

			.widget--btn__price {
				color: #fff;
			}

			.widgetbox {
				margin-bottom: 10px;
			}

			.widget--btn__price-sub,
			.widget--btn__price-sup {
				font-size: <?php echo $font_size_5; ?>px;
				font-weight: <?php echo $weight_5; ?>
			}

			.widget--btn__num {
				font-size: <?php echo $font_size_4; ?>px;
				font-weight: <?php echo $weight_4; ?>
			}

			.fonttype3 {
				font-size: <?php echo $font_size_3; ?>px;
				font-weight: <?php echo $weight_3; ?>;
				color: <?php echo $text_color; ?>;
			}

			.fonttype1 {
				font-size: <?php echo $font_size_1; ?>px;
				font-weight: <?php echo $weight_1; ?>
			}

			.fonttype2 {
				font-size: <?php echo $font_size_2; ?>px;
				font-weight: <?php echo $weight_2; ?>
			}

			.widget--buttons__small {
				font-size: 8px;
				font-weight: 600
			}

			.showWidget,
			.showWidget button {
				color: <?php echo $text_color; ?>;
				display: block;
				font-family: <?php echo $font_styling; ?>, sans-serif;
				line-height: 1;
			}

			.hideWidget {
				display: none !important;
			}

			.widget--btn__body {
				background-color: <?php echo $color_3; ?>;
				opacity: 0.8;
			}

			.paymentbtn:hover .widget--btn__body,
			.paymentbtn:focus .widget--btn__body {
				opacity: 1;
			}

			.widget--buttons__item,
			.widget--btn__footer {
				background-color: <?php echo $primary_color; ?> !important;
				cursor: pointer;
				font-family: <?php echo $font_styling; ?>, sans-serif;
			}

			.widget--buttons__item:hover,
			.widget--buttons__item:focus,
			.paymentbtn:hover .widget--btn__footer,
			.paymentbtn:focus .widget--btn__footer {
				box-shadow: 0 2px 5px 0 rgb(0 0 0 / 26%);
				background-color: <?php echo $color_2; ?> !important;
			}

			.paymentbtn:hover,
			.paymentbtn:focus,
			.widget--buttons__holder a:hover,
			.widget--buttons__holder a:focus {
				box-shadow: 0 2px 5px 0 rgb(0 0 0 / 26%);
			}

			.widget--buttons__holder a {
				background: #fff;
				color: <?php echo $text_color; ?>;
				cursor: pointer;
			}

			.widget--buttons__holder a:hover,
			.widget--buttons__holder a:focus {
				background: <?php echo $color_2; ?> !important;
				color: #fff !important;
				border-color: <?php echo $color_2; ?> !important;
			}

			.se-lm-widget a,
			.se-lm-widget__col-logo strong {
				color: undefined;
			}

			.se-lm-widget-header,
			.se-lm-widget .btn-se-lm-widget {
				background-color: undefined;
				border: none;
			}

			.imghover:hover {
				box-shadow: 0 2px 5px 0 rgb(0 0 0 / 26%);
			}

			.showImage {
				display: block !important;
			}

			.showCustom {
				display: flex !important;
			}

			.hideImage {
				display: none !important;
			}

			.hideCustom {
				display: none !important;
			}

			.buttonimgbox {
				margin-bottom: 6px;
				width: 100%;
			}

			.buttonimgbox img {
				width: 100%;
				height: auto;
				object-fit: contain;
			}

			.showIcon {
				display: inline;
			}

			.reverse-button {
				background-color: <?php echo $primary_color; ?> !important;
				color: <?php echo $text_color; ?> !important;
			}

			.fonttype4 {
				font-size: <?php echo $font_size_4; ?>px;
				font-weight: <?php echo $weight_4; ?>;
			}

			.fonttype5 {
				font-size: <?php echo $font_size_5; ?>px;
				font-weight: <?php echo $weight_5; ?>;
			}
		</style>
			<?php
	endif;
	}
);
