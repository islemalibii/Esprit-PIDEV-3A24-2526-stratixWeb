# 🚀 Stratix — Web
**Stratix Web** est la version web de la plateforme intelligente Stratix, développée avec le framework **Symfony**. Elle centralise la gestion des ressources humaines en offrant des outils avancés pour les responsables et les employés, avec une intégration d'intelligence artificielle, de cartographie interactive et de notifications automatiques.

---

## 📌 Table des matières
- [Aperçu](#-aperçu)
- [Fonctionnalités](#-fonctionnalités)
- [Technologies utilisées](#-technologies-utilisées)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Structure du projet](#-structure-du-projet)
- [Tests](#-tests)
- [Contribuer](#-contribuer)

---

## 🔍 Aperçu

Stratix Web est une application web **Symfony 6.4** conçue pour rationaliser les opérations RH d'une entreprise en fournissant une interface unifiée pour :

* **Ressources Humaines** : Gestion des employés, congés et authentification.
* **Événements** : Planification, participation et feedback des événements d'entreprise.
* **Projets** : Suivi des projets, tâches et deadlines avec notifications IA.
* **Autres modules** : Gestion centralisée de toutes les ressources de l'entreprise.

L'application exploite les technologies PHP modernes et s'intègre avec diverses APIs pour fournir des recommandations intelligentes, des rapports analytiques et des outils de communication automatisés.

---

## ✨ Fonctionnalités

### 👥 Gestion des Utilisateurs & Authentification
- 🔐 Authentification sécurisée avec gestion des rôles (Admin, Responsable, Employé)
- 🔒 Verrouillage de compte après tentatives échouées
- 📧 Authentification Google OAuth
- 🔑 Double authentification (2FA)
- 🤖 Protection reCAPTCHA

### 📅 Gestion des Événements
- ✏️ Création, modification et archivage des événements
- 🗺️ Localisation interactive avec OpenStreetMap
- 🔁 Événements récurrents (hebdomadaire / mensuel)
- 👥 Système de participation avec confirmation par email
- ⭐ Feedback employés avec notation 1-5 étoiles
- 🤖 Résumé IA des feedbacks (Google Gemini)
- 🤖 Recommandations d'événements personnalisées (Groq/Llama)
- 📄 Export PDF des détails d'événement
- 📊 Export CSV de la liste des participants

### 📋 Gestion des Projets & Tâches
- 📁 Création et suivi des projets
- ✅ Gestion des tâches et affectation aux membres
- 📬 Notifications IA de rappel de deadlines (Groq/Llama)
- 📊 Suivi de l'avancement des projets


---

## 🛠 Technologies utilisées

### Technologies principales
- **PHP 8.2** — Langage de programmation principal
- **Symfony 6.4** — Framework web PHP
- **Doctrine ORM** — Mapping objet-relationnel
- **MySQL** — Base de données relationnelle
- **Twig** — Moteur de templates
- **Bootstrap 5** — Framework CSS

### Bundles Symfony
| Bundle | Usage |
|--------|-------|
| **KnpPaginatorBundle** | Pagination des listes |
| **KnpSnappyBundle** | Génération de PDF |
| **Symfony Mailer** | Envoi d'emails multi-transport |
| **Symfony Scheduler** | Automatisation des rappels |
| **Symfony Security** | Authentification et autorisation |

### APIs externes
| API | Usage | Gratuit |
|-----|-------|---------|
| **Google Gemini API** | Résumé IA des feedbacks | ✅ Free tier |
| **Groq API (Llama 3.3)** | Recommandations & notifications IA | ✅ Free tier |
| **Nominatim (OpenStreetMap)** | Géocodage d'adresses | ✅ Gratuit |
| **OpenStreetMap + Leaflet.js** | Cartes interactives | ✅ Gratuit |
| **Gmail SMTP** | Envoi d'emails | ✅ Gratuit |
| **Google OAuth** | Authentification sociale | ✅ Gratuit |
| **reCAPTCHA** | Protection anti-bot | ✅ Gratuit |

### Outils de qualité
- **PHPUnit** — Tests unitaires
- **PHPStan** — Analyse statique du code
- **Doctrine Doctor** — Audit des entités Doctrine

---

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir installé :

- **PHP 8.2** ou supérieur
- **Composer 2.x**
- **MySQL Server 8.0+**
- **Symfony CLI**
- **wkhtmltopdf** (pour l'export PDF)
- **Git**

---

## ⚙️ Installation

### 1️⃣ Cloner le dépôt
```bash
git clone https://github.com/islemalibii/stratix-web.git
cd stratix-web
```

### 2️⃣ Installer les dépendances
```bash
composer install
```

### 3️⃣ Configurer la base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4️⃣ Lancer le serveur
```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

---

## 🔧 Configuration

Créez un fichier `.env.local` à la racine du projet et configurez vos variables :

```env
# Base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/stratix"

# Mailer (Gmail SMTP) — 3 transports séparés
MAILER_DSN="smtp://email1@gmail.com:app_password@smtp.gmail.com:465?verify_peer=false"
MAILER_DSN_EVENT="smtp://email2@gmail.com:app_password@smtp.gmail.com:465?verify_peer=false"
MAILER_DSN_PROJET="smtp://email3@gmail.com:app_password@smtp.gmail.com:465?verify_peer=false"

# Google Gemini API (résumé IA des feedbacks)
GEMINI_API_KEY="your_gemini_api_key"

# Groq API (recommandations IA et notifications projets)
GROQ_API_KEY="your_groq_api_key"

# Google OAuth
GOOGLE_CLIENT_ID="your_google_client_id"
GOOGLE_CLIENT_SECRET="your_google_client_secret"

# reCAPTCHA
RECAPTCHA_SITE_KEY="your_recaptcha_site_key"
RECAPTCHA_SECRET_KEY="your_recaptcha_secret_key"
```

> ⚠️ Ne jamais committer `.env.local` — il est ignoré par Git par défaut.

### Configuration wkhtmltopdf (Windows)
```yaml
# config/packages/knp_snappy.yaml
knp_snappy:
    pdf:
        binary: '"C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe"'
```

---

## 📁 Structure du projet

```text
src/
├── Command/                # Commandes Symfony (rappels, notifications)
├── Controller/             # Contrôleurs HTTP par module
│   ├── EventController.php
│   ├── ParticipationController.php
│   ├── FeedbackController.php
│   ├── ProjetController.php
│   └── ...
├── Entity/                 # Entités Doctrine
│   ├── Evenement.php
│   ├── Participation.php
│   ├── EventFeedback.php
│   ├── Utilisateur.php
│   ├── Projet.php
│   └── ...
├── Form/                   # Formulaires Symfony
├── Repository/             # Requêtes Doctrine
├── Security/               # Authentification et sécurité
│   └── LoginAuthenticator.php
└── Service/                # Logique métier
    ├── PictureService.php
    ├── ParticipationService.php
    ├── RecurrenceService.php
    ├── ReminderService.php
    ├── MeetingSummaryService.php
    └── RecommendationService.php

templates/
├── admin/                  # Templates back office
└── employee/               # Templates front office

tests/
└── Service/                # Tests unitaires PHPUnit
```

---

## 🧪 Tests

### Lancer tous les tests
```bash
php bin/phpunit
```

### Résultats
```
OK (128 tests, 204 assertions)

---

## ⏰ Commandes disponibles

```bash
# Envoyer les rappels aux participants (24h avant l'événement)
php bin/console app:send-reminders

# Envoyer les notifications IA de deadline des projets
php bin/console app:notify-deadlines

# Vider le cache
php bin/console cache:clear

# Lancer les migrations
php bin/console doctrine:migrations:migrate

# Analyser le code avec PHPStan
php bin/phpstan analyse src

# Lancer les tests unitaires
php bin/phpunit
```

---

## 🤝 Contribuer

Les contributions sont les bienvenues !

1. Fork le projet
2. Créer une branche : `git checkout -b feature/ma-fonctionnalite`
3. Commit vos modifications : `git commit -m "Ajout d'une fonctionnalité"`
4. Push : `git push origin feature/ma-fonctionnalite`
5. Ouvrir une Pull Request

---

## 👥 Équipe

Projet **STRATIX** — ESPRIT School of Engineering 2025-2026
