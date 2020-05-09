$(function(){
	"use strict";
	var albummanager = $('.albummanager'),
			albumList = albummanager.find('.album-list');
	
	albumList.on('click', '.album-item', function(e) {
		e.preventDefault();
		var album = $(this).attr('href'),
				albumID = $(this).attr('id'),
				albumTitle;
		if(albumID === 'farmhouse') { albumTitle = 'Farmhouse'; }
		if(albumID === 'minneotahouse') { albumTitle = 'Minneota House'; }
		if(albumID === 'operahouse') { albumTitle = 'Opera House'; }
		var heading = $('<h3>'+albumTitle+'</h3>');
	
		$.get('json-file_info.php', { recursive: '1', search_ext: 'jpg', dir: album }, function(data) {
			var response = [data];
			var scannedFiles = [];
			var filemanager = $('.filemanager'),
			fileList = filemanager.find('.data');
			fileList.empty();
			heading.appendTo(fileList);

			render(response);

			function render(data) {

				data.forEach(function(d){
					if(d.type === 'file') {
						scannedFiles.push(d);
					} else if(d.type === 'folder') {
						render(d.items);
					}
				});

			}
			
			if(scannedFiles.length) {
				scannedFiles.forEach(function(f){
					var name = escapeHTML(f.name),
							//fileSize = bytesToSize(f.size),
							path = escapeHTML(f.path),
							fileType = name.split('.'),
							fileDir = dirname(path),
							fileBase = fullpath(path),
							thumbnail = fileBase+'/thumbnail/'+name,
							icon;

					fileType = fileType[fileType.length-1];
					fileType = fileType.toLowerCase();
					icon = '<span class="icon file f-'+fileType+'">.'+fileType+'</span>';
					//var parse = "parse.php?file=" + encodeURIComponent(path);

					var file = $('<a href="'+path+'" title="'+fileDir+'" data-gallery><img src="'+thumbnail+'" alt="'+fileDir+'"></a>');
					file.appendTo(fileList);
					/* feather is a global created by icon script on page */
					//feather.replace();
				});
			}

		}); /* End of ajax handling */
		
	});
		
		// Convert file sizes from bytes to human readable units
		/*function bytesToSize(bytes) {
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			if (bytes === 0){ return '0 Bytes'; }
			var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
			return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
		}*/
		
		// This function escapes special html characters in names
		function escapeHTML(text) {
//			return text.replace(/\&/g,'&amp;').replace(/\</g,'&lt;').replace(/\>/g,'&gt;');
			return text.replace(/\&/g,'&amp;').replace(/</g,'&lt;').replace(/\>/g,'&gt;');
		}
		
		// Returns parent directory only
		function dirname(path) {
			return path.replace( /.+\/(.+)\/[^\/]+$/, '$1' );
		}
		
		// Returns path without filename
		function fullpath(path) {
			return path.replace( /\\/g, '/' ).replace( /\/[^\/]*$/, '' );
		}

});