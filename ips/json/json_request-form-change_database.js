$(document).ready(function() {

	// process the form
	$('#makeChangesForm').submit(function(event) {

		//$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		//$('.help-block').remove(); // remove the error text from a prior submission

		$('.alert-danger').remove();
		$('.alert-warning').remove();
		$('.alert-success').remove();

		// get the form data
		// there are many ways to get this data using jQuery (you can use the class or id also)

		var formData = {
			// not needed for add function			'id'			: $('input[name=add_id]').val(),
			// passing file_url[] with SESSION in php
			//'fileURL'		: $('input[name=change_file_url]').val(),
			'change_urls'			: $('input[name=change_urls]').prop('checked')
		};

		// process the form
		$.ajax({
			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'json/json-change_database.php', //the url where we want to POST
			data		: formData, // our data object
			dataType	: 'json', // what type of data do we expect back from the server
			encode		: true
		})
			//using the done promise callback
			.done(function(data) {

				// log data to the console so we can see
				console.log(data);

				// here we will handle errors and validation messages
				if ( ! data.success) {

					// handle file_urls errors --
					if (data.errors.file_urls) {
						$('#change-error').append('<div class="alert alert-danger" role="alert">' + data.errors.file_urls + '</div>');
					}
					// handle change_urls warnings --
					if (data.warnings.change_urls) {
						$('#change-error').append('<div class="alert alert-warning" role="alert">' + data.warnings.change_urls + '</div>');
					}
					// handle database errors --
					if (data.errors.database) {
						//$('#add_errors-group').addClass('has-error'); // add the error class
						$('#change_database-error').append('<div class="alert alert-danger" role="alert">' + data.errors.database + '</div>'); // add the actual error message
					}

				} else {

					// ALL GOOD! just show the success message!
					$('#ChangeDB').append('<div class="alert alert-success" role="alert">' + data.message + '</div>');

				}
			})

			// using the fail promise callback
			.fail(function(data) {

				// show any errors from php
				// best to remove this for production
				console.log(data);
			});

		// stop the form from submitting the normal way and refreshing the page
		event.preventDefault();
	});
});
