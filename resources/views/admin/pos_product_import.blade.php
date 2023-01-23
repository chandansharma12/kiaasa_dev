@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Products List','link'=>'pos/product/list'),array('name'=>'Import POS Product')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Import POS Product'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >

            <div id="updatePosProductStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updatePosProductStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            
            <div class="separator-10">&nbsp;</div>

            <div id="posProductContainer" class="table-container">
                <div id="posProductListOverlay" class="table-list-overlay"><div id="posProduct-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="posProductList">
                    <form method="post" name="importProductForm" id="importProductForm" enctype="multipart/form-data">
                        <div class="row " >
                            
                                <?php /* ?><div class="col-md-2" >
                                    <input type="file" name="product_csv" id="product_csv" class="form-control">
                                </div><?php */ ?>
                                <div class="col-md-1" >
                                    <input type="button" name="importProductSubmit" id="importProductSubmit" value="Import" class="btn btn-dialog" onclick="this.form.submit();">
                                </div>
                        </div>
                        @csrf
                    </form>
                     
                </div>
            </div>
        </div>
    </section>

@endif
@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js') }}" ></script>
@endsection
