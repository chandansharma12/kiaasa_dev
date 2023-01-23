"use strict";
var vendorMaterialsObject;

function editVendor(id){
    $("#edit_user_dialog").modal('show');
    $("#user_edit_id").val(id);
    $("#editUserErrorMessage,#editUserSuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#userName_edit").val(msg.user_data.name);
                    $("#userEmail_edit").val(msg.user_data.email);
                    $("#userPhone_edit").val(msg.user_data.phone);
                    $("#userAddress_edit").val(msg.user_data.address);
                    //$("#userStatus_edit").val(msg.user_data.status);
                    $("#ecommerceStatus_edit").val(msg.user_data.ecommerce_status);
                    $("#userCity_edit").val(msg.user_data.city);
                    $("#userState_edit").val(msg.user_data.state);
                    $("#userPotalCode_edit").val(msg.user_data.postal_code);
                    $("#userGstNo_edit").val(msg.user_data.gst_no);
                    $("#vendorCode_edit").val(msg.user_data.vendor_code);
                }
            }else{
                displayResponseError(msg,'editUserErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editUserErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateVendor(){
    var form_data = $("#editUserFrm").serialize();
    $("#user_edit_spinner").show();
    $("#editUserErrorMessage,#editUserSuccessMessage,.invalid-feedback").html('').hide();
    $("#user_edit_submit,#user_edit_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#user_edit_submit,#user_edit_cancel").attr('disabled',false);
            $("#user_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editUserSuccessMessage").html(msg.message).show();
                    $("#editUserErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_user_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'editUserErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editUserErrorMessage").html('Error in processing request').show();
            $("#user_edit_spinner").hide();
            $("#user_edit_submit,#user_edit_cancel").attr('disabled',false);
        }
    });
}

function addVendor(){
    $("#addUserSuccessMessage,#addUserErrorMessage,.invalid-feedback").html('').hide();
    $("#userName_add,#userEmail_add,#userPhone_add,#userAddress_add,#userStatus_add").val('');
    $("#add_user_dialog").modal('show');
}

function submitAddVendor(){
    var form_data = $("#addUserFrm").serialize();
    $("#user_add_spinner").show();
    $("#addUserSuccessMessage,#addUserErrorMessage,.invalid-feedback").html('').hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#user_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addUserErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addUserSuccessMessage").html(msg.message).show();
                    $("#addUserErrorMessage").html('').hide();
                    setTimeout(function(){  $("#add_user_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,'addUserErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addUserErrorMessage").html('Error in processing request').show();
            $("#user_add_spinner").hide();
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


/*$(document).ready(function(){
    if(typeof vendor_id != 'undefined')
        getMaterialData(vendor_id,'fabric');
});*/

function getMaterialData(id,tab_name){
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/materialdata/"+id,
        method:"GET",
        success:function(msg){
            vendorMaterialsObject = msg.materials_list;
            displayTabData(tab_name);
        }
    });
}

function displayTabData(tab_type){
    var str = '';
    if(tab_type == 'fabric'){
        str+='<div id="requirements_fabrics">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Fabrics</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onclick="deleteVendorMaterial(\'fabric\');" >Delete Row</a> \
        <a href="javascript:;" onClick="displayDialogBox(\'add-material-Fabric\');">Add Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_fabric" id="chk_all_fabric" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'fabric\');" /> \
        <th>Fabric Type</th><th>GSM</th><th>Color</th><th>Width</th><th>Content</th><th>Unit</th></th></tr></thead><tbody>';
        
        for(var i=0;i<vendorMaterialsObject.length;i++){
            if(vendorMaterialsObject[i].type_id == 1){
            str+='<tr><td><input type="checkbox" name="chk_fabric_'+i+'" id="chk_fabric_'+i+'" value="'+vendorMaterialsObject[i].id+'" class="fabric-chk form-check-input"></td>\
                <td>'+vendorMaterialsObject[i].name_id_name+'</td><td>'+vendorMaterialsObject[i].gsm_id_name+'</td><td>'+vendorMaterialsObject[i].color_id_name+'</td>\
                <td>'+vendorMaterialsObject[i].width_id_name+'</td><td>'+vendorMaterialsObject[i].content_id_name+'</td><td>'+vendorMaterialsObject[i].unit_code+'</td>\
                </tr>';
            }
        }
        
        if(i==0){ str+='<tr><td colspan="15" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }else if(tab_type == 'accessories'){
        str+='<div id="requirements_accessories">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Accessories</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onclick="deleteVendorMaterial(\'accessories\');" >Delete Row</a> \
        <a href="javascript:;" onClick="displayDialogBox(\'add-material-Accessories\');">Add Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_accessories" id="chk_all_accessories" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'accessories\');" /> </th>\
        <th>Category</th><th>SubCategory</th><th>Size</th><th>Color</th></tr></thead><tbody>';
        
        for(var i=0;i<vendorMaterialsObject.length;i++){
            if(vendorMaterialsObject[i].type_id == 2){
            str+='<tr><td><input type="checkbox" name="chk_accessories_'+i+'" id="chk_accessories_'+i+'" value="'+vendorMaterialsObject[i].id+'" class="accessories-chk form-check-input"></td>\
                <td>'+vendorMaterialsObject[i].name_id_name+'</td><td>'+vendorMaterialsObject[i].quality_id_name+'</td>\
                <td>'+vendorMaterialsObject[i].content_id_name+'</td><td>'+vendorMaterialsObject[i].color_id_name+'</td>\
                </tr>';
            }
        }
        
        if(i==0){ str+='<tr><td colspan="15" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }else if(tab_type == 'process'){
        str+='<div id="requirements_process">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Process</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onclick="deleteVendorMaterial(\'process\');" >Delete Row</a> \
        <a href="javascript:;" onClick="displayDialogBox(\'add-material-Process\');">Add Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_process" id="chk_all_process" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'process\');" /> </th>\
        <th>Category</th><th>Type</th></tr></thead><tbody>';
        
        for(var i=0;i<vendorMaterialsObject.length;i++){
            if(vendorMaterialsObject[i].type_id == 3){
            str+='<tr><td><input type="checkbox" name="chk_process_'+i+'" id="chk_process_'+i+'" value="'+vendorMaterialsObject[i].id+'" class="process-chk form-check-input"></td>\
                <td>'+vendorMaterialsObject[i].name_id_name+'</td><td>'+vendorMaterialsObject[i].quality_id_name+'</td></tr>';
            }
        }
        
        if(i==0){ str+='<tr><td colspan="15" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }else if(tab_type == 'packaging_sheet'){
        str+='<div id="requirements_packaging_sheet">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Packaging Sheet</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onclick="deleteVendorMaterial(\'packaging_sheet\');" >Delete Row</a> \
        <a href="javascript:;" onClick="displayDialogBox(\'add-material-PackagingSheet\');">Add Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_packaging_sheet" id="chk_all_packaging_sheet" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'packaging_sheet\');" /> </th>\
        <th>Name</th></tr></thead><tbody>';
        
        for(var i=0;i<vendorMaterialsObject.length;i++){
            if(vendorMaterialsObject[i].type_id == 4){
            str+='<tr><td><input type="checkbox" name="chk_packaging_sheet_'+i+'" id="chk_packaging_sheet_'+i+'" value="'+vendorMaterialsObject[i].id+'" class="packaging_sheet-chk form-check-input"></td>\
                <td>'+vendorMaterialsObject[i].name_id_name+'</td></tr>';
            }
        }
        
        if(i==0){ str+='<tr><td colspan="15" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }else if(tab_type == 'product_process'){
        str+='<div id="requirements_product_process">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Production Process</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onclick="deleteVendorMaterial(\'product_process\');" >Delete Row</a> \
        <a href="javascript:;" onClick="displayDialogBox(\'add-material-ProductProcess\');">Add Row</a></div>\
        </div></div>'
        
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>';
        
        str+='<th><input type="checkbox" name="chk_all_product_process" id="chk_all_product_process" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'product_process\');" /> </th>';
        str+='<th>Name</th></tr></thead><tbody>';

        for(var i=0;i<vendorMaterialsObject.length;i++){
            if(vendorMaterialsObject[i].type_id == 5){
            str+='<tr><td><input type="checkbox" name="chk_product_process_'+i+'" id="chk_product_process_'+i+'" value="'+vendorMaterialsObject[i].id+'" class="product_process-chk form-check-input"></td>\
                <td>'+vendorMaterialsObject[i].name_id_name+'</td></tr>';
            }
        }

        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    $("#req_tab_data").html(str);
}

function displayDialogBox(elem){
    $("#"+elem).modal('show');
    $("#"+elem).find('.form-control').val('');
    $("#"+elem).find('.invalid-feedback').html('');
    $("#addFabricErrorMessage,#addFabricSuccessMessage,#addAccessoriesErrorMessage,#addAccessoriesSuccessMessage,#addProcessErrorMessage,#addProcessSuccessMessage,#addPackagingSheetErrorMessage,#addPackagingSheetSuccessMessage").html('').hide();
    $("#addProductProcessSuccessMessage,#addProductProcessErrorMessage").html('').hide();
}

function addVendorMaterial(type_id){
    var form_data, type_name, tab_name = '';
    
    if(type_id == 1){
        form_data = "name_id="+$("#addFabricName").val()+"&gsm_id="+$("#addFabricGSM").val()+"&color_id="+$("#addFabricColor").val()+"&width_id="+$("#addFabricWidth").val()+"&unit_id="+$("#addFabricMasterUnit").val()+"&content_id="+$("#addFabricContent").val();
        type_name = 'Fabric';
        tab_name = 'fabric';
    }else if(type_id == 2){
        form_data = "category_id="+$("#addAccessoriesCategory").val()+"&subcategory_id="+$("#addAccessoriesSubCategory").val()+"&size_id="+$("#addAccessoriesSize").val()+"&color_id="+$("#addAccessoriesColor").val();
        type_name = 'Accessories';
        tab_name = 'accessories';
    }else if(type_id == 3){
        form_data = "category_id="+$("#addProcessCategory").val()+"&process_type_id="+$("#addProcessType").val();
        type_name = 'Process';
        tab_name = 'process';
    }else if(type_id == 4){
        form_data = "name_id="+$("#addPackagingSheetName").val();
        type_name = 'PackagingSheet';
        tab_name = 'packaging_sheet';
    }else if(type_id == 5){
        form_data = "name_id="+$("#addProductProcessName").val();
        type_name = 'ProductProcess';
        tab_name = 'product_process';
    }
    
    form_data = form_data+"&type_id="+type_id;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/addmaterial/"+vendor_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                var errors = '';
                if(msg.status == 'fail'){
                    if(typeof msg.errors !== 'undefined'){
                        if(typeof msg.errors  === 'string'){
                            errors = msg.errors;
                        }else{
                            $.each( msg.errors, function( key, value) {
                                if(type_id == 1){
                                    $("#error_addFabric_"+key).html(value).show();
                                }else if(type_id == 2){
                                    $("#error_addAccessories_"+key).html(value).show();
                                }else if(type_id == 3){
                                    $("#error_validation_"+key).html(value).show();
                                }else if(type_id == 4){
                                    $("#error_validation_packaging_sheet_"+key).html(value).show();
                                }else if(type_id == 5){
                                    $("#error_validation_product_process_"+key).html(value).show();
                                }
                            });
                        }
                    }else{
                        if(typeof msg.message !== 'undefined'){
                            errors = msg.message;  
                        }
                    }
                    if(errors != ''){
                        $("#add"+type_name+"ErrorMessage").html(errors).show();
                        $('.invalid-feedback').html('').hide();
                    } 
                }else{ 
                    $("#add"+type_name+"ErrorMessage").html('').hide();
                    $('.invalid-feedback').html('').hide();
                    $("#add"+type_name+"SuccessMessage").html(msg.message).show();
                    getMaterialData(vendor_id,tab_name);

                    setTimeout(function(){ $('#add-material-'+type_name).modal('hide'); }, 2000);
                }
            }else{
                displayResponseError(msg,"add"+type_name+"ErrorMessage");
            }
        },error:function(obj,status,error){
            $("#add"+type_name+"SuccessMessage").html('').hide();
            $("#add"+type_name+"ErrorMessage").html("Error in Processing Request").show();
        }
    });
}

function getProcessTypes(category_id,elem_id,sel_id){
    if(category_id == ''){
        $("#"+elem_id).html('<option value="">Select One</option>');
        return false;
    }
    
    ajaxSetup();	
    
    $.ajax({
        type: "GET",
        data:{category_id:category_id},
        url:ROOT_PATH+"/design/getprocesstypes/"+category_id,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#errortatusMessage").html(errors).show();
                    } 
                }else{ 
                    var process_types_list = msg.process_types;
                    var str = '<option value="">Select One</option>';
                    var sel = '';
                    for(var i=0;i<process_types_list.length;i++){
                        if(sel_id > 0 && sel_id == process_types_list[i].id) sel = 'selected';else sel = '';
                        str+='<option '+sel+' value="'+process_types_list[i].id+'">'+process_types_list[i].name+'</option>';
                    }
                    $("#"+elem_id).html(str);
                }
            }else{
                displayResponseError(msg,"errortatusMessage");
            }
        },
        error:function(obj,status,error){
            $("#errortatusMessage").html('Error in processing request').show();
        }
    });
}

function deleteVendorMaterial(type){
    
    var chk_class = type+'-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#error_delete_rows").modal('show');
        return false;
    }
        
    $("#deleteErrorMessage,#deleteSuccessMessage").html('').hide();
    
    $('#confirm_delete_rows').modal({
        backdrop: 'static',
        keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_rows_btn', function(e) {
        e.preventDefault();
        $("#delete-rows-spinner").show();
        $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',true);
        
        ajaxSetup();		

        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray},
            url:ROOT_PATH+"/vendor/deletematerial/"+vendor_id,
            success: function(msg){	
                if(objectPropertyExists(msg,'status')){
                    $("#delete-rows-spinner").hide();
                    $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',false);

                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#deleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){ $('#confirm_delete_rows').modal('hide'); }, 2000);

                        if(type == 'fabric'){
                            getMaterialData(vendor_id,'fabric');
                        }else if(type == 'accessories'){
                            getMaterialData(vendor_id,'accessories');
                        }else if(type == 'process'){
                            getMaterialData(vendor_id,'process');
                        }else if(type == 'packaging_sheet'){
                            getMaterialData(vendor_id,'packaging_sheet');
                        }else if(type == 'product_process'){
                            getMaterialData(vendor_id,'product_process');
                        }
                    }
                }else{
                    displayResponseError(msg,"deleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteErrorMessage").html('Error in processing request').show();
                $("#delete-rows-spinner").hide();
                $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',false);
            }
        });
    
    });
}

function submitAddVendorPayment(){
    var form_data = $("#addVendorPaymentFrm").serialize();
    $("#vendor_payment_spinner").show();
    $("#addVendorPaymentSuccessMessage,#addVendorPaymentErrorMessage,.invalid-feedback").html('').hide();
    $("#vendor_payment_add_cancel,#vendor_payment_add_submit").attr('disabled',true);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/inventory/payment/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#vendor_payment_spinner").hide();
            $("#vendor_payment_add_cancel,#vendor_payment_add_submit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addVendorPaymentErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addVendorPaymentSuccessMessage").html(msg.message).show();
                    $("#addVendorPaymentErrorMessage").html('').hide();
                    setTimeout(function(){ var url = ROOT_PATH+"/vendor/inventory/payment/list"; window.location.href = url; }, 2000);
                }
            }else{
                displayResponseError(msg,'addVendorPaymentErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addVendorPaymentErrorMessage").html('Error in processing request').show();
            $("#vendor_payment_add_cancel,#vendor_payment_add_submit").attr('disabled',false);
            $("#vendor_payment_spinner").hide();
        }
    });
}


function addSubVendor(){
    $("#addSubVendorSuccessMessage,#addSubVendorErrorMessage,.invalid-feedback").html('').hide();
    $("#subvendor_add").val('');
    $("#add_subvendor_dialog").modal('show');
}

function submitAddSubVendor(){
    var form_data = $("#addSubVendorFrm").serialize();
    $("#subvendor_add_spinner").show();
    $("#addSubVendorSuccessMessage,#addSubVendorErrorMessage,.invalid-feedback").html('').hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/vendor/subvendor/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#subvendor_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addSubVendorErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addSubVendorSuccessMessage").html(msg.message).show();
                    $("#addSubVendorErrorMessage").html('').hide();
                    setTimeout(function(){  $("#add_subvendor_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'addSubVendorErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addSubVendorErrorMessage").html('Error in processing request').show();
            $("#subvendor_add_spinner").hide();
        }
    });
}

function deleteSubvendorFromVendor(subvendor_id){
    $('#confirm_delete_subvendor').modal({
        backdrop: 'static',
        keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#subvendor_delete_btn', function(e) {
        e.preventDefault();
        $("#subvendor-delete-spinner").show();
        $("#subvendor_delete_btn,#subvendor_delete_cancel").attr('disabled',true);
        
        ajaxSetup();		

        $.ajax({
            type: "POST",
            data:{subvendor_id:subvendor_id},
            url:ROOT_PATH+"/vendor/subvendor/delete",
            success: function(msg){	
                if(objectPropertyExists(msg,'status')){
                    $("#subvendor-delete-spinner").hide();
                    $("#subvendor_delete_btn,#subvendor_delete_cancel").attr('disabled',false);

                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#subVendorDeleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $("#subVendorDeleteSuccessMessage").html(msg.message).show();
                        setTimeout(function(){ $('#confirm_delete_subvendor').modal('hide');window.location.reload(); }, 1000);
                    }
                }else{
                    displayResponseError(msg,"subVendorDeleteErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#subVendorDeleteErrorMessage").html('Error in processing request').show();
                $("#subvendor-delete-spinner").hide();
                $("#subvendor_delete_btn,#subvendor_delete_cancel").attr('disabled',false);
            }
        });
    
    });
}