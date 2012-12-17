<?php
/*
Plugin Name: ImageMapper
Plugin URI: https://github.com/SpaikFi/ImageMapper
Description: Create interactive and visual image maps with a visual editor! Based on the ImageMapster jQuery plugin.
Version: 0.3
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
add_action('admin_head', 'imgmap_load_tiny_mce');
add_action('manage_'.IMAGEMAP_POST_TYPE.'_posts_custom_column', 'imgmap_manage_imagemap_columns', 10, 2);
add_action('manage_'.IMAGEMAP_AREA_POST_TYPE.'_posts_custom_column', 'imgmap_manage_imagemap_area_columns', 10, 2);

add_filter('the_content', 'imgmap_replace_shortcode');
add_filter('post_updated_messages', 'imgmap_updated_message');
add_filter('manage_edit-'.IMAGEMAP_POST_TYPE.'_columns', 'imgmap_set_imagemap_columns');
add_filter('manage_edit-'.IMAGEMAP_AREA_POST_TYPE.'_columns', 'imgmap_set_imagemap_area_columns');
add_filter( 'manage_edit-'.IMAGEMAP_AREA_POST_TYPE.'_sortable_columns', 'imgmap_register_sortable_area_columns' );

add_filter('media_upload_tabs', 'imgmap_media_upload_tab');
add_action('media_upload_imagemap', 'imgmap_media_upload_tab_action');

$image_maps = array();


// Test data for highlight style management
$imgmap_colors = array(
'color1' => array('render_highlight' => array( 'fillColor' => 'c94a4a', 'strokeColor' => 'e82828', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color2' => array('render_highlight' => array( 'fillColor' => '1e39db', 'strokeColor' => '1e39db', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color3' => array('render_highlight' => array( 'fillColor' => '1ed4db', 'strokeColor' => '1ed4db', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color4' => array('render_highlight' => array( 'fillColor' => '4355c3', 'strokeColor' => '1edb4b', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color5' => array('render_highlight' => array( 'fillColor' => '3ddb1e', 'strokeColor' => '3ddb1e', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color6' => array('render_highlight' => array( 'fillColor' => 'dbc71e', 'strokeColor' => 'dbc71e', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color7' => array('render_highlight' => array( 'fillColor' => 'db4f1e', 'strokeColor' => 'db4f1e', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color8' => array('render_highlight' => array( 'fillColor' => 'd91edb', 'strokeColor' => 'd91edb', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color9' => array('render_highlight' => array( 'fillColor' => '1e34db', 'strokeColor' => '1e34db', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color10' => array('render_highlight' => array( 'fillColor' => 'db1e65', 'strokeColor' => 'db1e65', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color11' => array('render_highlight' => array( 'fillColor' => 'fefefe', 'strokeColor' => 'fefefe', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
'color12' => array('render_highlight' => array( 'fillColor' => '070707', 'strokeColor' => '070707', 'fillOpacity' => 0.3, 'strokeOpacity' => 0.8, 'strokeWidth' => 2)),
);

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
		wp_enqueue_style( 'farbtastic' );
		wp_register_script('imgmap_admin_script', plugins_url() . '/imagemapper/imagemapper_admin_script.js');
		wp_enqueue_script(array('imgmap_admin_script', 'farbtastic'));
	}
	else {
		wp_register_script('imgmap_script', plugins_url() . '/imagemapper/imagemapper_script.js');
		wp_enqueue_script('imgmap_script');
	}
	
	
	wp_localize_script('imgmap_script', 'imgmap', array(
		'ajaxurl' => admin_url('admin-ajax.php')));
};

// Set custom columns for imagemap archive page
function imgmap_set_imagemap_columns($columns) {
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['image'] = __('Image');
	$new_columns['title'] = _x('Imagemap name', 'column name');
	$new_columns['area_count'] = __('Areas');
	$new_columns['date'] = __('Updated');
	$new_columns['author'] = __('Author');
	return $new_columns;
}

// ..and do the same for areas
function imgmap_set_imagemap_area_columns($columns) {
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['title'] = _x('Imagemap area name', 'column name');
	$new_columns['parent_image'] = __('Imagemap image');
	$new_columns['parent_title'] = __('Imagemap title');
	$new_columns['date'] = __('Updated');
	$new_columns['author'] = __('Author');
	return $new_columns;
}

//Define what to do for custom columns
function imgmap_manage_imagemap_columns($column_name, $id) {
	global $wpdb;
	switch($column_name) {
		case 'image':
			echo '<img class="imagemap-column-image" src="'.get_post_meta($id, 'imgmap_image', true).'" alt>';
			break;
			
		case 'area_count': 
			$areas = get_posts('post_parent='.$id.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&numberposts=-1');
			echo count($areas);
			break;
	}
}
// for the areas too
function imgmap_manage_imagemap_area_columns($column_name, $id) {
	global $wpdb;
	switch($column_name) {
		case 'parent_image':
			$post = get_post($id);
			echo '<img class="imagemap-column-image" src="'.get_post_meta($post->post_parent, 'imgmap_image', true).'" alt>';
			break;
		
		case 'parent_title':
			$post = get_post($id);
			echo '<a href="'.get_edit_post_link($post->post_parent).'">'.get_the_title($post->post_parent).'</a>';
			break;
	}
}

//Make the parent title column sortable, so there's a way to sort areas by parent image map.
function imgmap_register_sortable_area_columns( $columns ) {
	$columns['parent_title'] = 'parent_title';
	return $columns;
}

//Necessary?
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
	
	if(get_post_type($id) == IMAGEMAP_POST_TYPE) {
		$uploadedFile = $_FILES['imgmap_image'];
		if($uploadedFile['error'] == 0){
			
			$file = wp_handle_upload($uploadedFile, array('test_form' => FALSE));
			
			if(!strpos('image/', $file['type']) == 0)
			wp_die('This is not an image!');
			
			update_post_meta($id, 'imgmap_image', $file['url']);
		}
	}
	if(get_post_type($id) == IMAGEMAP_AREA_POST_TYPE) {
		$area_vars = new StdClass();
		$area_vars->type = $_POST['area-type'];
		$area_vars->tooltip_text = $_POST['area-tooltip-text'];
		$area_vars->link_url = $_POST['area-link-url'];
		$area_vars->link_target = $_POST['area-link-target'];
		$area_vars->highlight_color = $_POST['area-highlight-color'];
		$area_vars->highlight_opacity = $_POST['area-highlight-opacity'];
		$area_vars->border_color = $_POST['area-border-color'];
		$area_vars->border_opacity = $_POST['area-border-opacity'];
		$area_vars->border_width = $_POST['area-border-width'];
		
		// Save area settings in JSON format.
		// Basically when you need one of them, you need all others as well, so it's inefficient to save them in separate columns.
		update_post_meta($id, 'imgmap_area_vars', json_encode($area_vars));
	}
}

function imgmap_updated_message( $messages ) {
	global $post_ID;
	if(get_post_type($post_ID) != IMAGEMAP_POST_TYPE) 
		return;
		
	$messages[IMAGEMAP_POST_TYPE] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Image map updated. You can add the image map to a post with Upload/Insert media tool.') ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Image map updated.'),
    5 => isset($_GET['revision']) ? sprintf( __('Image map restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Image map published.')),
    7 => __('Image map saved.'),
    8 => sprintf( __('Image map submitted.')),
    9 => sprintf( __('Image map scheduled for: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
    10 => sprintf( __('Image map draft updated.')),
	);
	
	return $messages;
}

/* Add custom fields to the custom post type forms. 
 * */
function imgmap_custom_form() {
	global $_wp_post_type_features;
	
	add_meta_box('imagemap-image-container', 'Image', 'imgmap_form_image', IMAGEMAP_POST_TYPE, 'normal');
	add_meta_box('imagemap-addarea', 'Add area', 'imgmap_form_addarea', IMAGEMAP_POST_TYPE, 'side');
	add_meta_box('imagemap-areas', 'Areas', 'imgmap_form_areas', IMAGEMAP_POST_TYPE, 'side');
	
	remove_post_type_support(IMAGEMAP_AREA_POST_TYPE, 'editor');
		
	//add_meta_box('imagemap-area-settings', 'Highlight', 'imgmap_area_form_settings', IMAGEMAP_AREA_POST_TYPE, 'side');
	add_meta_box('imagemap-area-types', 'Click event', 'imgmap_area_form_types', IMAGEMAP_AREA_POST_TYPE, 'normal');
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
	$areas = array();
	$value = '
	<div class="imgmap-frontend-image">
	<div class="imgmap-dialog" id="imgmap-dialog-'.$element_id.'">HLeello swwoolrd</div>
	<img src="'.get_post_meta($id, 'imgmap_image', true).'" usemap="#imgmap-'.$element_id.'" id="imagemap-'.$element_id.'" />
	<map name="imgmap-'.$element_id.'">';
	$areas = get_posts('post_parent='.$id.'&post_type='.IMAGEMAP_AREA_POST_TYPE.'&numberposts=-1');
	foreach($areas as $a) {
		$value .= imgmap_create_area_element($a->ID, $a->post_title);
	}
	$value .= '</map>
	</div>';
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
	global $imgmap_colors;
	$meta = json_decode(get_post_meta($post->ID, 'imgmap_area_vars', true));
	$meta->color = 'color1'; ?> 
	<div id="imgmap-area-styles"><?php 
	foreach($imgmap_colors as $key => $color) { ?>
		<div class="imgmap-area-style <?php echo ($key === $meta->color ? 'chosen' : ''); ?>">
			<div class="imgmap-area-color" style="
			background-color: #<?php echo $color['render_highlight']['fillColor']; ?>;
			opacity: <?php echo $color['render_highlight']['fillOpacity'];?>;
			box-shadow: 0 0 0 <?php echo $color['render_highlight']['strokeWidth']; ?>px <?php echo imgmap_hex_to_rgba($color['render_highlight']['strokeColor'], $color['render_highlight']['strokeOpacity']); ?>"></div>
	</div><?php }
	?><br style="clear:both;"></div>
	
	
	<p><label>Highlight color<br /> #<input maxlength="6" type="text" name="area-highlight-color" id="highlight-color" value="<?php echo $meta->highlight_color ?>"></label></p>
	<p><label>Higlight opacity<br /> <input type="number" max="1" min="0" step="0.1" name="area-highlight-opacity" value="<?php echo $meta->highlight_opacity ?>"></label></p>
	<p><label>Stroke color<br /> #<input maxlength="6" type="text" name="area-border-color" id="highlight-border-color" value="<?php echo $meta->border_color ?>"></label></p>
	<p><label>Stroke opacity<br /> <input type="number" max="1" min="0" step="0.1" name="area-border-opacity" value="<?php echo $meta->border_opacity ?>"></label></p>
	<p><label>Stroke width<br /> <input type="number" min="0" step="1" name="area-border-width" value="<?php echo $meta->border_width ?>"></label></p>
<?php
}

function imgmap_area_form_types($post) { 
	// Get area variables from post meta 
	$meta = json_decode(get_post_meta($post->ID, 'imgmap_area_vars', true));
	?>
	<div style="width: 20%; float: left;" id="area-form-types">
		<p><input type="radio" name="area-type" onclick="ShowTypes('link')" value="link" <?php echo $meta->type == 'link' ? 'checked' : '' ?>> 
			<input type="button" class="button" onclick="ShowTypes('link')" value="Link" /></p>
		<p><input type="radio" name="area-type" onclick="ShowTypes('tooltip')" value="tooltip" <?php echo $meta->type == 'tooltip' ? 'checked' : '' ?>> 
			<input type="button" class="button" onclick="ShowTypes('tooltip')" value="Tooltip" /></p>
		<p><input type="radio" name="area-type" onclick="ShowTypes('popup')" value="popup" <?php echo $meta->type == 'popup' ? 'checked' : '' ?>> 
			<input type="button" class="button" onclick="ShowTypes('popup')" value="Popup window" /></p>
	</div>
	<div style="width: 75%; float: right;">
		<div id="imagemap-area-popup-editor" class="area-type-editors <?php echo $meta->type == 'popup' ? 'area-type-editor-current' : '' ?>">
		<?php wp_editor($post->post_content, 'content'); ?></div>
		<div id="imagemap-area-tooltip-editor" class="area-type-editors <?php echo $meta->type == 'tooltip' ? 'area-type-editor-current' : '' ?>">
			<p><label>Tooltip text <br />
				<textarea cols="60" rows="8" name="area-tooltip-text"><?php echo $meta->tooltip_text ?></textarea>
			</label></p>
		</div>
		<div id="imagemap-area-link-editor" class="area-type-editors <?php echo $meta->type == 'link' ? 'area-type-editor-current' : '' ?>">
			<p><label>Url address: <br /><input type="text" name="area-link-url" value="<?php echo $meta->link_url; ?>"></label></p>
		</div>
	</div>
	<br style="clear:both">
<?php }

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
	$meta = json_decode(get_post_meta($id, 'imgmap_area_vars', true));
	return '
	<area
	data-type="'.$meta->type.'"
	data-tooltip="'.($meta->type == 'tooltip' ? $meta->tooltip_text : false ). '"
	data-fill-color="'.$meta->highlight_color.'"
	data-fill-opacity="'.$meta->highlight_opacity.'"
	data-stroke-color="'.$meta->border_color.'"
	data-stroke-opacity="'.$meta->border_opacity.'"
	data-stroke-width="'.$meta->border_width.'"
	data-mapkey="area-'.$id.'" 
	shape="poly" coords="'.get_post_meta($id, 'coords', true).'" 
	href="'. ($meta->type == 'link' ? $meta->link_url : '#') .'"
	title="'.$title.'" />';
}

/* Creates an list element to the list of imagemap's areas. */
function imgmap_create_list_element($id) {
	return 
	'<li data-listkey="area-'.$id.'" class="area-list-element">
	<div class="area-list-left">
		<input id="area-checkbox-'.$id.'" data-listkey="area-'.$id.'" type="checkbox" checked>
	</div>
	<div class="area-list-right">
		<label>Title: <input type="text" id="'.$id.'-list-area-title" value="'.get_the_title($id).'" /><div style="clear: both"></div></label>
		<div class="area-list-meta">
			<a class="save-area-link" href="#">Save</a>
			<a class="edit-area-link" href="'.get_edit_post_link($id).'">Edit page</a>
			<a class="delete-area" data-area="'.$id.'">Delete</a>
		</div>
	</div>
	</li>';
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
	global $imagemaps;
	preg_match_all('/\[imagemap id=\"(.*?)\"\]/', $content, $maps);
	foreach($maps[1] as $map) {
		if(!isset($imagemaps[$map]))
			$imagemaps[$map] = 0;
		$imagemaps[$map]++;
			
		$content = preg_replace('/\[imagemap id=\"'.$map.'\"\]/', get_imgmap_frontend_image($map, $map.'-'.$imagemaps[$map]), $content, 1);
	}
	return $content;
}

function imgmap_hex_to_rgba($hex, $opacity = false) {
	
	if(substr($hex, 0, 1) == '#')
		$hex = substr($hex, 1);
		
	$red = substr($hex, 0, 2);
	$green = substr($hex, 2, 2);
	$blue = substr($hex, 4, 2);
	
	$red = hexdec($red);
	$green = hexdec($green);
	$blue = hexdec($blue);
	
	if(is_numeric($opacity))
		return 'rgba('.$red.', '.$green.', '.$blue.', '.$opacity.')';
	else
		return 'rgb('.$red.', '.$green.', '.$blue.')';
}

?>