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
use TCPDF;

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
    
        // Create PDF with quotation details
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Lab Ready');
        $pdf->SetAuthor('Charlie');
        $pdf->SetTitle("Quotation {$quotation['reference_number']}");
        $pdf->AddPage();
    
        // Set font and content
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, "Quotation Details: {$quotation['reference_number']}", 0, 1, 'C');
        $pdf->Ln(5);
    
        // Add each field on a new line
        $fields = [
            'Material/Finish Details' => $quotation['other_information'] ?? 'N/A',
            'Quantity to Quote' => $quotation['quantity_to_quote'] ?? 'N/A',
            'Other Relevant Details' => $quotation['relevant_details'] ?? 'N/A'
        ];
    
        foreach ($fields as $label => $value) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 10, $label . ':', 0, 0);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(0, 10, $value, 0, 1);
            $pdf->Ln(2);
        }
    
        $pdfFileName = "quotation_details_{$quotation['reference_number']}.pdf";
        $pdfFilePath = $tempDir . $pdfFileName;
        $pdf->Output($pdfFilePath, 'F');
    
        // Create ZIP archive
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return $this->response->setJSON(['success' => false, 'message' => "Failed to open ZIP file: $zipFilePath"]);
        }
    
        $fileAdded = false;
    
        // Add PDF to ZIP first
        if (file_exists($pdfFilePath)) {
            $zip->addFile($pdfFilePath, "Quotation_Details.pdf");
            $fileAdded = true;
        }
    
        foreach ($items as $item) {
            // Add CAD file if exists
            if (!empty($item['cad_file_location'])) {
                $filePath = FCPATH . $item['cad_file_location'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, "CAD_Files/" . $item['cad_file_name']);
                    $fileAdded = true;
                }
            }
    
            // Add Print file if exists
            if (!empty($item['print_file_location'])) {
                $filePath = FCPATH . $item['print_file_location'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, "Print_Files/" . $item['print_file_name']);
                    $fileAdded = true;
                }
            }
        }
    
        if (!$fileAdded) {
            return $this->response->setJSON(['success' => false, 'message' => "No valid files to add in ZIP."]);
        }
    
        if (!$zip->close()) {
            return $this->response->setJSON(['success' => false, 'message' => "Failed to finalize ZIP file: $zipFilePath"]);
        }
    
        // Clean up PDF file after adding to ZIP
        if (file_exists($pdfFilePath)) {
            unlink($pdfFilePath);
        }
    
        return $this->response->download($zipFilePath, null)->setFileName($zipFileName);
    }
}
