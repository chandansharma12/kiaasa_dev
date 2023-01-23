"use strict";

function editStoreStaff(id){
    $("#edit_store_staff_dialog").modal('show');
    $("#store_staff_id").val(id);
    $("#editStoreStaffSuccessMessage,#editStoreStaffErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/staff/list?action=get_store_staff_data",
        method:"GET",
        data:{id:id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoreStaffErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#name_edit").val(msg.store_staff_data.name);
                    $("#phone_no_edit").val(msg.store_staff_data.phone_no);
                    $("#address_edit").val(msg.store_staff_data.address);
                    $("#status_edit").val(msg.store_staff_data.status);
                }
            }else{
                displayResponseError(msg,'editStoreStaffErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editStoreStaffErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateStoreStaff(){
    var form_data = $("#editStoreStaffFrm").serialize();
    $("#store_staff_edit_spinner").show();
    $("#editStoreStaffSuccessMessage,#editStoreStaffErrorMessage,.invalid-feedback").html('').hide();
    $("#store_staff_edit_cancel,#store_staff_edit_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/staff/list?action=update_store_staff_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#store_staff_edit_cancel,#store_staff_edit_submit").attr('disabled',false);
            $("#store_staff_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editStoreStaffErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStoreStaffSuccessMessage").html(msg.message).show();
                    $("#editStoreStaffErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_store_staff_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'editUserErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editStoreStaffErrorMessage").html('Error in processing request').show();
            $("#store_staff_edit_spinner").hide();
            $("#store_staff_edit_cancel,#store_staff_edit_submit").attr('disabled',false);
        }
    });
}

function addStoreStaff(){
    $("#addStoreStaffSuccessMessage,#addStoreStaffErrorMessage,.invalid-feedback").html('').hide();
    $("#name_add,#phone_no_add,#address_add").val('');
    $("#add_store_staff_dialog").modal('show');
}

function submitAddStoreStaff(){
    var form_data = $("#addStoreStaffFrm").serialize();
    $("#store_staff_add_spinner").show();
    $("#addStoreStaffSuccessMessage,#addStoreStaffErrorMessage,.invalid-feedback").html('').hide();
    $("#store_staff_add_cancel,#store_staff_add_submit").attr("disabled",true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/store/staff/list?action=add_store_staff",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#store_staff_add_spinner").hide();
            $("#store_staff_add_cancel,#store_staff_add_submit").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addStoreStaffSuccessMessage").html(msg.message).show();
                    $("#addStoreStaffErrorMessage").html('').hide();
                    setTimeout(function(){  $("#add_store_staff_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'addStoreStaffErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addStoreStaffErrorMessage").html('Error in processing request').show();
            $("#store_staff_add_spinner").hide();
            $("#store_staff_add_cancel,#store_staff_add_submit").attr("disabled",false);
        }
    });
}

function editVendorStatus(){
    $("#usersListOverlay").show();
    var user_ids = '';
    $(".vendor-list").each(function(){
        if($(this).is(":checked")){
            user_ids+= $(this).val()+",";
        }
    });
    
    user_ids = user_ids.substring(0,user_ids.length-1);
    var form_data = "action="+$("#vendor_action").val()+"&ids="+user_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            //$("#user_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#usersListOverlay").hide(); 
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateVendorStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateVendorStatusSuccessMessage").html(msg.message).show();
                    $("#updateVendorStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#usersListOverlay").hide();  window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'updateVendorStatusErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateVendorStatusErrorMessage").html('Error in processing request').show();
            $("#usersListOverlay").hide(); 
        }
    });
}

function selectAllVendors(elem){
    if($(elem).is(":checked")){
        $(".vendor-list").each(function(){
            $(this).prop("checked", true);
        });
    }else{
        $(".vendor-list").each(function(){
            $(this).prop("checked", false);
        });
    }
}


