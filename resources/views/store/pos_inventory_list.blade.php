@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Inventory'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    @if($is_fake_inventory_user)    
                        <div class="col-md-2" >
                            <select name="store_id" id="store_id"  class="form-control">
                                <option value="">-- Store --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php if($store_list[$i]['id'] == request('store_id')) $sel = 'selected';else $sel = ''; ?>    
                                    <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                                @endfor    
                            </select>
                        </div>
                    @endif
                    
                    <div class="col-md-2" >
                        <input type="text" name="barcode" id="barcode" class="form-control" placeholder="Product Name / Barcode / SKU" value="{{request('barcode')}}">
                    </div>
                    <div class="col-md-2" >
                        <select name="status" id="status" class="form-control">
                            <option value="">All Status</option>
                            @foreach($status_list as $id=>$status_name)
                                <?php if($id == 0 || $id == 1) continue; ?>
                                <?php $sel = ($id == request('status') && request('status') != '')?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$status_name}}</option>
                            @endforeach    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="category_search" id="category_search"  class="form-control search-select-1" onchange="getPosProductSubcategories(this.value,'search');">
                            <option value="">Category</option>
                            @for($i=0;$i<count($category_list);$i++)
                                <?php if($category_list[$i]['id'] == request('category_search')) $sel = 'selected';else $sel = ''; ?>    
                                <option {{$sel}} value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="product_subcategory_search" id="product_subcategory_search"  class="form-control search-select-1" >
                            <option value="">Sub Category</option>
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="inv_type" id="inv_type"  class="form-control " >
                            <option value="">-- All Inventory --</option>
                            <option <?php if(request('inv_type') == 1) echo 'selected'; ?> value="1">Kiaasa</option>
                            <option <?php if(request('inv_type') == 2) echo 'selected'; ?> value="2">Arnon</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                    <div class="col-md-1"><?php echo CommonHelper::displayDownloadDialogButton('Inventory List'); ?></div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                            <th><?php echo CommonHelper::getSortLink('Product ID','product_id','store/pos/inventory/list'); ?></th>    
                            <th><?php echo CommonHelper::getSortLink('Product Name','product_name','store/pos/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('SKU','sku','store/pos/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Inventory','inventory','store/pos/inventory/list'); ?></th>
                            @if(!$is_fake_inventory_user)
                                <th><?php echo CommonHelper::getSortLink('Product Barcode','product_barcode','store/pos/inventory/list'); ?></th>
                            @endif
                            <th><?php echo CommonHelper::getSortLink('Category','category','store/pos/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('SubCategory','subcategory','store/pos/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Cost Price','base_price','store/pos/inventory/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Sale Price','sale_price','store/pos/inventory/list'); ?></th>
                        </tr></thead>
                        <tbody>
                            <?php  ?>
                            @for($i=0;$i<count($products_list);$i++)
                                <tr>
                                    <td>{{$products_list[$i]->product_master_id}}</td>
                                    <td>{{$products_list[$i]->product_name}} {{$products_list[$i]->size_name}} {{$products_list[$i]->color_name}}</td>
                                    <td>{{$products_list[$i]->product_sku}}</td>
                                    <td>{{$products_list[$i]->inventory_count}}</td>
                                    @if(!$is_fake_inventory_user)    
                                        <td>{{$products_list[$i]->product_barcode}}</td>
                                    @endif
                                    <td>{{$products_list[$i]->category_name}}</td>
                                    <td>{{$products_list[$i]->subcategory_name}}</td>
                                    <td>{{$currency}} {{$products_list[$i]->store_base_rate}}</td>
                                    <td>{{$currency}} {{$products_list[$i]->sale_price}}</td>
                                </tr>
                                <?php ?>
                            @endfor
                            <tr>
                                <th colspan="3">Total</th>
                                <th>{{$inventory_count}}</th>
                                <th colspan="5"></th>
                            </tr>
                        </tbody>
                    </table>
                    {{ $products_list->withQueryString()->links() }} <p>Displaying {{$products_list->count()}} of {{ $products_list->total() }} products.</p>
                </div>
            </div>
        </div>
    </section>

    <?php echo CommonHelper::displayDownloadDialogHtml($products_list->total(),10000,'/store/pos/inventory/list','Download Inventory List','Inventory'); ?>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
<script src="{{ asset('js/pos_product.js') }}" ></script>
@if(!empty(request('category_search')))
    <script type="text/javascript">
        getPosProductSubcategories({{request('category_search')}},'search',"{{request('product_subcategory_search')}}");
    </script>        
@endif
@endsection
