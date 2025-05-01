<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
/*Admin*/
$routes->get('/admin', 'Admin\HomeController::index');
$routes->get('/admin/login/', 'Admin\HomeController::index');
$routes->post('/home/authenticate', 'Admin\HomeController::authenticate');
$routes->get('/admin/logout', 'Admin\LogoutController::index');
$routes->get('/dashboard', 'Admin\DashboardController::index');
$routes->get('/add-user', 'Admin\AddUserController::index');
$routes->post('/adduser/insert', 'Admin\AddUserController::insert');
$routes->get('/user-masterlist', 'Admin\UserMasterlistController::index');
$routes->post('/usermasterlist/getData', 'Admin\UserMasterlistController::getData');
$routes->get('/usermasterlist/downloadCSV', 'Admin\UserMasterlistController::downloadCSV');
$routes->delete('/usermasterlist/delete/(:num)', 'Admin\UserMasterlistController::delete/$1');
$routes->get('/edit-user/(:num)', 'Admin\EditUserController::index/$1');
$routes->post('/edituser/update', 'Admin\EditUserController::update');
$routes->get('/quotation-masterlist', 'Admin\QuotationMasterlistController::index');
$routes->post('/quotationmasterlist/getData', 'Admin\QuotationMasterlistController::getData');
$routes->delete('/quotationmasterlist/delete/(:num)', 'Admin\QuotationMasterlistController::delete/$1');
$routes->post('/quotationmasterlist/updateStatus/(:num)', 'Admin\QuotationMasterlistController::updateStatus/$1');
$routes->post('/quotationmasterlist/updateShipment/(:num)', 'Admin\QuotationMasterlistController::updateShipment/$1');
$routes->get('/quotationmasterlist/getShipment/(:num)', 'Admin\QuotationMasterlistController::getShipment/$1');
$routes->post('/quotationmasterlist/updateDeliveryDate/(:num)', 'Admin\QuotationMasterlistController::updateDeliveryDate/$1');
$routes->get('/dashboard/getData', 'Admin\DashboardController::getData');
$routes->get('/send-quotation', 'Admin\SendQuotationController::index');
$routes->post('/sendquotation/insert', 'Admin\SendQuotationController::insert');
$routes->get('/request-quotation-masterlist', 'Admin\RequestQuotationListController::index');
$routes->post('/requestquotationmasterlist/getData', 'Admin\RequestQuotationListController::getData');
$routes->post('/requestquotationmasterlist/insert', 'Admin\RequestQuotationListController::insert');
$routes->post('/requestquotationmasterlist/updateStatus/(:num)', 'Admin\RequestQuotationListController::updateStatus/$1');
$routes->get('/subscribers-masterlist', 'Admin\SubscribersMasterlistController::index');
$routes->post('/subscribersmasterlist/getData', 'Admin\SubscribersMasterlistController::getData');
$routes->delete('/subscribersmasterlist/delete/(:num)', 'Admin\SubscribersMasterlistController::delete/$1');
$routes->get('/send-newsletter', 'Admin\SendNewsletterController::index');
$routes->post('/sendnewsletter/sendMessage', 'Admin\SendNewsletterController::sendMessage');
$routes->get('/download-files/(:num)', 'Admin\RequestQuotationListController::downloadFiles/$1');
$routes->get('/add-material', 'Admin\AddMaterialController::index');
$routes->post('/addmaterial/insert', 'Admin\AddMaterialController::insert');
$routes->get('/material-masterlist', 'Admin\MaterialMasterlistController::index');
$routes->post('/materialmasterlist/getData', 'Admin\MaterialMasterlistController::getData');
$routes->delete('/materialmasterlist/delete/(:num)', 'Admin\MaterialMasterlistController::delete/$1');
$routes->get('/edit-material/(:num)', 'Admin\EditMaterialController::index/$1');
$routes->post('/editmaterial/update', 'Admin\EditMaterialController::update');
$routes->post('/materialmasterlist/getListByQuoteType', 'Admin\MaterialMasterlistController::getListByQuoteType');
$routes->post('/materialmasterlist/updateOrder', 'Admin\MaterialMasterlistController::updateOrder');
/*Admin*/

/*User*/
$routes->get('/user/login/', 'User\HomeController::index');
$routes->post('/user/authenticate', 'User\HomeController::authenticate');
$routes->get('/user/logout', 'User\LogoutController::index');
$routes->get('/request-quotation', 'User\RequestQuotationController::index');
$routes->post('/requestquotation/addQuotation', 'User\RequestQuotationController::addQuotation');
$routes->get('/request-quotation-list', 'User\RequestQuotationListController::index');
$routes->post('/requestquotationlist/getData', 'User\RequestQuotationListController::getData');
$routes->get('/process-quotation/(:num)', 'User\ProcessQuotationController::index/$1');
$routes->post('/processquotation/uploadFiles', 'User\ProcessQuotationController::uploadFiles');
$routes->get('/processquotation/getData', 'User\ProcessQuotationController::getData');
$routes->post('/processquotation/uploadSingleFile', 'User\ProcessQuotationController::uploadSingleFile');
$routes->post('/processquotation/deleteItem', 'User\ProcessQuotationController::deleteItem');
$routes->post('/processquotation/submitQuotation', 'User\ProcessQuotationController::submitQuotation');
$routes->post('/requestquotationlist/deleteQuotation/(:num)', 'User\RequestQuotationListController::deleteQuotation/$1');
$routes->post('/requestquotationlist/duplicateQuotation/(:num)', 'User\RequestQuotationListController::duplicateQuotation/$1');
$routes->get('/requestquotationlist/downloadAllFiles/(:num)', 'User\RequestQuotationListController::downloadAllFiles/$1');
$routes->get('/user-info', 'User\UserInfoController::index');
$routes->post('/userinfo/update', 'User\UserInfoController::update');
$routes->post('/quotations/chargeCreditCard', 'User\QuotationsController::chargeCreditCard');
$routes->post('/quotations/chargeEcheck', 'User\QuotationsController::chargeEcheck');
$routes->get('/quotations', 'User\QuotationsController::index');
$routes->get('/quotations/getData', 'User\QuotationsController::getData');
$routes->get('/quotations/quotationDetails', 'User\QuotationsController::quotationDetails');
$routes->delete('/quotations/delete/(:num)', 'User\QuotationsController::deleteQuotation/$1');
$routes->post('/quotations/requestPOApproval', 'User\QuotationsController::requestPOApproval');
/*User*/

$routes->get('/', 'HomeController::index');
$routes->get('/home', 'HomeController::index');
$routes->get('/about-us', 'AboutUsController::index');
$routes->get('/contact-us', 'ContactUsController::index');
$routes->post('/contactus/sendMessage', 'ContactUsController::sendMessage');
$routes->get('/materials-and-surface-finishes', 'MaterialsAndSurfaceFinishesController::index');
$routes->get('/register', 'RegisterController::index');
$routes->post('/register/insert', 'RegisterController::insert');
$routes->post('/subscribers/insert', 'SubscribersController::insert');
$routes->get('/privacy-policy', 'PrivacyPolicyController::index');
$routes->get('/terms-and-conditions', 'TermsAndConditionsController::index');
$routes->get('/product-pricing', 'ProductPricingController::index');
$routes->get('/refund-and-cancellation-policy', 'RefundAndCancellationPolicyController::index');
$routes->get('/forgot-password', 'ForgotPasswordController::index');
$routes->post('/forgotpassword/sendEmail', 'ForgotPasswordController::sendEmail');
$routes->get('/reset-password/(:any)', 'ResetPasswordController::index/$1');
$routes->post('/resetpassword/reset', 'ResetPasswordController::reset');
