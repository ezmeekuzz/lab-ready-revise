<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthorizeNet extends BaseConfig
{
    public $apiLoginId = '7835VbrypQgt';
    //public $apiLoginId = '9uP56t2qEz';
    public $transactionKey = '347q9x9X4n7N6Xz5';
    //public $transactionKey = '6B6u3dR674HVKhg6';
    public $sandbox = true; // Set to false for live environment
}
