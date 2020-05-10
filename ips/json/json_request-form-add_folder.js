$(document).ready(function() {

	// process the form
	$('#addFolderForm').submit(function(event) {

		$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-success').remove();

		// get the form data
		// there are many ways to get this data using jQuery (you can use the class or id also)

		var formData = {
// not needed for add function			'id'			: $('input[name=add_id]').val(),
			'folder'		: $('input[name=add_folder]').val(),
			'path'			: $('input[name=path_value]').val(),
			'thumbnail'		: $('input[name=add_thumbnail]').prop('checked')
		};

		// process the form
		$.ajax({
			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'json/json-add_folder.php', //the url where we want to POST
			data		: formData, // our data object
			dataType	: 'json', // what type of data do we expect back from the server
			encode		: true
		})
			//using the done promise callback
			.done(function(data) {

				// log data to the console so we can see
				//console.log(data);

				// here we will handle errors and validation messages
				if ( ! data.success) {

					// handle errors for username -------
					if (data.errors.folder) {
						$('#add_folder-group').addClass('has-error'); // add the error class to show red input
						$('#add_folder-group').append('<div class="help-block">' + data.errors.folder + '</div>'); // add the actual error message under our input
					}

					if (data.errors.path) {
						$('#add_folder-group').addClass('has-error');
						$('#add_folder-group').append('<div class="help-block">' + data.errors.path + '</div>');
					}

				} else {

					// ALL GOOD! just show the success message!
					$('#addFolderBody').append('<div class="alert alert-success">' + data.message + '</div>');

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
