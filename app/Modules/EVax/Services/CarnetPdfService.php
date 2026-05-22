<?php
declare(strict_types=1);

namespace App\Modules\EVax\Services;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\VaccinationRecord;
use Dompdf\Dompdf;
use Dompdf\Options;

final class CarnetPdfService
{
    public function __construct(private readonly CarnetQrService $qr) {}

    public function render(User|Dependent $carnet): string
    {
        $records = ($carnet instanceof User
            ? VaccinationRecord::where('patient_id', $carnet->id)
            : VaccinationRecord::where('dependent_id', $carnet->id))
            ->orderBy('administered_at')
            ->with(['vaccine', 'hosto'])
            ->get();

        $qrSvg = $this->qr->verificationQrSvg($carnet);
        $verifyUrl = $this->qr->verificationUrl($carnet);

        $html = view('evax::pdf.carnet', [
            'subject' => $carnet,
            'records' => $records,
            'qrSvg' => $qrSvg,
            'verifyUrl' => $verifyUrl,
            'generatedAt' => now(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
