WITH
-- ═══════════════════════════════════════
-- 5 OBJECTIFS
-- ═══════════════════════════════════════
o1 AS (
    INSERT INTO objective (title, description, createdat, updatedat, level)
    VALUES (
        'Condition physique générale',
        'Développer endurance et force musculaire pour mieux vivre au quotidien. Programme progressif sur 3 mois combinant cardio et musculation.',
        '2026-01-15 08:00:00', '2026-04-15 08:00:00', 'en_cours'
    ) RETURNING id_objective
),
o2 AS (
    INSERT INTO objective (title, description, createdat, updatedat, level)
    VALUES (
        'Gestion du stress et anxiété',
        'Intégrer des pratiques de relaxation et de pleine conscience dans ma routine quotidienne pour réduire le stress et améliorer le bien-être mental.',
        '2026-02-01 09:00:00', '2026-05-01 09:00:00', 'en_cours'
    ) RETURNING id_objective
),
o3 AS (
    INSERT INTO objective (title, description, createdat, updatedat, level)
    VALUES (
        'Perte de poids sainement',
        'Perdre 5 kg grâce à une activité physique régulière et une alimentation équilibrée. Objectif atteint en combinant cardio et musculation sur 3 mois.',
        '2025-10-01 07:00:00', '2026-01-01 07:00:00', 'termine'
    ) RETURNING id_objective
),
o4 AS (
    INSERT INTO objective (title, description, createdat, updatedat, level)
    VALUES (
        'Flexibilité et mobilité articulaire',
        'Améliorer la souplesse et la mobilité articulaire pour prévenir les blessures et réduire les tensions musculaires chroniques.',
        '2026-01-01 10:00:00', '2026-03-01 10:00:00', 'abandonne'
    ) RETURNING id_objective
),
o5 AS (
    INSERT INTO objective (title, description, createdat, updatedat, level)
    VALUES (
        'Méditation et pleine conscience',
        'Développer une pratique quotidienne de méditation pour améliorer la concentration, réduire l''anxiété et cultiver la sérénité mentale.',
        '2026-03-01 06:00:00', '2026-06-01 06:00:00', 'termine'
    ) RETURNING id_objective
),

-- ═══════════════════════════════════════
-- EXERCICES OBJECTIF 1 : Condition physique
-- ═══════════════════════════════════════
e1 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Course à pied matinale',
           'Session de jogging en plein air pour développer l''endurance cardiovasculaire et brûler des calories. Rythme conversationnel maintenu.',
           'cardio', 30, 'facile', '2026-01-20 07:00:00', '2026-01-20 07:00:00', 1, id_objective FROM o1
    RETURNING id_exercise
),
e2 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Musculation haut du corps',
           'Entraînement ciblé sur les bras, épaules et pectoraux avec poids libres et machines. 4 séries de 12 répétitions par exercice.',
           'musculation', 45, 'moyen', '2026-01-22 18:00:00', '2026-01-22 18:00:00', 1, id_objective FROM o1
    RETURNING id_exercise
),
e3 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'HIIT Circuit Training',
           'Entraînement fractionné haute intensité : 20 sec effort / 10 sec repos sur 8 cycles. Burpees, mountain climbers, jumping jacks.',
           'cardio', 25, 'difficile', '2026-01-25 06:30:00', '2026-01-25 06:30:00', 0, id_objective FROM o1
    RETURNING id_exercise
),
e4 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Vélo stationnaire endurance',
           'Session longue sur vélo d''appartement à intensité modérée (65-75% FCmax) pour développer la base aérobie et renforcer les jambes.',
           'cardio', 60, 'moyen', '2026-01-28 17:00:00', '2026-01-28 17:00:00', 1, id_objective FROM o1
    RETURNING id_exercise
),

-- ═══════════════════════════════════════
-- EXERCICES OBJECTIF 2 : Gestion stress
-- ═══════════════════════════════════════
e5 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Yoga du matin doux',
           'Séquence douce de salutations au soleil et postures d''ouverture pour réveiller le corps et apaiser l''esprit en douceur.',
           'yoga', 20, 'facile', '2026-02-05 07:00:00', '2026-02-05 07:00:00', 1, id_objective FROM o2
    RETURNING id_exercise
),
e6 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Méditation guidée anti-stress',
           'Séance de méditation focalisée sur la respiration et le scan corporel pour réduire le cortisol et apaiser le système nerveux.',
           'autre', 15, 'facile', '2026-02-08 12:00:00', '2026-02-08 12:00:00', 1, id_objective FROM o2
    RETURNING id_exercise
),
e7 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Cohérence cardiaque 365',
           'Protocole 365 : 3 fois par jour, 6 respirations par minute, 5 minutes. Inspirer 5 sec / expirer 5 sec pour réguler le SNA.',
           'autre', 10, 'facile', '2026-02-10 08:00:00', '2026-02-10 08:00:00', 0, id_objective FROM o2
    RETURNING id_exercise
),
e8 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Stretching anti-tensions',
           'Programme d''étirements doux ciblant cou, épaules, dos et hanches pour libérer les tensions physiques liées au stress et à la sédentarité.',
           'flexibilite', 30, 'facile', '2026-02-12 20:00:00', '2026-02-12 20:00:00', 1, id_objective FROM o2
    RETURNING id_exercise
),

-- ═══════════════════════════════════════
-- EXERCICES OBJECTIF 3 : Perte de poids
-- ═══════════════════════════════════════
e9 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Natation complète',
           'Crawl et brasse en piscine pour un cardio complet à faible impact articulaire. Idéal pour brûler des calories tout en préservant les articulations.',
           'cardio', 45, 'moyen', '2025-10-10 08:00:00', '2025-10-10 08:00:00', 1, id_objective FROM o3
    RETURNING id_exercise
),
e10 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Marche rapide quotidienne',
           'Marche à allure soutenue de 5-6 km/h pour activer le métabolisme, brûler les graisses et améliorer la santé cardiovasculaire.',
           'cardio', 50, 'facile', '2025-10-15 07:30:00', '2025-10-15 07:30:00', 1, id_objective FROM o3
    RETURNING id_exercise
),
e11 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'CrossFit Full Body',
           'WOD complet : squats goblet, burpees, tractions assistées, kettlebell swings, box jumps. Effort total du corps à haute intensité.',
           'musculation', 40, 'difficile', '2025-10-20 06:00:00', '2025-10-20 06:00:00', 1, id_objective FROM o3
    RETURNING id_exercise
),
e12 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Zumba en groupe',
           'Cours de danse cardio en groupe sur des rythmes latins (salsa, merengue, cumbia) pour brûler des calories en s''amusant collectivement.',
           'sport_collectif', 60, 'moyen', '2025-10-25 19:00:00', '2025-10-25 19:00:00', 0, id_objective FROM o3
    RETURNING id_exercise
),

-- ═══════════════════════════════════════
-- EXERCICES OBJECTIF 4 : Flexibilité
-- ═══════════════════════════════════════
e13 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Pilates Core et Mobilité',
           'Exercices de Pilates ciblant le gainage profond, la mobilité du bassin et la flexibilité de la colonne vertébrale. Travail lent et précis.',
           'flexibilite', 40, 'moyen', '2026-01-05 10:00:00', '2026-01-05 10:00:00', 1, id_objective FROM o4
    RETURNING id_exercise
),
e14 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Yoga des hanches',
           'Séquence dédiée à l''ouverture des hanches : pigeon, fente basse, papillon. Allongement des ischio-jambiers et fléchisseurs de hanche.',
           'yoga', 30, 'facile', '2026-01-08 08:00:00', '2026-01-08 08:00:00', 1, id_objective FROM o4
    RETURNING id_exercise
),
e15 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Étirements dynamiques',
           'Routine d''étirements actifs et ballistiques pour préparer les articulations à l''effort et améliorer progressivement la mobilité fonctionnelle.',
           'flexibilite', 25, 'facile', '2026-01-10 07:00:00', '2026-01-10 07:00:00', 0, id_objective FROM o4
    RETURNING id_exercise
),
e16 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Foam Rolling & Auto-massage',
           'Utilisation du rouleau de massage pour relâcher les fascias et réduire les tensions musculaires profondes. Zones : cuisses, dos, mollets.',
           'flexibilite', 20, 'facile', '2026-01-12 21:00:00', '2026-01-12 21:00:00', 1, id_objective FROM o4
    RETURNING id_exercise
),

-- ═══════════════════════════════════════
-- EXERCICES OBJECTIF 5 : Méditation
-- ═══════════════════════════════════════
e17 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Méditation pleine conscience',
           'Pratique du scan corporel et observation des pensées sans jugement. Développe la conscience du moment présent et réduit le vagabondage mental.',
           'autre', 20, 'facile', '2026-03-05 06:30:00', '2026-03-05 06:30:00', 1, id_objective FROM o5
    RETURNING id_exercise
),
e18 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Yoga Nidra - Sommeil yogique',
           'Pratique de yoga du sommeil conscient pour atteindre un état de relaxation profonde entre veille et sommeil, régénérant corps et esprit.',
           'yoga', 30, 'facile', '2026-03-08 21:00:00', '2026-03-08 21:00:00', 1, id_objective FROM o5
    RETURNING id_exercise
),
e19 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Pranayama - Nadi Shodhana',
           'Respiration alternée par les narines pour équilibrer les hémisphères cérébraux, calmer le mental et harmoniser l''énergie vitale.',
           'autre', 15, 'moyen', '2026-03-10 07:00:00', '2026-03-10 07:00:00', 0, id_objective FROM o5
    RETURNING id_exercise
),
e20 AS (
    INSERT INTO exercise (title, description, type, durationminutes, difficulty, createdat, updatedat, ispublished, objectiveid)
    SELECT 'Marche méditative en nature',
           'Promenade silencieuse en nature avec attention portée sur les sensations corporelles, les sons et l''environnement. Ancrage au moment présent.',
           'autre', 25, 'facile', '2026-03-12 08:00:00', '2026-03-12 08:00:00', 1, id_objective FROM o5
    RETURNING id_exercise
)
SELECT 'Insertion terminée : 5 objectifs et 20 exercices ajoutés avec succès.' AS resultat;
