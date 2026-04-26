<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425234507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCC18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_fav_user ON favori');
        $this->addSql('CREATE INDEX IDX_EF85A2CCFB88E14F ON favori (utilisateur_id)');
        $this->addSql('DROP INDEX idx_fav_proj ON favori');
        $this->addSql('CREATE INDEX IDX_EF85A2CCC18272 ON favori (projet_id)');
        $this->addSql('DROP INDEX unique_favori_user_projet ON favori');
        $this->addSql('CREATE UNIQUE INDEX unique_user_projet ON favori (utilisateur_id, projet_id)');
        $this->addSql('DROP INDEX email ON fournisseur');
        $this->addSql('ALTER TABLE fournisseur DROP rating');
        $this->addSql('ALTER TABLE offre CHANGE prix prix DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('DROP INDEX fk_fournisseur ON offre');
        $this->addSql('CREATE INDEX IDX_AF86866F670C757F ON offre (fournisseur_id)');
        $this->addSql('DROP INDEX fk_ressource ON offre');
        $this->addSql('CREATE INDEX IDX_AF86866FFC6CD52A ON offre (ressource_id)');
        $this->addSql('ALTER TABLE phase DROP FOREIGN KEY `FK_SPRINT_PROJET`');
        $this->addSql('ALTER TABLE phase DROP FOREIGN KEY `FK_SPRINT_PROJET`');
        $this->addSql('ALTER TABLE phase DROP velocite_estimee, DROP velocite_reelle, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL');
        $this->addSql('ALTER TABLE phase ADD CONSTRAINT FK_B1BDD6CBC18272 FOREIGN KEY (projet_id) REFERENCES projet (id)');
        $this->addSql('DROP INDEX idx_sprint_projet ON phase');
        $this->addSql('CREATE INDEX IDX_B1BDD6CBC18272 ON phase (projet_id)');
        $this->addSql('ALTER TABLE phase ADD CONSTRAINT `FK_SPRINT_PROJET` FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX employe_id ON planning');
        $this->addSql('ALTER TABLE planning CHANGE date date DATE NOT NULL, CHANGE type_shift type_shift VARCHAR(50) NOT NULL');
        $this->addSql('DROP INDEX idx_peremption ON produit');
        $this->addSql('DROP INDEX idx_garantie ON produit');
        $this->addSql('ALTER TABLE produit CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE categorie categorie VARCHAR(255) DEFAULT NULL, CHANGE ressources_necessaires ressources_necessaires LONGTEXT DEFAULT NULL, CHANGE details details LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE projet DROP equipe_membres, DROP progression, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE budget budget DOUBLE PRECISION NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE responsable_id responsable_id INT NOT NULL, CHANGE is_archived is_archived TINYINT NOT NULL');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA953C59D72 FOREIGN KEY (responsable_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_50159CA96C6E55B5 ON projet (nom)');
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
        $this->addSql('ALTER TABLE tache CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE priorite priorite VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE theme theme VARCHAR(255) DEFAULT \'light\', CHANGE last_emotion last_emotion VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCFB88E14F');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCC18272');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCFB88E14F');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCC18272');
        $this->addSql('DROP INDEX idx_ef85a2ccc18272 ON favori');
        $this->addSql('CREATE INDEX IDX_FAV_PROJ ON favori (projet_id)');
        $this->addSql('DROP INDEX unique_user_projet ON favori');
        $this->addSql('CREATE UNIQUE INDEX unique_favori_user_projet ON favori (utilisateur_id, projet_id)');
        $this->addSql('DROP INDEX idx_ef85a2ccfb88e14f ON favori');
        $this->addSql('CREATE INDEX IDX_FAV_USER ON favori (utilisateur_id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCC18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fournisseur ADD rating NUMERIC(3, 2) DEFAULT \'0.00\'');
        $this->addSql('CREATE UNIQUE INDEX email ON fournisseur (email)');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F670C757F');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FFC6CD52A');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866F670C757F');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_AF86866FFC6CD52A');
        $this->addSql('ALTER TABLE offre CHANGE prix prix NUMERIC(10, 3) NOT NULL');
        $this->addSql('DROP INDEX idx_af86866ffc6cd52a ON offre');
        $this->addSql('CREATE INDEX fk_ressource ON offre (ressource_id)');
        $this->addSql('DROP INDEX idx_af86866f670c757f ON offre');
        $this->addSql('CREATE INDEX fk_fournisseur ON offre (fournisseur_id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866F670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_AF86866FFC6CD52A FOREIGN KEY (ressource_id) REFERENCES ressource (id)');
        $this->addSql('ALTER TABLE phase DROP FOREIGN KEY FK_B1BDD6CBC18272');
        $this->addSql('ALTER TABLE phase DROP FOREIGN KEY FK_B1BDD6CBC18272');
        $this->addSql('ALTER TABLE phase ADD velocite_estimee INT DEFAULT NULL, ADD velocite_reelle INT DEFAULT NULL, CHANGE date_debut date_debut DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_fin date_fin DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE phase ADD CONSTRAINT `FK_SPRINT_PROJET` FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_b1bdd6cbc18272 ON phase');
        $this->addSql('CREATE INDEX IDX_SPRINT_PROJET ON phase (projet_id)');
        $this->addSql('ALTER TABLE phase ADD CONSTRAINT FK_B1BDD6CBC18272 FOREIGN KEY (projet_id) REFERENCES projet (id)');
        $this->addSql('ALTER TABLE planning CHANGE date date DATE DEFAULT NULL, CHANGE type_shift type_shift VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX employe_id ON planning (employe_id)');
        $this->addSql('ALTER TABLE produit CHANGE nom nom VARCHAR(150) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE categorie categorie VARCHAR(100) DEFAULT NULL, CHANGE ressources_necessaires ressources_necessaires TEXT DEFAULT NULL, CHANGE details details TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_peremption ON produit (date_peremption)');
        $this->addSql('CREATE INDEX idx_garantie ON produit (date_garantie)');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA953C59D72');
        $this->addSql('DROP INDEX UNIQ_50159CA96C6E55B5 ON projet');
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
        $this->addSql('ALTER TABLE tache CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE priorite priorite VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX projet_id ON tache (projet_id)');
        $this->addSql('CREATE INDEX employe_id ON tache (employe_id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE theme theme VARCHAR(10) DEFAULT \'light\', CHANGE last_emotion last_emotion VARCHAR(50) DEFAULT NULL');
    }
}
