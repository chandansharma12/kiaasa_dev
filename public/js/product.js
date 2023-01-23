"use strict";

function editProduct(id){
    $("#edit_product_dialog").modal('show');
    $("#product_edit_id").val(id);
    $("#editProductSuccessMessage,#editProductErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/product/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#productName_edit").val(msg.product_data.name);

                    $("#productType_edit").val(msg.product_data.type_id);
                    updateProductData(msg.product_data.type_id,'edit');
                    if(msg.product_data.type_id == 2 || msg.product_data.type_id == 3){
                        $("#productParent_edit").val(msg.product_data.parent_id);
                    }else if(msg.product_data.type_id == 4){
                        $("#productCategory_edit").val(msg.product_data.parent_id);
                    }
                }
            }else{
                displayResponseError(msg,"editProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateProduct(){
    var form_data = $("#editProductFrm").serialize();
    $("#product_edit_spinner").show();
    $("#editProductSuccessMessage,#editProductErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/product/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#product_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editProductSuccessMessage").html(msg.message).show();
                    $("#editProductErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_product_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"editProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editProductErrorMessage").html('Error in processing request').show();
            $("#product_edit_spinner").hide();
        }
    });
}

function addProduct(){
    $("#addProductSuccessMessage,#addProductErrorMessage,.invalid-feedback").html('').hide();
    $("#productName_add,#productParent_add").val('');
    $("#add_product_dialog").modal('show');
}

function submitAddProduct(){
    var form_data = $("#addProductFrm").serialize();
    $("#product_add_spinner").show();
    $("#addProductSuccessMessage,#addProductErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/product/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#product_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addProductErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addProductSuccessMessage").html(msg.message).show();
                    $("#addProductErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_product_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"addProductErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addProductErrorMessage").html('Error in processing request').show();
            $("#product_add_spinner").hide();
        }
    });
}

function updateProductStatus(){
    $("#productListOverlay").show();
    var product_ids = '';
    $(".product-list").each(function(){
        if($(this).is(":checked")){
            product_ids+= $(this).val()+",";
        }
    });
    
    product_ids = product_ids.substring(0,product_ids.length-1);
    var form_data = "action="+$("#product_action").val()+"&ids="+product_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/product/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#productListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateProductStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateProductStatusSuccessMessage").html(msg.message).show();
                    $("#updateProductStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#productListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateProductStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateProductStatusErrorMessage").html('Error in processing request').show();
            $("#productListOverlay").hide();
        }
    });
}

function selectAllProduct(elem){
    if($(elem).is(":checked")){
        $(".product-list").each(function(){
            $(this).prop("checked", true);
        });
    }else{
        $(".product-list").each(function(){
            $(this).prop("checked", false);
        });
    }
}

function updateProductData(type_id,type){
    if(type_id == 1){
        $("#parent_product_div_"+type).hide();
        $("#parent_category_div_"+type).hide();
    }else if(type_id == 2 || type_id == 3){
        $("#parent_product_div_"+type).show();
        $("#parent_category_div_"+type).hide();
    }else if(type_id == 4){
        $("#parent_product_div_"+type).hide();
        $("#parent_category_div_"+type).show();
    }
}