"use strict";

function editLookupItem(id){
    $("#edit_lookup_item_dialog").modal('show');
    $("#lookup_item_edit_id").val(id);
    $("#editLookupItemSuccessMessage,#editLookupItemErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/lookup-item/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editLookupItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#itemName_edit").val(msg.item_data.name);
                    $("#description_edit").val(msg.item_data.description);
                    $("#itemType_edit").val(msg.item_data.type.toLowerCase());
                    if(msg.item_data.api_data == 1){
                        $("#api_data_edit").prop("checked", true);
                    }else{
                        $("#api_data_edit").prop("checked", false);
                    }
                    
                    getParentItems(msg.item_data.type.toLowerCase(),'edit',msg.item_data.pid);
                }
            }else{
                displayResponseError(msg,"editLookupItemErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editLookupItemErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateLookupItem(){
    var form_data = $("#editLookupItemFrm").serialize();
    $("#lookup_item_edit_spinner").show();
    $("#editLookupItemSuccessMessage,#editLookupItemErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/lookup-item/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#lookup_item_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#editLookupItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editLookupItemSuccessMessage").html(msg.message).show();
                    $("#editLookupItemErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_lookup_item_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"editLookupItemErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editLookupItemErrorMessage").html('Error in processing request').show();
            $("#lookup_item_edit_spinner").hide();
        }
    });
}

function addLookupItem(){
    $("#addLookupItemSuccessMessage,#addLookupItemErrorMessage,.invalid-feedback").html('').hide();
    $("#itemName_add,#itemType_add").val('');
    $("#add_lookup_item_dialog").modal('show');
}

function submitAddLookupItem(){
    var form_data = $("#addLookupItemFrm").serialize();
    $("#lookup_item_add_spinner").show();
    $("#addLookupItemSuccessMessage,#addLookupItemErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/lookup-item/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#lookup_item_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_');
                    if(errors != ''){
                        $("#addLookupItemErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addLookupItemSuccessMessage").html(msg.message).show();
                    $("#addLookupItemErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_lookup_item_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"addLookupItemErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addLookupItemErrorMessage").html('Error in processing request').show();
            $("#lookup_item_add_spinner").hide();
        }
    });
}

function updateLookupItemStatus(){
    $("#itemsListOverlay").show();
    var item_ids = '';
    $(".item-list").each(function(){
        if($(this).is(":checked")){
            item_ids+= $(this).val()+",";
        }
    });
    
    item_ids = item_ids.substring(0,item_ids.length-1);
    var form_data = "action="+$("#item_action").val()+"&ids="+item_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/lookup-item/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#itemsListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateLookupItemsStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateLookupItemsStatusSuccessMessage").html(msg.message).show();
                    $("#updateLookupItemsStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#itemsListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateLookupItemsStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateLookupItemsStatusErrorMessage").html('Error in processing request').show();
            $("#itemsListOverlay").hide();
        }
    });
}

function selectAllLookupItems(elem){
    if($(elem).is(":checked")){
        $(".item-list").each(function(){
            $(this).prop("checked", true);
        });
    }else{
        $(".item-list").each(function(){
            $(this).prop("checked", false);
        });
    }
}

function getParentItems(item_type,elem_type,sel_id){
    if(item_type == ''){
        $("#itemTypeParent_"+elem_type).html('<option value="">Select One</option>');
        return false;
    }
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:{item_type:item_type},
        url:ROOT_PATH+"/lookup-item/parentitemslist",
        success: function(msg){		
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateLookupItemsStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    var parent_items_list = msg.items_list;
                    if(parent_items_list.length > 0){
                        var str = '<option value="">Select One</option>';
                        var sel = '';
                        for(var i=0;i<parent_items_list.length;i++){
                            if(sel_id > 0 && sel_id == parent_items_list[i].id) sel = 'selected';else sel = '';
                            str+='<option '+sel+' value="'+parent_items_list[i].id+'">'+parent_items_list[i].name+'</option>';
                        }
                        $("#itemTypeParentDiv_"+elem_type).slideDown("slow");
                        $("#itemTypeParent_"+elem_type).html(str);
                    }else{
                        $("#itemTypeParentDiv_"+elem_type).slideUp("slow");
                    }
                }
            }else{
                displayResponseError(msg,"updateLookupItemsStatusErrorMessage");
            }
        },
        error:function(obj,status,error){
            $("#updateLookupItemsStatusErrorMessage").html('Error in processing request').show();
        }
    });
    
    if(item_type == 'color' || item_type == 'pos_product_category'){
        $(".website-display-div").show();
    }else{
        $(".website-display-div").hide();
    }
}