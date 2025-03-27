<?php

namespace App\Controllers\User;

use App\Controllers\User\SessionController;
use App\Models\QuotationsModel;
use App\Models\ItemsModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Email\Email;

class ProcessQuotationController extends SessionController
{
    use ResponseTrait;

    public function index($id)
    {
        session()->set('quotation_id', $id);
        $data = [
            'title' => 'Process Quotation | Lab Ready',
            'currentpage' => 'requestquotation',
        ];
        return view('user/processquotation', $data);
    }
    public function uploadFiles()
    {
        $files = $this->request->getFiles();
        $uploadPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'quotation-files';
        $itemsModel = new ItemsModel();
        $quotationsModel = new QuotationsModel();
        $userId = session()->get('user_user_id');
        $quotationId = session()->get('quotation_id');

        if (!$quotationId) {
            return $this->response->setJSON(['error' => 'Quotation ID not found in session.']);
        }

        $quotation = $quotationsModel->where('quotation_id', $quotationId)
                ->where('user_id', $userId)
                ->where('status', 'Pending')
                ->first();

        if (!$quotation) {
            return $this->response->setJSON(['error' => 'Invalid quotation ID or access denied.']);
        }

        $response = [
            'success' => 'Files uploaded successfully.',
            'files' => [],
            'conversion_errors' => []
        ];

        $insertedIds = [];

        foreach ($files['files'] as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $originalName = $file->getName();
                $extension = strtoupper(pathinfo($originalName, PATHINFO_EXTENSION));
                $newName = bin2hex(random_bytes(8)) . '.' . $extension;

                $file->move($uploadPath, $newName);
                $stlFilePath = null;

                // Convert supported file formats to STL
                if (in_array($extension, ['STEP', 'IGS', 'STL'])) {
                    try {
                        $stlFilePath = $this->convertToSTL($uploadPath . DIRECTORY_SEPARATOR . $newName);
                    } catch (\Exception $e) {
                        log_message('error', "Conversion failed for {$originalName}: " . $e->getMessage());
                        $response['conversion_errors'][] = "Error converting {$originalName}.";
                    }
                }
                
                // Store file details in database
                $fileData = [
                    'quotation_id' => $quotationId,
                    'cad_file_name' => $originalName,
                    'cad_file_location' => 'uploads/quotation-files/' . $newName,
                    'stl_file_location' => $stlFilePath ? 'uploads/quotation-files/' . basename($stlFilePath) : null,
                ];
                $itemsModel->insert($fileData);
            } else {
                log_message('error', "File upload error: " . $file->getErrorString());
            }
        }
        
        return $this->response->setJSON($response);
    }

    private function convertToSTL($filePath)
    {
        $outputPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'quotation-files';
        $outputFile = $outputPath . DIRECTORY_SEPARATOR . bin2hex(random_bytes(8)) . '.stl';
        $freecadCmd = 'C:\\Program Files\\FreeCAD 0.21\\bin\\FreeCADCmd.exe';
        
        if (!file_exists($freecadCmd)) {
            throw new \RuntimeException("FreeCADCmd.exe not found at {$freecadCmd}");
        }
        
        $command = sprintf(
            "\"%s\" -c \"import FreeCAD as App; import Part, MeshPart; doc = App.newDocument(); obj = doc.addObject('Part::Feature'); obj.Shape = Part.read('%s'); doc.recompute(); mesh_obj = MeshPart.meshFromShape(Shape=obj.Shape, LinearDeflection=0.1, AngularDeflection=0.5); mesh_obj.write('%s');\"",
            $freecadCmd,
            str_replace('\\', '\\\\', $filePath),
            str_replace('\\', '\\\\', $outputFile)
        );        
        
        log_message('info', "Executing FreeCAD command: {$command}");
        $output = shell_exec($command . ' 2>&1');
        log_message('info', "FreeCAD output: {$output}");
        
        if (!file_exists($outputFile)) {
            log_message('error', "Conversion failed for {$filePath}: {$output}");
            throw new \RuntimeException("Failed to convert file. Command output: {$output}");
        }
        
        return $outputFile;
    }
    public function getData()
    {
        return datatables('items')
        ->join('quotations', 'quotations.quotation_id=items.quotation_id', 'left')
        ->where('quotations.user_id', session()->get('user_user_id'))
        ->where('quotations.quotation_id', session()->get('quotation_id'))
        ->where('quotations.status', 'Pending')
        ->make();
    }
    public function uploadSingleFile()
    {
        $itemModel = new ItemsModel();
        
        $file = $this->request->getFile('file');
        $itemId = $this->request->getPost('item_id');
    
        if (!$file || !$itemId) {
            return $this->response->setJSON(['error' => 'File or item ID missing.']);
        }
    
        // Fetch the existing item
        $item = $itemModel->find($itemId);
        if (!$item) {
            return $this->response->setJSON(['error' => 'Item not found.']);
        }
    
        // Check if an existing print file needs to be deleted
        if (!empty($item['print_file_location'])) {
            $existingFilePath = FCPATH . $item['print_file_location'];
            if (file_exists($existingFilePath)) {
                unlink($existingFilePath);
            }
        }
    
        if ($file->isValid() && !$file->hasMoved()) {
            $uploadPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'print-files';
            $originalName = $file->getName();
            $extension = strtoupper(pathinfo($originalName, PATHINFO_EXTENSION));
            $newName = bin2hex(random_bytes(8)) . '.' . $extension;
    
            $file->move($uploadPath, $newName);
    
            // Store file details in database
            $fileData = [
                'print_file_name' => $originalName,
                'print_file_location' => 'uploads/print-files/' . $newName
            ];
            $itemModel->update($itemId, $fileData);
    
            return $this->response->setJSON(['success' => 'File uploaded successfully.']);
        } else {
            return $this->response->setJSON(['error' => 'Invalid file or upload error.']);
        }
    }    
    public function deleteItem()
    {
        $itemModel = new ItemsModel();
        $itemId = $this->request->getPost('item_id');
        
        if (!$itemId) {
            return $this->response->setJSON(['error' => 'Item ID is missing.']);
        }
        
        $item = $itemModel->find($itemId);
        if (!$item) {
            return $this->response->setJSON(['error' => 'Item not found.']);
        }
        
        // File paths
        $cadFilePath = FCPATH . $item['cad_file_location'];
        $stlFilePath = !empty($item['stl_file_location']) ? FCPATH . $item['stl_file_location'] : null;
        $printFilePath = !empty($item['print_file_location']) ? FCPATH . $item['print_file_location'] : null;
        
        // Delete files if they exist
        if (file_exists($cadFilePath)) {
            unlink($cadFilePath);
        }
        if ($stlFilePath && file_exists($stlFilePath)) {
            unlink($stlFilePath);
        }
        if ($printFilePath && file_exists($printFilePath)) {
            unlink($printFilePath);
        }
        
        // Delete the item from database
        $itemModel->delete($itemId);
        
        return $this->response->setJSON(['success' => 'Item deleted successfully.']);
    }    
    public function submitQuotation()
    {
        $request = service('request');
    
        // Validate input
        $additionalInfo = trim($request->getPost('additional_info'));
        $quotationId = session()->get('quotation_id');
    
        if (empty($additionalInfo)) {
            return $this->response->setJSON(['error' => 'Additional information cannot be empty.']);
        }
    
        // Load Quotation Model
        $quotationModel = new QuotationsModel();
        
        // Retrieve quotation details
        $quotation = $quotationModel->find($quotationId);
        if (!$quotation) {
            return $this->response->setJSON(['error' => 'Quotation not found.']);
        }
    
        // Update quotation with additional info
        $data = [
            'other_information' => $additionalInfo,
            'status' => 'Submitted'
        ];
    
        if ($quotationModel->update($quotationId, $data)) {
            // Send Email Notifications
            $this->sendUserEmail($quotation, $additionalInfo);
            $this->sendAdminEmail($quotation, $additionalInfo);
    
            return $this->response->setJSON(['success' => 'Quotation submitted successfully!']);
        } else {
            return $this->response->setJSON(['error' => 'Failed to submit quotation.']);
        }
    }
    private function sendUserEmail($quotation, $additionalInfo)
    {
        $emailService = service('email');
        $userEmail = session()->get('user_email'); // Replace with actual column name

        $subject = "Quotation Submission Confirmation";
        $message = view('emails/thank-you', ['quotation' => $quotation, 'additionalInfo' => $additionalInfo]);

        $emailService->setTo($userEmail)
            ->setFrom("testing@braveegg.com", "Lab Ready")
            ->setSubject($subject)
            ->setMessage($message)
            ->setMailType('html');

        if (!$emailService->send()) {
            log_message('error', "Failed to send user email: " . $emailService->printDebugger(['headers']));
        }
    }

    private function sendAdminEmail($quotation, $additionalInfo)
    {
        $emailService = service('email');
        $adminEmail = "rustomcodilan@gmail.com"; // Replace this with admin email

        $subject = "New Quotation Submitted";
        $message = view('emails/admin-quote-received', ['quotation' => $quotation, 'additionalInfo' => $additionalInfo]);

        $emailService->setTo($adminEmail)
            ->setFrom("testing@braveegg.com", "Lab Ready")
            ->setSubject($subject)
            ->setMessage($message)
            ->setMailType('html');

        if (!$emailService->send()) {
            log_message('error', "Failed to send admin email: " . $emailService->printDebugger(['headers']));
        }
    }
}
