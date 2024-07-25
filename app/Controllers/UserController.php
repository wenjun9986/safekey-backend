<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\JWTService;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use ReflectionException;

class UserController extends BaseController
{
    protected string $format = 'json';
    protected string $modelName = 'App\Models\UserModel';
    private JWTService $JWTService;

    public function __construct()
    {
        parent::__construct();
        $this->JWTService = new JWTService();
    }

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
            'email' => 'required|valid_email',
            'master_password_hash' => 'required'
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

        if ($user && $input['master_password_hash'] === $user['master_password_hash']) {
            $config = $userModel->getUserConfig($user['user_id']);

            if (isset($config['2fa_secret'])) {
                return $this->success([
                    "message" => "2FA Enabled",
                    "user_id" => $user['user_id']
                ]);
            }
            $expirationSec = $config['expiration'] ?? 900;
            $JWTData = [
                'user_id' => $user['user_id'],
                'email' => $user['email'],
            ];
            $token = $this->JWTService->encode($JWTData, $expirationSec);
            return $this->success([
                'JWTToken' => $token,
                'user_id' => $user['user_id'],
            ]);
        } else {
            return $this->fails(['error' => 'Invalid Password'], ResponseInterface::HTTP_UNAUTHORIZED);
        }
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

    public function getUserConfigs(): ResponseInterface
    {
        $rules = [
            'user_id' => 'required'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $userModel = new UserModel();
        $user = $userModel->getUserByID($input['user_id']);
        if ($user) {
            $config = $userModel->getUserConfig($input['user_id']);
            return $this->success([
                '2FA' => isset($config['2fa_secret']) ? 'Enabled' : 'Disabled',
                'expiration' => $config['expiration'] ?? '900',
            ]);
        } else {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function updateVaultTimeout(): ResponseInterface
    {
        $rules = [
            'user_id' => 'required',
            'expiration' => 'required'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $userModel = new UserModel();
        $user = $userModel->getUserByID($input['user_id']);
        if ($user) {
            $userModel->updateUserConfig($input['user_id'], ['expiration' => $input['expiration']]);
            return $this->success(['message' => 'Expiration Updated'], ResponseInterface::HTTP_OK);
        } else {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }


}
