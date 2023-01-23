@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Stock In','link'=>'store/demand/inventory-push/list'),array('name'=>'Stock In Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Stock In Detail'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            
            <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
            <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->store_id}}">
            
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    <!--<div class="form-group col-md-2">
                        <label for="Season">Demand ID</label>						
                        {{$demand_data->id}}    
                    </div> -->
                    <div class="form-group col-md-2">
                        <label for="Season">Invoice No</label>						
                        {{$demand_data->invoice_no}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Product">Status</label>						
                        {{ucwords(str_replace('_',' ',$demand_data->demand_status))}}    
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$demand_data->store_name}}    
                    </div>
                
                    <div class="form-group col-md-2">
                        <label for="Color">Created On </label>						
                        {{date('d M Y H:i:s',strtotime($demand_data->created_at))}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Category">Comments</label>						
                        {{$demand_data->comments}}    
                    </div> 

                    @if(!empty($demand_data->receive_docket_no))
                        <div class="form-group col-md-2">
                            <label for="Category">Receive Docket No</label>						
                            {{$demand_data->receive_docket_no}}    
                        </div> 
                        <div class="form-group col-md-2">
                            <label for="Category">Receive Date</label>						
                            {{date('d M Y',strtotime($demand_data->receive_date))}}    
                        </div>
                    @endif
                    
                    @if(!empty($gate_pass_data))
                        <div class="form-group col-md-2">
                            <label for="Color">Boxes Count</label>						
                            {{$gate_pass_data->boxes_count}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Transporter Name</label>						
                            {{$gate_pass_data->transporter_name}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Transporter GST</label>						
                            {{$gate_pass_data->transporter_gst}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">Docket No</label>						
                            {{$gate_pass_data->docket_no}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">EWay Bill No</label>						
                            {{$gate_pass_data->eway_bill_no}}     
                        </div>
                        <div class="form-group col-md-2">
                            <label for="Color">LR No</label>						
                            {{$gate_pass_data->lr_no}}     
                        </div>
                    @endif
                    
                    @if(strtolower($demand_data->demand_status) == 'store_loaded' && $inventory_total_count > $inventory_received_count)
                        <div class="form-group col-md-2">
                            <label for="Color">Debit Note</label>		
                            @if(empty($debit_note))
                                <a href="{{url('store/demand/inventory-push/debit-note/'.$demand_data->id)}}" ><i title="Edit Debit Note" class="far fa-edit"></i></a> &nbsp;&nbsp;&nbsp;
                            @endif
                            
                            @if(!empty($debit_note))
                                <a href="{{url('store/demand/inventory-push/debit-note/invoice/'.$debit_note->id)}}" ><i title="Download Debit Note" class="fa fa-download"></i></a> 
                                &nbsp;&nbsp;<a href="javascript:;" onclick="cancelPushDemandDebitNote({{$debit_note->id}});" ><i title="Cancel Debit Note" class="fas fa-crosshairs"></i></a>
                            @endif
                        </div>
                    @endif
                    
                </div>
                
            </form> 
            <hr/>
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <h5>Products List</h5>
                    <?php /* ?><div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table" cellspacing="0" style="font-size: 13px;">
                            <thead>
                                <tr class="header-tr">
                                    <th>S No</th><th>Product</th><th>SKU</th><th>Color</th><th>Barcode</th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th colspan="2" align="center" style="text-align: center;">{{$size_list[$i]['size']}}</th>
                                    @endfor    
                                </tr>
                                <tr class="header-tr">
                                    <th colspan="5"></th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th style="text-align: center;">Rec</th>
                                        <th style="text-align: center;">Loaded</th>
                                    @endfor   
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($products))
                                    @for($i=0;$i<count($products);$i++)
                                        <tr>
                                            <td>{{$i+1}}</td>
                                            <td>{{$products[$i]['product_name']}}</td>
                                            <td>{{(!empty($products[$i]['vendor_sku']))?$products[$i]['vendor_sku']:$products[$i]['product_sku']}}</td>
                                            <td>{{$products[$i]['color_name']}}</td>
                                            <td>{{$products[$i]['product_barcode']}}</td>
                                            @for($q=0;$q<count($size_list);$q++)
                                                <?php $size_id = $size_list[$q]['id']; ?>
                                                <td style="text-align: center;">{{$cnt = (isset($products[$i]['size_'.$size_id]))?$products[$i]['size_'.$size_id]['rec_qty']:0 }}</td>
                                                <td style="text-align: center;">{{$loaded = (isset($products[$i]['size_'.$size_id]))?$products[$i]['size_'.$size_id]['intake_qty']:0 }}</td>
                                                <?php if(isset($size_data[$size_list[$q]['id']]['rec_count'])) $size_data[$size_list[$q]['id']]['rec_count']+=$cnt;else $size_data[$size_list[$q]['id']]['rec_count'] = $cnt; ?>
                                                <?php if(isset($size_data[$size_list[$q]['id']]['loaded_count'])) $size_data[$size_list[$q]['id']]['loaded_count']+=$loaded;else $size_data[$size_list[$q]['id']]['loaded_count'] = $loaded; ?>
                                            @endfor    
                                        </tr>
                                    @endfor

                                    <tr>
                                        <th colspan="5">Total</th>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <th style="text-align: center;">{{$size_data[$size_list[$q]['id']]['rec_count']}}</th>
                                            <th style="text-align: center;">{{$size_data[$size_list[$q]['id']]['loaded_count']}}</th>
                                        @endfor    
                                    </tr>
                                @endif
                                
                                @if(empty($products))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>    
                            
                        </table>
                    </div><?php */ ?>
                    
                    <div class="table-responsive table-filter">
                        <?php $total_size = $count = 0; ?>
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th>S No</th><th>Product</th><th>SKU</th><th>Color</th><th>HSN Code</th>
                                    @for($i=0;$i<count($size_list);$i++)
                                        <th>{{$size_list[$i]['size']}}</th>
                                    @endfor    
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products_sku as $sku=>$product_data)
                                    <tr>
                                        <td>{{++$count}}</td>
                                        <td>{{$product_data->product_name}}</td>
                                        <td>{{(!empty($product_data->vendor_sku))?$product_data->vendor_sku:$product_data->product_sku}}</td>
                                        <td>{{$product_data->color_name}}</td>
                                        <td>{{$product_data->hsn_code}}</td>
                                        @for($q=0;$q<count($size_list);$q++)
                                            <?php $key = strtolower($product_data->product_sku).'_'.$size_list[$q]['id']; ?>
                                            <td>{{$cnt = (isset($products[$key]))?$products[$key]:0 }}</td>
                                            <?php if(isset($size_data[$size_list[$q]['id']]['count'])) $size_data[$size_list[$q]['id']]['count']+=$cnt;else $size_data[$size_list[$q]['id']]['count'] = $cnt; ?>
                                        @endfor    
                                    </tr>
                                @endforeach
                               
                                <tr>
                                    <th colspan="5">Total</th>
                                    @for($q=0;$q<count($size_list);$q++)
                                        <th>{{$size_data[$size_list[$q]['id']]['count']}}</th>
                                        <?php $total_size+=$size_data[$size_list[$q]['id']]['count']; ?>
                                    @endfor    
                                </tr>
                                <tr>
                                    <th colspan="5">Total</th>
                                    <th colspan="{{count($size_list)}}" align="center" style="text-align: center;">{{$total_size}}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr/>
                    <h6>Products Inventory</h6>
                    <div id="products_imported_list"></div>
                    <div id="products_paging_links"></div>
                    <br/>
                   
                </div>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="push_demand_debit_note_cancel_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Cancel Debit Note</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                
                <div class="alert alert-success alert-dismissible elem-hidden"  id="cancelPushDemandDebitNoteSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="cancelPushDemandDebitNoteErrorMessage"></div>
                
                <form name="cancelPushDemandDebitNoteForm" id="cancelPushDemandDebitNoteForm" type="POST" >
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Comments</label>
                                <input id="push_demand_debit_note_cancel_comments" type="text" class="form-control" name="push_demand_debit_note_cancel_comments" value="" placeholder="Comments" maxlength="250">
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer center-footer">
                        <input type="hidden" name="push_demand_debit_note_id" id="push_demand_debit_note_id" value=""> 
                        <button type="button" id="cancelPushDemandDebitNoteCancel" name="cancelPushDemandDebitNoteCancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="cancelPushDemandDebitNoteSubmit" name="cancelPushDemandDebitNoteSubmit" class="btn btn-dialog" onclick="submitCancelPushDemandDebitNote();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.61') }}" ></script>
<script type="text/javascript">
    var page_type = 'detail';
    $(document).ready(function(){
        loadInventoryPushDemandInventory(1);
    });
    
</script>
@endsection
