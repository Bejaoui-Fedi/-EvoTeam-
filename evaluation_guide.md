# 🎓 Guide de Préparation à l'Évaluation du Projet Evolia

Ce guide est conçu pour vous aider à maîtriser votre code sur le bout des doigts. Il explique chaque partie du projet, des bases de Symfony aux fonctionnalités intelligentes (IA, Automatisation).

---

## 🏛️ 1. Architecture Symfony : Les Fondamentaux

Pour comprendre le projet, il faut comprendre comment Symfony organise le code.

### 📁 Structure des Dossiers
- **`src/`** : C'est le cœur du projet. Tout votre code PHP (Controllers, Entities, Services) est ici.
- **`config/`** : Contient la configuration du projet (Routes, Services, Bundles).
- **`templates/`** : Contient les fichiers Twig (HTML amélioré) pour l'affichage.
- **`public/`** : Le point d'entrée du serveur (index.php) et les fichiers statiques (images, CSS).

### 🛣️ Le Routing (Les Routes)
Le routing est ce qui lie une **URL** à une **action** dans un Controller.
- *Exemple* : L'URL `/patient/workspace` est liée à la méthode `index()` dans `PatientWorkspaceController.php` via l'annotation `#[Route('/patient/workspace')]`.

### 💾 Les Entities (Le Modèle de Données)
Les entities représentent vos tables en base de données. Nous utilisons **Doctrine** (un ORM) pour manipuler des objets PHP au lieu d'écrire du SQL.
- **`User.php`** : Gère les utilisateurs (ID, Nom, Email, Password, Role). Notez qu'il implémente `UserInterface` pour la sécurité Symfony.
- **`Appointment.php`** : Représente un rendez-vous. Il contient la date, l'heure, le motif, le statut, et lie un patient à un professionnel.
- **`Consultation.php`** : Dossier médical créé après un rendez-vous, contenant le diagnostic et le traitement.

---

## 📊 2. Gestion des Données & Formulaires

### 🔎 Les Repositories
Un Repository est une classe qui contient des méthodes pour **chercher** des données en base.
- *Exemple* : `AppointmentRepository::findBy(['userId' => $id])` permet de récupérer tous les rendez-vous d'un patient spécifique.

### 📝 Les Formulaires (`src/Form`)
Symfony utilise des classes de formulaires pour gérer la saisie et la validation.
- **`AppointmentType.php`** : 
    - Génère dynamiquement la liste des spécialistes disponibles.
    - Ajoute des classes CSS personnalisées (`elite-input`).
    - Gère les types de données (Date, Time, Choix).

---

## 🎨 3. L'Interface Utilisateur (Twig)

### 🏗️ Héritage de Templates
- **`base.html.twig`** : C'est le "squelette" du site. Il définit les blocs (`{% block body %}`, `{% block stylesheets %}`) qui seront remplis par les autres pages.
- **`index.html.twig`** : Utilise `{% extends 'base.html.twig' %}` pour hériter du squelette et ne définir que le contenu spécifique à la page.

### 🧩 Composants Dynamiques
Nous avons intégré des bibliothèques comme **FullCalendar** (via `ProCalendarBundle`) pour afficher les rendez-vous de manière visuelle dans le workspace professionnel.

---

## ⚙️ 4. La Logique Métier (Les Services)

Les Services sont des classes "réutilisables" qui contiennent la logique complexe du projet.

- **`AiService.php`** : 
    - Utilise l'API **Groq (Llama 3)**.
    - Fonction `analyzeUrgency` : Analyse le motif pour détecter si c'est une urgence.
    - Fonction `generateSummary` : Crée un résumé automatique d'une consultation.
- **`NotificationService.php`** :
    - Envoie des emails via Mailer.
    - Envoie des SMS via **Twilio**.
    - Centralise tous les messages (confirmation, alertes urgence).
- **`PdfGenerator.php`** :
    - Utilise `KnpSnappyBundle` ou `Dompdf` pour transformer un template HTML en fichier PDF (Ordonnances).

---

## 🤖 5. Automatisation (Les Commands Console)

Ce sont des scripts que le serveur peut exécuter automatiquement (via des Tâches Cron).
- **`SendConfirmationsCommand.php`** : Scanne les rendez-vous à J-2 et envoie un email de confirmation "Smart Confirm".
- **`AutoCloseConsultationsCommand.php`** : Clôture les vieux rendez-vous (48h après la date) pour garder la base de données propre.

---

## 💎 6. Le Système Evolia Elite (Logique Avancée)

### 🐇 Système Anti-Lapin
Dans `PatientWorkspaceController`, nous vérifions le nombre de fois qu'un patient a été marqué "Absent". Si ce nombre est supérieur à 3, la prise de rendez-vous est **bloquée**.

### 📅 Gestion des Congés
Un professionnel peut déclarer une période de congés. Le système :
1. Annule automatiquement tous les rendez-vous déjà prévus sur cette période.
2. Crée des entrées "Congé" en base pour bloquer ces créneaux.

---

## ❓ Questions Types de l'Evaluateur

**1. Comment l'IA est-elle intégrée ?**
> "Elle est isolée dans un service (`AiService`). On appelle l'API Groq avec des prompts spécifiques (Expert Médical) pour obtenir des réponses structurées (URGENT/NORMAL)."

**2. Comment gérez-vous les conflits d'horaires ?**
> "Via une vérification dans le Controller (`getAvailability`) qui croise les rendez-vous du professionnel, ceux du patient, et les périodes de congés."

**3. Pourquoi utiliser des Commandes Console ?**
> "Pour automatiser les tâches répétitives (relances, nettoyage) sans intervention humaine, ce qui rend le système autonome."
