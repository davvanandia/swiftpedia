-- Buat database
CREATE DATABASE IF NOT EXISTS swiftpedia_db;
USE swiftpedia_db;

-- Tabel users
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel posts
CREATE TABLE posts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    content VARCHAR(250) NOT NULL,
    image_path VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel comments (dengan parent_id untuk reply)
CREATE TABLE comments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    post_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    parent_id INT(11) DEFAULT NULL,
    content VARCHAR(250) NOT NULL,
    image_path VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- Ubah parent_id agar default NULL
ALTER TABLE comments DROP FOREIGN KEY comments_ibfk_3;
ALTER TABLE comments MODIFY parent_id INT NULL DEFAULT NULL;
ALTER TABLE comments ADD FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE;