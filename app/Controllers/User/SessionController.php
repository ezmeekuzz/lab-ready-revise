<?php

namespace App\Controllers\User;

use App\Controllers\BaseController;

class SessionController extends BaseController
{
    protected $session;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do not forget to call the parent's initController method
        parent::initController($request, $response, $logger);

        helper('url');
        $this->session = session();

        if (!$this->session->has('UserLoggedIn')) {
            redirect()->to('/user/login')->send();
            exit;
        }
    }
}
