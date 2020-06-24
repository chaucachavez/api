<?php

namespace App\Exports;
 
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DataExport implements FromArray, WithHeadings
{	
	protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array 
    {
        return $this->data;
    }    

    public function headings(): array
    {	
    	$columns = array();

    	if (!empty($this->data)) {
    		$columns = array_keys($this->data[0]);
    	}

        return $columns;
    }
}
