@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'dashboard'),array('name'=>'Scheduler Tasks List')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Scheduler Tasks List'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <form method="GET"></form>
            <div class="separator-10">&nbsp;</div>

            <div class="table-responsive">
                <table class="table table-striped clearfix admin-table" cellspacing="0" >
                    <thead><tr class="header-tr"><th>Task ID</th><th>Type</th><th>Ref ID</th><th>Ref No</th><th>Total Items</th><th>Completed Items</th><th>Status</th><th>Date Added</th><th>Details</th></tr></thead>
                    <tbody>
                        @for($i=0;$i<count($task_list);$i++)
                            <tr>
                                <td>{{$task_list[$i]->id}}</td>
                                <td>{{$task_list[$i]->task_type}}</td>
                                <td>{{$task_list[$i]->task_ref_id}}</td>
                                <td>{{$task_list[$i]->task_ref_no}}</td>
                                <td>{{$task_list[$i]->task_items_count}}</td>
                                <td>{{$task_list[$i]->task_items_comp_count}}</td>
                                <td>{{str_replace('_',' ',$task_list[$i]->task_status)}}</td>
                                <td>@if(!empty($task_list[$i]->created_at)) {{date('d M Y H:i',strtotime($task_list[$i]->created_at))}} @endif</td>
                                <td><a href="{{url('scheduler/task/detail/'.$task_list[$i]->id)}}" class="store-list-edit" ><i title="View Task Details" class="far fa-eye"></i></a>&nbsp;</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                @if(empty($error_message) )
                    {{$task_list->withQueryString()->links() }}
                    <p>Displaying {{$task_list->count()}} of {{ $task_list->total() }} Tasks.</p>
                @endif
            </div>
        </div>
    </section>

@endif

@endsection
