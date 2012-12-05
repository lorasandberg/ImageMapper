/* ImageMapper Wordpress frontend script
*/
var Image;
var Canvas, Ctx;

jQuery(function($) {
	$('img[usemap]').each(function() {
		$(this).mapster({
		fillColor: 'ff0000',
		fillOpacity: 0.0,
		stroke: true,
		strokeColor: 'ff0000',
		strokeOpacity: 0.6,
		strokeWidth: 5,
		listKey: 'data-listkey',
		mapKey: 'data-mapkey',
		listSelectedAttribute: 'checked',
		onClick: AreaClicked,
		singleSelect: true
		});
	});
	
	$('.imgmap-dialog').dialog({ 
		autoOpen: false, 
		zIndex: 10000,
		width: 800,
		height: 600,
		position: {
			of: $(parent)
			}
		});
	$('.mapster_el').load(function() {
		$(this).css('maxWidth', '100%');
	});
});


function AreaClicked(data) {
	var dialog = jQuery(this).parent()[0].name.replace('imgmap', '#imgmap-dialog');
	console.log(dialog);
	jQuery(dialog).dialog('option', 'title', jQuery('area[data-mapKey='+data.key+']').attr('title'));
	jQuery.post(imgmap_ajax.ajaxurl, { 
		action: 'imgmap_load_dialog_post',
		id: data.key.replace('area-', '')
		}, function(response) {
		jQuery(dialog).html(response).dialog('open');
	});
}

