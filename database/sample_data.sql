USE tms_db;
INSERT INTO vehicles (registration_number,vehicle_model,seating_capacity,owner_name,status,registration_date) VALUES
('KDA 123A','Toyota HiAce',14,'John Mwangi','Active',CURDATE()),
('KCB 456B','Nissan Caravan',14,'Grace Wanjiru','Active',CURDATE());
INSERT INTO staff (full_name,national_id,phone,staff_role,license_number,status) VALUES
('Peter Otieno','23456789','0712345678','Driver','DL-884421','Active'),
('Mary Achieng','31234567','0723456789','Conductor',NULL,'Active');
INSERT INTO routes (route_name,starting_point,destination,distance,fare) VALUES
('Nairobi - Kitengela','Nairobi CBD','Kitengela',35.00,150.00);
INSERT INTO passengers (full_name,phone_number,email) VALUES
('Faith Njeri','0734567890','faith@example.com');
