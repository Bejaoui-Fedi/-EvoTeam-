<?php

namespace App\Command;

use App\Service\AiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-ai',
    description: 'Teste la connectivité et la logique de l\'IA Groq',
)]
class TestAiCommand extends Command
{
    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du service Elite AI (Groq)');

        // TEST 1: URGENCE POSITIVE
        $io->section('Test 1: Détection d\'une urgence critique');
        $motifUrgent = "J'ai des douleurs thoraciques extrêmement fortes, j'ai du mal à respirer et je fais un malaise.";
        $io->text("Motif: $motifUrgent");
        $isUrgent = $this->aiService->analyzeUrgency($motifUrgent);
        
        if ($isUrgent) {
            $io->success('Succès: L\'urgence a été détectée correctement.');
        } else {
            $io->error('Échec: L\'urgence n\'a pas été détectée.');
        }

        // TEST 2: CAS NORMAL
        $io->section('Test 2: Cas de routine');
        $motifNormal = "Je souhaiterais un simple contrôle annuel pour ma vue, rien de pressant.";
        $io->text("Motif: $motifNormal");
        $isUrgentNormal = $this->aiService->analyzeUrgency($motifNormal);
        
        if (!$isUrgentNormal) {
            $io->success('Succès: Le cas a été classé comme normal.');
        } else {
            $io->warning('Attention: Le cas a été marqué comme urgent alors qu\'il semble normal.');
        }

        // TEST 3: SYNTHÈSE
        $io->section('Test 3: Génération de synthèse');
        $diag = "Infection respiratoire mineure, légère fièvre, poumons clairs.";
        $trait = "Repos, paracétamol 1g 3x/jour pendant 5 jours, hydratation abondante.";
        $io->text("Diag: $diag");
        $io->text("Trait: $trait");
        
        $summary = $this->aiService->generateSummary($diag, $trait);
        $io->note('Synthèse générée :');
        $io->writeln($summary);

        $io->success('Tests terminés.');

        return Command::SUCCESS;
    }
}
