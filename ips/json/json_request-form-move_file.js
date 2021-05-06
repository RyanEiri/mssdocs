$(document).ready(function() {

	// process the form
	$('#moveFileFolderForm').submit(function(event) {

		// stop the form from submitting the normal way and refreshing the page
		event.preventDefault();

		//$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		//$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-danger').remove();
		$('.alert-success').remove();

		// get the form data
		var dataObj = {};
		var moveFile = [];
		var moveFile = $('input:checkbox:checked.fileCheckbox').map(function() {
		  return $(this).val()
		}).get()
		dataObj['files'] = [];
		$.each(moveFile, function(i, val){
		  var dirname = val.match(/(.*)[\/\\]/)[1]||'';
                  var filename = val.replace(/^.*[\\\/]/, '');
                  dataObj['files'][i] = {};
                  dataObj['files'][i]['filename'] = filename;
                  dataObj['files'][i]['dirname'] = dirname;
		});

		var moveFolder = [];
		var moveFolder = $('input:checkbox:checked.folderCheckbox').map(function() {
		  return $(this).val()
		}).get()
		dataObj['folders'] = [];
		$.each(moveFolder, function(i, val){
		  dataObj['folders'][i] = val;
		});
/*
		$.ajax({
			type:"post",
			url:"json/json-file_info.php",
			data: {
				"recursive":	"1"
			},
			dataType: 'json',
			success:function(data){

			}
		});
*/
		fsDirName = $('#folderListSelect').val();
		dataObj['moveToFolder'] = fsDirName;

		// Set the newly selected folder based on the
		// selected fs folder. This requires some
		// regular expression trickery in order to
		// satisfy the database requirements.
		// The main concern being that the leading path
		// until after the master 'files' folder needs
		// to be removed for the database. The 'files'
		// folder should be set as a program option in
		// the future.
		    let reg = /(.*)(files)(.*)/i;
		    let selFSDirName = fsDirName.match(reg);
		    let serverPath = selFSDirName[1];
		    let masterDir = selFSDirName[2];
		    let dbPath = selFSDirName[3];
		    let dbDirName = masterDir + dbPath;
				dataObj['dbMoveToFolder'] = dbDirName;

		// process the form
		$.ajax({
			url		: 'json/json-move_file.php', //the url where we want to POST
			method		: 'post',
			data		: JSON.stringify(dataObj), // our data object
			contentType	: 'application/json'
		})
			//using the done promise callback
			.done(function(data) {

				// log data to the console so we can see
				//console.log(data);

				// here we will handle errors and validation messages
				if ( ! data.success) {
					console.log(data);
					// handle errors for selected files and folders -------
					if (data.errors.files) {
						//$('#moveFileFolderBody').addClass('has-error'); // add the error class to show red input
						$('#moveFileFolderBody').append(
							'<div class="alert alert-danger" role="alert">' + data.errors.files + '</div>'
						); // add the actual error message under our input
					}

					// handle errors for move to folder ------
					if (data.errors.moveToFolder) {
						//$('#moveFileFolderBody').addClass('has-error'); // add the error class to show red input
						$('#moveFileFolderBody').append(
							'<div class="alert alert-danger" role="alert">' + data.errors.moveToFolder + '</div>'
						); // add the actual error message under our input
					}

				} else {
					//console.log(data);
					// ALL GOOD! just show the success message!
					$('#moveFileFolderBody').append('<div class="alert alert-success">' + data.message + '</div>');


				}
			})

			// using the fail promise callback
			.fail(function(data) {

				// show any errors from php
				// best to remove this for production
				//console.log(data);
			});

	});
});
