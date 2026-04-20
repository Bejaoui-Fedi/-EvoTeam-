<?php

namespace App\Service;

use App\Entity\Consultation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGenerator
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Génère l'ordonnance médicale en PDF.
     */
    public function generatePrescription(Consultation $consultation, string $patientName, string $doctorName): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->twig->render('pdf/prescription.html.twig', [
            'consultation' => $consultation,
            'patientName' => $patientName,
            'doctorName' => $doctorName,
            'date' => new \DateTime(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
