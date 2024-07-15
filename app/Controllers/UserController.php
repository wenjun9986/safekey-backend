<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use ReflectionException;
use function PHPUnit\Framework\isNull;

class UserController extends BaseController
{
    protected string $format = 'json';
    protected string $modelName = 'App\Models\UserModel';

    public function findUser(): ResponseInterface
    {
        $rules = [
            'email' => 'required|valid_email'
        ];
        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }
        $userModel = new UserModel();
        $data = $userModel->getUserByEmail($input['email']);
        if (is_null($data)) {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
        return $this->success([
            'user_id' => $data['user_id'],
            'email' => $data['email']
        ]);
    }
    public function login(): ResponseInterface
    {
        $rules = [
            'email' => 'required|valid_email'
        ];
        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $userModel = new UserModel();
        $user = $userModel->getUserByEmail($input['email']);
        if (is_null($user)){
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->success([
            'user_id' => $user['user_id'],
            'master_password_hash' => $user['master_password_hash']
        ]);
    }

    public function register(): ResponseInterface
    {
        $rules = [
            'email' => 'required|valid_email',
            'master_password_hash' =>  'required'
        ];
        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }
        $userModel = new userModel();
        $user = $userModel->getUserByEmail($input['email']);
        if (!is_null($user)) {
            return $this->fails(['error' => 'Account Found, Email is Used'], ResponseInterface::HTTP_CONFLICT);
        } else {
            try {
                $user_id = $userModel->registerUser($input);
                return $this->success(['user_id' => $user_id], ResponseInterface::HTTP_CREATED);
            } catch (ReflectionException $e) {
                return $this->fails('User registration failed', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
}
