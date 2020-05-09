// form.js
$(document).ready(function() {

	// process the form
	$('form').submit(function(event) {

		$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
		$('.help-block').remove(); // remove the error text from a prior submission
		$('.alert-success').remove();

		// get the form data
		// there are many ways to get this data using jQuery (you can use the class or id also)

		var formData = {
			'id'			: $('input[name=id]').val(),
			'username'		: $('input[name=username]').val(),
			'password'		: $('input[name=password]').val(),
			'secretword'		: $('input[name=secretword]').val(),
			'admin'			: $('input[name=admin]').prop('checked')
		};

		// process the form
		$.ajax({
			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'changeUser_parse.php', //the url where we want to POST
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
						$('#user-group').addClass('has-error'); // add the error class to show red input
						$('#user-group').append('<div class="help-block">' + data.errors.username + '</div>'); // add the actual error message under our input
					}

					// handle errors for password ------
					if (data.errors.password) {
						$('#pass-group').addClass('has-error'); // add the error class to show red input
						$('#pass-group').append('<div class="help-block">' + data.errors.password + '</div>'); // add the actual error message under our input
					}

					// handle errors for secretword --
					if (data.errors.secretword) {
						$('#secret-group').addClass('has-error'); // add the error class to show red input
						$('#secret-group').append('<div class="help-block">' + data.errors.secretword + '</div>'); // add the actual error message under our input
					}

				} else {

					// ALL GOOD! just show the success message!
					$('#UserForm').append('<div class="alert alert-success">' + data.message + '</div>');

					// usually after form submission, you'll want to redirect
					// window.location = '/thank-you'; //redirect a user to another page
					$("<div>Success!</div>").dialog();
//					alert('success'); // for now we'll just alert the user

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
