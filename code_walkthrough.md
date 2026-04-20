# 📚 Guide d'Explication Détaillée du Code

Ce document décortique les fichiers les plus importants ligne par ligne. C'est ce que vous devez être prêt à expliquer lors de votre présentation.

---

## 🤖 1. Focus : `src/Service/AiService.php`

Ce service est le cerveau "IA" du projet. Il gère la communication avec l'API Groq (Modèle Llama 3).

### Les variables et le Constructeur (lignes 8-25)
- **`$apiKey` & `$apiUrl`** : Elles ne sont pas écrites "en dur" pour la sécurité. Elles sont injectées depuis le fichier `.env` via le fichier de configuration `services.yaml`.
- **`HttpClientInterface`** : C'est l'outil de Symfony pour faire des requêtes HTTP (comme on le ferait avec Postman) vers une API externe.

### Méthode `analyzeUrgency(string $motif)` (lignes 30-67)
C'est ici qu'on décide si un rendez-vous est urgent.
- **Ligne 43 (`'model' => 'llama-3.3-70b-versatile'`)** : On définit le modèle d'IA utilisé (très puissant et rapide).
- **Ligne 46 (`'role' => 'system'`)** : C'est le "prompt" de base. On lui donne une personnalité d'expert médical et une règle stricte : **répondre UNIQUEMENT par "URGENT" ou "NORMAL"**. C'est crucial pour que le code PHP puisse traiter la réponse facilement.
- **Ligne 54 (`'temperature' => 0.1`)** : On met une valeur basse pour que l'IA soit très factuelle et ne "fantasme" pas ses réponses.
- **Ligne 65 (`$this->fallbackUrgencyCheck($motif)`)** : **Point majeur pour l'évaluation**. Si l'API Groq est en panne ou n'a plus de crédit, le code ne plante pas. Il utilise une fonction "roue de secours" qui cherche des mots-clés (sang, douleur, etc.) manuellement.

### Méthode `fallbackUrgencyCheck` (lignes 150-158)
- C'est une vérification par mots-clés simples. C'est ce qu'on appelle de la **résilience logicielle**.

---

## 🛡️ 2. Focus : `src/Validator/NoOverlapValidator.php`

Ce fichier empêche qu'un médecin ou un patient ait deux rendez-vous en même temps. C'est un **Custom Validator**.

### Fonctionnement Global
Contrairement à une simple annotation `NotBlank`, ce validateur interroge la base de données.

### Logique de Validation (lignes 34-63)
- **Ligne 34 (`findOverlapping($value)`)** : On appelle une méthode personnalisée du Repository qui cherche s'il existe déjà un rendez-vous dans la même tranche horaire (généralement 30 min).
- **Ligne 39 (Règle Patient/Pro identique)** : Si le même patient essaie de reprendre le même jour avec le même pro, on bloque.
- **Lignes 49-52 (Médecin occupé)** : On vérifie si le médecin a déjà quelqu'un. Si oui, on ajoute une erreur (`addViolation`) spécifiquement sur le champ `heureRdv`.
- **Lignes 57-60 (Patient occupé)** : On vérifie si le patient n'a pas un autre rendez-vous ailleurs à la même heure.

### Pourquoi c'est intelligent ?
On utilise le **contexte de validation** de Symfony (`$this->context`). Cela permet d'afficher le message d'erreur exactement sous le bon champ dans le formulaire Twig, rendant l'expérience utilisateur (UX) bien meilleure.

---

## 🚀 3. Focus : `src/Command/SendConfirmationsCommand.php`

Ce fichier permet d'envoyer des emails automatiques 2 jours avant le rendez-vous.

### Logique de Temps (lignes 39-43)
- On utilise `DateTime`. On prend "maintenant" (`$now`) et on ajoute 2 jours (`modify('+2 days')`).
- On compare ensuite cette date avec celle des rendez-vous en base qui ont le statut "En attente".

### Le Token de Sécurité (lignes 59-60)
- **Ligne 60 (`md5(...)`)** : On crée un jeton unique basé sur l'ID du rendez-vous, le secret de l'application et la date.
- **Pourquoi ?** Cela permet au patient de cliquer sur un lien dans l'email pour confirmer son rendez-vous **sans avoir besoin de se connecter**. C'est ce qu'on appelle du "Passwordless confirmation".

---

## 🏥 4. Focus : `src/Controller/PatientWorkspaceController.php`

C'est ici que les patients interagissent avec le système. Le code contient deux logiques métier fortes.

### Le Système "Anti-Lapin" (lignes 52-61)
- **Ligne 53 (`count([... 'statut' => 'Absent'])`)** : Avant même d'afficher le formulaire, on interroge la base de données pour compter combien de fois ce patient a eu le statut "Absent".
- **Ligne 58 (`if ($missedCount >= 3)`)** : C'est la règle métier. Si le patient a 3 absences ou plus, on **bloque** l'accès au formulaire et on le redirige avec un message Flash d'erreur.
- **Pourquoi ?** Pour protéger le temps des praticiens et réduire les pertes financières.

### La Création de RDV & Triage IA (lignes 70-93)
- **Ligne 74 (`$aiService->analyzeUrgency(...)`)** : On appelle notre service d'IA juste après la validation du formulaire.
- **Ligne 75 (`$appointment->setIsUrgent($isUrgent)`)** : Le résultat de l'IA (vrai ou faux) est enregistré directement dans l'entité.
- **Lignes 87-92 (Alerte Urgence)** : Si l'IA a détecté une urgence, le code déclenche immédiatement un email **ET** un SMS d'alerte au professionnel de santé.

---

## 🎨 5. Focus : `src/Form/AppointmentType.php`

Ce fichier gère la création du formulaire de rendez-vous. Il n'est pas "statique" car il s'adapte à la base de données.

### Le Constructeur (lignes 18-21)
- On injecte `UserRepository` pour pouvoir aller chercher les médecins en base de données directement dans le formulaire.

### La Construction dynamique (lignes 25-48)
- **Ligne 25 (`createQueryBuilder`)** : On fait une requête pour ne récupérer que les utilisateurs ayant les rôles de "Professionnel" (Psychologue, Coach, etc.).
- **Lignes 32-47 (Boucle de formatage)** : On transforme les données brutes de la base en un tableau de choix lisible. 
  - *Exemple* : On transforme un objet User en une chaîne `"Dr. Dupont (Psychologue)"`. C'est ce qu'on appelle la préparation des **Choices**.

---

## 🔑 6. Focus : La Gestion des Sessions & Sécurité

C'est une question classique : "Comment avez-vous fait pour que l'utilisateur reste connecté ?"

### Pas de `$_SESSION` manuel !
Dans ce projet, on n'utilise pas le PHP "à l'ancienne" avec `session_start()`. On utilise le **Composant Security de Symfony**, qui est beaucoup plus sûr.

### Le fichier `config/packages/security.yaml` (lignes 35-50)
- **Le Firewall (`main`)** : C'est comme un garde à l'entrée du site. Il surveille toutes les URLs.
- **`lazy: true`** : La session n'est créée que si on en a vraiment besoin, ce qui booste les performances.
- **`form_login`** : Symfony gère tout seul la vérification de l'email et du mot de passe. Vous lui donnez juste la route de login (`app_user_login`), et il s'occupe du reste.
- **`remember_me`** : On a activé un jeton qui permet à l'utilisateur de rester connecté même s'il ferme son navigateur (pendant 1 semaine).

### Comment le code sait qui est connecté ? 
Dans n'importe quel Controller (ex: `PatientWorkspaceController`), on utilise :
```php
$user = $this->getUser();
```
- **Ligne 25** : Symfony va chercher l'ID de l'utilisateur stocké dans le cookie de session et reconstruit l'objet `User` depuis la base de données automatiquement.

### Les "Sessions de Consultation" (Rendez-vous)
Si l'évaluateur parle des sessions de travail (RDV) :
- On a ajouté un champ `typeRdv` (Présentiel / En ligne).
- Pour les sessions **En ligne**, on pourrait générer un lien unique. Pour l'instant, le système prépare simplement le terrain en marquant le rendez-vous comme "En ligne".

---

**Conseil pour l'oral sur les sessions :**
> "Nous avons choisi d'utiliser le système de sécurité natif de Symfony. Cela nous offre une protection contre les attaques CSRF (via `enable_csrf: true`) et une gestion robuste des sessions via des cookies sécurisés, sans avoir à réinventer la roue et risquer des failles de sécurité."
