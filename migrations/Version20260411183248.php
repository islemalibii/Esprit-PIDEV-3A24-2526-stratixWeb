<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411183248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY `FK_reset_user`');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY `FK_94C5AD88FD02F13`');
        $this->addSql('DROP INDEX evenement_id ON event_feedback');
        $this->addSql('CREATE INDEX IDX_94C5AD88FD02F13 ON event_feedback (evenement_id)');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT `FK_94C5AD88FD02F13` FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('DROP INDEX employe_id ON planning');
        $this->addSql('ALTER TABLE planning CHANGE type_shift type_shift VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_peremption ON produit');
        $this->addSql('DROP INDEX idx_garantie ON produit');
        $this->addSql('ALTER TABLE projet DROP equipe_membres, DROP progression, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE budget budget DOUBLE PRECISION NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE responsable_id responsable_id INT NOT NULL, CHANGE is_archived is_archived TINYINT NOT NULL');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA953C59D72 FOREIGN KEY (responsable_id) REFERENCES utilisateur (id)');
        $this->addSql('DROP INDEX fk_projet_utilisateur ON projet');
        $this->addSql('CREATE INDEX IDX_50159CA953C59D72 ON projet (responsable_id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id)');
        $this->addSql('DROP INDEX fk_service_utilisateur ON service');
        $this->addSql('CREATE INDEX IDX_E19D9AD2FB88E14F ON service (utilisateur_id)');
        $this->addSql('DROP INDEX categorie_id ON service');
        $this->addSql('CREATE INDEX IDX_E19D9AD2BCF5E72D ON service (categorie_id)');
        $this->addSql('DROP INDEX projet_id ON tache');
        $this->addSql('DROP INDEX employe_id ON tache');
        $this->addSql('ALTER TABLE utilisateur ADD avatar VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, hashed_token VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT `FK_reset_user` FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE event_feedback DROP FOREIGN KEY FK_94C5AD88FD02F13');
        $this->addSql('DROP INDEX idx_94c5ad88fd02f13 ON event_feedback');
        $this->addSql('CREATE INDEX evenement_id ON event_feedback (evenement_id)');
        $this->addSql('ALTER TABLE event_feedback ADD CONSTRAINT FK_94C5AD88FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE planning CHANGE type_shift type_shift VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX employe_id ON planning (employe_id)');
        $this->addSql('CREATE INDEX idx_peremption ON produit (date_peremption)');
        $this->addSql('CREATE INDEX idx_garantie ON produit (date_garantie)');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA953C59D72');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA953C59D72');
        $this->addSql('ALTER TABLE projet ADD equipe_membres TEXT DEFAULT NULL, ADD progression INT DEFAULT NULL, CHANGE nom nom VARCHAR(150) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE date_debut date_debut DATE DEFAULT NULL, CHANGE date_fin date_fin DATE DEFAULT NULL, CHANGE budget budget NUMERIC(10, 2) DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE is_archived is_archived TINYINT DEFAULT 0, CHANGE responsable_id responsable_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_50159ca953c59d72 ON projet');
        $this->addSql('CREATE INDEX fk_projet_utilisateur ON projet (responsable_id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA953C59D72 FOREIGN KEY (responsable_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2FB88E14F');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2BCF5E72D');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2FB88E14F');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2BCF5E72D');
        $this->addSql('DROP INDEX idx_e19d9ad2fb88e14f ON service');
        $this->addSql('CREATE INDEX fk_service_utilisateur ON service (utilisateur_id)');
        $this->addSql('DROP INDEX idx_e19d9ad2bcf5e72d ON service');
        $this->addSql('CREATE INDEX categorie_id ON service (categorie_id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_service (id)');
        $this->addSql('CREATE INDEX projet_id ON tache (projet_id)');
        $this->addSql('CREATE INDEX employe_id ON tache (employe_id)');
        $this->addSql('ALTER TABLE utilisateur DROP avatar');
    }
}
