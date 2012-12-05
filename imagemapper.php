<?php
/*
Plugin Name: ImageMapper
Plugin URI: https://github.com/SpaikFi/ImageMapper
Description: Create interactive and visual image maps with a visual editor! Based on the ImageMapster jQuery plugin.
Version: 0.2
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
add_action('template_include', 'imgmap_template');
add_action('wp_ajax_imgmap_save_area', 'imgmap_save_area_ajax');
add_action('wp_ajax_imgmap_delete_area', 'imgmap_delete_area_ajax');
add_action('wp_ajax_nopriv_imgmap_load_dialog_post', 'imgmap_load_dialog_post_ajax');
add_action('wp_ajax_imgmap_load_dialog_post', 'imgmap_load_dialog_post_ajax');
add_action('wp_ajax_imgmap_get_area_coordinates', 'imgmap_get_area_coordinates_ajax');
add_action('before_delete_post', 'imgmap_permanently_delete_imagemap');
add_action('wp_trash_post', 'imgmap_trash_imagemap');
/* add_action('imgmapper_frontend_image', 'imgmap_frontend_image'); */
add_action('admin_head', 'imgmap_load_tiny_mce');

add_filter('the_content', 'imgmap_replace_shortcode');

add_filter('media_upload_tabs', 'imgmap_media_upload_tab');
add_action('media_upload_imagemap', 'imgmap_media_upload_tab_action');

$image_maps = array();

/* Creation of the custom post types 
 * Also script and stylesheet importing
 * Note: The plugin uses jQueryUI library, which includes jQuery UI Stylesheet. If you want to use your own stylesheet made with jQuery UI stylesheet generator, please replace the jquery-ui.css link address with your own stylesheet.
 * jQuery UI is only used in the dialog window which opens when user clicks a highlighted area. 
 * Later there will be option for changing the stylesheet. 
 * */
function imgmap_create_post_type() {
	
	/* Create the imagemap post type */
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
	
	/* Create the imagemap area post type */
	/* Area to highlight. */
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
	
	/* Import ImageMapster and jQuery UI */
	wp_register_script('imgmap_imagemapster', plugins_url() . '/imagemapper/script/jquery.imagemapster.min.js');
	wp_register_style('jquery_ui', 'http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css');
	wp_register_style('imgmap_style', plugins_url().'/imagemapper/imgmap_style.css');
	wp_register_script('jquery_ui', 'http://code.jquery.com/ui/1.9.2/jquery-ui.js');
	
	/* Enqueue jQuery UI, jQuery and ImageMapster + jQueryu UI Stylesheet */
	wp_enqueue_style(array('jquery_ui', 'imgmap_style'));
	wp_enqueue_script(array('jquery', 'jquery_ui', 'editor', 'editor_functions', 'imgmap_imagemapster'));
	
	/* The javascript file server needs to load for plugin's functionality depends on is the page is the admin panel or a frontend page */
	/* (The frontend version obviously doesn't have for example the imagemap editor) */
	if(is_admin()) {
		wp_register_script('imgmap_admin_script', plugins_url() . '/imagemapper/imagemapper_admin_script.js');
		wp_enqueue_script('imgmap_admin_script');
	}
	else {
		wp_register_script('imgmap_script', plugins_url() . '/imagemapper/imagemapper_script.js');
		wp_localize_script('imgmap_script', 'imgmap_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
		wp_enqueue_script('imgmap_script');
	}
};

function imgmap_load_tiny_mce() {
	wp_tiny_mce(false, array('editor_selector' => 'content'));
}

/* To enable author to upload an image for the image map. */
function imgmap_add_post_enctype() {
    echo ' enctype="multipart/form-data"';
}

/* When updating a post, Wordpress needs to check for the custom fields 
 * At the moment it's only the uploaded image.
 * */
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

/* Add custom fields to the custom post type forms. 
 * */
function imgmap_custom_form() {
	add_meta_box('imagemap-image-container', 'Image', 'imgmap_form_image', IMAGEMAP_POST_TYPE, 'normal');
	add_meta_box('imagemap-addarea', 'Add area', 'imgmap_form_addarea', IMAGEMAP_POST_TYPE, 'side');
	add_meta_box('imagemap-areas', 'Areas', 'imgmap_form_areas', IMAGEMAP_POST_TYPE, 'side');
	
	add_meta_box('imagemap-area-settings', 'Settings', 'imgmap_area_form_settings', IMAGEMAP_AREA_POST_TYPE, 'side');
	
}

/* Custom field for the imagemap image.
 * Includes also the imagemap editor.
 *  */
function imgmap_form_image($post) {
	?>
	<input type="file" name="imgmap_image" id="file" />
	<div style="position: relative; margin-top: 30px">
		<img src="<?php echo get_post_meta($post->ID, 'imgmap_image', true); ?>" usemap="#imgmap-<?php echo $post->ID ?>" id="imagemap-image" />
		<canvas id="image-area-canvas"></canvas>
		<canvas id="image-coord-canvas"></canvas>
	</div>
	<?php
		
		$areas = get_posts('post_parent='.$post->ID.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&numberposts=-1');
		
	?>
	<map name="imgmap-<?php echo $post->ID ?>">
		<?php
			foreach($areas as $a) {
				echo imgmap_create_area_element($a->ID, $a->post_title);
			}
		?>
	</map>
	<?php
}

function imgmap_media_upload_tab($tabs) {
	$newtab = array('imagemap' => __('Image map', 'imagemap'));
	return array_merge($tabs, $newtab);
}

function imgmap_media_upload_tab_action() {
	return wp_iframe('media_imgmap_media_upload_tab_inside');
}

function media_imgmap_media_upload_tab_inside() {
	media_upload_header(); ?>
	<p>
		<?php
		$areas = get_posts('post_type='.IMAGEMAP_POST_TYPE.'&numberposts=-1');
		foreach($areas as $a) { ?>
			<div data-imagemap="<?php echo $a->ID; ?>" class="insert-media-imagemap" style="background-image: url(<?php echo get_post_meta($a->ID, 'imgmap_image', true); ?>);">
				<div><?php echo $a->post_title ?></div>
			</div>
		<?php }
		?>
	</p>
	<?php
}

/* Displays the image map in a frontend page. */
function imgmap_frontend_image($id, $element_id) {
	echo get_imgmap_frontend_image($id, $element_id);
	}
	
function get_imgmap_frontend_image($id, $element_id) {
	$value = '
	<div class="imgmap-dialog" id="imgmap-dialog-'.$element_id.'">HLeello swwoolrd</div>
	<img src="'.get_post_meta($id, 'imgmap_image', true).'" usemap="#imgmap-'.$element_id.'" id="imagemap-'.$element_id.'" />
	<map name="imgmap-'.$element_id.'">';
	$areas = get_posts('post_parent='.$id.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&numberposts=-1');
	foreach($areas as $a) {
		$value .= imgmap_create_area_element($a->ID, $a->post_title);
	}
	$value .= '</map>';
	return $value;
}


/* Fields for adding new areas to the imagemap using the editor.
 * However the editor functionality is included in the image field. */
function imgmap_form_addarea($post) {
	?>
	<input type="button" value="Add area" id="add-area-button"/>
	<?php
}

/* List of the current areas of the imagemap. 
 * Every element in the list has link to edit form of the area and a shortcut for deleting the areas. */
function imgmap_form_areas($post) {
	$areas = get_posts('post_parent='.$post->ID.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&orderby=id&order=desc&numberposts=-1');
	echo '<ul>';
	foreach($areas as $a) {
		echo imgmap_create_list_element($a->ID);
	}
	echo '</ul>';
}

/* Settings for the single imagemap area */
function imgmap_area_form_settings($post) {
	?>
	 
	
	<?php
}

/* Used when user adds a new area to the image map 
 * The function returns object with data of the newly-added area and link to edit it. 
 * Currently Wordpress should be redirecting user to the area edit form after the area has been saved. 
 * However there's a bug with the redirecting and it's redirecting in wrong page. Might be that Wordpress doesn't allow the redirect. */
function imgmap_save_area_ajax() {
	global $wpdb;
	
	$area = new StdClass();
	$area->coords = $_POST['coords'];
	$area->text = '';
	$area->title = 'New image map area'; 
	$area->parent = $_POST['parent_post'];
	$post = array(
	'post_author'    => get_current_user_id(),
	'post_content'   => $area->text,
	'post_parent'    => $area->parent,
	'post_status'    => 'publish',
	'post_name' 	 => $area->title,
	'post_title'     => $area->title,
	'post_type'      => IMAGEMAP_AREA_POST_TYPE
	);
	$post = wp_insert_post($post);
	
	$area->id = $post;
	$area->link = get_edit_post_link($area->id);
	update_post_meta($area->id, 'coords', $area->coords);
	$area->html = imgmap_create_list_element($area->id);
	ob_clean();
	echo json_encode($area);
	die();
}

/* Shortlink for deleting an area. (Well, the functionality which happens when the shortlink is pressed. */
function imgmap_delete_area_ajax() {
	echo json_encode(wp_delete_post($_POST['post'], true));
	die();
}

/* Creates an area element to the HTML image map */
function imgmap_create_area_element($id, $title) {	
	return '
	<area
	data-mapkey="area-'.$id.'" 
	shape="poly" coords="'.get_post_meta($id, 'coords', true).'" 
	href="#"
	title="'.$title.'" />';
}

/* Creates an list element to the list of imagemap's areas. */
function imgmap_create_list_element($id) {
	return 
	'<li data-listkey="area-'.$id.'" class="area-list-element">
	<input id="area-checkbox-'.$id.'" data-listkey="area-'.$id.'" type="checkbox" checked> '.
	'<a href="'.get_edit_post_link($id).'">#'.($id) . ' area</a>'.
	'<span class="delete-area" data-area="'.$id.'">Delete</a>'.
	'</li>';
}

/* Template for the imagemap frontend page. 
 * Checks first the theme folder. 
 * Note: If you want to edit the image map template, please check the single_imgmap.php template file in plugin's directory. */
function imgmap_template($template) {
	$post = get_the_ID();
	if(get_post_type() == IMAGEMAP_POST_TYPE) {
		if(locate_template(array('single-imgmap.php')) != '') 
			include locate_template(array('single-imgmap.php'));
		else
			include 'single-imgmap.php';
		return;
	}
	return $template;
}

/* Loads post in a jQuery dialog when a highlighted area is clicked. 
 * Checks first the theme folder, too */
function imgmap_load_dialog_post_ajax() {
	$post = get_post($_POST['id']);
	if(locate_template(array('single-imgmap-dialog.php')) != '') 
		include locate_template(array('single-imgmap-dialog.php'));
	else
		include 'single-imgmap-dialog.php';
	die();
}

/* Returns array of area data of an imagemap. */
function imgmap_get_area_coordinates_ajax() {
	$return = array();
	$areas = get_posts('post_parent='.$_POST['post'].'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&orderby=id&order=desc&numberposts=-1');
	foreach($areas as $a) {
		$newArea = new StdClass();
		$newArea->coords = get_post_meta($a->ID, 'coords', true);
		$newArea->id = $a->ID;
		$return[] = $newArea;
	}
	echo json_encode($return);
	die();
}

/* Be sure to delete areas when deleting parent post */
function imgmap_permanently_delete_imagemap($post_id) {
	imgmap_delete_imagemap($post_id, true);
}

/* ...and be sure to trash areas when trashing parent post as well. */
function imgmap_trash_imagemap($post_id) {
	imgmap_delete_imagemap($post_id, false);
}

/* Delete areas when deleting imagemap. 
 * Doesn't actually restore trashed imagemap areas when restoring the imagemap. */
function imgmap_delete_imagemap($post_id, $permanent) {
	
	$args = array( 
    'post_parent' => $post_id,
    'post_type' => IMAGEMAP_POST_TYPE
	);
	
	$posts = get_posts( $args );
	
	if (is_array($posts) && count($posts) > 0) {
		// Delete all the Children of the Parent Page
		foreach($posts as $post){
			wp_delete_post($post->ID, $permanent);
		}
	}
}

/* Insert image map code in posts */
function imgmap_replace_shortcode($content) {
	preg_match_all('/\[imagemap id=\"(.*?)\"\]/', $content, $maps);
	foreach($maps[1] as $map) {
		if(!isset($imagemaps[$map]))
			$imagemaps[$map] = 0;
		$imagemaps[$map]++;
			
		$content = preg_replace('/\[imagemap id=\"'.$map.'\"\]/', get_imgmap_frontend_image($map, $map.'-'.$imagemaps[$map]), $content, 1);
	}
	return $content;
}

?>