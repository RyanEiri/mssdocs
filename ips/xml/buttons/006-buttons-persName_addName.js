$(document).ready(function() {
"use strict";

// process the submission 
	$('#persName_addNameTableCreate').on('click', function(event) {
		event.preventDefault();
		
		$('.alert-danger').remove(); // remove prior error alert
		$('.alert-warning').remove(); // remove prior warning alert
	  $('.alert-success').remove(); // remove prior success alert
		
		var dataObj = {};
		dataObj.sex = 'create';
		
		// Send the call to process the session
		// variables.
		$.ajax({
			type	: 'POST', 
			url	: 'mysql/006-table-persName_addName-create.php', 
			data	: dataObj, 
			dataType : 'json', 
			encode	: true
		})
		
		//using the done promise callback
		.done(function(data) {
			if ( ! data.success) {
				// show any errors from php
				// best to remove this for production
				console.log(data);
				
				// handle errors for sexTableCreate -------
			  if (data.errors.sex) {
				$('.persName_addName_manage').prepend( // add the error message
				  '<div class="alert alert-danger" role="alert">' + data.errors.sex + '</div>'
				); 
			  }
				
			  // handle warning for sexTableCreate ------
			  if(data.warnings.sex) {
				$('.persName_addName_manage').prepend( // add the warning message
				  '<div class="alert alert-warning" role="alert">' + data.warnings.sex + '</div>'
				);
			  }
				
			} else {
				// show any errors from php
				// best to remove this for production
				console.log(data);
				
			  // handle warning for sexTableCreate ------
			  if(data.warnings.sex) {
				$('.persName_addName_manage').prepend( // add the warning message
				  '<div class="alert alert-warning" role="alert">' + data.warnings.sex + '</div>'
				);
			  }
				
				// Success! Show the success message!
				$('.persName_addName_manage').prepend(
				  '<div class="alert alert-success" role="alert">' + data.message + '</div>'
				);
				
			}			
		})
		
			// using the fail promise callback
			.fail(function(data) {
				// show any errors from php
				// best to remove this for production
				console.log(data);
			});
		
	});

	$('#persName_addNameTableDrop').on('click', function(event) {
		event.preventDefault();
		
		$('.alert-danger').remove(); // remove prior error alert
		$('.alert-warning').remove(); // remove prior warning alert
	  $('.alert-success').remove(); // remove prior success alert
		
		var dataObj = {};
		dataObj.sex = 'drop';
		
		// Send the call to process the session
		// variables.
		$.ajax({
			type	: 'POST', 
			url	: 'mysql/006-table-persName_addName-drop.php', 
			data	: dataObj, 
			dataType : 'json', 
			encode	: true
		})
		
		//using the done promise callback
		.done(function(data) {
			if ( ! data.success) {
				// show any errors from php
				// best to remove this for production
				console.log(data);
				
				// handle errors for sexTableCreate -------
			  if (data.errors.sex) {
				$('.persName_addName_manage').prepend( // add the error message
				  '<div class="alert alert-danger" role="alert">' + data.errors.sex + '</div>'
				); 
			  }
				
			  // handle warning for sexTableCreate ------
			  if(data.warnings.sex) {
				$('.persName_addName_manage').prepend( // add the warning message
				  '<div class="alert alert-warning" role="alert">' + data.warnings.sex + '</div>'
				);
			  }
				
			} else {
				// show any errors from php
				// best to remove this for production
				console.log(data);
				
			  // handle warning for sexTableCreate ------
			  if(data.warnings.sex) {
				$('.persName_addName_manage').prepend( // add the warning message
				  '<div class="alert alert-warning" role="alert">' + data.warnings.sex + '</div>'
				);
			  }
				
				// Success! Show the success message!
				$('.persName_addName_manage').prepend(
				  '<div class="alert alert-success" role="alert">' + data.message + '</div>'
				);
				
			}			
		})
		
			// using the fail promise callback
			.fail(function(data) {
				// show any errors from php
				// best to remove this for production
				console.log(data);
			});
		
	});
	
	$('#persName_addNameTableInsert').on('click', function(event) {
		event.preventDefault();
		
		$('.alert-danger').remove(); // remove prior error alert
		$('.alert-warning').remove(); // remove prior warning alert
	  $('.alert-success').remove(); // remove prior success alert
		
		var dataObj = {};
		dataObj.sex = 'insert';
		
		// Send the call to process the session
		// variables.
		$.ajax({
			type	: 'POST', 
			url	: 'mysql/006-table-persName_addName-insert.php', 
			data	: dataObj, 
			dataType : 'json', 
			encode	: true
		})
		
		//using the done promise callback
		.done(function(data) {
			if ( ! data.success) {
				// show any errors from php
				// best to remove this for production
				console.log(data);
				
				// handle errors for sexTableCreate -------
			  if (data.errors.sex) {
				$('.persName_addName_manage').prepend( // add the error message
				  '<div class="alert alert-danger" role="alert">' + data.errors.sex + '</div>'
				); 
			  }
				
			  // handle warning for sexTableCreate ------
			  if(data.warnings.sex) {
				$('.persName_addName_manage').prepend( // add the warning message
				  '<div class="alert alert-warning" role="alert">' + data.warnings.sex + '</div>'
				);
			  }
				
			} else {
				// show any errors from php
				// best to remove this for production
				console.log(data);
				
			  // handle warning for sexTableCreate ------
			  if(data.warnings.sex) {
				$('.persName_addName_manage').prepend( // add the warning message
				  '<div class="alert alert-warning" role="alert">' + data.warnings.sex + '</div>'
				);
			  }
				
				// Success! Show the success message!
				$('.persName_addName_manage').prepend(
				  '<div class="alert alert-success" role="alert">' + data.message + '</div>'
				);
				
			}			
		})
		
			// using the fail promise callback
			.fail(function(data) {
				// show any errors from php
				// best to remove this for production
				console.log(data);
			});
		
	});

});