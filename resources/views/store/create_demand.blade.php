@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'store/dashboard'),array('name'=>'Demands List','link'=>'store/demand/list'),array('name'=>'Create Demand')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Create Demand'); ?>
    
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            <div id="addDemandErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="addDemandSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <div id="demandsList">
                <form class="" name="createDemandFrm" id="createDemandFrm" method="POST" enctype="multipart/form-data">
                   <!--<button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('add');" class="btn btn-dialog">Add+</button> -->
                   <div class="separator-10"></div>
                    <div class="demand-items-container" id="">
                        <div class="form-row demand-items-row" id="demand_items_row_first">
                            <div class="form-group col-md-3">
                                <label class="label-text">Product</label>
                                <div class="typeahead__container">
                                    <div class="typeahead__field">
                                        <div class="typeahead__query">
                                            <input class="js-typeahead text-autosuggest form-control" name="q" autofocus autocomplete="off" id="q" >
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="product_add[]" id="product_add" class="form-control demand-item_add" >
                                <input type="hidden" name="product_id_add" id="product_id_add" class="form-control" >
                            </div>

                            <div class="form-group col-md-1">
                                <label class="label-text">Quantity</label>
                                <input type="number" name="productQuantity_add[]" id="productQuantity_add" class="form-control demand-item-quantity_add" onkeyup="updateDemandPrice('add');" onchange="updateDemandPrice('add');">
                            </div>
                            
                            <div class="form-group col-md-1">
                                <label class="label-text">Unit Price</label>
                                <span class="demand-item-unit-price"></span>
                            </div>
                            <div class="form-group col-md-1">
                                <label class="label-text">Total Price</label>
                                <span class="demand-item-price"></span>
                            </div>
                            <div class="form-group col-md-1">
                                <label class="label-text">&nbsp;</label>
                                <button type="button" id="add_demand_row" name="add_demand_row" onclick="addDemandRow('add');" class="btn btn-dialog">Add+</button>
                            </div>
                        </div>  
                    </div>
                    <div class="form-row" >
                        <div class="form-group col-md-8" id="demands_list_div">
                            <span id="demand_grand_total" name="demand_grand_total"></span>
                        </div> 
                    </div>
                    <br/>
                    <div class="form-row" >
                        <button type="button" id ="demand_add_submit" name="demand_add_submit" class="btn btn-dialog" value="Submit" onclick="createDemand();">Create Demand</button>&nbsp;&nbsp;
                        <button type="button" id="demand_add_cancel" name="demand_add_cancel" class="btn btn-secondary" onclick="window.location.href='{{url('store/demand/list')}}'">Cancel</button>
                        <input type="hidden" name="create_demand" id="create_demand" value="1">
                    </div>
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
                    <h6>Are you sure to delete Demand Item<br/></h6>
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
        var action_type = 'add',page_type = 'demand';
    </script>
@endif
<link rel="stylesheet" href="{{ asset('css/jquery.typeahead.min.css') }}" />
<script src="{{ asset('js/jquery.typeahead.min.js') }}" ></script>
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
