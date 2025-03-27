<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\SessionController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\QuotationsModel;
use App\Models\QuotationResponsesModel;
use App\Models\UserReceiveQuotationResponsesModel;
use App\Models\UserQuotationsModel;
use App\Models\ShipmentsModel;

class QuotationMasterlistController extends SessionController
{
    public function index()
    {
        $data = [
            'title' => 'Quotation Masterlist | Lab Ready',
            'currentpage' => 'quotationmasterlist'
        ];
        return view('admin/quotationmasterlist', $data);
    }

    public function getData()
    {
        // Get the year and month from the request
        $year = $this->request->getPost('year') ?: date('Y');   // Default to current year if not provided
        $month = $this->request->getPost('month') ?: date('m'); // Default to current month if not provided
    
        // Start the query
        $query = datatables('quotation_responses')
            ->select('quotations.*, quotation_responses.*, users.*, quotations.user_id as uid')
            ->join('quotations', 'quotation_responses.quotation_id = quotations.quotation_id', 'LEFT JOIN')
            ->join('users', 'quotations.user_id = users.user_id', 'LEFT JOIN')
            ->where('YEAR(quotations.created_at)', $year)     // Filter by the year (default or selected)
            ->where('MONTH(quotations.created_at)', $month);  // Filter by the month (default or selected)
    
        // Return the filtered data
        return $query->make();
    }
    

    public function delete($id)
    {
        $quotationResponsesModel = new QuotationResponsesModel();
        $userReceiveQuotationResponsesModel = new UserReceiveQuotationResponsesModel();
    
        // Find the quotation by ID
        $quotationResponse = $quotationResponsesModel->find($id);
    
        if ($quotationResponse) {
            // Get the filename of the PDF associated with the quotation
            $pdfFilename = $quotationResponse['invoice_file_location'];
    
            // Delete the record from the database
            $userReceiveQuotationResponsesModel->where('quotation_response_id', $id)->delete();
            $deleted = $quotationResponsesModel->delete($id);
    
            if ($deleted) {
                // Delete the PDF file from the server
                $pdfPath = FCPATH . $pdfFilename;
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
    
                return $this->response->setJSON(['status' => 'success']);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete the quotation from the database']);
            }
        }
    
        return $this->response->setJSON(['status' => 'error', 'message' => 'Quotation not found']);
    }

    public function updateStatus($id)
    {
        $quotationResponsesModel = new QuotationResponsesModel();
        $update = $quotationResponsesModel->update(
            $id,
            [
                'payment_status' => 'Paid'
            ]
        );
        if($update) {
            return $this->response->setJSON(['status' => 'success']);
        }
        else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update the quotation from the database']);
        }
    }

    public function updateShipment($id)
    {
        $shipmentsModel = new ShipmentsModel();
        $quotationsModel = new QuotationsModel();
        $requestQuotationsModel = new RequestQuotationModel();
        $requestQuotationsModel = new RequestQuotationModel();

        $data = $this->request->getPost();
        $validation = \Config\Services::validation();

        $validation->setRules([
            'shipment_link' => 'required|valid_url',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(['status' => 'error', 'message' => $validation->getErrors()]);
        }

        $shipmentData = [
            'quotation_id' => $id,
            'shipment_link' => $data['shipment_link'],
            'reference' => $data['reference'],
            'fullname' => $data['fullname'],
        ];

        $existingShipment = $shipmentsModel->where('quotation_id', $id)->first();
        $quotationDetails = $quotationsModel->find($id);

        if ($existingShipment) {
            $update = $shipmentsModel->update($existingShipment['shipment_id'], $shipmentData);
            $requestQuotationsModel
            ->where('quotation_id', $quotationDetails['quotation_id'])
            ->set('status', 'Shipped')->update();
        } else {
            $update = $shipmentsModel->insert($shipmentData);
            $requestQuotationsModel
            ->where('quotation_id', $quotationDetails['quotation_id'])
            ->set('status', 'Shipped')->update();
        }
        $message = view('emails/quote-shipped', $data);
        if ($update) {
            $email = \Config\Services::email();
            $email->setTo($data['email']);
            $email->setSubject('You\'re order has been shipped!');
            $email->setMessage($message);
            if ($email->send()) {
                return $this->response->setJSON(['status' => 'success']);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to send message!',
                ];
            }
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update the shipment details']);
        }
    }

    public function getShipment($id)
    {
        $shipmentsModel = new ShipmentsModel();
        $shipment = $shipmentsModel->where('quotation_id', $id)->first();
    
        if ($shipment) {
            return $this->response->setJSON(['status' => 'success', 'data' => $shipment]);
        } else {
            return $this->response->setJSON(['status' => 'success', 'data' => null]);
        }
    }
}
