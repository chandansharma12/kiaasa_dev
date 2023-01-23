"use strict";

function displayDialogBox(elem){
    //if(!checkRequisitionStatus()) return false;
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $("#"+elem).modal('show');
    $("#"+elem).find('.form-control').val('');
    $("#"+elem).find('.invalid-feedback').html('');
    $("#addFabricErrorMessage,#addAccessoriesErrorMessage,#addProcessErrorMessage").hide();
    $("#add_fabric_color_span,#add_acc_color_span").html('');
    $("#addFabricColor,#addAccessoriesColor").val('');
}

function checkRequisitionStatus(){
    
    if(designDataObject.design_data.is_requisition_created == 1){
        return false;
    }
     
    if(user_type == 5){ //Designer
        if(designDataObject.design_data.reviewer_status != null || designDataObject.design_data.reviewer_status == ''){
            if((designDataObject.design_data.reviewer_status.toLowerCase() == 'waiting' || designDataObject.design_data.reviewer_status.toLowerCase() == 'approved') && designDataObject.design_data.is_requisition_created == 1){
                $("#saveStatusDialogTitle").html("Error");
                $("#saveStatusDialogContent").html("Requisition status is "+designDataObject.design_data.reviewer_status+" ");
                $("#status_updated_dialog").modal('show');
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }else if(user_type == 2){   //Production
        if(designDataObject.design_data.production_status != null && designDataObject.design_data.production_status != ''){
            if((designDataObject.design_data.production_status.toLowerCase() == 'waiting' || designDataObject.design_data.production_status.toLowerCase() == 'approved') ){
                $("#saveStatusDialogTitle").html("Error");
                $("#saveStatusDialogContent").html("Design Production Review status is "+designDataObject.design_data.production_status+" ");
                $("#status_updated_dialog").modal('show');
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }else if(user_type == 3){   //Purchaser
        return true;
        /*if(designDataObject.design_data.production_status != null && designDataObject.design_data.production_status != ''){
            if((designDataObject.design_data.production_status.toLowerCase() == 'rejected' || designDataObject.design_data.production_status.toLowerCase() == 'approved') ){
                $("#saveStatusDialogTitle").html("Error");
                $("#saveStatusDialogContent").html("Design Production Review status is "+designDataObject.design_data.production_status+" ");
                $("#status_updated_dialog").modal('show');
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }*/
    }else if(user_type == 7){   //Production Head
        if(designDataObject.design_data.production_status != null && designDataObject.design_data.production_status != ''){
            if((designDataObject.design_data.production_status.toLowerCase() == 'rejected' || designDataObject.design_data.production_status.toLowerCase() == 'approved') ){
                $("#saveStatusDialogTitle").html("Error");
                $("#saveStatusDialogContent").html("Design Production Review status is "+designDataObject.design_data.production_status+" ");
                $("#status_updated_dialog").modal('show');
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }else{
        return true;
    }
}

$("#addFabricFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    var body_part = $("#addFabricBodyPart").val();
    var width = $("#addFabricWidth").val();
    var avg = $("#addFabricAvg").val();
    var rate = $("#addFabricRate").val();
    var cost = $("#addFabricCost").val();
    var unit_id = $("#addFabricUnit").val();
    var master_unit_id = $("#addFabricMasterUnit").val();
    var comments = $("#addFabricComments").val();
    var color = $("#addFabricColor").val();
    var name = $("#addFabricName").val();
    var content_id = $("#addFabricContent").val();
    var gsm_id = $("#addFabricGSM").val();
    
    formData.append('body_part', body_part);
    formData.append('width', width);
    formData.append('avg', avg);
    formData.append('rate', rate);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('master_unit_id', master_unit_id);
    formData.append('comments', comments);
    formData.append('color', color);
    formData.append('name', name);
    formData.append('content_id', content_id);
    formData.append('gsm_id', gsm_id);
    
    $("#add-fabric-spinner").show();
    $("#addFabricBtn_submit,#addFabricBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/adddesignfabric/"+design_id,
        success: function(msg){		
            $("#add-fabric-spinner").hide();
            $("#addFabricBtn_submit,#addFabricBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_addFabric_');
                    if(errors != ''){
                        $("#addFabricErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#add-design-fabric').modal('hide');
                    $("#addFabricErrorMessage").hide();
                    designDataObject.fabric_data = msg.fabric_data;
                    displayTabData('fabric');
                }
            }else{
                displayResponseError(msg,"addFabricErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addFabricErrorMessage").html('Error in processing request').show();
            $("#add-fabric-spinner").hide();
            $("#addFabricBtn_submit,#addFabricBtn_cancel").attr('disabled',false);
        }
    });
});

function displayDesignItemImage(img_url){
    $("#design_item_image_content").html('<img src="'+img_url+'" class="design-item-image">');
    $("#design_item_image_dialog").modal('show');
}

function addDesignFabric(id){
    $("#addFabricFrm").submit();
}

function editDesignFabric(id){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var fabric_data = designDataObject.fabric_data;
    var fabric_data_row = '';
    for(var i=0;i<fabric_data.length;i++){
        if(fabric_data[i].id == id){
            fabric_data_row = fabric_data[i];
        }
    }
    
    $("#editFabricBodyPart").val(fabric_data_row.body_part_id);
    $("#editFabricAvg").val(fabric_data_row.avg);
    $("#editFabricRate").val(fabric_data_row.rate);
    $("#editFabricCost").val(fabric_data_row.cost);
    $("#editFabricUnit").val(fabric_data_row.unit_id);
    $("#editFabricMasterUnit").val(fabric_data_row.master_unit_id);
    $("#fabric_id_edit_hdn").val(fabric_data_row.id);
    $("#editFabricComments").val(fabric_data_row.comments);
    $("#editFabricSize").val(fabric_data_row.size);
    $("#editFabricColor").val(fabric_data_row.color_id);
    $("#editFabricName").val(fabric_data_row.name_id);
    $("#editFabricImage").val("");
    $("#addFabricErrorMessage,#editFabricErrorMessage").hide();
    if(typeof fabric_data_row.color_name != 'undefined'){
        $("#edit_fabric_color_span").html(fabric_data_row.color_name);
    }
    
    updateFabricNameData(fabric_data_row.name_id,'edit',1,fabric_data_row);
    
    if(fabric_data_row.image_name != null && fabric_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+fabric_data_row.image_name;
        $("#editFabricImg").attr("src",img_url).show();
        $("#editFabricImg_delete_link").show();
    }else{
        $("#editFabricImg").attr("src",'').hide();
        $("#editFabricImg_delete_link").hide();
    }
    
    $('#edit-design-fabric').modal({
        backdrop: 'static',
        keyboard: false
    });
}
 
function updateDesignFabric(){
    $("#editFabricFrm").submit();
}

$("#editFabricFrm").on('submit', function(event){
    event.preventDefault();
    
    var formData = new FormData(this);
    var body_part = $("#editFabricBodyPart").val();
    var quality = $("#editFabricQuality").val();
    var width = $("#editFabricWidth").val();
    var avg = $("#editFabricAvg").val();
    var rate = $("#editFabricRate").val();
    var cost = $("#editFabricCost").val();
    var unit_id = $("#editFabricUnit").val();
    var master_unit_id = $("#editFabricMasterUnit").val();
    var fabric_id = $("#fabric_id_edit_hdn").val();
    var comments = $("#editFabricComments").val();
    var color = $("#editFabricColor").val();
    var name = $("#editFabricName").val();
    var delete_image = $("#editFabricImg_delete_hdn").val();
    var content_id = $("#editFabricContent").val();
    var count_id = $("#editFabricCount").val();
    var gsm_id = $("#editFabricGSM").val();
    
    formData.append('body_part', body_part);
    formData.append('quality', quality);
    formData.append('width', width);
    formData.append('avg', avg);
    formData.append('rate', rate);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('master_unit_id', master_unit_id);
    formData.append('id', fabric_id);
    formData.append('comments', comments);
    formData.append('color', color);
    formData.append('name', name);
    formData.append('delete_image', delete_image);
    formData.append('content_id', content_id);
    formData.append('count_id', count_id);
    formData.append('gsm_id', gsm_id);
    
    $("#edit-fabric-spinner").show();
    $("#editFabricBtn_submit,#editFabricBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignfabric/"+design_id,
        success: function(msg){		
            $("#edit-fabric-spinner").hide();
            $("#editFabricBtn_submit,#editFabricBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editFabric_');
                    if(errors != ''){
                        $("#editFabricErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-fabric').modal('hide');
                    designDataObject.fabric_data = msg.fabric_data;
                    displayTabData('fabric');
                    $("#editFabricImg_delete_hdn").val(0);
                }
            }else{
                displayResponseError(msg,"editFabricErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editFabricErrorMessage").html('Error in processing request').show();
            $("#edit-fabric-spinner").hide();
            $("#editFabricBtn_submit,#editFabricBtn_cancel").attr('disabled',false);
        }
    });
});

function addDesignAccessories(id){
    $("#addAccessoriesFrm").submit();
}

$("#addAccessoriesFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var category_id = $("#addAccessoriesCategory").val();
    var subcategory_id = $("#addAccessoriesSubCategory").val();
    var color_id = $("#addAccessoriesColor").val();
    var rate = $("#addAccessoriesRate").val();
    var qty = $("#addAccessoriesQuantity").val();
    var cost = $("#addAccessoriesCost").val();
    var unit_id = $("#addAccessoriesUnit").val();
    var comments = $("#addAccessoriesComments").val();
    var size = $("#addAccessoriesSize").val();
    
    var formData = new FormData(this);
    formData.append('category_id', category_id);
    formData.append('subcategory_id', subcategory_id);
    formData.append('color_id', color_id);
    formData.append('rate', rate);
    formData.append('qty', qty);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('comments', comments);
    formData.append('size', size);
    
    $("#add-Accessories-spinner").show();
    $("#addAccessoriesBtn_submit,#addAccessoriesBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/adddesignaccessories/"+design_id,
        success: function(msg){		
            $("#add-Accessories-spinner").hide();
            $("#addAccessoriesBtn_submit,#addAccessoriesBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_addAccessories_');
                    if(errors != ''){
                        $("#addAccessoriesErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#add-design-accessories').modal('hide');
                    designDataObject.accessories_data = msg.accessories_data;
                    displayTabData('accessories');
                }
            }else{
                displayResponseError(msg,"addAccessoriesErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addAccessoriesErrorMessage").html('Error in processing request').show();
            $("#add-Accessories-spinner").hide();
            $("#addAccessoriesBtn_submit,#addAccessoriesBtn_cancel").attr('disabled',false);
        }
    });
});

function editDesignAccessories(id){
    //if(!checkRequisitionStatus()) return false;
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    
    $(".invalid-feedback").html('').hide();
    var accessories_data = designDataObject.accessories_data;
    var accessories_data_data_row = '';
    for(var i=0;i<accessories_data.length;i++){
        if(accessories_data[i].id == id){
            accessories_data_data_row = accessories_data[i];
        }
    }
    
    $("#editAccessoriesCategory").val(accessories_data_data_row.category_id);
    $("#editAccessoriesColor").val(accessories_data_data_row.color_id);
    $("#editAccessoriesRate").val(accessories_data_data_row.rate);
    $("#editAccessoriesQuantity").val(accessories_data_data_row.avg);
    $("#editAccessoriesCost").val(accessories_data_data_row.cost);
    
    $("#editAccessoriesUnit").val(accessories_data_data_row.unit_id);
    $("#accessories_id_edit_hdn").val(accessories_data_data_row.id);
    $("#editAccessoriesComments").val(accessories_data_data_row.comments);
    $("#editAccessoriesImage").val("");
    $("#addAccessoriesErrorMessage,#editAccessoriesErrorMessage").hide();
    getAccessorySubcategories(accessories_data_data_row.category_id,'editAccessoriesSubCategory',accessories_data_data_row.quality_id);
    getAccessorySize(accessories_data_data_row.quality_id,'editAccessoriesSize',accessories_data_data_row.size_id);
    
    if(typeof accessories_data_data_row.color_name != 'undefined'){
        $("#edit_acc_color_span").html(accessories_data_data_row.color_name);
    }
    
    if(accessories_data_data_row.image_name != null && accessories_data_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+accessories_data_data_row.image_name;
        $("#editAccessoriesImg").attr("src",img_url).show();
        $("#editAccessoriesImg_delete_link").show();
    }else{
        $("#editAccessoriesImg").attr("src",'').hide();
        $("#editAccessoriesImg_delete_link").hide();
    }
    
    $('#edit-design-accessories').modal({
        backdrop: 'static',
        keyboard: false
    });
 }
 
 $("#editAccessoriesFrm").on('submit', function(event){
    event.preventDefault();
    $(".invalid-feedback").html('').hide();
    
    var category_id = $("#editAccessoriesCategory").val();
    var subcategory_id = $("#editAccessoriesSubCategory").val();
    var color_id = $("#editAccessoriesColor").val();
    var rate = $("#editAccessoriesRate").val();
    var qty = $("#editAccessoriesQuantity").val();
    var cost = $("#editAccessoriesCost").val();
    var unit_id = $("#editAccessoriesUnit").val();
    var accessories_id = $("#accessories_id_edit_hdn").val();
    var comments = $("#editAccessoriesComments").val();
    var size = $("#editAccessoriesSize").val();
    var delete_image = $("#editAccessoriesImg_delete_hdn").val();
    
    var formData = new FormData(this);
    formData.append('category_id', category_id);
    formData.append('subcategory_id', subcategory_id);
    formData.append('color_id', color_id);
    formData.append('rate', rate);
    formData.append('qty', qty);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('id', accessories_id);
    formData.append('comments', comments);
    formData.append('size', size);
    formData.append('delete_image', delete_image);
    
    $("#edit-Accessories-spinner").show();
    $("#editAccessoriesBtn_submit,#editAccessoriesBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignaccessories/"+design_id,
        success: function(msg){		
            $("#edit-Accessories-spinner").hide();
            $("#editAccessoriesBtn_submit,#editAccessoriesBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editAccessories_');
                    if(errors != ''){
                        $("#editAccessoriesErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-accessories').modal('hide');
                    designDataObject.accessories_data = msg.accessories_data;
                    displayTabData('accessories');
                }
            }else{
                displayResponseError(msg,"editAccessoriesErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editAccessoriesErrorMessage").html('Error in processing request').show();
            $("#edit-Accessories-spinner").hide();
            $("#editAccessoriesBtn_submit,#editAccessoriesBtn_cancel").attr('disabled',false);
        }
    });
    
 });
 
function updateDesignAccessories(id){
    $("#editAccessoriesFrm").submit();
}

$("#addProcessFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var category_id = $("#addProcessCategory").val();
    var rate = $("#addProcessRate").val();
    var avg = $("#addProcessAvg").val();
    var type_id = $("#addProcessType").val();
    var cost = $("#addProcessCost").val();
    var comments = $("#addProcessComments").val();
    var unit_id = $("#addProcessUnit").val();
    var fabric_id_arr = $("#addProcessFabric").val().split('__');
    var fabric_id = fabric_id_arr[0];
    
    var formData = new FormData(this);
    formData.append('category_id', category_id);
    formData.append('type_id', type_id);
    formData.append('rate', rate);
    formData.append('avg', avg);
    formData.append('cost', cost);
    formData.append('comments', comments);
    formData.append('unit_id', unit_id);
    formData.append('fabric_id', fabric_id);
    
    $("#add-Process-spinner").show();
    $("#addProcessBtn_submit,#addProcessBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/adddesignprocess/"+design_id,
        success: function(msg){		
            $("#add-Process-spinner").hide();
            $("#addProcessBtn_submit,#addProcessBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_addProcess_');
                    if(errors != ''){
                        $("#addProcessErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#add-design-process').modal('hide');
                    designDataObject.process_data = msg.process_data;
                    displayTabData('process');
                }
            }else{
                displayResponseError(msg,"addProcessErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addProcessErrorMessage").html('Error in processing request').show();
            $("#add-Process-spinner").hide();
            $("#addProcessBtn_submit,#addProcessBtn_cancel").attr('disabled',false);
        }
    });
});

function addDesignProcess(id){
    $("#addProcessFrm").submit();
}

function editDesignProcess(id){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var process_data = designDataObject.process_data;
    var process_data_row = '';
    for(var i=0;i<process_data.length;i++){
        if(process_data[i].id == id){
            process_data_row = process_data[i];
        }
    }
    
    $("#editProcessCategory").val(process_data_row.category_id);
    $("#editProcessType").val(process_data_row.type_id);
    $("#editProcessRate").val(process_data_row.rate);
    $("#editProcessAvg").val(process_data_row.avg);
    $("#editProcessCost").val(process_data_row.cost);
    $("#editProcessComments").val(process_data_row.comments);
    $("#process_id_edit_hdn").val(process_data_row.id);
    $("#editProcessUnit").val(process_data_row.unit_id);
    $("#editProcessImage").val("");
    $("#addProcessErrorMessage,#editProcessErrorMessage").html('').hide();
    getProcessTypes(process_data_row.category_id,'editProcessType',process_data_row.type_id);
    getProcessDesigns('edit',process_data_row.fabric_instance_id);
    
    if(process_data_row.image_name != null && process_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+process_data_row.image_name;
        $("#editProcessImg").attr("src",img_url).show();
        $("#editProcessImg_delete_link").show();
    }else{
        $("#editProcessImg").attr("src",'').hide();
        $("#editProcessImg_delete_link").hide();
    }
    
    $('#edit-design-process').modal({
        backdrop: 'static',
        keyboard: false
    })
 }
 
$("#editProcessFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var category_id = $("#editProcessCategory").val();
    var type_id = $("#editProcessType").val();
    var rate = $("#editProcessRate").val();
    var avg = $("#editProcessAvg").val();
    var cost = $("#editProcessCost").val();
    var process_id = $("#process_id_edit_hdn").val();
    var comments = $("#editProcessComments").val();
    var delete_image = $("#editProcessImg_delete_hdn").val();
    var unit_id = $("#editProcessUnit").val();
    var fabric_id_arr = $("#editProcessFabric").val().split('__');
    var fabric_id = fabric_id_arr[0];
    
    var formData = new FormData(this);
    formData.append('category_id', category_id);
    formData.append('type_id', type_id);
    formData.append('rate', rate);
    formData.append('avg', avg);
    formData.append('cost', cost);
    formData.append('id', process_id);
    formData.append('comments', comments);
    formData.append('delete_image', delete_image);
    formData.append('unit_id', unit_id);
    formData.append('fabric_id', fabric_id);
    
    $("#edit-Process-spinner").show();
    $("#editProcessBtn_submit,#editProcessBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignprocess/"+design_id,
        success: function(msg){		
            $("#edit-Process-spinner").hide();
            $("#editProcessBtn_submit,#editProcessBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editProcess_');
                    if(errors != ''){
                        $("#editProcessErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-process').modal('hide');
                    designDataObject.process_data = msg.process_data;
                    displayTabData('process');
                }
            }else{
                displayResponseError(msg,"editProcessErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editProcessErrorMessage").html('Error in processing request').show();
            $("#edit-Process-spinner").hide();
            $("#editProcessBtn_submit,#editProcessBtn_cancel").attr('disabled',false);
        }
    });
});
 
function updateDesignProcess(id){
    $("#editProcessFrm").submit();
}

function editDesignPackagingSheet(id){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var packaging_sheet_data = designDataObject.packaging_sheet_data;
    var packaging_sheet_data_row = '';
    for(var i=0;i<packaging_sheet_data.length;i++){
        if(packaging_sheet_data[i].id == id){
            packaging_sheet_data_row = packaging_sheet_data[i];
        }
    }
    
    $("#editPackagingSheetName").val(packaging_sheet_data_row.packaging_sheet_name);
    $("#editPackagingSheetCost").val(packaging_sheet_data_row.cost);
    $("#editPackagingSheetComments").val(packaging_sheet_data_row.comments);
    $("#editPackagingSheetQty").val(packaging_sheet_data_row.avg);
    $("#editPackagingSheetRate").val(packaging_sheet_data_row.rate);
    $("#packaging_sheet_id_edit_hdn").val(packaging_sheet_data_row.id);
    $("#editPackagingSheetImage").val("");
    
    if(packaging_sheet_data_row.image_name != null && packaging_sheet_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+packaging_sheet_data_row.image_name;
        $("#editPackagingSheetImg").attr("src",img_url).show();
        $("#editPackagingSheetImg_delete_link").show();
    }else{
        $("#editPackagingSheetImg").attr("src",'').hide();
        $("#editPackagingSheetImg_delete_link").hide();
    }
    
    $('#edit-design-packaging_sheet').modal({
        backdrop: 'static',
        keyboard: false
    })
 }
 
 $("#editPackagingSheetFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var cost = $("#editPackagingSheetCost").val();
    var rate = $("#editPackagingSheetRate").val();
    var qty = $("#editPackagingSheetQty").val();
    var packaging_sheet_id = $("#packaging_sheet_id_edit_hdn").val();
    var comments = $("#editPackagingSheetComments").val();
    var delete_image = $("#editPackagingSheetImg_delete_hdn").val();
    
    var formData = new FormData(this);
    formData.append('cost', cost);
    formData.append('id', packaging_sheet_id);
    formData.append('comments', comments);
    formData.append('qty', qty);
    formData.append('rate', rate);
    formData.append('delete_image', delete_image);
    
    $("#edit-PackagingSheet-spinner").show();
    $("#editPackagingSheetBtn_submit,#editPackagingSheetBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignpackagingsheet/"+design_id,
        success: function(msg){		
            $("#edit-PackagingSheet-spinner").hide();
            $("#editPackagingSheetBtn_submit,#editPackagingSheetBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editPackagingSheet_');
                    if(errors != ''){
                        $("#editPackagingSheetErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-packaging_sheet').modal('hide');
                    designDataObject.packaging_sheet_data = msg.packaging_sheet_data;
                    displayTabData('packaging_sheet');
                }
            }else{
                displayResponseError(msg,"editPackagingSheetErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editPackagingSheetErrorMessage").html('Error in processing request').show();
            $("#edit-PackagingSheet-spinner").hide();
            $("#editPackagingSheetBtn_submit,#editPackagingSheetBtn_cancel").attr('disabled',false);
        }
    });
});
 
function updateDesignPackagingSheet(id){
    $("#editPackagingSheetFrm").submit();
}

function editDesignProductProcess(id){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var product_process_data = designDataObject.product_process_data;
    var product_process_data_row = '';
    for(var i=0;i<product_process_data.length;i++){
        if(product_process_data[i].id == id){
            product_process_data_row = product_process_data[i];
        }
    }
    
    $("#editProductProcessBodyPart").val(product_process_data_row.body_part_id);
    $("#editProductProcessName").val(product_process_data_row.name_id);
    $("#editProductProcessDesignerCost").val(product_process_data_row.cost);
    //$("#editProductProcessDesignerTopCost").val(product_process_data_row.designer_top_cost);
    //$("#editProductProcessDesignerBottomCost").val(product_process_data_row.designer_bottom_cost);
    //$("#editProductProcessDesignerDupattaCost").val(product_process_data_row.designer_dupatta_cost);
    $("#editProductProcessComments").val(product_process_data_row.comments);
    $("#edit_product_process_id_edit_hdn").val(product_process_data_row.id);
    $("#editProductProcessImage").val("");
    
    if(product_process_data_row.image_name != null && product_process_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+product_process_data_row.image_name;
        $("#editProductProcessImg").attr("src",img_url).show();
        $("#editProductProcessImg_delete_link").show();
    }else{
        $("#editProductProcessImg").attr("src",'').hide();
        $("#editProductProcessImg_delete_link").hide();
    }
    
    /*if(product_process_data_row.product_process_name.toLowerCase() == 'packing'){
        $("#product_process_cost_edit_div").show();
        $("#product_process_top_cost_edit_div").hide();
        $("#product_process_bottom_cost_edit_div").hide();
        $("#product_process_dupatta_cost_edit_div").hide();
    }else{
        $("#product_process_cost_edit_div").hide();
        $("#product_process_top_cost_edit_div").show();
        $("#product_process_bottom_cost_edit_div").show();
        $("#product_process_dupatta_cost_edit_div").show();
    }*/
    
    $('#edit-design-product_process').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function updateDesignProductProcess(id){
    $("#editProductProcessFrm").submit();
}

$("#editProductProcessFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var body_part_id = $("#editProductProcessBodyPart").val();
    var name_id = $("#editProductProcessName").val();
    var cost = $("#editProductProcessDesignerCost").val();
    //var top_cost = $("#editProductProcessDesignerTopCost").val();
    //var bottom_cost = $("#editProductProcessDesignerBottomCost").val();
    //var dupatta_cost = $("#editProductProcessDesignerDupattaCost").val();
    var comments = $("#editProductProcessComments").val();
    var product_process_id = $("#edit_product_process_id_edit_hdn").val();
    var delete_image = $("#editProductProcessImg_delete_hdn").val();
    
    var formData = new FormData(this);
    formData.append('body_part_id', body_part_id);
    formData.append('name_id', name_id);
    //formData.append('top_cost', top_cost);
    //formData.append('bottom_cost', bottom_cost);
    //formData.append('dupatta_cost', dupatta_cost);
    formData.append('id', product_process_id);
    formData.append('comments', comments);
    formData.append('delete_image', delete_image);
    formData.append('cost', cost);
    
    $("#edit-ProductProcess-spinner").show();
    $("#editProductProcessBtn_submit,#editProductProcessBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignproductprocess/"+design_id,
        success: function(msg){		
            $("#edit-ProductProcess-spinner").hide();
            $("#editProductProcessBtn_submit,#editProductProcessBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editProductProcess_');
                    if(errors != ''){
                        $("#editProductProcessErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-product_process').modal('hide');
                    designDataObject.product_process_data = msg.product_process_data;
                    displayTabData('product_process');
                }
            }else{
                displayResponseError(msg,"editProductProcessErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editProductProcessErrorMessage").html('Error in processing request').show();
            $("#edit-ProductProcess-spinner").hide();
            $("#editProductProcessBtn_submit,#editProductProcessBtn_cancel").attr('disabled',false);
        }
    });
});

function addDesignProductProcess(id){
     $("#addProductProcessFrm").submit();
}

$("#addProductProcessFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var body_part_id = $("#addProductProcessBodyPart").val();
    var name_id = $("#addProductProcessName").val();
    var cost = $("#addProductProcessDesignerCost").val();
    //var top_cost = $("#addProductProcessDesignerTopCost").val();
    //var bottom_cost = $("#addProductProcessDesignerBottomCost").val();
    //var dupatta_cost = $("#addProductProcessDesignerDupattaCost").val();
    var comments = $("#addProductProcessComments").val();
    
    var formData = new FormData(this);
    formData.append('body_part_id', body_part_id);
    formData.append('name_id', name_id);
    formData.append('cost', cost);
    //formData.append('top_cost', top_cost);
    //formData.append('bottom_cost', bottom_cost);
    //formData.append('dupatta_cost', dupatta_cost);
    formData.append('comments', comments);
    
    $("#add-ProductProcess-spinner").show();
    $("#addProductProcessBtn_submit,#addProductProcessBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/adddesignproductprocess/"+design_id,
        success: function(msg){		
            $("#add-ProductProcess-spinner").hide();
            $("#addProductProcessBtn_submit,#addProductProcessBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_addProductProcess_');
                    if(errors != ''){
                        $("#addProductProcessErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#add-design-product_process').modal('hide');
                    designDataObject.product_process_data = msg.product_process_data;
                    displayTabData('product_process');
                }
            }else{
                displayResponseError(msg,"addProductProcessErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addProductProcessErrorMessage").html('Error in processing request').show();
            $("#add-ProductProcess-spinner").hide();
            $("#addProductProcessBtn_submit,#addProductProcessBtn_cancel").attr('disabled',false);
        }
    });
});

function addDesignSpecificationSheet(){
    $("#addSpecificationSheetSuccessMessage,#addSpecificationSheetErrorMessage").html("").hide();
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        url:ROOT_PATH+"/design/getdesignspecificationsheet/"+design_id,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addSpecificationSheetErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var specification_sheet = msg.specification_sheet;

                    var str = '<div class="table-responsive"><table id="spec_sheet_tbl" class="table clearfix"><!--<thead><tr><th>Name</th></tr></thead>--><tbody><tr>';
                    for(var i=0;i<specification_sheet.length;i++){
                        var chk = '<input type="checkbox" name="specification_sheet_'+specification_sheet[i].id+'" id="specification_sheet_'+specification_sheet[i].id+'" value="'+specification_sheet[i].id+'" class="specification-sheet-chk">';
                        str+='<td align="left">'+chk+'&nbsp;&nbsp;<label for="specification_sheet_'+specification_sheet[i].id+'">'+specification_sheet[i].name+'</label></td>';
                        if(i> 0 && (i+1)%3 == 0) str+='</tr><tr>';
                    }
                    str+='</tr></tbody></table></div>';
                    $("#specification_sheet_add_content").html(str);
                }
            }else{
                displayResponseError(msg,"addSpecificationSheetErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addSpecificationSheetErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitAddDesignSpecificationSheet(){
    var id_str = '';
    $(".specification-sheet-chk").each(function(){
        if($(this).is(":checked")){
            id_str+=$(this).val()+",";
        }
    });
    
    id_str = id_str.substring(0,id_str.length-1);
    
    var form_data = "id_str="+id_str;
    $("#add-SpecificationSheet-spinner").show();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/design/adddesignspecificationsheet/"+design_id,
        method:"POST",
        data:form_data,
        success:function(data){
            $("#add-SpecificationSheet-spinner").hide();
            if(objectPropertyExists(data,'status')){    
                if(data.status == 'success'){
                    $("#addSpecificationSheetSuccessMessage").html(data.message).show();
                    $("#addSpecificationSheetErrorMessage").html('').hide();
                    designDataObject.specification_sheet_data = data.specification_sheet_data;
                    displayTabData('specification_sheet');
                    setTimeout(function(){  $("#add-design-specification_sheet").modal('hide'); }, 2000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#addSpecificationSheetErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"addSpecificationSheetErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addSpecificationSheetErrorMessage").html(error).show();
            $("#add-SpecificationSheet-spinner").hide();
        }
    });
}

function updateDesignSpecificationSheet(){
    var form_data = $("#spec_sheet_form").serialize();
    //$("#update-SpecificationSheet-spinner").show();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/design/updatedesignspecificationsheet/"+design_id,
        method:"POST",
        data:form_data,
        success:function(data){
            //$("#update-SpecificationSheet-spinner").hide();
            if(objectPropertyExists(data,'status')){    
                if(data.status == 'success'){
                    displayStatusText(data.message,'success');
                    $("#updateSpecificationSheetErrorMessage").html('').hide();
                    designDataObject.specification_sheet_data = data.specification_sheet_data;
                    displayTabData('specification_sheet');
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#updateSpecificationSheetErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"updateSpecificationSheetErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateSpecificationSheetErrorMessage").html(error).show();
            //$("#update-SpecificationSheet-spinner").hide();
        }
    });
}


$("#addEmbroideryFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    var type = $("#addEmbroideryType").val();
    var rate = $("#addEmbroideryRate").val();
    var cost = $("#addEmbroideryCost").val();
    var unit_id = $("#addEmbroideryUnit").val();
    var comments = $("#addEmbroideryComments").val();
   
    formData.append('type', type);
    formData.append('rate', rate);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('comments', comments);
    
    $("#add-embroidery-spinner").show();
    $("#addEmbroideryBtn_submit,#addEmbroideryBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/adddesignembroidery/"+design_id,
        success: function(msg){		
            $("#add-embroidery-spinner").hide();
            $("#addEmbroideryBtn_submit,#addEmbroideryBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_addEmbroidery_');
                    if(errors != ''){
                        $("#addEmbroideryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#add-design-embroidery').modal('hide');
                    $("#addEmbroideryErrorMessage").hide();
                    designDataObject.embroidery_data = msg.embroidery_data;
                    displayTabData('embroidery');
                }
            }else{
                displayResponseError(msg,"addEmbroideryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addEmbroideryErrorMessage").html('Error in processing request').show();
            $("#add-embroidery-spinner").hide();
            $("#addEmbroideryBtn_submit,#addEmbroideryBtn_cancel").attr('disabled',false);
        }
    });
});

function addDesignEmbroidery(id){
    $("#addEmbroideryFrm").submit();
}

function editDesignEmbroidery(id){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var embroidery_data = designDataObject.embroidery_data;
    var embroidery_data_row = '';
    for(var i=0;i<embroidery_data.length;i++){
        if(embroidery_data[i].id == id){
            embroidery_data_row = embroidery_data[i];
        }
    }
    
    $("#editEmbroideryType").val(embroidery_data_row.name_id);
    $("#editEmbroideryRate").val(embroidery_data_row.rate);
    $("#editEmbroideryCost").val(embroidery_data_row.cost);
    $("#editEmbroideryUnit").val(embroidery_data_row.unit_id);
    $("#edit_embroidery_id_edit_hdn").val(embroidery_data_row.id);
    $("#editEmbroideryComments").val(embroidery_data_row.comments);
    $("#editEmbroideryImage").val("");
    $("#addEmbroideryErrorMessage,#editEmbroideryErrorMessage").hide();
   
    if(embroidery_data_row.image_name != null && embroidery_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+embroidery_data_row.image_name;
        $("#editEmbroideryImg").attr("src",img_url).show();
        $("#editEmbroideryImg_delete_link").show();
    }else{
        $("#editEmbroideryImg").attr("src",'').hide();
        $("#editEmbroideryImg_delete_link").hide();
    }
    
    $('#edit-design-embroidery').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function updateDesignEmbroidery(){
    $("#editEmbroideryFrm").submit();
}

$("#editEmbroideryFrm").on('submit', function(event){
    event.preventDefault();
    
    var formData = new FormData(this);
    var type = $("#editEmbroideryType").val();
    var rate = $("#editEmbroideryRate").val();
    var cost = $("#editEmbroideryCost").val();
    var unit_id = $("#editEmbroideryUnit").val();
    var comments = $("#editEmbroideryComments").val();
    var embroidery_id = $("#edit_embroidery_id_edit_hdn").val();
   
    formData.append('type', type);
    formData.append('rate', rate);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('comments', comments);
    formData.append('id', embroidery_id);
    
    $("#edit-embroidery-spinner").show();
    $("#editEmbroideryBtn_submit,#editEmbroideryBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignembroidery/"+design_id,
        success: function(msg){		
            $("#edit-embroidery-spinner").hide();
            $("#editEmbroideryBtn_submit,#editEmbroideryBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editEmbroidery_');
                    if(errors != ''){
                        $("#editEmbroideryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-embroidery').modal('hide');
                    designDataObject.embroidery_data = msg.embroidery_data;
                    displayTabData('embroidery');
                    $("#editEmbroideryImg_delete_hdn").val(0);
                }
            }else{
                displayResponseError(msg,"editEmbroideryErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editEmbroideryErrorMessage").html('Error in processing request').show();
            $("#edit-embroidery-spinner").hide();
            $("#editEmbroideryBtn_submit,#editEmbroideryBtn_cancel").attr('disabled',false);
        }
    });
});





$("#addPrintingFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    var type = $("#addPrintingType").val();
    var rate = $("#addPrintingRate").val();
    var cost = $("#addPrintingCost").val();
    var unit_id = $("#addPrintingUnit").val();
    var comments = $("#addPrintingComments").val();
   
    formData.append('type', type);
    formData.append('rate', rate);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('comments', comments);
    
    $("#add-printing-spinner").show();
    $("#addPrintingBtn_submit,#addPrintingBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/adddesignprinting/"+design_id,
        success: function(msg){		
            $("#add-printing-spinner").hide();
            $("#addPrintingBtn_submit,#addPrintingBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_addPrinting_');
                    if(errors != ''){
                        $("#addPrintingErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#add-design-printing').modal('hide');
                    $("#addPrintingErrorMessage").hide();
                    designDataObject.printing_data = msg.printing_data;
                    displayTabData('printing');
                }
            }else{
                displayResponseError(msg,"addPrintingErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#addPrintingErrorMessage").html('Error in processing request').show();
            $("#add-printing-spinner").hide();
            $("#addPrintingBtn_submit,#addPrintingBtn_cancel").attr('disabled',false);
        }
    });
});

function addDesignPrinting(id){
    $("#addPrintingFrm").submit();
}

function editDesignPrinting(id){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var printing_data = designDataObject.printing_data;
    var printing_data_row = '';
    for(var i=0;i<printing_data.length;i++){
        if(printing_data[i].id == id){
            printing_data_row = printing_data[i];
        }
    }
    
    $("#editPrintingType").val(printing_data_row.name_id);
    $("#editPrintingRate").val(printing_data_row.rate);
    $("#editPrintingCost").val(printing_data_row.cost);
    $("#editPrintingUnit").val(printing_data_row.unit_id);
    $("#edit_printing_id_edit_hdn").val(printing_data_row.id);
    $("#editPrintingComments").val(printing_data_row.comments);
    $("#editPrintingImage").val("");
    $("#addPrintingErrorMessage,#editPrintingErrorMessage").hide();
   
    if(printing_data_row.image_name != null && printing_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+printing_data_row.image_name;
        $("#editPrintingImg").attr("src",img_url).show();
        $("#editPrintingImg_delete_link").show();
    }else{
        $("#editPrintingImg").attr("src",'').hide();
        $("#editPrintingImg_delete_link").hide();
    }
    
    $('#edit-design-printing').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function updateDesignPrinting(){
    $("#editPrintingFrm").submit();
}

$("#editPrintingFrm").on('submit', function(event){
    event.preventDefault();
    
    var formData = new FormData(this);
    var type = $("#editPrintingType").val();
    var rate = $("#editPrintingRate").val();
    var cost = $("#editPrintingCost").val();
    var unit_id = $("#editPrintingUnit").val();
    var comments = $("#editPrintingComments").val();
    var printing_id = $("#edit_printing_id_edit_hdn").val();
   
    formData.append('type', type);
    formData.append('rate', rate);
    formData.append('cost', cost);
    formData.append('unit_id', unit_id);
    formData.append('comments', comments);
    formData.append('id', printing_id);
    
    $("#edit-printing-spinner").show();
    $("#editPrintingBtn_submit,#editPrintingBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesignprinting/"+design_id,
        success: function(msg){		
            $("#edit-printing-spinner").hide();
            $("#editPrintingBtn_submit,#editPrintingBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editPrinting_');
                    if(errors != ''){
                        $("#editPrintingErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-printing').modal('hide');
                    designDataObject.printing_data = msg.printing_data;
                    displayTabData('printing');
                    $("#editPrintingImg_delete_hdn").val(0);
                }
            }else{
                displayResponseError(msg,"editPrintingErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editPrintingErrorMessage").html('Error in processing request').show();
            $("#edit-printing-spinner").hide();
            $("#editPrintingBtn_submit,#editPrintingBtn_cancel").attr('disabled',false);
        }
    });
});

function editDesignGarmentCmt(id,name){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    $(".invalid-feedback").html('').hide();
    
    var garment_cmt_data = designDataObject.garment_cmt_data;
    var garment_cmt_data_row = '';
    for(var i=0;i<garment_cmt_data.length;i++){
        if(garment_cmt_data[i].id == id){
            garment_cmt_data_row = garment_cmt_data[i];
        }
    }
    
    $("#editGarmentCmtName").val(garment_cmt_data_row.garment_cmt_name);
    $("#editGarmentCmtCost").val(garment_cmt_data_row.cost);
    $("#editGarmentCmtComments").val(garment_cmt_data_row.comments);
    
    $("#editGarmentCmtRate").val(garment_cmt_data_row.rate);
    $("#garment_cmt_id_edit_hdn").val(garment_cmt_data_row.id);
    $("#editGarmentCmtImage").val("");
    
    if(name.toLowerCase() == 'margin'){
        $("#editGarmentCmtUnit").val('%');
        $("#garment_cmt_cost_div").hide();
    }else{
        $("#editGarmentCmtUnit").val('PCS');
        $("#garment_cmt_cost_div").show();
    }
    
    if(garment_cmt_data_row.image_name != null && garment_cmt_data_row.image_name!= ''){
        var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+garment_cmt_data_row.image_name;
        $("#editGarmentCmtImg").attr("src",img_url).show();
        $("#editGarmentCmtImg_delete_link").show();
    }else{
        $("#editGarmentCmtImg").attr("src",'').hide();
        $("#editGarmentCmtImg_delete_link").hide();
    }
    
    $('#edit-design-garment_cmt').modal({
        backdrop: 'static',
        keyboard: false
    })
 }
 
 $("#editGarmentCmtFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var cost = $("#editGarmentCmtCost").val();
    var rate = $("#editGarmentCmtRate").val();
    //var qty = $("#editPackagingSheetQty").val();
    var garment_cmt_id = $("#garment_cmt_id_edit_hdn").val();
    var comments = $("#editGarmentCmtComments").val();
    var delete_image = $("#editGarmentCmtImg_delete_hdn").val();
    
    var formData = new FormData(this);
    formData.append('cost', cost);
    formData.append('id', garment_cmt_id);
    formData.append('comments', comments);
    //formData.append('qty', qty);
    formData.append('rate', rate);
    formData.append('delete_image', delete_image);
    
    $("#edit-GarmentCmt-spinner").show();
    $("#editGarmentCmtBtn_submit,#editGarmentCmtBtn_cancel").attr('disabled',true);
    $(".invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/design/updatedesigngarmentcmt/"+design_id,
        success: function(msg){		
            $("#edit-GarmentCmt-spinner").hide();
            $("#editGarmentCmtBtn_submit,#editGarmentCmtBtn_cancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_editGarmentCmt_');
                    if(errors != ''){
                        $("#editGarmentCmtErrorMessage").html(errors).show();
                    } 
                }else{ 
                    displayStatusText(msg.message,'success');
                    $('#edit-design-garment_cmt').modal('hide');
                    designDataObject.garment_cmt_data = msg.garment_cmt_data;
                    displayTabData('garment_cmt');
                }
            }else{
                displayResponseError(msg,"editGarmentCmtErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#editGarmentCmtErrorMessage").html('Error in processing request').show();
            $("#edit-GarmentCmt-spinner").hide();
            $("#editGarmentCmtBtn_submit,#editGarmentCmtBtn_cancel").attr('disabled',false);
        }
    });
});
 
function updateDesignGarmentCmt(id){
    $("#editGarmentCmtFrm").submit();
}

function deleteProductProcess(){
    if(!checkRequisitionStatus()) return false;
    var chk_class = 'product_process-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#error_delete_rows").modal('show');
        return false;
    }
        
    $("#deleteErrorMessage").html('').hide();
    
    $('#confirm_delete_rows').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_rows_btn', function(e) {
        e.preventDefault();
        $("#delete-rows-spinner").show();
        $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray},
            url:ROOT_PATH+"/design/deletedesignproductprocess/"+design_id,
            success: function(msg){	
                $("#delete-rows-spinner").hide();
                $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        displayStatusText(msg.message,'success');
                        designDataObject.product_process_data = msg.product_process_data;
                        displayTabData('product_process');
                        $('#confirm_delete_rows').modal('hide');
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

function deleteSpecificationSheet(){
    if(!checkRequisitionStatus()) return false;
    var chk_class = 'specification_sheet-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#error_delete_rows").modal('show');
        return false;
    }
        
    $("#deleteErrorMessage").html('').hide();
    
    $('#confirm_delete_rows').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#delete_rows_btn', function(e) {
        e.preventDefault();
        $("#delete-rows-spinner").show();
        $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{deleteChk:deleteChkArray},
            url:ROOT_PATH+"/design/deletedesignspecificationsheet/"+design_id,
            success: function(msg){	
                $("#delete-rows-spinner").hide();
                $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){            
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        displayStatusText(msg.message,'success');
                        designDataObject.specification_sheet_data = msg.specification_sheet_data;
                        displayTabData('specification_sheet');
                        $('#confirm_delete_rows').modal('hide');
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

function deleteRows(type){
    if(!checkRequisitionStatus()){
        $("#saveStatusDialogTitle").html("Error");
        $("#saveStatusDialogContent").html("Design Review status is "+designDataObject.design_data.design_review+" ");
        $("#status_updated_dialog").modal('show');
        return false;
    } 
    var chk_class = type+'-chk';
    var deleteChkArray = $('.'+chk_class).map(function(){ if($(this).is(":checked") ) return $(this).val(); }).get();

    if(deleteChkArray.length == 0){
        $("#error_delete_rows").modal('show');
        return false;
    }
        
    $("#deleteErrorMessage").html('').hide();
    
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
            data:{deleteChk:deleteChkArray,type:type},
            
            url:ROOT_PATH+"/design/deletedesignitem/"+design_id,
            success: function(msg){	
                $("#delete-rows-spinner").hide();
                $("#delete_rows_btn,#delete_rows_cancel").attr('disabled',false);
                if(objectPropertyExists(msg,'status')){                
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        displayStatusText(msg.message,'success');
                        if(type == 'fabric'){
                            designDataObject.fabric_data = msg.fabric_data;
                            designDataObject.process_data = msg.process_data;
                            displayTabData('fabric');
                        }else if(type == 'accessories'){
                            designDataObject.accessories_data = msg.accessories_data;
                            displayTabData('accessories');
                        }else if(type == 'process'){
                            designDataObject.process_data = msg.process_data;
                            displayTabData('process');
                        }else if(type == 'packaging_sheet'){
                            designDataObject.packaging_sheet_data = msg.packaging_sheet_data;
                            displayTabData('packaging_sheet');
                        }else if(type == 'product_process'){
                            designDataObject.product_process_data = msg.product_process_data;
                            displayTabData('product_process');
                        }else if(type == 'embroidery'){
                            designDataObject.embroidery_data = msg.embroidery_data;
                            displayTabData('embroidery');
                        }else if(type == 'printing'){
                            designDataObject.printing_data = msg.printing_data;
                            displayTabData('printing');
                        }

                        $('#confirm_delete_rows').modal('hide');
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

function getProductData(pid,type_id,elem,sel_id){
    if(pid == ''){
        $("#"+elem).html('<option value="">Select One</option>');
        return false;
    }
    
    var form_data = "type_id="+type_id;
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:form_data,
        url:ROOT_PATH+"/design/getproductdata/"+pid,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){             
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#errortatusMessage").html(errors).show();
                    } 
                }else{ 
                    var elements_list = msg.elements_list;//alert(elements_list);
                    var data_str = '<option value="">Select One</option>';
                    var sel = '';
                    for(var i=0;i<elements_list.length;i++){
                        if(sel_id > 0 && sel_id == elements_list[i].id) sel = 'selected';else sel = '';
                        data_str+='<option '+sel+' value="'+elements_list[i].id+'">'+elements_list[i].name+'</option>';
                    }
                    $("#"+elem).html(data_str);
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

function getAccessorySubcategories(category_id,elem_id,sel_id){
    if(category_id == ''){
        $("#"+elem_id).html('<option value="">Select One</option>');
        return false;
    }
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        data:{category_id:category_id},
        url:ROOT_PATH+"/design/getaccessoriessubcategories/"+category_id,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#errortatusMessage").html(errors).show();
                    } 
                }else{ 
                    var sub_category_list = msg.sub_category_list;
                    var str = '<option value="">Select One</option>';
                    var sel = '';
                    for(var i=0;i<sub_category_list.length;i++){
                        if(sel_id > 0 && sel_id == sub_category_list[i].id) sel = 'selected';else sel = '';
                        str+='<option '+sel+' value="'+sub_category_list[i].id+'">'+sub_category_list[i].name+'</option>';
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

function getAccessorySize(subcategory_id,elem_id,sel_id){
    if(subcategory_id == ''){
        $("#"+elem_id).html('<option value="">Select One</option>');
        return false;
    }
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        url:ROOT_PATH+"/design/getaccessoriessize/"+subcategory_id,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#errortatusMessage").html(errors).show();
                    } 
                }else{ 
                    var size_list = msg.size_list;
                    var str = '<option value="">Select One</option>';
                    var sel = '';
                    for(var i=0;i<size_list.length;i++){
                        if(sel_id > 0 && sel_id == size_list[i].id) sel = 'selected';else sel = '';
                        str+='<option '+sel+' value="'+size_list[i].id+'">'+size_list[i].name+'</option>';
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

function getProcessDesigns(type,sel_id){
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:{type_id:1},
        url:ROOT_PATH+"/design/getdesignlookupitems/"+design_id,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#errortatusMessage").html(errors).show();
                    } 
                }else{ 
                    var lookup_items = msg.lookup_items;
                    var str = '<option value="">Select One</option>';
                    var sel = '';
                    for(var i=0;i<lookup_items.length;i++){
                        if(sel_id > 0 && sel_id == lookup_items[i].id) sel = 'selected';else sel = '';
                        var name = lookup_items[i].item_name+", "+lookup_items[i].color_name+", "+lookup_items[i].content_name+", "+lookup_items[i].gsm_name+", "+lookup_items[i].width_name;
                        str+='<option '+sel+' value="'+lookup_items[i].id+"__"+lookup_items[i].avg+'">'+name+'</option>';
                    }
                    $("#"+type+"ProcessFabric").html(str);
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

function displayStatusText(str,type){
    var text_type = (type == 'error')?'danger':'success';
    $("#requirementsMessage").html('<div class="alert alert-'+text_type+' alert-dismissible alert-top-header"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'+str+'</div>').hide();
    $("#requirementsMessage").fadeIn('slow');
    setTimeout(function(){  $("#requirementsMessage").fadeOut('slow'); }, 5000);
}

function checkAllCheckboxes(elem,type){

    if($(elem).is(':checked')){
        $("."+type+"-chk").prop("checked", true);
    }else{
        $("."+type+"-chk").prop("checked", false);
    }
}

function getDesignReviews(type){
    
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/design/getdesignreviewslist/"+design_id+"/"+type,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'design_reviews')){
                var design_reviews = msg.design_reviews;
                var str = '';
                str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
                <th>Version</th><th>Status</th><th>Comment</th><th>Date Added</th></tr></thead><tbody>';

                for(var i=0;i<design_reviews.length;i++){
                    var dt = new Date(design_reviews[i].created_at);
                    var date_str = dt.getDate()  + "/" + (dt.getMonth()+1) + "/" + dt.getFullYear() + " " +dt.getHours() + ":" + dt.getMinutes();
                    str+='<tr><td>'+design_reviews[i].version+'</td><td>'+design_reviews[i].design_status+'</td>\
                    <td>'+design_reviews[i].comment+'</td><td>'+date_str+'</td></tr>';
                }

                if(design_reviews.length == 0){ str+='<tr><td colspan="10">No Records</td></tr>';}
                str+='</tbody></table></div>';

                $("#design_reviews_content").html(str);
                var type_text = (type == 'design')?'Design':'Production';
                $("#designReviewsDialogTitle").html(type_text+' Reviews');
                $("#design_reviews_dialog").modal('show');
            }else{
                displayResponseError(msg,"designReviewsErrorMessage");
            }
        },error:function(obj,status,error){
            $("#designReviewsErrorMessage").html(error).show();
        }
    });
}

function deleteDesignItemImage(img,elem){
    $(elem).hide();
    $("#"+img).attr('src',"").hide();
    $("#"+img+"_delete_hdn").val(1);
}

function updateDesignItemImage(img){
    $("#"+img+"_delete_hdn").val(0);
}

$(".design-sel").change(function(){
    updateArticle();
})

function updateArticle(){
    var sku = '';
    if($("#season_id").val() != '' && $("#product_id").val() != '' && $("#category_id").val() != ''){
        sku = 'K';
        var season_text = $("#season_id option:selected").text();
        sku+=season_text.substring(0,1);
        
        var season_arr = season_text.split('-');
        sku+=season_arr[1];
        
        sku+=$("#category_id option:selected").text().substring(0,2);
        sku+=$("#product_id option:selected").text().substring(0,1);
        
        sku+=design_id;
    }
    
    $("#sku").val(sku);
}

function getSizeVariations(id){
    if(!checkRequisitionStatus()) return false;
    $("#sizeVariationSuccessMessage,#sizeVariationErrorMessage").html("").hide();
    $("#design_size_variation_dialog").modal('show');
    $("#size_var_inst_id").val(id);
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        url:ROOT_PATH+"/design/getsizevariations/"+id,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#sizeVariationErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var size_list = msg.size_list;
                    var id_str = '';
                    var str = '<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr><th>Size</th><th>Variation Type</th><th>Variation Value</th></tr></thead>';
                    for(var i=0;i<size_list.length;i++){
                        var size_id = size_list[i].dlm_id;

                        var select_str = '<select class="form-control" name="variation_type_'+size_id+'" id="variation_type_'+size_id+'"><option value="">Select One</option>';
                        if(size_list[i].variation_type != null && size_list[i].variation_type == 'percent') var variation_type_percent = 'selected';else var variation_type_percent = '';
                        if(size_list[i].variation_type != null && size_list[i].variation_type == 'absolute') var variation_type_absolute = 'selected';else var variation_type_absolute = '';
                        select_str+='<option value="percent" '+variation_type_percent+'>Percent</option><option value="absolute" '+variation_type_absolute+'>Absolute</option></select>';
                        if(size_list[i].variation_value != null) var var_value = size_list[i].variation_value;else var_value = '';
                        var variation_value = '<input type="text" name="variation_value_'+size_id+'" id="variation_value_'+size_id+'" value="'+var_value+'" class="form-control">';

                        str+='<tr><td>'+size_list[i].size_id_name+'</td><td>'+select_str+'</td><td>'+variation_value+'</td></tr>';
                        id_str+=size_id+",";
                    }
                    str+='</table></div>';
                    $("#design_size_variation_content").html(str);
                    $("#size_var_size_ids").val(id_str.substring(0,id_str.length-1));
                }
            }else{
                displayResponseError(msg,"sizeVariationErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#sizeVariationErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateSizeVariation(){
    var form_data = $("#SizeVariationForm").serialize();
    $("#edit-SizeVariation-spinner").show();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/design/updatesizevariationdata/"+design_id,
        method:"POST",
        data:form_data,
        success:function(data){
            $("#edit-SizeVariation-spinner").hide();
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#sizeVariationSuccessMessage").html(data.message).show();
                    $("#sizeVariationErrorMessage").html('').hide();
                    setTimeout(function(){  $("#design_size_variation_dialog").modal('hide'); }, 2000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#sizeVariationErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"sizeVariationErrorMessage");
            }
        },error:function(obj,status,error){
            $("#sizeVariationErrorMessage").html(error).show();
            $("#edit-SizeVariation-spinner").hide();
        }
    });
}

function updateFabricNameData(elem_value,elem_type,select_default,obj){
    var fabric_name_id = elem_value;
    if(fabric_name_id == ''){
        $("#"+elem_type+"FabricQuality,#"+elem_type+"FabricWidth,#"+elem_type+"FabricContent,#"+elem_type+"FabricCount,#"+elem_type+"FabricGSM").html('<option value="">Select One</option>');
        return false;
    }
    var form_data = "pid="+fabric_name_id;
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/design/getlookupitemsdata",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#"+elem_type+"FabricErrorMessage").html('').hide();
            if(objectPropertyExists(data,'status')){
                var options_arr = [];
                if(data.status == 'success'){
                    var design_lookup_items = data.design_lookup_items;
                    for(var i=0;i<design_lookup_items.length;i++){
                        if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_quality'){
                            //options_arr['quality']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_width'){
                            options_arr['width']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_content'){
                            options_arr['content']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_count'){
                            //options_arr['count']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }else if(design_lookup_items[i]['type'].toLowerCase() == 'fabric_gsm'){
                            options_arr['gsm']+='<option value="'+design_lookup_items[i]['id']+'">'+design_lookup_items[i]['name']+'</option>';
                        }
                    }

                    //$("#"+elem_type+"FabricQuality").html('<option value="">Select One</option>'+options_arr['quality']);
                    $("#"+elem_type+"FabricWidth").html('<option value="">Select One</option>'+options_arr['width']);
                    $("#"+elem_type+"FabricContent").html('<option value="">Select One</option>'+options_arr['content']);
                    //$("#"+elem_type+"FabricCount").html('<option value="">Select One</option>'+options_arr['count']);
                    $("#"+elem_type+"FabricGSM").html('<option value="">Select One</option>'+options_arr['gsm']);

                    if(select_default == 1){
                        //$("#"+elem_type+"FabricQuality").val(obj.quality_id);
                        $("#"+elem_type+"FabricWidth").val(obj.width_id);
                        $("#"+elem_type+"FabricContent").val(obj.content_id);
                        //$("#"+elem_type+"FabricCount").val(obj.count_id);
                        $("#"+elem_type+"FabricGSM").val(obj.gsm_id);
                    }
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#"+elem_type+"FabricErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,elem_type+"FabricErrorMessage");
            }
        },error:function(obj,status,error){
            $("#"+elem_type+"FabricErrorMessage").html("Error in processing request").show();
        }
    });
}

function updateProductProcessForm(){
    return false;
    
}

function updateAccessoriesCost(type){
    var rate = $("#"+type+"AccessoriesRate").val();
    var quantity = $("#"+type+"AccessoriesQuantity").val();
    if(isNaN(rate) || isNaN(quantity)){
        $("#"+type+"AccessoriesCost").val(0);
    }else{
        var cost = (rate*quantity);
        $("#"+type+"AccessoriesCost").val(cost);
    }
}

function updateFabricCost(type){
    var rate = $("#"+type+"FabricRate").val();
    var avg = $("#"+type+"FabricAvg").val();
    if(isNaN(rate) || isNaN(avg)){
        $("#"+type+"FabricCost").val(0);
    }else{
        var cost = (rate*avg);
        $("#"+type+"FabricCost").val(cost);
    }
}

function updateProcessCost(type){
    var fabric_id = $("#"+type+"ProcessFabric").val();
    var rate = $("#"+type+"ProcessRate").val();
    if(fabric_id == '' || isNaN(rate)){
       $("#"+type+"ProcessCost").val(0);
    }else{
        var fabric_id_arr = fabric_id.split('__');
        var avg = fabric_id_arr[1];
        var cost = (rate*avg);
        $("#"+type+"ProcessAvg").val(avg);
        $("#"+type+"ProcessCost").val(cost);
    }
}

function updateProcessCostWithAvg(type){
    var avg = $("#"+type+"ProcessAvg").val();
    var rate = $("#"+type+"ProcessRate").val();
    if(isNaN(avg) || isNaN(rate)){
       $("#"+type+"ProcessCost").val(0);
    }else{
        var cost = (rate*avg);
        $("#"+type+"ProcessCost").val(cost);
    }
}

function updatePackagingSheetCost(type){
    var rate = $("#"+type+"PackagingSheetRate").val();
    var avg = $("#"+type+"PackagingSheetQty").val();
    if(isNaN(rate) || isNaN(avg)){
        $("#"+type+"PackagingSheetCost").val(0);
    }else{
        var cost = (rate*avg);
        $("#"+type+"PackagingSheetCost").val(cost);
    }
}

function updateEmbroideryCost(type){
    var rate = $("#"+type+"EmbroideryRate").val();
    if(isNaN(rate)){
       $("#"+type+"EmbroideryCost").val(0);
    }else{
        var cost = (rate);
        $("#"+type+"EmbroideryCost").val(cost);
    }
}

function updatePrintingCost(type){
    var rate = $("#"+type+"PrintingRate").val();
    if(isNaN(rate)){
       $("#"+type+"PrintingCost").val(0);
    }else{
        var cost = (rate);
        $("#"+type+"PrintingCost").val(cost);
    }
}

function updateGarmentCmtCost(type){
    var rate = $("#"+type+"GarmentCmtRate").val();
    if(isNaN(rate)){
       $("#"+type+"GarmentCmtCost").val(0);
    }else{
        var cost = (rate);
        $("#"+type+"GarmentCmtCost").val(cost);
    }
}

function getDesignTotalCost(design_id,version,history_type){
    var url = ROOT_PATH+"/design/getdesigntotalcost/"+design_id;
    if(version != ''){
        url+="/"+version;
    }
    if(history_type != ''){
        url+="/"+history_type;
    }
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        url:url,
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#errortatusMessage").html(errors).show();
                    } 
                }else{ 
                    $("#fabric_cost_td").html(currency+" "+msg.fabric_cost);
                    $("#accessories_cost_td").html(currency+" "+msg.accessories_cost);
                    $("#fabric_process_cost_td").html(currency+" "+msg.process_cost);
                    $("#prod_process_cost_td").html(currency+" "+msg.prod_process_cost);
                    $("#total_cost_td").html(currency+" "+msg.total_cost);
                    $("#gst_td").html(currency+" "+msg.gst_amount+" ("+msg.gst_percent+"%)");
                    $("#net_cost_td").html(currency+" "+msg.net_cost);
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

function getDesignProductSubcategories(category_id,elem_id,sel_id){
    if(category_id == ''){
        $("#"+elem_id).html('<option value="">Select One</option>');
        return false;
    }
    
    var form_data = "pid="+category_id+"&type=POS_PRODUCT_SUBCATEGORY"
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:form_data,
        url:ROOT_PATH+"/design/getlookupitemsdata",
        success: function(msg){		
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#"+type+"PosProductErrorMessage").html(errors).show();
                } 
            }else{ 
                var sub_category_list = msg.design_lookup_items;
                var str = '<option value="">Select One</option>';
                var sel = '';
                for(var i=0;i<sub_category_list.length;i++){
                    if(sel_id > 0 && sel_id == sub_category_list[i].id) sel = 'selected';else sel = '';
                    str+='<option '+sel+' value="'+sub_category_list[i].id+'">'+sub_category_list[i].name+'</option>';
                }
                $("#"+elem_id).html(str);
            }
        },
        error:function(obj,status,error){
            $("#errortatusMessage").html('Error in processing request').show();
        }
    });
}

function getDesignProductHsnCode(category_id,elem_id){
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        data:{category_id:category_id},
        url:ROOT_PATH+"/pos/product/list?action=get_prod_hsn_code",
        success: function(msg){		
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#"+type+"PosProductErrorMessage").html(errors).show();
                } 
            }else{ 
                var hsn_code = msg.hsn_code;
                $("#"+elem_id).val(hsn_code);
            }
        },
        error:function(obj,status,error){
            $("#"+type+"PosProductErrorMessage").html('Error in processing request').show();
        }
    });
}