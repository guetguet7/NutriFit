<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218220337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY `fk_activities_user`');
        $this->addSql('ALTER TABLE audit_logs DROP FOREIGN KEY `fk_audit_actor`');
        $this->addSql('ALTER TABLE meal_items DROP FOREIGN KEY `fk_meal_items_meal`');
        $this->addSql('ALTER TABLE meals DROP FOREIGN KEY `fk_meals_user`');
        $this->addSql('ALTER TABLE user_profiles DROP FOREIGN KEY `fk_profiles_user`');
        $this->addSql('ALTER TABLE weigh_ins DROP FOREIGN KEY `fk_weighins_user`');
        $this->addSql('DROP TABLE activities');
        $this->addSql('DROP TABLE api_cache');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE meal_items');
        $this->addSql('DROP TABLE meals');
        $this->addSql('DROP TABLE translation_cache');
        $this->addSql('DROP TABLE user_profiles');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE weigh_ins');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('CREATE TABLE activities (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL, 
            user_id INT UNSIGNED NOT NULL, 
            activity_type VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`,
            duration_min SMALLINT UNSIGNED NOT NULL, 
            met NUMERIC(4, 2) DEFAULT NULL, 
            calories_burned INT UNSIGNED NOT NULL, 
            performed_at DATETIME NOT NULL, 
            note VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, 
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, 
            updated_at DATETIME DEFAULT NULL, INDEX idx_activities_user_date (user_id, performed_at), 
            INDEX IDX_B5F1AFE5A76ED395 (user_id), 
            PRIMARY KEY (id)) 
            DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        $this->addSql('CREATE TABLE api_cache (id INT UNSIGNED AUTO_INCREMENT NOT NULL, provider ENUM(\'spoonacular\') CHARACTER SET utf8mb4 DEFAULT \'spoonacular\' NOT NULL COLLATE `utf8mb4_unicode_ci`, cache_key VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, response_json JSON NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_api_cache_exp (expires_at), UNIQUE INDEX uq_api_cache_key (cache_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_logs (id INT UNSIGNED AUTO_INCREMENT NOT NULL, actor_user_id INT UNSIGNED DEFAULT NULL, action VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, entity VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, entity_id INT UNSIGNED DEFAULT NULL, metadata JSON DEFAULT NULL, ip_address VARCHAR(45) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, user_agent VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_audit_actor (actor_user_id), INDEX idx_audit_date (created_at), INDEX idx_audit_entity (entity, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE meal_items (id INT UNSIGNED AUTO_INCREMENT NOT NULL, meal_id INT UNSIGNED NOT NULL, item_name VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, quantity NUMERIC(8, 2) DEFAULT NULL, unit VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, calories INT UNSIGNED DEFAULT NULL, proteins_g NUMERIC(6, 2) DEFAULT NULL, carbs_g NUMERIC(6, 2) DEFAULT NULL, fats_g NUMERIC(6, 2) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_meal_items_meal (meal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE meals (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, name VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, source ENUM(\'api\', \'manual\') CHARACTER SET utf8mb4 DEFAULT \'manual\' NOT NULL COLLATE `utf8mb4_unicode_ci`, spoonacular_id INT UNSIGNED DEFAULT NULL, servings NUMERIC(5, 2) DEFAULT \'1.00\' NOT NULL, calories_total INT UNSIGNED NOT NULL, proteins_g NUMERIC(6, 2) DEFAULT NULL, carbs_g NUMERIC(6, 2) DEFAULT NULL, fats_g NUMERIC(6, 2) DEFAULT NULL, eaten_at DATETIME NOT NULL, note VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_meals_spoonacular (spoonacular_id), INDEX idx_meals_user_date (user_id, eaten_at), INDEX IDX_E229E6EAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE translation_cache (id INT UNSIGNED AUTO_INCREMENT NOT NULL, provider ENUM(\'lara_translate\') CHARACTER SET utf8mb4 DEFAULT \'lara_translate\' NOT NULL COLLATE `utf8mb4_unicode_ci`, source_hash CHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, source_lang CHAR(2) CHARACTER SET utf8mb4 DEFAULT \'en\' NOT NULL COLLATE `utf8mb4_unicode_ci`, target_lang CHAR(2) CHARACTER SET utf8mb4 DEFAULT \'fr\' NOT NULL COLLATE `utf8mb4_unicode_ci`, source_text VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, translated_text VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX uq_translation_hash (source_hash), INDEX idx_translation_lang (source_lang, target_lang), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_profiles (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, birth_date DATE DEFAULT NULL, sex ENUM(\'F\', \'M\', \'X\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, height_cm SMALLINT UNSIGNED DEFAULT NULL, weight_kg NUMERIC(5, 2) DEFAULT NULL, activity_level ENUM(\'sedentary\', \'light\', \'moderate\', \'active\', \'very_active\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, goal_type ENUM(\'loss\', \'maintain\', \'gain\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, target_weight_kg NUMERIC(5, 2) DEFAULT NULL, weekly_goal_kg NUMERIC(4, 2) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX uq_profiles_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password_hash VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, role ENUM(\'client\', \'employe\', \'admin\') CHARACTER SET utf8mb4 DEFAULT \'client\' NOT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX uq_users_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
       
        $this->addSql('CREATE TABLE weigh_ins (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, weighed_at DATE NOT NULL, weight_kg NUMERIC(5, 2) NOT NULL, note VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_weighins_user_date (user_id, weighed_at), UNIQUE INDEX uq_weighins_user_date (user_id, weighed_at), INDEX IDX_AE4797C1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        
        $this->addSql('ALTER TABLE activities 
            ADD CONSTRAINT `fk_activities_user` 
            FOREIGN KEY (user_id) 
            REFERENCES users (id) 
            ON UPDATE CASCADE ON DELETE CASCADE');
        
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (actor_user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE meal_items ADD CONSTRAINT `fk_meal_items_meal` FOREIGN KEY (meal_id) REFERENCES meals (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meals ADD CONSTRAINT `fk_meals_user` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_profiles ADD CONSTRAINT `fk_profiles_user` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE weigh_ins ADD CONSTRAINT `fk_weighins_user` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
