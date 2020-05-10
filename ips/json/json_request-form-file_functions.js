$(document).ready(function() {
  "use strict";
  // turn tooltips on
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

// process the submission
	$('#fileFunctionsForm').submit(function(event) {

    // stop the form from submitting the normal way and refreshing the page
		event.preventDefault();

	  //$('.list-group').removeClass('has-error'); // remove the error class from a prior submission
	  //$('.list-group').removeClass('has-warning');
	  //$('.help-block').remove(); // remove the error text from a prior submission

    // remove prior message classes
    $('.alert-danger').remove();
    $('.alert-secondary').remove(); // secondary alert messages for warnings
	  $('.alert-success').remove();

// get the form data from known input entry values
// create the main data object to be submitted
	  var dataObj = {};

// Set the newly selected folder based on the
// selected db folder. This requires some
// regular expression trickery in order to
// satisfy the filesystem requirements.
// The main concern being that the leading path
// until after the master 'files' folder needs
// to be removed for the database. The 'files'
// folder should be set as a program option in
// the future.
    let dbDirName = $('#dbDirName').val();
    let fsDirName = $('#fsDirName').val();
    let reg = /(.*)(files)(.*)/i;
    let selFSDirName = fsDirName.match(reg);
    let serverPath = selFSDirName[1];
    //let masterDir = selFSDirName[2];
    //let dbPath = selFSDirName[3];
    let newFSDirName = serverPath + dbDirName;

// place the information into the data object
	  dataObj['fileId'] = $('#contextFileId').val();
	  dataObj['dbDirName'] = $('#dbDirName').val();
    dataObj['fsDirName'] = newFSDirName;
	  var newDBFileFolder = dataObj['dbDirName'];
    var newFSFileFolder = dataObj['fsDirName'];
	  dataObj['previousFileFolder'] = $('#previousFileFolder').val();
	  dataObj['previousFileName'] = $('#previousFileName').val();
	  dataObj['fileName'] = $('#fileName').val();
	  var newFileName = dataObj['fileName'];
	  dataObj['previousFileTitle'] = $('#previousFileTitle').val();
	  dataObj['fileTitle'] = $('#fileTitle').val();
	  dataObj['previousFileDescription'] = $('#previousFileDescription').val();
	  dataObj['fileDescription'] = $('#fileDescription').val();

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

			// log data to the console so we can see
			//console.log(data);
			// here we will handle errors and validation messages
			if ( ! data.success) {

        if (typeof data.errors !== 'undefined') {
  				// handle errors for fileId -------
  			  if (typeof data.errors.fileId !== 'undefined') {
  				/*$('#fileFunctionsBody').addClass( // add the error class to show red input
  				  'has-error'
  				);*/
  				$('#file-error').append( // add the actual error message under our input
  				  '<div class="alert alert-danger" role="alert">' + data.errors.fileId + '</div>'
  				);
  			  }
  			  // handle errors for previousFileFolder ------
  			  if (typeof data.errors.previousFileFolder !== 'undefined') {
  				/*$('#fileFilesystemList-group').addClass( // add the error class to show red input
  				  'has-error'
  				);*/
  				$('#folder-error').append( // add the actual error message under our input
  				  '<div class="alert alert-danger" role="alert">' + data.errors.previousFileFolder + '</div>'
  				);
  			  }
  			  // handle errors for fileFolder --------
  			  if (typeof data.errors.fileFolder !== 'undefined') {
  				/*$('#fileFilesystemList-group').addClass( // add the error class to show red input
  				  'has-error'
  				);*/
  				$('#folder-error').append( // add the actual error message under our input
  				  '<div class="alert alert-danger" role="alert">' + data.errors.fileFolder + '</div>'
  				);
  			  }
  			  // handle errors for fileName ----------
  			  if (typeof data.errors.fileName !== 'undefined') {
  				/*$('#fileFilesystemList-group').addClass( // add the error class to show red input
  				  'has-error'
  				);*/
  				$('#file-error').append( // add the actual error message under our input
  				  '<div class="alert alert-danger" role="alert">' + data.errors.fileName + '</div>'
  				);
  			  }
        }

        if (typeof data.warnings !== 'undefined') {
          // handle warnings for fileTitle -------------
          if (typeof data.warnings.fileTitle !== 'undefined') {
          /*$('#fileDatabaseList-group').addClass( // add the warning class to show amber input
            'has-warning'
          );*/
          $('#file_title-warning').append( // add the actual warning message under our input
            '<div class="alert alert-secondary" role="alert">' + data.warnings.fileTitle + '</div>'
          );
          }
          // handle errors for fileDescription --------
          if(typeof data.warnings.fileDescription !== 'undefined') {
          /*$('#fileDatabaseList-group').addClass( // add the warning class to show amber input
            'has-warning'
          );*/
          $('#file_description-warning').append( // add the actual warning message under our input
            '<div class="alert alert-secondary" role="alert">' + data.warnings.fileDescription + '</div>'
          );
          }
        }

      } else {

        if (typeof data.warnings !== 'undefined') {
          if (typeof data.warnings.nameEntries !== 'undefined') {
            /*$('#fileFilesystemList-group').addClass( // add the warning class to show amber input
              'has-warning'
            );*/
            $('#filesystem-error').append( // add the actual warning message under our input
              '<div class="alert alert-secondary" role="alert">' + data.warnings.nameEntries + '</div>'
            );
          }
          if(typeof data.warnings.database !== 'undefined') {
            /*$('#fileDatabaseList-group').addClass( // add the warning class to show amber input
              'has-warning'
            );*/
            $('#database-error').append( // add the actual warning message under out input
              '<div class="alert alert-secondary" role="alert">' + data.warnings.database + '</div>'
            );
          }
        }

				// ALL GOOD! just show the success message!
				$('#previousFileName').attr('value', newFileName);
				$('#previousFileFolder').attr('value', newFSFileFolder);
        //console.log(data);
        if (typeof data.message !== 'undefined') {
  				$('#fileFunctionsBody').append(
  				  '<div class="alert alert-success">' + data.message + '</div>'
  				);
        }

			}

		})

		// using the fail promise callback
		.fail(function(data) {
			// show any errors from php
			// best to remove this for production
      //console.log(data);
		});

	});

	// reset the form
	$('#fileFunctionsForm').bind('reset', function(event) {
	  //$('.list-group').removeClass('has-error'); // remove the error class from a prior submission
	  //$('.list-group').removeClass('has-warning');
	  //$('.help-block').remove(); // remove the error text from a prior submission

    // remove prior message classes
    $('.alert-danger').remove();
    $('.alert-secondary').remove(); // secondary alert messages for warnings
	  $('.alert-success').remove();

	  // event.preventDefault();
	});

});
