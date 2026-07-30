# Msantha Pigs Management System (PHP & MySQL Version)

This version of the system has been rewritten in PHP to run flawlessly on your XAMPP setup!

## How to Install and Run on XAMPP

### 1. Move the Files
Copy the entire `msantha-pigs-php` folder into your XAMPP's `htdocs` directory.
- For example, it should be located at: `C:\xampp\htdocs\msantha-pigs-php`

### 2. Setup the Database
1. Open your XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open your web browser and go to `http://localhost/phpmyadmin`.
3. You don't need to create the database manually if you import the script. Just click on the **Import** tab at the top.
4. Click **Choose File** and select the `setup.sql` file located inside the `msantha-pigs-php` folder.
5. Click **Import** (or **Go**) at the bottom.
   - *This script automatically creates the database (`msantha_pigs`), all the tables, and inserts the default admin/clerk accounts!*

### 3. Run the System
1. Open your web browser.
2. Go to `http://localhost/msantha-pigs-php`.
3. You will see the beautiful landing page. Click **Login**!

### Default Login Credentials
- **Admin Role:** Username `admin` / Password `admin123`
- **Clerk Role:** Username `clerk` / Password `clerk123`

## Technical Details (For Learning)
- **PHP Data Objects (PDO):** We use PDO in `db.php` to connect to MySQL. This is the most modern and secure way to prevent SQL injection attacks using "prepared statements".
- **PHP Sessions:** When you log in, `login.php` starts a session (`session_start()`) and stores your user ID. The `requireLogin()` function in `db.php` checks this session on every private page to ensure hackers cannot bypass the login screen.
- **Includes:** Instead of rewriting the sidebar, header, and footer on every page, they are stored in the `includes` folder and injected using `<?php include 'includes/header.php'; ?>`.
- **Password Hashing:** We use `password_hash()` in PHP to securely encrypt passwords. Even if someone hacks phpMyAdmin, they cannot read the passwords.
