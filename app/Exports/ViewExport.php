<?php
namespace App\Exports;

use App\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ViewExport implements FromView
{
    protected $view_name,$view_path;

    public function __construct($view_name,$view_data)
    {
        $this->view_name = $view_name;
        $this->view_data = $view_data;
    }
    
    public function view(): View
    {
        /*return view('admin.invoices', [
            'invoices' => $this->invoices
        ]);*/
        
        return view($this->view_name, $this->view_data);
    }

}
