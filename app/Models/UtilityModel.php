<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class UtilityModel extends Model
{
    public function uploadFile($tableData, $insertData)
    {
        $forge =  Database::forge();
        $check =  $this->db->tableExists($tableData['table_name']);
        if($check){
            $result = true;
        }else{
            $result = false;
        }
        
        if($result){
            // Drop Table kalau tablenya ada
            $forge->dropTable($tableData['table_name']);
        }
        
        // Tambah field id untuk primary key
        $forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                ]
            ]);
            
            // Tambah Primary Key
            $forge->addKey('id', true);
            
            $forge->addField($tableData['field']);
            
            // Membuat tabel dan cek apakah gagal
            if (!$forge->createTable($tableData['table_name'], true, ['ENGINE' => 'InnoDB']))
            return false;
        
        // // Insert Data dan cek apakah datanya masuk
        if(!$this->db->table($tableData['table_name'])->insertBatch($insertData)) return false;

        return true;
    }
}
