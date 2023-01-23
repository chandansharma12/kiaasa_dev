"use strict";
var designDataObject;

$(document).ready(function(){
    
    $("#submitDesignBtn").click(function(){
        /*if($("#is_draft_saved").val() == 0){
            alert("Please save changes to create requisition.");
            return false;
        }else{*/
            $("#create_requisition").val(1);
            $("#update_design_data").submit(); // Submit the form
        //}
    });
    
    $("#submitDesignBtnDraft").click(function(){ 
        $("#create_requisition").val(0);
        $("#update_design_data").submit(); // Submit the form
    });

    $('#update_design_data').on('submit', function(event){
        event.preventDefault(); 
        $(".invalid-feedback").html('').hide();
        var form_data = $("#update_design_data").serialize();
        form_data+="&comments="+$("#comments").val();
        
        var create_requisition = ($("#create_requisition").val() == 1)?1:0;
        form_data = form_data+"&create_requisition="+create_requisition;
        
        if($("#image_updated_front").val() == 1)
            form_data = form_data+"&image_name_front="+$("#image_name_hdn_front").val();
        
        if($("#image_updated_back").val() == 1)
            form_data = form_data+"&image_name_back="+$("#image_name_hdn_back").val();
        
        if($("#image_updated_side").val() == 1)
            form_data = form_data+"&image_name_side="+$("#image_name_hdn_side").val();
        
        if($("#image_updated_top").val() == 1)
            form_data = form_data+"&image_name_top="+$("#image_name_hdn_top").val();
        
        ajaxSetup();
        
        $.ajax({
            url:ROOT_PATH+"/design/updatedesigndata/"+design_id,
            method:"POST",
            data:form_data,
            success:function(data){
                if(data.status == 'success'){
                    $("#saveStatusDialogTitle").html("Success");
                    $("#saveStatusDialogContent").html(data.message);
                    $("#design_version_title").html(data.design_data.version);
                    var requisition_created = (data.design_data.is_requisition_created == 1)?"Yes":"No";
                    $("#requisition_created_title").html(requisition_created);
                    $("#reviewer_status_title").html(", Status: "+data.design_data.reviewer_status);
                    designDataObject.design_data = data.design_data;
                    $("#status_updated_dialog").modal('show');
                    setTimeout(function(){  window.location.reload();  }, 1000);
                }else{    
                    var errors = getResponseErrors(data,'<br/>','error_Design_');
                    document.getElementById("update_design_data").scrollIntoView();
                    
                    if(errors != ''){
                        $("#saveStatusDialogTitle").html("Error");
                        $("#saveStatusDialogContent").html(errors);
                        $("#status_updated_dialog").modal('show');
                    } 
                }
            },error:function(obj,status,error){
                $("#saveStatusDialogTitle").html("Error");
                $("#saveStatusDialogContent").html("Error in Processing Request");
                $("#status_updated_dialog").modal('show');
            }
        });
    });
 
    $("#upload_image_form").on('submit', function(event){
        event.preventDefault(); 
        $("#design_image_upload_spinner").show();
        $("#design_upload_message").html('').hide();
        ajaxSetup();

        $.ajax({
            url:ROOT_PATH+"/design/uploadimage/"+design_id,
            method:"POST",
            data:new FormData(this),
            dataType:'JSON',
            contentType: false,
            cache: false,
            processData: false,
            success:function(data){
                $("#design_image_upload_spinner").hide();
                if(data.status == 'success'){
                    var image_url = data.image_url;
                    var image_type = data.image_type;
                    var image_name = data.image_name;
                    $("#design_image_div_view_"+image_type).show();
                    $("#design_image_div_upload_"+image_type).hide();
                    $("#design_image_view_"+image_type).attr('src',image_url);
                    $("#image_name_hdn_"+image_type).val(image_name);
                    //$("#design_upload_message").html(data.message).addClass('alert-success').show();
                    $("#image_updated_"+image_type).val(1);
            }else{
                    $("#design_upload_message").html(data.message).addClass('alert-danger').show();
                }
            },
            error:function(obj,status,error){
                $("#design_upload_message").html('Error in processing request').addClass('alert-danger').show();
                $("#design_image_upload_spinner").hide();
            }
        });
     });
 
    if(typeof design_id != 'undefined'){
        getDesignData(design_id);
    
        loadColorDropdown();
    }
});

function uploadDesignImage(type){
    if(!checkRequisitionStatus()) return false;
    $("#image_type").val(type);
    $("#upload_image_form").submit();
}

function deleteDesignImage(image_type){
    if(!checkRequisitionStatus()) return false;
    $("#design_image_div_view_"+image_type).hide();
    $("#design_image_div_upload_"+image_type).show();
    $("#design_image_view_"+image_type).attr('src','');
    $("#image_name_hdn_"+image_type).val('');
    $("#image_updated_"+image_type).val(1);
}

function getDesignData(id){
   
    ajaxSetup();
    $.ajax({
        url:ROOT_PATH+"/design/data/"+id,
        method:"GET",
        success:function(msg){
            if(objectPropertyExists(msg,'designdata')){
                designDataObject = msg.designdata;
                var fields = ['sku','season_id','story_id','color_id','comments','category_id','product_name'];
                for(var i=0;i<fields.length;i++){
                    var field_id = fields[i];
                    $("#"+field_id).val(designDataObject.design_data[field_id]);
                }

                if(typeof designDataObject.design_data.color_data != 'undefined'){
                    $(".custom-select__trigger").find('span').html(designDataObject.design_data.color_data.name);
                }
                
                $("#product_id").val(designDataObject.design_data.design_type_id);
                $("#sale_price").val(designDataObject.design_data.mrp);
                //getProductData(designDataObject.design_data.design_type_id,3,'category_id',designDataObject.design_data.category_id);
                //getProductData(designDataObject.design_data.category_id,4,'sub_category_id',designDataObject.design_data.sub_cat_id);
                getDesignProductSubcategories(designDataObject.design_data.category_id,'sub_category_id',designDataObject.design_data.sub_cat_id);

                displayTabData('fabric');
            }else{
                alert('Access Denied');
            }
        }
    });
}

function displayTabData(tab_type){
    var str = '';
    if(tab_type == 'fabric'){
        var fabric_data = designDataObject.fabric_data;
        
        str+='<div id="requirements_fabrics">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Fabrics</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onClick="displayDialogBox(\'add-design-fabric\');">Add Row</a>\
        <a href="javascript:;" onclick="deleteRows(\'fabric\');" >Delete Row</a> </div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_fabric" id="chk_all_fabric" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'fabric\');" /> </th><th>Image</th>\
        <th>Body Part</th><th>Fabric Type</th><th>GSM</th><th>Color</th><th>Width</th><th>Content</th><th>Average</th><th>Rate</th>\
        <th>Cost</th><th>Unit</th><th>Edit</th><!--<th>Size</th>--></tr></thead><tbody>';
        
        for(var i=0;i<fabric_data.length;i++){
            var img = '';
            if(fabric_data[i].image_name != null && fabric_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+fabric_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+fabric_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            str+='<tr><td><input type="checkbox" name="chk_fabric_'+i+'" id="chk_fabric_'+i+'" value="'+fabric_data[i].id+'" class="fabric-chk form-check-input"></td>\
            <td>'+img+'</td><td>'+fabric_data[i].body_part_name+'</td><td>'+fabric_data[i].fabric_name+'</td><td>'+fabric_data[i].gsm_name+'</td>\
            <td>'+fabric_data[i].color_name+'</td><td>'+fabric_data[i].width_name+'</td><td>'+fabric_data[i].content_name+'</td><td>'+fabric_data[i].avg+'</td>\
            <td>'+currency+" "+fabric_data[i].rate+'</td><td>'+currency+" "+fabric_data[i].cost+'</td>\
            <td>'+fabric_data[i].unit_code+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignFabric('+fabric_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            <!--<td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+fabric_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="15" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'accessories'){
        var accessories_data = designDataObject.accessories_data;
        
        str+='<div id="requirements_accessories">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Accessories</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onClick="displayDialogBox(\'add-design-accessories\');">Add Row</a>\
        <a href="javascript:;" onclick="deleteRows(\'accessories\');" >Delete Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_accessories" id="chk_all_accessories" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'accessories\');" /> </th>\
        <th>Image</th><th>Category</th><th>Sub Category</th><th>Size</th><th>Color</th><th>Rate</th><th>Quantity</th><th>Cost</th>\
        <th>Unit</th><th>Edit</th><!--<th>Size</th>--></tr></thead><tbody>';
        
        for(var i=0;i<accessories_data.length;i++){
            var img = '';
            if(accessories_data[i].image_name != null && accessories_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+accessories_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+accessories_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            str+='<tr><td><input type="checkbox" name="chk_accessories_'+i+'" id="chk_accessories_'+i+'" value="'+accessories_data[i].id+'" class="accessories-chk form-check-input"></td><td>'+img+'</td>\
            <td>'+accessories_data[i].category_name+'</td><td>'+accessories_data[i].quality_name+'</td><td>'+accessories_data[i].size_name+'</td>\
            <td>'+accessories_data[i].color_name+'</td><td>'+currency+" "+accessories_data[i].rate+'</td><td>'+accessories_data[i].avg+'</td>\
            <td>'+currency+" "+accessories_data[i].cost+'</td><td>'+accessories_data[i].unit_code+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignAccessories('+accessories_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            <!--<td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+accessories_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'process'){
        var process_data = designDataObject.process_data;
        
        str+='<div id="requirements_process">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Fabric Process</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onClick="displayDialogBox(\'add-design-process\');getProcessDesigns(\'add\',\'\');">Add Row</a>\
        <a href="javascript:;" onclick="deleteRows(\'process\');" >Delete Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_fabric" id="chk_all_fabric" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'process\');" /> </th><th>Image</th>\
        <th>Fabric</th><th>Category</th><th>Type</th><th>Average</th><th>Unit</th><th>Rate</th><th>Cost</th>\
        <th>Edit</th><!--<th>Size</th>--></tr></thead><tbody>';
        
        for(var i=0;i<process_data.length;i++){
            var img = '';
            if(process_data[i].image_name != null && process_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+process_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+process_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            
            var type_name = (process_data[i].type_name != null)?process_data[i].type_name:'';
            
            str+='<tr><td><input type="checkbox" name="chk_process_'+i+'" id="chk_process_'+i+'" value="'+process_data[i].id+'" class="process-chk form-check-input"></td><td>'+img+'</td>\
            <td>'+process_data[i].fabric_name+'</td><td>'+process_data[i].category_name+'</td><td>'+type_name+'</td>\
            <td>'+process_data[i].avg+'</td> <td>'+process_data[i].unit_code+'</td><td>'+process_data[i].rate+'</td><td>'+currency+" "+process_data[i].cost+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignProcess('+process_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            <!--<td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+process_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'packaging_sheet'){
        var packaging_sheet_data = designDataObject.packaging_sheet_data;
        
        str+='<div id="requirements_packaging_sheet">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Packaging Sheet</h2></div><div class="col-md-8" >\
        <div class="table_action"><!--<a href="javascript:;" onclick="deleteRows(\'packaging_sheet\');" >Delete Row</a> \
        <a href="javascript:;"  onClick="displayDialogBox(\'add-design-packaging_sheet\');">Add Row</a>--></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <!--<th><input type="checkbox" name="chk_all_packaging_sheet" id="chk_all_packaging_sheet" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'packaging_sheet\');" /> </th>-->\
        <th>Image</th><th>Name</th><th>Rate</th><th>Quantity</th><th>Cost</th>\
        <th>Edit</th><!--<th>Size</th>--></tr></thead><tbody>';
        
        for(var i=0;i<packaging_sheet_data.length;i++){
            var img = '';
            if(packaging_sheet_data[i].image_name != null && packaging_sheet_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+packaging_sheet_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+packaging_sheet_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            str+='<tr><!--<td><input type="checkbox" name="chk_process_'+i+'" id="chk_process_'+i+'" value="'+packaging_sheet_data[i].id+'" class="packaging_sheet-chk form-check-input"></td>--><td>'+img+'</td>\
            <td>'+packaging_sheet_data[i].packaging_sheet_name+'</td><td>'+currency+" "+packaging_sheet_data[i].rate+'</td>\
            <td>'+packaging_sheet_data[i].avg+'</td><td>'+currency+" "+packaging_sheet_data[i].cost+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignPackagingSheet('+packaging_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            <!--<td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+packaging_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'product_process'){
        var product_process_data = designDataObject.product_process_data;
        
        str+='<div id="requirements_product_process">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Production Process</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onClick="displayDialogBox(\'add-design-product_process\');">Add Row</a>\
        <a href="javascript:;" onclick="deleteRows(\'product_process\');" >Delete Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_product_process" id="chk_all_product_process" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'product_process\');" /> </th>\
        <th>Image</th><th>Body Part</th><th>Name</th><th>Total Cost</th>\
        <th>Edit</th><!--<th>Size</th>--></tr></thead><tbody>';
        
        for(var i=0;i<product_process_data.length;i++){
            var img = '';
            if(product_process_data[i].image_name != null && product_process_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+product_process_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+product_process_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            
            var cost = (product_process_data[i].cost != null)?product_process_data[i].cost:'';
            
            str+='<tr><td><input type="checkbox" name="chk_product_process_'+i+'" id="chk_product_process_'+i+'" value="'+product_process_data[i].id+'" class="product_process-chk form-check-input"></td>\
            <td>'+img+'</td><td>'+product_process_data[i].body_part_name+'</td><td>'+product_process_data[i].product_process_name+'</td>\
            <td>'+currency+" "+cost+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignProductProcess('+product_process_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            <!--<td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+product_process_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'specification_sheet'){
        var specification_sheet_data = designDataObject.specification_sheet_data;
        var id_str = '';
        var save_spec_css = (specification_sheet_data.length == 0)?'elem-hidden':'';
        
        str+='<form name="spec_sheet_form" id="spec_sheet_form"><div id="requirements_specification_sheet">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Specification Sheet</h2></div><div class="col-md-8" >\
        <div class="table_action">\
        <a href="javascript:;" onClick="displayDialogBox(\'add-design-specification_sheet\');addDesignSpecificationSheet();">Add Row</a>\
        <a href="javascript:;" onClick="updateDesignSpecificationSheet();" class="'+save_spec_css+'">Save Rows</a>\
        <a href="javascript:;" onclick="deleteSpecificationSheet();" >Delete Rows</a>\
        </div></div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_specification_sheet" id="chk_all_specification_sheet" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'specification_sheet\');" /> </th>\
        <th>Name</th><th>S</th><th style="color:#FF465F;font-size:15px;">M</th><th>L</th><th>XL</th><th>XXL</th><th>XXXL</th><th>Allowance</th><!--<th>Edit</th>--></tr></thead><tbody>';
        
        for(var i=0;i<specification_sheet_data.length;i++){
            var id = specification_sheet_data[i].id;
                       
            var size_s = (specification_sheet_data[i].size_s == null || specification_sheet_data[i].size_s == 'undefined')?'':specification_sheet_data[i].size_s;
            var size_m = (specification_sheet_data[i].size_m == null || specification_sheet_data[i].size_m == 'undefined')?'':specification_sheet_data[i].size_m;
            var size_l = (specification_sheet_data[i].size_l == null || specification_sheet_data[i].size_l == 'undefined')?'':specification_sheet_data[i].size_l;
            var size_xl = (specification_sheet_data[i].size_xl == null || specification_sheet_data[i].size_xl == 'undefined')?'':specification_sheet_data[i].size_xl;
            var size_xxl = (specification_sheet_data[i].size_xxl == null || specification_sheet_data[i].size_xxl == 'undefined')?'':specification_sheet_data[i].size_xxl;
            var size_xxxl = (specification_sheet_data[i].size_xxxl == null || specification_sheet_data[i].size_xxxl == 'undefined')?'':specification_sheet_data[i].size_xxxl;
            var allowance = (specification_sheet_data[i].allowance == null || specification_sheet_data[i].allowance == 'undefined')?'':specification_sheet_data[i].allowance;
            
            str+='<tr><td><input type="checkbox" name="chk_specification_sheet_'+i+'" id="chk_specification_sheet_'+i+'" value="'+specification_sheet_data[i].id+'" class="specification_sheet-chk form-check-input"></td>\
            <td>'+specification_sheet_data[i].specification_sheet_name+'</td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_s_'+id+'" id="spec_sheet_size_s_'+id+'" value="'+size_s+'" /></td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_m_'+id+'" id="spec_sheet_size_m_'+id+'" value="'+size_m+'" /></td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_l_'+id+'" id="spec_sheet_size_l_'+id+'" value="'+size_l+'" /></td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_xl_'+id+'" id="spec_sheet_size_xl_'+id+'" value="'+size_xl+'" /></td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_xxl_'+id+'" id="spec_sheet_size_xxl_'+id+'" value="'+size_xxl+'" /></td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_size_xxxl_'+id+'" id="spec_sheet_size_xxxl_'+id+'" value="'+size_xxxl+'" /></td>\
            <td><input class="form-control spec-sheet-text" type="text" name="spec_sheet_allowance_'+id+'" id="spec_sheet_allowance_'+id+'" value="'+allowance+'" /></td>\
            <!--<td class="tbl_actions"><a href="javascript:;" onclick="editDesignProductProcess('+specification_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="getSizeVariations('+specification_sheet_data[i].id+');"><i class="fa fa-edit"></i></a></td>-->\
            </tr>';
            
            id_str+=id+",";
        }
        
        id_str = id_str.substring(0,id_str.length-1);
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='<input type="hidden" name="id_str" id="id_str" value="'+id_str+'"></tbody></table></div></div></form>';
    }
    
    if(tab_type == 'embroidery'){
        var embroidery_data = designDataObject.embroidery_data;
        
        str+='<div id="requirements_embroidery">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Embroidery</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onClick="displayDialogBox(\'add-design-embroidery\');">Add Row</a>\
        <a href="javascript:;" onclick="deleteRows(\'embroidery\');" >Delete Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_embroidery" id="chk_all_embroidery" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'embroidery\');" /> </th>\
        <th>Image</th><th>Type</th><th>Unit</th><th>Total Cost</th>\
        <th>Edit</th></tr></thead><tbody>';
        
        for(var i=0;i<embroidery_data.length;i++){
            var img = '';
            if(embroidery_data[i].image_name != null && embroidery_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+embroidery_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+embroidery_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            
            var cost = (embroidery_data[i].cost != null)?embroidery_data[i].cost:'';
            
            str+='<tr><td><input type="checkbox" name="chk_embroidery_'+i+'" id="chk_embroidery_'+i+'" value="'+embroidery_data[i].id+'" class="embroidery-chk form-check-input"></td>\
            <td>'+img+'</td><td>'+embroidery_data[i].embroidery_type+'</td><td>'+embroidery_data[i].unit_code+'</td>\
            <td>'+currency+" "+cost+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignEmbroidery('+embroidery_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'printing'){
        var printing_data = designDataObject.printing_data;
        
        str+='<div id="requirements_printing">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Printing</h2></div><div class="col-md-8" >\
        <div class="table_action"><a href="javascript:;" onClick="displayDialogBox(\'add-design-printing\');">Add Row</a>\
        <a href="javascript:;" onclick="deleteRows(\'printing\');" >Delete Row</a></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th><input type="checkbox" name="chk_all_printing" id="chk_all_printing" value="1" class="form-check-input" onclick="checkAllCheckboxes(this,\'printing\');" /> </th>\
        <th>Image</th><th>Type</th><th>Unit</th><th>Total Cost</th>\
        <th>Edit</th></tr></thead><tbody>';
        
        for(var i=0;i<printing_data.length;i++){
            var img = '';
            if(printing_data[i].image_name != null && printing_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+printing_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+printing_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            
            var cost = (printing_data[i].cost != null)?printing_data[i].cost:'';
            
            str+='<tr><td><input type="checkbox" name="chk_printing_'+i+'" id="chk_printing_'+i+'" value="'+printing_data[i].id+'" class="printing-chk form-check-input"></td>\
            <td>'+img+'</td><td>'+printing_data[i].printing_type+'</td><td>'+printing_data[i].unit_code+'</td>\
            <td>'+currency+" "+cost+'</td>\
            <td class="tbl_actions"><a href="javascript:;" onclick="editDesignPrinting('+printing_data[i].id+');"><i class="fa fa-edit"></i></a></td>\
            </tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    if(tab_type == 'garment_cmt'){
        var garment_cmt_data = designDataObject.garment_cmt_data;
        
        str+='<div id="requirements_garment_cmt">\
        <div class="row no-gutters align-items-md-center"><div class="col-md-4"><h2>Garment CMT</h2></div><div class="col-md-8" >\
        <div class="table_action"><!--<a href="javascript:;" onclick="deleteRows(\'garment_cmt\');" >Delete Row</a> \
        <a href="javascript:;"  onClick="displayDialogBox(\'add-design-garment_cmt\');">Add Row</a>--></div>\
        </div></div>';
        str+='<div class="table-responsive"><table class="table table-striped clearfix"><thead><tr>\
        <th>Image</th><th>Name</th><th>Rate</th><th>Unit</th><th>Cost</th>\
        <th>Edit</th></tr></thead><tbody>';
        
        for(var i=0;i<garment_cmt_data.length;i++){
            var img = '';
            if(garment_cmt_data[i].image_name != null && garment_cmt_data[i].image_name!= ''){
                var img_thumb_url = ROOT_PATH+'/images/design_images/'+design_id+"/thumbs/"+garment_cmt_data[i].image_name;
                var img_url = ROOT_PATH+'/images/design_images/'+design_id+"/"+garment_cmt_data[i].image_name;
                img = '<a href="javascript:;" onclick="displayDesignItemImage(\''+img_url+'\');"><img src="'+img_thumb_url+'" class="list-design-image"></a>';
            }
            str+='<tr><td>'+img+'</td><td>'+garment_cmt_data[i].garment_cmt_name+'</td>';
            if(garment_cmt_data[i].garment_cmt_name.toLowerCase() == 'margin'){
                str+='<td>'+garment_cmt_data[i].rate+'</td><td>%</td><td></td>';
            }else{
                str+='<td>'+currency+" "+garment_cmt_data[i].rate+'</td><td>'+garment_cmt_data[i].unit_code+'</td><td>'+currency+" "+garment_cmt_data[i].cost+'</td>';
            }
            str+='<td class="tbl_actions"><a href="javascript:;" onclick="editDesignGarmentCmt('+garment_cmt_data[i].id+','+"'"+garment_cmt_data[i].garment_cmt_name+"'"+');"><i class="fa fa-edit"></i></a></td></tr>';
        }
        
        if(i==0){ str+='<tr><td colspan="10" align="center">No Records</td></tr>';}
        str+='</tbody></table></div></div>';
    }
    
    $("#req_tab_data").html(str);
    //getDesignTotalCost(design_id,'','');
}

function editDesignProduct(id){
    $("#edit_design_product_dialog").modal('show');
    $("#product_edit_id").val(id);
    $("#editDesignProductSuccessMessage,#editDesignProductErrorMessage,.invalid-feedback").html('').hide();
    loadColorDropdown(); 
    ajaxSetup();

    $.ajax({
        url:ROOT_PATH+"/design/list?id="+id+"&action=get_design_data",
        method:"GET",
        success:function(msg){
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','');
                if(errors != ''){
                    $("#editDesignProductErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#design_edit_id").val(msg.design_data.id);
                $("#product_name_edit").val(msg.design_data.product_name);
                //$("#product_barcode_edit").val(msg.design_data.product_barcode);
                //$("#product_sku_edit").val(msg.design_data.product_sku);
                $("#product_base_price_edit").val(msg.design_data.net_cost);
                $("#product_sale_price_edit").val(msg.design_data.mrp);
                $("#product_description_edit").val(msg.design_data.description);
                $("#product_category_edit").val(msg.design_data.category_id);
                //$("#size_id_edit").val(msg.design_data.size_id);
                getDesignProductSubcategories(msg.design_data.category_id,'product_subcategory_edit',msg.design_data.sub_cat_id);
                //$("#product_image_edit").val('');
                $("#edit_design_product_dialog").find("#color_id").val(msg.design_data.color_id);
                $("#color_label_edit").html(msg.design_data.color_data.name);
                //$("#product_sale_category_edit").val(msg.design_data.sale_category);
                $("#story_id_edit").val(msg.design_data.story_id);
                $("#season_id_edit").val(msg.design_data.season_id);
                $("#product_hsn_code_edit").val(msg.design_data.hsn_code);
                //$("#gst_inclusive_edit").val(msg.design_data.gst_inclusive);
                //$("#vendor_product_sku_edit").val(msg.design_data.vendor_product_sku);
            }
        },error:function(obj,status,error){
            $("#editDesignProductErrorMessage").html('Error in processing request').show();
        }
    });
}

function updateDesignProduct(){
    var form_data = $("#editDesignProductFrm").serialize();
    $("#design_product_edit_cancel,#design_product_edit_submit").attr('disabled',true);
    $("#editDesignProductSuccessMessage,#editDesignProductErrorMessage,.invalid-feedback").html('').hide();
    
    ajaxSetup();		
    
    $.ajax({
        url:ROOT_PATH+"/design/list?action=update_design_data",
        method:"POST",
        data:form_data,
        success:function(msg){
            $("#design_product_edit_cancel,#design_product_edit_submit").attr('disabled',false);
            if(msg.status == 'fail'){
                var errors = getResponseErrors(msg,'<br/>','error_validation_');
                if(errors != ''){
                    $("#editDesignProductErrorMessage").html(errors).show();
                } 
            }else{ 
                $("#editDesignProductSuccessMessage").html(msg.message).show();
                setTimeout(function(){  $("#edit_design_product_dialog").modal('hide');window.location.reload(); }, 2000);
            }
        },error:function(obj,status,error){
            $("#editDesignProductErrorMessage").html('Error in processing request').show();
            $("#design_product_edit_cancel,#design_product_edit_submit").attr('disabled',false);
        }
    });
}