"use strict";

function editCoupon(id){
    $("#edit_coupon_dialog").modal('show');
    $("#coupon_edit_id").val(id);
    $("#editCouponSuccessMessage,#editCouponErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/coupon/list?action=get_coupon_data&id="+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editCouponErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#couponName_edit").val(msg.coupon_data.coupon_name);
                    $("#couponItemsCount_edit").val(msg.coupon_data.items_count);
                    $("#couponStore_edit").val(msg.coupon_data.store_id);
                    $("#couponValidFrom_edit").val(displayDate(msg.coupon_data.valid_from));
                    $("#couponValidTo_edit").val(displayDate(msg.coupon_data.valid_to));
                    $("#couponStatus_edit").val(msg.coupon_data.status);
                    $("#couponDiscount_edit").val(msg.coupon_data.discount);
                    $("#couponType_edit").val(msg.coupon_data.coupon_type);
                }
            }else{
                displayResponseError(msg,"editCouponErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editCouponErrorMessage").html('Error in processing request').show();
        }
    });
}

function submitEditCoupon(){
    var form_data = $("#editCouponFrm").serialize();
    $("#coupon_edit_spinner").show();
    $("#editCouponSuccessMessage,#editCouponErrorMessage,.invalid-feedback").html('').hide();
    $("#couponEdit_cancel,#couponEdit_submit").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/coupon/list?action=update_coupon",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#couponEdit_cancel,#couponEdit_submit").attr('disabled',false);
            $("#coupon_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editCouponErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editCouponSuccessMessage").html(msg.message).show();
                    $("#editCouponErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_coupon_dialog").modal('hide');window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"editCouponErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editCouponErrorMessage").html('Error in processing request').show();
            $("#coupon_edit_spinner").hide();
            $("#couponEdit_cancel,#couponEdit_submit").attr('disabled',false);
        }
    });
}

function addCoupon(){
    $("#addStoreSuccessMessage,#addStoreErrorMessage,.invalid-feedback").html('').hide();
    $("#couponName_add,#couponItemsCount_add,#couponStore_add,#couponValidFrom_add,#couponValidTo_add,#couponType_add,#couponDiscount_add").val('');
    $("#add_coupon_dialog").modal('show');
}

function submitAddCoupon(){
    var form_data = $("#addCouponFrm").serialize();
    $("#coupon_add_spinner").show();
    $("#addCouponSuccessMessage,#addCouponErrorMessage,.invalid-feedback").html('').hide();
    $("#couponAdd_submit,#couponAdd_cancel").attr('disabled',true);
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/coupon/list?action=add_coupon",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#couponAdd_submit,#couponAdd_cancel").attr('disabled',false);
            $("#coupon_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addCouponErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addCouponSuccessMessage").html(msg.message).show();
                    $("#addCouponErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_coupon_dialog").modal('hide');window.location.reload(); }, 800);
                }
            }else{
                displayResponseError(msg,"addStoreErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addCouponErrorMessage").html('Error in processing request').show();
            $("#coupon_add_spinner").hide();
            $("#couponAdd_submit,#couponAdd_cancel").attr('disabled',false);
        }
    });
}