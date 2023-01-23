@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Warehouse Size Wise Inventory Balance Report')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Warehouse Size Wise Inventory Balance Report: '.ucfirst($inv_type)); $page_name = 'warehouse_size_wise_inventory_balance_report'; ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="reportStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="reportStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get" name="searchForm" id="searchForm">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="inv_type" id="inv_type" class="form-control">
                            <option value="">-- Inventory Type --</option>
                            <option value="arnon" @if($inv_type == 'arnon') selected @endif>Arnon</option>
                            <option value="northcorp" @if($inv_type == 'northcorp') selected @endif>Northcorp</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="cat_id" id="cat_id" class="form-control">
                            <option value="">-- Category --</option>
                            @foreach($design_items['POS_PRODUCT_CATEGORY'] as $id=>$name)
                                <?php if($id == request('cat_id')) $sel = 'selected';else $sel = ''; ?>
                                <option {{$sel}} value="{{$id}}">{{$name}}</option>
                            @endforeach   
                        </select>
                    </div>
                    
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
                    <div class="col-md-2" ><?php  ?>
                       <select name="size_sel" id="size_sel" class="form-control" >
                           <option value="">-- Size --</option>
                             <?php /* ?><option value="1,2,3,4,5,6" @if(request('size_sel') == '1,2,3,4,5,6') selected @endif>S | M | L | XL | XXL | 3XL</option>
                            <option value="1,2,3,4,5" @if(request('size_sel') == '1,2,3,4,5') selected @endif>S | M | L | XL | XXL</option>
                            <option value="1,2,3,4" @if(request('size_sel') == '1,2,3,4') selected @endif>S | M | L | XL</option>
                            <option value="1,2,3" @if(request('size_sel') == '1,2,3') selected @endif>S | M | L</option>
                            <option value="1,2" @if(request('size_sel') == '1,2') selected @endif>S | M</option>
                            <option value="1" @if(request('size_sel') == '1') selected @endif>S</option>
                            <option value="2" @if(request('size_sel') == '2') selected @endif>M</option>
                            <option value="3" @if(request('size_sel') == '3') selected @endif>L</option>
                            <option value="4" @if(request('size_sel') == '4') selected @endif>XL</option>
                            <option value="5" @if(request('size_sel') == '5') selected @endif>XXL</option>
                            <option value="6" @if(request('size_sel') == '6') selected @endif>3XL</option> <?php */ ?>
                           
                            @foreach($size_array as $id=>$name)
                                <?php $sel = (str_replace(',','_',$id) == str_replace(',','_',request('size_sel')) )?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1"><input type="button" name="search" id="search" value="Search" class="btn btn-dialog" onclick="$(this).attr('disabled',true);$('#searchForm').submit();"></div>
                    <?php $query_str = CommonHelper::getQueryString();?>
                    <div class="col-md-1"><a href="{{url('report/warehouse/size/inventory/balance?action=download_csv&'.$query_str)}}" class="btn btn-dialog" title="Download Report CSV File"><i title="Download Report CSV File" class="fa fa-download fas-icon" ></i> </a></div>
                    
                </div>
            </form>
            <div class="separator-10"></div>
            <div id="orderContainer" class="table-container">
                <div id="ordersList">
                    <div class="table-responsive table-filter">
                        <?php $total = array('inv_count'=>0,'inv_count1'=>0); ?>
                        <table class="table table-striped admin-table" cellspacing="0"  style="font-size:13px; ">
                            <thead>
                                <tr class="header-tr">
                                    <th>SNo</th>
                                    <th>Style</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Subcategory</th>
                                    <th>Color</th>
                                    <th>Season</th>
                                    <th>Story</th>
                                    <th>Supplier</th>
                                    <th>Cost Price</th>
                                    <th>Sale Price</th>
                                    @foreach($sizes as $id=>$size)
                                        <th>{{$size}}</th>
                                        <?php $total[$id] = 0; ?>
                                    @endforeach   
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                
@for($i=0;$i<count($warehouse_inv);$i++)
<?php $key = strtolower($warehouse_inv[$i]->product_sku).'_'.str_replace(' ','_',strtolower($warehouse_inv[$i]->product_name)).'_'.$warehouse_inv[$i]->color_id; ?>
<?php $size_id = $warehouse_inv[$i]->size_id; ?>
<?php $product_total = 0;  ?>
<tr >
    <td>{{$i+1}}</td>                                    
    <td>{{$warehouse_inv[$i]->product_sku}}</td>
    <td>{{$warehouse_inv[$i]->product_name}}</td>
    <td>{{isset($design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id])?$design_items['POS_PRODUCT_CATEGORY'][$warehouse_inv[$i]->category_id]:''}}</td>
    <td>{{isset($design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id])?$design_items['POS_PRODUCT_SUBCATEGORY'][$warehouse_inv[$i]->subcategory_id]:''}}</td>
    <td>{{isset($design_items['COLOR'][$warehouse_inv[$i]->color_id])?$design_items['COLOR'][$warehouse_inv[$i]->color_id]:''}}</td>
    <td>{{isset($design_items['SEASON'][$warehouse_inv[$i]->season_id])?$design_items['SEASON'][$warehouse_inv[$i]->season_id]:''}}</td>
    <td>{{isset($design_story[$warehouse_inv[$i]->story_id])?$design_story[$warehouse_inv[$i]->story_id]:''}}</td>
    @if($inv_type == 'arnon')
        <td>{{$warehouse_inv[$i]->supplier_name}}</td>
    @else
        <td>{{isset($vendors[$warehouse_inv[$i]->vendor_id])?$vendors[$warehouse_inv[$i]->vendor_id]:''}}</td>
    @endif
    <td>{{$warehouse_inv[$i]->base_price}}</td>
    <td>{{$warehouse_inv[$i]->sale_price}}</td>
    @foreach($sizes as $id=>$size)
        <td class="td-{{$id}}">{{$size_qty = isset($inv_size[$key][$id])?$inv_size[$key][$id]:0}}</td>
        <?php $product_total+=$size_qty; ?>
        <?php $total[$id]+=$size_qty; ?>
    @endforeach   
    <td>{{$product_total}}</td>
</tr>
<?php $total['inv_count1']+=$product_total; ?>
@endfor
                            </tbody>    
                            <tfoot>
                                <tr class="header-tr">
                                    <th colspan="11">Total</th>
                                    @foreach($sizes as $id=>$size)
                                        <th id="td_total_{{$id}}">{{$total[$id]}}</th>
                                    @endforeach   
                                    <th id="td_total_size">{{$total['inv_count1']}}</th>
                                </tr>
                                <?php /* ?><tr class="header-tr" >
                                    <th colspan="10">Total</th>
                                    @foreach($sizes as $id=>$size)
                                        <th>{{$size_total[$id]}}</th>
                                    @endforeach   
                                    <th>{{$inv_total->inv_count}}</th>
                                </tr><?php */ ?>
                            </tfoot>
                        </table>
                        <?php /* ?>{{ $warehouse_inv->withQueryString()->links() }}
                        <p>Displaying {{$warehouse_inv->count()}} of {{ $warehouse_inv->total() }} records.</p> <?php */ ?>
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
<script src="{{ asset('js/story.js?v=1.15') }}" ></script>
@endsection
