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

jQuery(function($) {
	console.log($.mapster);
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
		onClick: AreaClicked
	});
});

function AreaClicked(data) {
	console.log(data);
}

