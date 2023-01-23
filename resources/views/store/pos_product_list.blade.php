@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Kiasa Products')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Kiasa Products'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >

            <div id="updatePosProductStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updatePosProductStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" ></div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="posProductContainer" class="table-container">
                <div id="posProductListOverlay" class="table-list-overlay"><div id="posProduct-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="posProductList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr"><th><input type="checkbox" name="pos_product_list_all" id="pos_product_list_all"  value="1" onclick="checkAllCheckboxes(this,'pos_product-list');"> 
                                    &nbsp;<?php echo CommonHelper::getSortLink('ID','id','store/pos/product/list',true,'ASC'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Name','name','store/pos/product/list'); ?></th>    
                                    <th><?php echo CommonHelper::getSortLink('Barcode','barcode','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('SKU','sku','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Category','category','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('SubCategory','subcategory','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Base Price','base_price','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Sale Price','sale_price','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Status','status','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Created On','created','store/pos/product/list'); ?></th>
                                    <th><?php echo CommonHelper::getSortLink('Updated On','updated','store/pos/product/list'); ?></th>
                                    <th>Action</th>
                                </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($pos_product_list);$i++)
                                <tr>
                                    <td><input type="checkbox" name="pos_product_list" id="pos_product_list_{{$pos_product_list[$i]->id}}" class="pos_product-list-chk" value="{{$pos_product_list[$i]->id}}"> &nbsp;{{$pos_product_list[$i]->id}}</td>
                                    <td>{{$pos_product_list[$i]->product_name}}</td>
                                    <td>{{$pos_product_list[$i]->product_barcode}}</td>
                                    <td>{{$pos_product_list[$i]->product_sku}}</td>
                                    <td>{{$pos_product_list[$i]->category_name}}</td>
                                    <td>{{$pos_product_list[$i]->subcategory_name}}</td>
                                    <td>{{$currency}} {{$pos_product_list[$i]->base_price}}</td>
                                    <td>{{$currency}} {{$pos_product_list[$i]->sale_price}}</td>
                                    <td>@if($pos_product_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                    <td>@if(!empty($pos_product_list[$i]->created_at)) {{date('d M Y',strtotime($pos_product_list[$i]->created_at))}} @endif</td>
                                    <td>@if(!empty($pos_product_list[$i]->updated_at)) {{date('d M Y',strtotime($pos_product_list[$i]->updated_at))}} @endif</td>
                                    <td></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $pos_product_list->withQueryString()->links() }}
                        <p>Displaying {{$pos_product_list->count()}} of {{ $pos_product_list->total() }} products.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js') }}" ></script>
@endsection
