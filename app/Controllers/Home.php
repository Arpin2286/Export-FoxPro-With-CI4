<?php

namespace App\Controllers;

use App\Models\UtilityModel;
use Config\Services;
use XBase\TableReader;

ini_set('max_execution_time', 0); 
ini_set('memory_limit','2048M');
class Home extends BaseController
{
    public function index(): string
    {
        return view('index');
    }

    public function upload()
    {
        try {
            $validation = Services::validation();
            $validation->setRules([
                'file_upload' => [
                    'label' => "File Upload",
                    'rules' => [
                        'uploaded[file_upload]',
                        // 'max_size[file_upload, 10240]',
                    ]
                ]
            ]);
            if (!$validation->run($this->request->getPost()))
                return redirect()->back()->with('validation', $validation->getErrors());

            $data = $this->request->getFile('file_upload');

            $table = new TableReader($data->getTempName());
            $columns = $table->getColumns();

            $jml_record = $table->getRecordCount();

            foreach ($columns as $column) {
                $field[strtolower($column->getName())] = [
                    'type' => self::getType($column->getType()),//'VARCHAR',
                    'constraint' => $column->getType() == 'C' ? 100 : '',
                    'null' => true,
                ];
            }

            $tableData['table_name'] = str_replace('.dbf', '', strtolower($data->getName()));
            $tableData['field'] = $field;

            $record = [];
            for ($i = 0; $i < $jml_record; $i++) {
                $record[] = $table->pickRecord($i)->getData();
            }
            $insertData = $record;

            $execute = (new UtilityModel())->uploadFile($tableData, $insertData);

            if ($execute) {
                $this->session->setFlashdata('success', 'Import Berhasil, Silakan Cek Database.');
            } else {
                $this->session->setFlashdata('error', 'Import Gagal.');
            }
            return redirect()->back();

        } catch (\Throwable $th) {
            \log_message('error', __METHOD__ . '|' . $th->getMessage() . '|' . $th->getFile() . '|' . $th->getLine());
            return redirect()->back()->with('error', 'Sistem Mengalami Masalah. 500');
        }
    }

    private function getType($value)
    {
        $result = '';
        switch ($value) {
            case 'C':
                $result = 'VARCHAR';
                break;
            case 'N':
            case 'I':
                $result = 'INTEGER';
                break;
            case 'F':
                $result = 'FLOAT';
                break;
            case 'L':
                $result = 'BOOLEAN';
                break;
            case 'D':
                $result = 'DATE';
                break;
            case 'T':
                $result = 'DATETIME';
                break;
            case 'B':
                $result = 'DOUBLE';
                break;
            default:
                break;
        }
        return $result;
    }

    public function uploadCsv()
    {
        try {
            $validation = Services::validation();
            $validation->setRules([
                'file_upload' => [
                    'label' => "File Upload",
                    'rules' => [
                        'uploaded[file_upload]',
                        // 'max_size[file_upload, 10240]',
                    ]
                ]
            ]);
            if (!$validation->run($this->request->getPost()))
                return redirect()->back()->with('validation', $validation->getErrors());

            $data = $this->request->getFile('file_upload');
            
            $file = fopen($data->getTempName(), 'r');
            $collect_data = [];
            $insertData = [];
            $i = 0;
            while (!feof($file)) {
                if ($i == 0) {
                    $columns = fgetcsv($file);
                } else {
                    $group_data = fgetcsv($file);
                    if($group_data != false){
                        foreach ($group_data as $key => $value) {
                            $collect_data[strtolower($columns[$key])] = $value;
                        }
                        $insertData[] = $collect_data;
                    }
                }
                $i++;
            }
            fclose($file);
            // dd($insertData[0]);
            // dd($insertData);

            foreach ($columns as $column) {
                $field[strtolower($column)] = [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ];
            }
            $tableData['table_name'] = str_replace('.csv', '', strtolower($data->getName()));
            $tableData['field'] = $field;

            $execute = (new UtilityModel())->uploadFile($tableData, $insertData);

            if ($execute) {
                $this->session->setFlashdata('success', 'Import Berhasil, Silakan Cek Database.');
            } else {
                $this->session->setFlashdata('error', 'Import Gagal.');
            }
            return redirect()->back();
        } catch (\Throwable $th) {
            \log_message('error', __METHOD__ . '|' . $th->getMessage() . '|' . $th->getFile() . '|' . $th->getLine());
            return redirect()->back()->with('error', 'Sistem Mengalami Masalah. 500');
        }
    }
}
