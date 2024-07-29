<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\VaultItemModel;
use CodeIgniter\HTTP\ResponseInterface;
use ReflectionException;

class VaultItemController extends BaseController
{
    protected string $format = 'json';
    protected string $modelName = 'App\Models\VaultItemModel';

    public function getVaultList(): ResponseInterface
    {
        $rules = [
            'user_id' => 'required',
        ];
        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }
        $input['user_id'] = (int)$input['user_id'];
        $vaultItemModel = new VaultItemModel();
        $data = $vaultItemModel->getVaultItems($input['user_id']);
        if (is_null($data)) {
            return $this->fails(['error' => 'Vault is Empty'], ResponseInterface::HTTP_NOT_FOUND);
        }
        return $this->success($data);
    }

    public function createVaultItem(): ResponseInterface
    {
        $rules = [
            'user_id' => 'required',
            'type' => 'required',
            'encrypted_data' => 'required',
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $input['user_id'] = (int)$input['user_id'];

        $vaultItemModel = new VaultItemModel();
        try {
            $vault_id = $vaultItemModel->createVaultItem($input);
            return $this->success(['vault_id' => $vault_id], ResponseInterface::HTTP_CREATED);
        } catch (ReflectionException $e) {
            return $this->fails('Vault Creation failed', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateVaultItem(): ResponseInterface
    {
        $rules = [
            'item_id' => 'required',
            'user_id' => 'required',
            'encrypted_data' => 'required',
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $input['item_id'] = (int)$input['item_id'];

        $vaultItemModel = new VaultItemModel();

        $data = $vaultItemModel->getVaultItem($input['item_id']);
        if (!is_null($data)) {
            if ($data['user_id'] !== $input['user_id']) {
                return $this->fails(['error' => 'Unauthorized'], ResponseInterface::HTTP_UNAUTHORIZED);
            } else {
                try {
                    $result = $vaultItemModel->updateVaultItem($input['item_id'], $input['encrypted_data']);
                    return $this->success(['result' => $result]);
                } catch (ReflectionException $e) {
                    return $this->fails(['error' => 'Vault Item Update Failed'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        } else {
            return $this->fails(['error' => 'Vault Item Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    public function deleteVaultItem(): ResponseInterface
    {
        $rules = [
            'item_id' => 'required',
            'user_id' => 'required',
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            $result['data'] = $this->validator->getErrors();
            return $this->fails($result, ResponseInterface::HTTP_BAD_REQUEST);
        }

        $input['item_id'] = (int)$input['item_id'];

        $vaultItemModel = new VaultItemModel();

        $data = $vaultItemModel->getVaultItem($input['item_id']);
        if (!is_null($data)) {
            if ($data['user_id'] !== $input['user_id']) {
                return $this->fails(['error' => 'Unauthorized'], ResponseInterface::HTTP_UNAUTHORIZED);
            } else {
                $result = $vaultItemModel->delete($input['item_id']);
                return $this->success(['result' => $result]);
            }
        } else {
            return $this->fails(['error' => 'Vault Item Not Found'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }
}
