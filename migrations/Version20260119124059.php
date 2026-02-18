<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119124059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql(
            'CREATE TABLE activite (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, duree_min INT NOT NULL, calories_brulees INT NOT NULL, date_activite DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_B8755515FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4'
            );

        $this->addSql(
            '
            
            CREATE TABLE 
            element_repas (id INT AUTO_INCREMENT NOT NULL, 
            libelle VARCHAR(255) DEFAULT NULL, 
            quantite NUMERIC(10, 2) NOT NULL, 
            unite VARCHAR(20) NOT NULL, 
            calories INT NOT NULL, 
            repas_id INT NOT NULL, 
            recette_id INT DEFAULT NULL, 
            INDEX IDX_6CF1252A1D236AAA (repas_id), 
            INDEX IDX_6CF1252A89312FE9 (recette_id), 
            PRIMARY KEY (id)) 
            DEFAULT CHARACTER SET utf8mb4'
            );

        $this->addSql('CREATE TABLE journal_audit (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, type_cible VARCHAR(50) NOT NULL, id_cible INT NOT NULL, cree_le DATETIME NOT NULL, metadata JSON DEFAULT NULL, acteur_id INT NOT NULL, INDEX IDX_71C3CC53DA6F574A (acteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE objectif (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, poids_depart_kg NUMERIC(10, 2) NOT NULL, poids_cible_kg NUMERIC(10, 2) NOT NULL, deficit_kcal_jour INT DEFAULT NULL, rythme_perte_kg_semaine NUMERIC(10, 2) DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_E2F86851FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE profil_utilisateur (id INT AUTO_INCREMENT NOT NULL, sexe VARCHAR(10) NOT NULL, age INT NOT NULL, taille_cm INT NOT NULL, poids_kg NUMERIC(10, 2) NOT NULL, niveau_activite VARCHAR(20) NOT NULL, objectif_type VARCHAR(20) NOT NULL, poids_cible_kg NUMERIC(10, 2) DEFAULT NULL, rythme_perte_kg_semaine NUMERIC(10, 2) DEFAULT NULL, utilisateur_id INT NOT NULL, UNIQUE INDEX UNIQ_47227AF3FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql(
            'CREATE TABLE recette (
                id INT AUTO_INCREMENT NOT NULL, 
                spoonacular_id INT NOT NULL, 
                titre VARCHAR(255) NOT NULL, 
                image_url VARCHAR(255) DEFAULT NULL, 
                calories_par_portion INT DEFAULT NULL, 
                protein_g NUMERIC(10, 2) DEFAULT NULL, 
                carbs_g NUMERIC(10, 2) DEFAULT NULL, 
                fat_g NUMERIC(10, 2) DEFAULT NULL, 
                servings INT NOT NULL, 
                raw_json JSON DEFAULT NULL, 
                updated_at DATETIME NOT NULL, 
                UNIQUE INDEX UNIQ_49BB639083FB1E43 (spoonacular_id), 
                PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE repas (
        id INT AUTO_INCREMENT NOT NULL, 
        date_repas DATETIME NOT NULL, 
        type_repas VARCHAR(20) NOT NULL, 
        total_calories INT NOT NULL, 
        source VARCHAR(20) NOT NULL, 
        notes LONGTEXT DEFAULT NULL, 
        utilisateur_id INT NOT NULL, 
        INDEX IDX_A8D351B3FB88E14F (utilisateur_id), 
        PRIMARY KEY (id)) 
        DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE resume_journalier (id INT AUTO_INCREMENT NOT NULL, date_jour DATE NOT NULL, calories_consommees INT NOT NULL, calories_brulees INT NOT NULL, tdee_estime INT NOT NULL, deficit_estime INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_8D7999B4FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket_support (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(20) NOT NULL, sujet VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, cree_le DATETIME NOT NULL, mis_ajour_le DATETIME NOT NULL, utilisateur_id INT NOT NULL, assigne_a_id INT DEFAULT NULL, INDEX IDX_8CC8B2F7FB88E14F (utilisateur_id), INDEX IDX_8CC8B2F7BB1B0F33 (assigne_a_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE element_repas ADD CONSTRAINT FK_6CF1252A1D236AAA FOREIGN KEY (repas_id) REFERENCES repas (id)');

        $this->addSql('

        ALTER TABLE 
        element_repas 
        ADD CONSTRAINT FK_6CF1252A89312FE9 
        FOREIGN KEY (recette_id) REFERENCES 
        recette (id)');
        
        $this->addSql('ALTER TABLE journal_audit ADD CONSTRAINT FK_71C3CC53DA6F574A FOREIGN KEY (acteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT FK_E2F86851FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE profil_utilisateur ADD CONSTRAINT FK_47227AF3FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE repas 
        ADD CONSTRAINT FK_A8D351B3FB88E14F 
        FOREIGN KEY (utilisateur_id) 
        REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE resume_journalier ADD CONSTRAINT FK_8D7999B4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket_support ADD CONSTRAINT FK_8CC8B2F7FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket_support ADD CONSTRAINT FK_8CC8B2F7BB1B0F33 FOREIGN KEY (assigne_a_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515FB88E14F');
        $this->addSql('ALTER TABLE element_repas DROP FOREIGN KEY FK_6CF1252A1D236AAA');
        $this->addSql('ALTER TABLE element_repas DROP FOREIGN KEY FK_6CF1252A89312FE9');
        $this->addSql('ALTER TABLE journal_audit DROP FOREIGN KEY FK_71C3CC53DA6F574A');
        $this->addSql('ALTER TABLE objectif DROP FOREIGN KEY FK_E2F86851FB88E14F');
        $this->addSql('ALTER TABLE profil_utilisateur DROP FOREIGN KEY FK_47227AF3FB88E14F');
        $this->addSql('ALTER TABLE repas DROP FOREIGN KEY FK_A8D351B3FB88E14F');
        $this->addSql('ALTER TABLE resume_journalier DROP FOREIGN KEY FK_8D7999B4FB88E14F');
        $this->addSql('ALTER TABLE ticket_support DROP FOREIGN KEY FK_8CC8B2F7FB88E14F');
        $this->addSql('ALTER TABLE ticket_support DROP FOREIGN KEY FK_8CC8B2F7BB1B0F33');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE element_repas');
        $this->addSql('DROP TABLE journal_audit');
        $this->addSql('DROP TABLE objectif');
        $this->addSql('DROP TABLE profil_utilisateur');
        $this->addSql('DROP TABLE recette');
        $this->addSql('DROP TABLE repas');
        $this->addSql('DROP TABLE resume_journalier');
        $this->addSql('DROP TABLE ticket_support');
    }
}
