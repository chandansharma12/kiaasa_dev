@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'User Stores List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'User Stores List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateStoreStatusErrorMessage" class="alert alert-danger elem-hidden"  ></div>
            <div id="updateStoreStatusSuccessMessage" class="alert alert-success elem-hidden" ></div>
            <form method="get">
                <div class="row justify-content-end" >
                    <div class="col-md-2" >
                        <select name="user_id" id="user_id"  class="form-control" >
                            <option value="">-- User --</option>
                            @for($i=0;$i<count($users_list);$i++)
                                <?php if($users_list[$i]->id == request('user_id')) $sel = 'selected';else $sel = ''; ?>  
                                <option value="{{$users_list[$i]->id}}" {{$sel}}>{{$users_list[$i]->name}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-2" >
                        <select name="state_id" id="state_id"  class="form-control" >
                            <option value="">-- State --</option>
                            @for($i=0;$i<count($states_list);$i++)
                                <?php if($states_list[$i]->id == request('state_id')) $sel = 'selected';else $sel = ''; ?>  
                                <option value="{{$states_list[$i]->id}}" {{$sel}}>{{$states_list[$i]->state_name}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="zone_id" id="zone_id"  class="form-control" >
                            <option value="">-- Zone --</option>
                            @for($i=0;$i<count($store_zones);$i++)
                                <?php if($store_zones[$i]['id'] == request('zone_id')) $sel = 'selected';else $sel = ''; ?>  
                                <option {{$sel}} value="{{$store_zones[$i]['id']}}">{{$store_zones[$i]['name']}}</option>
                            @endfor    
                        </select>
                    </div>
                    <div class="col-md-1" >
                        <select name="store_type" id="store_type" class="form-control">
                            <option value="">-- Store Type --</option>
                            <option value="1" @if(request('store_type') == 1) selected @endif>Kiaasa</option>
                            <option value="2" @if(request('store_type') == 2) selected @endif>Franchise</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="submit" name="search" id="search" value="Search" class="btn btn-dialog"></div>
                </div>
            </form>
            <div class="separator-10">&nbsp;</div>

            <div id="storeContainer" class="table-container">
                <div class="table-responsive">
                    <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                        <thead>
                            <tr class="header-tr">
                                <th><input type="checkbox" name="store_list_all" id="store_list_all"  value="1" onclick="checkAllCheckboxes(this,'store-list');"> 
                                    &nbsp;Name
                                </th>
                                <th>State</th>
                                <th>Region</th>
                                <th>Type</th>
                                <th>Zone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i=0;$i<count($store_list);$i++)
                            <?php if(in_array($store_list[$i]->id, $user_store_ids)) $chk = 'checked';else $chk = ''; ?>
                            <tr>
                                <td><input <?php echo $chk; ?> type="checkbox" name="store_list" id="store_list_{{$store_list[$i]->id}}" class="store-list-chk" value="{{$store_list[$i]->id}}"> &nbsp;{{$store_list[$i]->store_name}}</td>
                                <td>{{$store_list[$i]->state_name}}</td>
                                <td>{{$store_list[$i]->region_name}}</td>
                                <td>{{$store_type_text = ($store_list[$i]->store_type != null)?($store_list[$i]->store_type == 1)?'Kiaasa':'Franchise':''}}</td>
                                <td>{{$store_list[$i]->zone_name}}</td>
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                    
                </div>
                <form method="get">
                    <div id="updateUserStoresErrorMessage" class="alert alert-danger elem-hidden"  ></div>
                    <div id="updateUserStoresSuccessMessage" class="alert alert-success elem-hidden" ></div>
                    <div class="separator-10">&nbsp;</div>
                    <div class="row" >
                        <div class="col-md-2" ><input type="button" name="edit_user_stores_btn" id="edit_user_stores_btn" value="Update" class="btn btn-dialog" onclick="updateUserStores();"></div>
                    </div>
                </form>
                <div class="separator-10">&nbsp;</div>
            </div>
        </div>
    </section>

@endif

@endsection

@section('scripts')
@if(empty($error_message))
    <script src="{{ asset('js/store.js?v=1.251') }}" ></script>
@endif
@endsection
