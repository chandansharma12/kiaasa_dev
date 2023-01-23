@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Products Inventory List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Products Inventory List'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="store_id" id="store_id" class="form-control">
                            <option value="">All Stores</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="status" id="status" class="form-control">
                            <option value="">All Status</option>
                            @foreach($status_list as $id=>$status_name)
                                <?php $sel = ($id == request('status'))?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$status_name}}</option>
                            @endforeach    
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div id="demandListOverlay" class="table-list-overlay"><div id="demand-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>Product Name</th><th>Store</th><th>Barcode</th><th>SKU</th><th>Category</th><th>SubCategory</th><th>Base Price</th><th>Sale Price</th><th>Status</th>
                            </tr></thead>
                            <tbody>
                                <?php  ?>
                                @for($i=0;$i<count($products_list);$i++)
                                    <tr>
                                        <td>{{$products_list[$i]->product_name}}</td>
                                        <td>{{$products_list[$i]->store_name}}</td>
                                        <td>{{$products_list[$i]->peice_barcode}}</td>
                                        <td>{{$products_list[$i]->product_sku}}</td>
                                        <td>{{$products_list[$i]->category_name}}</td>
                                        <td>{{$products_list[$i]->subcategory_name}}</td>
                                        <td>{{$currency}} {{$products_list[$i]->base_price}}</td>
                                        <td>{{$currency}} {{$products_list[$i]->sale_price}}</td>
                                        <td>{{strtoupper(CommonHelper::getposProductStatusName($products_list[$i]->product_status))}}</td>
                                    </tr>
                                    <?php ?>
                                @endfor
                            </tbody>
                        </table>
                        {{ $products_list->links() }} <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} products.</p>
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
