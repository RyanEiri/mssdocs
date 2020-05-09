$(document).ready(function() {
  // turn tooltips on
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

// process the submission 
	$('#fileFunctionsForm').submit(function(event) {

	  $('.list-group').removeClass('has-error'); // remove the error class from a prior submission
	  $('.list-group').removeClass('has-warning');
	  $('.help-block').remove(); // remove the error text from a prior submission
	  $('.alert-success').remove();

// get the form data from known input entry values
// create the main data object to be submitted
	  var dataObj = {};

// place the information into the data object 
	  dataObj['fileId'] = $('#contextFileId').val();
	  dataObj['previousFileFolder'] = $('#previousFileFolder').val();
	  dataObj['fileFolder'] = $('#dirName').val();
	  newFileFolder = dataObj['fileFolder'];
	  dataObj['previousFileName'] = $('#previousFileName').val();
	  dataObj['fileName'] = $('#fileName').val();
	  newFileName = dataObj['fileName'];
	  dataObj['previousFileTitle'] = $('#previousFileTitle').val();
	  dataObj['fileTitle'] = $('#fileTitle').val();
	  dataObj['previousFileDescription'] = $('#previousFileDescription').val();
	  dataObj['fileDescription'] = $('#fileDescription').val();

// process the form
		$.ajax({
			type	: 'POST', 
			url	: 'json-file_functions.php', 
			data	: dataObj, 
			dataType : 'json', 
			encode	: true
		})
		//using the done promise callback
		.done(function(data) {

			// log data to the console so we can see
			//console.log(data);
			// here we will handle errors and validation messages
			if ( ! data.success) {
				// handle errors for fileId -------
			  if (data.errors.fileId) {
				$('#fileFunctionsBody').addClass( // add the error class to show red input
				  'has-error'
				); 
				$('#fileFunctionsBody').append( // add the actual error message under our input
				  '<div class="help-block">' + data.errors.fileId + '</div>'
				); 
			  }
			  // handle errors for previousFileFolder ------
			  if (data.errors.previousFileFolder) {
				$('#fileFilesystemList-group').addClass( // add the error class to show red input
				  'has-error'
				); 
				$('#fileFilesystemList-group').append( // add the actual error message under our input
				  '<div class="help-block">' + data.errors.previousFileFolder + '</div>'
				); 
			  }
			  // handle errors for fileFolder --------
			  if(data.errors.fileFolder) {
				$('#fileFilesystemList-group').addClass( // add the error class to show red input
				  'has-error'
				);
				$('#fileFilesystemList-group').append( // add the actual error message under our input
				  '<div class="help-block">' + data.errors.fileFolder + '</div>'
				);
			  }
			  // handle errors for fileName ----------
			  if(data.errors.fileName) {
				$('#fileFilesystemList-group').addClass( // add the error class to show red input
				  'has-error'
				);
				$('#fileFilesystemList-group').append( // add the actual error message under our input
				  '<div class="help-block">' + data.errors.fileName + '</div>'
				);
			  }
			  // handle errors for fileTitle -------------
			  if(data.warnings.fileTitle) {
				$('#fileDatabaseList-group').addClass( // add the warning class to show amber input
				  'has-warning'
				);
				$('#fileDatabaseList-group').append( // add the actual warning message under our input
				  '<div class="help-block">' + data.warnings.fileTitle + '</div>'
				);
			  }
			  // handle errors for fileDescription --------
			  if(data.warnings.fileDescription) {
				$('#fileDatabaseList-group').addClass( // add the warning class to show amber input
				  'has-warning'
				);
				$('#fileDatabaseList-group').append( // add the actual warning message under our input
				  '<div class="help-block">' + data.warnings.fileDescription + '</div>'
				);
			  }
			} else {
			  if(data.warnings.filesystem) {
				$('#fileFilesystemList-group').addClass( // add the warning class to show amber input
				  'has-warning'
				);
				$('#fileFilesystemList-group').append( // add the actual warning message under our input
				  '<div class="help-block">' + data.warnings.filesystem + '</div>'
				);
			  }
			  if(data.warnings.database) {
				$('#fileDatabaseList-group').addClass( // add the warning class to show amber input
				  'has-warning'
				);
				$('#fileDatabaseList-group').append( // add the actual warning message under out input
				  '<div class="help-block">' + data.warnings.database + '</div>'
				);
			  }
				// ALL GOOD! just show the success message!
				$('#previousFileName').attr('value', newFileName);
				$('#previousFileFolder').attr('value', newFileFolder);
				$('#fileFunctionsBody').append(
				  '<div class="alert alert-success">' + data.message + '</div>'
				);
			}
			})

			// using the fail promise callback
			.fail(function(data) {

				// show any errors from php
				// best to remove this for production
//				console.log(data);
			});



		// stop the form from submitting the normal way and refreshing the page
		event.preventDefault();
	});

	// reset the form
	$('#fileFunctionsForm').bind('reset', function(event) {
	  $('.list-group').removeClass('has-error'); // remove the error class from a prior submission
	  $('.list-group').removeClass('has-warning');
	  $('.help-block').remove(); // remove the error text from a prior submission
	  $('.alert-success').remove();
	  // event.preventDefault();
	});

});
