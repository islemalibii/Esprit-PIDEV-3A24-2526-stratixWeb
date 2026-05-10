# 🚀 Stratix — Web
**Stratix Web** est la version web de la plateforme intelligente Stratix, développée avec le framework **Symfony**. Elle centralise la gestion des ressources humaines, des événements, des projets, des services et des stocks en offrant des outils avancés avec une intégration poussée de l'intelligence artificielle.

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

Stratix Web est une application web **Symfony 6.4** conçue pour rationaliser les opérations d'une entreprise en fournissant une interface unifiée pour :
 
* **Utilisateurs** : Authentification avancée, gestion des rôles et profils employés.
* **Événements** : Planification, participation, feedback et recommandations IA.
* **Projets & Tâches** : Suivi des projets avec notifications IA de deadlines.
* **Produits & Ressources** : Gestion du stock, alertes automatiques et analyse prédictive.
* **Services & Catégories** : Organisation des services avec assistant IA et visioconférence.
* **Plannings** : Gestion des plannings employés avec météo et statistiques IA.

---

## ✨ Fonctionnalités

### 👥 Gestion des Utilisateurs & Authentification
- 🔐 Connexion par email/mot de passe avec protection **reCAPTCHA v3**
- 🌐 Connexion via **Google OAuth**
- 📝 Inscription avec validation complète (nom, prénom, email, CIN, mot de passe fort)
- 🤳 Détection de visage à l'upload de photo de profil
- 🔁 Réinitialisation de mot de passe par email
- 🔒 Verrouillage automatique après 5 tentatives échouées (30 min)
- 🔑 Hashage sécurisé SHA-256+Base64 (compatible JavaFX)
- 👤 CRUD utilisateurs avec avatar, rôles et filtres avancés
- 📊 Dashboard admin avec statistiques (total, actifs, verrouillés, répartition par rôle)
- 🎭 Détection d'émotion à la connexion (happy, sad, angry…)
- 📋 Organigramme hiérarchique interactif
- 🏷️ Badge utilisateur imprimable + QR code par utilisateur
- 🌙 Thème clair/sombre par utilisateur
- 🔔 Notifications admin (nouveaux comptes, comptes verrouillés, modifications récentes)


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

### 📦 Gestion des Produits & Ressources
**Produits**
- 🗂️ Catalogue dynamique avec cartes interactives et prévisualisation d'images
- 📉 Suivi intelligent du stock (stock actuel vs stock minimum)
- ⚠️ Alertes automatiques : stocks faibles, produits périmés, garanties expirées
- 📅 Traçabilité complète (dates de fabrication, péremption, garantie)
- 💰 Calcul en temps réel de la valeur totale du stock (Prix × Quantité)
- 🔍 Filtres avancés et tri multicritère (nom, catégorie, prix, stock)
- 📄 Export PDF et Excel des rapports d'inventaire
- 🤖 Assistant virtuel **Groq** (chatbot sur le stock en langage naturel)
- 🤖 Système de recommandation intelligent basé sur l'état des ressources
- 🐍 Analyse prédictive (**module Python**) pour anticiper les réapprovisionnements


### 🛎️ Gestion des Services & Catégories
- ✏️ Création, modification, archivage et suppression des services
- 🗂️ Catégorisation des services
- 🔍 Recherche multicritères en temps réel (**AJAX**) par mot-clé, catégorie, budget, dates
- 📄 Export PDF et Excel de la liste des services
- 📱 QR code par service (accès rapide sur mobile)
- 🤖 Assistant IA intégré (**Groq**) pour répondre aux questions sur les services
- 📊 Insights IA (analyse automatique des budgets, catégories, échéances)
- 📹 Visioconférence intégrée (**Jitsi Meet**)
- 💱 Convertisseur de devises (TND → USD/EUR)
- 🆕 Badge "Nouveau" avec effet visuel pour les nouveaux services
- 👁️ Quick view modal (aperçu rapide au clic)

### 📆 Gestion des Plannings Employés
- ✏️ Création, modification et suppression des plannings
- 👤 Affectation des employés aux plannings
- 🔍 Recherche multicritères en temps réel (**AJAX**) par employé, date, type de shift
- 📄 Export PDF et Excel des plannings
- 🌤️ Météo intégrée pour chaque planning (**WeatherAPI**)
- 🤖 Assistant IA intégré (**Groq**) pour répondre aux questions sur les plannings
- 📊 Statistiques IA (analyse automatique des présences, absences, types de shift)
- 📅 Vue calendrier interactive mensuelle avec code couleur
- 🏷️ Badges colorés par statut de tâche (Terminé)
- 👁️ Quick view des plannings du jour (clic sur cellule calendrier)


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
| **Google Gemini API** | Résumé IA des feedbacks événements | ✅ Free tier |
| **Groq API (Llama 3.3)** | Recommandations, chatbot, notifications IA | ✅ Free tier |
| **Nominatim (OpenStreetMap)** | Géocodage d'adresses | ✅ Gratuit |
| **OpenStreetMap + Leaflet.js** | Cartes interactives | ✅ Gratuit |
| **Gmail SMTP** | Envoi d'emails | ✅ Gratuit |
| **Google OAuth** | Authentification sociale | ✅ Gratuit |
| **reCAPTCHA v3** | Protection anti-bot | ✅ Gratuit |
| **WeatherAPI** | Météo intégrée aux plannings | ✅ Free tier |
| **Jitsi Meet** | Visioconférence intégrée | ✅ Gratuit |

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
- **Python 3.x** (pour le module d'analyse prédictive)
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
 
# Mailer (Gmail SMTP) — 3 transports séparés par module
MAILER_DSN="smtp://email1@gmail.com:app_password@smtp.gmail.com:465?verify_peer=false"
MAILER_DSN_EVENT="smtp://email2@gmail.com:app_password@smtp.gmail.com:465?verify_peer=false"
MAILER_DSN_PROJET="smtp://email3@gmail.com:app_password@smtp.gmail.com:465?verify_peer=false"
 
# Google Gemini API (résumé IA des feedbacks)
GEMINI_API_KEY="your_gemini_api_key"
 
# Groq API (recommandations, chatbot, notifications IA)
GROQ_API_KEY="your_groq_api_key"
 
# Google OAuth
GOOGLE_CLIENT_ID="your_google_client_id"
GOOGLE_CLIENT_SECRET="your_google_client_secret"
 
# reCAPTCHA v3
RECAPTCHA_SITE_KEY="your_recaptcha_site_key"
RECAPTCHA_SECRET_KEY="your_recaptcha_secret_key"
 
# WeatherAPI (plannings)
WEATHER_API_KEY="your_weather_api_key"
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
│   ├── ProduitController.php
│   ├── RessourceController.php
│   ├── ServiceController.php
│   ├── PlanningController.php
│   └── ...
├── Entity/                 # Entités Doctrine
│   ├── Evenement.php
│   ├── Participation.php
│   ├── EventFeedback.php
│   ├── Utilisateur.php
│   ├── Projet.php
│   ├── Produit.php
│   ├── Ressource.php
│   ├── Service.php
│   ├── Planning.php
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
