<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\SessionController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\QuotationsModel;
use App\Models\QuotationResponsesModel;
use App\Models\UserReceiveQuotationResponsesModel;
use App\Models\UsersModel;

class SendQuotationController extends SessionController
{
    public function index()
    {
        $usersModel = new UsersModel();
        $userList = $usersModel->where('usertype', 'Regular User')->findAll();
        $data = [
            'title' => 'Send Quotation | Lab Ready',
            'currentpage' => 'sendquotation',
            'userList' => $userList
        ];
        return view('admin/sendquotation', $data);
    }
    public function insert()
    {
        // Initialize models
        $quotationsModel = new QuotationsModel();
        $quotationResponsesModel = new QuotationResponsesModel();
        $userReceiveQuotationResponsesModel = new UserReceiveQuotationResponsesModel();
        $usersModel = new UsersModel();
    
        // Get form inputs
        $quotationName = $this->request->getPost('quotation_name');
        $price = $this->request->getPost('price');
        $otherInformation = $this->request->getPost('other_information');
        $userId = $this->request->getPost('userId');
        $invoiceFile = $this->request->getFile('invoicefile');
    
        // Validation: Check for missing fields
        $errors = [];
        if (empty($quotationName)) {
            $errors[] = 'Quotation Name';
        }
        if (empty($price)) {
            $errors[] = 'Price';
        }
        if (!$invoiceFile || !$invoiceFile->isValid()) {
            $errors[] = 'Invoice File';
        }
    
        // If validation fails, return error response
        if (!empty($errors)) {
            $response = [
                'success' => false,
                'message' => 'Please fill in the following fields: ' . implode(', ', $errors),
            ];
            return $this->response->setJSON($response);
        }

        // Generate reference number (YYYYMMDD-XXX)
        $today = date('Ymd');
        $lastQuotation = $quotationsModel->where('reference_number LIKE', "$today%")
                                         ->orderBy('reference_number', 'DESC')
                                         ->first();
    
        $newNumber = $lastQuotation ? str_pad(((int)substr($lastQuotation['reference_number'], -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $referenceNumber = "$today-$newNumber";    

        // Insert request quotation
        $dataRequestQuotation = [
            'quotation_name' => $quotationName,
            'reference_number' => $referenceNumber,
            'other_information' => $otherInformation,
            'user_id' => $userId,
            'status' => 'Done',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $requestSubmitted = $quotationsModel->insert($dataRequestQuotation);
    
        if (!$requestSubmitted) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create request quotation.',
            ]);
        }
    
        // Upload invoice file
        $newFileName = $invoiceFile->getRandomName();
        $invoiceFile->move(FCPATH . 'uploads/invoices', $newFileName);
    
        // Prepare data for quotation insertion
        $dataQuotation = [
            'quotation_id' => $requestSubmitted,
            'price' => $price,
            'invoice_file_location' => '/uploads/invoices/' . $newFileName,
            'invoice_file_name' => $invoiceFile->getClientName(),
            'response_date' => date('Y-m-d'),
            'payment_status' => 'Unpaid',
        ];
        $inserted = $quotationResponsesModel->insert($dataQuotation);
    
        if (!$inserted) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save quotation details.',
            ]);
        }
    
        // Get user details for email
        $userDetails = $usersModel->find($userId);
        if ($userDetails) {
            $dataEmail = [
                'fullname' => $userDetails['fullname'],
                'reference' => $referenceNumber,
                'quotation' => $dataRequestQuotation
            ];
            $thankYouMessage = view('emails/thank-you', $dataEmail);
    
            // Send email
            $email = \Config\Services::email();
            $email->setTo($userDetails['email']);
            $email->setSubject('Thank you for your quotation request!');
            $email->setMessage($thankYouMessage);
            $email->setMailType('html');
    
            if (!$email->send()) {
                log_message('error', 'Failed to send thank-you email to: ' . $userDetails['email']);
            }
        }
    
        // Insert user quotations
        $userReceiveQuotationResponsesModel->insert([
            'user_id' => $userId,
            'quotation_response_id' => $inserted,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    
        // Success response
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Quotation forwarded successfully!',
        ]);
    }
}
