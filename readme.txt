=== ImageMapper ===
Contributors: spikefinned
Tags: image map, images, interactive images
Requires at least: 3.1
Tested up to: 3.4.2
Stable tag: 0.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create interactive and visual image maps with a visual editor. Based on the ImageMapster jQuery plugin.

== Description ==

Create interactive and visual image maps with a visual editor! Based on the ImageMapster jQuery plugin.

As the plugin is still missing most of the labels and instructions to use, I'll include here a short description of how to use it.
- Create new Image map, select an image to upload and update post
- The image should be displayed in the post form after refreshing.
- To add new areas to the image, start creating the path simply by clicking the image. When the path looks good press Add area.
- The new area will be added in the Areas list and it will shown in the image as well.
- To edit the content of the new area, click the area in the Areas list. It will redirect you to the editing form of the image map area.
- Create a new post and open Insert/Upload media window. Select the image map tab and click the imagemap to insert it into the post.

Features to be implemented in near future:
- Possibility to choose if the image map area opens a pop up window with post content, displays a small tooltip when hovering or just acts like a regular link.
- Choose the color and opacity of highlight of single area.
- Redraw image map areas.

== Installation ==

1. Upload imagemapper folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Optional: Create single_imgmap.php template file in your theme directory and place `<?php do_action('imgmapper_frontend_image', $post_id); ?>` in it.

== Changelog ==

= 0.1 =
* First release.

= 0.2 =
* Support for adding image maps in posts.
* Support for multiple image maps.

= 0.3 =
* Fixed a bug which prevented inserting image map to the post with Insert media window in WordPress 3.5
* Images of image maps in archive pages.
* Click events: Possibility to choose if an area acts as a regular link, shows a tooltip when hovering or opens up a post content in a dialog.
* Prevent adding an empty area or area with only two points.