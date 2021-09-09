<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Original ID',
            'Scan Station ID',
            'Entry Data',
            'Job ID',
            'Created At',
            'Cartolytics Customer ID',
            'Special Station Key',
            'Submitted_status',
            'Test Instance',
            'Updated At',
            'User ID',
            'Duplicated check random entry',
        ];
    }
    
    
    public function collection()
    {
        return $this->data;
    }
}
