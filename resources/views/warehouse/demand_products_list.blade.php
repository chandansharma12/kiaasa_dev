@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Demands List','link'=>'store/demand/list'),array('name'=>'Demand Products')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Demand Products'); ?>  

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <form id="demand_detail_form" name="demand_detail_form" method="post">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Product">Demand Status</label>						
                        {{str_replace('_',' ',$demand_data->demand_status)}}    
                    </div> 
                     <div class="form-group col-md-2">
                        <label for="Category">Store</label>						
                        {{$demand_data->store_name}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y',strtotime($demand_data->created_at))}}    
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Category">Created by</label>						
                        {{$demand_data->user_name}}    
                    </div> 
                    <div class="form-group col-md-2">
                        
                        @if(strtolower($demand_data->demand_status) != 'closed')
                            <a href="javascript:;" onclick="addDemandCourier();" class="btn btn-pdf">Gate Pass PDF</a>
                        @else
                            <a href="{{url('documents/gate_pass_documents/'.$demand_data->id.'/'.$demand_data->courier_id.'.pdf')}}" target="_blank"  class="btn btn-pdf">Gate Pass PDF</a>
                        @endif
                        &nbsp;<a href="{{url('store/demand/pdf/invoice/'.$demand_data->id)}}" class="btn  btn-pdf">Invoice PDF</a>
                    </div>
                    
                </div>    
                <hr/>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>Product Name</th><th>Barcode</th><th>Price</th><th>Quantity</th><th>Total Price</th>
                        </tr></thead>
                        <tbody>
                            <?php $total_price = $qty_demanded_total =  $qty_loaded_total = $qty_available_total = 0; ?>
                            @for($i=0;$i<count($demand_products);$i++)
                                <tr>
                                    <td>{{$demand_products[$i]->product_name}}</td>
                                    <td>{{$demand_products[$i]->product_barcode}}</td>
                                    <td>{{$currency}} {{$demand_products[$i]->base_price}}</td>
                                    <td>Demanded: {{$demand_products[$i]->product_quantity}}, Loaded: {{$demand_products[$i]->loaded}}, Available: {{$demand_products[$i]->available}}</td>
                                    <td>{{$currency}} {{round($demand_products[$i]->product_quantity*$demand_products[$i]->base_price,2)}}</td>
                                </tr>
                                <?php $total_price+=($demand_products[$i]->product_quantity*$demand_products[$i]->base_price); ?>
                                <?php $qty_demanded_total+=$demand_products[$i]->product_quantity; ?>
                                <?php $qty_loaded_total+=$demand_products[$i]->loaded; ?>
                                <?php $qty_available_total+=$demand_products[$i]->available; ?>
                            @endfor
                            <tr class="total-tr"><th colspan="3">Total</strong></th><th>Demanded: {{$qty_demanded_total}}, Loaded: {{$qty_loaded_total}}, Available: {{$qty_available_total}} </th>
                            <th>{{$currency}} {{$total_price}}</th></tr>
                        </tbody>
                    </table>
                </div>
                @if(strtolower($demand_data->demand_status) != 'closed')
                    <hr/>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="Season"><b>Load Product:</b></label>						
                            <input type="text" name="barcode" id="barcode" class="form-control" placeholder="Product Barcode">
                        </div> 
                        <div class="form-group col-md-2">
                            <label for="Season">&nbsp;</label>	
                            <input type="submit" class="btn  btn-pdf" value="Add Product" name="add_product_submit" id="add_product_submit">
                            <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->store_id}}">
                        </div>
                    </div>
                @endif
                @csrf
            </form> 
            <hr/>
            <div id="demandContainer" class="table-container">
                <div id="demandsList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>Product Name</th><th>Piece Barcode</th><th>SKU</th><th>Category</th><th>SubCategory</th><th>Base Price</th><th>Sale Price</th><th>Status</th>
                            </tr></thead>
                            <tbody>
                                <?php  ?>
                                @for($i=0;$i<count($demand_products_list);$i++)
                                    <tr>
                                        <td>{{$demand_products_list[$i]->product_name}}</td>
                                        <td>{{$demand_products_list[$i]->peice_barcode}}</td>
                                        <td>{{$demand_products_list[$i]->product_sku}}</td>
                                        <td>{{$demand_products_list[$i]->category_name}}</td>
                                        <td>{{$demand_products_list[$i]->subcategory_name}}</td>
                                        <td>{{$currency}} {{$demand_products_list[$i]->base_price}}</td>
                                        <td>{{$currency}} {{$demand_products_list[$i]->base_price}}</td>
                                        <td>{{strtoupper(CommonHelper::getposProductStatusName($demand_products_list[$i]->product_status))}}</td>
                                    </tr>
                                    <?php ?>
                                @endfor
                            </tbody>
                        </table>
                        {{ $demand_products_list->links() }} <p>Displaying {{$demand_products_list->count()}} of {{ $demand_products_list->total() }} products.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="store_demand_courier_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Courier Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="storeDemandCourierErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="storeDemandCourierSuccessMessage"></div>
                <form method="post" name="storeDemandCourierForm" id="storeDemandCourierForm">
                    <div class="modal-body">                    
                        <div class="form-group" >
                            <label>Courier Details</label>
                            <input id="courier_detail_add" type="text" class="form-control" name="courier_detail_add" value="">
                            <div class="invalid-feedback" id="error_validation_courier_detail_add"></div>
                        </div>
                        <div class="form-group" >
                            <label>Vehicle Details</label>
                            <input id="vehicle_detail_add" type="text" class="form-control" name="vehicle_detail_add" value="">
                            <div class="invalid-feedback" id="error_validation_vehicle_detail_add"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="store_demand_courier_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="store_demand_courier_add_cancel" name="store_demand_courier_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="store_demand_courier_add_submit" name="store_demand_courier_add_submit" class="btn btn-dialog" onclick="submitAddDemandCourier({{$demand_data->id}});">Submit</button>
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
