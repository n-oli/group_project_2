-- Team 2 Music Player database
-- Matches the corrected ERD: "Team 2 Music Player ERD (Corrected)" in Lucidchart.
-- Run in phpMyAdmin (SQL tab or Import) or:  mysql -u root < sql/schema.sql
-- WARNING: drops and recreates music_player, so any data in it is lost.

DROP DATABASE IF EXISTS music_player;
CREATE DATABASE music_player CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE music_player;

-- ===== Auth side =====
CREATE TABLE roles (
  role_id    INT AUTO_INCREMENT PRIMARY KEY,
  role_type  VARCHAR(255) NOT NULL UNIQUE          -- 'listener', 'admin'
) ENGINE=InnoDB;

CREATE TABLE contacts (
  contact_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) NOT NULL,
  last_name  VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL UNIQUE,
  phone      VARCHAR(20),
  street_1   VARCHAR(255),
  street_2   VARCHAR(255),
  city       VARCHAR(255),
  state_code CHAR(2),
  post_code  CHAR(5),                              -- CHAR keeps leading zeros (06515)
  updated    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  role_id       INT NOT NULL,
  contact_id    INT NOT NULL UNIQUE,               -- one contact per user
  creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id)    REFERENCES roles(role_id),
  FOREIGN KEY (contact_id) REFERENCES contacts(contact_id)
) ENGINE=InnoDB;

CREATE TABLE credentials (
  username      VARCHAR(255) PRIMARY KEY,
  user_id       INT NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,             -- from PHP password_hash()
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Music side =====
CREATE TABLE artists (
  artist_id     INT AUTO_INCREMENT PRIMARY KEY,
  artist_name   VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255),                      -- file path under uploads/
  bio           TEXT
) ENGINE=InnoDB;

CREATE TABLE genres (
  genre_id   INT AUTO_INCREMENT PRIMARY KEY,
  genre_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE albums (
  album_id     INT AUTO_INCREMENT PRIMARY KEY,
  album_name   VARCHAR(255) NOT NULL,
  release_date DATE,
  artist_id    INT NOT NULL,
  genre_id     INT,
  total_tracks INT,
  cover_art    VARCHAR(255),                       -- file path under uploads/
  FOREIGN KEY (artist_id) REFERENCES artists(artist_id),
  FOREIGN KEY (genre_id)  REFERENCES genres(genre_id)
) ENGINE=InnoDB;

CREATE TABLE songs (
  song_id       INT AUTO_INCREMENT PRIMARY KEY,
  song_title    VARCHAR(255) NOT NULL,
  artist_id     INT NOT NULL,
  album_id      INT,
  genre_id      INT,
  release_date  DATE,
  song_duration INT NOT NULL,                      -- seconds
  FOREIGN KEY (artist_id) REFERENCES artists(artist_id),
  FOREIGN KEY (album_id)  REFERENCES albums(album_id),
  FOREIGN KEY (genre_id)  REFERENCES genres(genre_id)
) ENGINE=InnoDB;

CREATE TABLE playlists (
  playlist_id   INT AUTO_INCREMENT PRIMARY KEY,
  playlist_name VARCHAR(100) NOT NULL,
  creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id       INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE playlist_songs (                      -- links playlists <-> songs
  playlist_id INT NOT NULL,
  song_id     INT NOT NULL,
  position    INT NOT NULL,
  PRIMARY KEY (playlist_id, song_id),
  FOREIGN KEY (playlist_id) REFERENCES playlists(playlist_id) ON DELETE CASCADE,
  FOREIGN KEY (song_id)     REFERENCES songs(song_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favorites (
  favorite_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  song_id     INT NOT NULL,
  date_added  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, song_id),                       -- can't favorite twice
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(song_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE listening_history (
  history_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  song_id    INT NOT NULL,
  played_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(song_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE searches (
  search_id    INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  search_query VARCHAR(255) NOT NULL,              -- what the user typed
  song_id      INT NULL,                           -- song they clicked, if any
  searched_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (song_id) REFERENCES songs(song_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== Seed data =====
INSERT INTO roles (role_type) VALUES ('listener'), ('admin');
INSERT INTO genres (genre_name) VALUES ('Hip-Hop'), ('Pop'), ('Afrobeats'), ('Rock');
