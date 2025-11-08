-- 1. TẠO CƠ SỞ DỮ LIỆU (DATABASE)
CREATE DATABASE IF NOT EXISTS quan_ly_thu_vien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE quan_ly_thu_vien;

---
-- 2. TẠO BẢNG VAI TRÒ (PHÂN QUYỀN)
CREATE TABLE vai_tro (
    ma_vai_tro INT AUTO_INCREMENT PRIMARY KEY,
    ten_vai_tro VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO vai_tro (ten_vai_tro) VALUES
('Quản trị viên'),
('Thủ thư'),
('Độc giả');

---
-- 3. TẠO BẢNG NGƯỜI DÙNG
CREATE TABLE nguoi_dung (
    ma_nguoi_dung INT AUTO_INCREMENT PRIMARY KEY,
    ma_vai_tro INT NOT NULL,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE,
    so_dien_thoai VARCHAR(20),
    dia_chi VARCHAR(255),
    trang_thai TINYINT(1) DEFAULT 1,
    ngay_dang_ky DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ma_vai_tro) REFERENCES vai_tro(ma_vai_tro)
);

---
-- 4. TẠO BẢNG TÁC GIẢ
CREATE TABLE tac_gia (
    ma_tac_gia INT AUTO_INCREMENT PRIMARY KEY,
    ten_tac_gia VARCHAR(100) NOT NULL,
    tieu_su TEXT
);

---
-- 5. TẠO BẢNG SÁCH (ĐÃ THÊM CỘT duong_dan_anh)
CREATE TABLE sach (
    ma_sach INT AUTO_INCREMENT PRIMARY KEY,
    ten_sach VARCHAR(255) NOT NULL,
    ma_tac_gia INT NOT NULL,
    nam_xuat_ban INT(4),
    nha_xuat_ban VARCHAR(100),
    tong_so_luong INT NOT NULL,
    so_luong_kha_dung INT NOT NULL,
    duong_dan_anh VARCHAR(255),
    FOREIGN KEY (ma_tac_gia) REFERENCES tac_gia(ma_tac_gia)
);

---
-- 6. TẠO BẢNG PHIẾU MƯỢN
CREATE TABLE phieu_muon (
    ma_phieu_muon INT AUTO_INCREMENT PRIMARY KEY,
    ma_nguoi_muon INT NOT NULL,
    ma_sach INT NOT NULL,
    ma_nguoi_lap_phieu INT NOT NULL,
    ngay_muon DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ngay_den_han DATE NOT NULL,
    ngay_tra_thuc_te DATETIME DEFAULT NULL,
    trang_thai_muon VARCHAR(50) NOT NULL,
    FOREIGN KEY (ma_nguoi_muon) REFERENCES nguoi_dung(ma_nguoi_dung),
    FOREIGN KEY (ma_sach) REFERENCES sach(ma_sach),
    FOREIGN KEY (ma_nguoi_lap_phieu) REFERENCES nguoi_dung(ma_nguoi_dung)
);

---
-- 7. TẠO BẢNG PHIẾU PHẠT
CREATE TABLE phieu_phat (
    ma_phieu_phat INT AUTO_INCREMENT PRIMARY KEY,
    ma_phieu_muon INT NOT NULL,
    so_tien_phat DECIMAL(10, 2) NOT NULL,
    ly_do_phat TEXT NOT NULL,
    ngay_ghi_nhan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trang_thai_thanh_toan VARCHAR(50) NOT NULL,
    FOREIGN KEY (ma_phieu_muon) REFERENCES phieu_muon(ma_phieu_muon)
);

USE quan_ly_thu_vien;

SET @default_password = '123456';

INSERT INTO nguoi_dung (ma_vai_tro, ten_dang_nhap, mat_khau, ho_ten, email, so_dien_thoai, dia_chi) VALUES
(1, 'admin', @default_password, 'Quản trị viên', 'qtv@gmail.com', '0981234567', 'Tầng 5, Tòa nhà VTC, 18 Tam Trinh, Q. Hai Bà Trưng, Hà Nội'),
(2, 'thuthu_van', @default_password, 'Trần Văn An', 'an.tranvan@gmail.com', '0375889901', '45 Nguyễn Đình Chiểu, P. Đa Kao, Q.1, TP.HCM'),
(2, 'thuthu_linh', @default_password, 'Nguyễn Thị Thuỳ Linh', 'linh.thuy@gmail.com', '0868778899', '202A Đường 3/2, P.12, Q.10, TP.HCM'),
(3, 'docgia_khanh', @default_password, 'Đặng Quốc Khánh', 'khanh.dangquoc@gmail.com', '0912345678', '15 Ngõ Huyện, P. Hàng Trống, Q. Hoàn Kiếm, Hà Nội'),
(3, 'docgia_huong', @default_password, 'Phạm Thu Hương', 'huong.phamthu@gmail.com', '0398765432', '35 Trần Hưng Đạo, P. Cái Khế, Q. Ninh Kiều, Cần Thơ'),
(3, 'docgia_khoa', @default_password, 'Đặng Hoàng Khoa', 'khoa.danghoang@gmail.com', '0977665544', 'Khu Phố 6, P. Linh Trung, TP. Thủ Đức, TP.HCM'),
(3, 'docgia_oanh', @default_password, 'Vũ Thanh Oanh', 'oanh.vuthanh@gmail.com', '0336901234', '145 Lê Duẩn, P. Láng Hạ, Q. Đống Đa, Hà Nội'),
(3, 'docgia_long', @default_password, 'Huỳnh Ngọc Long', 'long.huynhngoc@gmail.com', '0945823719', '29/8 Nguyễn Bỉnh Khiêm, P. Bến Nghé, Q.1, TP.HCM');

INSERT INTO tac_gia (ten_tac_gia, tieu_su) VALUES
( 'Donald Knuth', 'Nhà khoa học máy tính, tác giả bộ The Art of Computer Programming.' ),
( 'Thomas H. Cormen', 'Đồng tác giả cuốn Introduction to Algorithms (CLRS).' ),
( 'Robert Sedgewick', 'Chuyên gia Thuật toán và Cấu trúc dữ liệu, Đại học Princeton.' ),
( 'Andrew Tanenbaum', 'Chuyên gia về Hệ điều hành và Mạng máy tính.' ),
( 'Martin Fowler', 'Chuyên gia phần mềm, nổi tiếng với Refactoring và Design Patterns.' ),
( 'Eric Gamma', 'Thành viên "Gang of Four", đồng tác giả cuốn Design Patterns.' ),
( 'Robert C. Martin (Uncle Bob)', 'Tác giả nổi tiếng với các nguyên tắc Clean Code và Clean Architecture.' ),
( 'Dennis Ritchie', 'Đồng sáng tạo ngôn ngữ C và hệ điều hành Unix.' ),
( 'Bjarne Stroustrup', 'Người tạo ra ngôn ngữ lập trình C++.' ),
( 'Eric Evans', 'Nổi tiếng với khái niệm Domain Driven Design (DDD).' ),
( 'Douglas Crockford', 'Chuyên gia JavaScript, tác giả cuốn JavaScript: The Good Parts.' ),
( 'Kent Beck', 'Người tiên phong trong Lập trình Cực hạn (XP) và Test-Driven Development (TDD).' ),
( 'Steve McConnell', 'Tác giả cuốn Code Complete.' ),
( 'Frederick P. Brooks Jr.', 'Tác giả cuốn The Mythical Man-Month.' ),
( 'Peter Norvig', 'Chuyên gia AI/ML, Giám đốc Nghiên cứu tại Google.' ),
( 'Guido van Rossum', 'Người tạo ra ngôn ngữ lập trình Python.' ),
( 'Michael Stonebraker', 'Chuyên gia hàng đầu về Hệ thống Cơ sở dữ liệu.' ),
( 'Bùi Thế Hà', 'Giáo sư, Tiến sĩ người Việt Nam, chuyên ngành Cấu trúc dữ liệu và Thuật toán.' );

INSERT INTO sach (ma_sach, ten_sach, ma_tac_gia, nam_xuat_ban, nha_xuat_ban, tong_so_luong, so_luong_kha_dung, duong_dan_anh) VALUES
(1, 'Introduction to Algorithms (CLRS)', 2, 2009, 'MIT Press', 8, 7, 'https://images-na.ssl-images-amazon.com/images/I/41T0f4E7V+L._SX357_BO1,204,203,200_.jpg'),
(2, 'The Art of Computer Programming, Vol 1', 1, 1997, 'Addison-Wesley', 4, 3, 'https://images-na.ssl-images-amazon.com/images/I/41O0M4+tNOL._SX345_BO1,204,203,200_.jpg'),
(3, 'Algorithms, 4th Edition', 3, 2011, 'Addison-Wesley', 10, 8, 'https://images-na.ssl-images-amazon.com/images/I/41Y+H41bS2L._SX355_BO1,204,203,200_.jpg'),
(4, 'Cấu trúc dữ liệu và giải thuật trong C++', 18, 2017, 'NXB Khoa học Kỹ thuật', 15, 14, 'https://salt.tikicdn.com/cache/w1200/ts/product/b6/c7/e9/8b8e0b6b251a37c44e99a80b8535492f.jpg'),
(5, 'Computer Networks', 4, 2010, 'Pearson', 7, 6, 'https://images-na.ssl-images-amazon.com/images/I/51Bq6wQ3QCL._SX380_BO1,204,203,200_.jpg'),
(6, 'Operating Systems Design and Implementation', 4, 2014, 'Prentice Hall', 5, 5, 'https://images-na.ssl-images-amazon.com/images/I/51rY5pC+uWL._SX388_BO1,204,203,200_.jpg'),
(7, 'Refactoring: Improving the Design of Existing Code', 5, 2018, 'Addison-Wesley', 9, 8, 'https://images-na.ssl-images-amazon.com/images/I/512yJp2yAEL._SX404_BO1,204,203,200_.jpg'),
(8, 'Design Patterns: Elements of Reusable Object-Oriented Software', 6, 1994, 'Addison-Wesley', 12, 10, 'https://images-na.ssl-images-amazon.com/images/I/51A4zYtG3EL._SX332_BO1,204,203,200_.jpg'),
(9, 'Clean Code: A Handbook of Agile Software Craftsmanship', 7, 2008, 'Prentice Hall', 15, 13, 'https://images-na.ssl-images-amazon.com/images/I/51E2055I7YL._SX381_BO1,204,203,200_.jpg'),
(10, 'Database System Concepts (Silberschatz)', 17, 2019, 'McGraw-Hill', 11, 11, 'https://images-na.ssl-images-amazon.com/images/I/419+9H2eFQL._SX386_BO1,204,203,200_.jpg');


INSERT INTO sach (ma_sach, ten_sach, ma_tac_gia, nam_xuat_ban, nha_xuat_ban, tong_so_luong, so_luong_kha_dung, duong_dan_anh) VALUES
(11, 'The C Programming Language', 8, 1988, 'Prentice Hall', 10, 9, 'https://images-na.ssl-images-amazon.com/images/I/51Y+C2c0v3L._SX331_BO1,204,203,200_.jpg'),
(12, 'The C++ Programming Language', 9, 2013, 'Addison-Wesley', 10, 9, 'https://images-na.ssl-images-amazon.com/images/I/51Lp1l6+J8L._SX331_BO1,204,203,200_.jpg'),
(13, 'Python Crash Course', 16, 2016, 'No Starch Press', 18, 17, 'https://images-na.ssl-images-amazon.com/images/I/51r+G8fF8NL._SX379_BO1,204,203,200_.jpg'),
(14, 'JavaScript: The Good Parts', 11, 2008, 'O''Reilly', 15, 14, 'https://images-na.ssl-images-amazon.com/images/I/51n2m9bS84L._SX379_BO1,204,203,200_.jpg'),
(15, 'Eloquent JavaScript, 3rd Ed', 11, 2018, 'No Starch Press', 14, 13, 'https://images-na.ssl-images-amazon.com/images/I/51o+3+57oFL._SX380_BO1,204,203,200_.jpg'),
(16, 'Java Concurrency in Practice', 1, 2006, 'Addison-Wesley', 7, 7, 'https://images-na.ssl-images-amazon.com/images/I/51+s0v2-cFL._SX381_BO1,204,203,200_.jpg'),
(17, 'Head First Java', 5, 2005, 'O''Reilly', 12, 11, 'https://images-na.ssl-images-amazon.com/images/I/51oV8vF1u5L._SX404_BO1,204,203,200_.jpg'),
(18, 'Clean Architecture', 7, 2017, 'Prentice Hall', 11, 10, 'https://images-na.ssl-images-amazon.com/images/I/41xS0+q4TOL._SX331_BO1,204,203,200_.jpg'),
(19, 'Domain-Driven Design: Tackling Complexity in the Heart of Software', 10, 2003, 'Addison-Wesley', 8, 7, 'https://images-na.ssl-images-amazon.com/images/I/51+K1dF4kFL._SX384_BO1,204,203,200_.jpg'),
(20, 'The Mythical Man-Month', 14, 1995, 'Addison-Wesley', 6, 6, 'https://images-na.ssl-images-amazon.com/images/I/51i1G4SjI8L._SX331_BO1,204,203,200_.jpg'),
(21, 'Code Complete, 2nd Edition', 13, 2004, 'Microsoft Press', 13, 12, 'https://images-na.ssl-images-amazon.com/images/I/51H7+r+6F-L._SX385_BO1,204,203,200_.jpg'),
(22, 'Test-Driven Development by Example', 12, 2003, 'Addison-Wesley', 10, 9, 'https://images-na.ssl-images-amazon.com/images/I/41S3P1rOQBL._SX331_BO1,204,203,200_.jpg'),
(23, 'Implementing Domain-Driven Design', 10, 2013, 'Addison-Wesley', 8, 7, 'https://images-na.ssl-images-amazon.com/images/I/51jF1Hq5k4L._SX379_BO1,204,203,200_.jpg'),
(24, 'Enterprise Integration Patterns', 5, 2002, 'Addison-Wesley', 9, 9, 'https://images-na.ssl-images-amazon.com/images/I/51y4i1p2uKL._SX381_BO1,204,203,200_.jpg'),
(25, 'Patterns of Enterprise Application Architecture', 5, 2002, 'Addison-Wesley', 9, 8, 'https://images-na.ssl-images-amazon.com/images/I/51xGfS-L4gL._SX381_BO1,204,203,200_.jpg'),
(26, 'Programming Pearls', 1, 1999, 'Addison-Wesley', 5, 4, 'https://images-na.ssl-images-amazon.com/images/I/41wWq8g1g0L._SX331_BO1,204,203,200_.jpg'),
(27, 'The Algorithm Design Manual', 2, 2008, 'Springer', 6, 5, 'https://images-na.ssl-images-amazon.com/images/I/51wX21n5fBL._SX342_BO1,204,203,200_.jpg'),
(28, 'Computer Architecture: A Quantitative Approach', 2, 2017, 'Morgan Kaufmann', 7, 7, 'https://images-na.ssl-images-amazon.com/images/I/51H96N-wWwL._SX385_BO1,204,203,200_.jpg'),
(29, 'Structure and Interpretation of Computer Programs (SICP)', 3, 1996, 'MIT Press', 10, 9, 'https://images-na.ssl-images-amazon.com/images/I/41G8y29T1TL._SX348_BO1,204,203,200_.jpg'),
(30, 'Compilers: Principles, Techniques, & Tools (Dragon Book)', 1, 2006, 'Addison-Wesley', 8, 8, 'https://images-na.ssl-images-amazon.com/images/I/41VjQx-5QWL._SX379_BO1,204,203,200_.jpg'),
(31, 'Artificial Intelligence: A Modern Approach', 15, 2010, 'Pearson', 12, 11, 'https://images-na.ssl-images-amazon.com/images/I/51L+5+42wUL._SX386_BO1,204,203,200_.jpg'),
(32, 'Deep Learning (Adaptive Computation and ML)', 15, 2016, 'MIT Press', 9, 8, 'https://images-na.ssl-images-amazon.com/images/I/51j-yN+8yPL._SX331_BO1,204,203,200_.jpg'),
(33, 'Hands-On Machine Learning with Scikit-Learn, Keras & TensorFlow', 15, 2019, 'O''Reilly', 16, 15, 'https://images-na.ssl-images-amazon.com/images/I/514T0G9K5fL._SX379_BO1,204,203,200_.jpg'),
(34, 'Pattern Recognition and Machine Learning', 15, 2006, 'Springer', 7, 7, 'https://images-na.ssl-images-amazon.com/images/I/41gT8cK746L._SX362_BO1,204,203,200_.jpg'),
(35, 'TCP/IP Illustrated, Vol 1: The Protocols', 4, 1994, 'Addison-Wesley', 6, 6, 'https://images-na.ssl-images-amazon.com/images/I/510TqgJzL2L._SX342_BO1,204,203,200_.jpg'),
(36, 'Advanced Programming in the UNIX Environment', 8, 2013, 'Addison-Wesley', 8, 7, 'https://images-na.ssl-images-amazon.com/images/I/51N-3yL993L._SX331_BO1,204,203,200_.jpg'),
(37, 'Unix Network Programming, Vol 1', 8, 2003, 'Prentice Hall', 7, 6, 'https://images-na.ssl-images-amazon.com/images/I/512y2W2yqLL._SX331_BO1,204,203,200_.jpg'),
(38, 'Designing Data-Intensive Applications', 17, 2017, 'O''Reilly', 10, 9, 'https://images-na.ssl-images-amazon.com/images/I/51S4CgJgPML._SX379_BO1,204,203,200_.jpg'),
(39, 'High Performance MySQL, 4th Edition', 17, 2012, 'O''Reilly', 12, 11, 'https://images-na.ssl-images-amazon.com/images/I/51x88B+L00L._SX379_BO1,204,203,200_.jpg'),
(40, 'SQL Cookbook', 17, 2005, 'O''Reilly', 11, 10, 'https://images-na.ssl-images-amazon.com/images/I/51wXh-N9h2L._SX379_BO1,204,203,200_.jpg'),
(41, 'Peopleware: Productive Projects and Teams', 14, 2013, 'Addison-Wesley', 5, 4, 'https://images-na.ssl-images-amazon.com/images/I/51F4V6rF60L._SX331_BO1,204,203,200_.jpg'),
(42, 'The Pragmatic Programmer: Your Journey To Mastery', 7, 2019, 'Addison-Wesley', 10, 9, 'https://images-na.ssl-images-amazon.com/images/I/51A-x+f7gBL._SX379_BO1,204,203,200_.jpg');

INSERT INTO phieu_muon (ma_phieu_muon, ma_nguoi_muon, ma_sach, ma_nguoi_lap_phieu, ngay_muon, ngay_den_han, ngay_tra_thuc_te, trang_thai_muon) VALUES
(1, 4, 3, 2, '2025-09-01 10:00:00', '2025-09-15', '2025-09-15 11:30:00', 'da_tra'),
(2, 4, 1, 2, '2025-10-20 14:00:00', '2025-11-03', NULL, 'dang_muon'),
(3, 5, 8, 3, '2025-10-05 09:00:00', '2025-10-19', '2025-10-25 10:00:00', 'da_tra'),
(4, 6, 5, 2, '2025-10-01 15:00:00', '2025-10-15', NULL, 'dang_muon'),
(5, 7, 4, 3, '2025-09-15 08:30:00', '2025-09-29', '2025-10-03 09:00:00', 'da_tra'),
(6, 8, 9, 2, '2025-11-05 16:00:00', '2025-11-19', NULL, 'dang_muon'),
(7, 4, 11, 2, '2025-11-01 08:00:00', '2025-11-15', '2025-11-16 09:00:00', 'da_tra'),
(8, 5, 18, 3, '2025-10-25 11:00:00', '2025-11-08', NULL, 'dang_muon'),
(9, 6, 21, 2, '2025-11-07 14:30:00', '2025-11-21', NULL, 'dang_muon'),
(10, 7, 2, 3, '2025-10-10 17:00:00', '2025-10-24', NULL, 'dang_muon'),
(11, 8, 28, 2, '2025-09-05 10:00:00', '2025-09-19', '2025-09-29 11:00:00', 'da_tra'),
(12, 4, 7, 2, '2025-11-08 10:00:00', '2025-11-22', NULL, 'dang_muon'),
(13, 5, 14, 3, '2025-11-09 11:00:00', '2025-11-23', NULL, 'dang_muon'),
(14, 6, 22, 2, '2025-10-25 09:00:00', '2025-11-08', NULL, 'dang_muon'),
(15, 7, 25, 3, '2025-09-01 13:00:00', '2025-09-15', '2025-09-15 13:00:00', 'da_tra'),
(16, 8, 12, 2, '2025-10-03 16:00:00', '2025-10-17', NULL, 'dang_muon'),
(17, 4, 32, 2, '2025-11-01 10:00:00', '2025-11-15', NULL, 'dang_muon'),
(18, 5, 38, 3, '2025-10-20 12:00:00', '2025-11-03', '2025-11-05 14:00:00', 'da_tra'),
(19, 6, 40, 2, '2025-11-07 10:00:00', '2025-11-21', NULL, 'dang_muon'),
(20, 7, 41, 3, '2025-10-01 14:00:00', '2025-10-15', NULL, 'dang_muon'),
(21, 8, 27, 2, '2025-11-05 15:00:00', '2025-11-19', NULL, 'dang_muon');

INSERT INTO phieu_phat (ma_phieu_muon, so_tien_phat, ly_do_phat, trang_thai_thanh_toan, ngay_ghi_nhan) VALUES
(3, 30000.00, 'Trả sách Design Patterns chậm 6 ngày.', 'da_thanh_toan', '2025-10-25 10:15:00'),
(5, 20000.00, 'Trả sách Cấu trúc dữ liệu và giải thuật trong C++ chậm 4 ngày.', 'chua_thanh_toan', '2025-10-03 09:30:00'),
(4, 115000.00, 'Sách Computer Networks đang bị quá hạn nghiêm trọng.', 'chua_thanh_toan', '2025-11-07 10:00:00'),
(7, 5000.00, 'Trả sách The C Programming Language chậm 1 ngày.', 'chua_thanh_toan', '2025-11-16 09:00:00'),
(11, 50000.00, 'Trả sách Advanced Programming in the UNIX Environment chậm 10 ngày.', 'da_thanh_toan', '2025-09-29 11:30:00'),
(18, 10000.00, 'Trả sách Designing Data-Intensive Applications chậm 2 ngày.', 'chua_thanh_toan', '2025-11-05 14:00:00');