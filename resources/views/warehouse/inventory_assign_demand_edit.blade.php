@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Assign Demands List','link'=>'warehouse/demand/inventory-assign/list'),array('name'=>'Edit Inventory Assign Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Inventory Assign Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    @if(strtolower($demand_data->demand_status) == 'approval_pending' && in_array($user->user_type,array(6,12)))
        <section class="product_area">
            <div class="container-fluid" >

                <div id="updateDemandQtyErrorMessage" class="alert alert-danger elem-hidden" ></div>
                <div id="updateDemandQtySuccessMessage" class="alert alert-success elem-hidden" ></div>
                <form method="get">
                    <div class="row justify-content-end" >
                        <div class="col-md-2" >
                            <select name="product_sku" id="product_sku" class="form-control" onchange="getAssignDemandProductSKUSizeList(this.value,'edit');">
                                <option value="">Product SKU</option>
                                @for($i=0;$i<count($product_sku);$i++)
                                    <option value="{{$product_sku[$i]->product_sku}}">{{$product_sku[$i]->product_sku}}</option>
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
                                                <th><input type="text" name="qty_avl_{{$size_list[$i]['id']}}" id="qty_avl_{{$size_list[$i]['id']}}" class="form-control qty-text qty-avl"></th>
                                            @endfor    
                                        </tr>
                                        @for($i=0;$i<count($store_list);$i++)
                                            <tr>
                                                <td> {{$store_list[$i]['store_name']}}</td>
                                                @for($q=0;$q<count($size_list);$q++)
                                                    <td><input type="text" onkeyup="checkAssignAvailableQty({{$store_list[$i]['id']}},{{$size_list[$q]['id']}});" class="form-control qty-text size_{{$size_list[$q]['id']}}" name="qty_{{$store_list[$i]['id']}}_{{$size_list[$q]['id']}}" id="qty_{{$store_list[$i]['id']}}_{{$size_list[$q]['id']}}"></td>
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

                                <div class="separator-10"></div>
                                <input type="button" name="updateQtyBtn" id="updateQtyBtn" value="Update" class="btn btn-dialog" onclick="updateInventoryAssignDemandQty();"> &nbsp; 
                                <div id="update_inventory_assign_demand_spinner"  class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                                <div class="separator-10">&nbsp;</div>

                            </div>
                        </form>    
                    </div>
                </div>
            </div>
            <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
        </section>
    @endif

    <div class="modal fade" id="product_sku_error_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="productSkuSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="productSkuErrorMessage"></div>

                <form class="" name="productSkuErrorFrm" id="productSkuErrorFrm" type="POST">
                    <div class="modal-body">
                        <div class="modal-body">
                            <div class="form-group" id="product_sku_error_div">
                                Please select Product SKU
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="productSku_cancel" name="productSku_cancel" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
<script src="{{ asset('js/warehouse_po.js?v=1.35') }}" ></script>
@endsection
