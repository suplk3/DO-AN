CREATE DATABASE movie_ticket CHARACTER SET utf8mb4;
USE movie_ticket;

CREATE TABLE phim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_phim VARCHAR(100) NOT NULL
);

CREATE TABLE ve (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phim_id INT,
    suat_chieu VARCHAR(20),
    ghe VARCHAR(10),
    hoten VARCHAR(100),
    sdt VARCHAR(20),
    FOREIGN KEY (phim_id) REFERENCES phim(id)
);

INSERT INTO phim (ten_phim) VALUES
('Avengers'),
('Conan'),
('Fast & Furious');
