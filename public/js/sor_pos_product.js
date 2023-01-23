"use strict";

function editPosProduct(id){
    $("#edit_pos_product_dialog").modal('show');
    $("#product_edit_id").val(id);
    $("#editPosProductSuccessMessage,#editPosProductErrorMessage,.invalid-feedback,#deleteProductImageErrorMessage,#deleteProductImageSuccessMessage").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/data/"+id,
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#editPosProductErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#product_name_edit").val(msg.product_data.product_name);
                $("#product_barcode_edit").val(msg.product_data.product_barcode);
                $("#product_sku_edit").val(msg.product_data.product_sku);
                $("#product_base_price_edit").val(msg.product_data.base_price);
                $("#product_sale_price_edit").val(msg.product_data.sale_price);
                $("#product_description_edit").val(msg.product_data.product_description);
                $("#product_category_edit").val(msg.product_data.category_id);
                $("#size_id_edit").val(msg.product_data.size_id);
                getPosProductSubcategories(msg.product_data.category_id,'edit',msg.product_data.subcategory_id);
                $("#product_image_edit").val('');
                $("#edit_pos_product_dialog").find("#color_id").val(msg.product_data.color_id);
                $("#color_label_edit").html(msg.color_data.name);
                $("#product_sale_category_edit").val(msg.product_data.sale_category);
                $("#story_id_edit").val(msg.product_data.story_id);
                $("#season_id_edit").val(msg.product_data.season_id);
                $("#product_hsn_code_edit").val(msg.product_data.hsn_code);
                displayProductImages(msg);
            }
        },error:function(obj,status,error){
            $("#editPosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function displayProductImages(msg){
    var str = ''; //'<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0" style="border:none;text-align:left;">';
    var images_array = ['front','back','close'];
    $(".product-image-div-edit,.image-div-edit").remove();
    
    for(var i=0;i<msg.product_images.length;i++){
        var img_url = ROOT_PATH+'/images/pos_product_images/'+msg.product_images[i].product_id+"/"+msg.product_images[i].image_name;
        //str+='<tr><td><img src="'+img_url+'" class="list-design-image"> &nbsp;<a href="javascript:;" title="Delete" onclick="deleteProductImage('+msg.product_images[i].id+')"><i class="fas fa-trash"></i></a></td></tr>';
        var image_type = msg.product_images[i].image_type;//alert(image_type);
        
        if(images_array.indexOf(image_type) >= 0){
            str+='<div class="form-group col-md-4 image-div-edit" ><label>'+image_type+'</label><div class="separator-10"></div><img src="'+img_url+'" class="list-design-image pull-left"><div class="separator-10"></div><input type="file" name="product_image_'+image_type+'_edit" id="product_image_'+image_type+'_edit" class="form-control"><div class="invalid-feedback" id="error_validation_product_image_'+image_type+'_edit"></div></div>';
        }else{
            str+='<div class="form-group product-image-div-edit col-md-4" ><label>Picture</label><div class="separator-10"></div><img src="'+img_url+'" class="list-design-image pull-left">&nbsp;<a style="float:left;margin-left:5px;margin-top:5px;" href="javascript:;" title="Delete" onclick="deleteProductImage('+msg.product_images[i].id+')"><i class="fas fa-trash"></i></a><div class="separator-10"></div><input type="file" name="product_image_'+msg.product_images[i].id+'_edit" id="product_image_'+msg.product_images[i].id+'_edit" class="form-control"><div class="invalid-feedback" id="error_validation_product_image_edit"></div></div>';
        }
    }
    //str+='</table></div>';

    //$("#picture_images_list").html(str);
    $(str).insertAfter('#picture_images_list');
}

function updatePosProduct(){
    $("#editPosProductFrm").submit();
}

function addPosProduct(){
    $("#addPosProductSuccessMessage,#addPosProductErrorMessage,.invalid-feedback").html('').hide();
    $("#add_pos_product_dialog").find('.form-control').val('');
    $("#add_pos_product_dialog").find("#color_id").val('');
    $("#color_label_add").html('');
    $("#product_image_add").val('');
    $("#add_pos_product_dialog").modal('show');
}

function submitAddPosProduct(){
    $("#addPosProductFrm").submit();
}

$("#addPosProductFrm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    formData.append('color_id_add', $("#add_pos_product_dialog").find("#color_id").val());
    
    $("#pos_product_add_spinner").show();
    $("#pos_product_add_submit,#pos_product_add_cancel").attr('disabled',true);
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
        url:ROOT_PATH+"/designer/sor/product/add",
        success:function(msg){
            $("#pos_product_add_spinner").hide();
            $("#pos_product_add_submit,#pos_product_add_cancel").attr('disabled',false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                var image_errors = '';
                for(var i in msg.errors){
                    if(i.indexOf('product_image_add') >= 0)
                        image_errors+=msg.errors[i]+'<br>';
                }
                $("#picture_error_add").html(image_errors).show();
                if(errors != ''){
                    $("#addPosProductErrorMessage").html(errors).show();//alert(1);
                } 
            }else{ 
                $("#addPosProductSuccessMessage").html(msg.message).show();
                $("#addPosProductErrorMessage,.invalid-feedback").html('').hide();
                setTimeout(function(){  $("#add_pos_product_dialog").modal('hide');window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#addPosProductErrorMessage").html('Error in processing request').show();
            $("#pos_product_add_spinner").hide();
            $("#pos_product_add_submit,#pos_product_add_cancel").attr('disabled',false);
        }
    });
});

function updatePosProductStatus(){
    $("#posProductListOverlay").show();
    var product_ids = '';
    $(".pos_product-list-chk").each(function(){
        if($(this).is(":checked")){
            product_ids+= $(this).val()+",";
        }
    });
    
    product_ids = product_ids.substring(0,product_ids.length-1);
    var form_data = "action="+$("#pos_product_action").val()+"&ids="+product_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(msg.status == 'fail'){
                $("#posProductListOverlay").hide();
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#updatePosProductStatusErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#updatePosProductStatusSuccessMessage").html(msg.message).show();
                $("#updatePosProductStatusErrorMessage").html('').hide();
                setTimeout(function(){ $("#posProductListOverlay").hide(); window.location.reload(); }, 1000);
            }
        },error:function(obj,status,error){
            $("#updatePosProductStatusErrorMessage").html('Error in processing request').show();
            $("#posProductListOverlay").hide();
        }
    });
}

$("#editPosProductFrm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    formData.append('color_id_edit', $("#edit_pos_product_dialog").find("#color_id").val());
    $("#pos_product_edit_spinner").show();
    $("#pos_product_edit_cancel,#pos_product_edit_submit").attr('disabled',true);
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
        url:ROOT_PATH+"/pos/product/update",
        success:function(msg){
            $("#pos_product_edit_spinner").hide();
            $("#pos_product_edit_cancel,#pos_product_edit_submit").attr('disabled',false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#editPosProductErrorMessage").html(errors).show();
                } 
                
                var image_errors = '';
                for(var i in msg.errors){
                    if(i.indexOf('product_image_edit') >= 0)
                        image_errors+=msg.errors[i]+'<br>';
                }
                $("#picture_error_edit").html(image_errors).show();
            }else{ 
                $("#editPosProductSuccessMessage").html(msg.message).show();
                $("#editPosProductErrorMessage,.invalid-feedback").html('').hide();
                displayProductImages(msg);
                setTimeout(function(){  $("#edit_pos_product_dialog").modal('hide');window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#editPosProductErrorMessage").html('Error in processing request').show();
            $("#pos_product_edit_spinner").hide();
            $("#pos_product_edit_cancel,#pos_product_edit_submit").attr('disabled',false);
        }
    });
});

function getPosProductSubcategories(category_id,type_id,sel_id){
    if(category_id == ''){
        $("#product_subcategory_"+type_id).html('<option value="">Select One</option>');
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
                $("#product_subcategory_"+type_id).html(str);
            }
        },
        error:function(obj,status,error){
            $("#"+type+"PosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function addProductPicture(type){
    if(type == 'add'){
        var html = '<div class="form-group col-md-4" >'+$(".product-image-div-"+type).html()+"</div>";
    }else{
        var html = '<div class="form-group col-md-4" >'+$(".product-image-div-edit-copy").html()+"</div>";
    }
    html = html.replace('Front View','Picture')
    $(".product-image-container-"+type).append(html);
}

function deleteProductImage(id){
    $("#deleteProductImageErrorMessage,#deleteProductImageSuccessMessage").html('').hide();
    
    $('#confirm_delete_product_image').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#product_image_btn', function(e) {
        e.preventDefault();
        //$("#delete-demand_item-spinner").show();
        //$("#delete_demand_item_btn,#delete_demand_item_cancel").attr('disabled',true);
        
        ajaxSetup();        
        $.ajax({
            type: "POST",
            data:{id:id},
            url:ROOT_PATH+"/pos/product/image/delete/"+id,
            success: function(msg){	
                if(objectPropertyExists(msg,'status')){        
                    if(msg.status == 'fail'){
                        var errors = getResponseErrors(msg,'<br/>','');
                        if(errors != ''){
                            $("#deleteProductImageErrorMessage").html(errors).show();
                        } 
                    }else{ 
                        $('#confirm_delete_product_image').modal('hide');
                        displayProductImages(msg);
                        $("#deleteProductImageSuccessMessage").html(msg.message).show();
                    }
                }else{
                    displayResponseError(msg,"deleteProductImageErrorMessage");
                }
            },
            error:function(obj,status,error){
                $("#deleteProductImageErrorMessage").html('Error in processing request').show();
            }
        });
    });
}

loadColorDropdown();

function displayBarcodeCSVForm(){
    $("#barcodeCsvFile").val('');
    $("#uploadBarcodeCSVSuccessMessage,#uploadBarcodeCSVErrorMessage").html('').hide();
    $("#uploadBarcodeCsvDialog").modal('show');
}

function uploadBarcodeCsvFile(){
    $("#barcodeCsvForm").submit();
}

$("#barcodeCsvForm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    //formData.append('category_id', category_id);
    $("#product_barcode_csv_spinner").show();
    $("#uploadCsvBtn,#updateBarcodesCancel").attr('disabled',true);
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
        url:ROOT_PATH+"/pos/product/list?action=upload_barcode_csv",
        success: function(msg){		
            $("#product_barcode_csv_spinner").hide();
            $("#uploadCsvBtn,#updateBarcodesCancel").attr('disabled',false);
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#uploadBarcodeCSVErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#uploadBarcodeCSVSuccessMessage").html(msg.message).show();
                    $("#uploadBarcodeCsvDialog").modal('hide');
                }
            }else{
                displayResponseError(msg,"uploadBarcodeCSVErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#uploadBarcodeCSVErrorMessage").html('Error in processing request').show();
            $("#product_barcode_csv_spinner").hide();
            $("#uploadCsvBtn,#updateBarcodesCancel").attr('disabled',false);
        }
    });
});

function getInventoryBarcodes(){
    var product_ids = '';
    $(".pos_product-list-chk").each(function(){
        if($(this).is(":checked")){
            product_ids+= $(this).val()+",";
        }
    });
    
    product_ids = product_ids.substring(0,product_ids.length-1);
    var url = ROOT_PATH+"/pos/product/inventory/barcodes/list?id_str="+product_ids;
    window.location.href = url;
}

function getPosProductHsnCode(category_id,type_id){
    
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
                $("#product_hsn_code_"+type_id).val(hsn_code);
            }
        },
        error:function(obj,status,error){
            $("#"+type+"PosProductErrorMessage").html('Error in processing request').show();
        }
    });
}