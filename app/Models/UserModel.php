<?php

namespace App\Models;

use CodeIgniter\Model;
use ReflectionException;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['email', 'master_password_hash', 'user_config', 'created_at', 'updated_at'];
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getUserByEmail(string $email): array|null
    {
        $data =  $this->where('email', $email)->first();
        if ($data){
            return $data;
        }else{
            return null;
        }
    }

    public function getUserByID(string $userId): array|null
    {
        $data =  $this->where('user_id', $userId)->first();
        if ($data){
            return $data;
        }else{
            return null;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function registerUser(array $data = []): bool|int|string
    {
        $this->insert([
            'email' => $data['email'],
            'master_password_hash' => $data['master_password_hash'],
            'created_at' => date('Y-m-d H:i:s', time()),
        ]);
        return $this->getInsertID();
    }

    public function getUserConfig(int $userId): array|null
    {
        $user = $this->find($userId);
        return json_decode($user['user_config'], true) ?? [];
    }

    /**
     * @throws ReflectionException
     */
    public function updateUserConfig(int $userId, array $config): bool
    {
        $existingConfig = $this->getUserConfig($userId) ?? [];
        $newConfig = array_merge($existingConfig, $config);
        if (empty($newConfig)) {
            return $this->update($userId, ['user_config' => null]);
        }
        return $this->update($userId, ['user_config' => json_encode($newConfig)]);
    }

    /**
     * @throws ReflectionException
     */
    public function removeConfigKey(int $userId, string $keyToRemove): bool
    {
        $config = $this->getUserConfig($userId);
        if (array_key_exists($keyToRemove, $config)) {
            unset($config[$keyToRemove]);
            return $this->updateUserConfig($userId, $config);
        }
        return false;
    }
}
