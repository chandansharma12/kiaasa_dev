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
                $("#gst_inclusive_edit").val(msg.product_data.gst_inclusive);
                $("#vendor_product_sku_edit").val(msg.product_data.vendor_product_sku);
                if(msg.product_data.product_type == 'sor'){
                    $(".prod-size-add-div").show();
                }else{
                    $(".prod-size-add-div").hide();
                }
                
                displayProductImages(msg);
                
                var size_list = msg.size_list,option_str = '<option value="">Select Size</option>';
                for(var i=0;i<size_list.length;i++){
                    option_str+='<option value="'+size_list[i].id+'">'+size_list[i].size+'</option>';
                }
                $("#product_other_size_add_edit").html(option_str);
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
    
    var front_image_exists = false,back_image_exists = false,close_image_exists = false;
    for(var i=0;i<msg.product_images.length;i++){
        if(msg.product_images[i].image_type == 'front'){
            front_image_exists = true;
        }
        if(msg.product_images[i].image_type == 'back'){
            back_image_exists = true;
        }
        if(msg.product_images[i].image_type == 'close'){
            close_image_exists = true;
        }
    }
    
    if(!front_image_exists){
        str+='<div class="form-group col-md-4 image-div-edit" ><label>Front</label><div class="separator-10"></div><img src="" class="list-design-image pull-left"><div class="separator-10"></div><input type="file" name="product_image_front_edit" id="product_image_front_edit" class="form-control"><div class="invalid-feedback" id="error_validation_product_image_front_edit"></div></div>';
    }
    if(!back_image_exists){
        str+='<div class="form-group col-md-4 image-div-edit" ><label>Back</label><div class="separator-10"></div><img src="" class="list-design-image pull-left"><div class="separator-10"></div><input type="file" name="product_image_back_edit" id="product_image_back_edit" class="form-control"><div class="invalid-feedback" id="error_validation_product_image_back_edit"></div></div>';
    }
    if(!close_image_exists){
        str+='<div class="form-group col-md-4 image-div-edit" ><label>Close</label><div class="separator-10"></div><img src="" class="list-design-image pull-left"><div class="separator-10"></div><input type="file" name="product_image_close_edit" id="product_image_close_edit" class="form-control"><div class="invalid-feedback" id="error_validation_product_image_close_edit"></div></div>';
    }
    
    for(var i=0;i<msg.product_images.length;i++){
        var img_url = ROOT_PATH+'/images/pos_product_images/'+msg.product_images[i].product_id+"/"+msg.product_images[i].image_name;
        //str+='<tr><td><img src="'+img_url+'" class="list-design-image"> &nbsp;<a href="javascript:;" title="Delete" onclick="deleteProductImage('+msg.product_images[i].id+')"><i class="fas fa-trash"></i></a></td></tr>';
        var image_type = msg.product_images[i].image_type;//alert(image_type);
        
        if(images_array.indexOf(image_type) >= 0){
            str+='<div class="form-group col-md-4 image-div-edit" ><label>'+image_type+'</label><div class="separator-10"></div><img src="'+img_url+'" class="list-design-image pull-left">&nbsp;<a style="float:left;margin-left:5px;margin-top:5px;" href="javascript:;" title="Delete" onclick="deleteProductImage('+msg.product_images[i].id+')"><i class="fas fa-trash"></i></a><div class="separator-10"></div><input type="file" name="product_image_'+image_type+'_edit" id="product_image_'+image_type+'_edit" class="form-control"><div class="invalid-feedback" id="error_validation_product_image_'+image_type+'_edit"></div></div>';
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
        url:ROOT_PATH+"/pos/product/add",
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
                        //setTimeout(function(){  window.location.reload(); }, 1000);
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

/*function displayBarcodeCSVForm(){
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
    formData.append('po_id',$("#po_id").val());
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
*/

function updateBarcodesByScheduler(){
    $("#barcodeCsvFile").val('');
    $("#uploadBarcodeCSVSuccessMessage,#uploadBarcodeCSVErrorMessage").html('').hide();
    $("#uploadBarcodeCsvDialog").modal('show');
}

function submitUpdateBarcodesByScheduler(){
    $("#barcodeCsvForm").submit();
}

$("#barcodeCsvForm").on('submit', function(event){
    event.preventDefault(); 
    
    var formData = new FormData(this);
    formData.append('po_id',$("#po_id").val());
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
        url:ROOT_PATH+"/scheduler/add/pos/product/barcodes",
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
                    setTimeout(function(){ $("#uploadBarcodeCsvDialog").modal('hide'); window.location.reload(); }, 1000);
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

function getInventoryBarcodes(type){
    var product_ids = '';
    $(".pos_product-list-chk").each(function(){
        if($(this).is(":checked")){
            product_ids+= $(this).val()+",";
        }
    });
    
    product_ids = product_ids.substring(0,product_ids.length-1);
    var no_discount = $("#no_discount_chk").is(":checked")?1:0;
    var url = ROOT_PATH+"/pos/product/inventory/barcodes/list?id_str="+product_ids+"&type="+type+"&no_discount="+no_discount;
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

function addOtherSizePosProduct(){
   $("#product_other_size_add_submit").attr("disabled",true);
   var product_id = $("#product_edit_id").val();
   var size_id = $("#product_other_size_add_edit").val();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/list?action=add_other_size_product",
        method:"GET",
        data:{product_id:product_id,size_id:size_id},
        success:function(msg){
            $("#product_other_size_add_submit").attr("disabled",false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#editPosProductErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#editPosProductSuccessMessage").html(msg.message).show();
                $("#editPosProductErrorMessage").html('').hide();
                setTimeout(function(){  window.location.reload(); }, 1000);
            }
        },error:function(obj,status,error){
            $("#editPosProductErrorMessage").html('Error in processing request').show();
            $("#product_other_size_add_submit").attr("disabled",false);
        }
    });
}

function searchDebitNotes(){
    var debit_note_no = $("#debit_note_no").val();
    if(debit_note_no == ''){
       $("#searchDebitNoteForm").submit(); 
    }
    
    $("#search_btn").attr("disabled",true);
    $("#searchDebitNoteErrorMessage,#searchDebitNoteSuccessMessage").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/debit/notes/list?action=search_debit_note_no",
        method:"GET",
        data:{debit_note_no:debit_note_no},
        success:function(msg){
            $("#search_btn").attr("disabled",false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#searchDebitNoteErrorMessage").html(errors).show();
                } 
            }else{ 
                var debit_note_data = msg.debit_note_data;
                var type_id = debit_note_data.type_id;
                var debit_note_id = debit_note_data.debit_note_id;
                if(debit_note_data != null && typeof debit_note_data.debit_note_id != 'undefined'){
                    var url = ROOT_PATH+"/debit/notes/list?debit_note_no="+debit_note_no+"&type_id="+type_id+"&debit_note_id="+debit_note_id;
                    window.location.href = url;
                }else{
                    $("#searchDebitNoteErrorMessage").html(debit_note_no+' - Debit Note does not exists').show();
                }
            }
        },error:function(obj,status,error){
            $("#searchDebitNoteErrorMessage").html('Error in processing request').show();
            $("#search_btn").attr("disabled",false);
        }
    });
}

function editInventory(){
    $("#editInventoryForm .form-control").val('');
    $("#editInventoryErrorMessage,#editInventorySuccessMessage,.invalid-feedback").html('').hide();
    $("#editInventoryDialog").modal('show');
}

function submitEditInventory(){
   $("#editInventoryBtn,#editInventoryCancel").attr("disabled",true);
   $("#editInventoryErrorMessage,#editInventorySuccessMessage,.invalid-feedback").html('').hide();
   $("#edit_inventory_spinner").show();
   var po_id_edit_inv = $("#po_id_edit_inv").val();
   var sku_edit_inv = $("#sku_edit_inv").val();
   var mrp_edit_inv = $("#mrp_edit_inv").val();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/inventory/list?action=edit_inventory",
        method:"POST",
        data:{po_id_edit_inv:po_id_edit_inv,sku_edit_inv:sku_edit_inv,mrp_edit_inv:mrp_edit_inv},
        success:function(msg){
            $("#editInventoryBtn,#editInventoryCancel").attr("disabled",false);
            $("#edit_inventory_spinner").hide();
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#editInventoryErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#editInventorySuccessMessage").html(msg.message).show();
                $("#editInventoryErrorMessage").html('').hide();
                setTimeout(function(){  window.location.reload(); }, 1000);
            }
        },error:function(obj,status,error){
            $("#editInventoryErrorMessage").html('Error in processing request').show();
            $("#editInventoryBtn,#editInventoryCancel").attr("disabled",false);
            $("#edit_inventory_spinner").hide();
        }
    });
}

function searchCreditNotes(){
    var credit_note_no = $("#credit_note_no").val();
    if(credit_note_no == ''){
       $("#searchCreditNoteForm").submit(); 
    }
    
    $("#search_btn").attr("disabled",true);
    $("#searchCreditNoteErrorMessage,#searchCreditNoteSuccessMessage").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/credit/notes/list?action=search_credit_note_no",
        method:"GET",
        data:{credit_note_no:credit_note_no},
        success:function(msg){
            $("#search_btn").attr("disabled",false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#searchCreditNoteErrorMessage").html(errors).show();
                } 
            }else{ 
                var credit_note_data = msg.credit_note_data;
                var type_id = credit_note_data.type_id;
                var credit_note_id = credit_note_data.credit_note_id;
                if(credit_note_data != null && typeof credit_note_data.credit_note_id != 'undefined'){
                    var url = ROOT_PATH+"/credit/notes/list?credit_note_no="+credit_note_no+"&type_id="+type_id+"&credit_note_id="+credit_note_id;
                    window.location.href = url;
                }else{
                    $("#searchCreditNoteErrorMessage").html(credit_note_no+' - Credit Note does not exists').show();
                }
            }
        },error:function(obj,status,error){
            $("#searchCreditNoteErrorMessage").html('Error in processing request').show();
            $("#search_btn").attr("disabled",false);
        }
    });
}

function addStaticPosProduct(){
    $("#addStaticPosProductSuccessMessage,#addStaticPosProductErrorMessage,.invalid-feedback").html('').hide();
    $("#add_static_pos_product_dialog").find('.form-control').val('');
    $("#add_static_pos_product_dialog").modal('show');
}

function submitAddStaticPosProduct(){
    $("#static_pos_product_add_cancel,#static_pos_product_add_submit").attr('disabled',true);
    $("#addStaticPosProductErrorMessage,#addStaticPosProductSuccessMessage,.invalid-feedback").html('').hide();
    var form_data = $("#addStaticPosProductFrm").serialize();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/static/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#static_pos_product_add_cancel,#static_pos_product_add_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addStaticPosProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addStaticPosProductErrorMessage").html('').hide();
                    $("#addStaticPosProductSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#add_static_pos_product_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addStaticPosProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#static_pos_product_add_cancel,#static_pos_product_add_submit").attr('disabled',false);
            $("#addStaticPosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function editStaticPosProduct(prod_id){
    $("#editStaticPosProductSuccessMessage,#editStaticPosProductErrorMessage,.invalid-feedback").html('').hide();
    $("#edit_static_pos_product_dialog").find('.form-control').val('');
    $("#edit_static_pos_product_dialog").modal('show');
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/data/"+prod_id,
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#editStaticPosProductErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#static_product_name_edit").val(msg.product_data.product_name);
                $("#static_product_barcode_edit").val(msg.product_data.product_barcode);
                $("#static_product_sku_edit").val(msg.product_data.product_sku);
                $("#static_product_base_price_edit").val(msg.product_data.base_price);
                $("#static_product_sale_price_edit").val(msg.product_data.sale_price);
                //$("#product_description_edit").val(msg.product_data.product_description);
                $("#static_product_category_edit").val(msg.product_data.category_id);
                $("#static_size_id_edit").val(msg.product_data.size_id);
                getStaticPosProductSubcategories(msg.product_data.category_id,'edit',msg.product_data.subcategory_id);
                
                $("#static_product_color_edit").val(msg.product_data.color_id);
                
                $("#static_story_id_edit").val(msg.product_data.story_id);
                $("#static_season_id_edit").val(msg.product_data.season_id);
                $("#static_product_hsn_code_edit").val(msg.product_data.hsn_code);
                $("#static_product_id_edit").val(msg.product_data.id);
            }
        },error:function(obj,status,error){
            $("#editStaticPosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditStaticPosProduct(){
    $("#static_pos_product_edit_cancel,#static_pos_product_edit_submit").attr('disabled',true);
    $("#editStaticPosProductErrorMessage,#editStaticPosProductSuccessMessage,.invalid-feedback").html('').hide();
    var form_data = $("#editStaticPosProductFrm").serialize();
    var prod_id = $("#static_product_id_edit").val();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/pos/product/static/update/"+prod_id,
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#static_pos_product_edit_cancel,#static_pos_product_edit_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){    
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editStaticPosProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStaticPosProductErrorMessage").html('').hide();
                    $("#editStaticPosProductSuccessMessage").html(msg.message).show();
                    setTimeout(function(){ $("#edit_static_pos_product_dialog").modal('hide'); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editStaticPosProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#static_pos_product_edit_cancel,#static_pos_product_edit_submit").attr('disabled',false);
            $("#editStaticPosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function getStaticPosProductSubcategories(category_id,type_id,sel_id){
    if(category_id == ''){
        $("#static_product_subcategory_"+type_id).html('<option value="">Select One</option>');
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
                $("#static_product_subcategory_"+type_id).html(str);
            }
        },
        error:function(obj,status,error){
            $("#"+type+"StaticPosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function getStaticPosProductHsnCode(category_id,type_id){
    
    ajaxSetup();
    $.ajax({
        type: "GET",
        data:{category_id:category_id},
        url:ROOT_PATH+"/pos/product/list?action=get_prod_hsn_code",
        success: function(msg){		
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#"+type+"StaticPosProductErrorMessage").html(errors).show();
                } 
            }else{ 
                var hsn_code = msg.hsn_code;
                $("#static_product_hsn_code_"+type_id).val(hsn_code);
            }
        },
        error:function(obj,status,error){
            $("#"+type+"StaticPosProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function downloadInventory(){
    $("#downloadInventoryDialog .form-control").val('');
    $("#error_validation_inv_count").html('').hide();
    $("#downloadInventoryDialog").modal('show');
}

function submitDownloadInventory(){
    var inv_count = $("#inv_count").val(), str = '';
    $("#error_validation_inv_count").html('').hide();
    if(inv_count == ''){
        $("#error_validation_inv_count").html('Inventory Records is Required Field').show();
        return false;
    }
    
    const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);

    var url = ROOT_PATH+"/pos/product/inventory/list?action=download_csv&inv_count="+inv_count+"&"+str;
    window.location.href = url;
}

function downloadPosProducts(){
    $("#downloadPosProductDialog .form-control").val('');
    $("#error_validation_pos_product_count").html('').hide();
    $("#downloadPosProductDialog").modal('show');
}

function submitDownloadPosProducts(){
    var pos_product_count = $("#pos_product_count").val(), str = '';
    $("#error_validation_pos_product_count").html('').hide();
    if(pos_product_count == ''){
        $("#error_validation_pos_product_count").html('Inventory Records is Required Field').show();
        return false;
    }
    
    const urlParams = new URLSearchParams(location.search);
    for (const [key, value] of urlParams) {
        str+=key+"="+value+"&";
    }
    
    str = str.substring(0,str.length-1);

    var url = ROOT_PATH+"/pos/product/list?action=download_csv&pos_product_count="+pos_product_count+"&"+str;
    window.location.href = url;
}

function downloadHSNCodeBillSalesReport(){
    /*var store_id = $("#s_id").val();
    var start_date = $("#startDate").val();
    var end_date = $("#endDate").val();
    var url = ROOT_PATH+"/report/hsn/bill/sales?action=download_csv&s_id="+store_id+"&startDate="+start_date+"&endDate="+end_date;
    window.location.href = url;*/
    
    if($("#store_list_div_download").length > 0){ 
        $("#store_list_div_download").html($("#store_list_div").html());
    }
    $("#date_range_div_download").html($("#date_range_div").html());
    $('.input-daterange').datepicker({format: 'dd-mm-yyyy'});
    $("#report_download_dialog").modal('show');
}

function submitDownloadHSNCodeBillSalesReport(){
    var str = '';
    if($("#report_download_dialog #startDate").val() != '' && $("#report_download_dialog #endDate").val() != ''){
        str+="&startDate="+$("#report_download_dialog #startDate").val()+"&endDate="+$("#report_download_dialog #endDate").val();
    }
    
    if($("#store_list_div_download").length > 0 && $("#report_download_dialog #s_id").val() != ''){
        str+="&s_id="+$("#report_download_dialog #s_id").val()
    }
    
    var url = ROOT_PATH+"/report/hsn/bill/sales?action=download_csv&"+str;
    window.location.href = url;
}

function addPageDescription(){
    $("#addPageDescSuccessMessage,#addPageDescErrorMessage,.invalid-feedback").html('').hide();
    $("#addPageDescFrm .form-control").val('');
    $("#desc_add_dialog").modal('show');
}

function submitAddPageDescription(){
    var form_data = $("#addPageDescFrm").serialize();
    $("#addPageDescSuccessMessage,#addPageDescErrorMessage,.invalid-feedback").html('').hide();
    $("#page_desc_add_cancel,#page_desc_add_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/page/description/list?action=add_page_desc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#page_desc_add_cancel,#page_desc_add_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addPageDescErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addPageDescSuccessMessage").html(msg.message).show();
                    $("#addPageDescErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#desc_add_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"addPageDescErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addPageDescErrorMessage").html('Error in processing request').show();
            $("#page_desc_add_cancel,#page_desc_add_submit").attr('disabled',false);
        }
    });
}

function editPageDescription(id){
    $("#editPageDescSuccessMessage,#editPageDescErrorMessage,.invalid-feedback").html('').hide();
    $("#editPageDescFrm .form-control").val('');
    $("#desc_edit_dialog").modal('show');
    $("#desc_edit_id").val(id);
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/page/description/list?action=get_page_desc&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editPageDescErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#page_name_edit").val(msg.desc_data.page_name);
                    $("#desc_type_edit").val(msg.desc_data.desc_type);
                    $("#desc_name_edit").val(msg.desc_data.desc_name);
                    $("#desc_detail_edit").val(msg.desc_data.desc_detail);
                }
            }else{
                displayResponseError(msg,"editPageDescErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPageDescErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditPageDescription(){
    var form_data = $("#editPageDescFrm").serialize();
    var id = $("#desc_edit_id").val();
    form_data = form_data+"&id="+id;
    $("#editPageDescSuccessMessage,#editPageDescErrorMessage,.invalid-feedback").html('').hide();
    $("#page_desc_edit_cancel,#page_desc_edit_submit").attr('disabled',true);
    ajaxSetup();
    

    $.ajax({
        url:ROOT_PATH+"/page/description/list?action=edit_page_desc",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#page_desc_edit_cancel,#page_desc_edit_submit").attr('disabled',false);
            
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editPageDescErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editPageDescSuccessMessage").html(msg.message).show();
                    $("#editPageDescErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#desc_edit_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editPageDescErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editPageDescErrorMessage").html('Error in processing request').show();
            $("#page_desc_edit_cancel,#page_desc_edit_submit").attr('disabled',false);
        }
    });
}

function submitEditSorProduct(){
    $("#editSorProductFrm").submit();
}

$("#editSorProductFrm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    var product_id = $("#product_id").val();
    $("#product_edit_spinner").show();
    $("#product_edit_cancel,#product_edit_submit").attr('disabled',true);
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
        url:ROOT_PATH+"/designer/sor/product/edit/"+product_id,
        success:function(msg){
            $("#product_edit_spinner").hide();
            $("#product_edit_cancel,#product_edit_submit").attr('disabled',false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#updateSorProductErrorMessage").html(errors).show();
                } 
                
                var image_errors = '';
                for(var i in msg.errors){
                    if(i.indexOf('product_image_edit') >= 0)
                        image_errors+=msg.errors[i]+'<br>';
                }
                $("#picture_error_edit").html(image_errors).show();
            }else{ 
                $("#updateSorProductSuccessMessage").html(msg.message).show();
                $("#updateSorProductErrorMessage,.invalid-feedback").html('').hide();
                setTimeout(function(){  window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#updateSorProductErrorMessage").html('Error in processing request').show();
            $("#product_edit_spinner").hide();
            $("#product_edit_cancel,#product_edit_submit").attr('disabled',false);
        }
    });
});

function deleteSorProductImage(id){
    $("#deleteProductImageErrorMessage,#deleteProductImageSuccessMessage").html('').hide();
    
    $('#confirm_delete_product_image').modal({
        backdrop: 'static',keyboard: false
    }).off('click.confirm')
    .on('click.confirm', '#product_image_btn', function(e) {
        e.preventDefault();
        
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
                        $("#deleteProductImageSuccessMessage").html(msg.message).show();
                        setTimeout(function(){  window.location.reload(); }, 1000);
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


function submitImportStaticPosProducts(){
    $("#importStaticProductsForm").submit();
}

$("#importStaticProductsForm").on('submit', function(event){
    event.preventDefault(); 
    var formData = new FormData(this);
    $("#import_static_product_spinner").show();
    $("#importStaticProductsCancel,#importStaticProductsSubmit").attr('disabled',true);
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
        url:ROOT_PATH+"/pos/product/static/import",
        success:function(msg){
            $("#import_static_product_spinner").hide();
            $("#importStaticProductsCancel,#importStaticProductsSubmit").attr('disabled',false);
            $("#importStaticProductsCsvFile").val('');
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#importStaticProductsErrorMessage").html(errors).show();
                    
                    if(msg.errors_list != null && typeof msg.errors_list != 'undefined'){
                        var errors_list = msg.errors_list;
                        var error_str = '<div class="table-responsive"><table class="table table-striped clearfix admin-table" cellspacing="0" style=font-size:12px;><thead>';
                        error_str+='<tbody><tr class="header-tr"><th>Row</th><th>Product Name</th><th>SKU</th><th>Barcode</th><th>Color</th><th>Size</th><th>Cat</th><th>SubCat</th><th>Story</th><th>Season</th><th>Base Price</th><th>Sale Price</th><th>Errors</th></tr></thead>';

                        for(var i=0;i<errors_list.length;i++){
                            var prod = errors_list[i].product,err_str = '';
                            error_str+='<tr><td>'+errors_list[i].row+'</td><td>'+prod.name+'</td><td>'+prod.sku+'</td><td>'+prod.barcode+'</td><td>'+prod.color_id+'</td><td>'+prod.size_id+'</td><td>'+prod.category_id+'</td><td>'+prod.subcategory_id+'</td><td>'+prod.story_id+'</td><td>'+prod.season_id+'</td><td>'+prod.base_price+'</td><td>'+prod.sale_price+'</td>';

                            for(var q=0;q<errors_list[i].errors.length;q++){
                                err_str+='<li>'+errors_list[i].errors[q].replace('_',' ')+'</li>';
                            }

                            error_str+='<td>'+err_str+'</td></tr>';
                        }

                        error_str+='</tbody></table></div>';

                        $("#errorListTbl").html(error_str).show();
                    }
                } 
            }else{ 
                $("#importStaticProductsSuccessMessage").html(msg.message).show();
                $("#importStaticProductsErrorMessage,.invalid-feedback").html('').hide();
                setTimeout(function(){  window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#importStaticProductsErrorMessage").html('Error in processing request').show();
            $("#import_static_product_spinner").hide();
            $("#importStaticProductsCancel,#importStaticProductsSubmit").attr('disabled',false);
        }
    });
});