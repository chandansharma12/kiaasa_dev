@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateStoreStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateStoreStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="s_id" id="s_id" class="form-control" placeholder="Store ID" value="{{request('s_id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="s_name" id="s_name" class="form-control" placeholder="Store Name" value="{{request('s_name')}}" />
                    </div>
                    <div class="col-md-2" >
                        <select name="state_id" id="state_id"  class="form-control" >
                            <option value="">-- State --</option>
                            @for($i=0;$i<count($states_list);$i++)
                                <?php if($states_list[$i]->id == request('state_id')) $sel = 'selected';else $sel = ''; ?>  
                                <option value="{{$states_list[$i]->id}}" {{$sel}}>{{$states_list[$i]->state_name}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="zone_id" id="zone_id"  class="form-control" >
                            <option value="">-- Zone --</option>
                            @for($i=0;$i<count($store_zones);$i++)
                                <?php if($store_zones[$i]['id'] == request('zone_id')) $sel = 'selected';else $sel = ''; ?>  
                                <option {{$sel}} value="{{$store_zones[$i]['id']}}">{{$store_zones[$i]['name']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="store_type" id="store_type" class="form-control">
                            <option value="">-- Store Type --</option>
                            <option value="1" @if(request('store_type') == 1) selected @endif>Kiaasa</option>
                            <option value="2" @if(request('store_type') == 2) selected @endif>Franchise</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>

                    <div class="col-md-1" ><input type="button" name="addStoreBtn" id="addStoreBtn" value="Add Store" class="btn btn-dialog" onclick="addStore();"></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Stores List'); ?></div>
                </div>
            </form>
            <div class="separator-10"></div>

            <div id="storeContainer" class="table-container">
                
                <div style="width:3000px">&nbsp;</div>
                <div class="table-responsive table-filter" style="width:2980px;">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>&nbsp;<?php echo CommonHelper::getSortLink('ID','id','store/list',true,'ASC'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Name','name','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Code','code','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('State','state','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Address Line 1','address1','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Phone','phone','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Google Address','google_name','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Display Name','display_name','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('GST No','gst_no','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('GST Name','gst_name','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Type','type','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Zone','zone','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Bags Inv','bags_inv','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Slug','slug','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Ecomm Status','ecomm_status','store/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Created On','created_on','store/list'); ?></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($store_list);$i++)
                            <tr>
                                <td>{{$store_list[$i]->id}}</td>
                                <td>{{$store_list[$i]->store_name}}</td>
                                <td>{{$store_list[$i]->store_id_code}}</td>
                                <td>{{$store_list[$i]->state_name}}</td>
                                <td>{{$store_list[$i]->address_line1}}</td>
                                <td>{{$store_list[$i]->phone_no}}</td>
                                <td>{{$store_list[$i]->google_name}}</td>
                                <td>{{$store_list[$i]->display_name}}</td>
                                <td>{{$store_list[$i]->gst_no}}</td>
                                <td>{{$store_list[$i]->gst_name}}</td>
                                <td>{{$store_type_text = ($store_list[$i]->store_type != null)?($store_list[$i]->store_type == 1)?'Kiaasa':'Franchise':''}}</td>
                                <td>{{$store_list[$i]->zone_name}}</td>
                                <td>{{$store_list[$i]->bags_inventory}}</td>
                                <td>{{$store_list[$i]->slug}}</td>
                                <td>@if($store_list[$i]->ecommerce_status == 1) <i title="Ecommerce Status Enabled" class="far fa-check-circle"></i> @else <i title="Ecommerce Status Disabled" class="fa fa-ban"></i> @endif</td>
                                <td>{{date('d-m-Y',strtotime($store_list[$i]->created_at))}}</td>
                                <td>
                                    <a href="javascript:;" class="store-list-edit" onclick="editStore({{$store_list[$i]->id}});"><i title="Edit Store Data" class="far fa-edit"></i></a> &nbsp;
                                    <a href="javascript:;" class="store-list-edit" onclick="editStoreAccessKey({{$store_list[$i]->id}});"><i title="View/Edit Store API Access Key" class="far fa-edit"></i></a>&nbsp;
                                    <a href="{{url('store/expense/master/list/'.$store_list[$i]->id)}}" class="store-list-edit" ><i title="View/Edit Store PL Report Master data" class="far fa-edit"></i></a>&nbsp;
                                    <a href="{{url('store/expense/monthly/list/'.$store_list[$i]->id)}}" class="store-list-edit" ><i title="View/Edit Store PL Report Monthly data" class="far fa-edit"></i></a>
                                </td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $store_list->withQueryString()->links() }}
                    <p>Displaying {{$store_list->count()}} of {{ $store_list->total() }} stores.</p>
                </div>
                
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="edit_store_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="padding-top:6px;padding-bottom:6px;">
                    <h5 class="modal-title" id="exampleModalLongTitle" style="font-size: 1.05rem;">Edit Store</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editStoreSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editStoreErrorMessage"></div>

                <form class="" name="editStoreFrm" id="editStoreFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-5" >
                                <label>Name</label>
                                <input id="store_name_edit" type="text" class="form-control" name="store_name_edit" value=""  > 
                                <div class="invalid-feedback" id="error_validation_store_name_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Region</label>
                                <select name="store_region_edit" id="store_region_edit"  class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($region_list);$i++)
                                        <option value="{{$region_list[$i]['id']}}">{{$region_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_region_edit"></div>	
                            </div>
                            <div class="form-group col-md-4" >
                                <label>State</label>
                                <select name="store_state_edit" id="store_state_edit" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($states_list);$i++)
                                        <option value="{{$states_list[$i]->id}}">{{$states_list[$i]->state_name}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_state_edit"></div>	
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Address Line 1</label>
                                <input id="store_address_line1_edit" type="text" class="form-control" name="store_address_line1_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_address_line1_edit"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Address Line 2</label>
                                <input id="store_address_line2_edit" type="text" class="form-control" name="store_address_line2_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_address_line2_edit"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-3" >
                                <label>City Name</label>
                                <input id="store_city_name_edit" type="text" class="form-control" name="store_city_name_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_city_name_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Postal Code</label>
                                <input id="store_postal_code_edit" type="text" class="form-control" name="store_postal_code_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_postal_code_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Phone No</label>
                                <input id="store_phone_no_edit" type="text" class="form-control" name="store_phone_no_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_phone_no_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Store Code</label>
                                <input id="store_code_edit" type="text" class="form-control" name="store_code_edit" value="" maxlength="15" readonly="true">
                                <div class="invalid-feedback" id="error_validation_store_code_edit"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-2">
                                <label>GST Applicable</label>
                                <select name="store_gst_applicable_edit" id="store_gst_applicable_edit" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_gst_applicable_edit"></div>	
                            </div>
                            <div class="form-group col-md-3" >
                                <label>GST No</label>
                                <input id="store_gst_no_edit" type="text" class="form-control" name="store_gst_no_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_gst_no_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>GST Name</label>
                                <input id="store_gst_name_edit" type="text" class="form-control" name="store_gst_name_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_gst_name_edit"></div>
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Store Type</label>
                                <select name="store_type_edit" id="store_type_edit" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Kiaasa</option>
                                    <option value="2">Franchise</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_type_edit"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Ecommerce Status</label>
                                <select name="store_ecommerce_status_edit" id="store_ecommerce_status_edit" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_ecommerce_status_edit"></div>	
                            </div>
                        </div>    
                        
                        <div class="form-row" >
                            <div class="form-group col-md-2">
                                <label>GST Type</label>
                                <select name="store_gst_type_edit" id="store_gst_type_edit" class="form-control">
                                    <option value="">Select</option>
                                    <option value="inclusive">Inclusive</option>
                                    <option value="exclusive">Exclusive</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_gst_type_edit"></div>	
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Zone</label>
                                <select name="store_zone_edit" id="store_zone_edit"  class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($store_zones);$i++)
                                        <option value="{{$store_zones[$i]['id']}}">{{$store_zones[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_zone_edit"></div>	
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Store Info Type</label>
                                <select name="store_info_type_edit" id="store_info_type_edit" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Kiaasa Store</option>
                                    <option value="2">Tiki Store</option>
                                    <option value="3">Tiki Warehouse</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_info_type_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Google Address</label>
                                <input id="store_google_name_edit" type="text" class="form-control" name="store_google_name_edit" value="" maxlength="250" >
                                <div class="invalid-feedback" id="error_validation_store_google_name_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Display Name</label>
                                <input id="store_display_name_edit" type="text" class="form-control" name="store_display_name_edit" value="" maxlength="250" >
                                <div class="invalid-feedback" id="error_validation_store_display_name_edit"></div>
                            </div>
                        </div>
                        
                        <div class="form-row" >
                            <div class="form-group col-md-1" >
                                <img src="" name="front_picture_img" id="front_picture_img" style="display:none;width:50px;height:50px;">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Front Picture</label>
                                <input type="file" name="store_front_picture_edit" id="store_front_picture_edit"/>
                                <div class="invalid-feedback" id="error_validation_store_front_picture_edit"></div>	
                            </div>
                        
                            <div class="form-group col-md-1" >
                                <img src="" name="back_picture_img" id="back_picture_img" style="display:none;width:50px;height:50px;">
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Back Picture</label>
                                <input type="file" name="store_back_picture_edit" id="store_back_picture_edit">
                                <div class="invalid-feedback" id="error_validation_store_back_picture_edit"></div>	
                            </div>
                            
                            <div class="form-group col-md-2" >
                                <label>Latitude</label>
                                <input id="store_latitude_edit" type="text" class="form-control" name="store_latitude_edit" value="" maxlength="200" >
                                <div class="invalid-feedback" id="error_validation_store_latitude_edit"></div>
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Longitude</label>
                                <input id="store_longitude_edit" type="text" class="form-control" name="store_longitude_edit" value="" maxlength="200" >
                                <div class="invalid-feedback" id="error_validation_store_longitude_edit"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="store_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="store_edit_cancel" name="store_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="store_edit_submit" name="store_edit_submit" class="btn btn-dialog" onclick="updateStore();">Submit</button>
                        <input type="hidden" name="store_edit_id" id="store_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="add_store_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="padding-top:6px;padding-bottom:6px;">
                    <h5 class="modal-title" id="exampleModalLongTitle" style="font-size: 1.05rem;">Add New Store</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addStoreSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addStoreErrorMessage"></div>

                <form class="" name="addStoreFrm" id="addStoreFrm" type="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-5" >
                                <label>Name</label>
                                <input id="store_name_add" type="text" class="form-control" name="store_name_add" value="" autofocus > 
                                <div class="invalid-feedback" id="error_validation_store_name_add"></div>
                            </div>
                             <div class="form-group col-md-3" >
                                <label>Region</label>
                                <select name="store_region_add" id="store_region_add"  class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($region_list);$i++)
                                        <option value="{{$region_list[$i]['id']}}">{{$region_list[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_region_add"></div>	
                            </div>
                            <div class="form-group col-md-4" >
                                <label>State</label>
                                <select name="store_state_add" id="store_state_add"  class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($states_list);$i++)
                                        <option value="{{$states_list[$i]->id}}">{{$states_list[$i]->state_name}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_state_add"></div>	
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-6" >
                                <label>Address Line 1</label>
                                <input id="store_address_line1_add" type="text" class="form-control" name="store_address_line1_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_address_line1_add"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>Address Line 2</label>
                                <input id="store_address_line2_add" type="text" class="form-control" name="store_address_line2_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_address_line2_add"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-3" >
                                <label>City Name</label>
                                <input id="store_city_name_add" type="text" class="form-control" name="store_city_name_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_city_name_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Postal Code</label>
                                <input id="store_postal_code_add" type="text" class="form-control" name="store_postal_code_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_postal_code_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Phone No</label>
                                <input id="store_phone_no_add" type="text" class="form-control" name="store_phone_no_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_phone_no_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Store Code</label>
                                <input id="store_code_add" type="text" class="form-control" name="store_code_add" value="" maxlength="15" >
                                <div class="invalid-feedback" id="error_validation_store_code_add"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-2">
                                <label>GST Applicable</label>
                                <select name="store_gst_applicable_add" id="store_gst_applicable_add" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_gst_applicable_add"></div>	
                            </div>
                            <div class="form-group col-md-3" >
                                <label>GST No</label>
                                <input id="store_gst_no_add" type="text" class="form-control" name="store_gst_no_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_gst_no_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>GST Name</label>
                                <input id="store_gst_name_add" type="text" class="form-control" name="store_gst_name_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_store_gst_name_add"></div>
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Store Type</label>
                                <select name="store_type_add" id="store_type_add" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Kiaasa</option>
                                    <option value="2">Franchise</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_type_add"></div>
                            </div>
                            <div class="form-group col-md-2">
                                <label>Ecommerce Status</label>
                                <select name="store_ecommerce_status_add" id="store_ecommerce_status_add" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_ecommerce_status_add"></div>	
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-3" >
                                <label>Zone</label>
                                <select name="store_zone_add" id="store_zone_add"  class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($store_zones);$i++)
                                        <option value="{{$store_zones[$i]['id']}}">{{$store_zones[$i]['name']}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_zone_add"></div>	
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Store Info Type</label>
                                <select name="store_info_type_add" id="store_info_type_add" class="form-control">
                                    <option value="">Select</option>
                                    <option value="1">Kiaasa Store</option>
                                    <option value="2">Tiki Store</option>
                                    <option value="3">Tiki Warehouse</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_store_info_type_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Google Address</label>
                                <input id="store_google_name_add" type="text" class="form-control" name="store_google_name_add" value="" maxlength="250" />
                                <div class="invalid-feedback" id="error_validation_store_google_name_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Display Name</label>
                                <input id="store_display_name_add" type="text" class="form-control" name="store_display_name_add" value="" maxlength="250" />
                                <div class="invalid-feedback" id="error_validation_store_display_name_add"></div>
                            </div>
                        </div>
                        
                        <div class="form-row" >
                            <div class="form-group col-md-3" >
                                <label>Front Picture</label>
                                <input type="file" name="store_front_picture_add" id="store_front_picture_add"/>
                                <div class="invalid-feedback" id="error_validation_store_front_picture_add"></div>	
                            </div>
                        
                            <div class="form-group col-md-3" >
                                <label>Back Picture</label>
                                <input type="file" name="store_back_picture_add" id="store_back_picture_add">
                                <div class="invalid-feedback" id="error_validation_store_back_picture_add"></div>	
                            </div>
                            
                            <div class="form-group col-md-2" >
                                <label>Latitude</label>
                                <input id="store_latitude_add" type="text" class="form-control" name="store_latitude_add" value="" maxlength="200" >
                                <div class="invalid-feedback" id="error_validation_store_latitude_add"></div>
                            </div>
                            <div class="form-group col-md-2" >
                                <label>Longitude</label>
                                <input id="store_longitude_add" type="text" class="form-control" name="store_longitude_add" value="" maxlength="200" >
                                <div class="invalid-feedback" id="error_validation_store_longitude_add"></div>
                            </div>
                            
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="store_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="store_add_cancel" name="store_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="store_add_submit" name="store_add_submit" class="btn btn-dialog" onclick="submitAddStore();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_store_access_key_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">API Access Key</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editStoreAccessKeySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editStoreAccessKeyErrorMessage"></div>

                <form class="" name="editAccessKeyFrm" id="editAccessKeyFrm" type="POST">
                    <div class="modal-body">
                        <h6 id="store_name_api"></h6>
                        <div class="form-row" >
                            <div class="form-group col-md-10 " >
                                <label>API Access Key</label>
                                <input id="store_access_key_edit" type="text" class="form-control" name="store_access_key_edit" value=""  readonly="true">
                                <div class="invalid-feedback" id="error_validation_store_access_key_edit"></div>
                            </div>
                             <div class="form-group col-md-1" >
                                <label></label>
                                <button style="margin-top: 20px;" type="button" id="store_access_key_edit_btn" name="store_access_key_edit_btn" class="btn btn-dialog" onclick="$('#access_key_update_div').slideDown();$(this).hide();">Modify</button>
                            </div>
                            <div class="form-group col-md-10 " >
                                <label>Store API URL</label>
                                <input id="store_api_url" type="text" class="form-control" name="store_api_url" value=""  readonly="true">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer elem-hidden" id="access_key_update_div" >
                        
                        <button type="button" id="store_access_key_update_cancel" name="store_access_key_update_cancel" class="btn btn-secondary" data-dismiss="modal" >Cancel</button>
                        <button type="button" id ="store_access_key_update_submit" name="store_access_key_update_submit" class="btn btn-dialog" onclick="submitEditStoreAccessKey();">Update</button>
                        <input type="hidden" name="access_key_store_id" id="access_key_store_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($store_list->total(),1000,'/store/list','Download Stores List','Stores'); ?>

@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script src="{{ asset('js/store.js?v=2.45') }}" ></script>
@endif
@endsection
