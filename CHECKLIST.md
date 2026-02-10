# Nusantara Rental Car - Development Checklist

Use this checklist to track development progress. Mark items with [x] when completed.

---

## Phase 1: Setup and Konfiguration

### Database

- [x] Create database.sql schema
- [x] Test database import
- [x] Verify all tables created correctly

### Configuration Files

- [x] config/database.php - Database connection
- [x] config/config.php - Site settings and constants

### Shared Includes

- [x] includes/functions.php - Helper functions
- [x] includes/auth.php - Authentication functions
- [x] includes/header.php - Public header with navigation
- [x] includes/footer.php - Public footer

---

## Phase 2: Authentication System (Member 1)

### User Authentication

- [x] login.php - Login form and validation
- [x] register.php - Registration form and validation
- [x] logout.php - Session destruction
- [x] profile.php - User profile view and edit

### Testing

- [ ] Test user registration
- [ ] Test user login
- [ ] Test user logout
- [ ] Test profile update

---

## Phase 3: Public Car Display (Member 2)

### Car Listing

- [ ] index.php - Homepage with featured cars
- [ ] cars.php - All cars listing page
- [ ] car-detail.php - Single car detail page

### Filter System

- [ ] api/filter.php - Filter API endpoint
- [ ] assets/js/filter.js - Filter JavaScript functionality
- [ ] Implement brand filter
- [ ] Implement seats filter
- [ ] Implement transmission filter
- [ ] Implement price range filter

### API

- [ ] api/cars.php - Cars data endpoint

### Styling

- [ ] assets/css/style.css - Main styles

### Testing

- [ ] Test car listing display
- [ ] Test filter functionality
- [ ] Test car detail page

---

## Phase 4: Order System (Member 3)

### Order Pages

- [ ] order.php - Order form page
- [ ] my-orders.php - User order history

### Order Features

- [ ] Website order form
- [ ] WhatsApp redirect order
- [ ] Rental date selection
- [ ] Duration calculation
- [ ] Delivery option selection
- [ ] Price calculation

### API

- [ ] api/orders.php - Orders endpoint

### JavaScript

- [ ] assets/js/order.js - Order form handling

### Testing

- [ ] Test website order creation
- [ ] Test WhatsApp redirect
- [ ] Test order history display

---

## Phase 5: AI Chatbox (Member 3)

### Chat System

- [ ] api/chat.php - Chat API endpoint
- [ ] assets/js/chatbox.js - Chatbox UI and logic

### Features

- [ ] Chat UI component
- [ ] Message sending
- [ ] AI response handling
- [ ] Car recommendations based on user needs

### Testing

- [ ] Test chat interface
- [ ] Test AI responses
- [ ] Test car recommendations

---

## Phase 6: Admin Panel (Member 4)

### Admin Setup

- [ ] admin/includes/header.php - Admin header
- [ ] admin/includes/footer.php - Admin footer
- [ ] admin/includes/sidebar.php - Admin navigation
- [ ] admin/index.php - Admin login redirect
- [ ] admin/dashboard.php - Admin dashboard

### Car Management

- [ ] admin/cars.php - List all cars
- [ ] admin/car-add.php - Add new car form
- [ ] admin/car-edit.php - Edit car form
- [ ] admin/car-delete.php - Delete car handler
- [ ] Image upload functionality

### Order Management

- [ ] admin/orders.php - List all orders
- [ ] admin/order-detail.php - Order details view
- [ ] admin/order-update.php - Update order status
- [ ] Filter orders by status
- [ ] Filter orders by type (website/whatsapp)

### User Management

- [ ] admin/users.php - List all users

### Settings

- [ ] admin/settings.php - Site settings management

### Styling

- [ ] assets/css/admin.css - Admin styles
- [ ] assets/js/admin.js - Admin JavaScript

### Testing

- [ ] Test admin login
- [ ] Test car CRUD operations
- [ ] Test order management
- [ ] Test user listing
- [ ] Test settings update

---

## Phase 7: Final Integration and Testing

### Integration

- [ ] Connect all components
- [ ] Test complete user flow
- [ ] Test complete admin flow

### Security

- [ ] Input validation on all forms (server-side)
- [ ] SQL injection prevention (use prepared statements)
- [ ] Verify all queries use prepared statements
- [ ] XSS prevention (htmlspecialchars on all output)
- [ ] CSRF tokens on all forms
- [ ] Password hashing with password_hash()
- [ ] Session security (regenerate ID on login)
- [ ] Session timeout implementation
- [ ] File upload validation (type, size, rename)
- [ ] Access control on protected pages
- [ ] Admin role verification on admin pages
- [ ] HTTP security headers added
- [ ] Error logging configured (no errors shown to users)
- [ ] Database user with limited permissions

### UI/UX

- [ ] Responsive design testing
- [ ] Cross-browser testing
- [ ] Error message handling
- [ ] Loading states

### Final

- [ ] Code cleanup
- [ ] Final testing
- [ ] Documentation update

---

## Progress Summary

| Phase            | Status      | Assigned To |
| ---------------- | ----------- | ----------- |
| Phase 1: Setup   | Completed   | All         |
| Phase 2: Auth    | In Progress | Member 1    |
| Phase 3: Cars    | Not Started | Member 2    |
| Phase 4: Orders  | Not Started | Member 3    |
| Phase 5: AI Chat | Not Started | Member 3    |
| Phase 6: Admin   | Not Started | Member 4    |
| Phase 7: Final   | Not Started | All         |

---

## Notes

- Update this checklist after completing each task
- Communicate with team members when dependencies are met
- Test each feature before marking as complete
- IMPORTANT: Always use prepared statements for all database queries
