@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>
@if(empty($error_message))

    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Lookup Items List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Lookup Items List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateLookupItemsStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateLookupItemsStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="ID" value="{{request('id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="name" id="name" class="form-control" placeholder="Name" value="{{request('name')}}" />
                    </div>
                    <div class="col-md-2" >
                        <select name="type" id="type" class="form-control">
                            <option value="">Type</option>
                            @for($i=0;$i<count($types_list);$i++)
                                <?php if(strtolower($types_list[$i]) == strtolower(Request('type'))) $sel = 'selected';else $sel = ''; ?>
                                <option <?php echo $sel; ?> value="{{strtolower($types_list[$i])}}">{{ucwords(str_replace('_',' ',strtolower($types_list[$i])))}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <div class="col-md-1" ><input type="button" name="addItemBtn" id="addItemBtn" value="Add Item" class="btn btn-dialog" onclick="addLookupItem();"></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Lookup Items'); ?></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="itemsContainer">
                <div id="itemsListOverlay"><div id="items-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>

                <div id="itemsList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th><?php echo CommonHelper::getSortLink('ID','id','lookup-item/list',true,'ASC'); ?></th>   
                                    <th><?php echo CommonHelper::getSortLink('Name','name','lookup-item/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Description','description','lookup-item/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Parent','parent','lookup-item/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Type','type','lookup-item/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Slug','slug','lookup-item/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created','lookup-item/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Updated On','updated','lookup-item/list'); ?></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i=0;$i<count($items_list);$i++)
                                <tr>
                                    <td>{{$items_list[$i]->id}}</td>
                                    <td>{{$items_list[$i]->name}}</td>
                                    <td>{{$items_list[$i]->description}}</td>
                                    <td>{{$items_list[$i]->parent_item_name}}</td>
                                    <td>{{ucwords(str_replace('_',' ',strtolower($items_list[$i]->type)))}}</td>
                                    <td>{{$items_list[$i]->slug}}</td>
                                    <td>@if(!empty($items_list[$i]->created_at)) {{date('d M Y',strtotime($items_list[$i]->created_at))}} @endif</td>
                                    <td>@if(!empty($items_list[$i]->updated_at)) {{date('d M Y',strtotime($items_list[$i]->updated_at))}} @endif</td>
                                    <td><a href="javascript:;" class="user-list-edit" onclick="editLookupItem({{$items_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $items_list->withQueryString()->links() }}
                        <p>Displaying {{$items_list->count()}} of {{ $items_list->total() }} items.</p>
                    </div>
                </div>

             </div>
        </div>
    </section>

    <div class="modal fade" id="edit_lookup_item_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Lookup Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editLookupItemSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editLookupItemErrorMessage"></div>

                <form class="" name="editLookupItemFrm" id="editLookupItemFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="itemType_edit" id="itemType_edit" class="form-control" onchange="getParentItems(this.value,'edit',0);">
                                <option value="">Select</option>
                                @for($i=0;$i<count($types_list);$i++)
                                    <option value="{{strtolower($types_list[$i])}}">{{ucwords(str_replace('_',' ',strtolower($types_list[$i])))}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_itemType_edit"></div>
                        </div>
                        <div class="form-group" id="itemTypeParentDiv_edit" style="display:none;">
                            <label>Parent</label>
                            <select name="itemTypeParent_edit" id="itemTypeParent_edit" class="form-control">
                            </select>
                            <div class="invalid-feedback" id="error_validation_itemTypeParent_edit"></div>	
                        </div>
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="itemName_edit" type="text" class="form-control" name="itemName_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_itemName_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Description</label>
                            <input id="description_edit" type="text" class="form-control" name="description_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_description_edit"></div>
                        </div>
                        <div class="form-group website-display-div" >
                            <label></label>
                            <input id="api_data_edit" type="checkbox"  name="api_data_edit" value="1" > Display on Website
                            <div class="invalid-feedback" id="error_validation_api_data_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="lookup_item_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="lookup_item_edit_cancel" name="lookup_item_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="lookup_item_edit_submit" name="lookup_item_edit_submit" class="btn btn-dialog" onclick="updateLookupItem();">Submit</button>
                        <input type="hidden" name="lookup_item_edit_id" id="lookup_item_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_lookup_item_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Lookup Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="addLookupItemSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addLookupItemErrorMessage"></div>

                <form class="" name="addLookupItemFrm" id="addLookupItemFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="itemType_add" id="itemType_add" class="form-control" onchange="getParentItems(this.value,'add',0);">
                                <option value="">Select</option>
                                @for($i=0;$i<count($types_list);$i++)
                                    <option value="{{strtolower($types_list[$i])}}">{{ucwords(str_replace('_',' ',strtolower($types_list[$i])))}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_itemType_add"></div>	
                        </div>

                        <div class="form-group" id="itemTypeParentDiv_add" style="display:none;">
                            <label>Parent</label>
                            <select name="itemTypeParent_add" id="itemTypeParent_add" class="form-control">
                            </select>
                            <div class="invalid-feedback" id="error_validation_itemTypeParent_add"></div>	
                        </div>

                        <div class="form-group" >
                            <label>Name</label>
                            <input id="itemName_add" type="text" class="form-control" name="itemName_add" value="" autofocus >
                            <div class="invalid-feedback" id="error_validation_itemName_add"></div>
                        </div>

                        <div class="form-group" >
                            <label>Description</label>
                            <input id="description_add" type="text" class="form-control" name="description_add" value="" >
                            <div class="invalid-feedback" id="error_validation_description_add"></div>
                        </div>
                        <div class="form-group website-display-div" >
                            <label></label>
                            <input id="api_data_add" type="checkbox"  name="api_data_add" value="1" > Display on Website
                            <div class="invalid-feedback" id="error_validation_api_data_add"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="lookup_item_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="lookup_item_add_cancel" name="lookup_item_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="lookup_item_add_submit" name="lookup_item_add_submit" class="btn btn-dialog" onclick="submitAddLookupItem();">Submit</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($items_list->total(),10000,'/lookup-item/list','Download Lookup Items List','Lookup Items'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/lookup_item.js?v=1.1') }}" ></script>
@endsection
