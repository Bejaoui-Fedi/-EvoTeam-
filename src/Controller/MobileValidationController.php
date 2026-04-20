<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Repository\ExerciseRepository;
use App\Service\PredictiveService;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MobileValidationController extends AbstractController
{
    /**
     * Phase 1: Show Exercise Details & Instructions on Mobile
     */
    #[Route('/m/validate/{id}', name: 'app_mobile_validate', methods: ['GET'])]
    public function validate(Exercise $exercise, Request $request): Response
    {
        $userId = $request->query->get('uid');
        $token = $request->query->get('token');
        
        if (!$exercise || !$userId) {
            return $this->render('mobile/validation_result.html.twig', [
                'phase' => 'error',
                'message' => 'Lien invalide ou exercice introuvable.',
                'exercise' => $exercise // Pass even if null to avoid Twig errors
            ]);
        }

        $response = $this->render('mobile/validation_result.html.twig', [
            'phase' => 'preview',
            'exercise' => $exercise,
            'userId' => $userId,
            'token' => $token
        ]);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Phase 2: Actual validation after user clicks "Validate" button on phone
     */
    #[Route('/m/confirm/{id}', name: 'app_mobile_confirm', methods: ['POST'])]
    public function confirm(Exercise $exercise, Request $request, PredictiveService $predictive, EntityManagerInterface $em): Response
    {
        $userId = $request->request->get('uid');
        $token = $request->request->get('token');

        // Security check (token should match)
        $expectedToken = md5($exercise->getId() . $userId . 'evolia_secret');
        if ($token !== $expectedToken) {
            return $this->render('mobile/validation_result.html.twig', [
                'phase' => 'error',
                'message' => 'Token de sécurité invalide.'
            ]);
        }

        $userRepo = $em->getRepository(\App\Entity\User::class);
        $targetUser = $userRepo->find($userId);

        if (!$targetUser) {
            return $this->render('mobile/validation_result.html.twig', [
                'phase' => 'error',
                'message' => 'Utilisateur introuvable.'
            ]);
        }

        $result = $predictive->recordCompletion($targetUser, $exercise);

        $response = $this->render('mobile/validation_result.html.twig', [
            'phase' => 'success',
            'message' => $result['message'],
            'xpGained' => $result['xpGained'],
            'newLevel' => $result['newLevel'],
            'exercise' => $exercise
        ]);

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route('/user/exercise/{id}/qr', name: 'app_user_exercise_qr', methods: ['GET'])]
    public function generateQr(Exercise $exercise, Request $request, QRCodeService $qrService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        // Detect Local IP
        $localIp = '127.0.0.1';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('ipconfig') ?: '';
            // Supports English (IPv4 Address) and French (Adresse IPv4)
            if (preg_match_all('/(?:IPv4 Address|Adresse IPv4).*?: ([\d\.]+)/', $output, $matches)) {
                $foundIps = $matches[1];
                foreach ($foundIps as $ip) {
                    // Exclude loopback and APIPA (169.254.x.x)
                    if ($ip === '127.0.0.1' || str_starts_with($ip, '169.254.')) {
                        continue;
                    }
                    
                    // Filter for private network ranges: 192.168.x.x, 10.x.x.x, 172.16-31.x.x
                    $isPrivate = preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip);
                    
                    if ($isPrivate) {
                        // Favor IPs that don't end in .1 (usually gateways/VMs)
                        if (!str_ends_with($ip, '.1')) {
                            $localIp = $ip;
                            break;
                        }
                        // Fallback to .1 if nothing else found yet
                        if ($localIp === '127.0.0.1') {
                            $localIp = $ip;
                        }
                    }
                }
            }
            if ($localIp === '127.0.0.1' && !empty($foundIps)) {
                $localIp = $foundIps[0];
            }
        } else {
            $output = shell_exec('hostname -I');
            if ($output) {
                $localIp = trim(explode(' ', $output)[0]);
            }
        }

        $port = $request->getPort();
        $host = ($localIp !== '127.0.0.1') ? $localIp . ($port ? ':' . $port : '') : $request->getHttpHost();

        $url = sprintf('http://%s/m/validate/%d?uid=%d&token=%s&t=%d',
            $host,
            $exercise->getId(),
            $user->getId(),
            md5($exercise->getId() . $user->getId() . 'evolia_secret'),
            time() // Unique timestamp to bypass mobile browser cache
        );

        $qrCodeDataUri = $qrService->generateQrCode($url, "Valider: " . $exercise->getTitle());

        return $this->json([
            'qrCode' => $qrCodeDataUri,
            'url' => $url,
            'detectedIp' => $localIp
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
