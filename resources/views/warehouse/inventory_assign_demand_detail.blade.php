@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Assign Demands List','link'=>'warehouse/demand/inventory-assign/list'),array('name'=>'Inventory Assign Demand Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Assign Demand Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form id="demand_detail_form" name="demand_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Product">Demand Status</label>						
                        {{ucwords(str_replace('_',' ',$demand_data->demand_status))}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Color">Created By </label>						
                        {{$demand_data->user_name}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($demand_data->created_at))}}    
                    </div> 
                    
                    @if(strtolower($demand_data->demand_status) == 'approval_pending' && in_array($user->user_type,array(1,12)))
                        <div class="form-group col-md-2">
                            <label for="Color">Approve </label>						
                             <button type="button" id ="approve_demand_btn" name="approve_demand_btn" class="btn btn-dialog" onclick="updateAssignDemand('approve');">Approve</button>
                        </div> 
                        <div class="form-group col-md-2">
                            <label for="Color">Disapprove </label>						
                             <button type="button" id ="approve_demand_btn" name="approve_demand_btn" class="btn btn-dialog" onclick="updateAssignDemand('disapprove');">Disapprove</button>
                        </div>
                    @endif
                </div>    
            </form> 
            <hr/>
            
            <div id="updateDemandQtyErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandQtySuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="GET">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="product_sku" id="product_sku" class="form-control" onchange="getAssignDemandProductSKUSizeList(this.value,'detail');">
                            <option value="">Product SKU</option>
                            @for($i=0;$i<count($product_sku_list);$i++)
                                <option value="{{$product_sku_list[$i]}}">{{$product_sku_list[$i]}}</option>
                            @endfor
                        </select>
                    </div> 
                   
                    <?php /* ?><div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div><?php */ ?>
                    
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <form class="" name="updateInventoryAssignDemandFrm" id="updateInventoryAssignDemandFrm" type="POST">
                        <div class="table-responsive table-filter">
                            <table class="table table-striped admin-table" cellspacing="0" >
                                <thead><tr class="header-tr">
                                    <th>Store</th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th class="size_th" id="th_size_{{$size_list[$i]['id']}}">{{$size_list[$i]['size']}}</th>
                                    @endfor
                                    </tr></thead>
                                <tbody>
                                    <tr>
                                        <th>Available Quantity</th>
                                        @for($i=0;$i<count($size_list);$i++)
                                            <th><input type="text" name="qty_avl_{{$size_list[$i]['id']}}" id="qty_avl_{{$size_list[$i]['id']}}" class="form-control qty-text qty-avl qty-display"></th>
                                        @endfor    
                                    </tr>
                                    @for($i=0;$i<count($store_list);$i++)
                                        <tr>
                                            <td> {{$store_list[$i]['store_name']}}</td>
                                            @for($q=0;$q<count($size_list);$q++)
                                                <td><input type="text" onkeyup="checkAssignAvailableQty({{$store_list[$i]['id']}},{{$size_list[$q]['id']}});" class="form-control qty-text qty-display size_{{$size_list[$q]['id']}}" name="qty_{{$store_list[$i]['id']}}_{{$size_list[$q]['id']}}" id="qty_{{$store_list[$i]['id']}}_{{$size_list[$q]['id']}}"></td>
                                            @endfor
                                        </tr>
                                    @endfor
                                    <tr>
                                        <th>Total Assigned</th>

                                        @for($i=0;$i<count($size_list);$i++)
                                            <th><input type="text" name="qty_total_{{$size_list[$i]['id']}}" id="qty_total_{{$size_list[$i]['id']}}" class="form-control qty-text qty-total"></th>
                                        @endfor    
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>    
                </div>
            </div>
        </div>
        <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
    </section>

    <div class="modal fade" id="update_assign_demand_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="">Update Assign Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="updateAssignDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="updateAssignDemandErrorMessage"></div>

                <form class="" name="updateAssignDemandFrm" id="updateAssignDemandFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" id="updateAssignDemandDiv">
                                
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                         <div id="update_demand-spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="updateAssignDemandCancel" name="updateAssignDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="updateAssignDemandSubmit" name="updateAssignDemandSubmit" class="btn btn-dialog" onclick="submitUpdateAssignDemand();">Submit</button>
                        <input type="hidden" name="demand_update_action" id="demand_update_action" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="product_sku_success_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Success</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <form class="" name="productSkuErrorFrm" id="productSkuErrorFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" id="product_sku_success_div">
                                
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="productSkuSuccess_cancel" name="productSkuSuccess_cancel" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/warehouse_po.js?v=1.28') }}" ></script>
@endsection
