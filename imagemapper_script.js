/* ImageMapper Wordpress frontend script
*/
var Image;
var Canvas, Ctx;

jQuery(function($) {
	$('img[usemap]').mapster({
		fillColor: 'ffffff',
		fillOpacity: 0.4,
		stroke: true,
		strokeColor: 'ffffff',
		strokeOpacity: 0.6,
		strokeWidth: 2,
		listKey: 'data-listkey',
		mapKey: 'data-mapkey',
		listSelectedAttribute: 'checked',
		boundList: jQuery('.area-list-element > input[type=checkbox], .area-list-element'),
		onClick: AreaClicked,
		singleSelect: true
	});
	
	$('#imgmap-dialog').dialog({ 
		autoOpen: false, 
		zIndex: 10000,
		width: 800,
		height: 600,
		position: {
			of: $('#image')
		}
		});
});

function AreaClicked(data) {
	jQuery('#imgmap-dialog').dialog('option', 'title', jQuery('area[data-mapKey='+data.key+']').attr('title'));
	jQuery.post(imgmap_ajax.ajaxurl, { 
		action: 'imgmap_load_dialog_post',
		id: data.key.replace('area-', '')
		}, function(response) {
		jQuery('#imgmap-dialog').html(response);
		jQuery('#imgmap-dialog').dialog('open');
	});
}

