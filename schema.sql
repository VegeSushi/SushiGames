CREATE TABLE `games` (
                         `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                         `Name` varchar(255) NOT NULL,
                         `Platform` varchar(255) NOT NULL,
                         `Genre` varchar(100) NOT NULL,
                         `ImageURL` text NOT NULL,
                         `ROMURL` text NOT NULL,
                         `Developer` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;