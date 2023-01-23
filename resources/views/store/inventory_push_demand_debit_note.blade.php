@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Stock In','link'=>'store/demand/inventory-push/list'),array('name'=>'Stock In Detail','link'=>'store/demand/inventory-push/detail/'.$demand_data->id),array('name'=>'Stock In Debit Note')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Stock In Debit Note'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <input type="hidden" name="demand_id" id="demand_id" value="{{$demand_data->id}}">
            <input type="hidden" name="store_id" id="store_id" value="{{$demand_data->store_id}}">
            
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                   
                    <div class="form-group col-md-2">
                        <label for="Season">Invoice No</label>						
                        {{$demand_data->invoice_no}}    
                    </div> 

                    <div class="form-group col-md-2">
                        <label for="Product">Store</label>						
                        {{$demand_data->store_name}}    
                    </div>
                
                    <div class="form-group col-md-2">
                        <label for="Color">Demand Created On </label>						
                        {{date('d M Y',strtotime($demand_data->created_at))}}    
                    </div> 
                    
                    <div class="form-group col-md-2">
                        <label for="Product">Total Inventory</label>						
                        {{$inventory_total_count}}    
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="Product">Inventory Received</label>						
                        {{$inventory_received_count}}    
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="Category">Comments</label>						
                        {{$demand_data->comments}}    
                    </div> 

                </div>
                
            </form> 
            <hr/>
            <div class="separator-10"></div>
            <div id="demandContainer" class="table-container">
                
                <div id="demandList">
                    <h5>Products List</h5>
                    <div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead>
                                <tr class="header-tr">
                                    <th>S No</th><th>Product</th><th>SKU</th><th>Size</th><th>Color</th><th>Barcode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $inv_ids = (!empty($demand_data->debit_note_data))?explode(',',$demand_data->debit_note_data):array(); ?>
                                @for($i=0;$i<count($product_inventory);$i++)
                                    <?php $chk = (in_array($product_inventory[$i]->id,$inv_ids))?'checked':''; ?>
                                    <tr>
                                        <td><input type="checkbox" name="debit_note_chk" value="{{$product_inventory[$i]->id}}" class="debit-note-chk" <?php echo $chk; ?>> {{$i+1}} </td>
                                        <td>{{$product_inventory[$i]->product_name}}</td>
                                        <td>{{$product_inventory[$i]->vendor_sku}}</td>
                                        <td>{{$product_inventory[$i]->size_name}}</td>
                                        <td>{{$product_inventory[$i]->color_name}}</td>
                                        <td>{{$product_inventory[$i]->product_barcode}}</td>
                                    </tr>
                                @endfor

                                @if(empty($product_inventory))
                                    <tr><td colspan="10" align="center">No Records</td></tr>
                                @endif
                            </tbody>    
                            
                        </table>
                        <div id="updateDebitNoteErrorMessage" class="alert alert-danger elem-hidden" ></div>
                        <div id="updateDebitNoteSuccessMessage" class="alert alert-success elem-hidden" ></div>
                        <button type="button" id="debit_note_submit" name="debit_note_submit" class="btn btn-dialog" value="Submit" onclick="updatePushDemandDebitNote();">Submit</button>&nbsp;
                    </div>
                </div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js?v=1.71') }}" ></script>
<script type="text/javascript">
    
    $(document).ready(function(){
        
    });
    
</script>
@endsection
