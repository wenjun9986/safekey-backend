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
    protected $allowedFields    = ['email', 'master_password_hash', 'created_at', 'updated_at'];
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
}
