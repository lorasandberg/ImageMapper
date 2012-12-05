<?php
/* Template file for displaying the imagemap in a frontend page. 
 * Basically imgmap_frontend_image($post_id) is the only necessary function for the image map functionality. */
?>

<?php get_header(); ?>
	<div id="content" role="main">
		<?php do_action('imgmapper_frontend_image', get_the_ID()); ?>
	</div><!-- #content -->
<?php get_footer(); ?>