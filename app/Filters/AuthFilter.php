<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTService;
use Config\Services;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwtService = new JWTService();
        $authHeader = $request->getHeaderLine('Authorization');
        $arr = explode(" ", $authHeader);

        if(!isset($arr[1])){
            return Services::response()->setStatusCode(401, 'Unauthorized');
        }

        $token = $arr[1];

        try {
            $decoded = $jwtService->decode($token);
            $request -> setGlobal('decodedToken', (array)$decoded);
        } catch (\Exception $e) {
            return Services::response()->setStatusCode(401, 'Unauthorized');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
