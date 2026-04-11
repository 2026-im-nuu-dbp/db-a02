-- 1. 在使用者資料表增加權限欄位
ALTER TABLE dbusers ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';

-- 2. 把你自己設定為管理員 (請將下方的 email 換成你的 Google 帳號)
UPDATE dbusers SET role = 'admin' WHERE ACCOUNT = 's102139@yfms.tyc.edu.tw';