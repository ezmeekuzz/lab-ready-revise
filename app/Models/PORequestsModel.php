<?php

namespace App\Models;

use CodeIgniter\Model;

class PORequestsModel extends Model
{
    protected $table = 'po_requests';
    protected $primaryKey = 'request_id';
    protected $allowedFields = ['user_id', 'notes', 'request_date'];
    protected $useTimestamps = false;
    
    protected $beforeInsert = ['setRequestDate'];
    
    protected function setRequestDate(array $data)
    {
        $data['data']['request_date'] = date('Y-m-d H:i:s');
        return $data;
    }
}