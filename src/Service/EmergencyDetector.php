<?php

namespace App\Service;

class EmergencyDetector
{
    private const EMERGENCY_KEYWORDS = [
        "douleur", "intense", "respirer", "respiration", "poitrine", "sang", "saignement",
        "fracture", "accident", "brûlure", "inconscient", "crise", "étouffement",
        "malaise", "grave", "urgent", "immédiat", "fort", "insupportable", "perte",
        "suicide", "suicidaire", "mourir", "finir", "tuer", "panique", "angoisse",
        "hallucination", "voix", "désespoir", "vide", "noir"
    ];

    /**
     * Analyse le motif d'un rendez-vous pour déterminer s'il s'agit d'une urgence.
     */
    public function isUrgent(?string $motif): bool
    {
        if (!$motif || empty(trim($motif))) {
            return false;
        }

        $lowerMotif = mb_strtolower($motif);
        $count = 0;

        foreach (self::EMERGENCY_KEYWORDS as $keyword) {
            if (str_contains($lowerMotif, $keyword)) {
                $count++;
            }
        }

        // On considère que c'est urgent si au moins 2 mots-clés sont présents
        // ou si un mot très fort comme "urgent" ou "grave" est présent.
        $hasStrongKeyword = str_contains($lowerMotif, "urgent") 
            || str_contains($lowerMotif, "grave")
            || str_contains($lowerMotif, "immédiat")
            || str_contains($lowerMotif, "critique")
            || str_contains($lowerMotif, "suicide")
            || str_contains($lowerMotif, "suicidaire");

        return $count >= 2 || $hasStrongKeyword;
    }
}
