@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Scheduler Task List','link'=>'scheduler/task/list'),array('name'=>'Scheduler Task Detail')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Scheduler Task Detail'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form id="order_detail_form" name="order_detail_form">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="Color">Task ID</label>						
                        {{$task_data->id}}     
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Task Type</label>						
                        {{$task_data->task_type}}     
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Task Ref ID</label>						
                        {{$task_data->task_ref_id}}     
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Task Ref No</label>						
                        {{$task_data->task_ref_no}}     
                    </div>
                    <div class="form-group col-md-2">
                        <label for="Color">Task Status</label>						
                        {{$task_data->task_status}}     
                    </div>
                </div>    
            </form>    
            <div class="separator-10">&nbsp;</div>

            <h6>Task Items:</h6>
            <?php $error_status = ['0'=>'Pending','1'=>'In Progress','2'=>'Completed','3'=>'Error']; ?>
            <div class="table-responsive">
                <table class="table table-striped clearfix admin-table" cellspacing="0" style="font-size:12px; ">
                    <thead><tr class="header-tr"><th>ID</th><th>Data</th><th>Status</th><th>Error</th><th>Date Added</th><th>Date Updated</th></tr></thead>
                    <tbody>
                        @for($i=0;$i<count($task_items);$i++)
                            <tr>
                                <td>{{$task_items[$i]->id}}</td>
                                <td>{{$task_items[$i]->task_item_data}}</td>
                                <td>{{$error_status[$task_items[$i]->task_item_status]}}</td>
                                <td>{{$task_items[$i]->error_text}}</td>
                                <td>@if(!empty($task_items[$i]->created_at)) {{date('d M Y H:i',strtotime($task_items[$i]->created_at))}} @endif</td>
                                <td>@if(!empty($task_items[$i]->updated_at)) {{date('d M Y H:i',strtotime($task_items[$i]->updated_at))}} @endif</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                @if(empty($error_message) )
                    {{$task_items->withQueryString()->links() }}
                    <p>Displaying {{$task_items->count()}} of {{ $task_items->total() }} Task Items.</p>
                @endif
            </div>
            
        </div>
    </section>

@endif

@endsection
