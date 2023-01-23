"use strict";
var designDataObject;

$(document).ready(function(){
    getProductsList(1);
    
    $("#search_text").on("keyup",function(){
        if(this.value.length > 2 || this.value.length == 0){
            getProductsList(1);
        }
    });
    
    $("#filter_apply_btn").on("click",function(){
        getProductsList(1);
        $("#filters-div").modal('hide');
    });
    
    $("#filter_clear_btn").on("click",function(){
        $(".filter-status,.filter-product,.filter-category").prop('checked',false);
    });
    
    $(document).on('click', '.pagination a',function(event){
        event.preventDefault();
        $('li').removeClass('active');
        $(this).parent('li').addClass('active');
        var page=$(this).attr('href').split('page=')[1];
        getProductsList(page);
    });
});

function getProductsList(page){
    $("#productsListOverlay").show();
    var search_text;
    if($("#search_text").val().length > 2){
        search_text = $("#search_text").val();
    }
    
    var statusArray = [],categoryArray = [], productArray = [], count = 0;
    
    $(".filter-status").each(function(){
        if(($(this).is(":checked"))){
            statusArray[count] = $(this).val();
            count++;
        } 
    });
    
    count = 0;
    $(".filter-product").each(function(){
        if(($(this).is(":checked"))){
            productArray[count] = $(this).val();
            count++;
        } 
    });
    
    count = 0;
    $(".filter-category").each(function(){
        if(($(this).is(":checked"))){
            categoryArray[count] = $(this).val();
            count++;
        } 
    });
    
    ajaxSetup();
    $.ajax({
        type: "POST",
        data:{searchText:search_text,statusArray:statusArray,productArray:productArray,categoryArray:categoryArray},
        url:ROOT_PATH+"/design/getproductslist?page="+page,
        success: function(msg){		
            $("#productsListOverlay").hide();
            var errors = '';
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#productsListErrorMessage").html(errors).show();
                } 
            }else{
                $(".search_area").show();
                var designs = msg.designs;
                if(msg.user_type == 5){
                    var str = '<div class="pro_row clearfix ">';
                    for(var i=0;i<designs.data.length;i++){
                        var sku = (designs.data[i].sku != null)?designs.data[i].sku:'&mdash;';
                        str+='<div class="col"><div class="pro_blk"><a href="'+ROOT_PATH+'/design/edit/'+designs.data[i].id+'">\
                        <img src="'+designs.data[i].image_path+'" alt="" class="img-thumbnail" />\
                        <p>'+sku+'<span>Apparel</span></p></a>';
                        
                        if(designs.data[i].reviewer_status != null){
                            str+='<small>'+designs.data[i].reviewer_status+'</small>';
                        }
                        str+='</div></div>';
                        if(i > 0 && (i+1)%5 == 0){ str+='<div class="clearfix"></div>'; }
                    }

                    str+='</div>';
                    if(designs.data.length == 0) str+= '<center><h4>No Records</h4></center>';
                    str+=msg.paging_links;
                }else if(msg.user_type == 4){
                    var str = '<div class="table-responsive table-filter"><table class="table table-striped ">';
                    str+='<thead><tr><th>ID</th><th>Article Number</th><th>Product</th><th>Designer</th><th>Status</th><th>Version</th><th>Date Added</th><th>Action</th></tr></thead><tbody>';
                    for(var i=0;i<designs.data.length;i++){
                        var product_name = (designs.data[i].product_name != null)?designs.data[i].product_name:'&mdash;';
                        var sku = (designs.data[i].sku != null)?designs.data[i].sku:'&mdash;';
                        str+='<tr><td>'+designs.data[i].id+'</td><td>'+sku+'</td><td>'+product_name+'</td><td>'+designs.data[i].designer_name+'</td><td>'+designs.data[i].reviewer_status+'</td><td>'+designs.data[i].version+'</td>\
                        <td>'+designs.data[i].date_created+'</td><td><a href="'+ROOT_PATH+'/design/detail/'+designs.data[i].id+'" title="View Details"><img alt="View Details" src="'+ROOT_PATH+'/images/view-details.png" height="18" width="18"></a></td></tr>';
                    }
                    
                    if(designs.data.length == 0) str+= '<tr><td colspan="6"><center>No Records</center></td></tr>';
                    str+='</tbody></table></div>';
                    str+=msg.paging_links;
                }else if(msg.user_type == 3){
                    var str = '<a href="'+ROOT_PATH+'/createquotation" class="btn btn-primary ">Quotation by Item</a>&nbsp;&nbsp;&nbsp;<a href="'+ROOT_PATH+'/purchase-orders/list" class="btn btn-primary ">Purchase Orders</a><div class="table-responsive table-filter"><table class="table table-striped ">';
                    str+='<thead><tr><th>ID</th><th>SKU</th><th>Story</th><th>Designer</th><th>Reviewer</th><th>Status</th><th>Version</th><th>Production Count</th><th>Date Added</th><th>Date Reviewed</th><th align="center">Action</th></tr></thead><tbody>';
                    for(var i=0;i<designs.data.length;i++){
                        var dt = new Date(designs.data[i].date_reviewed);
                        var date_str = dt.getDate()  + "-" + (dt.getMonth()+1) + "-" + dt.getFullYear() + " " +dt.getHours() + ":" + dt.getMinutes();
                        var sku = (designs.data[i].sku != null)?designs.data[i].sku:'&mdash;';
                        var story_name = (designs.data[i].story_name != null)?designs.data[i].story_name:'&mdash;';
                        var production_count = (designs.data[i].production_count != null)?designs.data[i].production_count:0;
                        str+='<tr><td>'+designs.data[i].id+'</td><td>'+sku+'</td><td>'+story_name+'</td><td>'+designs.data[i].designer_name+'</td><td>'+designs.data[i].reviewer_name+'</td><td>'+designs.data[i].reviewer_status+'</td><td>'+designs.data[i].version+'</td><td>'+production_count+'</td>\
                        <td>'+designs.data[i].date_created+'</td><td>'+date_str+'</td>\
                        <td><a href="javascript:;" title="Request Quotation" onclick="displayQuotationForm('+designs.data[i].id+',\''+designs.data[i].sku+'\',\''+designs.data[i].version+'\',\''+designs.data[i].reviewer_name+'\',\''+production_count+'\');"><img alt="Request Quotation" src="'+ROOT_PATH+'/images/view-details.png" height="18" width="18"></a>\
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="'+ROOT_PATH+'/quotation/requests/'+designs.data[i].id+'" title="Quotation Requests List"><img alt="Quotation Requests List" src="'+ROOT_PATH+'/images/view-details.png" height="18" width="18"></a>\
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="'+ROOT_PATH+'/quotation/submissions/'+designs.data[i].id+'" title="Quotation Submissions List"><img alt="Quotation Submissions List" src="'+ROOT_PATH+'/images/view-details.png" height="18" width="18"></a>\
                        </td>\
                        </tr>';
                    }
                    
                    if(designs.data.length == 0) str+= '<tr><td colspan="6"><center>No Records</center></td></tr>';
                    str+='</tbody></table></div>';
                    str+=msg.paging_links;
                }else if(msg.user_type == 1){
                    $(".search_area").hide();
                    var str = '<a href="'+ROOT_PATH+'/user/list" class="btn btn-primary">Users List</a>\
                    <a style="margin-left:15px;" href="'+ROOT_PATH+'/vendor/list" class="btn btn-primary">Vendors List</a>\
                    <a style="margin-left:15px;" href="'+ROOT_PATH+'/story/list" class="btn btn-primary">Story List</a>\
                    <a style="margin-left:15px;" href="'+ROOT_PATH+'/category/list" class="btn btn-primary">Category List</a>\
                    <a style="margin-left:15px;" href="'+ROOT_PATH+'/lookup-item/list" class="btn btn-primary">Lookup Items List</a>';
                }
                
                $("#productsList").html(str).show();
            }
        },
        error:function(obj,status,error){
            $("#productsListErrorMessage").html('Error in processing request').show();
            $("#productsListOverlay").hide();
        }
    });
}
