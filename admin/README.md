# Admin Panel Documentation

## Overview

Admin panel untuk Nusantara Rental Car yang menyediakan fitur manajemen lengkap untuk:

- Mobil (CRUD)
- Orders (lihat, update status)
- Users (lihat, delete)
- Settings (profil admin, password management)

## Struktur File

### admin/includes/

- `header.php` - HTML head, navbar, dan proteksi autentikasi
- `sidebar.php` - Navigasi admin
- `footer.php` - Closing layout

### admin/ (Main Pages)

- `index.php` - Login page untuk admin
- `dashboard.php` - Dashboard dengan statistik
- `cars.php` - List semua mobil + delete
- `car-add.php` - Tambah mobil baru
- `car-edit.php` - Edit detail mobil
- `car-delete.php` - Handler delete (redirect)
- `orders.php` - List semua orders dengan filter status
- `order-detail.php` - Lihat detail order
- `order-update.php` - Update status order
- `users.php` - Manage users (lihat, delete)
- `settings.php` - Profil admin, password, info sistem

## Autentikasi & Security

### Login Requirements

1. User harus login sebagai `admin` (role = 'admin' di database)
2. Session timeout 30 menit (dapat diubah di config/config.php)
3. CSRF token pada setiap form

### Session Management

- Semua halaman admin diproteksi via `admin/includes/header.php`
- Redirect otomatis ke login jika:
  - User belum login
  - User adalah regular user (bukan admin)
  - Session sudah expired

### Default Admin Credentials

Email: `admin@nusantararental.com`
Password: `admin123` (harus diubah saat pertama login!)

⚠️ **PENTING**: Ubah password admin di halaman settings setelah login pertama kali!

## Core Dependencies

### config/config.php

- Session configuration
- Database path constant
- Upload path settings
- Security headers

### config/database.php

- MySQL connection

### includes/auth.php

- `login_user()` - Login function
- `logout_user()` - Logout function
- `is_admin()` - Check if user is admin
- `require_admin()` - Protect page (redirect jika tidak admin)
- `get_logged_in_user()` - Get user data

### includes/functions.php

- `redirect()` - Redirect ke URL
- `set_flash_message()` - Set flash message
- `display_flash_message()` - Display flash
- `format_currency()` - Format Rp
- `format_date()` - Format tanggal
- `get_status_badge()` - Badge for order status
- Dan function helper lainnya

### includes/security.php

- `generate_csrf_token()` - CSRF token generation
- `validate_csrf_token()` - CSRF validation
- `csrf_input_field()` - HTML input untuk CSRF token
- `sanitize_output()` - XSS prevention via htmlspecialchars
- `validate_email()` - Email validation
- `validate_int()` - Integer validation
- `validate_image_upload()` - Image validation
- `upload_image()` - Image upload handler

## CSS & JavaScript

### assets/css/admin.css

- Styling lengkap untuk admin panel
- Responsive design (mobile-friendly)
- Dark theme navbar
- Custom stat cards
- Table styling
- Modal styling
- Form styling

### assets/js/admin.js

- Auto-hide alerts
- Counter animation
- Form validation
- Image preview handler
- Table search/filter
- Export to CSV
- Print page
- Utility functions

## Database Tables Used

```
users           - id, name, email, password, phone, address, role, created_at, updated_at
cars            - id, brand_id, name, model, year, license_plate, seats, transmission, fuel_type,
                  price_per_day, description, specifications, image_main, is_available, created_at, updated_at
car_brands      - id, name, created_at
car_images      - id, car_id, image_path, is_primary, created_at
orders          - id, user_id, car_id, order_type, rental_start_date, rental_end_date, duration_days,
                  delivery_option, delivery_address, total_price, status, notes, created_at, updated_at
```

## API Endpoints Integration

Admin panel terintegrasi dengan API endpoints di `/api/`:

- `api/cars.php` - Car data API
- `api/orders.php` - Order data API
- `api/filter.php` - Filter/search API

(Catatan: API endpoints harus diimplementasikan secara terpisah)

## Usage Examples

### Login to Admin

1. Buka browser: `http://localhost/NusantaraRentalCar/admin/index.php`
2. Login dengan admin credentials
3. Password akan di-hash menggunakan PASSWORD_DEFAULT (bcrypt)

### Add New Car

1. Dashboard → Cars Management → Add Car
2. Isi form dengan detail mobil
3. Upload gambar utama (opsional, max 2MB)
4. Submit

### Manage Orders

1. Dashboard → Orders Management → All Orders
2. Lihat statistik orders di atas table
3. Filter by status (Pending, Approved, Cancelled, Completed)
4. Klik "View" untuk lihat detail
5. Klik "Update Status" untuk ubah status order

### Manage Users

1. Dashboard → Users
2. View user details
3. Delete user (akan menghapus orders terkait juga)

## Security Best Practices

1. **Always sanitize output**: Gunakan `sanitize_output()` untuk HTML output
2. **Validate input**: Gunakan `validate_*()` functions
3. **Use prepared statements**: Semua query sudah menggunakan prepared statements
4. **CSRF protection**: Semua form memiliki CSRF token
5. **Session timeout**: Auto logout setelah 30 menit inactivity
6. **Password hashing**: Gunakan PASSWORD_DEFAULT (bcrypt)
7. **Error logging**: Errors di-log ke error_log, bukan ditampilkan ke user

## Troubleshooting

### Session Expired / Unable to Access Admin

- Cek di browser console apakah ada error
- Verify database connection
- Cek SESSION_TIMEOUT setting di config/config.php
- Clear browser cookies

### Upload Image Failed

- Cek permissions folder `uploads/cars/`
- Verify MAX_FILE_SIZE di config/config.php
- Check image format (JPG, PNG, WebP accepted)

### Database Connection Error

- Verify credentials di config/database.php
- Check MySQL server running
- Verify database exists: `nusantara_rental_car`

### CSRF Token Error

- Ensure session.cookie_httponly = 1
- Check if cookies enabled in browser
- Verify session is started

## Customize

### Change Admin Colors

Edit `assets/css/admin.css`, ganti CSS custom properties:

```css
:root {
  --primary-color: #667eea;
  --secondary-color: #764ba2;
  /* ... dll */
}
```

### Change Session Timeout

Edit `config/config.php`:

```php
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
```

### Add New Admin User

Insert ke database:

```sql
INSERT INTO users (name, email, password, role)
VALUES ('Admin Name', 'email@domain.com', '$2y$10$...', 'admin');
```

Password harus di-hash dengan `password_hash($pass, PASSWORD_DEFAULT)`

## Future Enhancements

Potential improvements:

- [ ] Advanced dashboard analytics
- [ ] Bulk operations (import/export)
- [ ] Admin activity logging
- [ ] Multi-language support
- [ ] Email notifications
- [ ] Advanced reporting

---

Last Updated: February 2026
