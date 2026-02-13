<?php

namespace App\Exports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EsportaClienti implements FromCollection, WithHeadings
{

    public function __construct(protected $builder)
    {

    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->builder->get();
    }

    public function headings(): array
    {
        return ['first_name', 'last_name', 'email'];
    }
}
