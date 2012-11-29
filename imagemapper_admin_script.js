/* ImageMapper Wordpress admin panel script
*/
var Image;
var Canvas, Ctx;
var SavedAreasCanvas, SACtx;
var Coords = [];

jQuery(function() {
	
	if(jQuery('#image').length > 0) {
		jQuery('#image').load(function() { jQuery(this).show(200); });
		jQuery('#image-coord-canvas').click(imgClick);
		jQuery('img[usemap]').mapster({
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
			onClick: AreaClicked
		});
		jQuery('#add-area-button').click(AddArea);
		
		var img = new Image();
		img.onload = function() {
			Image = { width: this.width, height: this.height };
			Canvas.width = Image.width;
			Canvas.height = Image.height;
			SavedAreasCanvas.width = Image.width;
			SavedAreasCanvas.height = Image.height;
			
			
			jQuery(Canvas).width(jQuery('#image').width());
			jQuery(Canvas).height(jQuery('#image').height());
			jQuery(SavedAreasCanvas).width(jQuery('#image').width());
			jQuery(SavedAreasCanvas).height(jQuery('#image').height());
		};
		img.src = jQuery('#image').attr('src');
		
		Canvas = document.getElementById('image-coord-canvas');
		SavedAreasCanvas = document.getElementById('image-area-canvas');
		if(Canvas) {
			Ctx = Canvas.getContext('2d');
			SACtx = SavedAreasCanvas.getContext('2d');
			DrawSavedAreas(SavedAreasCanvas, SACtx);
			jQuery('.area-list-element').change(function() { DrawSavedAreas(SavedAreasCanvas, SACtx); });
		}
		jQuery('.delete-area').click(DeleteArea);
		
	}
});

function AreaClicked(data) {
	console.log(data);
}

function FileAPIAvailable() {
	return window.File && window.FileList && window.FileReader && window.Blob;
}

function imgClick(evt) {
	var offset = jQuery(this).offset();
	AddCoords(evt.pageX - offset.left, evt.pageY - offset.top);	
}
var coordinate_index = 0;
function AddCoords(x, y) {
	
	if(!Image.width) {
		alert("Source image haven't been downloaded yet! Please try again in few seconds.");
	}
	
	Coords.push({
	x: Math.floor(x * (Image.width/jQuery('#image').width())),
	y: Math.floor(y * (Image.height/jQuery('#image').height())),
	id: ++coordinate_index
	});
	
	jQuery('#coords').text(Coords.join(','));
	
	Ctx.clearRect(0, 0, Canvas.width, Canvas.height);
	Ctx.beginPath();
	
	Ctx.moveTo(Coords[0].x, Coords[0].y);
	for(var i = 1; i < Coords.length; i++) {
		Ctx.lineTo(Coords[i].x, Coords[i].y);
	}
	Ctx.lineWidth = 3;
	Ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
	Ctx.strokeStyle = 'rgba(30, 30, 30, 0.6)';
	Ctx.closePath();
	Ctx.stroke();
	Ctx.fill();
	
}

function AddArea() {
	var coordinates_to_send = [];
	for(var i = 0; i < Coords.length; i++)
		coordinates_to_send.push([Coords[i].x, Coords[i].y]);

	jQuery.post(ajaxurl, { 
		action: 'imgmap_save_area',
		parent_post: jQuery('#post_ID').val(),
		coords: coordinates_to_send.join(',')
	}, function(response) {
		console.log(response);
		response = JSON.parse(response);
		jQuery('#imagemap-areas > div > ul').prepend(response.html);
		jQuery('.area-list-element').change(function() { DrawSavedAreas(SavedAreasCanvas, SACtx); });
		jQuery('.delete-area').click(DeleteArea);
		Coords = [];
		Ctx.clearRect(0, 0, Canvas.width, Canvas.height);
		DrawSavedAreas(SavedAreasCanvas, SACtx);
	});
}

function DeleteArea() {
	if(!confirm('Do you really want to delete this area?'))
		return false;
		
	var id = jQuery(this).attr('data-area');
	var element = jQuery(this);
	jQuery.post(ajaxurl, { 
		action: 'imgmap_delete_area',
		post: id
		}, function(response) {
		response = JSON.parse(response);
		element.closest('li').remove();
		DrawSavedAreas(SavedAreasCanvas, SACtx);
	});
}

function DrawSavedAreas(canvas, ctx) {
	jQuery.post(ajaxurl, { 
		action: 'imgmap_get_area_coordinates',
		post: jQuery('#post_ID').val()
		}, function(response) {
		var areas = JSON.parse(response);
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		for(var i = 0; i < areas.length; i++) {
			
			var coords = areas[i].coords.split(',');
			
			if(jQuery('#area-checkbox-' + areas[i].id).is(':checked')) {
			
				ctx.beginPath();
				ctx.moveTo(coords[0], coords[1]);
				for(var j = 0; j < coords.length; j += 2) {
					ctx.lineTo(coords[j], coords[j + 1]);
				}
				ctx.fillStyle = 'rgba(255, 0, 0, 0.8)';
				ctx.strokeStyle = 'rgba(30, 30, 30, 0.8)';
				ctx.lineWidth = 3;
				ctx.closePath();
				ctx.fill();
				ctx.stroke();
			}
		}
	});
}