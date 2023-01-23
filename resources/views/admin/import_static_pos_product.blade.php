@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Products','link'=>'pos/product/list'),array('name'=>'Import Static POS Products')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Import Static POS Products'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                </div>
            </form>
            <div class="separator-10"></div>
           
            <div class="alert alert-danger alert-dismissible elem-hidden" id="importStaticProductsErrorMessage"></div>
            <div class="alert alert-success alert-dismissible elem-hidden" id="importStaticProductsSuccessMessage"></div>
            <div id="errorListTbl" class="elem-hidden"></div>
            
            <form method="post" name="importStaticProductsForm" id="importStaticProductsForm">
                <div class="form-group" >
                    <label>CSV File</label>
                    <input type="file" name="importStaticProductsCsvFile" id="importStaticProductsCsvFile" class="form-control"  />
                </div>
                
                <div class="form-group pull-right" >
                    <label></label>
                    <a href="{{url('documents/static_product_import_csv/static_product_sample_csv.csv')}}" target="_blank">Sample CSV File</a>
                </div>
               

                <div id="import_static_product_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                <button type="button" name="importStaticProductsCancel" id="importStaticProductsCancel" value="Cancel" class="btn btn-secondary" onclick="window.location.href='{{url('pos/product/list')}}'">Cancel</button>
                <button type="button" name="importStaticProductsSubmit" id="importStaticProductsSubmit" value="Upload CSV" class="btn btn-dialog" onclick="submitImportStaticPosProducts();">Upload CSV</button>
            </form> 
            
            <br/><br/><hr/> <h5>CSV Format</h5>
            
            <div class="table-responsive table-filter">
                <table class="table table-striped admin-table" cellspacing="0" style="font-size:12px; ">
                    <thead><tr class="header-tr">
                        <th>Product Name</th><th>SKU</th><th>Barcode</th><th>color_id</th><th>size_id</th><th>category_id</th><th>subcategory_id</th><th>story_id</th><th>season_id</th>
                        <th>Base Price</th><th>Sale Price</th>
                    </tr></thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            
            <a href="{{url('items/id/list')}}" class="btn btn-dialog" target="_blank">ID List</a>
            <div class="separator-10"></div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos_product.js?v=2.25') }}" ></script> 
@endsection
