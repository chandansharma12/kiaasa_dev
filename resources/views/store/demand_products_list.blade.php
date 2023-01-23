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
                </div>    
                <hr/>
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th>Product Name</th><th>Barcode</th><th>Price</th><th>Quantity</th><th>Total Price</th>
                        </tr></thead>
                        <tbody>
                            <?php $total_price = $qty_demanded_total = $qty_received_total = $qty_loaded_total = 0; ?>
                            @for($i=0;$i<count($demand_master_products);$i++)
                                <tr>
                                    <td>{{$demand_master_products[$i]->product_name}} {{$demand_master_products[$i]->size_name}} {{$demand_master_products[$i]->color_name}}</td>
                                    <td>{{$demand_master_products[$i]->product_barcode}}</td>
                                    <td>{{$currency}} {{$demand_master_products[$i]->base_price}}</td>
                                    <td>Demanded: {{$demand_master_products[$i]->product_quantity}}, Received: {{$demand_master_products[$i]->received}}, Loaded: {{$demand_master_products[$i]->loaded}}</td>
                                    <td>{{$currency}} {{round($demand_master_products[$i]->product_quantity*$demand_master_products[$i]->base_price,2)}}</td>
                                </tr>
                                <?php $total_price+=($demand_master_products[$i]->product_quantity*$demand_master_products[$i]->base_price); ?>
                                <?php $qty_demanded_total+=$demand_master_products[$i]->product_quantity; ?>
                                <?php $qty_received_total+=$demand_master_products[$i]->received; ?>
                                <?php $qty_loaded_total+=$demand_master_products[$i]->loaded; ?>
                            @endfor
                            <tr class="total-tr"><th colspan="3">Total</strong></th><th>Demanded: {{$qty_demanded_total}}, Received: {{$qty_received_total}}, Loaded: {{$qty_loaded_total}}</th><th>{{$currency}} {{$total_price}}</th></tr>
                        </tbody>
                    </table>
                </div>
                <hr/>
                <div class="form-row">
                    <div class="form-group col-md-1">
                        <label for="Season"><b>Add Product:</b></label>						
                        <input type="text" name="product_barcode" id="product_barcode" class="form-control" placeholder="Product Barcode">
                        
                    </div> 
                    <div class="form-group col-md-2">
                        <label for="Season"><b>&nbsp;</b></label>		
                        <input type="text" name="product_comments" id="product_comments" class="form-control" placeholder="Comments" maxlength="250">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Season">&nbsp;</label>	
                        <input type="submit" class="btn  btn-pdf" value="Add Product" name="add_product_submit" id="add_product_submit">
                    </div>
                </div>
                @csrf
            </form> 
            <hr/>
            <div id="demandContainer" class="table-container">
                <div id="demandsList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>Product Name</th><th>Barcode</th><th>SKU</th><th>Category</th><th>SubCategory</th><th>Base Price</th><th>Sale Price</th><th>Status</th><th>Comments</th>
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
                                        <td>{{$demand_products_list[$i]->store_comments}}</td>
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
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
