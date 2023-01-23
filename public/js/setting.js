"use strict";

function editSetting(id){
    $("#edit_setting_dialog").modal('show');
    $("#setting_edit_id").val(id);
    $("#editSettingErrorMessage,#editSettingSuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/setting/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editSettingErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#setting_name_edit").html(msg.setting_data.setting_key);
                    $("#setting_value_edit").val(msg.setting_data.setting_value);
                }
            }else{
                displayResponseError(msg,"editSettingErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editSettingErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateSetting(){
    var form_data = $("#editSettingFrm").serialize();
    $("#setting_edit_spinner").show();
    $("#editSettingErrorMessage,#editSettingSuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/setting/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#setting_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_edit_');
                    if(errors != ''){
                        $("#editSettingErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editSettingSuccessMessage").html(msg.message).show();
                    $("#editSettingErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_setting_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"editSettingErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editSettingErrorMessage").html('Error in processing request').show();
            $("#setting_edit_spinner").hide();
        }
    });
}

function addSetting(){
}

function submitAddSetting(){
}

function updateSettingStatus(){
    $("#settingListOverlay").show();
    var setting_ids = '';
    $(".setting-list-chk").each(function(){
        if($(this).is(":checked")){
            setting_ids+= $(this).val()+",";
        }
    });
    
    setting_ids = setting_ids.substring(0,user_ids.length-1);
    var form_data = "action="+$("#setting_action").val()+"&ids="+setting_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/setting/settingupdatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#settingListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateSettingStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateSettingStatusSuccessMessage").html(msg.message).show();
                    $("#updateSettingStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#settingListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateSettingStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateSettingStatusErrorMessage").html('Error in processing request').show();
            $("#settingListOverlay").hide();
        }
    });
}

function addHsnGst(){
    $("#add_hsn_gst_dialog .form-control").val('');
    $("#add_hsn_gst_dialog").modal("show");
}

function submitAddHsnGst(){
    $("#addHsnGstSuccessMessage,#addHsnGstErrorMessage,.invalid-feedback").html('').hide();
    $("#hsn_gst_add_cancel,#hsn_gst_add_submit").attr("disabled",true);
    var form_data = $("#addHsnGstFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/hsn/gst/list?action=add_hsn_gst_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#hsn_gst_add_cancel,#hsn_gst_add_submit").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addHsnGstErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addHsnGstSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#add_hsn_gst_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addHsnGstErrorMessage");
            }
        },error:function(obj,status,error){
            $("#hsn_gst_add_cancel,#hsn_gst_add_submit").attr("disabled",false);
            $("#addHsnGstErrorMessage").html('Error in processing request').show();
        }
    });
}

function editHsnGst(id){
    $("#hsn_gst_id").val(id);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/hsn/gst/list?action=get_hsn_gst_data",
        method:"GET",
        data:{id:id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#hsnGstErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var gst_data = msg.gst_data;
                    $("#hsn_code_edit").val(gst_data.hsn_code);
                    $("#min_amount_edit").val(gst_data.min_amount);
                    $("#rate_percent_edit").val(gst_data.rate_percent);
                    
                    if(msg.maximum_amount == gst_data.max_amount){
                        $("#max_amount_edit").val('').attr("readonly",true);
                        $("#max_amount_chk_edit").prop("checked",true);
                    }else{
                        $("#max_amount_edit").val(gst_data.max_amount).attr("readonly",false);
                        $("#max_amount_chk_edit").prop("checked",false);
                    }
                    
                    $("#edit_hsn_gst_dialog").modal("show");
                }
            }else{
                displayResponseError(msg,"hsnGstErrorMessage");
            }
        },error:function(obj,status,error){
            $("#hsnGstErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateHsnGst(){
    $("#editHsnGstSuccessMessage,#editHsnGstErrorMessage,.invalid-feedback").html('').hide();
    $("#hsn_gst_edit_cancel,#hsn_gst_edit_submit").attr("disabled",true);
    var form_data = $("#editHsnGstFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/hsn/gst/list?action=update_hsn_gst_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#hsn_gst_edit_cancel,#hsn_gst_edit_submit").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editHsnGstErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editHsnGstSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#edit_hsn_gst_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editHsnGstErrorMessage");
            }
        },error:function(obj,status,error){
            $("#hsn_gst_edit_cancel,#hsn_gst_edit_submit").attr("disabled",false);
            $("#editHsnGstErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteHsnGst(id){
    $('#delete_hsn_gst_dialog').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#hsn_gst_delete_submit', function(e) {
        e.preventDefault();
        $("#hsn_gst_delete_submit,#hsn_gst_delete_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id},
            url:ROOT_PATH+"/hsn/gst/list?action=delete_hsn_gst_data",
            success: function(msg){	
                $("#hsn_gst_delete_submit,#hsn_gst_delete_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteHsnGstErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteHsnGstSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  $("#delete_hsn_gst_dialog").modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"deleteHsnGstErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteHsnGstErrorMessage").html('Error in processing request').show();
                $("#hsn_gst_delete_submit,#hsn_gst_delete_cancel").attr('disabled',false);
            }
        });
    });
}

function editCategoryHsnCode(id){
    $("#category_id").val(id);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/category/hsn/list?action=get_category_hsn_data",
        method:"GET",
        data:{id:id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#categoryHsnErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var hsn_data = msg.hsn_data;
                    $("#category_name").val(hsn_data.name);
                    $("#hsn_code_edit").val(hsn_data.hsn_code);
                    $("#edit_category_hsn_dialog").modal("show");
                }
            }else{
                displayResponseError(msg,"categoryHsnErrorMessage");
            }
        },error:function(obj,status,error){
            $("#categoryHsnErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateCategoryHsnCode(){
    $("#editCategoryHsnSuccessMessage,#editCategoryHsnErrorMessage,.invalid-feedback").html('').hide();
    $("#category_hsn_edit_cancel,#category_hsn_edit_submit").attr("disabled",true);
    var form_data = $("#editCategoryHsnFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/category/hsn/list?action=update_category_hsn_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#category_hsn_edit_cancel,#category_hsn_edit_submit").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editCategoryHsnErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editCategoryHsnSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#edit_category_hsn_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editCategoryHsnErrorMessage");
            }
        },error:function(obj,status,error){
            $("#category_hsn_edit_cancel,#category_hsn_edit_submit").attr("disabled",false);
            $("#editCategoryHsnErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateGstMaxAmount(elem,max_elem){
    if($(elem).is(":checked")){
        $("#"+max_elem).val('').attr("readonly",true);
    }else{
        $("#"+max_elem).val('').attr("readonly",false);
    }
}

function updateStoreMasterExpense(){
    $("#updateStoreMasterExpenseSuccessMessage,#updateStoreMasterExpenseErrorMessage,.invalid-feedback").html('').hide();
    $("#updateStoreMasterExpenseCancel,#updateStoreMasterExpenseBtn").attr("disabled",true);
    var store_id = $("#store_id").val();
    var form_data = $("#updateStoreMasterExpenseFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/expense/master/edit/"+store_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#updateStoreMasterExpenseCancel,#updateStoreMasterExpenseBtn").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateStoreMasterExpenseErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateStoreMasterExpenseSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ var url = ROOT_PATH+"/store/expense/master/list/"+store_id;window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"updateStoreMasterExpenseErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateStoreMasterExpenseCancel,#updateStoreMasterExpenseBtn").attr("disabled",false);
            $("#updateStoreMasterExpenseErrorMessage").html('Error in processing request').show();
        }
    });
}

function cancelUpdateStoreMasterExpense(){
    var store_id = $("#store_id").val();
    var url = ROOT_PATH+"/store/expense/master/list/"+store_id;
    window.location.href = url;
}

function updateStoreMonthlyExpense(){
    $("#updateStoreMonthlyExpenseSuccessMessage,#updateStoreMonthlyExpenseErrorMessage,.invalid-feedback").html('').hide();
    $("#updateStoreMonthlyExpenseCancel,#updateStoreMonthlyExpenseBtn").attr("disabled",true);
    var store_id = $("#store_id").val();
    var expense_date = $("#expense_date").val();
    var form_data = $("#updateStoreMonthlyExpenseFrm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/expense/monthly/edit/"+store_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#updateStoreMonthlyExpenseCancel,#updateStoreMonthlyExpenseBtn").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateStoreMonthlyExpenseErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateStoreMonthlyExpenseSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ var url = ROOT_PATH+"/store/expense/monthly/list/"+store_id+"?expense_date="+expense_date;window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"updateStoreMonthlyExpenseErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateStoreMonthlyExpenseCancel,#updateStoreMonthlyExpenseBtn").attr("disabled",false);
            $("#updateStoreMonthlyExpenseErrorMessage").html('Error in processing request').show();
        }
    });
}

function cancelUpdateStoreMonthlyExpense(){
    var store_id = $("#store_id").val();
    var url = ROOT_PATH+"/store/expense/monthly/list/"+store_id;
    window.location.href = url;
}

function searchStoreMonthlyExpense(){
    var store_id = $("#s_id").val();
    var expense_date = $("#expense_date").val();
    if(store_id != ''){
        var url = ROOT_PATH+"/store/expense/monthly/list/"+store_id+'?expense_date='+expense_date;
        window.location.href = url;
    }
}

function autoInsertMonthlyExpenseData(){
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/expense/monthly/insert",
        method:"GET",
        
        success:function(msg){
            
        },error:function(obj,status,error){
            
        }
    });
}

function getStateStores(state_id,store_id){
   ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/report/gst/b2c?action=get_state_stores&state_id="+state_id,
        method:"GET",
        success:function(msg){
           if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#reportErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var stores = msg.stores_list,options_str = '<option value="">-- Store --</option>';
                    for(var i=0;i<stores.length;i++){
                        var sel =  (store_id != '' && store_id == stores[i].id)?'selected':'';
                        options_str+="<option "+sel+" value='"+stores[i].id+"'>"+stores[i].store_name+" ("+stores[i].store_id_code+")</option>";
                    }
                    $("#s_id").html(options_str);
                }
            }else{
                displayResponseError(msg,'reportErrorMessage');
            }
        },error:function(obj,status,error){
            $("#reportErrorMessage").html('Error in processing request').show();
        }
    });
}

function getStateStoresHSNReport(state_id,store_id){
   ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/report/gst/b2c?action=get_state_stores&state_id="+state_id,
        method:"GET",
        success:function(msg){
           if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#reportErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var ids = [];
                    var stores = msg.stores_list,options_str = '';
                    var store_ids = store_id!=''?store_id.split(','):[];
                    for(var i=0;i<store_ids.length;i++){
                        ids.push(parseInt(store_ids[i]));
                    }
                    store_ids = ids;
                    
                    for(var i=0;i<stores.length;i++){
                        var sel =  (store_ids.indexOf(stores[i].id) >= 0)?'selected':'';
                        options_str+="<option "+sel+" value='"+stores[i].id+"'>"+stores[i].store_name+" ("+stores[i].store_id_code+")</option>";
                    }
                    
                    /*if(state_id == 34 || state_id == ''){
                        var sel =  (store_id == -1)?'selected':'';
                        options_str+="<option "+sel+" value='-1'>Kiasa HO (HO)</option>";
                    }*/
                    
                    $("#s_id").html(options_str);
                }
            }else{
                displayResponseError(msg,'reportErrorMessage');
            }
        },error:function(obj,status,error){
            $("#reportErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateReportTypes(){
    $("#updateReportTypeSuccessMessage,#updateReportTypeErrorMessage,.invalid-feedback").html('').hide();
    $("#reports_types_btn").attr("disabled",true);
    var store_id = $("#store_id").val();
    var form_data = $("#reportTypeForm").serialize();
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/store/report-types",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#reports_types_btn").attr("disabled",false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateReportTypeErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateReportTypeSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ var url = ROOT_PATH+"/store/report-types?s_id="+store_id;window.location.href = url; }, 1000);
                }
            }else{
                displayResponseError(msg,"updateReportTypeErrorMessage");
            }
        },error:function(obj,status,error){
            $("#reports_types_btn").attr("disabled",false);
            $("#updateReportTypeErrorMessage").html('Error in processing request').show();
        }
    });
}