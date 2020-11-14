$(document).ready(function() {
  $.ajax({
    type: 'POST',
    url: 'json/json-session_id.php',
    success: function(session) {
      var sessionID = session.session_id;
      var timer;

  // turn tooltips on
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

	// process the form
	$('#folderFunctionsForm').submit(function(event) {

		$('.form-error').removeClass('has-error'); // remove the error class from a prior submission
		$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-success').remove();

		// get the form data

    // initialize object to send selection info
		var dataObj = {};

		// add the selected files to the dataObject
		var fileArr = [];
		fileArr = $('input:checkbox:checked.fileCheckbox').map(function() {
		  return $(this).val()
		}).get()

		dataObj['selectedFiles'] = {};
		$.each(fileArr, function(i, val){
		  var dirname = val.match(/(.*)[\/\\]/)[1]||'';
                  var filename = val.replace(/^.*[\\\/]/, '');
                  dataObj['selectedFiles'][i] = {};
                  dataObj['selectedFiles'][i]['filename'] = filename;
                  dataObj['selectedFiles'][i]['dirname'] = dirname;
		});


		// add the selected folders to the dataObject
		var folderArr = [];
		var folderArr = $('input:checkbox:checked.folderCheckbox').map(function() {
		  return $(this).val()
		}).get()

		dataObj['selectedFolders'] = {};
		$.each(folderArr, function(i, val){
		  dataObj['selectedFolders'][i] = val;
		});

		// add the single folder selection to the dataObject
		dataObj['folderToZip'] = $('#folderNameRadio').val();

		// Create a new ajax filesystem scan so that json-folder_functions can
		// get the file heirarchy for selected folder to be zipped
		/*$.ajax({
		  type:"post",
		  url:"json/json-file_info.php",
		  data: {
	            "recursive":  "1"
	          },
		  dataType: 'json',
		  success:function(data){
		    obj = [data];

		    // collect the folders and put them in the zipFolders array
		    // collect the files and put them in the zipFiles array
		    zipFolders = [];
		    collectFolders(obj);
		    function collectFolders(object) {
			object.forEach(function(d){
			  if(d.type === 'folder') {
			    zipFolders.push(d.path);
			    collectFolders(d.items);
			  }
			});
		    }
		    zipFiles = [];
		    collectFiles(obj);
		    function collectFiles(object) {
			object.forEach(function(d){
			  if(d.type ==='file') {
			    zipFiles.push(d.path);
			  }
			  if(d.type === 'folder') {
			    collectFiles(d.items);
			  }
			});
		    }
	// Add the zipFolders and the zipFiles arrays to the dataObject
		    dataObj['zipFolders'] = {};
		    $.each(zipFolders, function(i, val){
			dataObj['zipFolders'][i] = val;
		    });
		    dataObj['zipFiles'] = {};
		    $.each(zipFiles, function(i, val){
			var dirname = val.match(/(.*)[\/\\]/)[1]||'';
			var filename = val.replace(/^.*[\\\/]/, '');
			dataObj['zipFiles'][i] = {};
			dataObj['zipFiles'][i]['filename'] = filename;
			dataObj['zipFiles'][i]['dirname'] = dirname;

		    });

	// Pass the created dataObject to the php zip process in json-folder_functions
			//console.log(dataObj);
		    zipFolder(dataObj);
        	    timer = window.setInterval(refreshProgress, 250);
		  } // End of success handling
		}); // End of file heirarchy processing  */

    zipFolder(dataObj);
      timer = window.setInterval(refreshProgress, 250);


		// process the zip form through json-folder_functions
	function zipFolder(dObj){
		$.ajax({
		  type		: 'POST', //define the type of HTTP connection we want to use
		  url		: 'json/json-folder_functions.php', //the url where we want to POST
		  data		: dObj, // our data object
		  dataType	: 'json', // what type of data do we expect back from the server
		  encode		: true
		})
			//using the done promise callback
			.done(function(data) {

				// log data to the console for debugging
				//console.log(data);

				// here we will handle errors and validation messages
				if ( ! data.success) {

				  // handle errors for zip archive file -------
				  if (data.errors.archiveFile) {
					 $('#folderFunctionsForm').addClass(
					   'has-error'
					 ); // add the error class to show red input
					 $('#folderFunctionsForm').append(
					   '<div class="help-block">' + data.errors.archiveFile + '</div>'
					 ); // add the actual error message under our input
         }

				  // handle errors for selected files and folders -------
				  if (data.errors.folderToZip) {
					$('#folderFunctionsForm').addClass(
					  'has-error'
					); // add the error class to show red input
					$('#folderFunctionsForm').append(
					  '<div class="help-block">' + data.errors.folderToZip + '</div>'
					); // add the actual error message under our input
				  }

				  // handle errors for move to folder ------
				  /*if (data.errors.zipFiles) {
					$('#folderFunctionsForm').addClass(
					  'has-error'
					); // add the error class to show red input
					$('#folderFunctionsForm').append(
					  '<div class="help-block">' + data.errors.zipFiles + '</div>'
					); // add the actual error message under our input
				  }

				  if (data.errors.zipFolders) {
					$('#folderFunctionsForm').addClass(
					  'has-error'
					); // add the error class to show red input
					$('#folderFunctionsForm').append(
					  '<div class="help-block">' + data.errors.zipFolders + '</div>'
					); // add the actual error message under our input
				  }

				  if (data.errors.selectedFiles) {
					$('#folderFunctionsForm').addClass(
					  'has-error'
					); // add the error class to show red input
					$('#folderFunctionsForm').append(
					  '<div class="help-block">' + data.errors.selectedFiles + '</div>'
					); // add the actual error message under our input
				  }

				  if (data.errors.selectedFolders) {
					$('#folderFunctionsForm').addClass(
					  'has-error'
					); // add the error class to show red input
					$('#folderFunctionsForm').append(
					  '<div class="help-block">' + data.errors.selectedFolders + '</div>'
					); // add the actual error message under our input
        }*/

				} else {

					// ALL GOOD! Show success and alter form as needed.
					//$('#folderFunctionsForm').prepend(
					//  '<div class="alert alert-success">' + data.message + '</div>'
					//);
					$("#zip_message").html(
					  '<div class="alert alert-success">' + data.message + '</div>'
					);
					$('#zipname').html(
					  '<br /><h5>Newly created zip file:</h5><a type="application/zip" href="' + data.zipURL + '">' + data.zipFilename + '</a>'
					);

				}
			})

			// using the fail promise callback
			.fail(function(data) {

				// uncomment to show any errors from php
//				console.log(data);
			});
	}

  function refreshProgress(){
    //console.log("hit");
    $.ajax({
      type: 'POST',
      url: 'json/json-progress.php',
      data: {
        "session_id": sessionID
      },
      success: function(data) {
//        $("#zip_progress").html('<div class="bar" style="width:' + data.percent + '%"></div>');
	$("#zip_progress").css('width', data.percent + '%');
	$("#zip_progress").attr('aria-valuenow', data.percent + '%');
        $("#zip_message").html('Adding files... ' + data.percent + '%');
        if(data.percent === 100) {
          $("#zip_message").empty();
          window.clearInterval(timer);
          timer = window.setInterval(completed, 250);
        }
      }
    });
  }

  function completed() {
    $("#zip_message").html('All files added to archive. Saving file. Please wait... <img src=img/loading.gif height="18">');
    window.clearInterval(timer);
  }

		// stop the form from submitting the normal way and refreshing the page
		event.preventDefault();
	});

	// reset the folderFunctionsForm
	$('#folderFunctionsForm').bind('reset', function(event) {
	  // event.preventDefault();
	});

	$('#filesInFolderForm').submit(function(event) {

		$('.form-error').removeClass('has-error'); // remove error class from prior submission
		$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-success').remove();

		// create data object to send to php
		var dataObj = {};

		// pull the form variables
		var folderName = $('#folder_name').val();
		var folderId = $('#folder_id').val();

		// pull the table fields into separate arrays
		var fileId = $("input[name='file_id\\[\\]']")
				.map(function(){return $(this).val();}).get();
		var fileName = $("input[name='file_name\\[\\]']")
				.map(function(){return $(this).val();}).get();
		var fileTitle = $("input[name='file_title\\[\\]']")
				.map(function(){return $(this).val();}).get();
		var fileDescription = $("input[name='file_description\\[\\]']")
				.map(function(){return $(this).val();}).get();

		// collate the separate form field types into an object
		var folderFilesObj = {};
		$.each(fileId, function(i, val){
		  var id = val;
		  folderFilesObj[i] = {};
		  folderFilesObj[i]['id'] = id;
		});
		$.each(fileName, function(i, val){
		  var name = val;
		  folderFilesObj[i]['name'] = name;
		});
		$.each(fileTitle, function(i, val){
		  var title = val;
		  folderFilesObj[i]['title'] = title;
		});
		$.each(fileDescription, function(i, val){
		  var description = val;
		  folderFilesObj[i]['description'] = description;
		});

		// place the folder files object into the data object
		dataObj['filesInFolder'] = folderFilesObj;
		dataObj['folderName'] = folderName;
		dataObj['folderId'] = folderId;
		//console.log(dataObj);

		// The following is how jQuery formats vars so that they can be
		// placed in a GET string. POST is better, and I have not thus
		// far had a problem sending an object to php. However,
		// I have played around with this. Looks like work for
		// little reward at this point. I would rather create objects
		// that can be manipulated as multi-dimensional arrays in PHP.
		// JS does not support multi-dimensional arrays, hence the
		// need for objects which store multiple arrays.

		// **WARNING**
		// The following annotation provides a serialized form string.
		// It does not provide a multidimensional array,
		// and does not currently work with any of the PHP scripts
		// currently written for this project.
//		var datastring = $("#filesInFolderForm").serialize();

		$.ajax({
		  type : 'post',
		  url  : 'json/json-folder_files.php',
		  data : dataObj,
		  dataType	: 'json',
		  encode	: true
		})
			// done callback for completed calls
			.done(function(data) {
			  //console.log(data);

			  // errors and validation
			  if ( ! data.success) {

			    // handle errors and add error message to form
			    if (data.errors) {
				$('#filesInFolderForm').addClass(
				  'has-error'
				); // add the error class to show red input
				$('#filesInFolderForm').prepend(
				  '<div class="help-block">' + data.errors + '</div>'
				);
				$('#filesInFolderForm').append(
				  '<div class="help-block">' + data.errors + '</div>'
  				); // add the actual error message
			    }

			  } else {
			    // Success! Add success message to form
			    $('#filesInFolderForm').prepend(
				'<div class="alert alert-success">' + data.message + '</div>'
			    );
			    $('#filesInFolderForm').append(
			  	'<div class="alert alert-success">' + data.message + '</div>'
			    );

			  }
			})

			// fail callback for uncompleted calls
			.fail(function(data) {
			  // uncomment to show errors from php
//			  console.log(data);
			});

		// stop form from submitting the normal way and refreshing the page
		event.preventDefault();

	});
  }
  })
});
