/* 
* 
* 
* 
* 
* 
*/
var Image;
var Canvas, Ctx;
var Areas = [];
Areas[0] = [];

jQuery(function() {
	if(FileAPIAvailable()) {
		document.getElementById('file').addEventListener('change', handleFileSelect, false);
	}
	jQuery('#image').load(function() { jQuery(this).show(200); }).
	click(imgClick);
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
	};
	img.src = jQuery('#image').attr('src');
	
	Canvas = document.getElementById('image-coord-canvas');
	Canvas.width = Image.width;
	Canvas.height = Image.height;
	Ctx = Canvas.getContext('2d');
	
	jQuery('.delete-area').click(DeleteArea);
});

function AreaClicked(data) {
	console.log(data);
}

function FileAPIAvailable() {
	return window.File && window.FileList && window.FileReader && window.Blob;
}

function handleFileSelect(evt) {
	var files = evt.target.files;

	for(var i = 0, f; f = files[i]; i++) {
		
		if(!f.type.match('image/*'))
			continue;
		
		var reader = new FileReader();
		reader.onload = (function(theFile) {
				return function(e) {
					document.getElementById('image').src = e.target.result;
				};
		})(f);
		
		reader.readAsDataURL(f);
		
	}
}

function imgClick(evt) {
	var offset = jQuery(this).offset();
	AddCoords(evt.pageX - offset.left, evt.pageY - offset.top);	
}

function AddCoords(x, y) {
	
	if(!Image.width) {
		alert("Source image haven't been downloaded yet! Please try again in few seconds.");
	}
	
	if(jQuery('#coords').text() != '')
		jQuery('#coords').append(', ');
	jQuery('#coords').append(
		Math.floor(x * (Image.width/jQuery('#image').width())) + ', ' + Math.floor(y * (Image.height/jQuery('#image').height())));
}

function AddArea() {
	jQuery.post(ajaxurl, { 
		action: 'imgmap_save_area',
		parent_post: 75,
		coords: jQuery('#coords').text()
	}, function(response) {
		response = JSON.parse(response);
		jQuery('#imagemap-areas > ul').append(response.html);
		jQuery('#coords').text('');
	});
}

function DeleteArea() {
	var id = jQuery(this).attr('data-area');
	
	jQuery.post(ajaxurl, { 
		action: 'imgmap_delete_area',
		post: id
		}, function(response) {
		response = JSON.parse(response);
		console.log(response);
	});
}