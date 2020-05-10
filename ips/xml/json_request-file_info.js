$(function(){
	"use strict";
	
	$.get('json-file_info.php', { recursive: '1', search_ext: 'xml' }, function(data) {
		var response = [data];
		var scannedFiles = [];
		var filemanager = $('.filemanager'),
		fileList = filemanager.find('.data');
		
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

		console.log(scannedFiles);
		if(scannedFiles.length) {
			scannedFiles.forEach(function(f){
				var fileSize = bytesToSize(f.size),
						name = escapeHTML(f.name),
						path = escapeHTML(f.path),
						fileType = name.split('.'),
						fileBase = dirname(path),
						icon;
				
				fileType = fileType[fileType.length-1];
				fileType = fileType.toLowerCase();
				icon = '<span class="icon file f-'+fileType+'">.'+fileType+'</span>';
				var parse = "parse.php?file=" + encodeURIComponent(path);
				
				var file = $('<tr class="files"><td><a class="d-flex align-items-center text-muted" href="'+parse+'"><span data-feather="cpu"></span></a></td><td>'+icon+'</td><td><a href="'+path+'" title="'+path+'" class="files"><span class="name">'+name+'</span></a></td><td><span class="details">'+fileSize+'</span></td></tr>');
				file.appendTo(fileList);
				/* feather is a global created by icon script on page */
				feather.replace();
			});
		}
		
		// Convert file sizes from bytes to human readable units
		function bytesToSize(bytes) {
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			if (bytes === 0){ return '0 Bytes'; }
			var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
			return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
		}
		
		// This function escapes special html characters in names
		function escapeHTML(text) {
//			return text.replace(/\&/g,'&amp;').replace(/\</g,'&lt;').replace(/\>/g,'&gt;');
			return text.replace(/\&/g,'&amp;').replace(/</g,'&lt;').replace(/\>/g,'&gt;');
		}
		
		// Returns parent directory only
		function dirname(path) {
			return path.replace( /.+\/(.+)\/[^\/]+$/, '$1' );
		}			
		
	}); /* End of ajax handling */

});