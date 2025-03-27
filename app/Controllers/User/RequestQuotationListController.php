<?php

namespace App\Controllers\User;

use App\Controllers\User\SessionController;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\QuotationsModel;
use App\Models\ItemsModel;
use ZipArchive;

class RequestQuotationListController extends SessionController
{
    public function index()
    {
        $data = [
            'title' => 'Request Quotation List | Lab Ready',
            'currentpage' => 'requestquotationlist'
        ];
        return view('user/requestquotationlist', $data);
    }

    public function getData()
    {
        // Get month and year from the request
        $month = $this->request->getPost('month');
        $year = $this->request->getPost('year');
    
        // Get the current month and year if the filters are not provided
        $currentYear = date('Y');
        $currentMonth = date('m');
    
        // Start your datatable query
        $query = datatables('quotations')
            ->where('status !=', 'Ongoing')
            ->where('user_id', session()->get('user_user_id'));
    
        // If month and year are not provided, filter by the current month and year
        if (!$year && !$month) {
            $query = $query->where('YEAR(created_at)', $currentYear)
                           ->where('MONTH(created_at)', $currentMonth);
        }
        // If only year is provided, filter by the year
        elseif ($year && !$month) {
            $query = $query->where('YEAR(created_at)', $year);
        }
        // If only month is provided, filter by the current year and the provided month
        elseif ($month && !$year) {
            $query = $query->where('YEAR(created_at)', $currentYear)
                           ->where('MONTH(created_at)', $month);
        }
        // If both month and year are provided, filter by them
        elseif ($month && $year) {
            $query = $query->where('YEAR(created_at)', $year)
                           ->where('MONTH(created_at)', $month);
        }
    
        // Return the filtered data
        return $query->make();
    }
    public function deleteQuotation($id)
    {
        $quotationsModel = new QuotationsModel();
        $quotationItemsModel = new ItemsModel();
    
        // Check if the quotation exists
        $quotation = $quotationsModel->find($id);
        if (!$quotation) {
            return $this->response->setJSON(['success' => false, 'message' => 'Quotation not found']);
        }
    
        // Get all items related to this quotation
        $items = $quotationItemsModel->where('quotation_id', $id)->findAll();
    
        foreach ($items as $item) {
            // Remove file locations if they exist
            $updateData = [];
            if (!empty($item['cad_file_location'])) {
                @unlink(FCPATH . $item['cad_file_location']);
            }
            if (!empty($item['stl_file_location'])) {
                @unlink(FCPATH . $item['stl_file_location']);
            }
            if (!empty($item['print_file_location'])) {
                @unlink(FCPATH . $item['print_file_location']);
            }
        }
    
        // Delete all related items
        $quotationItemsModel->where('quotation_id', $id)->delete();
    
        // Delete the quotation
        $quotationsModel->delete($id);
    
        return $this->response->setJSON(['success' => true, 'message' => 'Quotation deleted successfully']);
    }    
    public function duplicateQuotation($id)
    {
        $quotationsModel = new QuotationsModel();
        $itemsModel = new ItemsModel();
        $quotation = $quotationsModel->find($id);
    
        if (!$quotation) {
            return $this->response->setJSON(['success' => false, 'message' => 'Quotation not found.']);
        }
    
        // Get the new quotation name from the request
        $newName = $this->request->getPost('new_name');
    
        // Generate reference number (YYYYMMDD-XXX)
        $today = date('Ymd');
        $lastQuotation = $quotationsModel->where('reference_number LIKE', "$today%")
                                        ->orderBy('reference_number', 'DESC')
                                        ->first();
    
        $newNumber = $lastQuotation ? str_pad(((int)substr($lastQuotation['reference_number'], -3)) + 1, 3, '0', STR_PAD_LEFT) : '001';
        $referenceNumber = "$today-$newNumber";
    
        // Prepare new quotation data
        $newQuotationData = [
            'user_id'         => session()->get('user_user_id'),
            'quotation_name'  => $newName,
            'reference_number'  => $referenceNumber,
            'status'          => 'Pending',  // Reset status to Pending
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ];
    
        // Insert new quotation
        $newQuotationId = $quotationsModel->insert($newQuotationData);
    
        if ($newQuotationId) {
            // Fetch all items from the original quotation
            $originalItems = $itemsModel->where('quotation_id', $id)->findAll();
    
            if (!empty($originalItems)) {
                foreach ($originalItems as $item) {
                    unset($item['item_id']); // Remove original item ID to avoid conflicts
                    $item['quotation_id'] = $newQuotationId; // Assign new quotation ID
    
                    // Duplicate the files and rename them
                    $item['cad_file_location'] = $this->duplicateFile($item['cad_file_location']);
                    $item['stl_file_location'] = $this->duplicateFile($item['stl_file_location']);
                    $item['print_file_location'] = $this->duplicateFile($item['print_file_location']);
    
                    // Insert duplicated item
                    $itemsModel->insert($item);
                }
            }
    
            return $this->response->setJSON(['success' => true, 'message' => 'Quotation duplicated successfully!']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to duplicate quotation.']);
        }
    }
    
    /**
     * Function to duplicate a file and return the new file path
     */
    private function duplicateFile($filePath)
    {
        if (!$filePath || !file_exists(FCPATH . $filePath)) {
            return null; // Return null if file does not exist
        }
    
        $pathInfo = pathinfo($filePath);
        $newFileName = $pathInfo['filename'] . '_' . time() . '.' . $pathInfo['extension'];
        $newFilePath = $pathInfo['dirname'] . '/' . $newFileName;
    
        // Copy the file to the new location
        if (copy(FCPATH . $filePath, FCPATH . $newFilePath)) {
            return $newFilePath; // Return new file path if copy is successful
        } else {
            return null; // Return null if copying fails
        }
    }    
    public function downloadAllFiles($quotation_id)
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
    
        $zipFileName = "quotation_{$quotation_id}_files.zip";
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
