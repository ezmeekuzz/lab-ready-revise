<?php

namespace App\Controllers\User;

use App\Controllers\User\SessionController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AuthorizeNet;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use App\Models\QuotationsModel;
use App\Models\QuotationResponsesModel;
use App\Models\UserReceiveQuotationResponsesModel;
use App\Models\UsersModel;

class QuotationsController extends SessionController
{
    public function index()
    {
        $data = [
            'title' => 'Quotations | Lab Ready',
            'currentpage' => 'quotations'
        ];
        return view('user/quotations', $data);
    }
    public function getData()
    {
        $userReceiveQuotationResponsesModel = new UserReceiveQuotationResponsesModel();
        $search = $this->request->getVar('search');
    
        // Get year and month from request, or use the current year and month if not provided
        $year = $this->request->getVar('year');
        $month = $this->request->getVar('month');
    
        // Query to get the quotations joined with related tables
        $quotationList = $userReceiveQuotationResponsesModel
        ->select('user_receive_quotation_responses.*, quotation_responses.*, quotations.*, user_receive_quotation_responses.created_at as quotationdate')
        ->join('quotation_responses', 'quotation_responses.quotation_response_id=user_receive_quotation_responses.quotation_response_id', 'left')
        ->join('quotations', 'quotation_responses.quotation_id=quotations.quotation_id', 'left')
        ->where('user_receive_quotation_responses.user_id', session()->get('user_user_id'));
    
        // Apply search filter if provided
        if ($search) {
            $quotationList = $quotationList->like('quotations.reference_number', $search);
        }
    
        // Apply year and month filter by default to the current year and month
        
        if ($year) {
            $quotationList = $quotationList->where('YEAR(quotation_responses.response_date)', $year);
        }
        
        if ($month) {
            $quotationList = $quotationList->where('MONTH(quotation_responses.response_date)', $month);
        }
    
        // Fetch the filtered quotations
        $quotationList = $quotationList->findAll();
    
        // Return the filtered results as JSON
        return $this->response->setJSON($quotationList);
    }      
    
    public function quotationDetails()
    {
        $quotationReponseId = $this->request->getVar('quotationReponseId');
        
        $quotationResponsesModel = new QuotationResponsesModel();
        $quotationResponseDetails = $quotationResponsesModel
        ->join('quotations', 'quotations.quotation_id=quotation_responses.quotation_id', 'left')
        ->find($quotationReponseId);
        
        return $this->response->setJSON($quotationResponseDetails);
    }    
    public function pay()
    {
        $quotationsModel = new QuotationsModel();
        $usersModel = new UsersModel();
        $quotationResponsesModel = new QuotationResponsesModel();

        $quotationId = $this->request->getPost('quotationId');
        $quotationReponseId = $this->request->getPost('quotationReponseId');

        $updated = $quotationResponsesModel->update($quotationReponseId, ['payment_status' => 'Paid']);

        $quotationResponseDetails = $quotationResponsesModel
        ->join('quotations', 'quotations.quotation_id=quotation_responses.quotation_id', 'left')
        ->find($quotationReponseId);

        $quotationsModel->update($quotationId, ['status' => 'Done']);
    
        if ($updated) {
            $userDetails = $usersModel->find(session()->get('user_user_id'));
            $data = [
                'userDetails' => $userDetails,
                'quotationResponseDetails' => $quotationResponseDetails,
                'phonenumber' => session()->get('user_phonenumber'),
            ];
            $message = view('emails/payment-success', $data);
            // Email sending code
            $pdfFilePath = FCPATH . $quotationResponseDetails['invoice_file_location'];
            $this->adminEmailReceived($data);
            $email = \Config\Services::email();
            $email->setTo($userDetails['email']);
            $email->setSubject('We\'ve got you\'re payment!');
            $email->setMessage($message);
            $email->attach($pdfFilePath, 'attachment', $quotationResponseDetails['invoice_file_name']);
            if ($email->send()) {
                $response = [
                    'success' => true,
                    'message' => 'Successfully Paid!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to send message!',
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Payment Failed!',
            ];
        }
    
        return $this->response->setJSON($response);
    }
    private function adminEmailReceived($data)
    {
        $message = "";
        $reference = $data['quotationResponseDetails']['reference_number'] ?? null; // Check if 'reference' exists
        $productName = $data['quotationResponseDetails']['quotation_name'];

        // Use the reference if it exists and is not empty, otherwise use the product name
        $message .= "An order has been paid with this Quotation Number: " . (!empty($reference) ? $reference : $productName).'<br/>';
        $message .= "<h3>Shipping Address</h3><br/>";
        $message .= "<p>Phone Number : ".$data['phonenumber']."</p>";
        $email = \Config\Services::email();
        //$email->setTo('charlie@lab-ready.net');
        $email->setTo('rustomcodilan@gmail.com');
        $email->setSubject('Quotation Payment');
        $email->setMessage($message);
        $email->send();
    }
    public function deleteQuotation($id)
    {
        $userQuotationsModel = new UserReceiveQuotationResponsesModel();
    
        // Find the users by ID
        $quotations = $userQuotationsModel->find($id);
    
        if ($quotations) {
    
            // Delete the record from the database
            $deleted = $userQuotationsModel->delete($id);
    
            if ($deleted) {
                return $this->response->setJSON(['status' => 'success']);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete the users from the database']);
            }
        }
    
        return $this->response->setJSON(['status' => 'error', 'message' => 'users not found']);
    }
    public function chargeCreditCard()
    {
        helper('form');
    
        $address = $this->request->getPost('address');
        $city = $this->request->getPost('city');
        $state = $this->request->getPost('state');
        $zipcode = $this->request->getPost('zipcode');
        $phoneNumber = $this->request->getPost('phoneNumber');
        $quotationId = $this->request->getPost('quotationId');
        $quotationReponseId = $this->request->getPost('quotationReponseId');
    
        $config = new \Config\AuthorizeNet();
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($config->apiLoginId);
        $merchantAuthentication->setTransactionKey($config->transactionKey);
    
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($this->request->getPost('cardNumber'));
        $creditCard->setExpirationDate($this->request->getPost('expirationDate'));
        $creditCard->setCardCode($this->request->getPost('cvv'));
    
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);
    
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($this->request->getPost('amount'));
        $transactionRequestType->setPayment($paymentOne);
    
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);
    
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($config->sandbox ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    
        $data = [
            'quotationnId' => $quotationId,
            'quotationReponseId' => $quotationReponseId,
        ];
        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
    
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $quotationsModel = new QuotationsModel();
                    $quotationResponsesModel = new QuotationResponsesModel();
                    $usersModel = new UsersModel();
                    $quotationResponseDetails = $quotationResponsesModel
                    ->join('quotations', 'quotations.quotation_id=quotation_responses.quotation_id', 'left')
                    ->find($quotationReponseId);
                    $userDetails = $usersModel->find(session()->get('user_user_id'));
                    $updated = $quotationResponsesModel->where('quotation_response_id', $quotationReponseId)
                        ->set('address', $address)
                        ->set('city', $city)
                        ->set('state', $state)
                        ->set('zipcode', $zipcode)
                        ->set('phonenumber', $phoneNumber)
                        ->set('payment_status', 'Paid')
                        ->update();

                    $data = [
                        'userDetails' => $userDetails,
                        'quotationResponseDetails' => $quotationResponseDetails,
                        'address' => $address,
                        'city' => $city,
                        'state' => $state,
                        'zipcode' => $zipcode,
                        'phonenumber' => $phoneNumber,
                    ];

                    $message = view('emails/payment-success', $data);
                    // Email sending code
                    $pdfFilePath = FCPATH . $quotationResponseDetails['invoice_file_location'];
                    $this->adminEmailReceived($data);
                    $email = \Config\Services::email();
                    $email->setTo($userDetails['email']);
                    $email->setSubject('We\'ve got you\'re payment!');
                    $email->setMessage($message);
                    $email->attach($pdfFilePath, 'attachment', $quotationResponseDetails['invoice_file_name']);
                    if ($email->send()) {
                        $response = [
                            'success' => true,
                            'message' => 'Successfully Paid!',
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Failed to send message!',
                        ];
                    }
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => 'Transaction Successful: ' . $tresponse->getMessages()[0]->getDescription()
                    ]);
                } else {
                    log_message('error', 'Transaction Failed: ' . $tresponse->getErrors()[0]->getErrorText());
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Transaction Failed: ' . $tresponse->getErrors()[0]->getErrorText()
                    ]);
                }
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Transaction Failed: ' . $response->getMessages()->getMessage()[0]->getText()
                ]);
            }
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No response returned'
            ]);
        }
    } 
    public function chargeEcheck()
    {
        helper('form');
    
        $address = $this->request->getPost('address');
        $city = $this->request->getPost('city');
        $state = $this->request->getPost('state');
        $zipcode = $this->request->getPost('zipcode');
        $phoneNumber = $this->request->getPost('phoneNumber');
        $quotationId = $this->request->getPost('quotationId');
    
        // Get API credentials
        $config = new \Config\AuthorizeNet();
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($config->apiLoginId);
        $merchantAuthentication->setTransactionKey($config->transactionKey);
    
        // Log credentials for debugging (ensure sensitive data isn't logged in production)
        log_message('info', 'API Login ID: ' . $config->apiLoginId);
    
        // Create Bank Account
        $bankAccount = new AnetAPI\BankAccountType();
        $bankAccount->setRoutingNumber($this->request->getPost('routingNumber'));
        $bankAccount->setAccountNumber($this->request->getPost('accountNumber'));
        $bankAccount->setNameOnAccount($this->request->getPost('accountHolder')); // Fixed to match field from form
        $bankAccount->setAccountType($this->request->getPost('accountType')); // Checking or Savings from form input
    
        $payment = new AnetAPI\PaymentType();
        $payment->setBankAccount($bankAccount);
    
        // Transaction Request
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($this->request->getPost('amount'));
        $transactionRequestType->setPayment($payment);
    
        // Create Transaction Request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);
    
        // Send request to Authorize.net
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(
            $config->sandbox ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION
        );
    
        // Log the response for debugging
        log_message('info', 'Authorize.net Response: ' . print_r($response, true));
        $data = [
            'quotationnId' => $quotationId,
            'quotationReponseId' => $quotationReponseId,
        ];
        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
    
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $quotationsModel = new QuotationsModel();
                    $quotationResponsesModel = new QuotationResponsesModel();
                    $usersModel = new UsersModel();
                    $quotationResponseDetails = $quotationResponsesModel
                    ->join('quotations', 'quotations.quotation_id=quotation_responses.quotation_id', 'left')
                    ->find($quotationReponseId);
                    $userDetails = $usersModel->find(session()->get('user_user_id'));
                    $updated = $quotationResponsesModel->where('quotation_response_id', $quotationReponseId)
                        ->set('address', $address)
                        ->set('city', $city)
                        ->set('state', $state)
                        ->set('zipcode', $zipcode)
                        ->set('phonenumber', $phoneNumber)
                        ->set('payment_status', 'Paid')
                        ->update();

                    $data = [
                        'userDetails' => $userDetails,
                        'quotationResponseDetails' => $quotationResponseDetails,
                        'address' => $address,
                        'city' => $city,
                        'state' => $state,
                        'zipcode' => $zipcode,
                        'phonenumber' => $phoneNumber,
                    ];

                    $message = view('emails/payment-success', $data);
                    // Email sending code
                    $pdfFilePath = FCPATH . $quotationResponseDetails['invoice_file_location'];
                    $this->adminEmailReceived($data);
                    $email = \Config\Services::email();
                    $email->setTo($userDetails['email']);
                    $email->setSubject('We\'ve got you\'re payment!');
                    $email->setMessage($message);
                    $email->attach($pdfFilePath, 'attachment', $quotationResponseDetails['invoice_file_name']);
                    if ($email->send()) {
                        $response = [
                            'success' => true,
                            'message' => 'Successfully Paid!',
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Failed to send message!',
                        ];
                    }
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => 'Transaction Successful: ' . $tresponse->getMessages()[0]->getDescription()
                    ]);
                } else {
                    log_message('error', 'Transaction Failed: ' . $tresponse->getErrors()[0]->getErrorText());
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Transaction Failed: ' . $tresponse->getErrors()[0]->getErrorText()
                    ]);
                }
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Transaction Failed: ' . $response->getMessages()->getMessage()[0]->getText()
                ]);
            }
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No response returned'
            ]);
        }
    }    
}
