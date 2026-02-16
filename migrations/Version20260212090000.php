<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused tables: journal_audit, resume_journalier, ticket_support, objectif';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS journal_audit');
        $this->addSql('DROP TABLE IF EXISTS resume_journalier');
        $this->addSql('DROP TABLE IF EXISTS ticket_support');
        $this->addSql('DROP TABLE IF EXISTS objectif');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE journal_audit (id INT AUTO_INCREMENT NOT NULL, acteur_id INT NOT NULL, action VARCHAR(50) NOT NULL, type_cible VARCHAR(50) NOT NULL, id_cible INT NOT NULL, cree_le DATETIME NOT NULL, metadata JSON DEFAULT NULL, INDEX IDX_8D5E8B5795A3A1C7 (acteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE objectif (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, type VARCHAR(20) NOT NULL, poids_depart_kg NUMERIC(10, 2) NOT NULL, poids_cible_kg NUMERIC(10, 2) NOT NULL, deficit_kcal_jour INT DEFAULT NULL, rythme_perte_kg_semaine NUMERIC(10, 2) DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, INDEX IDX_3F93A55DFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resume_journalier (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, date_jour DATE NOT NULL, calories_consommees INT NOT NULL, calories_brulees INT NOT NULL, tdee_estime INT NOT NULL, deficit_estime INT NOT NULL, UNIQUE INDEX uniq_resume_journalier_user_date (utilisateur_id, date_jour), INDEX IDX_7E0B7B2AFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_support (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, assigne_a_id INT DEFAULT NULL, statut VARCHAR(20) NOT NULL, sujet VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, cree_le DATETIME NOT NULL, mis_a_jour_le DATETIME NOT NULL, INDEX IDX_6A597B0EFB88E14F (utilisateur_id), INDEX IDX_6A597B0E5DA83F98 (assigne_a_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE journal_audit ADD CONSTRAINT FK_8D5E8B5795A3A1C7 FOREIGN KEY (acteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT FK_3F93A55DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE resume_journalier ADD CONSTRAINT FK_7E0B7B2AFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket_support ADD CONSTRAINT FK_6A597B0EFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket_support ADD CONSTRAINT FK_6A597B0E5DA83F98 FOREIGN KEY (assigne_a_id) REFERENCES user (id)');
    }
}
