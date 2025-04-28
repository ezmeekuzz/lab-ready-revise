<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\SessionController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\QuotationsModel;
use App\Models\QuotationResponsesModel;
use App\Models\ItemsModel;
use App\Models\UsersModel;
use App\Models\UserReceiveQuotationResponsesModel;
use ZipArchive;

class RequestQuotationListController extends SessionController
{
    public function index()
    {
        $data = [
            'title' => 'Request Quotation List | Lab Ready',
            'currentpage' => 'requestquotationlist'
        ];
        return view('admin/requestquotationlist', $data);
    }
    public function getData()
    {
        // Get year and month from the POST request
        $year = $this->request->getPost('year');
        $month = $this->request->getPost('month');
    
        // Start the query
        $query = datatables('quotations')
            ->select('quotations.*, users.*, quotations.user_id as uid')
            ->join('users', 'quotations.user_id = users.user_id', 'LEFT JOIN')
            ->where('quotations.status !=', 'Pending');
    
        // Apply year filter if provided
        if ($year) {
            $query = $query->where('YEAR(quotations.created_at)', $year); // Assuming 'created_at' is the date field
        }
    
        // Apply month filter if provided
        if ($month) {
            $query = $query->where('MONTH(quotations.created_at)', $month);
        }
    
        // Return the filtered data
        return $query->make();
    }
    public function insert()
    {
        $quotationsModel = new QuotationsModel();
        $quotationResponsesModel = new QuotationResponsesModel();
        $userReceivedQuotationResponsesModel = new UserReceiveQuotationResponsesModel();
        $price = $this->request->getPost('price');
        $invoiceFile = $this->request->getFile('invoicefile');
        $quotationId = $this->request->getPost('quotationId');
    
        $errors = [];
        
        if (empty($price)) {
            $errors[] = 'Price';
        }
        if (!$invoiceFile->isValid()) {
            $errors[] = 'Invoice File';
        }
    
        // If there are any errors, return them
        if (!empty($errors)) {
            $errorMessage = 'Please fill in the following fields: ' . implode(', ', $errors);
            $response = [
                'success' => false,
                'message' => $errorMessage,
            ];
            return $this->response->setJSON($response);
        }
    
        // Upload invoice file
        $newFileName = $invoiceFile->getRandomName();
        $invoiceFile->move(FCPATH . 'uploads/invoices', $newFileName);
    
        // Prepare data for insertion
        $data = [
            'quotation_id' => $quotationId,
            'price' => $price,
            'invoice_file_location' => '/uploads/invoices/' . $newFileName,
            'invoice_file_name' => $invoiceFile->getClientName(),
            'response_date' => date('Y-m-d'),
            'payment_status' => 'Unpaid'
        ];
    
        // Insert data into database
        $inserted = $quotationResponsesModel->insert($data);
        $UsersModel = new UsersModel();
        $userDetails = $UsersModel->find($this->request->getPost('userId'));
        $quotationsDetails = $quotationsModel->where('quotation_id', $quotationId)->first();
        $data['userDetails'] = $userDetails;
        $data['quotationsDetails'] = $quotationsDetails;
        if ($inserted) {
            $userReceivedQuotationResponsesModel->insert([
                'quotation_response_id' => $inserted,
                'user_id' => $this->request->getPost('userId'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $quotationsModel->update($quotationId, ['status' => 'Done']);
            $message = view('emails/quotation-response', $data);
            // Email sending code
            $email = \Config\Services::email();
            $email->setTo($userDetails['email']);
            $email->setSubject('You\'ve got a response from your quotation!');
            $email->setMessage($message);
            if ($email->send()) {
                $response = [
                    'success' => true,
                    'message' => 'Quotation forwarded successfully!',
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
                'message' => 'Failed to forward quotation.',
            ];
        }
    
        return $this->response->setJSON($response);
    } 
    public function downloadFiles($quotation_id)
    {
        $quotationModel = new QuotationsModel();
        $itemsModel = new ItemsModel();
    
        $quotation = $quotationModel->find($quotation_id);
        if (!$quotation) {
            return $this->response->setJSON(['success' => false, 'message' => 'Quotation not found.']);
        }
    
        $items = $itemsModel->where('quotation_id', $quotation_id)->findAll();
        if (empty($items)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No files found for this quotation.']);
        }
    
        log_message('debug', 'Items retrieved: ' . print_r($items, true));
    
        // Ensure temp directory exists
        $tempDir = FCPATH . 'temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
    
        $zipFileName = "{$quotation['reference_number']}.zip";
        $zipFilePath = realpath($tempDir) . DIRECTORY_SEPARATOR . $zipFileName;
    
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return $this->response->setJSON(['success' => false, 'message' => "Failed to open ZIP file: $zipFilePath"]);
        }
    
        $fileAdded = false;
    
        foreach ($items as $item) {
            // Get file paths directly (no JSON decoding needed)
            $cadFile = $item['cad_file_location'] ?? '';
            $printFile = $item['print_file_location'] ?? '';
    
            log_message('debug', "Processing item ID: " . $item['item_id']);
            log_message('debug', "CAD file: " . $cadFile);
            log_message('debug', "Print file: " . $printFile);
    
            // Add CAD file if exists
            if (!empty($cadFile)) {
                $filePath = FCPATH . $cadFile;
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, "CAD_Files/" . $item['cad_file_name']);
                    $fileAdded = true;
                    log_message('debug', "Added CAD file: " . $filePath);
                } else {
                    log_message('error', "CAD File not found: " . $filePath);
                }
            }
    
            // Add Print file if exists
            if (!empty($printFile)) {
                $filePath = FCPATH . $printFile;
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, "Print_Files/" . $item['print_file_name']);
                    $fileAdded = true;
                    log_message('debug', "Added Print file: " . $filePath);
                } else {
                    log_message('error', "Print File not found: " . $filePath);
                }
            }
        }
    
        if (!$fileAdded) {
            return $this->response->setJSON(['success' => false, 'message' => "No valid files to add in ZIP."]);
        }
    
        if (!$zip->close()) {
            return $this->response->setJSON(['success' => false, 'message' => "Failed to finalize ZIP file: $zipFilePath"]);
        }
    
        return $this->response->download($zipFilePath, null)->setFileName($zipFileName);
    }
}
