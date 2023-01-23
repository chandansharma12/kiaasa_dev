@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Discount List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Discount List');
    $discountType=array('1'=>'Percent','2'=>'FlatPrice','3'=>'FreeItems','4'=>'Bill');
    ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateDiscountStatusErrorMessage" class="alert alert-danger elem-hidden"  ></div>
            <div id="updateDiscountStatusSuccessMessage" class="alert alert-success elem-hidden"  ></div>
            <form method="GET">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="Discount ID" value="{{request('id')}}" />
                    </div>
                    <div class="col-md-1">
                        <input type="text" name="sku" id="sku" class="form-control" placeholder="Product SKU" value="{{request('sku')}}">
                    </div>
                    <div class="col-md-1" >
                        <select name="discount_percent" id="discount_percent" class="form-control">
                            <option value="">Discount %</option>
                            @for($i=0;$i<count($discount_percents);$i++)
                                <?php $sel = ($discount_percents[$i]['discount_percent'] == request('discount_percent') && request('discount_percent') != '')?'selected':''; ?>
                                <option {{$sel}} value="{{$discount_percents[$i]['discount_percent']}}">{{$discount_percents[$i]['discount_percent']}} %</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="discount_rate" id="discount_rate" class="form-control">
                            <option value="">-- Flat Price --</option>
                            @for($i=0;$i<count($discount_flat_rates);$i++)
                                <?php $sel = ($discount_flat_rates[$i]['flat_price'] == request('discount_rate') && request('discount_rate') != '')?'selected':''; ?>
                                <option {{$sel}} value="{{$discount_flat_rates[$i]['flat_price']}}">{{$discount_flat_rates[$i]['flat_price']}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="store_id" id="store_id" class="form-control">
                            <option value="">-- Store --</option>
                            @foreach($store_list as $id=>$obj)
                                <?php $sel = ($id == request('store_id') && request('store_id') != '')?'selected':''; ?>
                                <option {{$sel}} value="{{$id}}">{{$obj['store_name']}} ({{$obj['store_id_code']}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" autocomplete="off" placeholder="Add Start Date" value="@if(!empty(request('startDate'))){{request('startDate')}}@endif">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" autocomplete="off" placeholder="Add End Date" value="@if(!empty(request('endDate'))){{request('endDate')}}@endif">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    <div class="col-md-3" >
                        <a href="javascript:;" onclick="downloadDiscount();" class="btn btn-dialog" title="Download Discounts CSV"><i title="Download Discounts CSV" class="fa fa-download fas-icon" ></i> </a>&nbsp;
                        <a href="{{url('discount/multiple/add')}}" class="btn btn-dialog" title="Add Multiple Discount"><i title="Add Multiple Discount" class="fa fa-plus-circle fas-icon" ></i> </a>&nbsp;
                        <button type="button" onclick="addDiscount();" name="addDiscountBtn" id="addDiscountBtn" value="Add Discount" class="btn btn-dialog" title="Add Discount"><i title="Add Discount" class="fa fa-plus fas-icon" ></i></button>&nbsp;
                        <button type="button" onclick="updateDiscountStatus()" name="deleteDiscountBtn" id="deleteDiscountBtn" value="Delete Discount" class="btn btn-dialog" title="Delete Discount"><i title="Delete Discount" class="fa fa-trash fas-icon" ></i></button>&nbsp;
                        <button type="button" onclick="deleteMultipleDiscount()" name="deleteMultipleDiscountBtn" id="deleteMultipleDiscountBtn" value="Delete Multiple Discounts" class="btn btn-dialog" title="Delete Multiple Discounts"><i title="Delete Multiple Discounts" class="fa fa-trash fas-icon" ></i></button>&nbsp;
                        <button type="button" onclick="checkDiscount();" name="checkDiscountBtn1" id="checkDiscountBtn1" value="Check Discount" class="btn btn-dialog" title="Check Discount"><i title="Check Discount" class="fa fa-check fas-icon" ></i></button>&nbsp;
                    </div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="discountsContainer">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead><tr class="header-tr">
                                <th><input type="checkbox" name="discount_list_all" id="user_list_all"  value="1" onclick="checkAllCheckboxes(this,'discount-list');"> ID </th>    
                                <th>Disc Type </th>    
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
                                <th>GST Inc </th>     
                                <th>Inventory </th>    
                                <th>Date Added</th>
                            </tr></thead>
                        <tbody>
                            <?php $currency = CommonHelper::getCurrency(); ?>
                            @foreach($discount_list as $discount_list_arr)
                            <tr>
                                <td><input type="checkbox" name="discount_list" id="discount_list_{{$discount_list_arr['id']}}" class="discount-list-chk" value="{{$discount_list_arr['id']}}"> &nbsp;{{$discount_list_arr['id']}}</td>
                                <td>{{ $discountType[$discount_list_arr['discount_type']] }}</td>
                                <td>{{$discount_list_arr['discount_percent'] }}</td>
                                <td>{{(!empty($discount_list_arr['flat_price']))?$currency:''}} {{$discount_list_arr['flat_price'] }}</td>
                                <td>{{ (isset( $category_list_arr[$discount_list_arr['category_id']]) ?  $category_list_arr[$discount_list_arr['category_id']] : '') }}</td>
                                <td>{{ (isset( $season[$discount_list_arr['season']]) ?  $season[$discount_list_arr['season']] : '') }}</td>
                                <td>{{ isset( $store_list[$discount_list_arr['store_id']]) ?  $store_list[$discount_list_arr['store_id']]['store_name'].' ('.$store_list[$discount_list_arr['store_id']]['store_id_code'].')' : '' }}</td>
                                <td>{{$discount_list_arr['sku']}}</td>
                                <td>{{$discount_list_arr['from_price'] }}</td>
                                <td>{{$discount_list_arr['to_price'] }}</td>  
                                <td>{{ date('d-m-Y', strtotime($discount_list_arr['from_date']))  }}</td>
                                <td>{{ date('d-m-Y', strtotime($discount_list_arr['to_date']))  }}</td>
                                <td>{{($discount_list_arr['gst_including']==1)?'Yes':'No' }}</td>
                                <td>{{($discount_list_arr['inv_type']==1)?CommonHelper::getInventoryType(1):CommonHelper::getInventoryType(2) }}</td>
                                <td>{{ date('d-m-Y H:i', strtotime($discount_list_arr['created_at']))  }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $discount_list->withQueryString()->links() }}
                    <p>Displaying {{$discount_list->count()}} of {{ $discount_list->total() }} discounts.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade data-modal" id="addDiscountDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Discount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="addDiscountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="addDiscountSuccessMessage"></div>
                <form method="post" name="addDiscountForm" id="addDiscountForm">
                    <div class="modal-body">
                        <div class="form-row">
                                <div class="form-group col-md-3" >
                                    <label>Discount Type</label>
                                    <select name="discount_type" onchange="discountType(this.value)" id="discount_type" class="form-control">
                                        <option value="">--Discount Type--</option>
                                        <option value="1">Percent</option>
                                        <option value="2">Flat Price</option>
                                        <!--<option value="3">FreeItems</option>
                                        <option value="4">Bill</option>  -->
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_discount_type"></div>
                                </div>
                            
                                <div class="col-md-3 form-group"  >
                                    <label>Search SKU</label>
                                    <input type="text" name="sku_search" id="sku_search" class="form-control" placeholder="SKU" value="" />
                                </div>
                            
                                <div class="col-md-2 form-group" >
                                    <label>&nbsp;</label>
                                    <input type="button" style="margin-top:15px;" name="skuSearchBtn" id="skuSearchBtn" value="Search" class="btn btn-dialog" onclick="searchAddDiscountSKU();">
                                </div>

                                <div class="col-md-3 form-group" id="flatPriceDiv" style="display:none">
                                    <label>Flat Price</label>
                                    <input type="text" name="flat_price" id="flat_price" class="form-control" placeholder="Flat Price" value="" />
                                    <div class="invalid-feedback" id="error_validation_flat_price"></div>
                                </div>

                                <div class="col-md-3 form-group" id="percentDiscountDiv" style="display:none">
                                    <label>Discount %</label>
                                    <input type="text" name="discount_percent" id="discount_percent" class="form-control" placeholder="Discount %" value="" />
                                    <div class="invalid-feedback" id="error_validation_discount_percent"></div>
                                </div>
                                <!--
                                <div class="col-md-2 form-group" id="minItemsDiv" style="display:none">
                                    <label>Min Item</label>
                                    <input type="text" name="min_item" id="min_item" class="form-control" placeholder="Buy Items" value="" />
                                </div>
                                <div class="col-md-2 form-group" id="maxItemsDiv" style="display:none">
                                    <label>Max Item</label>
                                    <input type="text" name="max_item" id="max_item" class="form-control" placeholder="Free Items" value="" />
                                </div>-->
                            
                            </div>
                            
                            <div class="form-row">
                                
                                <div class="col-md-3 form-group" >
                                    <label>Category</label>
                                    <select name="category_id" id="category_id" class="form-control" onchange="updateDiscountSKUList();">
                                        <option value="">--Category--</option>
                                        @foreach($category_list_arr as $categoryKey=>$categoryValue) 
                                            <option value="{{ $categoryKey}}">{{$categoryValue}}</option>
                                        @endforeach
                                    </select>
                                </div>        
                                <div class="col-md-3 form-group" >
                                    <label>Season</label>
                                    <select name="season_id" id="season_id" class="form-control" onchange="updateDiscountSKUList();">
                                        <option value="">--Season--</option>
                                        @foreach($season  as $seasonKey=>$seasonValue) 
                                            <option value="{{ $seasonKey}}">{{$seasonValue}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group" >
                                    <label>Store</label>
                                    <select name="store_id" id="store_id" class="form-control" onchange="updateDiscountSKUList();">
                                        <option value="">-- Store --</option>
                                          @foreach($store_list as $storeKey=>$storeValue) 
                                           <option value="{{ $storeKey}}">{{$storeValue['store_name']}} ({{$storeValue['store_id_code']}})</option>
                                            @endforeach
                                    </select>
                                </div>
                                 <div class="col-md-3 form-group" >
                                    <label>Product SKU</label> 
                                    <input type="text" name="product_sku" id="product_sku" class="form-control" placeholder="SKU" value="" />
                                    
                                    <?php /* ?>
                                    <select name="product_sku" id="product_sku" class="form-control">
                                        <option value="">--SKU--</option>
                                          @foreach($product_sku as $SKUKey=>$SKUValue) 
                                           <option value="{{ $SKUKey}}">{{$SKUValue}}</option>
                                            @endforeach
                                    </select> <?php */ ?>
                                </div>
                            </div>    
                        
                            <div class="form-row">
                                <div class="col-md-4 input-daterange form-group" >
                                    <label>From Date</label>
                                    <input type="text" class="form-control datepicker" autocomplete="off" name="from_date" id="from_date" placeholder="Select Date" value="">   
                                    <div class="invalid-feedback" id="error_validation_from_date"></div>
                                </div>

                                <div class="col-md-4 input-daterange form-group" >
                                    <label>To Date</label>
                                    <input type="text" class="form-control datepicker" autocomplete="off"  name="to_date" id="to_Date" placeholder="Select Date" value="">
                                    <div class="invalid-feedback" id="error_validation_to_date"></div>
                                </div>

                                <div class="col-md-4 form-group" >
                                    <div style="float:left;margin-top:30px;">
                                        <input type="checkbox"  value="1" name="gst_including" id="gst_including" > &nbsp;GST Included
                                    </div>
                                </div>
                            </div>    
                        
                            <div class="form-row">
                                <div class="col-md-3 form-group" >
                                    <label>From Price</label>
                                    <input type="text" class="form-control"  name="from_price" id="from_price" placeholder="From Price" value="">   
                                    <div class="invalid-feedback" id="error_validation_from_price"></div>
                                </div>

                                <div class="col-md-3 form-group" >
                                    <label>To Price</label>
                                    <input type="text" class="form-control"  name="to_price" id="to_price" placeholder="To Price" value="">
                                    <div class="invalid-feedback" id="error_validation_to_price"></div>
                                </div>
                                <div class="col-md-3 form-group" >
                                    <label>Inventory Type</label>
                                    <select name="inv_type" id="inv_type" class="form-control" >
                                        <option value="">Inventory Type</option>
                                        <option value="1">NorthCorp</option>
                                        <option value="2">Arnon</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_validation_inv_type"></div>
                                </div>
                            </div>    
                        
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="addDiscount_spinner" class="spinner-border spinner-border-sm text-secondary" style="display:none;" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="addDiscountCancel" id="addDiscountCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="addDiscountBtn" id="addDiscountBtn" value="Add Discount" class="btn btn-dialog" onclick="submitAddDiscount();">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="checkDiscountDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Check Discount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="checkDiscountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="checkDiscountSuccessMessage"></div>
                <form method="post" name="checkDiscountForm" id="checkDiscountForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-3 form-group"  >
                                <label>QR Code</label>
                                <input type="text" name="check_discount_qr_code" id="check_discount_qr_code" class="form-control" placeholder="QR Code" value="" />
                                <div class="invalid-feedback" id="error_validation_check_discount_qr_code"></div>
                            </div>
                            <div class="col-md-2 form-group" >
                                <label>&nbsp;</label>
                                <input type="button" style="margin-top:15px;" name="checkDiscountBtn" id="checkDiscountBtn" value="Submit" class="btn btn-dialog" onclick="submitCheckDiscount();">
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped clearfix admin-table check-discount-tbl" cellspacing="0" style="font-size:12px; ">
                                <thead>
                                    <tr class="header-tr">
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Category</th>
                                        <th>Subcategory</th>
                                        <th>HSN Code</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="prod_name_check_disc"></td>
                                        <td id="sku_check_disc"></td>
                                        <td id="category_check_disc"></td>
                                        <td id="subcategory_check_disc"></td>
                                        <td id="hsn_code_check_disc"></td>
                                    </tr>
                                </tbody>
                            </table>    
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped clearfix admin-table check-discount-tbl" cellspacing="0" style="font-size:12px; ">
                                <thead>
                                    <tr class="header-tr">
                                        <th>MRP</th>
                                        <th>Discount %</th>
                                        <th>Discount Amount</th>
                                        <th>Discounted Price</th>
                                        <th>GST</th>
                                        <th>GST Type</th>
                                        <th>Net Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="mrp_check_disc"></td>
                                        <td id="disc_percent_check_disc"></td>
                                        <td id="disc_amount_check_disc"></td>
                                        <td id="discounted_price_check_disc"></td>
                                        <td id="gst_check_disc"></td>
                                        <td id="gst_type_check_disc"></td>
                                        <td id="net_price_check_disc"></td>
                                    </tr>
                                </tbody>
                            </table>    
                        </div>
                        
                    </div>
                </form>    
                
            </div>
        </div>
    </div>

    <div class="modal fade" id="downloadDiscountDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Download Discount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadDiscountErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadDiscountSuccessMessage"></div>
                <form method="post" name="downloadDiscountForm" id="downloadDiscountForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Discount Records</label>
                                <select name="discount_count" id="discount_count" class="form-control" >
                                    <option value="">--Discount Records--</option>
                                        @for($i=0;$i<=$discount_list_count;$i=$i+50000) 
                                            <?php $start = $i+1; $end = $i+50000; ?>
                                            <?php $end = ($end < $discount_list_count)?$end:$discount_list_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_discount_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <div id="downloadDiscount_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                    <button name="downloadDiscountCancel" id="downloadDiscountCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadDiscountBtn" id="downloadDiscountBtn" value="Download Discount" class="btn btn-dialog" onclick="submitDownloadDiscount();">Download</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade data-modal" id="deleteMultipleDiscountDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Delete Multiple Discounts</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="deleteMultipleDiscountErrorMessage" style="max-height:300px;overflow-y: scroll; "></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="deleteMultipleDiscountSuccessMessage"></div>
                
                <form method="post" name="deleteMultipleDiscountForm" id="deleteMultipleDiscountForm">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>SKU Text File</label>
                            <input type="file" name="skuTxtFile" id="skuTxtFile" class="form-control"  />
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="deleteMultipleDiscountSpinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="deleteMultipleDiscountCancel" id="deleteMultipleDiscountCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="deleteMultipleDiscountSubmit" id="deleteMultipleDiscountSubmit" value="Submit" class="btn btn-dialog" onclick="submitDeleteMultipleDiscount();">Submit</button>
                </div>
            </div>
        </div>
    </div>
    

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/discount.js?v=2.65') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker({format: 'yyyy-mm-dd'});</script>

<script>
</script>
@endsection
