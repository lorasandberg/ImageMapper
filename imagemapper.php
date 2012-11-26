<?php
/*
Plugin Name: ImageMapper
Plugin URI: -
Description: Add interactivity in your images or comics! This plugin can be used for making posts including images with image maps. Multiple areas from image can be selected and highlighted. You can also add links or popups to open when clicking an area. Includes also an editor for image maps.
Version: 0.1
Author: A.Sandberg AKA Spike
Author URI: http://spike.viuhka.fi
License: GPL2
*/
define('IMAGEMAP_POST_TYPE', 'imagemap');
define('IMAGEMAP_AREA_POST_TYPE', 'imagemap_area');
add_action('init', 'imgmap_create_post_type');
add_action('admin_menu', 'imgmap_custom_form');
add_action('save_post', 'imgmap_save_meta');
add_action('post_edit_form_tag', 'imgmap_add_post_enctype');
add_action('wp_ajax_imgmap_save_area', 'imgmap_save_area_ajax');
add_action('wp_ajax_imgmap_delete_area', 'imgmap_delete_area_ajax');
add_action('template_include', 'imgmap_template');

function imgmap_create_post_type() {
	register_post_type(IMAGEMAP_POST_TYPE,
		array( 
			'labels' => array(
					'name' => __('Image maps'),
					'singular_name' => __('Image map')
					),
					'public' => true,
					'has_archive' => true,
					'supports' => array(
						'title'
					)
			)
	);
	
	register_post_type(IMAGEMAP_AREA_POST_TYPE,
		array( 
			'labels' => array(
				'name' => __('Image map areas'),
				'singular_name' => __('Image map area')
			),
			'public' => true,
			'has_archive' => true
		)
	);
	wp_register_script('imgmap_imagemapster', plugins_url() . '/imagemapper/script/jquery.imagemapster.min.js');
	wp_enqueue_script('jquery');
	wp_enqueue_script('imgmap_imagemapster');
	
	if(is_admin()) {
		wp_register_script('imgmap_admin_script', plugins_url() . '/imagemapper/imagemapper_admin_script.js');
		wp_enqueue_script('imgmap_admin_script');
	}
	else {
		wp_register_script('imgmap_script', plugins_url() . '/imagemapper/imagemapper_script.js');
		wp_enqueue_script('imgmap_script');
	}
};

function imgmap_add_post_enctype() {
    echo ' enctype="multipart/form-data"';
}

function imgmap_save_meta($id = false) {
	if(get_post_type($id) != IMAGEMAP_POST_TYPE) 
		return;
	
	$uploadedFile = $_FILES['imgmap_image'];
	if($uploadedFile['error'] == 0){
	
		$file = wp_handle_upload($uploadedFile, array('test_form' => FALSE));
		
		if(!strpos('image/', $file['type']) == 0)
			wp_die('This is not an image!');
			
		update_post_meta($id, 'imgmap_image', $file['url']);
	}
}

function imgmap_custom_form() {
	add_meta_box('imagemap-image', 'Image', 'imgmap_form_image', IMAGEMAP_POST_TYPE, 'normal');
	add_meta_box('imagemap-addarea', 'Add area', 'imgmap_form_addarea', IMAGEMAP_POST_TYPE, 'side');
	add_meta_box('imagemap-areas', 'Areas', 'imgmap_form_areas', IMAGEMAP_POST_TYPE, 'side');
}

function imgmap_form_image($post) {
	?>
	<input type="file" name="imgmap_image" id="file" />
	<br />
	<img src="<?php echo get_post_meta($post->ID, 'imgmap_image', true); ?>" usemap="#imgmap" id="image" style="max-width: 100%"  />
	<canvas id="image-coord-canvas"></canvas>
	<div style="clear:both"></div>
	<?php
		
		$areas = get_posts('post_parent='.$post->ID.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&numberposts=-1');
		
	?>
	<map name="imgmap">
		<?php
			foreach($areas as $a) {
				echo imgmap_create_area_element($a->ID, $a->post_content);
			}
		?>
	</map>
	<?php
}

function imgmap_frontend_image($id) {
	?>
	<img src="<?php echo get_post_meta($id, 'imgmap_image', true); ?>" usemap="#imgmap" id="image" style="max-width: 100%"  />
	<div style="clear:both"></div>
	<map name="imgmap">
		<?php
			$areas = get_posts('post_parent='.$id.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&numberposts=-1');
			foreach($areas as $a) {
				echo imgmap_create_area_element($a->ID, $a->post_content);
			}
		?>
	</map>
	<?php
}

function imgmap_form_addarea($post) {
	?>
	<textarea id="coords" style="width: 100%" rows="10"></textarea>
	<input type="button" value="Add area" id="add-area-button"/>
	<?php
}

function imgmap_form_areas($post) {
	$areas = get_posts('post_parent='.$post->ID.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&orderby=id&order=desc&numberposts=-1');
	echo '<ul>';
	foreach($areas as $a) {
		echo imgmap_create_list_element($a->ID);
	}
	echo '</ul>';
}

function imgmap_save_area_ajax() {
	global $wpdb;
	
	$area = new StdClass();
	$area->coords = $_POST['coords'];
	$area->text = 'hello world';
	$area->parent = $_POST['parent_post'];
	$post = array(
	'post_author'    => get_current_user_id(),
	'post_content'   => $area->text,
	'post_parent'    => $area->parent,
	'post_status'    => 'publish',
	'post_name' 	 => $area->Title,
	'post_title'     => $area->Title,
	'post_type'      => IMAGEMAP_AREA_POST_TYPE
	);
	$post = wp_insert_post($post);
	
	$area->id = $post;
	
	update_post_meta($area->id, 'coords', $area->coords);
	$area->html = imgmap_create_list_element($area->id);
	echo json_encode($area);
	die();
}

function imgmap_delete_area_ajax() {
	echo json_encode(wp_delete_post($_POST['post'], true));
	die();
}

function imgmap_create_area_element($id, $title) {	
	return '
	<area
	data-mapkey="area-'.$id.'" 
	shape="poly" coords="'.get_post_meta($id, 'coords', true).'" 
	href="http://www.google.com" title="'.$title.'" />';
}

function imgmap_create_list_element($id) {
	return 
	'<li data-listkey="area-'.$id.'" class="area-list-element"><input data-listkey="area-'.$id.'" type="checkbox"> '.
	'<a href="'.get_edit_post_link($id).'">#'.($id) . ' area</a>'.
	'<span style="float: right; cursor: pointer" class="delete-area" data-area="'.$id.'">Delete</a>'.
	'</li>';
}

function imgmap_template($post) {
	
	if(get_post_type() == IMAGEMAP_POST_TYPE) {
		include 'single_imgmap.php';
		return;
	}
	return $post;
}
?>