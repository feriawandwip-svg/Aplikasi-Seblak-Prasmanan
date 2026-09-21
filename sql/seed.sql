USE seblak_db;

INSERT INTO kategori (id, nama, urutan) VALUES
(1, 'Karbo', 1),
(2, 'Protein & Topping', 2),
(3, 'Sayur & Pelengkap', 3);

INSERT INTO menu_item (id_kategori, nama, harga_satuan) VALUES
(1, 'Kerupuk Mawar', 2000),
(1, 'Kerupuk Mie', 2000),
(1, 'Makaroni', 3000),
(1, 'Kwetiau', 5000),
(1, 'Mi Instan', 4000),
(2, 'Sosis', 4000),
(2, 'Bakso', 4000),
(2, 'Ceker', 5000),
(2, 'Tulang Ayam', 6000),
(2, 'Siomay', 3000),
(2, 'Dumpling Keju', 4000),
(3, 'Sawi', 2000),
(3, 'Kol', 2000),
(3, 'Jamur Enoki', 4000),
(3, 'Tahu', 3000);

INSERT INTO kuah (nama, harga) VALUES
('Original', 0),
('Tom Yam', 3000),
('Seblak Ceker', 5000);

INSERT INTO level_pedas (level, surcharge) VALUES
(0, 0),
(1, 0),
(2, 0),
(3, 0),
(4, 0),
(5, 0);

INSERT INTO meja (nomor_meja) VALUES
(1), (2), (3), (4), (5), (6), (7), (8), (9), (10);