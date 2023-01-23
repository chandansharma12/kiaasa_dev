@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Push Demands Admin List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Push Demands Admin List'); ?>
  
    <?php $currency = CommonHelper::getCurrency(); ?>
    <section class="product_area">
        <div class="container-fluid" >
            
            <div id="updateDemandStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateDemandStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <form method="get">
                <div class="row justify-content-end" >

                    <div class="col-md-2" >
                        <select name="demand_action" id="demand_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="waiting">Request for Approval</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editDemand" id="editDemand" value="Update" class="btn btn-dialog" onclick="updatePushDemandStatus();"></div>
                    <div class="col-md-2" ><a href="{{url('warehouse/demand/push/add')}}" class="btn btn-dialog ">Create Push Demand</a></div>
                </div>
            </form> 
            <div class="separator-10">&nbsp;</div>
            <div id="demandContainer" class="table-container">
                <div id="demandListOverlay" class="table-list-overlay"><div id="demand-list-spinner" class="table-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="demandList">

                    <div class="table-responsive table-filter">
                        
                        <table class="table table-striped admin-table" cellspacing="0" >
                            <thead><tr class="header-tr">
                                <th><input type="checkbox" name="chk_demand_id_all" id="chk_demand_id_all" value="1" class="demand_id-chk" onclick="checkAllCheckboxes(this,'demand_id');" > ID</th>
                                <th>Demand Status</th><th>Created On</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($demands_list);$i++)
                                    <tr>
                                        <td><input type="checkbox" name="chk_demand_id_{{$demands_list[$i]->id}}" id="chk_demand_id_{{$demands_list[$i]->id}}" value="{{$demands_list[$i]->id}}" class="demand_id-chk"> {{$demands_list[$i]->id}}</td>
                                        <td>{{$demands_list[$i]->demand_status}}</td>
                                        
                                        <td>{{date('d M Y',strtotime($demands_list[$i]->created_at))}}</td>
                                        <td>@if($demands_list[$i]->status == 1) <i title="Enabled" class="far fa-check-circle"></i> @else <i title="Disabled" class="fa fa-ban"></i> @endif</td>
                                        <td>
                                            <a href="{{url('warehouse/demand/push/detail/'.$demands_list[$i]->id)}}" ><i title="Push Demand Detail" class="fas fa-eye"></i></a> 
                                           
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>

                        {{ $demands_list->links() }} <p>Displaying {{$demands_list->count()}} of {{ $demands_list->total() }} demands.</p>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>
    
@endif

@endsection

@section('scripts')
<script src="{{ asset('js/store.js') }}" ></script>
@endsection
