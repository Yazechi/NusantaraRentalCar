# Database Migration Guide - Localhost

## What Changed?

Your database needs **4 new columns** in the `users` table for password reset functionality:

| Column Name | Type | Purpose |
|-------------|------|---------|
| `email_verified` | BOOLEAN | Track if user verified their email |
| `verification_token` | VARCHAR(100) | Token for email verification |
| `reset_token` | VARCHAR(100) | Token for password reset |
| `reset_token_expires` | DATETIME | When reset token expires |

---

## How to Update Your Database

### Option 1: Run the Batch File (Easiest)

1. **Make sure XAMPP is running:**
   - Open XAMPP Control Panel
   - MySQL should have a **green** indicator (running)

2. **Run the migration:**
   - Navigate to: `C:\xampp\htdocs\NusantaraRentalCar\database\`
   - **Double-click** `run_migration.bat`
   - The script will automatically update your database

3. **Done!**
   - You'll see "Migration Completed Successfully!"
   - Close the window

---

### Option 2: Use phpMyAdmin (Manual)

1. **Open phpMyAdmin:**
   - Go to: http://localhost/phpmyadmin/
   - Click on `nusantara_rental_car` database (left sidebar)

2. **Click SQL tab** (top menu)

3. **Copy and paste this SQL:**

```sql
-- Add new columns
ALTER TABLE users 
ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER role,
ADD COLUMN verification_token VARCHAR(100) AFTER email_verified,
ADD COLUMN reset_token VARCHAR(100) AFTER verification_token,
ADD COLUMN reset_token_expires DATETIME AFTER reset_token;

-- Add indexes
ALTER TABLE users 
ADD INDEX idx_email (email),
ADD INDEX idx_verification_token (verification_token),
ADD INDEX idx_reset_token (reset_token);
```

4. **Click "Go"** button

5. **Done!**
   - You should see "Query executed successfully"

---

### Option 3: Use MySQL Command Line

1. **Open Command Prompt**

2. **Navigate to XAMPP MySQL:**
   ```bash
   cd C:\xampp\mysql\bin
   ```

3. **Connect to MySQL:**
   ```bash
   mysql -u root
   ```

4. **Select database:**
   ```sql
   USE nusantara_rental_car;
   ```

5. **Run the migration file:**
   ```sql
   SOURCE C:/xampp/htdocs/NusantaraRentalCar/database/migration_password_reset.sql;
   ```

6. **Done!**

---

## Verify Migration Worked

**In phpMyAdmin:**
1. Go to `nusantara_rental_car` → `users` table
2. Click "Structure" tab
3. You should see these 4 new columns:
   - `email_verified`
   - `verification_token`
   - `reset_token`
   - `reset_token_expires`

**Screenshot of what you should see:**
```
┌──────────────────────┬──────────────┬──────┐
│ Field                │ Type         │ Null │
├──────────────────────┼──────────────┼──────┤
│ id                   │ int          │ NO   │
│ name                 │ varchar(100) │ NO   │
│ email                │ varchar(100) │ NO   │
│ password             │ varchar(255) │ NO   │
│ phone                │ varchar(20)  │ YES  │
│ address              │ text         │ YES  │
│ role                 │ enum         │ YES  │
│ email_verified       │ tinyint(1)   │ YES  │ ← NEW
│ verification_token   │ varchar(100) │ YES  │ ← NEW
│ reset_token          │ varchar(100) │ YES  │ ← NEW
│ reset_token_expires  │ datetime     │ YES  │ ← NEW
│ created_at           │ timestamp    │ NO   │
│ updated_at           │ timestamp    │ NO   │
└──────────────────────┴──────────────┴──────┘
```

---

## What This Enables

After migration, these features will work:

**Password Reset**
- Users can click "Forgot Password" on login page
- They receive email with reset link
- They can set new password securely

**Email Verification (Ready)**
- Database ready for email verification
- Can be implemented later if needed

---

## Troubleshooting

**Problem: "MySQL not found"**
- Check if XAMPP is installed in `C:\xampp\`
- If installed elsewhere, use Option 2 (phpMyAdmin)

**Problem: "Cannot connect to MySQL"**
- Start MySQL in XAMPP Control Panel
- Make sure it shows green indicator

**Problem: "Database not found"**
- Make sure you've already imported `database.sql`
- Database name should be: `nusantara_rental_car`

**Problem: "Column already exists"**
- This is now handled automatically!
- The new migration script checks before adding
- Safe to run multiple times

---

## Notes

- **Your existing data is safe** - This only adds new columns
- **No data will be lost** - It's an ALTER TABLE, not DROP/CREATE
- **Can run multiple times** - Script checks if columns exist first
- **Takes < 1 second** - Very quick operation

---

## Need Help?

If you encounter any issues:

1. Check XAMPP MySQL is running
2. Try Option 2 (phpMyAdmin) - it's the most reliable
3. Make sure database name is `nusantara_rental_car`
4. Check for typos in SQL commands

---

**After migration, test the password reset feature:**
1. Go to: http://localhost/NusantaraRentalCar/login.php
2. Click "Forgot Password?"
3. Enter an email (note: email won't send without SMTP, but form works!)

---

*Migration created: February 11, 2026*
