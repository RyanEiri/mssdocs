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
					var imageServerBaseBegin = path.match(/^(.*)(files\/)/i),
							imageServerPathEnd = path.match(/(?:files\/)(.*)$/i),
							imageServerURI = encodeURI(imageServerBaseBegin[1]+'image_server.php?image='+imageServerPathEnd[1]),
							thumbnailServerBaseEnd = fileBase.match(/(?:files\/)(.*)$/i),
							thumbnailServerPath = thumbnailServerBaseEnd[1]+'/thumbnail/'+name,
							thumbnailServerURI = encodeURI(imageServerBaseBegin[1]+'image_server.php?thumbnail=true&image='+thumbnailServerPath),
							file = $('<a href="'+imageServerURI+'" title="'+fileDir+'" data-gallery><img src="'+thumbnailServerURI+'" alt="'+fileDir+'"></a>');
					file.appendTo(fileList);
				});
			}

		}); /* End of ajax handling */

	});

		// This function escapes special html characters in names
		function escapeHTML(text) {
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
