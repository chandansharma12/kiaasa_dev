@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Accessories List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Accessories List (Non Trading Items)'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateAccessoryStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateAccessoryStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1">
                        <input type="text" name="acc_id" id="acc_id" class="form-control" value="{{request('acc_id')}}" placeholder="ID">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="acc_name" id="acc_name" class="form-control" value="{{request('acc_name')}}" placeholder="Name">
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" ><input type="button" name="addAccessoryBtn" id="addAccessoryBtn" value="Add Accessory" class="btn btn-dialog" onclick="addAccessory();"></div>
                </div>
            </form>
            
            <div class="separator-10">&nbsp;</div>

            <div id="accessoryContainer">
                
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                                <th>ID</th><th><!--<input type="checkbox" name="accessory_list_all" id="accessory_list_all"  value="1" onclick="selectAllCategory(this);"> &nbsp;-->Accessory Name</th>
                                <th>Rate</th><th>GST%</th><th>Description</th><th>Created On</th><th>Updated On</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($accessories_list);$i++)
                            <tr>
                                <td>{{$accessories_list[$i]->id}}</td>     
                                <td><!--<input type="checkbox" name="accessory_list" id="accessory_list_{{$accessories_list[$i]->id}}" class="accessory-list" value="{{$accessories_list[$i]->id}}"> &nbsp;-->{{$accessories_list[$i]->accessory_name}}</td>
                                <td>{{$accessories_list[$i]->rate}}</td>
                                <td>{{$accessories_list[$i]->gst_percent}}</td>
                                <td>{{$accessories_list[$i]->description}}</td>
                                <td>@if(!empty($accessories_list[$i]->created_at)) {{date('d M Y',strtotime($accessories_list[$i]->created_at))}} @endif</td>
                                <td>@if(!empty($accessories_list[$i]->updated_at)) {{date('d M Y',strtotime($accessories_list[$i]->updated_at))}} @endif</td>
                                <td><a href="javascript:;" class="user-list-edit" onclick="editAccessory({{$accessories_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $accessories_list->links() }}
                    <p>Displaying {{$accessories_list->count()}} of {{ $accessories_list->total() }} accessories.</p>
                </div>
                
             </div>
        </div>
    </section>

    <div class="modal fade" id="edit_accessory_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Accessory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="editAccessorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editAccessoryErrorMessage"></div>

                <form class="" name="editAccessoryFrm" id="editAccessoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Name</label>
                            <input id="acc_name_edit" type="text" class="form-control" name="acc_name_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_acc_name_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Rate</label>
                            <input id="acc_rate_edit" type="text" class="form-control" name="acc_rate_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_acc_rate_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>GST %</label>
                            <input id="acc_gst_edit" type="text" class="form-control" name="acc_gst_edit" value="" >
                            <div class="invalid-feedback" id="error_validation_acc_gst_edit"></div>
                        </div>
                        <div class="form-group" >
                            <label>Description</label>
                            <textarea id="acc_desc_edit" type="text" class="form-control" name="acc_desc_edit" value="" ></textarea>
                            <div class="invalid-feedback" id="error_validation_acc_desc_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="accessory_edit_cancel" name="accessory_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="accessory_edit_submit" name="accessory_edit_submit" class="btn btn-dialog" onclick="updateAccessory();">Submit</button>
                        <input type="hidden" name="accessory_edit_id" id="accessory_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_accessory_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add New Accessory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addAccessorySuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addAccessoryErrorMessage"></div>

                <form class="" name="addAccessoryFrm" id="addAccessoryFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" >
                                <label>Name</label>
                                <input id="acc_name_add" type="text" class="form-control" name="acc_name_add" value="" >
                                <div class="invalid-feedback" id="error_validation_acc_name_add"></div>
                            </div>
                            <div class="form-group" >
                                <label>Rate</label>
                                <input id="acc_rate_add" type="text" class="form-control" name="acc_rate_add" value="" >
                                <div class="invalid-feedback" id="error_validation_acc_rate_add"></div>
                            </div>
                            <div class="form-group" >
                                <label>GST %</label>
                                <input id="acc_gst_add" type="text" class="form-control" name="acc_gst_add" value="" >
                                <div class="invalid-feedback" id="error_validation_acc_gst_add"></div>
                            </div>
                            <div class="form-group" >
                                <label>Description</label>
                                <textarea id="acc_desc_add" type="text" class="form-control" name="acc_desc_add" value="" ></textarea>
                                <div class="invalid-feedback" id="error_validation_acc_desc_add"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="accessory_add_cancel" name="accessory_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="accessory_add_submit" name="accessory_add_submit" class="btn btn-dialog" onclick="submitAddAccessory();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/purchaser.js') }}" ></script>
@endsection
