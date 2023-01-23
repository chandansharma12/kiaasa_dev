@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Warehouse Product Wise Inventory Balance Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Warehouse Product Wise Inventory Balance Report: Arnon');$page_name = 'warehouse_product_wise_inventory_balance_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    
                    <!--<div class="col-md-2" >
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">Inventory Type</option>

                        </select>
                    </div>-->
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="sku" id="sku" placeholder="Product SKU/Name" value="{{request('sku')}}">
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" autocomplete="off" placeholder="Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" autocomplete="off" placeholder="End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-2"><a href="{{url('report/warehouse/product/inventory/balance?action=download_csv&'.$query_str)}}" class="btn btn-dialog"  title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0"  style="font-size:13px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>Style</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Subcategory</th>
                                    
                                    <th>Bal Qty</th>
                                    <th>Size</th>
                                    <th>Color</th>
                                    <th>Season</th>
                                    <th>Story</th>
                                    <th>Supplier</th>
                                    <th>Cost Price</th>
                                    <th>Sale Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total = array('inv_count'=>0); ?>
                                @for($i=0;$i<count($warehouse_inv);$i++)
                                    <tr>
                                        <td>{{$warehouse_inv[$i]->product_sku}}</td>
                                        <td>{{$warehouse_inv[$i]->product_name}}</td>
                                        <td>{{isset($design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id])?$design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id]:''}}</td>
                                        <td>{{isset($design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id])?$design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id]:''}}</td>
                                        
                                        <td>{{$warehouse_inv[$i]->inv_count}}</td>
                                        <td>{{isset($sizes[$warehouse_inv[$i]->size_id])?$sizes[$warehouse_inv[$i]->size_id]:''}}</td>
                                        <td>{{isset($design_items['COLOR'][$warehouse_inv[$i]->color_id])?$design_items['COLOR'][$warehouse_inv[$i]->color_id]:''}}</td>
                                        <td>{{isset($design_items['SEASON'][$warehouse_inv[$i]->season_id])?$design_items['SEASON'][$warehouse_inv[$i]->season_id]:''}}</td>
                                        <td>{{isset($design_story[$warehouse_inv[$i]->story_id])?$design_story[$warehouse_inv[$i]->story_id]:''}}</td>
                                        <td>{{$warehouse_inv[$i]->supplier_name}}</td>
                                        <td>{{$warehouse_inv[$i]->base_price}}</td>
                                        <td>{{$warehouse_inv[$i]->sale_price}}</td>
                                    </tr>
                                    <?php $total['inv_count']+=$warehouse_inv[$i]->inv_count; ?>
                                    
                                @endfor
                            </tbody>    
                            <tfoot>
                                <tr >
                                    <th colspan="4">Page Total</th>
                                    <th>{{$total['inv_count']}}</th>
                                    <th colspan="7"></th>
                                </tr>
                                <tr class="header-tr" >
                                    <th colspan="4">Total</th>
                                    <th>{{$inv_total->inv_count}}</th>
                                    <th colspan="7"></th>
                                </tr>
                            </tfoot>
                        </table>
                        {{ $warehouse_inv->withQueryString()->links() }}
                        <p>Displaying {{$warehouse_inv->count()}} of {{ $warehouse_inv->total() }} records.</p>
                        <br/>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')

<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'dd-mm-yyyy'});</script>

@endsection
