@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Coupons List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Coupons List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="c_id" id="c_id" class="form-control" placeholder="Coupon ID" value="{{request('c_id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <input type="text" name="c_name" id="c_name" class="form-control" placeholder="Coupon Name" value="{{request('c_name')}}" />
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <div class="col-md-2" ><input type="button" name="addCouponBtn" id="addCouponBtn" value="Add Coupon" class="btn btn-dialog" onclick="addCoupon();"></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Coupon Items'); ?></div>
                </div>
            </form>

            <div class="separator-10">&nbsp;</div>
            
            <div id="couponsContainer">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0">
                        <thead><tr class="header-tr"><th>ID</th><th>Name</th><th>Store Name</th><th>Code</th><th>Valid From</th><th>Valid To</th>
                                <th>Coupons Count</th><th>Type</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            @for($i=0;$i<count($coupon_list);$i++)
                                <tr>
                                    <td>{{$coupon_list[$i]->id}}</td>
                                    <td>{{$coupon_list[$i]->coupon_name}}</td>
                                    <td>{{$coupon_list[$i]->store_name}}</td>
                                    <td>{{$coupon_list[$i]->store_id_code}}</td>
                                    <td>{{date('d M Y',strtotime($coupon_list[$i]->valid_from))}}</td>
                                    <td>{{date('d M Y',strtotime($coupon_list[$i]->valid_to))}}</td>
                                    <td>{{$coupon_list[$i]->items_count}}</td>
                                   <td>{{$coupon_list[$i]->coupon_type}}</td>
                                    <td>{{date('d M Y, H:i',strtotime($coupon_list[$i]->created_at))}}</td>
                                    <td>{{$coupon_list[$i]->status==1?'Enabled':'Disabled'}}</td>
                                    <td>
                                        <a href="javascript:;" onclick="editCoupon({{$coupon_list[$i]->id}});"><i title="Edit Coupon" class="fas fa-edit"></i></a>&nbsp;
                                        <a href="{{url('coupon/detail/'.$coupon_list[$i]->id)}}"><i title="Coupon Details" class="fas fa-eye"></i>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $coupon_list->withQueryString()->links() }}
                        <p>Displaying {{$coupon_list->count()}} of {{ $coupon_list->total() }} Coupons</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="add_coupon_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Coupon</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="addCouponSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addCouponErrorMessage"></div>

                <form class="" name="addCouponFrm" id="addCouponFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-3" >
                                <label>Name</label>
                                <input id="couponName_add" type="text" class="form-control" name="couponName_add" value="">
                                <div class="invalid-feedback" id="error_validation_couponName_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Coupons Count</label>
                                <input id="couponItemsCount_add" type="text" class="form-control" name="couponItemsCount_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_couponItemsCount_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Store</label>
                                <select name="couponStore_add" id="couponStore_add" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($store_list);$i++)
                                        <option value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_couponStore_add"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Discount</label>
                                <input id="couponDiscount_add" type="text" class="form-control" name="couponDiscount_add" value=""  >
                                <div class="invalid-feedback" id="error_validation_couponDiscount_add"></div>
                            </div>
                        </div> 
                        <div class="form-row input-daterange" >
                            
                            <div class="form-group col-md-4" >
                                <label>Valid From</label>
                                <input id="couponValidFrom_add" type="text" class="form-control datepicker" name="couponValidFrom_add" value="" >
                                <div class="invalid-feedback" id="error_validation_couponValidFrom_add"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Valid To</label>
                                <input id="couponValidTo_add" type="text" class="form-control datepicker" name="couponValidTo_add" value="" >
                                <div class="invalid-feedback" id="error_validation_couponValidTo_add"></div>
                            </div>
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-3" >
                                <label>Coupon Type</label>
                                <select name="couponType_add" id="couponType_add" class="form-control">
                                    <option value="">Select</option>
                                    <option value="unique">Unique Coupons</option>
                                    <option value="common">Common Coupon</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_couponType_add"></div>
                            </div>
                        </div>    
                    </div>
                    
                    <div class="modal-footer center-footer">
                        <div id="coupon_add_spinner"  class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="couponAdd_cancel" name="couponAdd_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="couponAdd_submit" name="couponAdd_submit" class="btn btn-dialog" onclick="submitAddCoupon();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="edit_coupon_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Coupon</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden"  id="editCouponSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editCouponErrorMessage"></div>

                <form class="" name="editCouponFrm" id="editCouponFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Name</label>
                                <input id="couponName_edit" type="text" class="form-control" name="couponName_edit" value="">
                                <div class="invalid-feedback" id="error_validation_couponName_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Coupons Count</label>
                                <input id="couponItemsCount_edit" type="text" class="form-control" name="couponItemsCount_edit" value=""  readonly="true">
                                <div class="invalid-feedback" id="error_validation_couponItemsCount_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Store</label>
                                <select name="couponStore_edit" id="couponStore_edit" class="form-control">
                                    <option value="">Select</option>
                                    @for($i=0;$i<count($store_list);$i++)
                                        <option value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_couponStore_edit"></div>
                            </div>
                        </div> 
                        <div class="form-row input-daterange" >
                            
                            <div class="form-group col-md-4" >
                                <label>Valid From</label>
                                <input id="couponValidFrom_edit" type="text" class="form-control datepicker" name="couponValidFrom_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_couponValidFrom_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Valid To</label>
                                <input id="couponValidTo_edit" type="text" class="form-control datepicker" name="couponValidTo_edit" value="" >
                                <div class="invalid-feedback" id="error_validation_couponValidTo_edit"></div>
                            </div>
                            
                        </div>    
                        <div class="form-row" >
                            <div class="form-group col-md-4" >
                                <label>Discount</label>
                                <input id="couponDiscount_edit" type="text" class="form-control" name="couponDiscount_edit" value=""  >
                                <div class="invalid-feedback" id="error_validation_couponDiscount_edit"></div>
                            </div>
                            <div class="form-group col-md-3" >
                                <label>Coupon Type</label>
                                <select name="couponType_edit" id="couponType_edit" class="form-control" disabled="true">
                                    <option value="">Select</option>
                                    <option value="unique">Unique Coupons</option>
                                    <option value="common">Common Coupon</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_couponType_edit"></div>
                            </div>
                            <div class="form-group col-md-4" >
                                <label>Status</label>
                                <select name="couponStatus_edit" id="couponStatus_edit" class="form-control" >
                                    <option value="">Select</option>
                                    <option value="1">Enabled</option>
                                    <option value="0">Disabled</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_couponStatus_edit"></div>
                            </div>
                        </div>    
                        
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="coupon_edit_spinner"  class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="couponEdit_cancel" name="couponEdit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="couponEdit_submit" name="couponEdit_submit" class="btn btn-dialog" onclick="submitEditCoupon();">Submit</button>
                        <input type="hidden" name="coupon_edit_id" id="coupon_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php echo CommonHelper::displayDownloadDialogHtml($coupon_list->total(),10000,'/coupon/list','Download Coupons List','Coupons'); ?>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>
<script src="{{ asset('js/coupon.js?v=1.1') }}" ></script>
@endsection
