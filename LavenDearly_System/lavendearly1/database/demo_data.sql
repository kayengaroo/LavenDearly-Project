-- LavenDearly demo data (optional). Import AFTER lavendearly.sql so the screens have something to show.
USE lavendearly;

INSERT INTO medicines (id,name,dose,times_of_day,start_date,refill_date,end_date,prescribing_doctor,status) VALUES
(1,'Losartan','50 mg, 1 tablet','08:00','2026-08-26','2026-12-14',NULL,'Dr. Elena Ramos','active'),
(2,'Metformin','500 mg, 1 tablet','08:00,20:00','2026-08-26','2026-11-29',NULL,'Dr. Elena Ramos','active'),
(3,'Amlodipine','5 mg, 1 tablet','08:00','2026-09-25','2026-11-26',NULL,'Dr. Elena Ramos','active'),
(4,'Atorvastatin','20 mg, 1 tablet','20:00','2026-09-25','2026-12-24',NULL,'Dr. Elena Ramos','active');

INSERT INTO dose_log (medicine_id,scheduled_date,scheduled_time,status,actual_time,logged_by,note,created_at) VALUES
(1,'2026-11-23','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-23','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-23','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-23','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-23','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-22','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-22','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-22','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-22','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-22','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-21','08:00:00','given','08:00:00',2,'Felt dizzy, held dose until after breakfast',NOW()),
(2,'2026-11-21','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-21','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-21','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-21','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-20','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-20','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-20','20:00:00','missed',NULL,2,NULL,NOW()),
(3,'2026-11-20','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-20','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-19','08:00:00','skipped',NULL,2,NULL,NOW()),
(2,'2026-11-19','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-19','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-19','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-19','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-18','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-18','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-18','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-18','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-18','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-17','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-17','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-17','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-17','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-17','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-16','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-16','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-16','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-16','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-16','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-15','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-15','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-15','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-15','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-15','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-14','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-14','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-14','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-14','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-14','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-13','08:00:00','skipped',NULL,2,NULL,NOW()),
(2,'2026-11-13','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-13','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-13','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-13','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-12','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-12','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-12','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-12','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-12','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-11','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-11','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-11','20:00:00','given','20:00:00',2,NULL,NOW()),
(3,'2026-11-11','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-11','20:00:00','given','20:00:00',2,NULL,NOW()),
(1,'2026-11-10','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-10','08:00:00','given','08:00:00',2,NULL,NOW()),
(2,'2026-11-10','20:00:00','missed',NULL,2,NULL,NOW()),
(3,'2026-11-10','08:00:00','given','08:00:00',2,NULL,NOW()),
(4,'2026-11-10','20:00:00','given','20:00:00',2,NULL,NOW());

INSERT INTO appointments (type,title,doctor,appt_date,notes,file_path,created_by,created_at) VALUES
('visit','Cardiology follow-up','Dr. Elena Ramos','2026-11-03','BP 130/85. Continue current medicines. Return in 6 weeks.',NULL,1,NOW()),
('lab','Fasting blood sugar and lipid panel','Medical City Laboratory','2026-11-06','FBS 118 mg/dL. Cholesterol slightly high.',NULL,2,NOW()),
('prescription','Maintenance medicines (3 months)','Dr. Elena Ramos','2026-11-03','Losartan, Metformin, Amlodipine, Atorvastatin.',NULL,1,NOW()),
('visit','Endocrinology check-up','Dr. Carlo Mendoza','2026-12-06','Bring the medicine list and latest lab results.',NULL,1,NOW());

INSERT INTO expenses (expense_date,item,category,amount,paid_by,receipt_path,created_by,created_at) VALUES
('2026-11-04','Losartan, Metformin, Amlodipine (30 days)','Medicines',2450.00,NULL,NULL,2,NOW()),
('2026-11-06','Lab tests: FBS and lipid panel','Check-up fees',1800.00,1,NULL,1,NOW()),
('2026-11-12','Groceries: oats, vegetables, fish','Groceries',1320.50,NULL,NULL,2,NOW()),
('2026-11-15','Cardiology consultation fee','Check-up fees',1500.00,3,NULL,3,NOW()),
('2026-11-18','Adult diapers and wipes','Supplies',960.00,NULL,NULL,2,NOW()),
('2026-11-21','Atorvastatin (30 days)','Medicines',1100.00,NULL,NULL,2,NOW());

INSERT INTO contributions (user_id,amount,contrib_date,note,created_at) VALUES
(1,2000.00,'2026-11-02','Sent to household fund',NOW()),
(3,3000.00,'2026-11-05','GCash from abroad',NOW()),
(4,1500.00,'2026-11-10','Cash handed to Rosa',NOW());

INSERT INTO care_notes (note_date,mood,meals,sleep,concerns,created_by,created_at) VALUES
('2026-11-22','Cheerful','Ate most of breakfast and lunch','Slept about 7 hours',NULL,2,NOW()),
('2026-11-23','A bit tired','Small dinner, drank enough water','Woke up twice at night','Mild knee pain when climbing stairs. Will mention it at the next check-up.',2,NOW());

INSERT INTO note_comments (note_id,user_id,comment,created_at) VALUES
(2,3,'Thanks Rosa. Please keep her off the stairs for now.',NOW()),
(2,1,'Noted. I will ask Dr. Ramos about the knee on the next visit.',NOW());
