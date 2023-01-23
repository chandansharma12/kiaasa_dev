@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Push Demands List','link'=>'warehouse/demand/push/list'),array('name'=>'Edit Push Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Edit Push Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="createDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="demandsList">
                <div id="editPushDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="editPushDemandSuccessMessage" class="alert alert-success" style="display:none;"></div>
                <form class="" name="editPushDemandFrm" id="editPushDemandFrm" method="POST" enctype="multipart/form-data">
                   <button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('edit');" class="btn btn-dialog">Add+</button>
                   <div class="separator-10"></div>
                    <div class="demand-items-container" id="">
                        @for($i=0;$i<count($products_list);$i++)
                            <div class="form-row demand-items-row" @if($i == 0) id="demand_items_row_first" @endif>
                                <div class="form-group col-md-2">
                                    <label>Product</label>
                                    <div class="typeahead__container">
                                        <div class="typeahead__field">
                                            <div class="typeahead__query">
                                                <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" value="{{$products_list[$i]->product_name}} {{$products_list[$i]->size_name}} {{$products_list[$i]->color_name}}">
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="product_edit[]" id="product_edit" class="form-control demand-item_edit" value="{{$products_list[$i]->id}}" >
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Available</label>
                                    <span class="demand-item-inventory-count_edit demand-item-data">{{$products_list[$i]->inventory_count-$products_quantity[$products_list[$i]->id]}}</span>
                                    <input type="hidden" name="demand_item_inventory_count_edit" id="demand_item_inventory_count_edit" class="demand_item_inventory_count_edit" value="{{$products_list[$i]->inventory_count}}">
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Unit Price</label>
                                    <span class="demand-item-unit-price demand-item-data">{{$products_list[$i]->base_price}}</span>
                                </div>
                                @for($q=0;$q<count($store_list);$q++)
                                    <div class="form-group col-md-1">
                                        <label>{{$store_list[$q]['store_name']}}</label>
                                        <input type="number" name="store_edit_{{$store_list[$q]['id']}}[]" id="store_edit_{{$store_list[$q]['id']}}" value="{{$store_products[$store_list[$q]['id']][$products_list[$i]->id]}}" class="form-control demand-item-quantity_edit" onkeyup="updatePushDemandQuantity('edit',this);" onchange="updatePushDemandQuantity('edit',this);">
                                    </div>
                                @endfor
                                <div class="form-group col-md-1 " style="margin-left: 10px;">
                                    <label>Delete</label>
                                    <button type="button" id="demand_add_submit" name="demand_add_submit" class="btn btn-dialog" value="Submit" onclick="deletePushDemandProduct({{$push_demand_data->id}},{{$products_list[$i]->id}});">X</button>
                                </div>
                            </div> 
                        @endfor    
                    </div>
                    
                    <br/>
                    <div class="form-row" >
                        <div id="push_demand_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id ="push_demand_edit_submit" name="push_demand_edit_submit" class="btn btn-dialog" value="Submit" onclick="updatePushDemand({{$push_demand_data->id}});">Update Demand</button>&nbsp;&nbsp;
                        <button type="button" id="push_demand_edit_cancel" name="push_demand_edit_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('warehouse/demand/push/list')}}'">Cancel</button>
                    </div>
                    <?php $id_str = ''; ?>
                    @for($i=0;$i<count($store_list);$i++)
                        <?php $id_str.=$store_list[$i]['id'].',' ?>
                    @endfor    
                    <input type="hidden" name="store_ids" id="store_ids" value="{{rtrim($id_str,',')}}" >
                    @csrf
                </form>
            </div>
        </div>
    </section>

    <div class="modal fade" id="confirm_delete_demand_item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="deleteDemandItemErrorMessage"></div>
                <div class="alert alert-success alert-dismissible" style="display:none" id="deleteDemandItemSuccessMessage"></div>
                <div class="modal-body">
                    <h6>Are you sure to delete Demand Product<br/></h6>
                    <span id="name_delete_rows"></span><br/>
                </div>
                <div class="modal-footer center-footer">
                    <div id="delete-demand_item-spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                    <button type="button" data-dismiss="modal" class="btn btn-secondary" id="delete_rows_cancel">Cancel</button>
                    <button type="button"  class="btn btn-danger" id="delete_rows_btn" name="delete_rows_btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >
        var action_type = 'edit',page_type = 'push_demand';
    </script>
@endif
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
