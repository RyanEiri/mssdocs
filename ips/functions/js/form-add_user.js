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
			'url'			: $('input[name=url]').val(),
			'name'			: $('input[name=name]').val(),
			'featuredSite'		: $('input[name=featuredSite]').prop('checked'),
			'shortDescription'	: $('input[name=shortDescription]').val(),
			'longDescription'	: $('textarea[name=longDescription]').val(),
			'primary_cat'		: $('select[name=primary_cat]').val(),
			'secondary_cat'		: $('select[name=secondary_cat]').val()
		};

		// process the form
		$.ajax({
			type		: 'POST', //define the type of HTTP verb we want to use (POST for our form)
			url		: 'add_parse.php', //the url where we want to POST
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

					// handle errors for url -------
					if (data.errors.url) {
						$('#url-group').addClass('has-error'); // add the error class to show red input
						$('#url-group').append('<div class="help-block">' + data.errors.url + '</div>'); // add the actual error message under our input
					}

					// handle errors for name ------
					if (data.errors.name) {
						$('#name-group').addClass('has-error'); // add the error class to show red input
						$('#name-group').append('<div class="help-block">' + data.errors.name + '</div>'); // add the actual error message under our input
					}

					// handle errors for shortDescription --
					if (data.errors.shortDescription) {
						$('#short-group').addClass('has-error'); // add the error class to show red input
						$('#short-group').append('<div class="help-block">' + data.errors.shortDescription + '</div>'); // add the actual error message under our input
					}

					// handle errors for longDescription ---
					if (data.errors.longDescription) {
						$('#long-group').addClass('has-error'); // add the error class to show red input
						$('#long-group').append('<div class="help-block">' + data.errors.longDescription + '</div>'); // add the actual error message under our input
					}

					// handle errors for primary_cat ------
					if (data.errors.primary_cat) {
						$('#primary-group').addClass('has-error'); // add the error class to show red input
						$('#primary-group').append('<div class="help-block">' + data.errors.primary_cat + '</div>'); // add the actual error message under our input
					}
				} else {

					// ALL GOOD! just show the success message!
					$('#add_entry').append('<div class="alert alert-success">' + data.message + '</div>');

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
