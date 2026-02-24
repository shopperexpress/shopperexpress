<?php
$post_id = !empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
get_template_part( 'template-parts/content', 'listings', [ 'post_type' => '-used-listings', 'post_id' => $post_id ] );
?>