@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
  
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'POS Customers')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'POS Customers'); ?>

    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateOrderStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateOrderStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="ID" value="{{request('id')}}" />
                    </div>
                    <div class="col-md-2">
                        <input name="cust_name" id="cust_name" class="form-control" placeholder="Name / Phone" value="{{request('cust_name')}}" />
                    </div>
                    <div class="col-md-3" >
                        <select name="s_id" id="s_id" class="form-control">
                            <option value="">-- All Stores --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php $sel = ($store_list[$i]['id'] == request('s_id'))?'selected':''; ?>
                                <option {{$sel}} value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-1"><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog" ></div>
                    
                    @if($user->user_type == 1)
                        <div class="col-md-1"><a href="javascript:;" onclick="downloadPosOrderCustomers();" class="btn btn-dialog" title="Download Pos Order Customers CSV"><i title="Download Pos Order Customers CSV" class="fa fa-download fas-icon" ></i> </a></div>
                    @endif
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>
            <div id="orderContainer" class="table-container">
                <div id="orderListOverlay" class="table-list-overlay"><div id="order-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="ordersList">

                    <div class="table-responsive table-filter">
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th>ID</th>    
                                <th>Customer Name</th>
                                <th>Phone No</th>
                                <th>Email </th>
                                <th>Store Name</th>
                                <th>Store Code</th>
                            </tr></thead>
                            <tbody>
                                @for($i=0;$i<count($pos_customers);$i++)
                                    <tr>
                                        <td>{{$pos_customers[$i]->id}}</td>
                                        <td>@if(strtolower($pos_customers[$i]->salutation) != 'other') {{$pos_customers[$i]->salutation}} @endif {{$pos_customers[$i]->customer_name}}</td>
                                        <td>{{$pos_customers[$i]->phone}}</td>
                                        <td>{{$pos_customers[$i]->email}}</td>
                                        <td>{{$pos_customers[$i]->store_name}}</td>
                                        <td>{{$pos_customers[$i]->store_id_code}}</td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $pos_customers->withQueryString()->links() }} <p>Displaying {{$pos_customers->count()}} of {{ $pos_customers->total() }} POS customers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="downloadPosOrderCustomersDialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Download Pos Orders Customers</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-danger alert-dismissible elem-hidden" id="downloadPosOrderCustomersErrorMessage"></div>
                <div class="alert alert-success alert-dismissible elem-hidden" id="downloadPosOrderCustomersSuccessMessage"></div>
                
                <form method="post" name="downloadPosOrderCustomersForm" id="downloadPosOrderCustomersForm">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="col-md-10 form-group"  >
                                <label>Pos Customers Records</label>
                                <select name="pos_order_cust_count" id="pos_order_cust_count" class="form-control" >
                                    <option value="">-- Pos Customers Records --</option>
                                        @for($i=0;$i<=$pos_order_cust_count;$i=$i+50000) 
                                            <?php $start = $i+1; $end = $i+50000; ?>
                                            <?php $end = ($end < $pos_order_cust_count)?$end:$pos_order_cust_count; ?>
                                            <option value="{{$start}}_{{$end}}">{{$start}} - {{$end}}</option>
                                        @endfor
                                </select>
                                <div class="invalid-feedback" id="error_validation_pos_order_cust_count"></div>
                            </div>
                        </div>
                    </div>
                </form>    
                
                <div class="modal-footer center-footer">
                    <button name="downloadPosOrderCustomersCancel" id="downloadPosOrderCustomersCancel" value="Cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button name="downloadPosOrderCustomersBtn" id="downloadPosOrderCustomersBtn" value="Download Pos Order Customers" class="btn btn-dialog" onclick="submitDownloadPosOrderCustomers();">Download</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.35') }}" ></script>
@endsection
