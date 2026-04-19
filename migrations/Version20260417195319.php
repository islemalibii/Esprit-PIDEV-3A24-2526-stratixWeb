<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417195319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_log (id INT AUTO_INCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, sender_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE evenement_ressource DROP FOREIGN KEY `evenement_ressource_ibfk_1`');
        $this->addSql('ALTER TABLE evenement_ressource DROP FOREIGN KEY `evenement_ressource_ibfk_2`');
        $this->addSql('DROP TABLE evenement_ressource');
        $this->addSql('ALTER TABLE categorie_service CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE date_creation date_creation DATE DEFAULT NULL, CHANGE archive archive TINYINT DEFAULT NULL');
        $this->addSql('ALTER TABLE evenement ADD latitude NUMERIC(10, 7) DEFAULT NULL, ADD longitude NUMERIC(10, 7) DEFAULT NULL, CHANGE type_event type_event VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE lieu lieu VARCHAR(255) DEFAULT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE isArchived isArchived TINYINT DEFAULT NULL');
        //$this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY `event_feedback_ibfk_1`');
        //$this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY `event_feedback_ibfk_1`');
        $this->addSql('ALTER TABLE event_feedback CHANGE evenement_id evenement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT FK_94C5AD88FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('DROP INDEX evenement_id ON event_feedback');
        $this->addSql('CREATE INDEX IDX_94C5AD88FD02F13 ON event_feedback (evenement_id)');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT `event_feedback_ibfk_1` FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX email ON fournisseur');
        $this->addSql('ALTER TABLE fournisseur ADD telephone VARCHAR(255) DEFAULT NULL, DROP rating');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY `fk_fournisseur`');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY `fk_ressource`');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY `fk_fournisseur`');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY `fk_ressource`');
        $this->addSql('ALTER TABLE offre CHANGE prix prix DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('DROP INDEX fk_fournisseur ON offre');
        $this->addSql('CREATE INDEX IDX_AF86866F670C757F ON offre (fournisseur_id)');
        $this->addSql('DROP INDEX fk_ressource ON offre');
        $this->addSql('CREATE INDEX IDX_AF86866FFC6CD52A ON offre (ressource_id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT `fk_fournisseur` FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT `fk_ressource` FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX employe_id ON planning');
        $this->addSql('ALTER TABLE planning CHANGE type_shift type_shift VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_garantie ON produit');
        $this->addSql('DROP INDEX idx_peremption ON produit');
        $this->addSql('ALTER TABLE produit CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE categorie categorie VARCHAR(255) DEFAULT NULL, CHANGE ressources_necessaires ressources_necessaires LONGTEXT DEFAULT NULL, CHANGE details details LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE projet DROP equipe_membres, DROP progression, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE budget budget DOUBLE PRECISION NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE responsable_id responsable_id INT NOT NULL, CHANGE is_archived is_archived TINYINT NOT NULL');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA953C59D72 FOREIGN KEY (responsable_id) REFERENCES utilisateur (id)');
        $this->addSql('DROP INDEX fk_projet_utilisateur ON projet');
        $this->addSql('CREATE INDEX IDX_50159CA953C59D72 ON projet (responsable_id)');
        $this->addSql('ALTER TABLE projet_utilisateur ADD CONSTRAINT FK_C626378DC18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE projet_utilisateur ADD CONSTRAINT FK_C626378DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ressource CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE type_ressource type_ressource VARCHAR(255) DEFAULT NULL, CHANGE fournisseur fournisseur VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE service CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE type_service type_service VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE archive archive TINYINT DEFAULT NULL');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id)');
        $this->addSql('DROP INDEX fk_service_utilisateur ON service');
        $this->addSql('CREATE INDEX IDX_E19D9AD2FB88E14F ON service (utilisateur_id)');
        $this->addSql('DROP INDEX categorie_id ON service');
        $this->addSql('CREATE INDEX IDX_E19D9AD2BCF5E72D ON service (categorie_id)');
        $this->addSql('DROP INDEX projet_id ON tache');
        $this->addSql('DROP INDEX employe_id ON tache');
        $this->addSql('ALTER TABLE tache CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE priorite priorite VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE tel tel VARCHAR(255) DEFAULT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE department department VARCHAR(255) DEFAULT NULL, CHANGE poste poste VARCHAR(255) DEFAULT NULL, CHANGE competences competences LONGTEXT DEFAULT NULL, CHANGE failed_login_attempts failed_login_attempts INT DEFAULT NULL, CHANGE account_locked account_locked TINYINT DEFAULT NULL, CHANGE two_factor_enabled two_factor_enabled TINYINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evenement_ressource (id INT AUTO_INCREMENT NOT NULL, evenement_id INT DEFAULT NULL, ressource_id INT DEFAULT NULL, quantite INT DEFAULT NULL, UNIQUE INDEX evenement_id (evenement_id), UNIQUE INDEX ressource_id (ressource_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE evenement_ressource ADD CONSTRAINT `evenement_ressource_ibfk_1` FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE evenement_ressource ADD CONSTRAINT `evenement_ressource_ibfk_2` FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('DROP TABLE import_log');
        $this->addSql('ALTER TABLE categorie_service CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE date_creation date_creation DATE DEFAULT CURRENT_DATE, CHANGE archive archive TINYINT DEFAULT 0');
        $this->addSql('ALTER TABLE evenement DROP latitude, DROP longitude, CHANGE type_event type_event VARCHAR(100) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE lieu lieu VARCHAR(150) DEFAULT NULL, CHANGE titre titre VARCHAR(200) NOT NULL, CHANGE isArchived isArchived TINYINT DEFAULT 0');
        $this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY FK_94C5AD88FD02F13');
        $this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY FK_94C5AD88FD02F13');
        $this->addSql('ALTER TABLE event_feedback CHANGE evenement_id evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT `event_feedback_ibfk_1` FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_94c5ad88fd02f13 ON event_feedback');
        $this->addSql('CREATE INDEX evenement_id ON event_feedback (evenement_id)');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT FK_94C5AD88FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE fournisseur ADD rating NUMERIC(3, 2) DEFAULT \'0.00\', DROP telephone');
        $this->addSql('CREATE UNIQUE INDEX email ON fournisseur (email)');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F670C757F');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FFC6CD52A');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F670C757F');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FFC6CD52A');
        $this->addSql('ALTER TABLE offre CHANGE prix prix NUMERIC(10, 3) NOT NULL');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT `fk_fournisseur` FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT `fk_ressource` FOREIGN KEY (ressource_id) REFERENCES ressource (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_af86866f670c757f ON offre');
        $this->addSql('CREATE INDEX fk_fournisseur ON offre (fournisseur_id)');
        $this->addSql('DROP INDEX idx_af86866ffc6cd52a ON offre');
        $this->addSql('CREATE INDEX fk_ressource ON offre (ressource_id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE planning CHANGE type_shift type_shift VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX employe_id ON planning (employe_id)');
        $this->addSql('ALTER TABLE produit CHANGE nom nom VARCHAR(150) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE categorie categorie VARCHAR(100) DEFAULT NULL, CHANGE ressources_necessaires ressources_necessaires TEXT DEFAULT NULL, CHANGE details details TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_garantie ON produit (date_garantie)');
        $this->addSql('CREATE INDEX idx_peremption ON produit (date_peremption)');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA953C59D72');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA953C59D72');
        $this->addSql('ALTER TABLE projet ADD equipe_membres TEXT DEFAULT NULL, ADD progression INT DEFAULT NULL, CHANGE nom nom VARCHAR(150) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE date_debut date_debut DATE DEFAULT NULL, CHANGE date_fin date_fin DATE DEFAULT NULL, CHANGE budget budget NUMERIC(10, 2) DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE is_archived is_archived TINYINT DEFAULT 0, CHANGE responsable_id responsable_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_50159ca953c59d72 ON projet');
        $this->addSql('CREATE INDEX fk_projet_utilisateur ON projet (responsable_id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA953C59D72 FOREIGN KEY (responsable_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE projet_utilisateur DROP FOREIGN KEY FK_C626378DC18272');
        $this->addSql('ALTER TABLE projet_utilisateur DROP FOREIGN KEY FK_C626378DFB88E14F');
        $this->addSql('ALTER TABLE ressource CHANGE nom nom VARCHAR(150) DEFAULT NULL, CHANGE type_ressource type_ressource VARCHAR(50) DEFAULT NULL, CHANGE fournisseur fournisseur VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2FB88E14F');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2BCF5E72D');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2FB88E14F');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2BCF5E72D');
        $this->addSql('ALTER TABLE service CHANGE titre titre VARCHAR(150) DEFAULT NULL, CHANGE type_service type_service VARCHAR(100) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE archive archive TINYINT DEFAULT 0');
        $this->addSql('DROP INDEX idx_e19d9ad2fb88e14f ON service');
        $this->addSql('CREATE INDEX fk_service_utilisateur ON service (utilisateur_id)');
        $this->addSql('DROP INDEX idx_e19d9ad2bcf5e72d ON service');
        $this->addSql('CREATE INDEX categorie_id ON service (categorie_id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id)');
        $this->addSql('ALTER TABLE tache CHANGE titre titre VARCHAR(150) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE priorite priorite VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX projet_id ON tache (projet_id)');
        $this->addSql('CREATE INDEX employe_id ON tache (employe_id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE tel tel VARCHAR(20) DEFAULT NULL, CHANGE nom nom VARCHAR(150) NOT NULL, CHANGE prenom prenom VARCHAR(150) NOT NULL, CHANGE role role ENUM(\'admin\', \'ceo\', \'employe\', \'responsable\', \'responsable_projet\', \'responsable_production\', \'responsable_rh\') NOT NULL, CHANGE statut statut ENUM(\'actif\', \'inactif\') DEFAULT \'actif\', CHANGE department department VARCHAR(100) DEFAULT NULL, CHANGE poste poste VARCHAR(100) DEFAULT NULL, CHANGE competences competences TEXT DEFAULT NULL, CHANGE failed_login_attempts failed_login_attempts INT DEFAULT 0, CHANGE account_locked account_locked TINYINT DEFAULT 0, CHANGE two_factor_enabled two_factor_enabled TINYINT DEFAULT 0');
        $this->addSql('CREATE INDEX idx_email ON utilisateur (email)');
    }
}
