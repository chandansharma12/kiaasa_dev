<?php
namespace App\Exports;

use App\Invoice;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InvoicesExport implements FromArray,WithDrawings
{
    protected $invoices;

    public function __construct(array $invoices)
    {
        $this->invoices = $invoices;
        
        
    }

    public function array(): array
    {
        return $this->invoices;
    }
    
    public function drawings(){
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('This is my logo');
        $drawing->setPath(public_path('images/login_img.jpg'));
        $drawing->setHeight(20);
        $drawing->setCoordinates('A1');
        
        return [$drawing];
    }
    
}
