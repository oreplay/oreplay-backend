#######################################################################
drop table if exists stage_types;

CREATE TABLE stage_types (
        id                   INTEGER NOT NULL,
        description          VARCHAR(255),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

INSERT INTO stage_types VALUES (0, 'Foot-O, MTBO, Ski-O', NULL, NULL, NULL);
INSERT INTO stage_types VALUES (1, 'Mass Start', NULL, NULL, NULL);
INSERT INTO stage_types VALUES (2, 'Chase Start', NULL, NULL, NULL);
INSERT INTO stage_types VALUES (3, 'Relay', NULL, NULL, NULL);
INSERT INTO stage_types VALUES (4, 'Rogaine', NULL, NULL, NULL);
INSERT INTO stage_types VALUES (5, 'Raid', NULL, NULL, NULL);
INSERT INTO stage_types VALUES (6, 'Trail-O', NULL, NULL, NULL);

#######################################################################
drop table if exists federations;

CREATE TABLE federations (
        id                   VARCHAR(36) NOT NULL,
        description          VARCHAR(255),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

INSERT INTO federations VALUES ('FEDO', 'FEDO SICO', NULL, NULL, NULL);
INSERT INTO federations VALUES ('IOF', 'IOF OEVENTOR', NULL, NULL, NULL);

#######################################################################
drop table if exists events;

CREATE TABLE events (
        id                   INTEGER NOT NULL AUTO_INCREMENT,
        description          VARCHAR(255),
        initial_date         DATE NULL,
	final_date           DATE NULL,
        federation_id        VARCHAR(36) NULL,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE events
    ADD FOREIGN KEY (federation_id)
    REFERENCES federations (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists stages;

CREATE TABLE stages (
        id                   INTEGER NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        description          VARCHAR(255),
        base_date            DATE NULL,
	base_time            TIME NULL,
        order_number         INTEGER DEFAULT 1,
        stage_type_id        INTEGER DEFAULT 0,
        server_offset        INTEGER DEFAULT 0,
        utc_value            VARCHAR(10),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE stages
    ADD FOREIGN KEY (event_id)
    REFERENCES events (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE stages 
    ADD FOREIGN KEY (stage_type_id)
    REFERENCES stage_types (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists users;

CREATE TABLE users (
        id                   VARCHAR(36) NOT NULL,
        password             VARCHAR(128),
        first_name           VARCHAR(50),
        last_name            VARCHAR(100),
        is_admin             BOOLEAN NOT NULL DEFAULT 0,
        is_super             BOOLEAN NOT NULL DEFAULT 0,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

#######################################################################
drop table if exists users_federations;

CREATE TABLE users_federations (
        user_id              VARCHAR(36) NOT NULL,
        federation_id        VARCHAR(36) NOT NULL,
        uuid_value           VARCHAR(36),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(user_id, federation_id));

ALTER TABLE users_federations
    ADD FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE users_federations
    ADD FOREIGN KEY (federation_id)
    REFERENCES federations (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

#######################################################################
drop table if exists users_events;

CREATE TABLE users_events (
        user_id              VARCHAR(36) NOT NULL,
        event_id             INTEGER NOT NULL,
        is_admin             BOOLEAN DEFAULT 0,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(user_id, event_id));

ALTER TABLE users_events
    ADD FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE users_events
    ADD FOREIGN KEY (event_id)
    REFERENCES events (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

#######################################################################
drop table if exists clubs;

CREATE TABLE clubs (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        uuid                 VARCHAR(36),
        oe_key               VARCHAR(36),
        short_name           VARCHAR(50),
        long_name            VARCHAR(50),
        city                 VARCHAR(50),
        logo                 BLOB,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE clubs
    ADD FOREIGN KEY (event_id)
    REFERENCES events (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE clubs
    ADD FOREIGN KEY (stage_id)
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

#######################################################################
drop table if exists courses;

CREATE TABLE courses (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        uuid                 VARCHAR(36),
        oe_key               VARCHAR(36),
        short_name           VARCHAR(50),
        long_name            VARCHAR(50),
        distance             VARCHAR(15),
        climb                VARCHAR(15),
        controls             INTEGER,
        coord_system         CHAR(1),
        datum                VARCHAR(50),
        utm_zone             INTEGER,
        hemisphere           CHAR(1),
        latitude             NUMERIC(14, 6),
        longitude            NUMERIC(14, 6),
        zoom                 INTEGER,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE courses
    ADD FOREIGN KEY (event_id)
    REFERENCES events (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE courses
    ADD FOREIGN KEY (stage_id)
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

#######################################################################
drop table if exists classes;

CREATE TABLE classes (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        course_id            BIGINT NULL,
        uuid                 VARCHAR(36),
        oe_key               VARCHAR(36),
        short_name           VARCHAR(50),
        long_name            VARCHAR(50),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE classes
    ADD FOREIGN KEY (event_id)
    REFERENCES events (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE classes
    ADD FOREIGN KEY (stage_id)
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE classes
    ADD FOREIGN KEY (course_id)
    REFERENCES courses (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists teams;

CREATE TABLE teams (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        uuid                 VARCHAR(36),
        bib_number           VARCHAR(10),
        bib_alt              VARCHAR(10),
        team_name            VARCHAR(50),
        sicard               VARCHAR(10),
        sicard_alt           VARCHAR(10),
        class_id             BIGINT NULL,
        class_uuid           VARCHAR(36),
        club_id              BIGINT NULL,
        legs                 INTEGER NULL,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE teams
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE teams
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE teams
    ADD FOREIGN KEY (class_id)
    REFERENCES classes (id)
    ON DELETE SET NULL;

ALTER TABLE teams
    ADD FOREIGN KEY (club_id)
    REFERENCES clubs (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists runners;

CREATE TABLE runners (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        uuid                 VARCHAR(36),
        first_name           VARCHAR(50),
        last_name            VARCHAR(100),
        db_id                VARCHAR(50),
        iof_id               VARCHAR(50),
        bib_number           VARCHAR(10),
        bib_alt              VARCHAR(10),
        sicard               VARCHAR(10),
        sicard_alt           VARCHAR(10),
        license              VARCHAR(15),
        national_id          VARCHAR(15),
        birth_date           DATE NULL,
        sex                  CHAR(1),
        telephone1           VARCHAR(50),
        telephone2           VARCHAR(50),
        email                VARCHAR(100),
        user_id              VARCHAR(36) NULL,
        class_id             BIGINT NULL,
        class_uuid           VARCHAR(36),
        club_id              BIGINT NULL,
        team_id              BIGINT NULL,
        leg_number           INTEGER NULL DEFAULT 1,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE runners
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE runners
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE runners
    ADD FOREIGN KEY (class_id)
    REFERENCES classes (id)
    ON DELETE SET NULL;

ALTER TABLE runners
    ADD FOREIGN KEY (club_id)
    REFERENCES clubs (id)
    ON DELETE SET NULL;

ALTER TABLE runners
    ADD FOREIGN KEY (team_id)
    REFERENCES teams (id)
    ON DELETE SET NULL;

ALTER TABLE runners
    ADD FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists control_types;

CREATE TABLE control_types (
        id                   INTEGER NOT NULL,
        description          VARCHAR(255),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

INSERT INTO control_types VALUES (0, 'Normal Control', NULL, NULL, NULL);
INSERT INTO control_types VALUES (1, 'Start', NULL, NULL, NULL);
INSERT INTO control_types VALUES (2, 'Finish', NULL, NULL, NULL);
INSERT INTO control_types VALUES (3, 'Clear', NULL, NULL, NULL);
INSERT INTO control_types VALUES (4, 'Check', NULL, NULL, NULL);

#######################################################################
drop table if exists controls;

CREATE TABLE controls (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
	control_name         VARCHAR(50),
	station              VARCHAR(10),
        coord_system         CHAR(1),
        datum                VARCHAR(50),
        utm_zone             INTEGER,
        hemisphere           CHAR(1),
        latitude             NUMERIC(14, 6),
        longitude            NUMERIC(14, 6),
        control_type_id      INTEGER DEFAULT 0,
        battery_perc         INTEGER DEFAULT 100,
        last_reading         TIMESTAMP NULL,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE controls
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE controls 
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE controls 
    ADD FOREIGN KEY (control_type_id)
    REFERENCES control_types (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists classes_controls;

CREATE TABLE classes_controls (
        class_id             BIGINT NOT NULL,
        control_id           BIGINT NOT NULL,
        id_leg               INTEGER NOT NULL,
        id_revisit           INTEGER NOT NULL,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        description          VARCHAR(255),
        order_number         INTEGER,
        kilometer            NUMERIC(6,2),
        relative_position    INTEGER,
        controls             INTEGER,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(class_id, control_id, id_leg, id_revisit));

ALTER TABLE classes_controls
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE classes_controls
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE classes_controls
    ADD FOREIGN KEY (class_id)
    REFERENCES classes (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE classes_controls
    ADD FOREIGN KEY (control_id)
    REFERENCES controls (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

#######################################################################
drop table if exists result_types;

CREATE TABLE result_types (
        id                   INTEGER NOT NULL,
        description          VARCHAR(255),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

INSERT INTO result_types VALUES (0, 'Overall', NULL, NULL, NULL);
INSERT INTO result_types VALUES (1, 'Stage', NULL, NULL, NULL);
INSERT INTO result_types VALUES (2, 'Trail-O Normal', NULL, NULL, NULL);
INSERT INTO result_types VALUES (3, 'Trail-O Timed', NULL, NULL, NULL);
INSERT INTO result_types VALUES (4, 'Raid Section', NULL, NULL, NULL);

#######################################################################
drop table if exists runner_results;

CREATE TABLE runner_results (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        runner_id            BIGINT NOT NULL,
        class_id             BIGINT NULL,
        stage_order          INTEGER DEFAULT 1,
        runner_uuid          VARCHAR(36),
        class_uuid           VARCHAR(36),
        result_type_id       INTEGER DEFAULT 1,
        check_time           TIMESTAMP NULL,
        start_time           TIMESTAMP NULL,
        finish_time          TIMESTAMP NULL,
        time_seconds         INTEGER DEFAULT 0,
        position             INTEGER DEFAULT 0,
        status_code          CHAR(1) DEFAULT '0',
        time_behind          INTEGER DEFAULT 0,
        time_neutralization  INTEGER DEFAULT 0,
        time_adjusted        INTEGER DEFAULT 0,
        time_penalty         INTEGER DEFAULT 0,
        time_bonus           INTEGER DEFAULT 0,
        points_final         INTEGER DEFAULT 0,
        points_adjusted      INTEGER DEFAULT 0,
        points_penalty       INTEGER DEFAULT 0,
        points_bonus         INTEGER DEFAULT 0,
        leg_number           INTEGER DEFAULT 1,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE runner_results 
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE runner_results 
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE runner_results 
    ADD FOREIGN KEY (runner_id)
    REFERENCES runners (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE runner_results 
    ADD FOREIGN KEY (class_id)
    REFERENCES classes (id)
    ON DELETE SET NULL;

ALTER TABLE runner_results 
    ADD FOREIGN KEY (result_type_id)
    REFERENCES result_types (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists team_results;

CREATE TABLE team_results (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        team_id              BIGINT NOT NULL,
        class_id             BIGINT NULL,
        stage_order          INTEGER DEFAULT 1,
        team_uuid            VARCHAR(36),
        class_uuid           VARCHAR(36),
        result_type_id       INTEGER DEFAULT 1,
        check_time           TIMESTAMP NULL,
        start_time           TIMESTAMP NULL,
        finish_time          TIMESTAMP NULL,
        time_seconds         INTEGER DEFAULT 0,
        position             INTEGER DEFAULT 0,
        status_code          CHAR(1) DEFAULT '0',
        time_behind          INTEGER DEFAULT 0,
        time_neutralization  INTEGER DEFAULT 0,
        time_adjusted        INTEGER DEFAULT 0,
        time_penalty         INTEGER DEFAULT 0,
        time_bonus           INTEGER DEFAULT 0,
        points_final         INTEGER DEFAULT 0,
        points_adjusted      INTEGER DEFAULT 0,
        points_penalty       INTEGER DEFAULT 0,
        points_bonus         INTEGER DEFAULT 0,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE team_results 
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE team_results 
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE team_results 
    ADD FOREIGN KEY (team_id)
    REFERENCES teams (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE team_results 
    ADD FOREIGN KEY (class_id)
    REFERENCES classes (id)
    ON DELETE SET NULL;

ALTER TABLE team_results 
    ADD FOREIGN KEY (result_type_id)
    REFERENCES result_types (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists splits;

CREATE TABLE splits (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        stage_order          INTEGER,
        sicard               VARCHAR(10),
        station              VARCHAR(10),
        reading_time         TIMESTAMP NULL,
	reading_milli        BIGINT,
        points               INTEGER,
        runner_result_id     BIGINT NULL,
        team_result_id       BIGINT NULL,
        class_id             BIGINT NULL,
        control_id           BIGINT NULL,
        id_leg               INTEGER NULL,
        id_revisit           INTEGER NULL,
        runner_id            BIGINT NULL,
        team_id              BIGINT NULL,
        bib_runner           VARCHAR(10),
        bib_team             VARCHAR(10),
        club_id              BIGINT NULL,
        order_number         INTEGER,
        battery_perc         INTEGER,
        battery_time         TIMESTAMP NULL,
        raw_value            VARCHAR(50),
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

CREATE INDEX splits_lectura ON splits (sicard, station, reading_milli);
CREATE INDEX splits_lectura2 ON splits (event_id, stage_id, sicard, station, reading_milli);

ALTER TABLE splits 
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

ALTER TABLE splits 
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE splits 
    ADD FOREIGN KEY (runner_result_id)
    REFERENCES runner_results (id)
    ON DELETE SET NULL;

ALTER TABLE splits 
    ADD FOREIGN KEY (team_result_id)
    REFERENCES team_results (id)
    ON DELETE SET NULL;

ALTER TABLE splits 
    ADD FOREIGN KEY (class_id)
    REFERENCES classes (id)
    ON DELETE SET NULL;

ALTER TABLE splits 
    ADD FOREIGN KEY (control_id)
    REFERENCES controls (id);

ALTER TABLE splits 
    ADD FOREIGN KEY (class_id, control_id, id_leg, id_revisit)
    REFERENCES classes_controls (class_id, control_id, id_leg, id_revisit)
    ON DELETE SET NULL;

ALTER TABLE splits 
    ADD FOREIGN KEY (runner_id)
    REFERENCES runners (id)
    ON DELETE SET NULL;

ALTER TABLE splits 
    ADD FOREIGN KEY (team_id)
    REFERENCES teams (id)
    ON DELETE SET NULL;

ALTER TABLE splits 
    ADD FOREIGN KEY (club_id)
    REFERENCES clubs (id)
    ON DELETE SET NULL;

#######################################################################
drop table if exists answers;

CREATE TABLE answers (
        id                   BIGINT NOT NULL AUTO_INCREMENT,
        event_id             INTEGER NOT NULL,
        stage_id             INTEGER NOT NULL,
        runner_result_id     BIGINT NULL,
        order_number         INTEGER,
        given                VARCHAR(10),
        correct              VARCHAR(10),
        time_seconds         NUMERIC(8,2) DEFAULT 0,
        created              TIMESTAMP NULL,
        modified             TIMESTAMP NULL,
        deleted              TIMESTAMP NULL,
  CONSTRAINT PRIMARY KEY(id));

ALTER TABLE answers 
    ADD FOREIGN KEY (event_id) 
    REFERENCES events (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE answers 
    ADD FOREIGN KEY (stage_id) 
    REFERENCES stages (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE answers 
    ADD FOREIGN KEY (runner_result_id)
    REFERENCES runner_results (id)
    ON DELETE SET NULL;

