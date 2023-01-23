@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'administrator/dashboard'),array('name'=>'Store Assets List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Assets List'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateItemStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateItemStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="store_item_action" id="store_item_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editItemBtn" id="editItemBtn" value="Update" class="btn btn-dialog" onclick="updateStoreAssetStatus();"></div>
                    <div class="col-md-1" ><input type="button" name="addItemBtn" id="addItemBtn" value="Add Asset" class="btn btn-dialog" onclick="addStoreAsset();"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="storeItemsContainer">
                <div id="storeItemsListOverlay"><div id="store-items-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="storeItemsList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr"><th><input type="checkbox" name="store_items_list_all" id="store_items_list_all"  value="1" onclick="checkAllCheckboxes(this,'store-item-list');"> 
                                &nbsp;<?php echo CommonHelper::getSortLink('ID','id','store/asset/list',true,'ASC'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Asset Name','name','store/asset/list'); ?></th>    
                                <th><?php echo CommonHelper::getSortLink('Type','type','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Category','category','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Sub category','subcategory','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Base Price','base_price','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Description','description','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Status','status','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Created On','created','store/asset/list'); ?></th>
                                <th><?php echo CommonHelper::getSortLink('Updated On','updated','store/asset/list'); ?></th>
                                <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($store_items_list);$i++)
                                <tr>
                                    <td><input type="checkbox" name="store_item_list_{{$store_items_list[$i]->id}}" id="store_item_list_{{$store_items_list[$i]->id}}" class="store-item-list-chk" value="{{$store_items_list[$i]->id}}"> &nbsp;{{$store_items_list[$i]->id}}</td>
                                    <td>{{$store_items_list[$i]->item_name}}</td>
                                    <td>{{$store_items_list[$i]->item_type}}</td>
                                    <td>{{$store_items_list[$i]->category_name}}</td>
                                    <td>{{$store_items_list[$i]->subcategory_name}}</td>
                                    <td>@if(!empty($store_items_list[$i]->base_price)) {{$currency}} {{$store_items_list[$i]->base_price}} @endif</td>
                                    <td>{{$store_items_list[$i]->item_desc}}</td>
                                    <td>@if($store_items_list[$i]->item_status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>@if(!empty($store_items_list[$i]->created_at)) {{date('d M Y',strtotime($store_items_list[$i]->created_at))}} @endif</td>
                                    <td>@if(!empty($store_items_list[$i]->updated_at)) {{date('d M Y',strtotime($store_items_list[$i]->updated_at))}} @endif</td>
                                    <td><a href="javascript:;" class="store-item-list-edit" onclick="editStoreAsset({{$store_items_list[$i]->id}});"><i title="Edit Asset Details" class="far fa-edit"></i></a>
                                        &nbsp;&nbsp;<a href="javascript:;" class="store-item-list-edit" onclick="getStoreAssetRegionPrices({{$store_items_list[$i]->id}});"><i title="Edit Region Prices" class="far fa-edit"></i></a>
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $store_items_list->links() }}
                        <p>Displaying {{$store_items_list->count()}} of {{ $store_items_list->total() }} store assets.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="edit_item_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Store Asset</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editStoreItemSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editStoreItemErrorMessage"></div>

                <form class="" name="editItemFrm" id="editItemFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group"  >
                            <label>Asset Type</label>
                            <select name="item_type_edit" id="item_type_edit"  class="form-control">
                                <option value="">Select</option>
                                <option value="movable">Movable</option>
                                <option value="fixed">Fixed</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_item_type_edit"></div>	
                        </div>
                        <div class="form-group"  >
                            <label>Category</label>
                            <select name="item_category_edit" id="item_category_edit"  class="form-control" onchange="getAssetSubcategories(this.value,'edit','');">
                                <option value="">Select</option>
                                @for($i=0;$i<count($category_list);$i++)
                                    <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_item_category_edit"></div>	
                        </div>
                        <div class="form-group"  >
                            <label>Sub category</label>
                            <select name="item_subcategory_edit" id="item_subcategory_edit"  class="form-control">
                                <option value="">Select</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_item_subcategory_edit"></div>	
                        </div>
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="item_name_edit" type="text" class="form-control" name="item_name_edit" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_item_name_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Base Price</label>
                            <input id="item_base_price_edit" type="text" class="form-control" name="item_base_price_edit" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_item_base_price_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Description</label>
                            <textarea name="item_desc_edit" id="item_desc_edit" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_validation_item_desc_edit"></div>
                        </div>
                        <!--<div class="form-group" >
                            <label>Manufacturer</label>
                            <input id="item_manufacturer_edit" type="text" class="form-control" name="item_manufacturer_edit" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_item_manufacturer_edit"></div>
                        </div>-->
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="item_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="item_edit_cancel" name="item_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="item_edit_submit" name="item_edit_submit" class="btn btn-dialog" onclick="updateStoreAsset();">Submit</button>
                        <input type="hidden" name="item_edit_id" id="item_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_item_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Store Asset</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addStoreItemSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addStoreItemErrorMessage"></div>

                <form class="" name="addItemFrm" id="addItemFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group"  >
                            <label>Asset Type</label>
                            <select name="item_type_add" id="item_type_add"  class="form-control">
                                <option value="">Select</option>
                                <option value="movable">Movable</option>
                                <option value="fixed">Fixed</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_item_type_add"></div>	
                        </div>
                        <div class="form-group"  >
                            <label>Category</label>
                            <select name="item_category_add" id="item_category_add"  class="form-control" onchange="getAssetSubcategories(this.value,'add','');">
                                <option value="">Select</option>
                                @for($i=0;$i<count($category_list);$i++)
                                    <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_item_category_add"></div>	
                        </div>
                        <div class="form-group"  >
                            <label>Sub category</label>
                            <select name="item_subcategory_add" id="item_subcategory_add"  class="form-control">
                                <option value="">Select</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_item_subcategory_add"></div>	
                        </div>
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="item_name_add" type="text" class="form-control" name="item_name_add" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_item_name_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Base Price</label>
                            <input id="item_base_price_add" type="text" class="form-control" name="item_base_price_add" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_item_base_price_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Description</label>
                            <textarea name="item_desc_add" id="item_desc_add" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_validation_item_desc_add"></div>
                        </div>

                        <!--<div class="form-group" >
                            <label>Manufacturer</label>
                            <input id="item_manufacturer_add" type="text" class="form-control" name="item_manufacturer_add" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_item_manufacturer_add"></div>
                        </div>-->
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="item_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="item_add_cancel" name="item_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="item_add_submit" name="item_add_submit" class="btn btn-dialog" onclick="submitAddStoreAsset();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="store_item_region_price_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document" >
            <div class="modal-content">
                <form name="storeItemRegionPriceForm" id="storeItemRegionPriceForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Asset Region Prices</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible" style="display:none" id="storeItemRegionPriceErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="storeItemRegionPriceSuccessMessage"></div>

                <div class="modal-body" id="store_item_region_price_content"></div>
                <div class="modal-footer center-footer">
                    <div id="edit-StoreItemRegionPrices-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" id="editStoreItemRegionPriceBtn_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id ="editStoreItemRegionPriceBtn_submit" class="btn btn-dialog" onclick="updateStoreAssetRegionPrices();">Submit</button>
                    <input type="hidden" name="store_item_region_ids" id="store_item_region_ids" value="">
                    <input type="hidden" name="store_region_price_item_id" id="store_region_price_item_id" value="">
                </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
