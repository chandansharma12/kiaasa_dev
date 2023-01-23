@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Discounts List','link'=>'discount/list'),array('name'=>'Add Multiple Discounts')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Add Multiple Discounts');
    $discountType=array('1'=>'Percent','2'=>'FlatPrice','3'=>'FreeItems','4'=>'Bill');?>

    <section class="product_area">
        <div class="container-fluid" >

            <form method="GET">
                <div class="row justify-content-end" >
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="discountsContainer">
               
                <form method="post" name="addDiscountForm" id="addDiscountForm">

                    <div class="form-row">
                        <div class="form-group col-md-2" >
                            <label>Discount Type</label>
                            <select name="discount_type" onchange="discountType(this.value)" id="discount_type" class="form-control">
                                <option value="">--Discount Type--</option>
                                <option value="1">Percent</option>
                                <option value="2">Flat Price</option>

                            </select>
                            <div class="invalid-feedback" id="error_validation_discount_type"></div>
                        </div>

                        <!--<div class="col-md-3 form-group"  >
                            <label>Search SKU</label>
                            <input type="text" name="sku_search" id="sku_search" class="form-control" placeholder="SKU" value="" />
                        </div>

                        <div class="col-md-2 form-group" >
                            <label>&nbsp;</label>
                            <input type="button" style="margin-top:15px;" name="skuSearchBtn" id="skuSearchBtn" value="Search" class="btn btn-dialog" onclick="searchAddDiscountSKU();">
                        </div>-->

                        <div class="col-md-2 form-group" id="flatPriceDiv" style="display:none">
                            <label>Flat Price</label>
                            <input type="text" name="flat_price" id="flat_price" class="form-control" placeholder="Flat Price" value="" />
                            <div class="invalid-feedback" id="error_validation_flat_price"></div>
                        </div>

                        <div class="col-md-2 form-group" id="percentDiscountDiv" style="display:none">
                            <label>Discount %</label>
                            <input type="text" name="discount_percent" id="discount_percent" class="form-control" placeholder="Discount %" value="" />
                            <div class="invalid-feedback" id="error_validation_discount_percent"></div>
                        </div>
                       <div class="col-md-2 form-group" >
                            <label>Inventory Type</label>
                            <select name="inv_type" id="inv_type" class="form-control" >
                                <option value="">Inventory Type</option>
                                <option value="1">NorthCorp</option>
                                <option value="2">Arnon</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_inv_type"></div>
                        </div>
                        <div class="col-md-2 form-group" >
                            <label>GST Type</label>
                            <select name="gst_type" id="gst_type" class="form-control" >
                                <option value="">--GST Type--</option>
                               <option value="inclusive">Inclusive</option>
                               <option value="exclusive">Exclusive</option>
                            </select>
                            <div class="invalid-feedback" id="error_validation_gst_type"></div>
                        </div>
                        <div class="col-md-2 form-group" >
                            <label>Category</label>
                            <select name="category_id" id="category_id" class="form-control" >
                                <option value="">--Category--</option>
                                @for($i=0;$i<count($category_list);$i++)
                                    <option value="{{$category_list[$i]['id']}}">{{$category_list[$i]['name']}} </option>
                                @endfor
                            </select>
                            <div class="invalid-feedback" id="error_validation_category_id"></div>
                        </div>        
                        <div class="col-md-2 form-group" >
                            <label>Season</label>
                            <select name="season_id" id="season_id" class="form-control" >
                                <option value="">--Season--</option>
                                @for($i=0;$i<count($season_list);$i++)
                                    <option value="{{$season_list[$i]['id']}}">{{$season_list[$i]['name']}} </option>
                                @endfor
                            </select>
                            <div class="invalid-feedback" id="error_validation_season_id"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-2 input-daterange form-group" >
                            <label>From Date</label>
                            <input type="text" class="form-control datepicker" autocomplete="off" name="from_date" id="from_date" placeholder="Select Date" value="">   
                            <div class="invalid-feedback" id="error_validation_from_date"></div>
                        </div>

                        <div class="col-md-2 input-daterange form-group" >
                            <label>To Date</label>
                            <input type="text" class="form-control datepicker" autocomplete="off"  name="to_date" id="to_Date" placeholder="Select Date" value="">
                            <div class="invalid-feedback" id="error_validation_to_date"></div>
                        </div>

                         <div class="col-md-2 form-group" >
                            <label>From Price</label>
                            <input type="text" class="form-control"  name="from_price" id="from_price" placeholder="From Price" value="">   
                            <div class="invalid-feedback" id="error_validation_from_price"></div>
                        </div>

                        <div class="col-md-2 form-group" >
                            <label>To Price</label>
                            <input type="text" class="form-control"  name="to_price" id="to_price" placeholder="To Price" value="">
                            <div class="invalid-feedback" id="error_validation_to_price"></div>
                        </div>

                    </div>   
                        
                    <div class="form-row">
                         <div class="col-md-3 form-group"  >
                            <label>Search SKU</label>
                            <input type="text" name="sku_search" id="sku_search" class="form-control" placeholder="SKU" value="" />
                        </div>

                        <div class="col-md-3 form-group" >
                            <label>&nbsp;</label>
                            <input type="button" style="margin-top:15px;" name="skuSearchBtn" id="skuSearchBtn" value="Add SKU" class="btn btn-dialog" onclick="searchAddMultipleDiscountSKU();">&nbsp;
                            <input type="button" style="margin-top:15px;" name="importSKUBtn" id="importSKUBtn" value="Import SKU" class="btn btn-dialog" onclick="importMultipleDiscountSKU();">
                        </div>

                        <div class="col-md-3 form-group"  >
                             <label>&nbsp;</label>
                            <div id="searchMultipleDiscountSKUErrorMessage" class="alert alert-danger elem-hidden"  ></div>
                            <div id="searchMultipleDiscountSKUSuccessMessage" class="alert alert-success elem-hidden"  ></div>
                        </div>
                    </div>
                </form>
                
                <div class="invalid-feedback" id="error_validation_sku_list"></div>
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" >
                        <thead><tr class="header-tr">
                                <th colspan="10">
                                    <input type="checkbox" name="sku_list_all" id="sku_list_all"  value="1" onclick="checkAllCheckboxes(this,'sku-list');"> SKU 
                                    <a style="margin-right:15px;float:right;" href="javascript:;" class="user-list-edit" onclick="deleteAddMultipleDiscountSKU();"><i title="Delete SKU" class="fa fa-trash" style="color:#fff;"></i></a>
                                </th>    
                                
                        </tr></thead>
                        <tbody id="sku_list_table">
                            <tr><td>No Records</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="invalid-feedback" id="error_validation_store_list"></div>
                <div id="updateStoresErrorMessage" class="alert alert-danger elem-hidden" ></div>
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                            <th colspan="10">
                                <input type="checkbox" name="discount_list_all" id="discount_list_all"  value="1" onclick="checkAllCheckboxes(this,'store-list');"> Stores 
                                
                                <select name="zone_id" id="zone_id"  style="width:100px;margin-left: 20px;background-color: #fff;border:1px solid #fff;" onchange="updateMultipleDiscountStoresByZone(this.value);">
                                    <option value="">--Zone--</option>
                                    @for($i=0;$i<count($zone_list);$i++)
                                        <option value="{{$zone_list[$i]['id']}}">{{$zone_list[$i]['name']}} </option>
                                    @endfor
                                </select>
                            </th>    
                        </tr></thead>
                        <tbody id="store_list_table">
                            <tr>
                                @for($i=0;$i<count($store_list);$i++)
                                    <td>
                                        <input type="checkbox" name="store_list" id="store_list"  value="{{$store_list[$i]['id']}}" class="store-list-chk"> {{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})
                                    </td>
                                    @if($i > 0 && ($i+1)%8 == 0) </tr><tr> @endif
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="addMultipleDiscountErrorMessage" class="alert alert-danger elem-hidden" ></div>
                <div id="addMultipleDiscountSuccessMessage" class="alert alert-success elem-hidden" ></div>
                
                <input type="button" style="margin-top:15px;" name="addDiscountBtn" id="addDiscountBtn" value="Add Discount" class="btn btn-dialog" onclick="addMultipleDiscountSKU();">
                <div class="separator-10">&nbsp;</div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="uploadSKUCsvDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Import SKU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="uploadSKUCSVErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="uploadSKUCSVSuccessMessage"></div>
                <form method="post" name="uploadSKUCsvForm" id="uploadSKUCsvForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>CSV File</label>
                            <input type="file" name="uploadSKUCsvFile" id="uploadSKUCsvFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="upload_sku_csv_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="updateSKUCsvCancel" id="updateSKUCsvCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="updateSKUCsvBtn" id="updateSKUCsvBtn" value="Upload CSV" class="btn btn-dialog" onclick="submitImportMultipleDiscountSKU();">Upload CSV</button>
                </div>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/discount.js?v=2.15') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'yyyy-mm-dd'});</script>
<script>
</script>
@endsection
