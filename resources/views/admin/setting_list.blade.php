@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Setting List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Setting List'); ?>

    <section class="product_area">
        <div class="container-fluid" >

            <div id="updateSettingStatusErrorMessage" class="alert alert-danger" style="display:none;"></div>
            <div id="updateSettingStatusSuccessMessage" class="alert alert-success" style="display:none;"></div>
            <!--<form method="get">
                <div class="row justify-content-end" >

                    <div class="col-md-2" >
                        <select name="setting_action" id="setting_action" class="form-control">
                            <option value="">-- Select Action --</option>
                            <option value="enable">Enable</option>
                            <option value="disable">Disable</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-md-1" ><input type="button" name="editSetting" id="editSetting" value="Update" class="btn btn-dialog" onclick="updateSettingStatus();"></div>
                    <div class="col-md-1" ><input type="button" name="addSettingBtn" id="addSettingBtn" value="Add Setting" class="btn btn-dialog" onclick="addSetting();"></div>
                </div>
            </form>-->
            <div class="separator-10">&nbsp;</div>

            <div id="settingContainer">
                <div id="settingListOverlay"><div id="setting-list-spinner"><img width="75px;" src="{{asset('images/loading.gif')}}"></div></div>
                <div id="settingList">
                    <div class="table-responsive">
                        <table class="table table-striped clearfix admin-table" cellspacing="0" >
                            <thead><tr class="header-tr"><th><!--<input type="checkbox" name="setting_list_all" id="setting_list_all"  value="1" onclick="checkAllCheckboxes(this,'setting-list');">--> &nbsp;ID</th>
                                <th>Name</th><th>Value</th><th>Updated On</th><th>Action</th></tr></thead>
                            <tbody>
                                @for($i=0;$i<count($setting_list);$i++)
                                <tr>
                                    <td><!--<input type="checkbox" name="setting_list" id="setting_list_{{$setting_list[$i]->id}}" class="setting-list-chk" value="{{$setting_list[$i]->id}}">--> &nbsp;{{$setting_list[$i]->id}}</td>
                                    <td>{{$setting_list[$i]->setting_key}}</td>
                                    <td>{{$setting_list[$i]->setting_value}}</td>
                                    <td>{{(!empty($setting_list[$i]->updated_at))?date('d M Y H:i:s',strtotime($setting_list[$i]->updated_at)):''}}</td>
                                    <td><a href="javascript:;" class="setting-list-edit" onclick="editSetting({{$setting_list[$i]->id}});"><i title="Edit" class="far fa-edit"></i></a></td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                        {{ $setting_list->links() }}
                        <p>Displaying {{$setting_list->count()}} of {{ $setting_list->total() }} settings.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="edit_setting_dialog" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Edit Setting</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><img src="{{asset('images/close.png')}}" alt="Close" title="Close" /></button>
                </div>

                <div class="alert alert-success alert-dismissible" style="display:none" id="editSettingSuccessMessage"></div>
                <div class="alert alert-danger alert-dismissible" style="display:none" id="editSettingErrorMessage"></div>

                <form class="" name="editSettingFrm" id="editSettingFrm" type="POST">
                    <div class="modal-body">
                        <div class="form-group" >
                            <label>Name</label>
                            <span id="setting_name_edit"></span>
                        </div>
                        <div class="form-group" >
                            <label>Value</label>
                            <input id="setting_value_edit" type="text" class="form-control" name="setting_value_edit" value=""  >
                            <div class="invalid-feedback" id="error_validation_setting_value_edit"></div>
                        </div>
                    </div>
                    <div class="modal-footer center-footer">
                        <div id="ssetting_edit_spinner" style="display:none;" class="spinner-border spinner-border-sm text-secondary" role="status"><span class="sr-only">Loading...</span></div>
                        <button type="button" id="setting_edit_cancel" name="setting_edit_cancel" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id ="setting_edit_submit" name="setting_edit_submit" class="btn btn-dialog" onclick="updateSetting();">Submit</button>
                        <input type="hidden" name="setting_edit_id" id="setting_edit_id" value="">
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

@endsection

@section('scripts')
<script src="{{ asset('js/setting.js') }}" ></script>
@endsection
