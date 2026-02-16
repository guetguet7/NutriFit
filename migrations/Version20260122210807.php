<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122210807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal_audit DROP FOREIGN KEY `FK_71C3CC53DA6F574A`');
        $this->addSql('ALTER TABLE objectif DROP FOREIGN KEY `FK_E2F86851FB88E14F`');
        $this->addSql('ALTER TABLE resume_journalier DROP FOREIGN KEY `FK_8D7999B4FB88E14F`');
        $this->addSql('ALTER TABLE ticket_support DROP FOREIGN KEY `FK_8CC8B2F7BB1B0F33`');
        $this->addSql('ALTER TABLE ticket_support DROP FOREIGN KEY `FK_8CC8B2F7FB88E14F`');
        $this->addSql('DROP TABLE journal_audit');
        $this->addSql('DROP TABLE objectif');
        $this->addSql('DROP TABLE resume_journalier');
        $this->addSql('DROP TABLE ticket_support');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE journal_audit (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, type_cible VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, id_cible INT NOT NULL, cree_le DATETIME NOT NULL, metadata JSON DEFAULT NULL, acteur_id INT NOT NULL, INDEX IDX_71C3CC53DA6F574A (acteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE objectif (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, poids_depart_kg NUMERIC(10, 2) NOT NULL, poids_cible_kg NUMERIC(10, 2) NOT NULL, deficit_kcal_jour INT DEFAULT NULL, rythme_perte_kg_semaine NUMERIC(10, 2) DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_E2F86851FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE resume_journalier (id INT AUTO_INCREMENT NOT NULL, date_jour DATE NOT NULL, calories_consommees INT NOT NULL, calories_brulees INT NOT NULL, tdee_estime INT NOT NULL, deficit_estime INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_8D7999B4FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ticket_support (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, sujet VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, cree_le DATETIME NOT NULL, mis_ajour_le DATETIME NOT NULL, utilisateur_id INT NOT NULL, assigne_a_id INT DEFAULT NULL, INDEX IDX_8CC8B2F7BB1B0F33 (assigne_a_id), INDEX IDX_8CC8B2F7FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE journal_audit ADD CONSTRAINT `FK_71C3CC53DA6F574A` FOREIGN KEY (acteur_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT `FK_E2F86851FB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE resume_journalier ADD CONSTRAINT `FK_8D7999B4FB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_support ADD CONSTRAINT `FK_8CC8B2F7BB1B0F33` FOREIGN KEY (assigne_a_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket_support ADD CONSTRAINT `FK_8CC8B2F7FB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
