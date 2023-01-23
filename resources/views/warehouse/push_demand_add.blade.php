@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'warehouse/dashboard'),array('name'=>'Push Demands List','link'=>'warehouse/demand/push/list'),array('name'=>'Create Push Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Create Push Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="region_id" id="region_id" class="form-control" onchange="this.form.submit();">
                            <option value="">-- All Regions --</option>
                            @for($i=0;$i<count($region_list);$i++)
                                <?php if($region_list[$i]['id'] == request('region_id')) $sel = 'selected';else $sel = ''; ?>
                                <option value="{{$region_list[$i]['id']}}" <?php echo $sel; ?>>{{$region_list[$i]['name']}}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </form> 
            <div id="createDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="demandsList">
                <div id="addPushDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="addPushDemandSuccessMessage" class="alert alert-success" style="display:none;"></div>
                <form class="" name="createPushDemandFrm" id="createPushDemandFrm" method="POST" enctype="multipart/form-data">
                   <button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('add');" class="btn btn-dialog">Add+</button>
                   <div class="separator-10"></div>
                    <div class="demand-items-container" id="">
                        <div class="form-row demand-items-row" id="demand_items_row_first">
                            <div class="form-group col-md-2">
                                <label>Product</label>
                                <div class="typeahead__container">
                                    <div class="typeahead__field">
                                        <div class="typeahead__query">
                                            <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" >
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="product_add[]" id="product_add" class="form-control demand-item_add" >
                            </div>
                            <div class="form-group col-md-1">
                                <label>Available</label>
                                <span class="demand-item-inventory-count_add demand-item-data"></span>
                                <input type="hidden" name="demand_item_inventory_count_add" id="demand_item_inventory_count_add" class="demand_item_inventory_count_add" value="">
                            </div>
                            <div class="form-group col-md-1">
                                <label>Unit Price</label>
                                <span class="demand-item-unit-price demand-item-data"></span>
                            </div>
                            @for($i=0;$i<count($store_list);$i++)
                                <div class="form-group col-md-1">
                                    <label>{{$store_list[$i]['store_name']}}</label>
                                    <input type="number" name="store_add_{{$store_list[$i]['id']}}[]" id="store_add_{{$store_list[$i]['id']}}" class="form-control demand-item-quantity_add" onkeyup="updatePushDemandQuantity('add',this);" onchange="updatePushDemandQuantity('add',this);">
                                </div>
                            @endfor
                        </div>  
                    </div>
                    <!--<div class="form-row" >
                        <div class="form-group col-md-2">
                            <span id="demand_grand_total" name="demand_grand_total"></span>
                        </div> 
                    </div> -->
                    <br/>
                    <div class="form-row" >
                        <div id="push_demand_add_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id ="push_demand_add_submit" name="push_demand_add_submit" class="btn btn-dialog" value="Submit" onclick="createPushDemand();">Create Demand</button>&nbsp;&nbsp;
                        <button type="button" id="push_demand_add_cancel" name="push_demand_add_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('warehouse/demand/push/list')}}'">Cancel</button>
                        
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
    
@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script type="text/javascript" >
        var action_type = 'add',page_type = 'push_demand';
    </script>
@endif
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
