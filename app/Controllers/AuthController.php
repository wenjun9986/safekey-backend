<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\JWTService;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Publisher;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use ReflectionException;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends BaseController
{
    protected string $format = 'json';
    private JWTService $JWTService;

    public function __construct()
    {
        parent::__construct();
        $this->JWTService = new JWTService();
    }

    public function validateToken(): ResponseInterface
    {
        $rules = [
            'token' => 'required'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $key = env('JWT_SECRET_KEY');
        try {
            JWT::decode($input['token'], new Key($key, 'HS256'));
            return $this->success(['message' => 'Token is valid'], ResponseInterface::HTTP_OK);
        } catch (ReflectionException $e) {
            return $this->fails('Invalid Token', ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
    */
    public function generate2FASecret(): ResponseInterface
    {
        $rules = [
            'email' => 'required'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $google2fa = new Google2FA();
        $userModel = new UserModel();

        $user = $userModel->getUserByEmail($input['email']);
        if ($user){
            $secret = $google2fa->generateSecretKey();
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'SafeKey',
                $input['email'],
                $secret
            );
            return $this->success([
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl
            ]);
        } else {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    public function get2FADetails(): ResponseInterface
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
        $google2fa = new Google2FA();

        $user = $userModel->getUserByID($input['user_id']);
        if ($user) {
            $config = $userModel->getUserConfig($input['user_id']);
            $secret = $config['2fa_secret'] ?? '';
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'SafeKey',
                $user['email'],
                $secret
            );
            return $this->success([
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl
            ]);
        } else {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }

    }

    /**
     * @return ResponseInterface
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws ReflectionException
     * @throws SecretKeyTooShortException
     */
    public function enable2FA(): ResponseInterface
    {
        $rules = [
            'user_id' => 'required',
            'otp' => 'required',
            'secret' => 'required'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($input['secret'], $input['otp'])) {
            $userModel = new UserModel();
            $user = $userModel->getUserByID($input['user_id']);
            if ($user){
                $userModel->updateUserConfig($input['user_id'], ['2fa_secret' => $input['secret']]);
                return $this->success(['message' => '2FA Enabled'], ResponseInterface::HTTP_OK);
            } else {
                return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
            }
        } else {
            return $this->fails(['error' => 'Invalid OTP'], ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @return ResponseInterface
     * @throws ReflectionException
     */
    public function disable2FA(): ResponseInterface
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
            $userModel->removeConfigKey($input['user_id'], '2fa_secret');
            return $this->success(['message' => '2FA Disabled'], ResponseInterface::HTTP_OK);
        } else {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function verify2FAToken(): ResponseInterface
    {
        $rules = [
            'user_id' => 'required',
            'otp' => 'required',
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $userModel = new UserModel();
        $google2fa = new Google2FA();

        $user = $userModel->getUserByID($input['user_id']);
        if ($user) {
            $config = $userModel->getUserConfig($input['user_id']);
            $secret = $config['2fa_secret'] ?? '';
            if ($google2fa->verifyKey($secret, $input['otp'])) {
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
                return $this->fails(['error' => 'Invalid OTP'], ResponseInterface::HTTP_UNAUTHORIZED);
            }
        } else {
            return $this->fails(['error' => 'User Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }
}
