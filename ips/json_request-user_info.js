$(document).ready(function(){
	$("#usertable").on("click", ".userselector", function(){
		var changeValue=this.name;
		$.ajax({
			type:"post",
			url:"json-user_info.php",
			dataType: 'json',
			data:"id="+changeValue,
			success:function(data){
			  $.each(data, function(){
			    $.each(this, function(key, value){
			      if(key == "admin"){
			        if(value == "1"){
			          $("#changeUserForm_admin").prop("checked", true);
			        } else if(value == "0"){
			          $("#changeUserForm_admin").prop("checked", false);
			        }
			      } else if(key != "admin"){
			        $("#changeUserForm_"+key).val(value);
			      }
			    });
			  });
			}
		});
	});
	
	$("#usertable").on("click", ".removeselector", function(){
		var removeValue=this.name;
		$.ajax({
			type:"post",
			url:"json-user_info.php",
			dataType: 'json',
			data:"id="+removeValue,
			success:function(data){
			  $.each(data, function(){
			    $.each(this, function(key, value){
			      if(key == "id"){
			        $("#removeUserForm_id").val(value);
			      }
			    });
			  });
			}
		});
	});
});
