<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipationQrService
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function generateEventQrSvg(string $signedToken): string
    {
        $path = $this->urlGenerator->generate('app_evenement_public', ['token' => $signedToken]);
        // FORCAGE DE L'IP LOCALE + index.php pour que le serveur PHP interne trouve la route
        $url = 'http://192.168.1.14:8000/index.php' . $path;

        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getString();
    }
}
