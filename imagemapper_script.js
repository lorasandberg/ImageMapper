/* ImageMapper Wordpress frontend script
*/
var Image;
var Canvas, Ctx;

jQuery(function($) {
	$('img[usemap]').each(function() {
		var areas = [];
		$('map[name="' + $(this).attr('usemap').substr(1) + '"]').find('area').each(function() {
			areas.push({
				'key': $(this).attr('data-mapkey'),
				'toolTip': $(this).attr('data-tooltip'),
				'isSelectable': false,
				'render_highlight': {
					'fillColor': $(this).attr('data-fill-color'),
					'fillOpacity': $(this).attr('data-fill-opacity'),
					'strokeColor': $(this).attr('data-stroke-color'),
					'strokeOpacity': $(this).attr('data-stroke-opacity'),
					'stroke': $(this).attr('data-stroke-width') > 0,
					'strokeWidth': $(this).attr('data-stroke-width')
				}
			});
		});
		console.log(areas.length);
		$(this).mapster({
			clickNavigate: true,
			showToolTip: true,
			toolTipContainer: $('<div class="imagemapper-tooltip"></div>'),
			toolTipClose: ['area-click', 'tooltip-click'],
			mapKey: 'data-mapkey',
			onClick: AreaClicked,
			singleSelect: true,
			render_select: {
				fillOpacity: 0
			},
			areas: areas
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
	});
});


function AreaClicked(data) {
	var type = jQuery('area[data-mapKey='+data.key+']').attr('data-type'); 
	console.log(type);
	if(type == 'popup' || type == '' ) {
		var dialog = jQuery(this).parent()[0].name.replace('imgmap', '#imgmap-dialog');
		jQuery(dialog).dialog('option', 'title', jQuery('area[data-mapkey='+data.key+']').attr('title'));
		jQuery.post(imgmap.ajaxurl, { 
			action: 'imgmap_load_dialog_post',
			id: data.key.replace('area-', '')
			}, function(response) {
			jQuery(dialog).html(response).dialog('open');
		});
	}
}

