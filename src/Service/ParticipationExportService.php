<?php

namespace App\Service;

use App\Entity\Participation;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipationExportService
{
    /**
     * @param Participation[] $participations
     */
    public function createParticipantsXlsxResponse(array $participations, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($tempFile);

        $headerStyle = (new Style())->setFontBold();
        $writer->addRow(Row::fromValues(['Nom', 'Email', 'Telephone', 'Statut', 'Date inscription', 'Evenement'], $headerStyle));

        foreach ($participations as $participation) {
            $user = $participation->getUser();
            $event = $participation->getEvent();
            $writer->addRow(Row::fromValues([
                $user?->getNom() ?? '',
                $user?->getEmail() ?? '',
                $user?->getTelephone() ?? '',
                $participation->getStatus(),
                $participation->getCreatedAt()?->format('Y-m-d H:i') ?? '',
                $event?->getName() ?? '',
            ]));
        }

        $writer->close();

        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($tempFile);
        $response->setContentDisposition(\Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
