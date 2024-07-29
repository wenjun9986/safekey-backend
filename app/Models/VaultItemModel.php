<?php

namespace App\Models;

use CodeIgniter\Model;
use ReflectionException;

class VaultItemModel extends Model
{
    protected $table            = 'vault_items';
    protected $primaryKey       = 'item_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'type', 'encrypted_data', 'created_at', 'updated_at'];
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $afterInsert = ['afterInsert'];
    protected $afterUpdate = ['afterUpdate'];

    protected array $insertedData = [];
    protected array $updatedData = [];

    public function getVaultItems(int $user_id): array|null
    {
        $data = $this->where('user_id', $user_id)->findAll();
        if ($data) {
            return $data;
        } else {
            return null;
        }
    }

    public function getVaultItem(int $item_id): array|null
    {
        $data = $this->find($item_id);
        if ($data) {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function createVaultItem(array $data = []): bool|int|string
    {
        $this->insert([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'encrypted_data' => $data['encrypted_data'],
            'created_at' => date('Y-m-d H:i:s', time()),
        ]);
        return $this->getInsertID();
    }

    /**
     * @throws ReflectionException
     */
    public function updateVaultItem(int $item_id, string $data): bool
    {
        return $this->set(['encrypted_data' => $data])->where('item_id', $item_id)->update();
    }

    public function deleteVaultItem(int $item_id): bool
    {
        return $this->where('item_id', $item_id)->delete();
    }

    protected function afterInsert(array $data): void
    {
        $this->insertedData = array_merge([$this->primaryKey => $data['id']], $data['data']);
    }

    protected function afterUpdate(array $data): void
    {
        $this->updatedData = $data['data'];
    }
}
