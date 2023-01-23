@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Inventory Return Demands List','link'=>'store/demand/inventory-return/list'),array('name'=>'Inventory Return Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Inventory Return Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
            
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-1">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> 
                    @if($user->user_type == 9)
                        <div class="form-group col-md-2">
                            <label for="Season">Debit Note No</label>						
                            {{$demand_data->invoice_no}}    
                        </div> 
                    @else
                        <div class="form-group col-md-2">
                            <label for="Season">Credit Note No</label>						
                            {{$demand_data->credit_invoice_no}}    
                        </div> 
                        <div class="form-group col-md-2">
                            <label for="Season">Debit Note No</label>						
                            {{$demand_data->invoice_no}}    
                        </div> 
                    @endif
                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{CommonHelper::getDemandStatusText('inventory_return_to_warehouse',$demand_data->demand_status)}}
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$demand_data->store_name}} ({{$demand_data->store_id_code}})   
                    </div>
                
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y H:i',strtotime($demand_data->created_at))}}    
                    </div> 

                    <!--<div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$demand_data->user_name}}    
                    </div> -->
                    
                    <div class="form-group col-md-2">
                        <label for="Category">Comments</label>						
                        {{$demand_data->comments}}    
                    </div> 
                    
                    @if(in_array(strtolower($demand_data->demand_status),['warehouse_dispatched','warehouse_loaded','cancelled']) || (strtolower($demand_data->demand_status) == 'warehouse_loading' && $user->user_type == 9))
                        <div class="form-group col-md-2">
                            <label for="Category">Debit Note PDF</label>						
                            <a type="button" class="btn btn-dialog" href="{{url('store/demand/inventory-return/invoice/'.$demand_data->id.'/1')}}" title="Download Debit Note PDF">Debit Note PDF <i title="Download Debit Note Invoice" class="fas fa-download" style="color: #fff;"></i></a>
                        </div> 

                        @if($user->user_type != 9)
                            <div class="form-group col-md-2">
                                <label for="Category">Credit Note PDF</label>						
                                <a type="button" class="btn btn-dialog" href="{{url('store/demand/inventory-return/invoice/'.$demand_data->id.'/2')}}" title="Download Credit Note PDF">Credit Note PDF <i title="Download Credit Note Invoice" class="fas fa-download" style="color: #fff;"></i></a>
                            </div> 
                        @endif
                    @endif
                        
                    @if(strtolower($demand_data->demand_status) != 'cancelled')
                        @if($user->user_type == 9 && in_array(strtolower($demand_data->demand_status),array('store_loading','warehouse_dispatched')))
                            <div class="form-group col-md-2">
                                <label for="Category">Cancel Demand</label>						
                                <a type="button" class="btn btn-dialog" href="javascript:;" onclick="cancelInventoryReturnDemand();">Cancel Demand <i title="Cancel Demand" class="fas fa-crosshairs" style="color: #fff;"></i></a>
                            </div> 
                        @endif
                    
                    @endif
                    
                    @if(strtolower($demand_data->demand_status) == 'cancelled')
                        <div class="form-group col-md-2">
                            <label for="Color">Cancel Comments</label>						
                            {{$demand_data->cancel_comments}}     
                        </div>
                    @endif
                    
                </div>
                
            </form> 
            <hr/>
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <h5>Products List</h5>
                     <?php /* ?><div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>ID</th><th>Product</th><th>Size</th><th>Color</th><th>SKU</th><th>Barcode</th><th>Qty. Returned</th><th>Qty. Received</th><th>Action</th></tr></thead>
                            <tbody>
                                @if(count($product_list) == 0)
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                                @for($i=0;$i<count($product_list);$i++)
                                    <tr>
                                        <td>{{$product_list[$i]->id}}</td>
                                        <td>{{$product_list[$i]->product_name}}</td>
                                        <td>{{$product_list[$i]->size_name}}</td>
                                        <td>{{$product_list[$i]->color_name}}</td>
                                        <td>{{$product_list[$i]->vendor_sku}}</td>
                                        <td>{{$product_list[$i]->product_barcode}}</td>
                                        <td>{{$product_list[$i]->product_quantity}}</td>
                                        <td>{{$product_list[$i]->store_intake_qty}}</td>
                                        <td></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $product_list->links() }} <p>Displaying {{$product_list->count()}} of {{ $product_list->total() }} products</p>
                        
                    </div> <?php */ ?>
                     
                     <div class="table-responsive table-filter">
                        <?php $total_size = $count = 0; ?>
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>S No</th><th>Product</th><th>SKU</th><th>Color</th><th>HSN Code</th><th>Barcode</th>
                                @for($i=0;$i<count($size_list);$i++)
                                    <th>{{$size_list[$i]['size']}}</th>
                                @endfor    
                            </tr></thead>
                            <tbody>
                                
                                @foreach($products_sku as $sku=>$product_data)
                                    <tr>
                                        <td>{{++$count}}</td>
                                        <td>{{$product_data->product_name}}</td>
                                        <td>{{(!empty($product_data->vendor_sku))?$product_data->vendor_sku:$product_data->product_sku}}</td>
                                        <td>{{$product_data->color_name}}</td>
                                        <td>{{$product_data->hsn_code}}</td>
                                        <td>{{$product_data->product_barcode}}</td>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <?php $key = strtolower($product_data->product_sku).'_'.$size_list[$q]['id']; ?>
                                            <td>{{$cnt = (isset($products[$key]))?$products[$key]:0 }}</td>
                                            <?php if(isset($size_data[$size_list[$q]['id']]['count'])) $size_data[$size_list[$q]['id']]['count']+=$cnt;else $size_data[$size_list[$q]['id']]['count'] = $cnt; ?>
                                        @endfor    
                                    </tr>
                                @endforeach
                               
                                <tr>
                                    <th colspan="6">Total</th>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <th>{{$size_data[$size_list[$q]['id']]['count']}}</th>
                                        <?php $total_size+=$size_data[$size_list[$q]['id']]['count']; ?>
                                    @endfor    
                                </tr>
                                <tr>
                                    <th colspan="6">Total</th>
                                    <th colspan="{{count($size_list)}}" align="center" style="text-align: center;">{{$total_size}}</th>
                                </tr>
                                
                            </tbody>
                        </table>
                        
                    </div>
                    
                    <hr/>
                    
                    <h6>Products Inventory</h6>
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" style="font-size:13px; ">
                            <thead><tr class="header-tr">
                                <th>ID</th>
                                <th>Product Name</th>
                                @if($user->user_type == 1)
                                    <th>Piece Barcode</th>
                                @endif
                                <th>Product Barcode</th>
                                <th>SKU</th>
                                <th>Cost Price</th>
                                <th>Sale Price</th>
                                <th>Push Demand</th>
                                <th>Status</th>
                            </tr></thead>
                            <tbody>
                                <?php $total = array('inv'=>0,'base_price'=>0,'sale_price'=>0); ?>
                                @for($i=0;$i<count($product_inventory);$i++)
                                    <tr>
                                        <td>{{$product_inventory[$i]->id}}</td>
                                        <td>{{$product_inventory[$i]->product_name}} {{$product_inventory[$i]->size_name}} {{$product_inventory[$i]->color_name}}</td>
                                        @if($user->user_type == 1)
                                            <td>{{$product_inventory[$i]->peice_barcode}}</td>
                                        @endif
                                        <td>{{$product_inventory[$i]->product_barcode}}</td>
                                        <td>{{$product_inventory[$i]->vendor_sku}}</td>
                                        <td>{{$currency}} {{$product_inventory[$i]->store_base_price}}</td>
                                        <td>{{$currency}} {{$product_inventory[$i]->sale_price}}</td>
                                        <td>{{$product_inventory[$i]->push_demand_no}}</td>
                                        <td>@if($product_inventory[$i]->product_status > 0) {{strtoupper(CommonHelper::getposProductStatusName($product_inventory[$i]->product_status))}} @endif</td>
                                    </tr>
                                    <?php $total['inv']+=1; ?>
                                    <?php $total['base_price']+=$product_inventory[$i]->store_base_price; ?>
                                    <?php $total['sale_price']+=$product_inventory[$i]->sale_price; ?>
                                @endfor
                                <tr>
                                    <th colspan="4">Records: {{$total['inv']}}</th>
                                    @if($user->user_type == 1)
                                            <th></th>
                                    @endif
                                    <th>{{$currency}} {{$total['base_price']}}</th>
                                    <th>{{$currency}} {{$total['sale_price']}}</th>
                                    <th colspan="2"></th>
                                </tr>
                                @if($product_inventory->hasPages())
                                    <tr>
                                        <th colspan="4">
                                            Total Records: {{$product_inventory_count->inv_count}}
                                        </th>
                                        @if($user->user_type == 1)
                                            <th></th>
                                        @endif
                                        <th>{{$currency}} {{$product_inventory_count->store_base_price_total}}</th>
                                        <th>{{$currency}} {{$product_inventory_count->sale_price_total}}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        {{ $product_inventory->withQueryString()->links() }} <p>Displaying {{$product_inventory->count()}} of {{ $product_inventory->total() }} inventory products.</p>
                    </div>
                    
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="inventory_return_demand_cancel_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Inventory Return Demand</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="cancelInventoryReturnDemandSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="cancelInventoryReturnDemandErrorMessage"></div>
                
                <form class="" name="cancelInventoryReturnDemandForm" id="cancelInventoryReturnDemandForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="cancel_comments" type="text" class="form-control" name="cancel_comments" value="" placeholder="Comments" maxlength="200">
                                <div class="invalid-feedback" id="error_validation_comments_cancel_demand"></div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="cancelInventoryReturnDemandSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="cancelInventoryReturnDemandCancel" name="cancelInventoryReturnDemandCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="cancelInventoryReturnDemandSubmit" name="cancelInventoryReturnDemandSubmit" class="btn btn-dialog" onclick="submitCancelInventoryReturnDemand();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.45') }}" ></script>
@endsection
