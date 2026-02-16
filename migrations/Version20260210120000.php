<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pseudo to profil_utilisateur';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('profil_utilisateur')->hasColumn('pseudo')) {
            return;
        }
        $this->addSql('ALTER TABLE profil_utilisateur ADD pseudo VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->getTable('profil_utilisateur')->hasColumn('pseudo')) {
            return;
        }
        $this->addSql('ALTER TABLE profil_utilisateur DROP pseudo');
    }
}
