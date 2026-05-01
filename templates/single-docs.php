<?php
/**
 * Template for displaying single docs.
 *
 * @package docsraptor
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		docsraptor_output_single_doc_content_layout();
	endwhile;
endif;

get_footer();
