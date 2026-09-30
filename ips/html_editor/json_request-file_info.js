// Lists the HTML files the editor can open (see json-file_info.php) and links each to edit.php.
$(function () {
	"use strict";

	var rows = $('.filemanager .data');

	function bytesToSize(bytes) {
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		if (bytes === 0) { return '0 Bytes'; }
		var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)), 10);
		return Math.round(bytes / Math.pow(1024, i)) + ' ' + sizes[i];
	}

	// The name of the folder a file sits in ("files/html_templates/x.html" -> "html_templates").
	function parentFolder(path) {
		var parts = path.split('/');
		return parts.length > 2 ? parts[parts.length - 2] : '';
	}

	function collect(items, found) {
		items.forEach(function (d) {
			if (d.type === 'file') {
				found.push(d);
			} else if (d.type === 'folder') {
				collect(d.items, found);
			}
		});
		return found;
	}

	function message(text) {
		rows.append($('<tr>').append($('<td colspan="3">').text(text)));
	}

	$.get('json-file_info.php', { recursive: '1', search_ext: 'html' }, function (data) {
		if (data.success === false) {
			message('The file list could not be loaded.');
			return;
		}
		var files = collect(data.items || [], []);
		if (!files.length) {
			message('No HTML files found.');
			return;
		}
		files.forEach(function (f) {
			var link = $('<a class="files">')
				.attr('href', 'edit.php?html_file=' + encodeURIComponent(f.path))
				.attr('title', f.path)
				.append($('<span class="name">').text(f.name));
			rows.append($('<tr class="files">')
				.append($('<td>').append(link))
				.append($('<td>').text(parentFolder(f.path)))
				.append($('<td>').text(bytesToSize(f.size))));
		});
	}).fail(function () {
		message('The file list could not be loaded.');
	});
});
