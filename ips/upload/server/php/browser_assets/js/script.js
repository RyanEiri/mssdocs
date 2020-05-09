$(function(){
	"use strict";
	
	var filemanager = $('.filemanager'),
		breadcrumbs = $('.breadcrumbs'),
		fileList = filemanager.find('.data'),
		buttons = $('.filebrowser-buttonbar'); /* FHP Addition */

	// Start by fetching the file data from scan.php with an AJAX request

	$.get('json-file_info.php', function(data) {

		var response = [data],
			currentPath = '',
			breadcrumbsUrls = [];

		var folders = [],
			files = [];

		// This event listener monitors changes on the URL. We use it to
		// capture back/forward navigation in the browser.

		$(window).on('hashchange', function(){

			//goto(window.location.hash);
			// The following line replaces the prior because
			// the hash is available in more browsers with the
			// following method. 
			goto(location.href.substr(location.href.indexOf('#')));

			// We are triggering the event. This will execute 
			// this function on page load, so that we show the correct folder:

		}).trigger('hashchange');


		// Hiding and showing the search box

		filemanager.find('.search').click(function(){

			var search = $(this);

			search.find('span').hide();
			search.find('input[type=search]').show().focus();

		});


		// Listening for keyboard input on the search field.
		// We are using the "input" event which detects cut and paste
		// in addition to keyboard input.

	//	filemanager.find('input').on('input.search', function(e){
		$( "#search" ).keypress(function(){ 

			folders = [];
			files = [];

			var value = this.value.trim();

			if(value.length) {

				filemanager.addClass('searching');

				// Update the hash on every key stroke
				window.location.hash = 'search=' + value.trim();

			}

			else {

				filemanager.removeClass('searching');
				window.location.hash = encodeURIComponent(currentPath);

			}

		}).on('keyup', function(e){

			// Pressing 'ESC' button triggers focusout and cancels the search

			var search = $(this);

			if(e.keyCode === 27) {

				search.trigger('focusout');

			}

		}).focusout(function(){

			// Cancel the search

			var search = $(this);

			if(!search.val().trim().length) {

				window.location.hash = encodeURIComponent(currentPath);
				search.hide();
				search.parent().find('span').show();

			}

		});

		
		// Begin FHP Additions

		// added to select folders for manipulation::FHP
		$('#selectAllFolderList').click(function(){
		  if(this.checked){
		    $(fileList).find('input.folderCheckbox').each(function() {
			$(this).prop('checked', true);
		    });
		  } else {
		    $(fileList).find('input.folderCheckbox').each(function() {
			$(this).prop('checked', false);
		    });
		  }
		});

		// Clicking on individual folders
		
                fileList.on('click', 'li.folders', function(e){
                        e.preventDefault();

                        var folderBox = $(this).find('input.folderCheckbox');

			if ($('#selectAllFolderList').is(':checked')) {
			  $('#selectAllFolderList').prop('checked', false);
			}

                        if(folderBox.is(':checked')) {
                                $(folderBox.prop('checked', false));
                        }
                        else {
                                $(folderBox.prop('checked', true));
                        }

			if ($('.folderCheckbox:checked').length === $('.folderCheckbox').length) {
			  $('#selectAllFolderList').prop('checked', true);
			}
                });

		fileList.on('dblclick', 'li.folders', function(e){
			e.preventDefault();

			var nextDir = $(this).find('a.folders').attr('href');

			if(filemanager.hasClass('searching')) {

				// Building the breadcrumbs

				breadcrumbsUrls = generateBreadcrumbs(nextDir);

				filemanager.removeClass('searching');
				filemanager.find('input[type=search]').val('').hide();
				filemanager.find('span').show();
			}
			else {
				breadcrumbsUrls.push(nextDir);
			}

			window.location.hash = encodeURIComponent(nextDir);
			currentPath = nextDir;
		});

		// added to select folder for manipulation::FHP
		fileList.on('contextmenu', 'li.folders', function(e){
			e.preventDefault();

			var showFolder = $(this).find('a.folders').attr('href');
			$('#folderNameText').text(showFolder);
			$('#folderNameRadio').val(showFolder);

			singleFolderGrabDB(showFolder);

			$('#folderFunctions').modal('show');
		});


		// added to select files for manipulation::FHP
		// Clicking on selectAllFile toggle
                $('#selectAllFileList').click(function(){
                  if(this.checked){
                    $(fileList).find('input.fileCheckbox').each(function() {
                      $(this).prop('checked', true);
                    });
                  } else {
                    $(fileList).find('input.fileCheckbox').each(function() {
                      $(this).prop('checked', false);
                    });
                  }
                });

		// Clicking on individual files
		fileList.on('click', 'li.files', function(e){
			e.preventDefault();

			var fileBox = $(this).find('input.fileCheckbox');

			if ($('#selectAllFileList').is(':checked')) {
			  $('#selectAllFileList').prop('checked', false);
			}

			if(fileBox.is(':checked')) {
				$(fileBox.prop('checked', false));
			}
			else {
				$(fileBox.prop('checked', true));
			}

			if ($('.fileCheckbox:checked').length === $('.fileCheckbox').length) {
			  $('#selectAllFileList').prop('checked', true);
			}
		});

		// Context Menu for individual file
		fileList.on('contextmenu', 'li.files', function(e){
			e.preventDefault();
			
			var showFile = $(this).find('a.files').attr('href');
			singleFileGrabDB(showFile);
			
			// warning that the filename has changed from its default value
/*			$('#warningCurrentFileName').text(showFile);
			$('#fileName').keydown(function(event) {
			  $('#warningFileName').show();
			});
*/
		
			$('#fileFunctions').modal('show');
		});		


		// Clicking on Open Folder button
		buttons.on('click', '.open-folder-button', function(){
			var nextDir = $('input:checkbox:checked.folderCheckbox').val();

			if(filemanager.hasClass('searching')) {
			  // Building the breadcrumbs
			  breadcrumbsUrls = generateBreadcrumbs(nextDir);
			  filemanager.removeClass('searching');
			  filemanager.find('input[type=search]').val('').hide();
			  filemanager.find('span').show();
			}
			else {
			  if(nextDir) {
			    breadcrumbsUrls.push(nextDir);
			  } else {
			    alert('No folder selected! Please select a folder to open.');
			  }
			}
			if(nextDir) {
			  window.location.hash = encodeURIComponent(nextDir);
			  currentPath = nextDir;
			  $(buttons).find('input#selectAllFolderList').prop('checked', false);
			} else {
			  
			}
		});

		// Click on File Info button
		buttons.on('click', '.open-file-button', function(){
			var infoFile = $('input:checkbox:checked.fileCheckbox').val();
			singleFileGrabDB(infoFile);
			$('#fileFunctions').modal('show');
		});

		// Click on Folder Info button
		buttons.on('click', '.folder-info-button', function(){
			var infoFolder = $('input:checkbox:checked.folderCheckbox').val();
			$('#folderNameText').text(infoFolder);
			$('#folderNameRadio').val(infoFolder);
			singleFolderGrabDB(infoFolder);
			$('#folderFunctions').modal('show');
		});

		// end of FHP additions


		// Clicking on breadcrumbs

		breadcrumbs.on('click', 'a', function(e){
			e.preventDefault();

			var index = breadcrumbs.find('a').index($(this)),
				nextDir = breadcrumbsUrls[index];

			breadcrumbsUrls.length = Number(index);

			window.location.hash = encodeURIComponent(nextDir);

		});


		// Navigates to the given hash (path)

		function goto(hash) {

			hash = decodeURIComponent(hash).slice(1).split('=');

			if (hash.length) {
				var rendered = '';

				// if hash has search in it

				if (hash[0] === 'search') {

					filemanager.addClass('searching');
					rendered = searchData(response, hash[1].toLowerCase());

					if (rendered.length) {
						currentPath = hash[0];
						render(rendered);
					}
					else {
						render(rendered);
					}

				}

				// if hash is some path

				else if (hash[0].trim().length) {

					rendered = searchByPath(hash[0]);

					if (rendered.length) {

						currentPath = hash[0];
						breadcrumbsUrls = generateBreadcrumbs(hash[0]);

						// added to send path to add folder form::FHP
						$('.path_value').remove(); // remove prior instance of path
						$.each(breadcrumbsUrls, function(i){
						  $('.path_value').remove(); // remove so we only see the final value containing the full path
						  $('#add_folder-group').append('<input type="hidden" class="path_value" name="path_value" value="'+breadcrumbsUrls[i]+'">');
						});
						// end of FHP addition

						render(rendered);

					}
					else {
						currentPath = hash[0];
						breadcrumbsUrls = generateBreadcrumbs(hash[0]);

						// added to send path to add folder form::FHP
						$('.path_value').remove(); // remove prior instance of path
						$.each(breadcrumbsUrls, function(i){
						  $('.path_value').remove(); // remove so we only see the final value containing the full path
						  $('#add_folder-group').append('<input type="hidden" class="path_value" name="path_value" value="'+breadcrumbsUrls[i]+'">');
						});
						// end of FHP addition

						render(rendered);
					}

				}

				// if there is no hash

				else {
					currentPath = data.path;
					breadcrumbsUrls.push(data.path);

					// added to send path to add folder form::FHP
					$('.path_value').remove();
					$('#add_folder-group').append('<input type="hidden" class="path_value" name="path_value" value="'+breadcrumbsUrls+'">');
					// end of FHP addition

					render(searchByPath(data.path));
				}
			}
		}

		// Splits a file path and turns it into clickable breadcrumbs

		function generateBreadcrumbs(nextDir){
			var path = nextDir.split('/').slice(0);
			for(var i=1;i<path.length;i++){
				path[i] = path[i-1]+ '/' +path[i];
			}
			return path;
		}


		// Locates a file by path

		function searchByPath(dir) {
			var path = dir.split('/'),
				demo = response,
				flag = 0;

			for(var i=0;i<path.length;i++){
				for(var j=0;j<demo.length;j++){
					if(demo[j].name === path[i]){
						flag = 1;
						demo = demo[j].items;
						break;
					}
				}
			}

			demo = flag ? demo : [];
			return demo;
		}


		// Recursively search through the file tree

		function searchData(data, searchTerms) {

			data.forEach(function(d){
				if(d.type === 'folder') {

					searchData(d.items,searchTerms);

					if(d.name.toLowerCase().match(searchTerms)) {
						folders.push(d);
					}
				}
				else if(d.type === 'file') {
					if(d.name.toLowerCase().match(searchTerms)) {
						files.push(d);
					}
				}
			});
			return {folders: folders, files: files};
		}


		// Render the HTML for the file manager

		function render(data) {

			var scannedFolders = [],
				scannedFiles = [];

			if(Array.isArray(data)) {

				data.forEach(function (d) {

					if (d.type === 'folder') {
						scannedFolders.push(d);
					}
					else if (d.type === 'file') {
						scannedFiles.push(d);
					}

				});

			}
			else if(typeof data === 'object') {

				scannedFolders = data.folders;
				scannedFiles = data.files;

			}


			// Empty the old result and make the new one

			fileList.empty().hide();

			if(!scannedFolders.length && !scannedFiles.length) {
				filemanager.find('.nothingfound').show();
				fileList.hide();
			}
			else {
				filemanager.find('.nothingfound').hide();
				fileList.show();
			}

			if(scannedFolders.length) {

				scannedFolders.forEach(function(f) {

					var itemsLength = f.items.length,
						name = escapeHTML(f.name),
						icon = '<span class="icon folder"></span>';

					if(itemsLength) {
						icon = '<span class="icon folder full"></span>';
					}

					if(itemsLength === 1) {
						itemsLength += ' item';
					}
					else if(itemsLength > 1) {
						itemsLength += ' items';
					}
					else {
						itemsLength = 'Empty';
					}

					var folder = $('<li class="folders"><input type="checkbox" class="folderCheckbox" value="'+f.path+'"><a href="'+ f.path +'" title="'+ f.path +'" class="folders">'+icon+'<span class="name">' + name + '</span> <span class="details">' + itemsLength + '</span></a></li>');
					folder.appendTo(fileList);
				});

			}

			if(scannedFiles.length) {

				scannedFiles.forEach(function(f) {

					var fileSize = bytesToSize(f.size),
						name = escapeHTML(f.name),
						fileType = name.split('.'),
						icon = '<span class="icon file"></span>';

					fileType = fileType[fileType.length-1];
					fileType = fileType.toLowerCase();

					icon = '<span class="icon file f-'+fileType+'">.'+fileType+'</span>';

					var file = $('<li class="files"><input type="checkbox" class="fileCheckbox" value="'+f.path+'"><a href="'+ f.path+'" title="'+ f.path +'" class="files">'+icon+'<span class="name">'+ name +'</span> <span class="details">'+fileSize+'</span></a></li>');
					file.appendTo(fileList);
				});

			}


			// Generate the breadcrumbs

			var url = '';

			if(filemanager.hasClass('searching')){

				url = '<span>Search results: </span>';
				fileList.removeClass('animated');

			}
			else {

				fileList.addClass('animated');

				breadcrumbsUrls.forEach(function (u, i) {

					var name = u.split('/');

					if (i !== breadcrumbsUrls.length - 1) {
						url += '<a href="'+u+'"><span class="folderName">' + name[name.length-1] + '</span></a> <span class="arrow">→</span> ';
					}
					else {
						url += '<span class="folderName">' + name[name.length-1] + '</span>';
					}

				});

			}

			breadcrumbs.text('').append(url);


			// Show the generated elements

			fileList.animate({'display':'inline-block'});

		}


		// This function escapes special html characters in names

		function escapeHTML(text) {
			return text.replace(/\&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		}


		// Convert file sizes from bytes to human readable units

		function bytesToSize(bytes) {
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			if (bytes === 0){ return '0 Bytes'; }
			var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
			return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
		}

		// Grab filesystem and database information for a single file

		function singleFileGrabDB(grabfile) {
//			console.log(grabfile);
			var dbData = {};
			var dirNameContext = grabfile.match(/(.*)[\/\\]/)[1]||'';
			var fileNameContext = grabfile.replace(/^.*[\\\/]/, '');
			var thumbnailContext = dirNameContext + '/thumbnail/' + fileNameContext;
			dbData.context = 'file';
			dbData.fileName = fileNameContext;
			dbData.dirName = dirNameContext;
			$.ajax({
			  type:"post",
			  url:"json-database_info.php",
			  dataType: 'json',
			  data: dbData,
			})			
//			  success: function(data) {
			  .done(function(data) {
//				  console.log(data);
			    if ( ! data.success) {
				if (data.errors.database) {
				  $('#fileFunctionsBody').addClass('has-error');
				  $('#fileFunctionsBody').append('<div class="help-block">' + data.errors.database + '</div>');
				}
			    } else {
//console.log(data);
				// Grab the folders available
				var availableFolders = [];
				collectAvailableFolders(response);
				function collectAvailableFolders(object) {
					object.forEach(function(d){
						if(d.type === 'folder') {
							if(d.name !== 'thumbnail'){
								availableFolders.push(d.path);
								collectAvailableFolders(d.items);
							}
						}
					});
				}
				// Fill in the select box for the available folders
				$('#dirName').find('option').remove();
				$.each(availableFolders, function(i,val){
				  $('select#dirName').append('<option value="'+val+'">'+val+'</option>');
				});
				// Set the values for the fields
				$('#contextFileId').val(data.fileId);
				$('#dirName option[value="'+dirNameContext+'"]').attr("selected", true);
				$('#previousFileFolder').attr('value', dirNameContext);
				$('#fileName').attr('value', fileNameContext);
				$('#previousFileName').attr('value', fileNameContext);
				$('#fileSize').text(data.fileSize);
				$('#fileType').text(data.fileType);
				$('#fileUrl').html('<a href="' + data.fileUrl +'">' + data.fileUrl + '</a>');
				$('.fileUrl').attr('href', data.fileUrl);
				$('#fileTitle').attr('value', data.fileTitle);
				$('#previousFileTitle').attr('value', data.fileTitle);
				$('#fileDescription').attr('value', data.fileDescription);
				$('#previousFileDescription').attr('value', data.fileDescription);
				$('#fileDate').text(data.fileDate);
				$('#fileThumbnail').attr('src', thumbnailContext);
			    }
			 }) /* end of .done function */
			 .fail(function(data) {
			 	console.log(data);
			 });
//			  } /* end of success function */
//			}); /* end of ajax call */
		}
		// Grab the filesystem and database information for a single folder
		function singleFolderGrabDB(grabfolder) {
			var dbData = {};
			dbData.context = 'folder';
			dbData.dirName = grabfolder;
			$.ajax({
			  type:"post",
			  url:"json-database_info.php",
			  dataType: 'json',
			  data: dbData,
			})
			   .done(function(data) {
			    if ( ! data.success) {
				if (data.errors.database) {
				  $('#folderFunctionsBody').addClass('has-error');
				  $('#folderFunctionsBody').append('<div class="help-block">' + data.errors.database + '</div>');
				}
			    } else {
//				console.log(data);
				// Set the values for the fields
				if (data.folderId) {
				  $('#folder_id').val(data.folderId);
				  $('#folder_name').val(data.folderName);
				}
				if (data.zipname) {
				  $('#zipname').append('<a type="application/zip" href=' + data.zipURL + ' download>' + data.zipname + '</a> created at ' + data.zipDate + ' (PST or PDT)');
				} else {
				  $('#zipname').append('No zip archive created yet.');
				}
				var filesObj = {};
				filesObj = data.files;
				$.each(filesObj, function(i, val){
				  var fileId = val.id;
				  var fileName = val.name;
				  var fileTitle = val.title;
				  var fileDescription = val.description;
				  fileName = fileName.replace(/^.*[\\\/]/, ''); 
				  $('#filetable').append(
					  '<tr>' +
					  '<div class="form-group"><td class="col-sm-1 col-lg-1">' +
					    '<p class="form-control-static input-sm">' + 
					    fileId + 
					    '</p>' +
					    '<input class="form-control" name="file_id[]" id="file_id-' + i + 
					    '" type="hidden" value="' + fileId + 
					    '">' +
					  '</td></div>' +
					  '<div class="form-group"><td class="col-sm-3 col-lg-3">' +
					    '<input class="form-control input-sm" name="file_name[]" id="file_name-' + i + 
					    '" type="text" ' + 'value="' + fileName + 
					    '">' +
					  '</td></div>' +
					  '<div class="form-group"><td class="col-sm-4 col-lg-4">' +
					    '<input class="form-control input-sm" name="file_title[]" id="file_title-' + i + 
					    '" type="text" ' + 'value="' + fileTitle + 
					    '">' +
					  '</td></div>' +
					  '<div class="form-group"><td class="col-sm-4 col-lg-4">' +
					    '<input class="form-control input-sm" name="file_description[]" id="file_description-' + i +
					    '" type="text" ' + 'value="' + fileDescription + 
					    '">' +
					  '</td></div>' +
					  '</tr>'
					  );
				});
			    }
			   })
			   .fail(function(data) {
					console.log(data);
			   });
		}


	});
});