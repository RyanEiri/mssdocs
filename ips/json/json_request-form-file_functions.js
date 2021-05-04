$(document).ready(function() {
  "use strict";
  // turn tooltips on
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

  // process the File Info submission
	$('#fileFunctionsForm').submit(function(event) {

    // stop the form from submitting the normal way and refreshing the page
		event.preventDefault();

    // remove prior message classes
    $('.alert-danger').remove();
    $('.alert-secondary').remove(); // secondary alert messages for warnings
	  $('.alert-success').remove();

    // get the form data from known input entry values
    // create the main data object to be submitted
	  var dataObj = {};

    /* Set the newly selected folder based on the selected db folder.
     * The leading 'files/' needs to be stripped for the DB.
    */
    let dbDirName = $('#dbDirName').val();
    let fsDirName = $('#fsDirName').val();
    let reg = /(.*)(files)(.*)/i;
    let selFSDirName = fsDirName.match(reg);
    let serverPath = selFSDirName[1];
    var newFSDirName = serverPath + dbDirName;

    let fileName = $('#fileName').val();
    var newFileName = fileName;

    var fileUrl = $('#fileUrl').val();
    let folderUrl = fileUrl.match(/(.*[\/\\])/)[1]||'';
    var newFileUrl = folderUrl+newFileName;

    // place the information into the data object
	  dataObj['fileId'] = $('#contextFileId').val();
	  dataObj['dbDirName'] = $('#dbDirName').val();
    dataObj['fsDirName'] = newFSDirName;
	  dataObj['previousFileFolder'] = $('#previousFileFolder').val();
	  dataObj['previousFileName'] = $('#previousFileName').val();
	  dataObj['fileName'] = fileName;
    dataObj['fileUrl'] = fileUrl;
    dataObj['newFileUrl'] = newFileUrl;
	  dataObj['previousFileTitle'] = $('#previousFileTitle').val();
	  dataObj['fileTitle'] = $('#fileTitle').val();
	  dataObj['previousFileDescription'] = $('#previousFileDescription').val();
	  dataObj['fileDescription'] = $('#fileDescription').val();

    // set up our new data to pass back to the form for subsequent submissions
    var newDBFileFolder = dataObj['dbDirName'];
    var newFSFileFolder = dataObj['fsDirName'];
    var newFileTitle = dataObj['fileTitle'];
    var newFileDescription = dataObj['fileDescription'];

    // process the form
		$.ajax({
			type	: 'POST',
			url	: 'json/json-file_functions.php',
			data	: dataObj,
			dataType : 'json',
			encode	: true
		})
		//using the done promise callback
		.done(function(data) {

			// here we will handle errors and validation messages
			if ( ! data.success) {
        if (typeof data.errors !== 'undefined') {
  				// handle errors for fileId -------
  			  if (typeof data.errors.fileId !== 'undefined') {
    				$('#file-error').append( // add the actual error message under our input
    				  '<div class="alert alert-danger" role="alert">' + data.errors.fileId + '</div>'
    				);
  			  }
  			  // handle errors for previousFileFolder ------
  			  if (typeof data.errors.previousFileFolder !== 'undefined') {
    				$('#folder-error').append( // add the actual error message under our input
    				  '<div class="alert alert-danger" role="alert">' + data.errors.previousFileFolder + '</div>'
    				);
  			  }
  			  // handle errors for fileFolder --------
  			  if (typeof data.errors.fileFolder !== 'undefined') {
    				$('#folder-error').append( // add the actual error message under our input
    				  '<div class="alert alert-danger" role="alert">' + data.errors.fileFolder + '</div>'
    				);
  			  }
  			  // handle errors for fileName ----------
  			  if (typeof data.errors.fileName !== 'undefined') {
    				$('#file-error').append( // add the actual error message under our input
    				  '<div class="alert alert-danger" role="alert">' + data.errors.fileName + '</div>'
    				);
  			  }
          if (typeof data.errors.fileUrl !== 'undefined') {
            $('#database-error').append(
              '<div class="alert alert-danger" role"alert">' + data.errors.fileUrl + '</div>'
            );
          }
        }
      } else {

				// ALL GOOD! Adjust the form and show the success message!
				$('#previousFileName').attr('value', newFileName);
				$('#previousFileFolder').attr('value', newFSFileFolder);
        $('#previousFileTitle').attr('value', newFileTitle);
        $('#previousFileDescription').attr('value', newFileDescription);
        $('#fileUrl').val(newFileUrl);
        $('.fileUrl').attr('href', newFileUrl);
        $('#fileUrlLink').html('<a href="' + newFileUrl +'">' + newFileUrl + '</a>');
        //console.log(data);
        if (typeof data.message !== 'undefined') {
  				$('#fileFunctionsBody').append(
  				  '<div class="alert alert-success">' + data.message + '</div>'
  				);
        }

			}

		})

    /*
		// fail promise for debug data
		.fail(function(data) {
      console.log(data);
		});
    */

	});

  	// reset the form
  $('#fileFunctionsForm').bind('reset', function(event) {

    // remove prior message classes
    $('.alert-danger').remove();
    $('.alert-secondary').remove(); // secondary alert messages for warnings
	  $('.alert-success').remove();

	});

});
