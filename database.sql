CREATE DATABASE movie_ticket;
USE movie_ticket;

CREATE TABLE phim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phim VARCHAR(100)
);

CREATE TABLE ve (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phim VARCHAR(100),
    suat_chieu VARCHAR(50),
    ghe VARCHAR(10),
    hoten VARCHAR(100),
    sdt VARCHAR(20)
);

INSERT INTO phim (ten_phim) VALUES
('Avengers'),
('Conan'),
('Fast & Furious');
