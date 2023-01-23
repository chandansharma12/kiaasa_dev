"use strict";

function editStory(id){
    $("#edit_story_dialog").modal('show');
    $("#story_edit_id").val(id);
    $("#editStoryErrorMessage,#editStorySuccessMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/story/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#editStoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#name_edit").val(msg.story_data.name);
                    $("#designCount_edit").val(msg.story_data.design_count);
                    $("#productionDesignCount_edit").val(msg.story_data.production_design_count);
                    $("#story_year").val(msg.story_data.story_year);
                }
            }else{
                displayResponseError(msg,"editStoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateStory(){
    var form_data = $("#editStoryFrm").serialize();
    $("#story_edit_spinner").show();
    $("#editStoryErrorMessage,#editStorySuccessMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/story/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#story_edit_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_edit_');
                    if(errors != ''){
                        $("#editStoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#editStorySuccessMessage").html(msg.message).show();
                    $("#editStoryErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#edit_story_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"editStoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#editStoryErrorMessage").html('Error in processing request').show();
            $("#story_edit_spinner").hide();
        }
    });
}

function addStory(){
    $("#addStorySuccessMessage,#addStoryErrorMessage,.invalid-feedback").html('').hide();
    $("#name_add,#designCount_add,#productionDesignCount_add").val('');
    $("#add_story_dialog").modal('show');
}

function submitAddStory(){
    var form_data = $("#addStoryFrm").serialize();
    $("#story_add_spinner").show();
    $("#addStorySuccessMessage,#addStoryErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/story/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#story_add_spinner").hide();
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    var errors = getResponseErrors(msg,'<br/>','error_validation_add_');
                    if(errors != ''){
                        $("#addStoryErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#addStorySuccessMessage").html(msg.message).show();
                    $("#addStoryErrorMessage,.invalid-feedback").html('').hide();
                    setTimeout(function(){  $("#add_story_dialog").modal('hide');window.location.reload(); }, 2000);
                }
            }else{
                displayResponseError(msg,"addStoryErrorMessage");
            }
        },error:function(obj,status,error){
            $("#addStoryErrorMessage").html('Error in processing request').show();
            $("#story_add_spinner").hide();
        }
    });
}

function updateStoryStatus(){
    $("#storyListOverlay").show();
    var user_ids = '';
    $(".story-list-chk").each(function(){
        if($(this).is(":checked")){
            user_ids+= $(this).val()+",";
        }
    });
    
    user_ids = user_ids.substring(0,user_ids.length-1);
    var form_data = "action="+$("#story_action").val()+"&ids="+user_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/story/storyupdatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(objectPropertyExists(msg,'status')){
                if(msg.status == 'fail'){
                    $("#storyListOverlay").hide();
                    var errors = getResponseErrors(msg,'<br/>','');
                    if(errors != ''){
                        $("#updateStoryStatusErrorMessage").html(errors).show();
                    } 
                }else{ 
                    $("#updateStoryStatusSuccessMessage").html(msg.message).show();
                    $("#updateStoryStatusErrorMessage").html('').hide();
                    setTimeout(function(){ $("#storyListOverlay").hide(); window.location.reload(); }, 1000);
                }
            }else{
                displayResponseError(msg,"updateStoryStatusErrorMessage");
            }
        },error:function(obj,status,error){
            $("#updateStoryStatusErrorMessage").html('Error in processing request').show();
            $("#storyListOverlay").hide();
        }
    });
}

function filterWarehouseInvReportSizeRows(elem){
    return;
    var size_data = [];
    var ids = $(elem).val().split(',');
    
    for(var i=1;i<=total_tr_rows;i++){
        $("#tr_"+i).removeClass('elem-hidden');
        for(var q=0;q<ids.length;q++){
            var size_id = parseInt(ids[q]);
            var val = parseInt($("#tr_"+i).find('.td-'+size_id).html());
            
            if(val == 0){
                $("#tr_"+i).addClass('elem-hidden');
                break;
            }
        }
    }
    
    for(var i=1;i<=total_tr_rows;i++){
        if(!$("#tr_"+i).hasClass('elem-hidden')){
            for(var q=1;q<=12;q++){
                var size_id = q;
                if($("#tr_"+i).find('.td-'+size_id).length > 0){
                    var val = parseInt($("#tr_"+i).find('.td-'+size_id).html());
                }else{
                    var val = 0;
                }
                
                if(size_data[size_id] != 'undefined' && size_data[size_id] != null)
                    size_data[size_id]+=val;
                else
                    size_data[size_id]=val;
                
            }
        }
    }
    
    if(size_data.length == 0){
        for(var i=1;i<=12;i++){
            size_data[i] = 0;
        }
    }
    
    //alert(size_data);
    var size_total = 0;
    for(var i=1;i<=12;i++){
        var size_id = i;
        if($("#td_total_"+size_id).length > 0){
            if(size_data[size_id] != 'undefined' && size_data[size_id] != null){
                $("#td_total_"+size_id).html(size_data[size_id]);
                size_total+=parseInt(size_data[size_id]);
            }else{
                //$("#td_total_"+size_id).html(0);
            }
        }
    }
    
    $("#td_total_size").html(size_total);
            
}