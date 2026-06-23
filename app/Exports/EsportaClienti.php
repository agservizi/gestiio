<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EsportaClienti implements FromCollection, WithHeadings
{
    public function __construct(protected $builder) {}

    /**
     * @return Collection
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
