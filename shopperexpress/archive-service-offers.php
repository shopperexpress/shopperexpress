<?php
/**
 * Template part for displaying archive service offers
 *
 * @package Shopperexpress
 */

get_template_part( 'archive', 'listings', array( 'post_type' => get_queried_object()->name ) );
