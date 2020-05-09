$(document).ready(function() {

	// process the form 
	$('#moveFileFolderForm').submit(function(event) {

		$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-success').remove();

		// get the form data
		// there are many ways to get this data using jQuery (you can use the class or id also)
		var dataObj = {};
		var moveFile = [];
		var moveFile = $('input:checkbox:checked.fileCheckbox').map(function() {
		  return $(this).val()
		}).get()
		dataObj['files'] = {};
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
		dataObj['folders'] = {};
		$.each(moveFolder, function(i, val){
		  dataObj['folders'][i] = val;
		});	

		dataObj['moveToFolder'] = $('#folderListSelect').val();
		
		// process the form
		$.ajax({
//			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'json-move_file.php', //the url where we want to POST
			method		: 'post', 
			data		: JSON.stringify(dataObj), // our data object
//			dataType	: 'json', // what type of data do we expect back from the server
			contentType	: 'application/json'
//			encode		: true
		})
			//using the done promise callback
			.done(function(data) {

				// log data to the console so we can see
				//console.log(data);

				// here we will handle errors and validation messages
				if ( ! data.success) {

					// handle errors for selected files and folders -------
					if (data.errors.files) {
						$('#moveFileFolderBody').addClass('has-error'); // add the error class to show red input
						$('#moveFileFolderBody').append('<div class="help-block">' + data.errors.files + '</div>'); // add the actual error message under our input
					}

					// handle errors for move to folder ------
					if (data.errors.moveToFolder) {
						$('#moveFileFolderBody').addClass('has-error'); // add the error class to show red input
						$('#moveFileFolderBody').append('<div class="help-block">' + data.errors.moveToFolder + '</div>'); // add the actual error message under our input
					}

				} else {

					// ALL GOOD! just show the success message!
					$('#moveFileFolderBody').append('<div class="alert alert-success">' + data.message + '</div>');


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
});
