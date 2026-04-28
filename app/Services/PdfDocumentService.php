<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfDocumentService
{
    public function render(string $view, array $data = [], string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data));
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }

    public function contractPath(string $loanGuid): string
    {
        return WRITEPATH . 'uploads/contracts/' . $loanGuid . '-contrato.pdf';
    }

    public function saveContract(string $loanGuid, string $pdf): void
    {
        $directory = dirname($this->contractPath($loanGuid));
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->contractPath($loanGuid), $pdf);
    }
}
