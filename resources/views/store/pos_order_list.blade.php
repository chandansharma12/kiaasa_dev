@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Orders')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'POS Orders'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1">
                        <select name="search_by" id="search_by" class="form-control" onchange="updateSearchValue();">
                            <option value="">-- Search By --</option>
                            <option value="order_no" <?php $sel = ('order_no' == $search_by)?'selected':''; echo $sel; ?>>Order No</option>
                            <option value="order_id" <?php $sel = ('order_id' == $search_by)?'selected':''; echo $sel; ?>>Order ID</option>
                            <option value="phone" <?php $sel = ('phone' == $search_by)?'selected':''; echo $sel; ?>>Phone</option>
                            <option value="cust_name" <?php $sel = ('cust_name' == $search_by)?'selected':''; echo $sel; ?>>Customer Name</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <input name="search_value" id="search_value" class="form-control" placeholder="Order No" value="{{request('search_value')}}" />
                    </div>
                    
                    @if($user->user_type == 1 || $is_fake_inventory_user)
                        <div class="col-md-2">
                            <select name="store_id" id="store_id" class="form-control">
                                <option value="">-- Store --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                     <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>   
                                    <option <?php echo $sel; ?> value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                        </div>
                    @endif       
                    @if($user->user_type == 1)
                        <div class="col-md-1">
                            <select name="order_type" id="order_type" class="form-control">
                                <option value="">-- All Order Types --</option>
                                <!--<option value="0" <?php $sel = (request('order_type') != '' && 0 == request('order_type'))?'selected':''; echo $sel; ?> >Orders with GST Exclusive Prods</option>
                                <option value="1" <?php $sel = (1 == request('order_type'))?'selected':''; echo $sel; ?>>Orders with GST Inclusive Prods</option>-->
                                <option value="2" <?php $sel = (2 == request('order_type'))?'selected':''; echo $sel; ?>>FOC Orders</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <select name="order_source" id="order_source" class="form-control">
                                <option value="">-- Order Source --</option>
                                <option value="pos" <?php $sel = ('pos' == request('order_source'))?'selected':''; echo $sel; ?>>POS</option>
                                <option value="website" <?php $sel = ('website' == request('order_source'))?'selected':''; echo $sel; ?>>Website</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <select name="discount" id="discount" class="form-control">
                                <option value="">Discount %</option>
                                @for($i=0;$i<count($discount_list);$i++)
                                     <?php $sel = ($discount_list[$i]['discount_percent'] == request('discount'))?'selected':''; ?>   
                                    <option <?php echo $sel; ?> value="{{$discount_list[$i]['discount_percent']}}">{{round($discount_list[$i]['discount_percent'],2)}} % ({{$discount_list[$i]['cnt']}})</option>
                                @endfor
                            </select>
                        </div>
                    @endif        
                    
                    @if($is_fake_inventory_user)
                        <div class="col-md-2">
                            <select name="order_type" id="order_type" class="form-control">
                                <option value="">-- All Order Types --</option>
                                <option value="1" <?php $sel = (1 == request('order_type'))?'selected':''; echo $sel; ?>>Fake Orders</option>
                                <option value="2" <?php $sel = (2 == request('order_type'))?'selected':''; echo $sel; ?>>Real Orders</option>
                            </select>
                        </div>
                    @endif 
                    
                    <div class="col-md-2">
                        <div class="input-group input-daterange">
                            <input type="text" class="form-control datepicker" name="startDate" id="startDate" placeholder="Start Date" value="{{request('startDate')}}" autocomplete="off">
                            <div class="input-group-addon" style="margin-top:10px;">to</div>
                            <input type="text" class="form-control datepicker" name="endDate" id="endDate" placeholder="End Date" value="{{request('endDate')}}" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    @if($user->user_type == 9)
                        <div class="col-md-3"><a href="{{url('store/posbilling')}}" class="btn btn-dialog ">Create Order</a> &nbsp;&nbsp;
                        <a href="javascript:;" onclick="holdBills();" class="btn btn-dialog ">Hold Bills</a></div>
                    @endif
                    
                    @if($is_fake_inventory_user)
                        <div class="col-md-3">
                            <a href="javascript:;" onclick="createFakePosOrders();" class="btn btn-dialog ">Create Orders</a>
                            <a href="javascript:;" onclick="createFakePosOrdersFromCsv();" class="btn btn-dialog ">Create Orders CSV</a>
                            <a href="javascript:;" onclick="downloadPosOrders();" class="btn btn-dialog" title="Download Pos Orders CSV"><i title="Download Pos Orders CSV" class="fa fa-download fas-icon" ></i> </a>
                        </div>
                    @endif
                    
                    @if($user->user_type == 1)
                        <div class="col-md-1"><a href="javascript:;" onclick="createFocOrderType();" class="btn btn-dialog ">FOC Order &raquo;</a> </div>
                    @endif
                    
                    @if(in_array($user->user_type,array(1,9)))
                        <div class="col-md-1"><a href="javascript:;" onclick="downloadPosOrders();" class="btn btn-dialog" title="Download Pos Orders CSV"><i title="Download Pos Orders CSV" class="fa fa-download fas-icon" ></i> </a></div>
                    @endif
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div class="table-responsive table-filter">
                    <table class="table table-striped admin-table" cellspacing="0" style="font-size:13px; ">
                        <thead><tr class="header-tr">
                            <th><?php echo CommonHelper::getSortLink('ID','order_id','pos/order/list',true,'DESC'); ?></th>    
                            <th><?php echo CommonHelper::getSortLink('Order No','order_no','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Store Name','store_name','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Code','store_code','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Customer Name','customer_name','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Phone','customer_phone','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Total Amt','total_amount','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Total Items','total_items','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Status','order_status','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Created By','created_by','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('FOC','foc','pos/order/list'); ?></th>
                            <th><?php echo CommonHelper::getSortLink('Created On','created_on','pos/order/list'); ?></th>
                            @if($is_fake_inventory_user)
                                <th >Order Type</th>
                            @endif
                            <th>Action</th>
                            </tr></thead>
                        <tbody>
                            @for($i=0;$i<count($pos_orders);$i++)
                                <tr>
                                    <td>{{$pos_orders[$i]->id}}</td>
                                    <td>{{$pos_orders[$i]->order_no}}</td>
                                    @if(!empty($pos_orders[$i]->store_id))
                                        <td>{{$pos_orders[$i]->store_name}}</td>
                                        <td>{{$pos_orders[$i]->store_id_code}}</td>
                                    @else
                                        <td>Warehouse</td>
                                        <td></td>
                                    @endif
                                    <td>@if(strtolower($pos_orders[$i]->salutation) != 'other') {{$pos_orders[$i]->salutation}} @endif {{$pos_orders[$i]->customer_name}}</td>
                                    <td>{{$pos_orders[$i]->customer_phone}}</td>
                                    <td>{{$currency}} {{round($pos_orders[$i]->total_price,2)}}</td>
                                    <td>{{$pos_orders[$i]->total_items}}</td>
                                    <td>{{CommonHelper::getPosOrderStatusText($pos_orders[$i]->order_status)}}</td>
                                    <td>{{$pos_orders[$i]->order_type=='customer'?$pos_orders[$i]->store_user_name:'Auditor'}}</td>
                                    <td>{{($pos_orders[$i]->foc == 1)?'Yes':'No'}}</td>
                                    <td>{{date('d M Y',strtotime($pos_orders[$i]->created_at))}}</td>
                                    @if($is_fake_inventory_user)
                                        <td @if($pos_orders[$i]->fake_inventory ==1) style="background-color:#EE7158;color:#fff;" @endif >{{($pos_orders[$i]->fake_inventory ==1)?'Fake':'Real'}}</td>
                                    @endif
                                    <td>
                                        <a href="{{url('pos/order/detail/'.$pos_orders[$i]->id)}}" ><i title="POS Order Details" class="fas fa-eye"></i></a> &nbsp;
                                        @if($user->user_type == 1 && ($pos_orders[$i]->order_status == 1 || $pos_orders[$i]->order_status == 2) )
                                            <a href="{{url('pos/order/edit/'.$pos_orders[$i]->id)}}" ><i title="Edit POS Order" class="fas fa-edit"></i></a> &nbsp;
                                        @endif
                                        @if($user->user_type == 9 && ($pos_orders[$i]->order_status == 2))
                                            <a href="{{url('store/posbilling?order_id='.$pos_orders[$i]->id)}}" ><i title="Complete POS Order" class="fas fa-edit"></i></a> &nbsp;
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>

                    {{ $pos_orders->withQueryString()->links() }} <p>Displaying {{$pos_orders->count()}} of {{ $pos_orders->total() }} POS orders.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="fake_pos_add_order_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Fake POS Orders</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-danger alert-dismissible elem-hidden"  id="fakePosCreateOrderErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden"  id="fakePosCreateOrderSuccessMessage"></div>

                <form method="post" name="createPosOrderForm" id="createPosOrderForm">
                    <div class="modal-body">             
                        <div class="form-group">
                            <label>Store</label>
                            <select name="fake_order_store_id" id="fake_order_store_id" class="form-control">
                                <option value="">Store</option>
                                @for($i=0;$i<count($store_list);$i++)
                                     <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>   
                                    <option <?php echo $sel; ?> value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                                @endfor
                            </select>
                            <div class="invalid-feedback" id="error_validation_billing_fake_order_store_id"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Number of Orders</label>
                                <select name="fake_order_count" id="fake_order_count" class="form-control">
                                    <option value=""></option>
                                    @for($i=1;$i<=10;$i++)
                                        <option value="{{$i}}">{{$i}}</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_billing_fake_order_count"></div>
                            </div>

                            <div class="form-group col-md-6" >
                                <label>Orders Date</label>
                                <input id="fake_order_date" type="text" class="form-control datepicker" name="fake_order_date" value=""  >
                                <div class="invalid-feedback" id="error_validation_billing_fake_order_date"></div>
                            </div>
                        </div>   
                        <div class="form-row">
                            <div class="form-group col-md-6" >
                                <label>Discount %</label>
                                <select name="fake_order_discount" id="fake_order_discount" class="form-control">
                                    <option value=""></option>
                                    @for($i=0;$i<=90;$i++)
                                        <option value="{{$i}}">{{$i}} %</option>
                                    @endfor    
                                </select>
                                <div class="invalid-feedback" id="error_validation_billing_fake_order_discount"></div>
                            </div>
                            <div class="form-group col-md-6" >
                                <label>GST Type</label>
                                <select name="fake_order_gst_type" id="fake_order_gst_type" class="form-control">
                                    <option value=""></option>
                                    <option value="exclusive">Exclusive</option>
                                    <option value="inclusive">Inclusive</option>
                                </select>
                                <div class="invalid-feedback" id="error_validation_billing_fake_order_gst_type"></div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="modal-footer center-footer ">
                        <div id="fake_pos_add_order_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="fake_pos_order_cancel" name="fake_pos_order_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="fake_pos_order_submit" name="fake_pos_order_submit" class="btn btn-dialog" onclick="submitCreateFakePosOrders();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadFakePosOrderCsvDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Upload Fake POS Orders CSV</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="uploadFakePosOrderCSVErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="uploadFakePosOrderCSVSuccessMessage"></div>
                <form method="post" name="fakePosOrderCsvForm" id="fakePosOrderCsvForm">
                <div class="modal-body">
                    <div class="form-group" >
                        <label>CSV File</label>
                        <input type="file" name="fakePosOrderCsvFile" id="fakePosOrderCsvFile" class="form-control"  />
                    </div>
                    <div class="form-group">
                        <label>Store</label>
                        <select name="fake_order_csv_store_id" id="fake_order_csv_store_id" class="form-control">
                            <option value="">Store</option>
                            @for($i=0;$i<count($store_list);$i++)
                                 <?php $sel = ($store_list[$i]['id'] == request('fake_order_csv_store_id'))?'selected':''; ?>   
                                <option <?php echo $sel; ?> value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group pull-right" >
                        <label></label>
                        <a href="{{url('documents/fake_pos_orders_csv/fake_pos_orders_sample_csv.csv')}}" target="_blank">Sample CSV File</a>
                    </div>
                </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="upload_fake_pos_order_csv_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="updateFakePosOrderCsvCancel" id="updateFakePosOrderCsvCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="updateFakePosOrderCsvBtn" id="updateFakePosOrderCsvBtn" value="Upload CSV" class="btn btn-dialog" onclick="submitCreateFakePosOrdersFromCsv();">Upload CSV</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="holdBillsDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Hold Bills</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="holdBillsErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="holdBillsSuccessMessage"></div>
                <form method="post" name="holdBillsForm" id="holdBillsForm">
                    <div class="modal-body">
                       <div class="form-group">
                            <label>Bills Count</label>
                            <select name="hold_bills_count" id="hold_bills_count" class="form-control">
                                <option value="">-- Bills Count --</option>
                                @for($i=1;$i<=10;$i++)
                                     <?php $sel = ($i == request('hold_bills_count'))?'selected':''; ?>   
                                    <option <?php echo $sel; ?> value="{{$i}}">{{$i}}</option>
                                @endfor
                            </select>
                            <div class="invalid-feedback" id="error_validation_hold_bills_count"></div>
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <div id="hold_bills_spinner" class="spinner-border spinner-border-sm text-secondary elem-hidden"  role="status"><span class="sr-only">Loading...</span></div>
                    <button name="holdBillsCancel" id="holdBillsCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="holdBillsBtn" id="holdBillsBtn" value="Upload CSV" class="btn btn-dialog" onclick="submitHoldBills();">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createFocOrderTypeDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Create FOC Order</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <form method="post" name="createFocOrderTypeForm" id="createFocOrderTypeForm">
                    <div class="modal-body">
                       <div class="form-group">
                            <label>Store</label>
                            <select name="foc_order_store_id" id="foc_order_store_id" class="form-control">
                                <option value="-1">-- Warehouse / Store --</option>
                                <option value="">Warehouse</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <?php $sel = ($store_list[$i]['id'] == request('store_id'))?'selected':''; ?>   
                                    <option <?php echo $sel; ?> value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor
                            </select>
                            <div class="invalid-feedback" id="error_validation_create_foc_order_type"></div>
                        </div>
                    </div>
                </form>    
                <div class="modal-footer center-footer">
                    <button name="createFocOrderTypeCancel" id="createFocOrderTypeCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="createFocOrderTypeBtn" id="createFocOrderTypeBtn" value="Create Order" class="btn btn-dialog" onclick="submitCreateFocOrderType();">Submit</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="downloadPosOrdersDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Download Pos Orders</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadPosOrdersErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadPosOrdersSuccessMessage"></div>
                
                <form method="post" name="downloadPosOrdersForm" id="downloadPosOrdersForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Pos Orders Records</label>
                                <select name="pos_orders_count" id="pos_orders_count" class="form-control" >
                                    <option value="">-- Pos Orders Records --</option>
                                        @for($i=0;$i<=$pos_orders_count;$i=$i+50000) 
                                            <?php $start = $i+1; $end = $i+50000; ?>
                                            <?php $end = ($end < $pos_orders_count)?$end:$pos_orders_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_pos_orders_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <button name="downloadPosOrdersCancel" id="downloadPosOrdersCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadPosOrdersBtn" id="downloadPosOrdersBtn" value="Download PosOrders" class="btn btn-dialog" onclick="submitDownloadPosOrders();">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/pos.js?v=3.35') }}" ></script>
<script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
<link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
<script type="text/javascript">$('.input-daterange').datepicker();$("#fake_order_date").datepicker({format: 'dd-mm-yyyy',startDate: '-2M',endDate: '+0d'});</script>
@endsection
