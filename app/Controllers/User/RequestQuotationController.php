<?php

namespace App\Controllers\User;

use App\Controllers\User\SessionController;
use App\Models\QuotationsModel;
use CodeIgniter\API\ResponseTrait;

class RequestQuotationController extends SessionController
{
    use ResponseTrait;

    public function index()
    {
        $data = [
            'title' => 'Request Quotation | Lab Ready',
            'currentpage' => 'requestquotation'
        ];
        return view('user/requestquotation', $data);
    }

    public function addQuotation()
    {
        $quotationsModel = new QuotationsModel();
        
        $quotationName = trim($this->request->getPost('quotation_name'));
        $userId = session()->get('user_user_id');
    
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User session not found.'
            ]);
        }
    
        if (empty($quotationName)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Quotation name is required.'
            ]);
        }
    
        // Check if quotation name already exists for the user
        $existingQuotation = $quotationsModel->where('user_id', $userId)
                                             ->where('quotation_name', $quotationName)
                                             ->first();
        if ($existingQuotation) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Quotation name is already taken.'
            ]);
        }
    
        // Generate reference number (YYYYMMDD-XXX)
        $today = date('Ymd');
        $lastQuotation = $quotationsModel->where('reference_number LIKE', "$today%")
                                         ->orderBy('reference_number', 'DESC')
                                         ->first();
    
        $newNumber = $lastQuotation ? str_pad(((int)substr($lastQuotation['reference_number'], -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $referenceNumber = "$today-$newNumber";
    
        $data = [
            'user_id' => $userId,
            'quotation_name' => $quotationName,
            'reference_number' => $referenceNumber,
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    
        $quotationId = $quotationsModel->insert($data);
    
        if ($quotationId) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Quotation added successfully!',
                'redirect_url' => base_url("/process-quotation/$quotationId") // Redirect URL
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to add quotation.'
            ]);
        }
    }
       
}
