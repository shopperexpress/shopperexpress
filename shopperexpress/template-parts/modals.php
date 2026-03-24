<?php
/**
 * Modal template
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Shopperexpress
 */

$modals    = array();
$post_type = get_field( 'post_type' );
if ( empty( $post_type ) ) {
	$post_type = is_post_type_archive() ? get_queried_object()->name : get_post_type( get_the_id() );
}
$vehicle_overview = get_field( 'vehicle_overview' ) ? wp_kses_post( str_replace( array( '<div>', '</div>' ), '', get_field( 'vehicle_overview' ) ) ) : '';
$modals[]         = array(
	'id'              => 'overviewModal',
	'title'           => '<h5 class="modal-title">' . esc_html__( 'Overview', 'shopperexpress' ) . '</h5>',
	'aria-labelledby' => 'overviewModalLabel',
	'content'         => $vehicle_overview,
	'footer'          => true,
);

ob_start();
if ( have_rows( 'features_options' ) ) :
	?>
	<ul class="modal-content-list">
		<?php
		while ( have_rows( 'features_options' ) ) :
			the_row();
			$text = get_sub_field( 'text' );
			if ( $text ) :
				?>
				<li><?php echo $text; ?></li>
				<?php
			endif;
		endwhile;
		?>
	</ul>
	<?php
endif;

$features_options = ob_get_clean();

$modals[] = array(
	'id'              => 'featuresAndOptionsModal',
	'title'           => '<h5 class="modal-title">' . esc_html__( 'Features & Options', 'shopperexpress' ) . '</h5>',
	'aria-labelledby' => 'featuresAndOptionsModalLabel',
	'content'         => $features_options,
	'footer'          => true,
);

if ( is_single() ) {
	$post_type = get_post_type( get_the_id() );
} elseif ( get_queried_object() ) {
	if ( isset( get_queried_object()->name ) ) {
		$post_type = get_queried_object()->name;
	} else {
		$post_type = get_field( 'post_type' );
	}
}
if ( ! wps_auth() ) {
	while ( have_rows( 'unlock_button_' . $post_type, 'options' ) ) :
		the_row();
		$title = get_sub_field( 'title' );
	endwhile;
} else {
	while ( have_rows( 'contact_button_' . $post_type, 'options' ) ) :
		the_row();
		$title = get_sub_field( 'title' );
	endwhile;
}

ob_start();
?>
<div id="savings-form">
	<?php
	$form_id = '';
	if ( is_post_type_archive( 'offers' ) || is_singular( 'offers' ) ) {
		$form_id = is_user_logged_in() ? get_field( 'contact_button_offers', 'options' )['form_id'] : get_field( 'unlock_button_offers', 'options' )['form_id'];
	} elseif ( is_post_type_archive( 'service-offers' ) || is_singular( 'service-offers' ) ) {
		$form_id = is_user_logged_in() ? get_field( 'contact_button_service-offers', 'options' )['form_id'] : get_field( 'unlock_button_service-offers', 'options' )['form_id'];
	} elseif ( is_single() && get_field( 'unlock_savings_form', 'options' ) ) {
		$form_id = get_field( 'unlock_savings_form', 'options' );
	}
	if ( $form_id ) {
		echo do_shortcode( '[wpforms id="' . $form_id . '"]' );
	}
	?>
</div>
<?php
$form_description = get_field( 'form_description', 'options' );
if ( $form_description ) :
	?>
	<div class="form-description text-center">
		<?php echo wp_kses_post( $form_description ); ?>
	</div>
	<?php
endif;
$content = ob_get_clean();

$modals[] = array(
	'id'              => 'unlockSavingsModal',
	'title'           => '<h5 class="modal-title">' . $title . '</h5>',
	'aria-labelledby' => 'unlockSavingsModalLabel',
	'content'         => $content,
	'wraper'          => false,
	'class'           => 'modal-md modal-form',
);

$modals[] = array(
	'id'              => 'buttonModal',
	'title'           => '<h3 class="modal-title" data-title></h3>',
	'aria-labelledby' => 'buttonModalLabel',
	'content'         => '<div id="button-form"></div>',
	'class'           => 'modal-md modal-form',
	'wraper'          => false,
);

ob_start();
if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
	?>
	<h4><?php echo $heading_save_offer; ?></h4>
	<?php
endif;
if ( $form_lease_special = get_field( 'form_lease_special', 'options' ) ) {
	echo do_shortcode( '[wpforms id="' . $form_lease_special . '"]' );
}
?>
	<div class="text-wrapp">
		<div class="text-holder scrollable h-sm">
			<?php echo wp_kses_post( get_field( 'disclosure_lease' ) ); ?>
		</div>
	</div>
<?php
$content = ob_get_clean();

$modals[] = array(
	'id'              => 'Disclosure_lease',
	'title'           => '<h3 class="modal-title">' . esc_html__( 'Additional Information', 'shopperexpress' ) . '</h3>',
	'aria-labelledby' => 'DisclosureLeaseLabel',
	'content'         => $content,
	'wraper'          => false,
);

ob_start();
$heading_save_offer = get_field( 'heading_save_offer', 'options' );
if ( $heading_save_offer ) :
	?>
	<h4><?php echo $heading_save_offer; ?></h4>
	<?php
endif;
if ( is_singular( 'offers' ) ) {
	$form_special_apr = get_field( 'form_id_special_apr', 'options' ) ? get_field( 'form_id_special_apr', 'options' ) : get_field( 'form_special_apr', 'options' );
	if ( $form_special_apr ) {
		echo do_shortcode( '[wpforms id="' . $form_special_apr . '"]' );
	}
}
?>
<div class="text-wrapp">
	<div class="text-holder scrollable h-sm">
		<?php echo wp_kses_post( get_field( 'disclosure_finance' ) ); ?>
	</div>
</div>

<?php
$content = ob_get_clean();

$modals[] = array(
	'id'              => 'Disclosure_loan',
	'title'           => '<h3 class="modal-title">' . esc_html__( 'Additional Information', 'shopperexpress' ) . '</h3>',
	'aria-labelledby' => 'DisclosureLoanLabel',
	'content'         => $content,
	'wraper'          => false,
);

ob_start();
if ( $heading_save_offer = get_field( 'heading_save_offer', 'options' ) ) :
	?>
	<h4><?php echo $heading_save_offer; ?></h4>
	<?php
endif;
if ( is_singular( 'offers' ) ) {
	$form_special_apr = get_field( 'offers_form_cash', 'options' ) ? get_field( 'offers_form_cash', 'options' ) : get_field( 'form_cash', 'options' );
	if ( $form_special_apr ) {
		echo do_shortcode( '[wpforms id="' . $form_special_apr . '"]' );
	}
}
?>
<div class="text-wrapp">
	<div class="text-holder scrollable h-sm">
		<?php echo wp_kses_post( get_field( 'disclosure_cash' ) ); ?>
	</div>
</div>
<?php
$content = ob_get_clean();

$modals[] = array(
	'id'              => 'Disclosure_Cash',
	'title'           => '<h3 class="modal-title">' . esc_html__( 'Additional Information', 'shopperexpress' ) . '</h3>',
	'aria-labelledby' => 'DisclosureCashLabel',
	'content'         => $content,
	'wraper'          => false,
);

while ( have_rows( 'popup_form-service-offers', 'options' ) ) :
	the_row();

	$heading = get_sub_field( 'heading' );
	$form_id = get_sub_field( 'form_id' );
	$text    = get_sub_field( 'text' );

	if ( $heading || $form_id || $text ) :

		ob_start();

		if ( $form_id ) {
			echo do_shortcode( '[wpforms id="' . $form_id . '" ]' );
		}
		if ( $text ) :
			?>
			<div class="form-description text-center">
				<?php echo wp_kses_post( $text ); ?>
			</div>
			<?php
		endif;

		$content = ob_get_clean();

		$modals[] = array(
			'id'              => 'sendtoemail',
			'title'           => '<h3 class="modal-title">' . esc_html( $heading ) . '</h3>',
			'aria-labelledby' => 'contactModalLabel',
			'content'         => $content,
			'wraper'          => false,
		);

		endif;
endwhile;

$popup_form = get_field( 'popup_form', 'options' );
if ( $popup_form ) {
	$link              = get_field( 'link', 'options' );
	$popup_form_before = get_field( 'popup_form_top_text', 'options' );
	$popup_form_after  = get_field( 'popup_form_bottom_text', 'options' );

	ob_start();

	if ( $popup_form_before ) :
		?>
		<div class="lead text-center">
		<?php echo $popup_form_before; ?>
		</div>
		<?php
	endif;

	echo do_shortcode( '[wpforms id="' . $popup_form . '" ]' );

	if ( $popup_form_after ) :
		?>
		<div class="form-description text-center">
			<?php echo $popup_form_after; ?>
		</div>
		<?php
	endif;

	$content = ob_get_clean();

	$modals[] = array(
		'id'              => 'contactModal',
		'title'           => '<h3 class="modal-title">' . esc_html( $link['title'] ) . '</h3>',
		'aria-labelledby' => 'contactModalLabel',
		'content'         => $content,
		'wraper'          => false,
	);
}

if ( is_post_type_archive( array( 'listings', 'used-listings' ) ) || is_singular( array( 'listings', 'used-listings' ) ) ) :
	$modals[] = array(
		'id'              => 'popUpDetails',
		'title'           => '<h3 class="modal-title">' . esc_html__( 'DETAILS', 'shopperexpress' ) . '</h3>',
		'aria-labelledby' => 'popUpDetails',
		'content'         => '<div class="content-holder"><p id="popUpDetailsText"></p></div>',
		'wraper'          => false,
	);
endif;

if ( is_post_type_archive( 'offers' ) ) :
	$modals[] = array(
		'id'              => 'detailModal-offers',
		'title'           => '<h3 class="modal-title">' . esc_html__( 'DETAILS', 'shopperexpress' ) . '</h3>',
		'aria-labelledby' => 'detailModalLabel',
		'content'         => '<div class="content-holder">' . wp_kses_post( get_field( 'custom_content' ) ) . '</div>',
		'wraper'          => false,
	);
endif;

if ( is_post_type_archive( 'used-listings' ) || is_singular( 'used-listings' ) || $post_type == 'used-listings' ) :
	$comment_footer = get_field( 'used_listings_comment_footer', 'option' );
else :
	$comment_footer = get_field( 'comment_footer', 'options' );
endif;

if ( $comment_footer ) {
	$modals[] = array(
		'id'              => 'detailModal',
		'title'           => '<h3 class="modal-title">' . esc_html__( 'DETAILS', 'shopperexpress' ) . '</h3>',
		'aria-labelledby' => 'detailModalLabel',
		'content'         => $comment_footer,
	);
}

foreach ( $modals as $modal ) :
		$wraper  = isset( $modal['wraper'] ) ? $modal['wraper'] : true;
		$wraper  = ! empty( $wraper ) || false === $wraper ? $wraper : $wraper;
		$content = $modal['content'];
		$class   = ! empty( $modal['class'] ) ? $modal['class'] : 'modal-lg';
		$footer  = ! empty( $modal['footer'] ) ? $modal['footer'] : false;
	?>
	<div class="modal fade" id="<?php echo esc_attr( $modal['id'] ); ?>" tabindex="-1" aria-hidden="true" aria-labelledby="<?php echo esc_attr( $modal['aria-labelledby'] ); ?>">
		<div class="modal-dialog <?php echo esc_attr( $class ); ?> modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<?php echo wp_kses_post( $modal['title'] ); ?>
					<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e( 'Close' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24" viewBox="0 -960 960 960" width="24" fill="#000">
							<path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"/>
						</svg>
					</button>
				</div>
				<?php if ( true === $wraper ) : ?>
					<div class="modal-body-wrap">
						<div class="modal-body">
							<div class="content-holder">
								<?php echo str_replace( array( '<div>', '</div>' ), '', do_shortcode( $content ) ); ?>
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="modal-body">
						<?php echo do_shortcode( $content ); ?>
					</div>
					<?php
				endif;
				if ( $footer ) :
					?>
					<div class="modal-footer justify-content-center justify-content-md-end">
						<button type="button" class="btn btn-primary btn-lg" data-dismiss="modal"><?php esc_html_e( 'Close' ); ?></button>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
		<?php
endforeach;
