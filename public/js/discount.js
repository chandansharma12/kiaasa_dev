"use strict";

function discountType(selectedDiscount){     
    if(selectedDiscount ==1){
        $('#flatPriceDiv').hide();
        $('#minItemsDiv').hide();            
        $('#maxItemsDiv').hide();
           $('#percentDiscountDiv').show();

    }else if(selectedDiscount ==2){
        $('#flatPriceDiv').show();
         $('#minItemsDiv').hide();            
        $('#maxItemsDiv').hide();    $('#percentDiscountDiv').hide();
    }else if(selectedDiscount ==3){
        $('#minItemsDiv').show();            
        $('#maxItemsDiv').show();
        $('#flatPriceDiv').hide();
    } else if(selectedDiscount ==4){
        $('#minItemsDiv').hide();            
        $('#maxItemsDiv').hide();
        $('#flatPriceDiv').hide();
    }        
}

$("#discountsListOverlay").hide();
    
function updateDiscountStatus(){
    $("#discountsListOverlay").show();
    var discount_ids = '';
    $(".discount-list-chk").each(function(){
        if($(this).is(":checked")){
            discount_ids+= $(this).val()+",";
        }
    });
    
    discount_ids = discount_ids.substring(0,discount_ids.length-1);
    var form_data = "action=delete&ids="+discount_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#discountsListOverlay").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateDiscountStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateDiscountStatusSuccessMessage").html(msg.message).show();
                    $("#updateDiscountStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#usersListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'updateDiscountStatusErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateDiscountStatusErrorMessage").html('Error in processing request').show();
            $("#discountsListOverlay").hide();
        }
    });
}

function addDiscount(){
    $("#addDiscountDialog .form-control").val('');
    $("#gst_including").val(1);
    $("#addDiscountDialog").modal('show');
}

function submitAddDiscount(){
    var form_data = $("#addDiscountForm").serialize();
    $("#addDiscountBtn,#addDiscountCancel").attr('disabled',true);
    $(".invalid-feedback").hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#addDiscountBtn,#addDiscountCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addDiscountSuccessMessage").html(msg.message).show();
                    $("#addDiscountErrorMessage").html('').hide();
                    setTimeout(function(){ $("#addDiscountDialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'updateDiscountStatusErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addDiscountBtn,#addDiscountCancel").attr('disabled',false);
            $("#addDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateDiscountSKUList(){
    var form_data = $("#addDiscountForm").serialize();
    $("#product_sku").html('<option value="">Loading...</option>');
    $(".invalid-feedback").hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/list?action=filter_sku_list",
        method:"GET",
        data:form_data,
        success:function(msg){
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#addDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addDiscountErrorMessage").html('').hide();
                    var sku_list = msg.product_sku;
                    var option_str = '<option value="">--SKU--</option>';
                    for(var i=0;i<sku_list.length;i++){
                        option_str+='<option value="'+sku_list[i].product_sku+'">'+sku_list[i].product_sku+'</option>';
                    }
                    $("#product_sku").html(option_str);
                }
            }else{
                displayResponseError(msg,'addDiscountErrorMessage');
            }
        },error:function(obj,status,error){
            
            $("#addDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}

function searchAddDiscountSKU(){
    var sku = $("#sku_search").val().toUpperCase();
    if(sku != ''){
        $("#product_sku").val(sku);
    }
    
    return false;
}

function addGroupDiscount(type){
    $("#addDiscountDialog .form-control").val('');
    $("#div_multiple_add,#div_single_add").addClass('elem-hidden');
    $("#div_"+type+"_add").removeClass('elem-hidden');
    $("#discount_type_add").val(type);
    $("#addDiscountDialog").modal('show');
}

function submitAddGroupDiscount(){
    var form_data = $("#addDiscountForm").serialize();
    $("#addDiscountBtn,#addDiscountCancel").attr('disabled',true);
    $(".invalid-feedback").hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discounts/list?action=add_discount",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#addDiscountBtn,#addDiscountCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addDiscountSuccessMessage").html(msg.message).show();
                    $("#addDiscountErrorMessage").html('').hide();
                    setTimeout(function(){ $("#addDiscountDialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'addDiscountErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addDiscountBtn,#addDiscountCancel").attr('disabled',false);
            $("#addDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}

function editGroupDiscount(id,type){
    $("#editDiscountDialog .form-control").val('');
    $("#editDiscountDialog").modal('show');
    $("#div_multiple_edit,#div_single_edit").addClass('elem-hidden');
    $("#div_"+type+"_edit").removeClass('elem-hidden');
    $("#discount_type_edit").val(type);
    $("#discount_id_edit").val(id);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discounts/list?action=get_discount_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoreErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var discount_data = msg.discount_data; 
                    
                    $("#gst_type_edit").val(discount_data.gst_type);
                    $("#discount_edit").val(discount_data.discount);
                    $("#buy_items_edit").val(discount_data.buy_items);
                    $("#get_items_edit").val(discount_data.get_items);
                }
            }else{
                displayResponseError(msg,"editStoreErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoreErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditGroupDiscount(){
    var form_data = $("#editDiscountForm").serialize();
    $("#editDiscountBtn,#editDiscountCancel").attr('disabled',true);
    $(".invalid-feedback").hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discounts/list?action=edit_discount",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#editDiscountBtn,#editDiscountCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editDiscountSuccessMessage").html(msg.message).show();
                    $("#editDiscountErrorMessage").html('').hide();
                    setTimeout(function(){ $("#editDiscountDialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'editDiscountErrorMessage');
            }
        },error:function(obj,status,error){
            $("#editDiscountBtn,#editDiscountCancel").attr('disabled',false);
            $("#editDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}

var multipleDiscountSKUList = [];

function searchAddMultipleDiscountSKU(){
    var sku = $("#sku_search").val();
    if(sku == ''){
        $("#searchMultipleDiscountSKUErrorMessage").html('SKU is Required Field').show();
        return false;
    }
   
    if(multipleDiscountSKUList.length > 0 && multipleDiscountSKUList.indexOf(sku) >= 0){
        $("#searchMultipleDiscountSKUErrorMessage").html('SKU is already added').show();
        return false;
    }
    
    if(multipleDiscountSKUList.length >= 50){
        $("#searchMultipleDiscountSKUErrorMessage").html('Maximum 50 SKUs can be added').show();
        return false;
    }
    
    $("#searchMultipleDiscountSKUErrorMessage").html('').hide();
    $("#skuSearchBtn").attr('disabled',true);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/multiple/add?action=get_sku_data",
        method:"POST",
        data:{sku:sku},
        success:function(msg){
            $("#skuSearchBtn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    if(msg.errors != ''){
                        $("#searchMultipleDiscountSKUErrorMessage").html(msg.errors).show();
                    } 
                }else{ 
                    $("#searchMultipleDiscountSKUErrorMessage").html('').hide();
                    multipleDiscountSKUList.push(msg.product_data.product_sku);
                    displayMultipleDiscountSKU();
                    
                    $("#sku_search").val('').focus();
                }
            }else{
                displayResponseError(msg,'searchMultipleDiscountSKUErrorMessage');
            }
        },error:function(obj,status,error){
            $("#skuSearchBtn").attr('disabled',false);
            $("#searchMultipleDiscountSKUErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayMultipleDiscountSKU(){
    var sku_str = '';
    
    sku_str = '<tr>';
    for(var i=0;i<multipleDiscountSKUList.length;i++){
        sku_str+='<td><input type="checkbox" class="sku-list-chk" name="sku_list_data" id="sku_list_data" value="'+multipleDiscountSKUList[i]+'"> '+multipleDiscountSKUList[i]+'</td>';
        if(i > 0 && (i+1)%8 == 0){
            sku_str+='</tr><tr>';
        }
    }

    sku_str+='</tr>';
    $("#sku_list_table").html(sku_str);
}

function addMultipleDiscountSKU(){
    $("#addMultipleDiscountErrorMessage,#addMultipleDiscountSuccessMessage").html('').hide();
    var form_data = $("#addDiscountForm").serialize();
    var sku_list = '',store_list = '';
    $(".sku-list-chk").each(function(){
        if($(this).is(":checked")){
            sku_list+= $(this).val()+",";
        }
    });
    
    sku_list = sku_list.substring(0,sku_list.length-1);//alert(sku_list);
    
    $(".store-list-chk").each(function(){
        if($(this).is(":checked")){
            store_list+= $(this).val()+",";
        }
    });
    
    store_list = store_list.substring(0,store_list.length-1);
    
    form_data+="&sku_list="+encodeURIComponent(sku_list)+"&store_list="+store_list;
    
    $("#addDiscountBtn").attr('disabled',true);
    $(".invalid-feedback").hide();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/multiple/add?action=add_multiple_discount",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#addDiscountBtn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addMultipleDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addMultipleDiscountSuccessMessage").html(msg.message).show();
                    $("#addMultipleDiscountErrorMessage").html('').hide();
                    setTimeout(function(){ window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,'addMultipleDiscountErrorMessage');
            }
        },error:function(obj,status,error){
            $("#addDiscountBtn").attr('disabled',false);
            $("#addMultipleDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}

function deleteAddMultipleDiscountSKU(){
    $(".sku-list-chk").each(function(){
        if($(this).is(":checked")){
            var sku = $(this).val();
            const index = multipleDiscountSKUList.indexOf(sku);
            if (index > -1) {
              multipleDiscountSKUList.splice(index, 1);
            }
        }
    });
    
    displayMultipleDiscountSKU();
}

function checkDiscount(){
    $("#checkDiscountDialog .form-control").val('');
    $("#checkDiscountErrorMessage,#checkDiscountSuccessMessage").html('').hide();
    $("#checkDiscountBtn").attr('disabled',false);
    $(".check-discount-tbl td").html('');
    $("#checkDiscountDialog").modal('show');
}

function submitCheckDiscount(){
    var qr_code = $("#check_discount_qr_code").val();
    $(".check-discount-tbl td").html('');
    $("#checkDiscountBtn").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/list?action=check_discount",
        method:"GET",
        data:{qr_code:qr_code},
        success:function(msg){
            $("#checkDiscountBtn").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#checkDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#checkDiscountErrorMessage").html('').hide();
                    //$("#checkDiscountSuccessMessage").html(msg.message).show();
                    var product_data = msg.product_data;
                    $("#prod_name_check_disc").html(product_data.product_name+" "+product_data.size_name+" "+product_data.color_name);
                    $("#sku_check_disc").html(product_data.product_sku);
                    $("#category_check_disc").html(product_data.category_name);
                    $("#subcategory_check_disc").html(product_data.subcategory_name);
                    $("#hsn_code_check_disc").html(product_data.hsn_code);
                    
                    $("#mrp_check_disc").html(product_data.sale_price);
                    $("#disc_percent_check_disc").html(product_data.discount_percent+" %");
                    $("#disc_amount_check_disc").html(product_data.discount_amount);
                    $("#discounted_price_check_disc").html(product_data.discounted_price);
                    $("#gst_check_disc").html(product_data.gst_amount+" ("+product_data.gst_percent+" %)" );
                    var gst_type = (product_data.gst_inclusive == 1)?'Inclusive':'Exclusive';
                    $("#gst_type_check_disc").html(gst_type);
                    $("#net_price_check_disc").html(product_data.net_price);
                    setTimeout(function(){  }, 1000);
                }
            }else{
                displayResponseError(msg,'checkDiscountErrorMessage');
            }
        },error:function(obj,status,error){
            $("#checkDiscountBtn").attr('disabled',false);
            $("#checkDiscountErrorMessage").html('Error in processing request').show();
        }
    });
}

function importMultipleDiscountSKU(){
    $("#uploadSKUCsvFile").val('');
    $("#uploadSKUCSVErrorMessage,#uploadSKUCSVSuccessMessage").html('').hide();
    $("#uploadSKUCsvDialog").modal('show');
}

function submitImportMultipleDiscountSKU(){
    $("#uploadSKUCsvForm").submit();
}

$("#uploadSKUCsvForm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    $("#upload_sku_csv_spinner").show();
    $("#updateSKUCsvBtn,#updateSKUCsvCancel").attr('disabled',true);
    $("#uploadSKUCSVErrorMessage,#uploadSKUCSVSuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/discount/multiple/add?action=import_sku",
        success: function(msg){		
            $("#upload_sku_csv_spinner").hide();
            $("#updateSKUCsvBtn,#updateSKUCsvCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#uploadSKUCSVErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#uploadSKUCSVSuccessMessage").html(msg.message).show();
                    var sku_list = msg.sku_list;
                    for(var i=0;i<sku_list.length;i++){
                        var sku = sku_list[i];
                        if(multipleDiscountSKUList.indexOf(sku) < 0 && multipleDiscountSKUList.length < 50){
                            multipleDiscountSKUList.push(sku);
                        }
                    }
                    displayMultipleDiscountSKU();
                    setTimeout(function(){  $("#uploadSKUCsvDialog").modal('hide'); }, 1500);
                }
            }else{
                displayResponseError(msg,"uploadSKUCSVErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#uploadSKUCSVErrorMessage").html('Error in processing request').show();
            $("#upload_sku_csv_spinner").hide();
            $("#updateSKUCsvBtn,#updateSKUCsvCancel").attr('disabled',false);
        }
    });
});

function updateMultipleDiscountStoresByZone(zone_id){
    $("#updateStoresErrorMessage").html('').hide();
    var zone_id = $("#zone_id").val();
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/discount/multiple/add?action=get_zone_stores",
        method:"POST",
        data:{zone_id:zone_id},
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#updateStoresErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateStoresErrorMessage").html('').hide();
                    var stores_list = msg.stores_list;
                    var stores_str = '<tr>';
    
                    for(var i=0;i<stores_list.length;i++){
                        stores_str+='<td><input type="checkbox" class="store-list-chk" name="store_list" id="store_list" value="'+stores_list[i].id+'"> '+stores_list[i].store_name+'</td>';
                        if(i > 0 && (i+1)%8 == 0){
                            stores_str+='</tr><tr>';
                        }
                    }

                    stores_str+='</tr>';
                    
                    if(stores_list.length == 0){
                        stores_str = '<tr><td>No Records</td></tr>';
                    }
                    $("#store_list_table").html(stores_str);
                }
            }else{
                displayResponseError(msg,'updateStoresErrorMessage');
            }
        },error:function(obj,status,error){
            $("#updateStoresErrorMessage").html('Error in processing request').show();
        }
    });
}

function downloadDiscount(){
    $("#downloadDiscountDialog .form-control").val('');
    $("#error_validation_discount_count").html('').hide();
    $("#downloadDiscountDialog").modal('show');
}

function submitDownloadDiscount(){
    var discount_count = $("#discount_count").val(), str = '';
    $("#error_validation_discount_count").html('').hide();
    if(discount_count == ''){
        $("#error_validation_discount_count").html('Discount Records is Required Field').show();
        return false;
    }
    
    const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);
    
    var url = ROOT_PATH+"/discount/list?action=download_csv&discount_count="+discount_count+"&"+str;
    window.location.href = url;
}

function deleteMultipleDiscount(){
    $("#skuTxtFile").val('');
    $("#deleteMultipleDiscountErrorMessage,#deleteMultipleDiscountSuccessMessage").html('').hide();
    $("#deleteMultipleDiscountDialog").modal('show');
}

function submitDeleteMultipleDiscount(){
    $("#deleteMultipleDiscountForm").submit();
}

$("#deleteMultipleDiscountForm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
   
    $("#deleteMultipleDiscountSpinner").show();
    $("#deleteMultipleDiscountCancel,#deleteMultipleDiscountSubmit").attr('disabled',true);
    $("#deleteMultipleDiscountErrorMessage,#deleteMultipleDiscountSuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        method:"POST",
        data:formData,
        dataType:'JSON',
        contentType: false,
        cache: false,
        processData: false,
        url:ROOT_PATH+"/discount/multiple/delete",
        success: function(msg){		
            $("#deleteMultipleDiscountSpinner").hide();
            $("#deleteMultipleDiscountCancel,#deleteMultipleDiscountSubmit").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#deleteMultipleDiscountErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#deleteMultipleDiscountSuccessMessage").html(msg.message).show();
                    setTimeout(function(){  $("#deleteMultipleDiscountDialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"deleteMultipleDiscountErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#deleteMultipleDiscountErrorMessage").html('Error in processing request').show();
            $("#deleteMultipleDiscountSpinner").hide();
            $("#deleteMultipleDiscountCancel,#deleteMultipleDiscountSubmit").attr('disabled',false);
        }
    });
});