"use strict";

function editCategory(id){
    $("#edit_category_dialog").modal('show');
    $("#category_edit_id").val(id);
    $("#editCategorySuccessMessage,#editCategoryErrorMessage,.invalid-feedback").html('').hide();
     
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/category/data/"+id,
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#editCategoryErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#categoryName_edit").val(msg.category_data.name);
                $("#categoryParent_edit").val(msg.category_data.pid);
            }
        },error:function(obj,status,error){
            $("#editCategoryErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateCategory(){
    var form_data = $("#editCategoryFrm").serialize();
    $("#category_edit_spinner").show();
    $("#editCategorySuccessMessage,#editCategoryErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/category/update",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#category_edit_spinner").hide();
            
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#editCategoryErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#editCategorySuccessMessage").html(msg.message).show();
                $("#editCategoryErrorMessage,.invalid-feedback").html('').hide();
                setTimeout(function(){  $("#edit_category_dialog").modal('hide');window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#editCategoryErrorMessage").html('Error in processing request').show();
            $("#category_edit_spinner").hide();
        }
    });
}

function addCategory(){
    $("#addCategorySuccessMessage,#addCategoryErrorMessage,.invalid-feedback").html('').hide();
    $("#categoryName_add,#categoryParent_add").val('');
    $("#add_category_dialog").modal('show');
}

function submitAddCategory(){
    var form_data = $("#addCategoryFrm").serialize();
    $("#category_add_spinner").show();
    $("#addCategorySuccessMessage,#addCategoryErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/category/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#category_add_spinner").hide();
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#addCategoryErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#addCategorySuccessMessage").html(msg.message).show();
                $("#addCategoryErrorMessage,.invalid-feedback").html('').hide();
                setTimeout(function(){  $("#add_category_dialog").modal('hide');window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#addCategoryErrorMessage").html('Error in processing request').show();
            $("#category_add_spinner").hide();
        }
    });
}

function updateCategoryStatus(){
    $("#categoryListOverlay").show();
    var category_ids = '';
    $(".category-list").each(function(){
        if($(this).is(":checked")){
            category_ids+= $(this).val()+",";
        }
    });
    
    category_ids = category_ids.substring(0,category_ids.length-1);
    var form_data = "action="+$("#category_action").val()+"&ids="+category_ids;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/category/updatestatus",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(msg.status == 'fail'){
                $("#categoryListOverlay").hide();
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#updateCategoryStatusErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#updateCategoryStatusSuccessMessage").html(msg.message).show();
                $("#updateCategoryStatusErrorMessage").html('').hide();
                setTimeout(function(){ $("#categoryListOverlay").hide(); window.location.reload(); }, 1000);
            }
        },error:function(obj,status,error){
            $("#updateCategoryStatusErrorMessage").html('Error in processing request').show();
            $("#categoryListOverlay").hide();
        }
    });
}

function selectAllCategory(elem){
    if($(elem).is(":checked")){
        $(".category-list").each(function(){
            $(this).prop("checked", true);
        });
    }else{
        $(".category-list").each(function(){
            $(this).prop("checked", false);
        });
    }
}

function getPriceSlotCategoryData(min,max){
    $("#reportStatusSuccessMessage,reportStatusErrorMessage").html('').hide();
    var form_data = $("#searchForm").serialize();
    form_data = form_data+"&min="+min+"&max="+max;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/price/report/sales?action=get_category_report",
        method:"GET",
        data:form_data,
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#reportStatusErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#reportStatusErrorMessage").html('').hide();
                var total = ['count','net_price','sale_price'];
                total['count'] = 0;total['net_price'] = 0;total['sale_price'] = 0;
                var category_list = msg.category_list;
                var str = '<div class="table-responsive table-filter"><table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">\
                <thead><tr class="header-tr"><th>Category</th><th>Total Units</th><th>Total NET Price</th><th>Total Sale Price</th><th>Action</th></tr></thead>\
                <tbody>'
                for(var i=0;i<category_list.length;i++){
                    var subacat_link = '<a href="javascript:;" class="table-link" onclick="getPriceSlotSubCategoryData('+min+','+max+','+category_list[i].category_id+')">Subcategories &raquo;</a>';
                    str+='<tr><td>'+category_list[i].category_name+'</td><td>'+category_list[i].cat_count+'</td><td>'+(parseFloat(category_list[i].cat_net_price)).toFixed(2)+'</td><td>'+category_list[i].cat_sale_price+'</td><td>'+subacat_link+'</td></tr>';
                    total['count']+=parseInt(category_list[i].cat_count);
                    total['net_price']+=parseFloat(category_list[i].cat_net_price);
                    total['sale_price']+=parseFloat(category_list[i].cat_sale_price);
                }
                
                str+='</tbody>';
                str+='<tfoot><tr><th>Total</th><th>'+total['count']+'</th><th>'+total['net_price'].toFixed(2)+'</th><th>'+total['sale_price']+'</th></tr></tfoot>';
                str+= '</table></div>';
                $("#category_report_content").html(str);
                $("#category_report_title").html('Category Report: '+msg.min+"-"+msg.max);
                $("#category_report_dialog").modal('show');
                
            }
        },error:function(obj,status,error){
            $("#reportStatusErrorMessage").html('Error in processing request').show();
        }
    });
}

function getPriceSlotSubCategoryData(min,max,category_id){
    $("#categoryReportSuccessMessage,categoryReportErrorMessage").html('').hide();
    var form_data = $("#searchForm").serialize();
    form_data = form_data+"&min="+min+"&max="+max+"&category_id="+category_id;
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/price/report/sales?action=get_subcategory_report",
        method:"GET",
        data:form_data,
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#categoryReportErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#categoryReportErrorMessage").html('').hide();
                var total = ['count','net_price','sale_price'];
                total['count'] = 0;total['net_price'] = 0;total['sale_price'] = 0;
                var subcategory_list = msg.subcategory_list;
                var str = '<div class="table-responsive table-filter"><table class="table table-striped admin-table report-sort" cellspacing="0"  id="storeReportTbl">\
                <thead><tr class="header-tr"><th>Subcategory</th><th>Total Units</th><th>Total NET Price</th><th>Total Sale Price</th></tr></thead>\
                <tbody>'
                for(var i=0;i<subcategory_list.length;i++){
                    var subcategory_name = (subcategory_list[i].subcategory_name != null)?subcategory_list[i].subcategory_name:'NA';
                    str+='<tr><td>'+subcategory_name+'</td><td>'+subcategory_list[i].subcat_count+'</td><td>'+(parseFloat(subcategory_list[i].subcat_net_price)).toFixed(2)+'</td><td>'+subcategory_list[i].subcat_sale_price+'</td></tr>';
                    total['count']+=parseInt(subcategory_list[i].subcat_count);
                    total['net_price']+=parseFloat(subcategory_list[i].subcat_net_price);
                    total['sale_price']+=parseFloat(subcategory_list[i].subcat_sale_price);
                }
                
                str+='</tbody>';
                str+='<tfoot><tr><th>Total</th><th>'+total['count']+'</th><th>'+total['net_price'].toFixed(2)+'</th><th>'+total['sale_price']+'</th></tr></tfoot>';
                str+= '</table></div>';
                
                var cat_link = '<div style="float:left;margin-bottom:5px;"><a href="javascript:;" class="table-link" onclick="getPriceSlotCategoryData('+min+','+max+')">'+msg.category_data.name+'</a> &raquo Subcategories</div>';
                
                $("#category_report_content").html(cat_link+str);
                $("#category_report_title").html('Subcategory Report: '+min+"-"+max+"  &nbsp;|&nbsp; Category: "+msg.category_data.name);
            }
        },error:function(obj,status,error){
            $("#categoryReportErrorMessage").html('Error in processing request').show();
        }
    });
}

function addSize(){
    $("#addSizeSuccessMessage,#addSizeErrorMessage,.invalid-feedback").html('').hide();
    $("#sizeName_add").val('');
    $("#add_size_dialog").modal('show');
}

function submitAddSize(){
    var form_data = $("#addSizeFrm").serialize();
    $("#addSizeSuccessMessage,#addSizeErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/size/add",
        method:"POST",
        data:form_data,
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#addSizeErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#addSizeSuccessMessage").html(msg.message).show();
                $("#addSizeErrorMessage,.invalid-feedback").html('').hide();
                setTimeout(function(){  $("#add_size_dialog").modal('hide');window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#addSizeErrorMessage").html('Error in processing request').show();
        }
    });
}