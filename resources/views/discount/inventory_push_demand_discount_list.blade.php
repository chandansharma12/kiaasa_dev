@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Demands List','link'=>'warehouse/demand/inventory-push/list'),array('name'=>'Inventory Push Demand Detail','link'=>'warehouse/demand/inventory-push/detail/'.$demand_data->id),array('name'=>'Discount List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Discount List');
    $discountType=array('1'=>'Percent','2'=>'FlatPrice','3'=>'FreeItems','4'=>'Bill');
    ?>

    <section class="product_area">
        <div class="container-fluid" >
            <h6>Invoice No: {{$demand_data->invoice_no}}</h6>
            <div id="updateDiscountStatusErrorMessage" class="alert alert-danger elem-hidden"  ></div>
            <div id="updateDiscountStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="GET">
                <div class="row justify-content-end" >
                    <div class="col-md-2">
                        <input type="text" name="sku" id="sku" class="form-control" placeholder="Product SKU" value="{{request('sku')}}">
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="discountsContainer">
                <div id="usersList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table static-header-tbl" style="font-size:12px; " cellspacing="0" >
                            <thead><tr class="header-tr">
                                    <th>SNo</th>
                                    <th>SKU </th>    
                                    <th>Discount Type </th>    
                                    <th>Percent </th>    
                                    <th>Flat Price</th> 
                                    <th>Category </th> 
                                    <th>Season </th> 
                                    <th>Store </th> 
                                    <th>SKU </th>  
                                    <th>From Price </th> 
                                    <th>To Price </th> 
                                    <th>From Date </th> 
                                    <th>To Date </th> 
                                    <th>GST Included </th>     
                                    <th>Inventory </th>     
                                </tr></thead>
                            <tbody>
                                <?php $currency = CommonHelper::getCurrency(); ?>
                                <?php $count = 1; ?>
                                @for($i=0;$i<count($discount_list);$i++)
                                <?php $sku_data = $discount_list[$i]; ?>
                                <?php if(!empty($sku_data->store_id) && $demand_data->store_id != $sku_data->store_id ) continue; ?>
                                <?php $sku_type_data = $sku_data_list[strtolower($sku_data->sku)]; ?>
                                <tr>
                                    <td>{{$count}}</td>
                                    <td>{{$sku_data->sku}}</td>
                                    <td>{{(isset($discountType[$sku_data->discount_type]))?$discountType[$sku_data->discount_type]:'' }}</td>
                                    <td>{{(!empty($sku_data->discount_percent))?$sku_data->discount_percent.' %':'' }}</td>
                                    <td>{{(!empty($sku_data->flat_price))?$currency:''}} {{$sku_data->flat_price }}</td>
                                    <td>{{(isset($category_list[$sku_data->category_id]) ?  $category_list[$sku_data->category_id] : '') }}</td>
                                    <td>{{(isset($season_list[$sku_data->season]) ?  $season_list[$sku_data->season] : '') }}</td>
                                    <td>{{(isset($store_list[$sku_data->store_id]) ?  $store_list[$sku_data->store_id] : '') }}</td>
                                    <td>{{(isset($product_sku[$sku_data->sku]) ?  $product_sku[$sku_data->sku] : '') }}</td>
                                    <td>{{$sku_data->from_price }}</td>
                                    <td>{{$sku_data->to_price }}</td>  
                                    <td>{{ date('d-m-Y', strtotime($sku_data->from_date))  }}</td>
                                    <td>{{ date('d-m-Y', strtotime($sku_data->to_date))  }}</td>
                                    <td>{{($sku_data->gst_including==1)?'Yes':'No' }}</td>
                                    <td>{{$sku_type_data['arnon_product'] == 1?'Arnon':'Northcorp'}}</td>
                                </tr>
                                <?php $count++; ?>
                                @endfor
                                
                                @for($i=0;$i<count($sku_with_no_discounts);$i++)
                                    <tr>
                                        <td>{{$count++}}</td>
                                        <td>{{$sku_with_no_discounts[$i]}}</td>
                                        <td colspan="13" class="alert alert-danger" align="center"> Discount Not Added</td>
                                    </tr>
                                @endfor   
                            </tbody>
                        </table>
                        {{ $demand_sku_list->withQueryString()->links() }} <p>Displaying {{$demand_sku_list->count()}} of {{ $demand_sku_list->total() }} SKU.</p>
                    </div>
                </div>
             </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/discount.js?v=1.51') }}" ></script>
<script src="{{asset('js/jquery.stickytableheaders.min.js')}}"></script>
@endsection
