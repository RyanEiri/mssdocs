$(document).ready(function() {

	// process the form
	$('#removeUserForm').submit(function(event) {

		$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-success').remove();

		// get the form data
		// there are many ways to get this data using jQuery (you can use the class or id also)

		var formData = {
			'id'			: $('input[name=remove_id]').val(),
		};

		// process the form
		$.ajax({
			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'json/json-remove_user.php', //the url where we want to POST
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

					// handle errors for id -------
					if (data.errors.id) {
						$('#remove_id-group').addClass('has-error'); // add the error class to show red input
						$('#remove_id-group').append('<div class="help-block">' + data.errors.id + '</div>'); // add the actual error message under our input
					}

				} else {

					// ALL GOOD! just show the success message!
					$('#UserRemove').append('<div class="alert alert-success">' + data.message + '</div>');

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
