$(document).ready(function() {

	// process the form
	$('#addUserForm').submit(function(event) {

		//$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		//$('.help-block').remove(); // remove the error text from a prior submission

		$('.alert-danger').remove();
		$('.alert-success').remove();

		// get the form data
		// there are many ways to get this data using jQuery (you can use the class or id also)

		var formData = {
// not needed for add function			'id'			: $('input[name=add_id]').val(),
			'username'		: $('input[name=add_username]').val(),
			'email'				: $('input[name=add_email]').val(),
			'password'		: $('input[name=add_password]').val(),
			'cpassword'		: $('input[name=add_cpassword]').val(),
			'admin'			: $('input[name=add_admin]').prop('checked')
		};

		// process the form
		$.ajax({
			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'json/json-add_user.php', //the url where we want to POST
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

					// handle errors for username -------
					if (data.errors.username) {
						//$('#add_user-group').addClass('has-error'); // add the error class to show red input
						$('#add_user-error').append('<div class="alert alert-danger" role="alert">' + data.errors.username + '</div>'); // add the actual error message under our input
					}
					if (data.errors.duplicate) {
						//$('#add_user-group').addClass('has-error'); // add the error class
						$('#add_user-error').append('<div class="alert alert-danger" role="alert">' + data.errors.duplicate + '</div>'); // add the actual error message
					}

					// handle errors for email ---------
					if (data.errors.email) {
						//$('#change_user-group').addClass('has-error'); // add the error class
						$('#add_email-error').append('<div class="alert alert-danger" role="alert">' + data.errors.email + '</div>'); // add the actual error message
					}

					// handle errors for password ------
					if (data.errors.password) {
						//$('#add_pass-group').addClass('has-error'); // add the error class to show red input
						$('#add_pass-error').append('<div class="alert alert-danger" role="alert">' + data.errors.password + '</div>'); // add the actual error message under our input
					}

					// handle errors for cpassword --
					if (data.errors.cpassword) {
						//$('#add_cpass-group').addClass('has-error'); // add the error class to show red input
						$('#add_cpass-error').append('<div class="alert alert-danger" role="alert">' + data.errors.cpassword + '</div>'); // add the actual error message under our input
					}

					// handle database errors --
					if (data.errors.database) {
						//$('#add_errors-group').addClass('has-error'); // add the error class
						$('#add_database-error').append('<div class="alert alert-danger" role="alert">' + data.errors.database + '</div>'); // add the actual error message
					}

				} else {

					// ALL GOOD! just show the success message!
					$('#UserAdd').append('<div class="alert alert-success" role="alert">' + data.message + '</div>');

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
