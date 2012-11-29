<div id="dialog-meta">
	<?php
	if(current_user_can('edit_post', $post->ID)) {
		?><a class="post-edit-link" href="<?php echo get_edit_post_link($post->ID); ?>" title="Edit">Edit</a><?php
	} ?>
</div>
<div id="dialog-content">
	<?php 
		$content = apply_filters('the_content', $post->post_content);
		echo str_replace(']]>', ']]&gt;', $content);
	?>
</div>