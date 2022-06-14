<section class="block-logo">
	<div class="container">
		<?php if( $logo = get_sub_field( 'logo' ) ) echo wp_get_attachment_image($logo['id'],'full',null,['class' => 'logo-lg']); ?>
	</div>
</section>