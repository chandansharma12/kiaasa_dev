@extends('layouts.default')
@section('content')

<?php echo CommonHelper::displayPageErrorMsg($error_message); ?>

@if(empty($error_message))
    <?php $breadCrumbArray = array(array('name'=>'Dashboard','link'=>'it/dashboard')); ?>
    <?php echo CommonHelper::pageSubHeader($breadCrumbArray,'Dashboard'); ?>

    <section class="product_area">
        <div class="container-fluid" >
            <div id="dashboardErrorMessage" class="alert alert-danger elem-hidden" ></div>
            <section class="product_area">
                <div class="container-fluid" >
                    
                    <div id="productionDashboard">
                        <div class="row">
                            <div class="col-md-2"><a href="{{url('purchase-order/product/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-shopping-bag fas-icon" ></i> SOR Purchase Orders</a></div>
                            <div class="col-md-2"><a href="{{url('store/report/sales/dates')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-file fas-icon"></i> Stores Sales Report</a>&nbsp</div>
                            <div class="col-md-2"><a href="{{url('warehouse/demand/inventory-assign/list')}}" class="btn btn-dialog dashboard-btn"><i class="fa fa-desktop fas-icon" ></i> Assign Inventory</a></div>
                            <!--<button id="rzp-button1">Pay</button>-->
                        </div>
                        
                       
                    </div>
                </div>
            </section>
        </div>
    </section>

@endif

@endsection

@section('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "rzp_test_B6V7iE0NspoP7T", // Enter the Key ID generated from the Dashboard
    "amount": "1000", // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
    "currency": "INR",
    "name": "Acme Corp",
    "description": "Test Transaction",
    "image": "https://example.com/your_logo",
    "order_id": "order_JQkf1CJfYbUiJ9", //This is a sample Order ID. Pass the `id` obtained in the response of Step 1
    "handler": function (response){
        alert(response.razorpay_payment_id);
        alert(response.razorpay_order_id);
        alert(response.razorpay_signature)
    },
    "prefill": {
        "name": "Yogesh Kumar",
        "email": "yogesh.kumar@example.com",
        "contact": "9999999999"
    },
    "notes": {
        "address": "Razorpay Corporate Office"
    },
    "theme": {
        "color": "#3399cc"
    }
};
var rzp1 = new Razorpay(options);
rzp1.on('payment.failed', function (response){
        alert(response.error.code);
        alert(response.error.description);
        alert(response.error.source);
        alert(response.error.step);
        alert(response.error.reason);
        alert(response.error.metadata.order_id);
        alert(response.error.metadata.payment_id);
});
document.getElementById('rzp-button1').onclick = function(e){
    rzp1.open();
    e.preventDefault();
}
</script>

@endsection
