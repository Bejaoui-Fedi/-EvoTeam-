<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405152415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY fk_event_review');
        $this->addSql('DROP TABLE daily_routine_task');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE exercise_completion');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE objective');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE wellbeing_tracker');
        $this->addSql('ALTER TABLE appointment ADD professional_id INT DEFAULT NULL, ADD professional_name VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE motif motif LONGTEXT NOT NULL, CHANGE type_rdv type_rdv VARCHAR(255) NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY consultation_ibfk_1');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY fk_appointment');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY consultation_ibfk_1');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY fk_appointment');
        $this->addSql('ALTER TABLE consultation CHANGE date_consultation date_consultation DATE NOT NULL, CHANGE diagnostic diagnostic LONGTEXT DEFAULT NULL, CHANGE observation observation LONGTEXT DEFAULT NULL, CHANGE traitement traitement LONGTEXT DEFAULT NULL, CHANGE ordonnance ordonnance LONGTEXT DEFAULT NULL, CHANGE statut_consultation statut_consultation VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('DROP INDEX appointment_id ON consultation');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_964685A6E5B533F9 ON consultation (appointment_id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT consultation_ibfk_1 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT fk_appointment FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_email ON user');
        $this->addSql('DROP INDEX idx_role ON user');
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('ALTER TABLE user DROP date_creation, DROP date_modification, DROP xp, DROP level, DROP current_streak, DROP last_activity_date, DROP reward_pending, DROP reward_claimed, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(255) DEFAULT NULL, CHANGE actif actif TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX idx_user_id ON user_profile');
        $this->addSql('DROP INDEX idx_langue ON user_profile');
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY user_profile_ibfk_1');
        $this->addSql('ALTER TABLE user_profile CHANGE avatar avatar VARCHAR(255) DEFAULT NULL, CHANGE bio bio LONGTEXT DEFAULT NULL, CHANGE langue langue VARCHAR(100) DEFAULT NULL, CHANGE notifications_email notifications_email TINYINT(1) NOT NULL, CHANGE notifications_sms notifications_sms TINYINT(1) NOT NULL, CHANGE parametres_confidentialite parametres_confidentialite LONGTEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE date_modification date_modification DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX user_id ON user_profile');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D95AB405A76ED395 ON user_profile (user_id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT user_profile_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE daily_routine_task (id INT NOT NULL, user_id INT NOT NULL, title VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_completed TINYINT(1) DEFAULT 0, completed_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evenement (eventId INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, startDate DATE NOT NULL, endDate DATE NOT NULL, maxParticipants INT NOT NULL, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, fee NUMERIC(10, 2) NOT NULL, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(eventId)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE exercise (id_exercise BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, objectiveId BIGINT UNSIGNED NOT NULL, title VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type ENUM(\'respiration\', \'journaling\', \'meditation\', \'cbt\', \'challenge\', \'relaxation\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, durationMinutes SMALLINT UNSIGNED NOT NULL, difficulty ENUM(\'debutant\', \'moyen\', \'avance\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mediaUrl VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, steps TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, orderIndex INT UNSIGNED DEFAULT NULL, isPublished TINYINT(1) DEFAULT 0 NOT NULL, createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, calendarEventId VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_type (type), INDEX idx_difficulty (difficulty), INDEX idx_objective (objectiveId), INDEX idx_exercise_user (user_id), INDEX idx_published (isPublished), PRIMARY KEY(id_exercise)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE exercise_completion (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, exercise_id INT NOT NULL, objective_id INT NOT NULL, completed_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_user (user_id), INDEX idx_objective (user_id, objective_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'UNREAD\' COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE objective (id_objective BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, title VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, icon VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, color CHAR(7) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, level ENUM(\'debutant\', \'moyen\', \'avance\', \'global\') CHARACTER SET utf8mb4 DEFAULT \'global\' NOT NULL COLLATE `utf8mb4_unicode_ci`, isPublished TINYINT(1) DEFAULT 0 NOT NULL, createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_level (level), INDEX idx_objective_user (user_id), INDEX idx_published (isPublished), PRIMARY KEY(id_objective)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE review (reviewId INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, reviewDate DATE NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, eventId INT NOT NULL, userId INT DEFAULT NULL, INDEX fk_event_review (eventId), PRIMARY KEY(reviewId)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wellbeing_tracker (id INT NOT NULL, user_id INT NOT NULL, daily_routine_task_id INT DEFAULT NULL, date DATE NOT NULL, mood TINYINT(1) DEFAULT NULL, stress TINYINT(1) DEFAULT NULL, energy TINYINT(1) DEFAULT NULL, sleep_hours NUMERIC(3, 1) DEFAULT NULL, note TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT fk_event_review FOREIGN KEY (eventId) REFERENCES evenement (eventId) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE appointment DROP professional_id, DROP professional_name, CHANGE statut statut VARCHAR(20) DEFAULT \'EN_ATTENTE\', CHANGE motif motif VARCHAR(255) DEFAULT NULL, CHANGE type_rdv type_rdv VARCHAR(50) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6E5B533F9');
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6E5B533F9');
        $this->addSql('ALTER TABLE consultation CHANGE date_consultation date_consultation DATE DEFAULT NULL, CHANGE diagnostic diagnostic TEXT DEFAULT NULL, CHANGE observation observation TEXT DEFAULT NULL, CHANGE traitement traitement TEXT DEFAULT NULL, CHANGE ordonnance ordonnance TEXT DEFAULT NULL, CHANGE statut_consultation statut_consultation VARCHAR(20) DEFAULT \'EN_COURS\'');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT consultation_ibfk_1 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT fk_appointment FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX uniq_964685a6e5b533f9 ON consultation');
        $this->addSql('CREATE UNIQUE INDEX appointment_id ON consultation (appointment_id)');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('ALTER TABLE `user` ADD date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, ADD date_modification DATETIME DEFAULT CURRENT_TIMESTAMP, ADD xp INT DEFAULT 0, ADD level VARCHAR(50) DEFAULT \'DÃ©butant\', ADD current_streak INT DEFAULT 0, ADD last_activity_date DATE DEFAULT NULL, ADD reward_pending TINYINT(1) DEFAULT 0, ADD reward_claimed TINYINT(1) DEFAULT 0, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(100) NOT NULL, CHANGE role role ENUM(\'ADMIN\', \'PSY_COACH\', \'PATIENT\') DEFAULT \'PATIENT\' NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE actif actif TINYINT(1) DEFAULT 1');
        $this->addSql('CREATE INDEX idx_email ON `user` (email)');
        $this->addSql('CREATE INDEX idx_role ON `user` (role)');
        $this->addSql('CREATE UNIQUE INDEX email ON `user` (email)');
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
        $this->addSql('ALTER TABLE user_profile CHANGE avatar avatar VARCHAR(500) DEFAULT NULL, CHANGE bio bio TEXT DEFAULT NULL, CHANGE langue langue VARCHAR(10) DEFAULT \'FR\', CHANGE notifications_email notifications_email TINYINT(1) DEFAULT 0, CHANGE notifications_sms notifications_sms TINYINT(1) DEFAULT 0, CHANGE parametres_confidentialite parametres_confidentialite ENUM(\'PUBLIC\', \'PRIVATE\', \'FRIENDS_ONLY\') DEFAULT \'PUBLIC\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE date_modification date_modification DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_user_id ON user_profile (user_id)');
        $this->addSql('CREATE INDEX idx_langue ON user_profile (langue)');
        $this->addSql('DROP INDEX uniq_d95ab405a76ed395 ON user_profile');
        $this->addSql('CREATE UNIQUE INDEX user_id ON user_profile (user_id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
