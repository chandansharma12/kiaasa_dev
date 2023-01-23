@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Store Bags Inventory')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Store Bags Inventory'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateStoreStatusErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <div id="updateStoreStatusSuccessMessage" class="alert alert-success elem-hidden"></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-1" >
                        <input type="text" name="id" id="id" class="form-control" placeholder="ID" value="{{request('id')}}" />
                    </div>
                    <div class="col-md-2" >
                        <select name="store_id" id="store_id"  class="form-control" >
                            <option value="">-- Store --</option>
                            @for($i=0;$i<count($store_list);$i++)
                                <?php if($store_list[$i]['id'] == request('store_id')) $sel = 'selected';else $sel = ''; ?>  
                                <option value="{{$store_list[$i]['id']}}" {{$sel}}>{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>

                    <div class="col-md-2" ><input type="button" name="addStoreInvBtn" id="addStoreInvBtn" value="Add Store Bags" class="btn btn-dialog" onclick="addStoreBags();"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="storeContainer" class="table-container">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th>ID</th>
                                <th>Store</th>
                                <th>Bags Assigned</th>
                                <th>Date Assigned</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($inv_list);$i++)
                                <tr>
                                    <td>{{$inv_list[$i]->id}}</td>
                                    <td>{{$inv_list[$i]->store_name}}</td>
                                    <td>{{$inv_list[$i]->bags_assigned}}</td>
                                    <td>{{date('d M Y',strtotime($inv_list[$i]->date_assigned))}}</td>
                                    <td>{{$created_at = date('d M Y',strtotime($inv_list[$i]->created_at))}}</td>
                                    <td>
                                        <?php $days_diff = CommonHelper::dateDiff($created_at,date('Y/m/d')); ?>
                                        @if($days_diff <= 5)
                                            <a href="javascript:;" class="store-list-edit" title="Edit" onclick="editStoreBags({{$inv_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a> &nbsp;
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                    {{ $inv_list->withQueryString()->links() }}
                    <p>Displaying {{$inv_list->count()}} of {{ $inv_list->total() }} records.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="add_store_bags_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Add Store Bags</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="addStoreBagsSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="addStoreBagsErrorMessage"></div>

                <form class="" name="addStoreBagsFrm" id="addStoreBagsFrm" type="POST">
                    <div class="modal-body">
                        
                        <div class="form-group" >
                            <label>Store</label>
                            <select name="store_id_add" id="store_id_add"  class="form-control" >
                                <option value="">-- Store --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <option value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_store_id_add"></div>	
                        </div>
                        
                        <div class="form-group" >
                            <label>Bags Assigned</label>
                            <select name="bags_count_add" id="bags_count_add"  class="form-control" >
                                <option value="">-- Bags --</option>
                                @for($i=1;$i<=2500;$i++)
                                    <option value="{{$i}}">{{$i}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_bags_count_add"></div>	
                        </div>
                        
                        <div class="form-group" >
                            <label>Date Assigned</label>
                            <input id="date_assigned_add" type="text" class="form-control" name="date_assigned_add" value=""  >
                            <div class="invalid-feedback" id="error_validation_date_assigned_add"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="store_bag_add_cancel" name="store_bag_add_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="store_bag_add_submit" name="store_bag_add_submit" class="btn btn-dialog" onclick="submitAddStoreBags();">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_store_bags_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Store Bags</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible elem-hidden" id="editStoreBagsSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible elem-hidden"  id="editStoreBagsErrorMessage"></div>

                <form class="" name="editStoreBagsFrm" id="editStoreBagsFrm" type="POST">
                    <div class="modal-body">
                        
                        <div class="form-group" >
                            <label>Store</label>
                            <select name="store_id_edit" id="store_id_edit"  class="form-control" >
                                <option value="">-- Store --</option>
                                @for($i=0;$i<count($store_list);$i++)
                                    <option value="{{$store_list[$i]['id']}}">{{$store_list[$i]['store_name']}} ({{$store_list[$i]['store_id_code']}})</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_store_id_edit"></div>	
                        </div>
                        
                        <div class="form-group" >
                            <label>Bags Assigned</label>
                            <select name="bags_count_edit" id="bags_count_edit"  class="form-control" >
                                <option value="">-- Bags --</option>
                                @for($i=1;$i<=2500;$i++)
                                    <option value="{{$i}}">{{$i}}</option>
                                @endfor    
                            </select>
                            <div class="invalid-feedback" id="error_validation_bags_count_edit"></div>	
                        </div>
                        
                        <div class="form-group" >
                            <label>Date Assigned</label>
                            <input id="date_assigned_edit" type="text" class="form-control" name="date_assigned_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_date_assigned_edit"></div>	
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <button type="button" id="store_bag_edit_cancel" name="store_bag_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="store_bag_edit_submit" name="store_bag_edit_submit" class="btn btn-dialog" onclick="submitEditStoreBags();">Submit</button>
                        <input type="hidden" name="store_bags_edit_id" id="store_bags_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script src="{{ asset('js/store.js?v=1.258') }}" ></script>
    <script src="{{ asset('js/bootstrap-datepicker.min.js') }}" ></script>
    <link rel="stylesheet" href="{{ asset('css/bootstrap-datepicker.standalone.min.css') }}" />
    <script type="text/javascript">$('#date_assigned_add,#date_assigned_edit').datepicker({format: 'dd-mm-yyyy',endDate: '-0d'});</script>
@endif
@endsection
