$(document).ready(function(){

// Move file button functionality
//
	$("#buttonbar").on("click", ".move-button", function(){
	
	$('.form-group').removeClass('has-error'); // remove the error class from a prior submission
	$('.help-block').remove(); // remove the error text from a prior submission
	$('.alert-success').remove();

	$.ajax({
	  type:"post",
	  url:"json-file_info.php",
	  dataType: 'json',
	  success:function(data){
  	  obj = [data];

// Collect the selected files and display them for confirmation
	  var moveFileValues = [];
	  $('.fileList').remove();
	  var moveFileValues = $('input:checkbox:checked.fileCheckbox').map(function() {
	    return $(this).val()
	  }).get() 
	  if(moveFileValues.length > 0) {
	    $.each(moveFileValues, function(i, val){
		var dirname = val.match(/(.*)[\/\\]/)[1]||'';
		var filename = val.replace(/^.*[\\\/]/, '');
		$('#fileList-group').append('<li class="list-group-item fileList"><label for="file_'+i+'">File '+i+':</label><input type="hidden" class="fileList file form-control" name="file_'+i+'" value="'+val+'" />&nbsp;'+filename+'</li>');
	    });
	  }
	  else {
	    $('#fileList-group').append('<li class="list-group-item fileList"><div>No File Selected</div></li>');
	  }

// Collect the selected folders and display them for confirmation
	  var moveFolderValues = [];
	  $('.folderList').remove();
	  var moveFolderValues = $('input:checkbox:checked.folderCheckbox').map(function() {
	    return $(this).val()
	  }).get()
	  if(moveFolderValues.length > 0) {
	    $.each(moveFolderValues, function(i, val){
		var dirname = val;
		$('#folderList-group').append('<li class="list-group-item folderList"><label for="folder_'+i+'">Folder '+i+':</label><input type="hidden" class="folderList folder form-control" name="folder_'+i+'" value="'+val+'" />&nbsp;'+dirname+'</li>');
	    });
	  }
	  else {
	    $('#folderList-group').append('<li class="list-group-item folderList"><div>No Folder Selected</div></li>');
	  }

// Collect the folders available to be moved to and place them in select box
	  var moveToFolders = [];
//	  console.log(obj);
	  collectFolders(obj);
	  function collectFolders(object) {
	    object.forEach(function(d){
		if(d.type === 'folder') {
		  if(d.name != 'thumbnail'){
		    moveToFolders.push(d.path);
		    collectFolders(d.items);
		  }
		}
	    });
	  }
	  $('#folderListSelect').find('option').remove();
	  $.each(moveToFolders, function(i, val){
	    $('select#folderListSelect').append('<option value="'+val+'">'+val+'</option>');
	  }); // End of folder collection
		
	  } /* End of success handling */
	}); /* End of move file ajax handling */
	}); /* End of move file button handling */
	
// Remove button functionality
//
        $("#buttonbar").on("click", ".remove-button", function(){

        $('.form-group').removeClass('has-error'); // remove the error class from a prior submission
        $('.help-block').remove(); // remove the error text from a prior submission
        $('.alert-success').remove();

        $.ajax({
          type:"post",
          url:"json-file_info.php",
          dataType: 'json',
          success:function(data){
          obj = [data];

// Collect the selected files and display them for confirmation
          var removeValues = [];
          $('.removeFileList').remove();
          var removeValues = $('input:checkbox:checked.fileCheckbox').map(function() {
            return $(this).val()
          }).get()
          if(removeValues.length > 0) {
            $.each(removeValues, function(i, val){
                var dirname = val.match(/(.*)[\/\\]/)[1]||'';
                var filename = val.replace(/^.*[\\\/]/, '');
                $('#removeFileList-group').append('<li class="list-group-item removeFileList"><label for="file_'+i+'">File '+i+':</label><input type="hidden" class="removeFileList file form-control" name="file_'+i+'" value="'+val+'" />&nbsp;'+filename+'</li>');
            });
          }
          else {
            $('#removeFileList-group').append('<li class="list-group-item removeFileList"><div>No File Selected!</div></li>');
          }

// Collect the selected folders and display them for confirmation
	  var removeFolderValues = [];
	  $('.folderList').remove();
	  var removeFolderValues = $('input:checkbox:checked.folderCheckbox').map(function() {
	    return $(this).val()
	  }).get()
	  if(removeFolderValues.length > 0) {
	    $.each(removeFolderValues, function(i, val){
		var dirname = val;
		$('#removeFolderList-group').append('<li class="list-group-item removeFolderList"><label for="removeFolder_'+i+'">Folder '+i+':</label><input type="hidden" class="removeFolderList folder form-control" name="removeFolder_'+i+'" value="'+val+'" />&nbsp;'+dirname+'</li>');
	    });
	  }
	  else {
	    $('#removeFolderList-group').append('<li class="list-group-item removeFolderList"><div>No Folder Selected</div></li>');
	  } 

          } /* End of success handling */
        }); /* End of remove file ajax handling */
        }); /* End of remove file button handling */

// Add folder button functionality
	$("#buttonbar").on("click", ".addfolder-button", function(){
	  $('#addFolder').on('shown.bs.modal', function() {
	    $('#addFolderForm_name').focus();
	  });
	}); /* End of add folder button handling */

});
