"use strict";

function editPermission(id){
    $("#edit_permission_dialog").modal('show');
    $("#permission_edit_id").val(id);
    $("#editPermissionErrorMessage,#editPermissionSuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/permission/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editPermissionErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#routePath_edit").val(msg.permission_data.route_path);
                    $("#routeKey_edit").val(msg.permission_data.route_key);
                    $("#description_edit").val(msg.permission_data.description);
                    $("#permission_type_edit").val(msg.permission_data.permission_type);
                }
            }else{
                displayResponseError(msg,"editPermissionErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPermissionErrorMessage").html('Error in processing request').show();
        }
    });
}

function updatePermission(){
    var form_data = $("#editPermissionFrm").serialize();
    $("#permission_edit_spinner").show();
    $("#editPermissionErrorMessage,#editPermissionSuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/permission/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#permission_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editPermissionErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editPermissionSuccessMessage").html(msg.message).show();
                    $("#editPermissionErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_permission_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editPermissionErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPermissionErrorMessage").html('Error in processing request').show();
            $("#permission_edit_spinner").hide();
        }
    });
}

function addPermission(){
    $("#addPermissionSuccessMessage,#addPermissionErrorMessage,.invalid-feedback").html('').hide();
    $("#routePath_add,#routeKey_add,#permission_add").val('');
    $("#add_permission_dialog").modal('show');
}

function submitAddPermission(){
    var form_data = $("#addPermissionFrm").serialize();
    $("#permission_add_spinner").show();
    $("#addPermissionSuccessMessage,#addPermissionErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/permission/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#permission_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addPermissionErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addPermissionSuccessMessage").html(msg.message).show();
                    $("#addPermissionErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_permission_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"addPermissionErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addPermissionErrorMessage").html('Error in processing request').show();
            $("#permission_add_spinner").hide();
        }
    });
}

function updatePermissionStatus(){
    $("#permissionListOverlay").show();
    var user_ids = '';
    $(".permission-list-chk").each(function(){
        if($(this).is(":checked")){
            user_ids+= $(this).val()+",";
        }
    });
    
    user_ids = user_ids.substring(0,user_ids.length-1);
    var form_data = "action="+$("#permission_action").val()+"&ids="+user_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/permission/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#permissionListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updatePermissionStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updatePermissionStatusSuccessMessage").html(msg.message).show();
                    $("#updatePermissionStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#permissionListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updatePermissionStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updatePermissionStatusErrorMessage").html('Error in processing request').show();
            $("#permissionListOverlay").hide();
        }
    });
}

function submitRoleForm(){
    var role_id = $("#role_id").val();
    var url = ROOT_PATH+"/permission/role-permissions/"+role_id;
    $("#role_form").attr('action',url).submit();
}

function updateRolePermissions(){
    $("#permissionListOverlay").show();
    var permission_ids = '',permission_page_ids = '';
    $(".permission-list-chk").each(function(){
        if($(this).is(":checked")){
            permission_ids+=$(this).val()+",";
        }
        permission_page_ids+=$(this).val()+",";
    });
    
    permission_ids = permission_ids.substring(0,permission_ids.length-1);
    permission_page_ids = permission_page_ids.substring(0,permission_page_ids.length-1);
    
    var role_id = $("#role_id").val();
    var form_data = 'permission_ids='+permission_ids+"&role_id="+role_id+"&permission_page_ids="+permission_page_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/permission/updaterolepermissions/"+role_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#permissionListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateRolePermissionStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateRolePermissionStatusSuccessMessage").html(msg.message).show();
                    $("#updateRolePermissionStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#permissionListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateRolePermissionStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateRolePermissionStatusErrorMessage").html('Error in processing request').show();
            $("#permissionListOverlay").hide();
        }
    });
}

function submitUserForm(){
    var user_id = $("#user_id").val();
    var url = ROOT_PATH+"/permission/user-permissions/"+user_id;
    $("#user_form").attr('action',url).submit();
}

function updateUserPermissions(){
    $("#permissionListOverlay").show();
    var permission_ids = '',permission_page_ids = '';
    $(".permission-list-chk").each(function(){
        if($(this).is(":checked")){
            permission_ids+=$(this).val()+",";
        }
        permission_page_ids+=$(this).val()+",";
    });
    
    permission_ids = permission_ids.substring(0,permission_ids.length-1);
    permission_page_ids = permission_page_ids.substring(0,permission_page_ids.length-1);
    
    var user_id = $("#user_id").val();
    var form_data = 'permission_ids='+permission_ids+"&user_id="+user_id+"&permission_page_ids="+permission_page_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/permission/updateuserpermissions/"+user_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#permissionListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateRolePermissionStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateUserPermissionStatusSuccessMessage").html(msg.message).show();
                    $("#updateUserPermissionStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#permissionListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateUserPermissionStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateUserPermissionStatusErrorMessage").html('Error in processing request').show();
            $("#permissionListOverlay").hide();
        }
    });
}