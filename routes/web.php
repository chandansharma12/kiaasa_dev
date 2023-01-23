<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes();

Route::get('logout', [ 'as' => 'logout', 'uses' => 'HomeController@logout']);
Route::get('privacy-policy', [ 'as' => 'privacypolicy', 'uses' => 'HomeController@privacyPolicy']);

Route::group(['middleware' => ['auth']], function () {
    Route::get('access-denied', [ 'as' => 'accessdenied', 'uses' => 'HomeController@accessDenied']);
    Route::get('demand-data/{id}', [ 'as' => 'demanddata', 'uses' => 'WarehouseController@demandData']);
});

Route::group(['middleware' => ['auth','permission']], function () {
    
    Route::get('dashboard', [ 'as' => 'dashboard', 'uses' => 'HomeController@dashboard']);
    Route::post('design/getproductslist', [ 'as' => 'getproductslist', 'uses' => 'DesignController@getProductsList']);
    Route::get('design/getprocesstypes/{parentCatId}', [ 'as' => 'getprocesstypes', 'uses' => 'DesignController@getProcessTypes']);
    Route::get('design/getaccessoriessubcategories/{parentCatId}', [ 'as' => 'getaccessoriessubcategories', 'uses' => 'DesignController@getAccessoriesSubcategories']);
    Route::get('design/getaccessoriessize/{parentSubCatId}', [ 'as' => 'getaccessoriessize', 'uses' => 'DesignController@getAccessoriesSize']);
    Route::post('design/getlookupitemsdata', [ 'as' => 'getlookupitemsdata', 'uses' => 'LookupItemsMasterController@getLookupItemsData']);
    Route::get('design/getdesignreviewslist/{designId}/{type}', [ 'as' => 'getdesignreviewslist', 'uses' => 'DesignController@getDesignReviewsList']);
    Route::get('design/detail/{designId}/{version?}/{historyTypeId?}', [ 'as' => 'designDetail', 'uses' => 'DesignController@designDetail']);
    Route::post('design/detail/{designId}/{version?}/{historyTypeId?}', [ 'as' => 'designDetail', 'uses' => 'DesignController@updateDesignDetail']);
    Route::get('design/getsizevariations/{id}', [ 'as' => 'getsizevariations', 'uses' => 'DesignController@getSizeVariations']);
    Route::post('design/updatesizevariationdata/{designId}', [ 'as' => 'updatesizevariationdata', 'uses' => 'DesignController@updateSizeVariationData']);
    Route::get('design/data/{designId}/{version?}/{historyTypeId?}', [ 'as' => 'designData', 'uses' => 'DesignController@designData']);
    Route::post('design/getdesignlookupitems/{designId}', [ 'as' => 'getdesignlookupitems', 'uses' => 'DesignController@getDesignLookupItems']);
    Route::post('user/sendnotification', [ 'as' => 'sendnotification', 'uses' => 'UserController@sendNotification']);
    Route::get('user/getnotificationslist', [ 'as' => 'getnotificationslist', 'uses' => 'UserController@getNotificationsList']);
    Route::get('user/readnotification/{notificationId}', [ 'as' => 'readnotification', 'uses' => 'UserController@readNotification']);
    Route::any('design/list', [ 'as' => 'designlist', 'uses' => 'DesignController@designList']);
    
    Route::post('design/add', [ 'as' => 'addDesign', 'uses' => 'DesignController@addDesign']);
    Route::get('design/edit/{designId}', [ 'as' => 'editDesign', 'uses' => 'DesignController@editDesign']);
    Route::post('design/save/{designId}', [ 'as' => 'saveDesign', 'uses' => 'DesignController@saveDesign']);
    Route::post('design/uploadimage/{designId}', [ 'as' => 'uploadImage', 'uses' => 'DesignController@uploadImage']);
    Route::post('design/updatedesigndata/{designId}', [ 'as' => 'updatedesignData', 'uses' => 'DesignController@updateDesignData']);
    
    Route::post('design/deletedesignitem/{designId}', [ 'as' => 'deletedesignitem', 'uses' => 'DesignController@deleteDesignItem']);
    Route::post('design/adddesignfabric/{designId}', [ 'as' => 'adddesignfabric', 'uses' => 'DesignController@addDesignFabric']);
    Route::post('design/updatedesignfabric/{designId}', [ 'as' => 'updatedesignfabric', 'uses' => 'DesignController@updateDesignFabric']);
    Route::post('design/adddesignaccessories/{designId}', [ 'as' => 'adddesignaccessories', 'uses' => 'DesignController@addDesignAccessories']);
    Route::post('design/updatedesignaccessories/{designId}', [ 'as' => 'updatedesignaccessories', 'uses' => 'DesignController@updateDesignAccessories']);
    Route::post('design/adddesignprocess/{designId}', [ 'as' => 'adddesignprocess', 'uses' => 'DesignController@addDesignProcess']);
    Route::post('design/updatedesignprocess/{designId}', [ 'as' => 'updatedesignprocess', 'uses' => 'DesignController@updateDesignProcess']);
    Route::post('design/adddesignpackagingsheet/{designId}', [ 'as' => 'adddesignpackagingsheet', 'uses' => 'DesignController@addDesignPackagingSheet']);
    Route::post('design/updatedesignpackagingsheet/{designId}', [ 'as' => 'updatedesignpackagingsheet', 'uses' => 'DesignController@updateDesignPackagingSheet']);
    Route::post('design/updatedesignproductprocess/{designId}', [ 'as' => 'updatedesignproductprocess', 'uses' => 'DesignController@updateDesignProductProcess']);
    Route::post('design/adddesignproductprocess/{designId}', [ 'as' => 'adddesignproductprocess', 'uses' => 'DesignController@addDesignProductProcess']);
    Route::post('design/deletedesignproductprocess/{designId}', [ 'as' => 'deletedesignproductprocess', 'uses' => 'DesignController@deleteDesignProductProcess']);
    Route::post('design/adddesignembroidery/{designId}', [ 'as' => 'adddesignembroidery', 'uses' => 'DesignController@addDesignEmbroidery']);
    Route::post('design/updatedesignembroidery/{designId}', [ 'as' => 'updatedesignembroidery', 'uses' => 'DesignController@updateDesignEmbroidery']);
     Route::post('design/adddesignprinting/{designId}', [ 'as' => 'adddesignprinting', 'uses' => 'DesignController@addDesignPrinting']);
    Route::post('design/updatedesignprinting/{designId}', [ 'as' => 'updatedesignprinting', 'uses' => 'DesignController@updateDesignPrinting']);
    Route::post('design/updatedesigngarmentcmt/{designId}', [ 'as' => 'updatedesigngarmentcmt', 'uses' => 'DesignController@updateDesignGarmentCmt']);
    
    Route::post('design/getproductdata/{parentId}', [ 'as' => 'getproductdata', 'uses' => 'DesignController@getProductData']);
    Route::get('design/getdesigntotalcost/{designId}/{version?}/{historyTypeId?}', [ 'as' => 'getdesigntotalcost', 'uses' => 'DesignController@getDesignTotalCost']);
    
    Route::get('design/getdesignspecificationsheet/{designId}', [ 'as' => 'getdesignspecificationsheet', 'uses' => 'DesignController@getDesignSpecificationSheet']);
    Route::post('design/adddesignspecificationsheet/{designId}', [ 'as' => 'adddesignspecificationsheet', 'uses' => 'DesignController@addDesignSpecificationSheet']);
    Route::post('design/updatedesignspecificationsheet/{designId}', [ 'as' => 'updatedesignspecificationsheet', 'uses' => 'DesignController@updateDesignSpecificationSheet']);
    Route::post('design/deletedesignspecificationsheet/{designId}', [ 'as' => 'deletedesignspecificationsheet', 'uses' => 'DesignController@deleteDesignSpecificationSheet']);
    Route::post('design/review/{designId}', [ 'as' => 'reviewdesign', 'uses' => 'DesignController@reviewDesign']);
    
    /*Route::post('quotation/requestquotation/{designId}', [ 'as' => 'requestquotation', 'uses' => 'QuotationController@requestQuotation']);
    Route::get('quotation/requests/{designId}', [ 'as' => 'getquotationrequests', 'uses' => 'QuotationController@getQuotationRequests']);
    Route::get('quotation/submissions/{designId}', [ 'as' => 'getquotationsubmissions', 'uses' => 'QuotationController@getQuotationSubmissions']);*/
    Route::get('quotation/list', [ 'as' => 'quotationlisting', 'uses' => 'QuotationController@listing']);
    Route::get('quotation/quotationvendorslist/{quotationId}', [ 'as' => 'quotationvendorslist', 'uses' => 'QuotationController@quotationVendorsList']);
    Route::get('vendor/quotation/list', [ 'as' => 'vendorquotationlist', 'uses' => 'VendorController@quotationList']);
   
    Route::get('quotation/submissions/list/{quotationId}', [ 'as' => 'quotationsubmissionslust', 'uses' => 'QuotationController@quotationSubmissionsList']);
    
    Route::post('purchase-order/createpurchaseorder/{quotationId}', [ 'as' => 'createpurchaseorder', 'uses' => 'PurchaseOrderController@createPurchaseOrder']);
    Route::get('purchase-orders/list', [ 'as' => 'purchaseorderslist', 'uses' => 'PurchaseOrderController@purchaseOrdersList']);
    Route::get('purchase-orders/detail/{orderId}', [ 'as' => 'purchaseorderdetail', 'uses' => 'PurchaseOrderController@purchaseOrderDetail']);
    
    Route::get('purchase-order/product/create', [ 'as' => 'createproductpurchaseorder', 'uses' => 'PurchaseOrderController@createProductPurchaseOrder']);
    Route::post('purchase-order/product/create', [ 'as' => 'createproductpurchaseorder', 'uses' => 'PurchaseOrderController@saveProductPurchaseOrder']);
    Route::get('purchase-order/product/list', [ 'as' => 'listproductpurchaseorder', 'uses' => 'PurchaseOrderController@listProductPurchaseOrder']);
    Route::get('purchase-order/product/grn/list', [ 'as' => 'listproductpurchaseordergrn', 'uses' => 'PurchaseOrderController@listProductPurchaseOrderGrn']);
    Route::get('purchase-order/product/detail/{orderId}', [ 'as' => 'detailproductpurchaseorder', 'uses' => 'PurchaseOrderController@productPurchaseOrderDetail']);
    Route::any('purchase-order/product/edit/{orderId}', [ 'as' => 'editproductpurchaseorder', 'uses' => 'PurchaseOrderController@productPurchaseOrderEdit']);
    Route::get('purchase-order/product/invoice/{orderID}', [ 'as' => 'purchaseorderproductinvoice', 'uses' => 'PurchaseOrderController@productPurchaseOrderInvoice']);
    Route::get('purchase-order/product/invoice/list/{poID}', [ 'as' => 'productpurchaseorderinvoicelist', 'uses' => 'PurchaseOrderController@productPurchaseOrderInvoiceList']);
    Route::get('purchase-order/purchased/products', [ 'as' => 'purchaseorderpurchasedproducts', 'uses' => 'PurchaseOrderController@productPurchaseOrderPurchasedProducts']);
    Route::get('purchase-order/stock/details', [ 'as' => 'purchaseorderstockdetails', 'uses' => 'PurchaseOrderController@productPurchaseOrderStockDetails']);
    Route::get('store/stock/details', [ 'as' => 'storestockdetails', 'uses' => 'PurchaseOrderController@storeStockDetails']);
    Route::get('product-sku/details', [ 'as' => 'productskudetails', 'uses' => 'PurchaseOrderController@productSkuDetails']);
    Route::get('purchase-order/edit/data/{orderId}', [ 'as' => 'editproductpurchaseorderdata', 'uses' => 'PurchaseOrderController@editPurchaseOrderData']);
    Route::post('purchase-order/edit/data/{orderId}', [ 'as' => 'editproductpurchaseorderdata', 'uses' => 'PurchaseOrderController@updatePurchaseOrderData']);
    
    Route::get('purchase-order/product/static/create', [ 'as' => 'createstaticproductpurchaseorder', 'uses' => 'PurchaseOrderController@createStaticProductPurchaseOrder']);
    Route::post('purchase-order/product/static/create', [ 'as' => 'createstaticproductpurchaseorder', 'uses' => 'PurchaseOrderController@saveStaticProductPurchaseOrder']);
    Route::any('purchase-order/product/static/edit/{orderId}', [ 'as' => 'editstaticproductpurchaseorder', 'uses' => 'PurchaseOrderController@editStaticProductPurchaseOrder']);
    
    Route::get('purchase-order/bulk/create', [ 'as' => 'createbulkpurchaseorder', 'uses' => 'PurchaseOrderController@createBulkPurchaseOrder']);
    Route::post('purchase-order/bulk/create', [ 'as' => 'createbulkpurchaseorder', 'uses' => 'PurchaseOrderController@saveBulkPurchaseOrder']);
    Route::get('purchase-order/bulk/list', [ 'as' => 'listbulkpurchaseorder', 'uses' => 'PurchaseOrderController@listBulkPurchaseOrder']);
    Route::get('purchase-order/bulk/detail/{orderId}', [ 'as' => 'detailbulkpurchaseorder', 'uses' => 'PurchaseOrderController@bulkPurchaseOrderDetail']);
    Route::get('purchase-order/bulk/invoice/{orderId}', [ 'as' => 'bulkpurchaseorderinvoice', 'uses' => 'PurchaseOrderController@bulkPurchaseOrderInvoice']);
    Route::get('purchase-order/bulk/invoice/list/{orderId}', [ 'as' => 'bulkpurchaseorderinvoicelist', 'uses' => 'PurchaseOrderController@bulkPurchaseOrderInvoiceList']);
    Route::get('purchase-order/bulk/items/import/{invoiceId}', [ 'as' => 'purchaseorderbulkitemsimport', 'uses' => 'PurchaseOrderController@importBulkPurchaseOrderItems']);
    Route::post('purchase-order/bulk/items/import/{invoiceId}', [ 'as' => 'purchaseorderbulkitemsimport', 'uses' => 'PurchaseOrderController@submitImportBulkPurchaseOrderItems']);
    Route::get('purchase-order/bulk/items/import/invoice/{invoiceId}', [ 'as' => 'purchaseorderbulkitemsimportinvoice', 'uses' => 'PurchaseOrderController@bulkPurchaseOrderItemsImportInvoice']);
    Route::get('purchase-order/bulk/items/qc/{invoiceId}', [ 'as' => 'purchaseorderbulkitemsqc', 'uses' => 'PurchaseOrderController@qcBulkPurchaseOrderItems']);
    Route::post('purchase-order/bulk/items/qc/{invoiceId}', [ 'as' => 'purchaseorderbulkitemsqc', 'uses' => 'PurchaseOrderController@submitQcBulkPurchaseOrderItems']);
    Route::get('purchase-order/bulk/items/qc-return/invoice/{qcReturnId}/{invoiceTypeId?}', [ 'as' => 'purchaseorderbulkitemsqcreturninvoice', 'uses' => 'PurchaseOrderController@bulkPurchaseOrderItemsReturnedInvoice']);
    Route::get('purchase-order/bulk/items/qc-return/gatepass/{qcReturnId}', [ 'as' => 'purchaseorderbulkitemsqcreturngatepass', 'uses' => 'PurchaseOrderController@bulkPurchaseOrderItemsReturnGatePass']);
    
    Route::get('purchase-order/bulk/finished/create', [ 'as' => 'createbulkfinishedpurchaseorder', 'uses' => 'PurchaseOrderController@createBulkFinishedPurchaseOrder']);
    Route::post('purchase-order/bulk/finished/create', [ 'as' => 'createbulkfinishedpurchaseorder', 'uses' => 'PurchaseOrderController@saveBulkFinishedPurchaseOrder']);
    /*Route::get('purchase-order/bulk/finished/list', [ 'as' => 'listbulkfinishedpurchaseorder', 'uses' => 'PurchaseOrderController@listBulkFinishedPurchaseOrder']);
    Route::get('purchase-order/bulk/finished/detail/{orderId}', [ 'as' => 'detailbulkfinishedpurchaseorder', 'uses' => 'PurchaseOrderController@bulkFinishedPurchaseOrderDetail']);
    Route::get('purchase-order/bulk/finished/invoice/{orderId}', [ 'as' => 'bulkfinishedpurchaseorderinvoice', 'uses' => 'PurchaseOrderController@bulkFinishedPurchaseOrderInvoice']);
    Route::any('purchase-order/bulk/finished/invoice/list/{orderId}', [ 'as' => 'bulkfinishedpurchaseorderinvoicelist', 'uses' => 'PurchaseOrderController@bulkFinishedPurchaseOrderInvoiceList']);
    Route::get('purchase-order/bulk/finished/items/import/{invoiceId}', [ 'as' => 'purchaseorderbulkfinisheditemsimport', 'uses' => 'PurchaseOrderController@importBulkFinishedPurchaseOrderItems']);
    Route::post('purchase-order/bulk/finished/items/import/{invoiceId}', [ 'as' => 'purchaseorderbulkfinisheditemsimport', 'uses' => 'PurchaseOrderController@submitImportBulkFinishedPurchaseOrderItems']);
    Route::get('purchase-order/bulk/finished/items/import/invoice/{invoiceId}', [ 'as' => 'purchaseorderbulkfinisheditemsimportinvoice', 'uses' => 'PurchaseOrderController@bulkFinishedPurchaseOrderItemsImportInvoice']);
    Route::get('purchase-order/bulk/finished/items/qc/{invoiceId}', [ 'as' => 'purchaseorderbulkfinisheditemsqc', 'uses' => 'PurchaseOrderController@qcBulkFinishedPurchaseOrderItems']);
    Route::post('purchase-order/bulk/finished/items/qc/{invoiceId}', [ 'as' => 'purchaseorderbulkfinisheditemsqc', 'uses' => 'PurchaseOrderController@submitQcBulkFinishedPurchaseOrderItems']);*/
    
    Route::get('purchase-order/accessories/create', [ 'as' => 'createaccessoriespurchaseorder', 'uses' => 'PurchaseOrderController@createAccessoriesPurchaseOrder']);
    Route::post('purchase-order/accessories/create', [ 'as' => 'createaccessoriespurchaseorder', 'uses' => 'PurchaseOrderController@saveAccessoriesPurchaseOrder']);
    Route::get('purchase-order/accessories/list', [ 'as' => 'listaccessoriespurchaseorder', 'uses' => 'PurchaseOrderController@listAccessoriesPurchaseOrder']);
    Route::get('purchase-order/accessories/detail/{orderId}', [ 'as' => 'detailaccessoriespurchaseorder', 'uses' => 'PurchaseOrderController@accessoriesPurchaseOrderDetail']);
    Route::get('purchase-order/accessories/invoice/{orderId}', [ 'as' => 'accessoriespurchaseorderinvoice', 'uses' => 'PurchaseOrderController@accessoriesPurchaseOrderInvoice']);
    Route::get('purchase-order/accessories/invoice/list/{orderId}', [ 'as' => 'accessoriespurchaseorderinvoicelist', 'uses' => 'PurchaseOrderController@accessoriesPurchaseOrderInvoiceList']);
    Route::get('purchase-order/accessories/items/import/{invoiceId}', [ 'as' => 'purchaseorderaccessoriesitemsimport', 'uses' => 'PurchaseOrderController@importAccessoriesPurchaseOrderItems']);
    Route::post('purchase-order/accessories/items/import/{invoiceId}', [ 'as' => 'purchaseorderaccessoriesitemsimport', 'uses' => 'PurchaseOrderController@submitImportAccessoriesPurchaseOrderItems']);
    Route::get('purchase-order/accessories/items/import/invoice/{invoiceId}', [ 'as' => 'purchaseorderaccessoriesitemsimportinvoice', 'uses' => 'PurchaseOrderController@accessoriesPurchaseOrderItemsImportInvoice']);
    Route::any('accessories/list', [ 'as' => 'accessorieslist', 'uses' => 'PurchaseOrderController@accessoriesList']);
    Route::any('vendor/accessories/list', [ 'as' => 'vendoraccessorieslist', 'uses' => 'PurchaseOrderController@vendorAccessoriesList']);

    Route::get('vendor/materials/{vendorId}', [ 'as' => 'vendormaterials', 'uses' => 'VendorController@vendorMaterials']);
    Route::get('vendor/materialdata/{vendorId}', [ 'as' => 'getvendormaterialdata', 'uses' => 'VendorController@getVendorMaterialData']);
    Route::post('vendor/addmaterial/{vendorId}', [ 'as' => 'addvendormaterial', 'uses' => 'VendorController@addVendorMaterial']);
    Route::post('vendor/deletematerial/{vendorId}', [ 'as' => 'deletevendormaterial', 'uses' => 'VendorController@deleteVendorMaterial']);
    
    Route::get('user/editprofile', [ 'as' => 'editProfile', 'uses' => 'UserController@editProfile']);
    Route::post('user/updateprofile', [ 'as' => 'updateProfile', 'uses' => 'UserController@updateProfile']);
    Route::get('user/list', [ 'as' => 'userlist', 'uses' => 'UserController@listing']);
    Route::get('user/data/{id}', [ 'as' => 'userdata', 'uses' => 'UserController@data']);
    Route::post('user/update', [ 'as' => 'userupdate', 'uses' => 'UserController@update']);
    Route::post('user/add', [ 'as' => 'useradd', 'uses' => 'UserController@add']);
    Route::post('user/updatestatus', [ 'as' => 'userupdatestatus', 'uses' => 'UserController@updateStatus']);
    Route::post('user/updaterole', [ 'as' => 'userupdaterole', 'uses' => 'UserController@updateRole']);
    Route::get('user/activity/list', [ 'as' => 'useractivitylist', 'uses' => 'UserController@userActivityList']);
    
    Route::get('vendor/list', [ 'as' => 'vendorlist', 'uses' => 'VendorController@listing']);
    Route::get('vendor/data/{id}', [ 'as' => 'vendordata', 'uses' => 'VendorController@data']);
    Route::post('vendor/add', [ 'as' => 'vendoradd', 'uses' => 'VendorController@add']);
    Route::post('vendor/update', [ 'as' => 'vendorupdate', 'uses' => 'VendorController@update']);
    Route::post('vendor/updatestatus', [ 'as' => 'vendorupdatestatus', 'uses' => 'VendorController@updateStatus']);
    
    Route::get('story/list', [ 'as' => 'storylist', 'uses' => 'StoryController@storyList']);
    Route::post('story/add', [ 'as' => 'storyadd', 'uses' => 'StoryController@add']);
    Route::get('story/data/{id}', [ 'as' => 'storydata', 'uses' => 'StoryController@storyData']);
    Route::post('story/update', [ 'as' => 'storyupdate', 'uses' => 'StoryController@update']);
    Route::post('story/storyupdatestatus', [ 'as' => 'storyupdatestatus', 'uses' => 'StoryController@storyUpdateStatus']);
    
    Route::get('lookup-item/list', [ 'as' => 'lookupitemlist', 'uses' => 'LookupItemsMasterController@listing']);
    Route::post('lookup-item/add', [ 'as' => 'lookupitemadd', 'uses' => 'LookupItemsMasterController@add']);
    Route::get('lookup-item/data/{id}', [ 'as' => 'lookupitemdata', 'uses' => 'LookupItemsMasterController@data']);
    Route::post('lookup-item/update', [ 'as' => 'lookupitemupdate', 'uses' => 'LookupItemsMasterController@update']);
    Route::post('lookup-item/updatestatus', [ 'as' => 'lookupitemupdatestatus', 'uses' => 'LookupItemsMasterController@updateStatus']);
    Route::post('lookup-item/parentitemslist', [ 'as' => 'parentitemslist', 'uses' => 'LookupItemsMasterController@parentItemsList']);
    
    /*Route::get('product/list', [ 'as' => 'productlist', 'uses' => 'ProductController@listing']);
    Route::post('product/add', [ 'as' => 'productadd', 'uses' => 'ProductController@add']);
    Route::get('product/data/{id}', [ 'as' => 'productdata', 'uses' => 'ProductController@data']);
    Route::post('product/update', [ 'as' => 'productupdate', 'uses' => 'ProductController@update']);
    Route::post('product/updatestatus', [ 'as' => 'productupdatestatus', 'uses' => 'ProductController@updateStatus']);*/
    
    Route::get('production/dashboard', [ 'as' => 'productiondashboard', 'uses' => 'ProductionController@dashboard']);
    Route::get('production/design-list', [ 'as' => 'productiondesignlist', 'uses' => 'ProductionController@designList']);
    Route::post('production/updatedesignproductioncount/{designId}', [ 'as' => 'updatedesignproductioncount', 'uses' => 'ProductionController@updateDesignProductionCount']);
    Route::post('production/sku-quotation', [ 'as' => 'productionskuquotation', 'uses' => 'ProductionController@skuQuotation']);
    Route::post('production/addquotation', [ 'as' => 'productionaddquotation', 'uses' => 'ProductionController@addQuotation']);
    Route::post('production/uploaddesigndocument/{designId}', [ 'as' => 'uploaddesigndocument', 'uses' => 'ProductionController@uploadDesignDocument']);
    Route::get('production/downnloaddesigndocument/{designId}/{documentNumber}', [ 'as' => 'downnloaddesigndocument', 'uses' => 'ProductionController@downloadDesignDocument']);
    Route::post('production/deletedesigndocument', [ 'as' => 'deletedesigndocument', 'uses' => 'ProductionController@deleteDesignDocument']);
    Route::post('production/requestreview/{designId}', [ 'as' => 'productionrequestreview', 'uses' => 'ProductionController@requestReview']);
    
    Route::get('production-head/dashboard', [ 'as' => 'productionheaddashboard', 'uses' => 'ProductionHeadController@dashboard']);
    
    Route::post('production-head/reviewdesign/{designId}', [ 'as' => 'productionheadreviewdesign', 'uses' => 'ProductionHeadController@reviewProductionDesign']);
    
    Route::get('purchaser/dashboard', [ 'as' => 'purchaserdashboard', 'uses' => 'PurchaseOrderController@dashboard']);
    Route::get('purchaser/design-list', [ 'as' => 'purchaserdesignlist', 'uses' => 'PurchaseOrderController@designList']);
    Route::post('purchaser/sku-quotation', [ 'as' => 'purchaserskuquotation', 'uses' => 'PurchaseOrderController@skuQuotation']);
    Route::get('purchaser/design/po/list/{designId}', [ 'as' => 'purchaserdesignpolist', 'uses' => 'PurchaseOrderController@designPOList']);
    
    Route::get('administrator/dashboard', [ 'as' => 'administratordashboard', 'uses' => 'AdminController@dashboard']);
    
    Route::get('designer/dashboard', [ 'as' => 'designerdashboard', 'uses' => 'DesignController@dashboard']);
    
    Route::get('reviewer/dashboard', [ 'as' => 'reviewerdashboard', 'uses' => 'ReviewerController@dashboard']);
    
    Route::get('permission/list', [ 'as' => 'permissionlist', 'uses' => 'PermissionController@listing']);
    Route::post('permission/add', [ 'as' => 'permissionadd', 'uses' => 'PermissionController@add']);
    Route::get('permission/data/{id}', [ 'as' => 'permissiondata', 'uses' => 'PermissionController@data']);
    Route::post('permission/update', [ 'as' => 'permissionupdate', 'uses' => 'PermissionController@update']);
    Route::post('permission/updatestatus', [ 'as' => 'permissionupdatestatus', 'uses' => 'PermissionController@updateStatus']);
    Route::get('permission/role-permissions/{roleId?}', [ 'as' => 'permissionrolepermissions', 'uses' => 'PermissionController@rolePermissionsList']);
    Route::post('permission/updaterolepermissions/{roleId}', [ 'as' => 'updaterolepermissions', 'uses' => 'PermissionController@updateRolePermissions']);
    Route::get('permission/user-permissions/{userId?}', [ 'as' => 'permissionuserpermissions', 'uses' => 'PermissionController@userPermissionsList']);
    Route::post('permission/updateuserpermissions/{userId}', [ 'as' => 'updateuserpermissions', 'uses' => 'PermissionController@updateUserPermissions']);
    Route::get('user/permissions/{userId}', [ 'as' => 'userpermissions', 'uses' => 'PermissionController@userPermissions']);
    
    Route::post('purchaser/addquotation', [ 'as' => 'purchaseraddquotation', 'uses' => 'PurchaseOrderController@addQuotation']);
    
    Route::get('setting/list', [ 'as' => 'settinglist', 'uses' => 'SettingController@settingList']);
    Route::get('setting/data/{id}', [ 'as' => 'settingdata', 'uses' => 'SettingController@settingData']);
    Route::post('setting/update', [ 'as' => 'settingupdate', 'uses' => 'SettingController@update']);
    Route::post('setting/settingupdatestatus', [ 'as' => 'settingupdatestatus', 'uses' => 'SettingController@settingUpdateStatus']);
    Route::get('setting/gstrules/data', [ 'as' => 'gstrulesdata', 'uses' => 'SettingController@getGSTRules']);
    
    Route::get('store/asset/list', [ 'as' => 'storeassetlist', 'uses' => 'StoreController@storeAssetList']);
    Route::post('store/asset/create', [ 'as' => 'storeassetcreate', 'uses' => 'StoreController@addStoreAsset']);
    Route::get('store/asset/data/{id}', [ 'as' => 'storeassetdata', 'uses' => 'StoreController@storeAssetData']);
    Route::post('store/asset/update', [ 'as' => 'storeassetupdate', 'uses' => 'StoreController@updateStoreAsset']);
    Route::post('store/asset/updatestatus', [ 'as' => 'storeassetupdatestatus', 'uses' => 'StoreController@storeAssetUpdateStatus']);
    Route::get('store/asset/getregionprices/{id}', [ 'as' => 'storeassetgetregionprices', 'uses' => 'StoreController@getStoreAssetRegionPrices']);
    Route::post('store/asset/updateregionprices', [ 'as' => 'storeassetupdateregionprices', 'uses' => 'StoreController@updateStoreAssetRegionPrices']);
    
    Route::get('store/dashboard', [ 'as' => 'storedashboard', 'uses' => 'StoreController@dashboard']);
    
    Route::any('store/asset/order/list', [ 'as' => 'storeassetorderlist', 'uses' => 'StoreController@assetOrdersList']);
    Route::any('store/asset/order/create', [ 'as' => 'storeassetcreateorder', 'uses' => 'StoreController@createAssetOrder']);
    Route::any('store/asset/order/edit/{orderId}', [ 'as' => 'storeassetediteorder', 'uses' => 'StoreController@editAssetOrder']);
    Route::get('store/asset/order/detail/{orderId}', [ 'as' => 'storeassetdetailorder', 'uses' => 'StoreController@assetOrderDetail']);
    Route::post('store/asset/order/deleteitem', [ 'as' => 'storeassetorderdeleteitem', 'uses' => 'StoreController@deleteAssetOrderItem']);
    Route::post('store/asset/order/updatestatus', [ 'as' => 'storeassetorderupdatestatus', 'uses' => 'StoreController@updateAssetOrderStatus']);
    Route::get('store/asset/order/items-list', [ 'as' => 'storeassetorderitemslist', 'uses' => 'StoreController@assetsOrdersItemsList']);
    /*Route::get('store/asset/order/admin-list', [ 'as' => 'storeassetorderadminlist', 'uses' => 'StoreController@assetOrdersAdminList']);*/
    Route::post('store/asset/order/bill/add', [ 'as' => 'storeassetbilladd', 'uses' => 'StoreController@addAssetOrderBill']);
    Route::get('store/asset/order/bill/data/{billId}', [ 'as' => 'storeassetorderbilldata', 'uses' => 'StoreController@assetOrderBillData']);
    Route::post('store/asset/order/bill/update', [ 'as' => 'storeassetorderbilldata', 'uses' => 'StoreController@updateAssetOrderBill']);
    Route::post('store/asset/autocomplete_data', [ 'as' => 'storeassetautocompletedata', 'uses' => 'StoreController@assetAutocompleteData']);
    Route::get('store/asset/order/products/{orderId}', [ 'as' => 'storeassetorderproducts', 'uses' => 'StoreController@assetOrderProductsData']);
    Route::any('store/staff/list', [ 'as' => 'storestafflist', 'uses' => 'StoreStaffController@listing']);
    Route::get('store/bags/inventory/list', [ 'as' => 'storebagsinventorylist', 'uses' => 'StoreController@storeBagsInventoryList']);
    Route::post('store/bags/add', [ 'as' => 'storebagsadd', 'uses' => 'StoreController@addStoreBags']);
    Route::post('store/bags/update', [ 'as' => 'storebagsupdate', 'uses' => 'StoreController@updateStoreBags']);

    Route::get('store-head/dashboard', [ 'as' => 'storeheaddashboard', 'uses' => 'StoreHeadController@dashboard']);
    Route::get('store-head/asset/order/list', [ 'as' => 'storeheadassetorderlist', 'uses' => 'StoreHeadController@assetOrdersList']);

    Route::get('store/list', [ 'as' => 'storelist', 'uses' => 'StoreController@storeList']);
    Route::post('store/add', [ 'as' => 'storeadd', 'uses' => 'StoreController@addStore']);
    Route::post('store/update', [ 'as' => 'storeupdate', 'uses' => 'StoreController@updateStore']);
    Route::get('store/data/{storeId}', [ 'as' => 'storedata', 'uses' => 'StoreController@storeData']);
    Route::post('store/updatestatus', [ 'as' => 'storeupdatestatus', 'uses' => 'StoreController@storeUpdateStatus']);
    Route::any('user/stores/list', [ 'as' => 'userstoreslist', 'uses' => 'StoreController@userStoresList']);
    
    Route::get('store/demand/list', [ 'as' => 'storedemandlist', 'uses' => 'StoreController@demandsList']);
    Route::get('store/demand/create', [ 'as' => 'storedemandcreate', 'uses' => 'StoreController@createDemand']);
    Route::post('store/demand/create', [ 'as' => 'storedemandcreate', 'uses' => 'StoreController@submitCreateDemand']);
    Route::get('store/demand/edit/{demandId}', [ 'as' => 'storedemandedit', 'uses' => 'StoreController@editDemand']);
    Route::post('store/demand/edit/{demandId}', [ 'as' => 'storedemandedit', 'uses' => 'StoreController@submitEditDemand']);
    Route::get('store/demand/detail/{demandId}', [ 'as' => 'storedemanddetail', 'uses' => 'StoreController@demandDetail']);
    Route::post('store/demand/deleteitem', [ 'as' => 'storedemanddeleteitem', 'uses' => 'StoreController@deleteDemandItem']);
    Route::post('store/demandupdatestatus', [ 'as' => 'storedemandupdatestatus', 'uses' => 'StoreController@updateDemandStatus']);
    /*Route::get('store/admin-demands-list', [ 'as' => 'storeadmindemandslist', 'uses' => 'StoreController@adminDemandsList']);*/
    Route::post('store/demand/autocomplete_data', [ 'as' => 'storedemandautocompletedata', 'uses' => 'StoreController@demandAutocompleteData']);
    Route::get('store/demand/products-list/{demandId}', [ 'as' => 'storedemandproductslist', 'uses' => 'StoreController@demandProductsData']);
    Route::get('store/demand/pdf/{pdfType}/{demandId}', [ 'as' => 'storedemandpdf', 'uses' => 'StoreController@generateDemandPdf']);
    Route::any('store/demand/products/{demandId}', [ 'as' => 'storedemandproducts', 'uses' => 'StoreController@demandProductsList']);
    Route::get('category/report/sales/graph', [ 'as' => 'categoryreportsalesgraph', 'uses' => 'AdminController@categorySalesReportGraph']);
    
    Route::get('store/pos/inventory/list', [ 'as' => 'storeposinventorylist', 'uses' => 'StoreController@posInventoryList']);
    Route::get('store/pos/product/list', [ 'as' => 'storeposproductlist', 'uses' => 'StoreController@posProductList']);
    Route::get('store/report/sales/dates', [ 'as' => 'storereportsalesdates', 'uses' => 'AdminController@storeSalesReportByDate']);
    Route::get('report/store/to/customer', [ 'as' => 'reportstoretocustomer', 'uses' => 'PosProductController@storeToCustomerSalesReport']);
    Route::get('report/warehouse/to/store', [ 'as' => 'reportwarehousetostore', 'uses' => 'PosProductController@warehouseToStoreSalesReport']);
    Route::get('store/report/discount/types', [ 'as' => 'storereportdiscounttypes', 'uses' => 'AdminController@storeDiscountTypesReport']);
    Route::get('category/report/sales', [ 'as' => 'categoryreportsales', 'uses' => 'AdminController@categorySalesReport']);
    Route::get('category/detail/report/sales', [ 'as' => 'categorydetailreportsales', 'uses' => 'AdminController@categoryDetailSalesReport']);
    Route::get('subcategory/report/sales/{categoryId}', [ 'as' => 'subcategoryreportsales', 'uses' => 'AdminController@subcategorySalesReport']);
    Route::get('size/report/sales', [ 'as' => 'sizereportsales', 'uses' => 'AdminController@sizeSalesReport']);
    Route::get('store/report/sales', [ 'as' => 'storereportsales', 'uses' => 'AdminController@storeSalesReport']);
    Route::get('store/staff/report/sales', [ 'as' => 'storestaffreportsales', 'uses' => 'AdminController@storeStaffSalesReport']);
    Route::get('price/report/sales', [ 'as' => 'pricereportsales', 'uses' => 'AdminController@priceSalesReport']);
    Route::get('time-slot/report/sales', [ 'as' => 'timeslotreportsales', 'uses' => 'AdminController@timeSlotSalesReport']);
    Route::get('store/report/inventory/status', [ 'as' => 'storereportinventorystatus', 'uses' => 'AdminController@storeInventoryStatusReport']);
    Route::get('warehouse/report/inventory/status', [ 'as' => 'warehousereportinventorystatus', 'uses' => 'AdminController@warehouseInventoryStatusReport']);
    Route::get('warehouse/report/inventory/daily/update', [ 'as' => 'warehousereportinventorydailyupdate', 'uses' => 'AdminController@warehouseInventoryDailyUpdateReport']);
    Route::get('warehouse/report/inventory/balance', [ 'as' => 'warehousereportinventorybalance', 'uses' => 'AdminController@warehouseInventoryBalanceReport']);
    Route::get('store/inventory/review', [ 'as' => 'storeinventoryreview', 'uses' => 'StoreController@reviewInventory']);
    Route::get('inventory/report/raw', [ 'as' => 'inventoryreportraw', 'uses' => 'AdminController@inventoryRawReport']);
    Route::get('vendor/sku/inventory/report', [ 'as' => 'vendorskuinventoryreport', 'uses' => 'AdminController@vendorSkuInventoryReport']);
    Route::get('store/sku/inventory/report', [ 'as' => 'storeskuinventoryreport', 'uses' => 'AdminController@storeSkuInventoryReport']);
    Route::get('grn/sku/report', [ 'as' => 'grnskureport', 'uses' => 'AdminController@grnSkuReport']);
    Route::get('report/shelf/life', [ 'as' => 'reportshelflife', 'uses' => 'AdminController@shelfLifeReport']);
    Route::get('report/hsn/bill/sales', [ 'as' => 'reporthsnbillsales', 'uses' => 'PosProductController@hsnCodeBillSalesReport']);
    Route::get('report/warehouse/product/inventory/balance', [ 'as' => 'reportwarehouseproductinventorybalance', 'uses' => 'AdminController@warehouseProductWiseInventoryBalanceReport']);
    Route::get('report/warehouse/size/inventory/balance', [ 'as' => 'reportwarehousesizeinventorybalance', 'uses' => 'AdminController@warehouseSizeWiseInventoryBalanceReport']);
    Route::get('report/store/to/store', [ 'as' => 'reportstoretostore', 'uses' => 'PosProductController@storeToStoreSalesReport']);
    
    Route::get('warehouse/dashboard', [ 'as' => 'warehousedashboard', 'uses' => 'WarehouseController@dashboard']);
    
    Route::any('warehouse/demand/products/{demandId}', [ 'as' => 'warehousedemandproducts', 'uses' => 'WarehouseController@demandProductsList']);
    
    Route::get('warehouse/demand/inventory-push/list', [ 'as' => 'warehousedemandinventorypushlist', 'uses' => 'WarehouseController@inventoryPushDemandList']);
    Route::get('warehouse/demand/inventory-push/edit/{demandId}', [ 'as' => 'warehousedemandinventorypushedit', 'uses' => 'WarehouseController@editInventoryPushDemand']);
    Route::post('warehouse/demand/inventory-push/edit/{demandId}', [ 'as' => 'warehousedemandinventorypushedit', 'uses' => 'WarehouseController@updateInventoryPushDemand']);
    Route::any('warehouse/demand/inventory-push/detail/{demandId}', [ 'as' => 'warehousedemandinventorypushdetail', 'uses' => 'WarehouseController@inventoryPushDemandDetail']);
    Route::get('warehouse/demand/inventory-push/invoice/{demandId}', [ 'as' => 'warehousedemandinventorypushinvoice', 'uses' => 'WarehouseController@inventoryPushDemandInvoice']);
    Route::get('warehouse/demand/inventory-push/gatepass/{demandId}', [ 'as' => 'warehousedemandinventorypushgatepass', 'uses' => 'WarehouseController@inventoryPushDemandGatePass']);
    Route::any('warehouse/inventory/track', [ 'as' => 'warehouseinventorytrack', 'uses' => 'WarehouseController@trackInventory']);
    
    Route::get('warehouse/sor/inventory/import/{invoiceId}', [ 'as' => 'warehousesorinventoryimport', 'uses' => 'WarehouseController@importSorInventory']);
    Route::post('warehouse/sor/inventory/import/{invoiceId}', [ 'as' => 'warehousesorinventoryimport', 'uses' => 'WarehouseController@submitImportSorInventory']);
    Route::get('warehouse/sor/inventory/import/invoice/{invoiceId}', [ 'as' => 'warehousesorinventoryimportinvoice', 'uses' => 'WarehouseController@sorInventoryImportInvoice']);
    Route::get('warehouse/sor/inventory/qc/{invoiceId}', [ 'as' => 'warehousesorinventoryqc', 'uses' => 'WarehouseController@QcSorInventory']);
    Route::post('warehouse/sor/inventory/qc/{invoiceId}', [ 'as' => 'warehousesorinventoryqc', 'uses' => 'WarehouseController@submitQcSorInventory']);
    Route::get('warehouse/sor/inventory/qc-return/invoice/{qcReturnId}/{invoiceTypeId?}', [ 'as' => 'warehousesorinventoryqcreturninvoice', 'uses' => 'WarehouseController@SorInventoryReturnedInvoice']);
    Route::get('warehouse/sor/inventory/qc-return/gatepass/{qcReturnId}', [ 'as' => 'warehousesorinventoryqcreturngatepass', 'uses' => 'WarehouseController@SorInventoryReturnGatePass']);
    Route::any('warehouse/sor/inventory/debit-note/add/{invoiceId}', [ 'as' => 'warehousesorinventorydebitnotedd', 'uses' => 'WarehouseController@addSorInvoiceDebitNote']);
    Route::get('warehouse/sor/inventory/pending/invoice/{invoiceId}/{invoiceTypeId?}', [ 'as' => 'warehousesorinventorypendinginvoice', 'uses' => 'WarehouseController@sorInventoryPendingInvoice']);
    Route::any('warehouse/sor/inventory/debit-note/excess-amount/add/{invoiceId}', [ 'as' => 'warehousesorinventorydebitnotexcessamountedd', 'uses' => 'WarehouseController@addSorInvoiceExcessAmountDebitNote']);
    Route::get('warehouse/sor/inventory/debit-note/excess-amount/download/{invoiceId}/{invoiceTypeId?}', [ 'as' => 'warehousesorinventorydebitnotexcessamountdownload', 'uses' => 'WarehouseController@downloadSorInvoiceExcessAmountDebitNote']);
    Route::any('warehouse/sor/inventory/debit-note/less-amount/add/{invoiceId}', [ 'as' => 'warehousesorinventorydebitnotlessamountedd', 'uses' => 'WarehouseController@addSorInvoiceLessAmountDebitNote']);
    Route::get('warehouse/sor/inventory/debit-note/less-amount/download/{invoiceId}', [ 'as' => 'warehousesorinventorydebitnotlessamountdownload', 'uses' => 'WarehouseController@downloadSorInvoiceLessAmountDebitNote']);
    
    /*Route::get('warehouse/bulk/finished/inventory/import/{invoiceId}', [ 'as' => 'warehousebulkfinishedinventoryimport', 'uses' => 'WarehouseController@importBulkFinishedInventory']);
    Route::post('warehouse/bulk/finished/inventory/import/{invoiceId}', [ 'as' => 'warehousebulkfinishedinventoryimport', 'uses' => 'WarehouseController@submitImportBulkFinishedInventory']);
    Route::get('warehouse/bulk/finished/inventory/import/invoice/{invoiceId}', [ 'as' => 'warehousebulkfinishedinventoryimportinvoice', 'uses' => 'WarehouseController@bulkFinishedInventoryImportInvoice']);
    Route::get('warehouse/bulk/finished/inventory/qc/{invoiceId}', [ 'as' => 'warehousebulkfinishedinventoryqc', 'uses' => 'WarehouseController@QcBulkFinishedInventory']);
    Route::post('warehouse/bulk/finished/inventory/qc/{invoiceId}', [ 'as' => 'warehousebulkfinishedinventoryqc', 'uses' => 'WarehouseController@submitQcBulkFinishedInventory']);
    Route::get('warehouse/bulk/finished/inventory/qc-return/invoice/{qcReturnId}/{invoiceTypeId?}', [ 'as' => 'warehousebulkfinishedinventoryqcreturninvoice', 'uses' => 'WarehouseController@bulkFinishedInventoryReturnedInvoice']);
    Route::get('warehouse/bulk/finished/inventory/qc-return/gatepass/{qcReturnId}', [ 'as' => 'warehousebulkfinishedinventoryqcreturngatepass', 'uses' => 'WarehouseController@bulkFinishedInventoryReturnGatePass']);*/

    Route::get('store/demand/inventory-push/list', [ 'as' => 'storedemandinventorypushlist', 'uses' => 'StoreController@inventoryPushDemandList']);
    Route::get('store/demand/inventory-push/edit/{demandId}', [ 'as' => 'storedemandinventorypushedit', 'uses' => 'StoreController@editInventoryPushDemand']);
    Route::post('store/demand/inventory-push/edit/{demandId}', [ 'as' => 'storedemandinventorypushedit', 'uses' => 'StoreController@updateInventoryPushDemand']);
    Route::get('store/demand/inventory-push/detail/{demandId}', [ 'as' => 'storedemandinventorypushdetail', 'uses' => 'StoreController@inventoryPushDemandDetail']);
    Route::any('store/demand/inventory-push/debit-note/{demandId}', [ 'as' => 'storedemandinventorypushdebitnote', 'uses' => 'StoreController@inventoryPushDemandDebitNote']);
    Route::get('store/demand/inventory-push/debit-note/invoice/{demandId}/{invoiceTypeId?}', [ 'as' => 'storedemandinventorypushdebitnoteinvoice', 'uses' => 'StoreController@inventoryPushDemandDebitNoteInvoice']);
    
    Route::any('store/demand/inventory-return-complete/list', [ 'as' => 'storedemandinventoryreturncompletelist', 'uses' => 'StoreController@inventoryReturnCompleteDemandList']);
    Route::any('store/demand/inventory-return-complete/detail/{demandId}', [ 'as' => 'storedemandinventoryreturncompletedetail', 'uses' => 'StoreController@inventoryReturnCompleteDemandDetail']);
    Route::get('store/demand/inventory-return-complete/invoice/{demandId}/{invoiceTypeId?}', [ 'as' => 'storedemandinventoryreturncompleteinvoice', 'uses' => 'StoreController@inventoryReturnCompleteDemandInvoice']);
    Route::any('store/demand/inventory-transfer-store/list', [ 'as' => 'storedemandinventorytransferstorelist', 'uses' => 'StoreController@inventoryTransferStoreDemandList']);
    Route::any('store/demand/inventory-transfer-store/detail/{demandId}', [ 'as' => 'storedemandinventorytransferstoredetail', 'uses' => 'StoreController@inventoryTransferStoreDemandDetail']);
    Route::get('store/demand/inventory-transfer-store/edit/{demandId}', [ 'as' => 'storedemandinventorytransferstoreedit', 'uses' => 'StoreController@editInventoryTransferStoreDemand']);
    Route::post('store/demand/inventory-transfer-store/edit/{demandId}', [ 'as' => 'storedemandinventorytransferstoreedit', 'uses' => 'StoreController@updateInventoryTransferStoreDemand']);
    Route::any('store/demand/inventory-transfer-store/load/{demandId}', [ 'as' => 'storedemandinventorytransferstoreload', 'uses' => 'StoreController@loadInventoryTransferStoreDemand']);
    Route::get('store/demand/inventory-transfer-store/invoice/{demandId}/{invoiceTypeId?}', [ 'as' => 'storedemandinventorytransferstoreinvoice', 'uses' => 'StoreController@inventoryTransferStoreDemandInvoice']);
    
    Route::any('store/demand/inventory-return/list', [ 'as' => 'storedemandinventoryreturnlist', 'uses' => 'StoreController@inventoryReturnDemandList']);
    Route::any('store/demand/inventory-return/edit/{demandId}', [ 'as' => 'storedemandinventoryreturnedit', 'uses' => 'StoreController@editInventoryReturnDemand']);
    Route::any('store/demand/inventory-return/detail/{demandId}', [ 'as' => 'storedemandinventoryreturndetail', 'uses' => 'StoreController@inventoryReturnDemandDetail']);
    Route::get('store/demand/inventory-return/invoice/{demandId}/{invoiceTypeId?}', [ 'as' => 'storedemandinventoryreturninvoice', 'uses' => 'StoreController@inventoryReturnDemandInvoice']);
    Route::any('warehouse/demand/inventory-return/load/{demandId}', [ 'as' => 'warehousedemandinventoryreturnload', 'uses' => 'WarehouseController@inventoryReturnDemandLoad']);
    
    Route::any('warehouse/demand/inventory-assign/list', [ 'as' => 'warehousedemandinventoryassignlist', 'uses' => 'WarehouseController@inventoryAssignDemandList']);
    Route::any('warehouse/demand/inventory-assign/edit/{demandId}', [ 'as' => 'warehousedemandinventoryassignedit', 'uses' => 'WarehouseController@editInventoryAssignDemand']);
    Route::any('warehouse/demand/inventory-assign/detail/{demandId}', [ 'as' => 'warehousedemandinventoryassigndetail', 'uses' => 'WarehouseController@inventoryAssignDemandDetail']);
    
    Route::any('warehouse/demand/inventory-return-vendor/list', [ 'as' => 'warehousedemandinventoryreturnvendorlist', 'uses' => 'WarehouseController@inventoryReturnVendorDemandList']);
    Route::any('warehouse/demand/inventory-return-vendor/edit/{demandId}', [ 'as' => 'warehousedemandinventoryreturnvendoredit', 'uses' => 'WarehouseController@editInventoryReturnVendorDemand']);
    Route::any('warehouse/demand/inventory-return-vendor/detail/{demandId}', [ 'as' => 'warehousedemandinventoryreturnvendordetail', 'uses' => 'WarehouseController@inventoryReturnVendorDemandDetail']);
    Route::get('warehouse/demand/inventory-return-vendor/invoice/{demandId}/{invoiceTypeId?}', [ 'as' => 'warehousedemandinventoryreturnvendorinvoice', 'uses' => 'WarehouseController@inventoryReturnVendorDemandInvoice']);
    Route::get('warehouse/demand/inventory-return-vendor/gatepass/{demandId}', [ 'as' => 'warehousedemandinventoryreturnvendorgatepass', 'uses' => 'WarehouseController@inventoryReturnVendorDemandGatePass']);
    
    Route::get('pos/product/list', [ 'as' => 'posproductlist', 'uses' => 'PosProductController@posProductsList']);
    Route::post('pos/product/list', [ 'as' => 'posproductlist', 'uses' => 'PosProductController@updatePosProductList']);
    Route::post('pos/product/add', [ 'as' => 'posproductadd', 'uses' => 'PosProductController@addPosProduct']);
    Route::get('pos/product/data/{productId}', [ 'as' => 'posproductdata', 'uses' => 'PosProductController@posProductData']);
    Route::post('pos/product/update', [ 'as' => 'posproductupdate', 'uses' => 'PosProductController@updatePosProduct']);
    Route::post('pos/product/updatestatus', [ 'as' => 'posproductupdatestatus', 'uses' => 'PosProductController@posProductUpdateStatus']);
    Route::post('pos/product/detail', [ 'as' => 'posproductdetail', 'uses' => 'PosOrderController@posProductDetailByBarcode']);
    Route::post('pos/product/image/delete/{imageId}', [ 'as' => 'posproductimagedelete', 'uses' => 'PosProductController@deleteProductImage']);
    Route::get('pos/product/download/csv/{typeId}', [ 'as' => 'posproductdownloadcsv', 'uses' => 'PosProductController@downloadPosProductCsv']);
    Route::get('pos/product/detail/{productId}', [ 'as' => 'posproductdetail', 'uses' => 'PosProductController@posProductDetail']);
    Route::get('pos/product/import/csv', [ 'as' => 'posproductimportcsv', 'uses' => 'PosProductController@importPosProduct']);
    //Route::post('pos/product/import/csv', [ 'as' => 'posproductimportcsv', 'uses' => 'PosProductController@submitImportPosProduct']);
    Route::any('pos/product/inventory/list', [ 'as' => 'posproductinventorylist', 'uses' => 'PosProductController@listPosProductInventory']);
    Route::get('pos/product/inventory/detail/{id}', [ 'as' => 'posproductinventorydetail', 'uses' => 'PosProductController@posProductInventoryDetail']);
    Route::get('pos/product/inventory/barcodes/list', [ 'as' => 'posproductinventorybarcodeslist', 'uses' => 'PosProductController@listPosProductInventoryBarcodes']);
    Route::post('pos/product/static/add', [ 'as' => 'posproductstaticadd', 'uses' => 'PosProductController@addStaticPosProduct']);
    Route::post('pos/product/static/update/{productId}', [ 'as' => 'posproductstaticupdate', 'uses' => 'PosProductController@updateStaticPosProduct']);
    Route::get('pos/product/inventory/duplicate/list', [ 'as' => 'posproductinventoryduplicatelist', 'uses' => 'PosProductController@listPosProductDuplicateInventory']);
    Route::get('vendor/inventory/payment/add', [ 'as' => 'vendorinventorypaymentadd', 'uses' => 'VendorController@addVendorInventoryPayment']);
    Route::post('vendor/inventory/payment/add', [ 'as' => 'vendorinventorypaymentadd', 'uses' => 'VendorController@submitAddVendorInventoryPayment']);
    Route::get('vendor/inventory/payment/list', [ 'as' => 'vendorinventorypaymentlist', 'uses' => 'VendorController@vendorInventoryPaymentList']);
    Route::get('vendor/inventory/payment/detail/{paymentId}', [ 'as' => 'vendorinventorypaymentdetail', 'uses' => 'VendorController@vendorInventoryPaymentDetail']);
    Route::get('items/id/list', [ 'as' => 'itemsidlist', 'uses' => 'AccountsController@itemsIDList']);
    Route::get('pos/product/static/import', [ 'as' => 'posproductstaticimport', 'uses' => 'PosProductController@importStaticPosProduct']);
    Route::post('pos/product/static/import', [ 'as' => 'posproductstaticimport', 'uses' => 'PosProductController@submitImportStaticPosProduct']);

    Route::get('store/posbilling', [ 'as' => 'storeposbilling', 'uses' => 'PosOrderController@posBilling']);
    Route::post('pos/order/create', [ 'as' => 'posordercreate', 'uses' => 'PosOrderController@createPosOrder']);
    Route::get('pos/order/list', [ 'as' => 'posorderlist', 'uses' => 'PosOrderController@listPosOrder']);
    Route::get('pos/order/detail/{orderID}', [ 'as' => 'posorderdetail', 'uses' => 'PosOrderController@posOrderDetail']);
    Route::get('pos/order/invoice/{orderID}', [ 'as' => 'posorderinvoice', 'uses' => 'PosOrderController@posOrderInvoice']);
    Route::any('pos/order/edit/{orderID}', [ 'as' => 'posorderedit', 'uses' => 'PosOrderController@posOrderEdit']);
    Route::get('store/pos-billing', [ 'as' => 'storeposbillingupdated', 'uses' => 'PosOrderController@posBillingUpdated']);
    Route::post('pos/product/detail-updated', [ 'as' => 'posproductdetailupdated', 'uses' => 'PosOrderController@posProductDetailByBarcodeUpdated']);
    Route::post('pos/billing/products/detail', [ 'as' => 'posbillingproductsdetail', 'uses' => 'PosOrderController@posBillingProductsDetail']);
    Route::post('pos/order/create-updated', [ 'as' => 'posordercreateupdated', 'uses' => 'PosOrderController@createPosOrderUpdated']);
    Route::get('pos/customer/list', [ 'as' => 'poscustomerlist', 'uses' => 'PosOrderController@listPosCustomer']);
    Route::post('pos/orders/fake/create', [ 'as' => 'posordersfakecreate', 'uses' => 'PosOrderController@createFakePosOrders']);
    Route::post('pos/orders/fake/csv/create', [ 'as' => 'posordersfakecsvcreate', 'uses' => 'PosOrderController@createFakePosOrdersFromCsv']);
    Route::get('pos/order/series/update/list', [ 'as' => 'posorderseriesupdatelist', 'uses' => 'PosOrderController@posOrderSeriesUpdateList']);
    Route::get('pos/order/series/update/detail/{updateID}', [ 'as' => 'posorderseriesupdatedetail', 'uses' => 'PosOrderController@posOrderSeriesUpdateDetail']);
    Route::post('pos/orders/draft/save', [ 'as' => 'posordersdraftsave', 'uses' => 'PosOrderController@savePosOrderDraft']);
    Route::get('pos/orders/draft/list', [ 'as' => 'posordersdraftlist', 'uses' => 'PosOrderController@listPosOrderDrafts']);
    Route::get('pos/orders/draft/items/{draftID}', [ 'as' => 'posordersdraftitems', 'uses' => 'PosOrderController@getPosOrderDraftItems']);
    Route::post('pos/orders/draft/delete', [ 'as' => 'posordersdraftdelete', 'uses' => 'PosOrderController@deletePosOrderDraft']);
    Route::post('pos/order/cancel', [ 'as' => 'posordercancel', 'uses' => 'PosOrderController@cancelPosOrder']);
    Route::post('pos/order/hold', [ 'as' => 'posorderhold', 'uses' => 'PosOrderController@holdPosOrder']);
    Route::get('pos/order/error/list', [ 'as' => 'posordererrorlist', 'uses' => 'PosOrderController@posOrderErrorList']);
    Route::get('pos/order/error/detail/{orderID}', [ 'as' => 'posordererrordetail', 'uses' => 'PosOrderController@posOrderErrorDetail']);

    Route::get('accounts/dashboard', [ 'as' => 'accountsdashboard', 'uses' => 'AccountsController@dashboard']);
    /*Route::get('accounts/asset/order/list', [ 'as' => 'accountsassetorderlist', 'uses' => 'AccountsController@assetOrderList']);*/
    Route::get('accounts/asset/order/items-list', [ 'as' => 'accountsassetorderitemslist', 'uses' => 'AccountsController@assetsOrderItemsList']);
	
    Route::get('discount/list', [ 'as' => 'discountlist', 'uses' => 'DiscountController@discountlist']);
    Route::post('discount/add', [ 'as' => 'adddiscount', 'uses' => 'DiscountController@adddiscount']);
    Route::post('discount/update', [ 'as' => 'updatediscount', 'uses' => 'DiscountController@updatediscount']); 
    Route::get('discount/barcode/{barcode}', [ 'as' => 'getdiscount', 'uses' => 'DiscountController@getdiscount']); 
    Route::get('inventory-push/demand/discount/list/{demandID}', [ 'as' => 'inventorypushdemanddiscountlist', 'uses' => 'DiscountController@inventoryPushDemandDiscountList']);
    Route::any('discounts/list', [ 'as' => 'discountslist', 'uses' => 'DiscountController@discountslist']);
    Route::any('discount/multiple/add', [ 'as' => 'discountmultipleadd', 'uses' => 'DiscountController@addMultipleDiscounts']);
    Route::any('discount/multiple/edit', [ 'as' => 'discountmultipleedit', 'uses' => 'DiscountController@editMultipleDiscounts']);
    Route::post('discount/multiple/delete', [ 'as' => 'discountmultipledelete', 'uses' => 'DiscountController@deleteMultipleDiscounts']); 
    
    Route::get('vendor/report/sales/{vendorId?}', [ 'as' => 'vendorsalesreport', 'uses' => 'VendorController@vendorSalesReport']);
    Route::get('operation/dashboard', [ 'as' => 'operationdashboard', 'uses' => 'OperationController@dashboard']);
    Route::get('it/dashboard', [ 'as' => 'itdashboard', 'uses' => 'ITController@dashboard']);
    Route::get('audit/dashboard', [ 'as' => 'auditdashboard', 'uses' => 'AuditController@dashboard']);
    Route::any('audit/list', [ 'as' => 'auditlist', 'uses' => 'AuditController@auditsList']);
    Route::any('audit/inventory/scan/{auditId}', [ 'as' => 'auditinventoryscan', 'uses' => 'AuditController@auditScanInventory']);
    Route::get('audit/inventory/scan/detail/{auditId}', [ 'as' => 'auditinventoryscandetail', 'uses' => 'AuditController@auditScanInventoryDetail']);
    Route::get('audit/inventory/report/variance/{auditId}/{reportTypeId?}', [ 'as' => 'auditinventoryreportvariance', 'uses' => 'AuditController@auditInventoryVarianceReport']);
    Route::get('audit/inventory/report/mismatch/{auditId}', [ 'as' => 'auditinventoryreportmismatch', 'uses' => 'AuditController@auditInventoryMismatchReport']);
    Route::get('audit/detail/{auditId}', [ 'as' => 'auditdetail', 'uses' => 'AuditController@auditDetail']);
    Route::any('audit/inventory/scan/bulk/{auditId}', [ 'as' => 'auditinventoryscanbulk', 'uses' => 'AuditController@auditScanBulkInventory']);
    Route::get('audit/bill/create/{auditId}', [ 'as' => 'auditbillcreate', 'uses' => 'AuditController@createBill']);
    Route::post('audit/bill/create/{auditId}', [ 'as' => 'auditbillcreate', 'uses' => 'AuditController@submitCreateBill']);
    
    Route::any('audit/inventory/scan/wh/{auditId}', [ 'as' => 'auditinventoryscanwh', 'uses' => 'AuditController@auditScanWarehouseInventory']);
    Route::any('audit/inventory/scan/bulk/wh/{auditId}', [ 'as' => 'auditinventoryscanbulkwh', 'uses' => 'AuditController@auditScanBulkWarehouseInventory']);
    Route::get('audit/inventory/scan/detail/wh/{auditId}', [ 'as' => 'auditinventoryscandetailwh', 'uses' => 'AuditController@auditScanWarehouseInventoryDetail']);
    Route::get('audit/inventory/report/variance/wh/{auditId}/{reportTypeId?}', [ 'as' => 'auditinventoryreportvariancewh', 'uses' => 'AuditController@auditWarehouseInventoryVarianceReport']);
    
    Route::get('vendor/dashboard', [ 'as' => 'vendordashboard', 'uses' => 'VendorController@dashboard']);
    Route::get('vendor/inventory/status', [ 'as' => 'vendorinventorystatus', 'uses' => 'VendorController@inventoryStatus']);
    Route::get('debit/notes/list', [ 'as' => 'debitnoteslist', 'uses' => 'AdminController@debitNotesList']);
    Route::get('credit/notes/list', [ 'as' => 'creditnoteslist', 'uses' => 'AdminController@creditNotesList']);
    
    Route::any('hsn/gst/list', [ 'as' => 'hsngstlist', 'uses' => 'AdminController@hsnCodeGstList']);
    Route::any('category/hsn/list', [ 'as' => 'categoryhsnlist', 'uses' => 'AdminController@categoryHsnCodeList']);
    Route::get('asm/dashboard', [ 'as' => 'asmdashboard', 'uses' => 'ASMController@dashboard']);
    Route::get('report/closing-stock/detail', [ 'as' => 'reportclosingstockdetail', 'uses' => 'PosProductController@closingStockDetailReport']);
    Route::get('report/closing-stock/data', [ 'as' => 'reportclosingstockdata', 'uses' => 'AccountsController@closingStockDetailReport']);
    
    Route::get('hrm/dashboard', [ 'as' => 'hrmdashboard', 'uses' => 'HRMController@dashboard']);
    Route::get('user/profile/view/{userID}', [ 'as' => 'userprofileview', 'uses' => 'UserController@viewUserProfile']);
    Route::post('user/profile/update/{userID}', [ 'as' => 'userprofileupdate', 'uses' => 'UserController@updateUserProfile']);
    Route::get('user/attendance/list', [ 'as' => 'userattendancelist', 'uses' => 'UserController@listAttendance']);
    Route::get('user/attendance/edit', [ 'as' => 'userattendanceedit', 'uses' => 'UserController@editDailyAttendance']);
    Route::post('user/attendance/edit', [ 'as' => 'userattendanceedit', 'uses' => 'UserController@updateDailyAttendance']);
    Route::any('user/leaves/list', [ 'as' => 'userleaveslist', 'uses' => 'UserController@listLeaves']);
    Route::any('user/overtime/list', [ 'as' => 'userovertimelist', 'uses' => 'UserController@listOverTime']);
    Route::any('user/salary/list/{userID}', [ 'as' => 'usersalarylist', 'uses' => 'UserController@listSalary']);
    Route::get('user/salary/view/{salaryID}', [ 'as' => 'usersalaryview', 'uses' => 'UserController@viewsalary']);
    Route::any('user/salary/edit/{salaryID}', [ 'as' => 'usersalaryedit', 'uses' => 'UserController@editsalary']);
    Route::any('user/attendance/edit/{userID}', [ 'as' => 'userattendanceedituser', 'uses' => 'UserController@editUserAttendance']);
    Route::get('user/attendance/view/{userID}', [ 'as' => 'userattendanceview', 'uses' => 'UserController@viewUserAttendance']);
    
    Route::any('coupon/list', [ 'as' => 'couponlist', 'uses' => 'CouponController@listCoupons']);
    Route::get('coupon/detail/{couponID}', [ 'as' => 'coupondetail', 'uses' => 'CouponController@couponDetail']);
    Route::get('report/store/category/sales', [ 'as' => 'reportstorecategorysales', 'uses' => 'StoreController@storeCategorySalesReport']);
    Route::get('report/store/category/staff/sales', [ 'as' => 'reportstorecategorystaffsales', 'uses' => 'StoreController@storeCategoryStaffSalesReport']);
    
    Route::get('fic/dashboard', [ 'as' => 'ficdashboard', 'uses' => 'FakeInventoryController@dashboard']);
    Route::any('page/description/list', [ 'as' => 'pagedescriptionlist', 'uses' => 'AccountsController@pageDescriptionList']);
    Route::get('store/expense/list', [ 'as' => 'storeexpenselist', 'uses' => 'AccountsController@storeExpenseList']);
    Route::get('store/expense/master/list/{storeID}', [ 'as' => 'storeexpensemasterlist', 'uses' => 'AccountsController@storeExpenseMasterList']);
    Route::get('store/expense/master/edit/{storeID}', [ 'as' => 'storeexpensemasteredit', 'uses' => 'AccountsController@editStoreExpenseMaster']);
    Route::post('store/expense/master/edit/{storeID}', [ 'as' => 'storeexpensemasteredit', 'uses' => 'AccountsController@updateStoreExpenseMaster']);
    Route::get('store/expense/monthly/list/{storeID}', [ 'as' => 'storeexpensemonthlylist', 'uses' => 'AccountsController@storeExpenseMonthlyList']);
    Route::get('store/expense/monthly/edit/{storeID}', [ 'as' => 'storeexpensemonthlyedit', 'uses' => 'AccountsController@editStoreExpenseMonthly']);
    Route::post('store/expense/monthly/edit/{storeID}', [ 'as' => 'storeexpensemonthlyedit', 'uses' => 'AccountsController@updateStoreExpenseMonthly']);
    Route::get('report/profit-loss', [ 'as' => 'reportprofitloss', 'uses' => 'AccountsController@profitLossReport']);
    Route::get('store/expense/monthly/insert', [ 'as' => 'storeexpensemonthlyinsert', 'uses' => 'AccountsController@autoInsertMonthlyData']);
    Route::get('report/gst/b2b', [ 'as' => 'reportgstb2b', 'uses' => 'AccountsController@b2bGstReport']);
    Route::get('report/gst/b2c', [ 'as' => 'reportgstb2c', 'uses' => 'AccountsController@b2cGstReport']);
    Route::get('report/gst/hsn', [ 'as' => 'reportgsthsn', 'uses' => 'AccountsController@hsnGstReport']);
    
    Route::get('size/list', [ 'as' => 'sizelist', 'uses' => 'AccountsController@sizeList']);
    Route::post('size/add', [ 'as' => 'sizeadd', 'uses' => 'AccountsController@addSize']);
    
    Route::post('scheduler/add/pos/product/barcodes', [ 'as' => 'scheduleraddposproductbarcodes', 'uses' => 'PosProductController@addPosProductBarcodesToScheduler']);
    Route::get('scheduler/task/list', [ 'as' => 'schedulertasklist', 'uses' => 'PosProductController@schedulerTaskList']);
    Route::get('scheduler/task/detail/{taskID}', [ 'as' => 'schedulertaskdetail', 'uses' => 'PosProductController@schedulerTaskDetail']);
    Route::get('vendor/subvendors/list/{vendorID}', [ 'as' => 'vendorsubvendorslist', 'uses' => 'VendorController@vendorSubVendorsList']);
    Route::post('vendor/subvendor/add', [ 'as' => 'vendorsubvendoradd', 'uses' => 'VendorController@addVendorSubVendor']);
    Route::post('vendor/subvendor/delete', [ 'as' => 'vendorsubvendordelete', 'uses' => 'VendorController@deleteVendorSubVendor']);
    
    Route::get('store/report-types', [ 'as' => 'storereporttypes', 'uses' => 'AccountsController@storeReportTypes']);
    Route::post('store/report-types', [ 'as' => 'storereporttypes', 'uses' => 'AccountsController@updateStoreReportTypes']);
    
});

//Route::get('pos/product/import/csvdata', [ 'as' => 'posproductimportcsv', 'uses' => 'PosProductController@importPosProduct']);

/*Route::get('quotation/submit/{quotationId}/{vendorId}', [ 'as' => 'quotationsubmit', 'uses' => 'QuotationController@submitQuotation']);
Route::post('quotation/submit/{quotationId}/{vendorId}', [ 'as' => 'quotationsubmitpost', 'uses' => 'QuotationController@saveQuotation']);*/
Route::get('store/inventory/update', [ 'as' => 'storeinventoryupdate', 'uses' => 'AdminController@updateStoreInventory']);
Route::get('scheduler/execute', [ 'as' => 'schedulerexecute', 'uses' => 'PosProductController@executeScheduler']);
Route::get('store/inventory/sku/update/{type}', [ 'as' => 'storeinventoryskuupdate', 'uses' => 'AccountsController@updateStoreSKUInvBalance']);
Route::get('store/inventory/sku/updateprice', [ 'as' => 'storeinventoryskuupdateprice', 'uses' => 'AccountsController@updateStoreSKUPrice']);


Route::group(['prefix' => 'designer','middleware' => ['auth']], function () { 
    Route::get('sor/product/add', [ 'as' => 'get-sor-product-add', 'uses' => 'SorProductController@sorProductsAddView']);
    Route::post('sor/product/add', [ 'as' => 'post-sor-product-add', 'uses' => 'SorProductController@addSorProduct']);
    Route::get('sor/product/edit/{productId}', [ 'as' => 'sorproductedit', 'uses' => 'SorProductController@editSorProduct']);
    Route::post('sor/product/edit/{productId}', [ 'as' => 'sorproductedit', 'uses' => 'SorProductController@updateSorProduct']);
});
