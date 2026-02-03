CREATE DATABASE cinema;
USE cinema;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten VARCHAR(100),
    email VARCHAR(100),
    mat_khau VARCHAR(255),
    vai_tro ENUM('user','admin') DEFAULT 'user'
);

CREATE TABLE phim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phim VARCHAR(200),
    the_loai VARCHAR(100),
    thoi_luong INT,
    mo_ta TEXT,
    poster VARCHAR(255)
);

CREATE TABLE rap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_rap VARCHAR(200)
);

CREATE TABLE phong_chieu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rap_id INT,
    ten_phong VARCHAR(50)
);

CREATE TABLE ghe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phong_id INT,
    ten_ghe VARCHAR(10)
);

CREATE TABLE suat_chieu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phim_id INT,
    phong_id INT,
    ngay DATE,
    gio TIME,
    gia INT
);

CREATE TABLE ve (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    suat_chieu_id INT,
    ghe_id INT
);
INSERT INTO users (ten, email, mat_khau, vai_tro)
VALUES (
    'User A',
    'user@gmail.com',
    '$2y$10$0kD0d4oQkDFugcfnS4.8NOC/XByH7lO6gf9f.uTe7qH93t/qm.r/.',
    'user'
);