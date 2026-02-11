# Nusantara Rental Car

A car rental website built with native PHP, JavaScript, and Bootstrap CSS using MySQL database.

## Features

### For Users
- **Browse Cars** - View all available cars with images, specifications, and daily rental prices
- **Advanced Filtering** - Filter cars by brand, seats, transmission type, fuel type, and price range
- **Car Details** - View detailed information including specifications and multiple images
- **Online Ordering** - Book cars directly through the website with date selection and delivery options
- **WhatsApp Ordering** - Quick order via WhatsApp for direct communication with the owner
- **AI Chat Assistant** - Intelligent chatbot with car recommendations and visual previews
- **Order History** - Track all your rental orders and their status
- **User Profile** - Manage your account information
- **Password Reset** - Forgot password functionality with email verification
- **Email Notifications** - Receive order confirmations and status updates via email
- **Persistent Chat** - Chat history saved across pages, cleared only on logout

### For Administrators
- **Dashboard** - Overview of orders, cars, users, and revenue statistics
- **Car Management** - Add, edit, and delete cars with multiple images
- **Order Management** - View and manage all orders (approve, cancel, complete)
- **Order Export** - Export orders to CSV for reporting
- **User Management** - View and manage registered users
- **Email Notifications** - Automatic email notifications for new orders and status updates
- **Site Settings** - Configure Gemini API key, WhatsApp number, admin email, and site address
- **AI Configuration** - Easy setup for Gemini AI chat feature

## Tech Stack

- **Backend:** PHP (Native)
- **Frontend:** HTML, CSS (Bootstrap 5), JavaScript
- **Database:** MySQL
- **Email:** PHPMailer (SMTP) with fallback to PHP mail()
- **Icons:** Font Awesome

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) - XAMPP recommended for local development
- **cURL extension enabled** (required for Gemini AI API)
- **Gemini API key** (optional, for AI chat - FREE from Google AI Studio)
- SMTP server or email service (optional, for email notifications)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/NusantaraRentalCar.git
```

### 2. Database Setup

1. Create a MySQL database named `nusantara_rental_car`
2. Import the database schema:

```bash
mysql -u root -p nusantara_rental_car < database/database.sql
```

Or import via phpMyAdmin:
- Open phpMyAdmin
- Create database `nusantara_rental_car`
- Import `database/database.sql`

### 3. Configuration

1. Copy the example database configuration:

```bash
cp config/database_example.php config/database.php
```

2. Edit `config/database.php` with your database credentials:

```php
$db_host = "localhost";
$db_user = "your_username";
$db_pass = "your_password";
$db_name = "nusantara_rental_car";
```

3. Update `config/config.php` with your site URL:

```php
define('SITE_URL', 'http://localhost/NusantaraRentalCar');
```

### 4. File Permissions

Ensure the uploads directory is writable:

```bash
chmod -R 755 uploads/
```

On Windows (XAMPP), this is usually not required.

### 5. Access the Website

- **Frontend:** `http://localhost/NusantaraRentalCar`
- **Admin Panel:** `http://localhost/NusantaraRentalCar/admin`

## Default Admin Account

- **Email:** admin@nusantararental.com
- **Password:** Admin@2024!

**Important:** Change this password after first login in production.

## Project Structure

```
NusantaraRentalCar/
├── admin/                  # Admin panel files
│   ├── includes/           # Admin header, footer, sidebar
│   ├── cars.php            # Car management
│   ├── orders.php          # Order management
│   ├── users.php           # User management
│   └── settings.php        # Site settings
├── api/                    # API endpoints
│   ├── cars.php            # Cars data
│   ├── chat.php            # AI chat
│   ├── filter.php          # Filter functionality
│   └── orders.php          # Order operations
├── assets/                 # Static assets
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   └── images/             # Static images
├── config/                 # Configuration files
│   ├── config.php          # Site configuration
│   └── database.php        # Database connection
├── database/               # Database files
│   └── database.sql        # Database schema
├── includes/               # Shared PHP includes
│   ├── auth.php            # Authentication functions
│   ├── email.php           # Email functions (PHPMailer)
│   ├── functions.php       # Helper functions
│   ├── header.php          # Public header
│   ├── footer.php          # Public footer
│   └── security.php        # Security functions
├── uploads/                # User uploaded files
│   └── cars/               # Car images
├── index.php               # Homepage
├── cars.php                # Car listing
├── car-detail.php          # Car details
├── order.php               # Order form
├── my-orders.php           # User orders
├── login.php               # Login page
├── register.php            # Registration page
├── profile.php             # User profile
├── forgot-password.php     # Password reset request
├── reset-password.php      # Password reset form
└── logout.php              # Logout handler
```

## Security Features

- Prepared statements for all database queries (SQL injection prevention)
- Password hashing with bcrypt
- CSRF token protection on forms
- Session security with HTTP-only cookies
- Input validation and sanitization
- XSS prevention with output encoding
- Security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection)
- **Output buffering** to prevent header errors
- Email verification system (database ready)
- Password reset with token expiration
- Secure API key storage in database

## AI Chat Feature

The AI chat assistant uses **Google Gemini 2.5 Flash** with visual car previews and automatic fallback to keyword matching.

### Features

**With Gemini AI (Recommended):**
- Natural language understanding in English and Indonesian
- Intelligent responses about cars in your database
- Context-aware recommendations based on user needs
- **Visual car previews** - Shows car images, prices, and details inline
- **Clickable car cards** - Direct links to car detail pages
- Answers complex questions about specifications
- 30-second response timeout for reliable performance

**Without API Key (Fallback):**
- Keyword-based responses
- Basic car recommendations
- Works offline
- No external dependencies

### Chat History Persistence
- **Session-based storage** - Chat history persists across page navigation
- **Auto-save** - Messages automatically saved to browser session
- **Smart cleanup** - History cleared only on logout
- **Seamless UX** - Users can switch pages without losing conversation

### Setup (Free & Optional)

1. **Get a free API key:**
   - Visit [Google AI Studio](https://aistudio.google.com/app/apikey)
   - Sign in with Google account
   - Click "Create API Key"
   - Copy the key

2. **Configure in Admin Panel:**
   - Login to admin panel (`/admin`)
   - Go to **Settings** → **Site Settings** tab
   - Paste your Gemini API key in the field
   - Click **Save Changes**

3. **Test the chat:**
   - Open the chat widget on the frontend
   - Ask questions like "Show me family cars" or "Cars with 7 seats"
   - The AI will respond with recommendations and show car images

### Rate Limits (Free Tier)
- **Model:** Gemini 2.5 Flash
- **15 requests per minute**
- **1,500 requests per day**
- **1 million token context window**
- **Cost:** FREE (no credit card required)
- **Auto-fallback** when limit exceeded (uses keyword matching)

### Troubleshooting
- Ensure cURL extension is enabled in `php.ini`
- Check error logs in `logs/chat_errors.log`
- Verify API key is valid in Settings
- Test with simple questions first

See [AI_CHAT_SETUP.md](AI_CHAT_SETUP.md) for detailed troubleshooting guide.

## Email Configuration

To enable email notifications:

1. Configure SMTP settings in `includes/email.php`:
   - SMTP host, port, username, password
   - Or use default PHP mail() function
2. Set admin email in site settings (admin panel)
3. Test email functionality with password reset feature

Note: If SMTP is not configured, the system will fall back to PHP's mail() function.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Create a Pull Request

## Documentation

- [DOCUMENTATION.md](DOCUMENTATION.md) - Detailed project documentation and coding standards
- [CHECKLIST.md](CHECKLIST.md) - Development progress checklist

## License

This project is for educational purposes.

## Support

For questions or issues, please open an issue on the repository.
