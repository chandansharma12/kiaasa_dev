@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Discount List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Discount List');?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateDiscountStatusErrorMessage" class="alert alert-danger elem-hidden"  ></div>
            <div id="updateDiscountStatusSuccessMessage" class="alert alert-success elem-hidden"  ></div>
            <form method="GET">
                <div class="row justify-content-end" >
                    <?php /* ?>
                    <div class="col-md-2">
                        <input type="text" name="sku" id="sku" class="form-control" placeholder="Product SKU" value="{{request('sku')}}">
                    </div>
                    <div class="col-md-2" >
                        <select name="discount_percent" id="discount_percent" class="form-control">
                            <option value="">Discount Percent</option>
                            
                        </select>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    <?php */ ?>
                    <div class="col-md-2" >
                        <input type="button" onclick="addGroupDiscount('multiple');" name="addGroupDiscountBtn" id="addGroupDiscountBtn" value="Add Discount" class="btn btn-dialog" >
                    </div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="discountsContainer">
                <div id="usersList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th> ID </th>    
                                <th>Type </th>    
                                <th>Discount</th>    
                                <th>GST Type</th> 
                                <th>Action</th>
                            </tr></thead>
                            <tbody>
                                <?php $currency = CommonHelper::getCurrency(); ?>
                                @for($i=0;$i<count($discount_list);$i++)
                                    <tr>
                                        <td>{{$discount_list[$i]['id']}}</td>
                                        <td>{{$discount_list[$i]['item_type']}}</td>
                                        <td>{{($discount_list[$i]['item_type'] == 'single')?$discount_list[$i]['discount'].' %':'Buy '.$discount_list[$i]['buy_items'].' Get '.$discount_list[$i]['get_items']}}</td>
                                        <td>{{$discount_list[$i]['gst_type']}}</td>
                                        <td>
                                            <a href="javascript:;" class="user-list-edit" onclick="editGroupDiscount({{$discount_list[$i]->id}},'{{$discount_list[$i]->item_type}}');"><i title="Edit Discount" class="far fa-edit"></i></a>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $discount_list->withQueryString()->links() }}
                        <p>Displaying {{$discount_list->count()}} of {{ $discount_list->total() }} discounts.</p>
                    </div>
                </div>
             </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="addDiscountDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Discount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-danger alert-dismissible elem-hidden" id="addDiscountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="addDiscountSuccessMessage"></div>
                
                <form method="post" name="addDiscountForm" id="addDiscountForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-3" >
                                <label>Discount Type</label>
                                
                                <input name="discount_type_add" id="discount_type_add" class="form-control" readonly="true" />
                                <div class="invalid-feedback" id="error_validation_discount_type"></div>
                            </div>
                            <div class="col-md-3 form-group" >
                                <label>GST Type</label>
                                <select name="gst_type_add" id="gst_type_add" class="form-control" >
                                    <option value="">GST Type</option>
                                    <option value="inclusive">Inclusive</option>
                                    <option value="exclusive">Exclusive</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_gst_type_add"></div>
                            </div>
                        </div>
                            
                        <div class="form-row elem-hidden" id="div_multiple_add">
                            <div class="col-md-3 form-group" >
                                <label>Buy Items</label>
                                <select name="buy_items_add" id="buy_items_add" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=2;$i<=100;$i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_buy_items_add"></div>
                            </div>

                            <div class="col-md-3 form-group" >
                                <label>Get Items</label>
                                <select name="get_items_add" id="get_items_add" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=2;$i<=300;$i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_get_items_add"></div>
                            </div>
                        </div>    
                        
                        <div class="form-row elem-hidden" id="div_single_add">
                            <div class="col-md-3 form-group" >
                                <label>Discount %</label>
                                <input name="discount_add" id="discount_add" class="form-control" />
                                <div class="invalid-feedback" id="error_validation_discount_add"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <button name="addDiscountCancel" id="addDiscountCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="addDiscountBtn" id="addDiscountBtn" value="Add Discount" class="btn btn-dialog" onclick="submitAddGroupDiscount();">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="editDiscountDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Discount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-danger alert-dismissible elem-hidden" id="editDiscountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="editDiscountSuccessMessage"></div>
                
                <form method="post" name="editDiscountForm" id="editDiscountForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-3" >
                                <label>Discount Type</label>
                                <input name="discount_type_edit" id="discount_type_edit" class="form-control" readonly="true" />
                                <div class="invalid-feedback" id="error_validation_discount_type"></div>
                            </div>
                            <div class="col-md-3 form-group" >
                                <label>GST Type</label>
                                <select name="gst_type_edit" id="gst_type_edit" class="form-control" >
                                    <option value="">GST Type</option>
                                    <option value="inclusive">Inclusive</option>
                                    <option value="exclusive">Exclusive</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_gst_type_edit"></div>
                            </div>
                        </div>
                            
                        <div class="form-row elem-hidden" id="div_multiple_edit">
                            <div class="col-md-3 form-group" >
                                <label>Buy Items</label>
                                <select name="buy_items_edit" id="buy_items_edit" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=2;$i<=100;$i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_buy_items_edit"></div>
                            </div>

                            <div class="col-md-3 form-group" >
                                <label>Get Items</label>
                                <select name="get_items_edit" id="get_items_edit" class="form-control" >
                                    <option value="">Select One</option>
                                    @for($i=2;$i<=300;$i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_get_items_edit"></div>
                            </div>
                        </div>    
                        
                        <div class="form-row elem-hidden" id="div_single_edit">
                            <div class="col-md-3 form-group" >
                                <label>Discount %</label>
                                <input name="discount_edit" id="discount_edit" class="form-control" />
                                <div class="invalid-feedback" id="error_validation_discount_edit"></div>
                                <input type="hidden" name="discount_id_edit" id="discount_id_edit" value="">
                            </div>
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    
                    <button name="editDiscountCancel" id="editDiscountCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="editDiscountBtn" id="editDiscountBtn" value="Add Discount" class="btn btn-dialog" onclick="submitEditGroupDiscount();">Submit</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/discount.js?v=1.52') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'yyyy-mm-dd'});</script>
<script>
</script>
@endsection
