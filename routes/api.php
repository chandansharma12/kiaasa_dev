<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('pos/login', 'PosApiController@login');
Route::get('pos/store/orders/list', 'PosApiController@storeOrdersList');

Route::group(['middleware' => ['pos_api']], function () {
    Route::get('pos/product/detail/{barcode}', 'PosApiController@getProductData');
    Route::post('pos/customer/detail', 'PosApiController@getCustomerData');
    Route::post('pos/customer/add', 'PosApiController@addCustomerData');
    Route::post('pos/order/add', 'PosApiController@addPosOrder');
    Route::post('pos/logout', 'PosApiController@logout');
    Route::get('pos/product/list', 'PosApiController@getProductList');
    Route::get('pos/category/list', 'PosApiController@getCategoryList');
    Route::get('pos/subcategory/list/{category_id?}', 'PosApiController@getSubCategoryList');
    Route::get('pos/store/list', 'PosApiController@getStoreList');
    Route::get('pos/size/list', 'PosApiController@getSizeList');
    Route::get('pos/color/list', 'PosApiController@getColorList');
    Route::get('pos/state/list', 'PosApiController@getStateList');
    Route::get('pos/product/details/{productId}/{storeId}', 'PosApiController@getProductDetails');
    Route::post('pos/customer/create', 'PosApiController@createPosCustomer');// register
    Route::post('pos/order/create', 'PosApiController@createPosOrder');
    Route::post('pos/customer/details', 'PosApiController@getPosCustomerDetails');
    Route::get('pos/order/list/{customerId}', 'PosApiController@getPosOrderList');
    Route::get('pos/order/detail/{orderId}', 'PosApiController@getPosOrderDetails');
    Route::post('pos/customer/profile/update', 'PosApiController@updatePosCustomerProfile');
    Route::post('pos/customer/password/update', 'PosApiController@updatePosCustomerPassword');
    Route::post('pos/customer/address/create', 'PosApiController@createPosCustomerAddress');
    Route::get('pos/customer/address/list/{customerId}', 'PosApiController@getPosCustomerAddressList');
    Route::post('pos/razorpay/order/create', 'PosApiController@createRazorPayOrder');
    Route::post('pos/razorpay/order/signature/verify', 'PosApiController@verifyRazorPaySignature');

    // forget password api
    Route::post('pos/customer/forget-password', 'PosApiController@posCustomerForgetPassword');
    Route::post('pos/customer/reset-password', 'PosApiController@posCustomeResetPassword');
    Route::post('pos/customer/product-wishlist-create', 'PosApiController@pos_product_wishlist');
    Route::get('pos/customer/read-product-wishlist/{customerId}', 'PosApiController@get_customer_product_wishlist');
    Route::post('pos/customer/destroy-product-wishlist', 'PosApiController@destroy_customer_product_wishlist');

    Route::post('pos/product/guest-customer-send-otp', 'PosApiController@guest_customer_send_otp');
    Route::post('pos/product/guest-customer-verify-otp', 'PosApiController@guest_customer_verify_otp');

    Route::get('pos/customer/test-shiprocket', 'PosApiController@customer_ship_rocket');

     // Create billing address
     Route::post('pos/customer/billing/address/create', 'PosApiController@createPosCustomerBillingAddress');
    // Get all cities
     Route::get('pos/customer/getCities/{id}','PosApiController@getCitiesPostalcode');


});