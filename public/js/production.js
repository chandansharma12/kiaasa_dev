"use strict";

function editProductionCount(id,cnt){
    $("#edit-design-production_count").modal('show');
    $("#edit_production_count_design_id_edit_hdn").val(id);
    $("#productionCount").val(cnt);
    $("#editDesignProductionCountSuccessMessage,#editDesignProductionCountErrorMessage,.invalid-feedback").html('').hide();
}

function updateDesignproductionCount(){
    var form_data = "production_count="+$("#productionCount").val();
    $("#edit-productionCount-spinner").show();
    var design_id = $("#edit_production_count_design_id_edit_hdn").val();
    
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/production/updatedesignproductioncount/"+design_id,
        method:"POST",
        data:form_data,
        success:function(data){
            $("#edit-productionCount-spinner").hide();
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#editDesignProductionCountSuccessMessage").html(data.message).show();
                    $("#editDesignProductionCountErrorMessage").html('').hide();
                    setTimeout(function(){  $("#edit-design-production_count").modal('hide'); window.location.reload(); }, 2000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#editDesignProductionCountErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"editDesignProductionCountErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editDesignProductionCountErrorMessage").html(error).show();
            $("#edit-productionCount-spinner").hide();
        }
    });
}

function requestQuotationBySKU(){
    var id_str = '';
    $(".design_id-chk").each(function(){
        if($(this).is(":checked")){
            id_str+=$(this).val()+",";
        }
    });
    
    if(id_str == ''){
        $("#error_request_quotation_sku_dialog").modal('show');
        return false;
    }
    
    id_str = id_str.substring(0,id_str.length-1);
    $("#design_ids").val(id_str);
    $("#sku_quotation_form").submit();
}

function requestSKUQuotation(){
    $("#skuQuotationErrorMessage,#skuQuotationSuccessMessage").html('').hide();
    var prod_process_ids = '',pack_sheet_ids = '', prod_process_qty = '', pack_sheet_qty = '';
    
    $(".prod_process_id-chk").each(function(){
        if($(this).is(":checked")){
            prod_process_ids+=$(this).val()+",";
            prod_process_qty+=$("#prod_process_qty_"+$(this).val()).val()+",";
        }
    });
    
    $(".pack_sheet_id-chk").each(function(){
        if($(this).is(":checked")){
            pack_sheet_ids+=$(this).val()+",";
            pack_sheet_qty+=$("#pack_sheet_qty_"+$(this).val()).val()+",";
        }
    });
    
    prod_process_ids = prod_process_ids.substring(0,prod_process_ids.length-1);
    prod_process_qty = prod_process_qty.substring(0,prod_process_qty.length-1);
    
    pack_sheet_ids = pack_sheet_ids.substring(0,pack_sheet_ids.length-1);
    pack_sheet_qty = pack_sheet_qty.substring(0,pack_sheet_qty.length-1);
    
    var vendor_ids = $("#vendor_ids").val();
    if(vendor_ids == '' || vendor_ids == null){
        $("#skuQuotationErrorMessage").html('Please select vendors').show();
        return false;
    }
    
    $("#sku_quotation_spinner").show();
    $("#sku_quotation_btn").attr('disabled',true);
    
    var form_data = "prod_process_ids="+prod_process_ids+"&prod_process_qty="+prod_process_qty+"&pack_sheet_ids="+pack_sheet_ids+"&pack_sheet_qty="+pack_sheet_qty+"&vendor_ids="+vendor_ids;
   
    ajaxSetup();    
    $.ajax({
        url:ROOT_PATH+"/production/addquotation",
        method:"POST",
        data:form_data,
        success:function(data){
            $("#sku_quotation_spinner").hide();
            $("#sku_quotation_btn").attr('disabled',false);
            
            if(objectPropertyExists(data,'status')){
                if(data.status == 'success'){
                    $("#skuQuotationSuccessMessage").html(data.message).show();
                    $("#skuQuotationErrorMessage").html('').hide();
                }else{    
                    var errors = getResponseErrors(data,'<br/>','');
                    if(errors != ''){
                        $("#skuQuotationErrorMessage").html(errors).show();
                    } 
                }
            }else{
                displayResponseError(data,"skuQuotationErrorMessage");
            }
        },error:function(obj,status,error){
            $("#skuQuotationErrorMessage").html(error).show();
            $("#sku_quotation_spinner").hide();
            $("#sku_quotation_btn").attr('disabled',false);
            
        }
    });
}