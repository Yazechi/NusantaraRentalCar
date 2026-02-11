# Quick Start Guide - Admin Panel

## 🚀 Getting Started

### Step 1: Access Admin Panel
```
URL: http://localhost/NusantaraRentalCar/admin/index.php
```

### Step 2: Login with Default Credentials
```
Email: admin@nusantararental.com
Password: admin123
```

### Step 3: Change Your Password Immediately! ⚠️
1. Go to **Settings** → **Change Password**
2. Enter current password (admin123)
3. Set a strong new password
4. Confirm new password
5. Click "Update Password"

---

## 📋 Daily Tasks Checklist

### Dashboard
- ✅ View overall statistics
- ✅ See recent orders
- ✅ Quick access to main functions

### Cars Management
```
Cars → List Cars
├─ View all cars with pagination
├─ Using status (Available / Not Available)
├─ Edit car details
└─ Delete car from system

Cars → Add New Car
├─ Fill in car details
├─ Upload main image
├─ Set price per day
└─ Mark as available
```

**Adding a Car:**
1. Click "Add New Car" button
2. Fill all required fields (marked with *)
3. Select brand from dropdown
4. Enter price per day in Rupiah format
5. Upload image (JPG/PNG, max 2MB) - optional
6. Click "Add Car"

### Orders Management
```
Orders → All Orders
├─ Filter by status (All, Pending, Approved, Cancelled, Completed)
├─ View order summary
└─ Click "View" for details

Order Details → Update Status
├─ Change status: Pending → Approved → Completed
└─ Add notes about status change
```

**Processing an Order:**
1. View all orders
2. Click "View" on pending orders
3. Review order details:
   - Customer info
   - Car details
   - Rental dates
   - Delivery address
4. Click "Update Status"
5. Select new status
6. Add internal notes (optional)
7. Confirm update

### Users Management
```
Users
├─ View all registered users
├─ See user details (email, phone, address)
├─ Track their rental history
└─ Delete user if necessary
```

### Settings
```
Settings
├─ Profile: Update admin info
├─ Password: Change admin password
└─ System Info: View database stats
```

---

## ⚠️ Important Notes

### Security
- [ ] Change default admin password immediately
- [ ] Never share admin credentials
- [ ] Logout after each session
- [ ] Clear browser cookies on shared computers
- [ ] Enable browser cookies for proper session handling

### Database Considerations
- [ ] Always backup database before major changes
- [ ] Deleting users also deletes their orders
- [ ] Images deleted from server when car is deleted
- [ ] Reserved upload folder: `/uploads/cars/`

### Best Practices
- [ ] Use descriptive car names (e.g., "Toyota Avanza 1.5 Automatic")
- [ ] Keep car descriptions concise but informative
- [ ] Update car availability status accurately
- [ ] Review pending orders regularly
- [ ] Maintain accurate pricing

---

## 🔧 Troubleshooting

### Can't Login?
- [ ] Verify email is correct: `admin@nusantararental.com`
- [ ] Check CAPS LOCK is off
- [ ] If forgot password, contact database administrator
- [ ] Try clearing browser cache

### File Upload Errors
- [ ] Image must be JPG, PNG, or WebP
- [ ] File size must be less than 2MB
- [ ] Ensure `/uploads/cars/` folder has write permissions
- [ ] Check disk space on server

### Layout Issues
- [ ] Try refreshing page (Ctrl+F5)
- [ ] Test with Firefox/Chrome/Safari
- [ ] Check browser console for JavaScript errors
- [ ] Disable browser extensions if having problems

### Database Errors
- [ ] Verify MySQL server is running
- [ ] Check database credentials in `config/database.php`
- [ ] Verify `nusantara_rental_car` database exists
- [ ] Check user table has admin user

---

## 📞 Support

For issues or questions:
1. Check README.md for detailed documentation
2. Review error messages carefully
3. Check browser developer console (F12)
4. Contact system administrator

---

## 🔐 Security Reminders

- ✅ Always use strong passwords
- ✅ Session expires after 30 minutes of inactivity
- ✅ All forms protected against CSRF attacks
- ✅ User inputs sanitized to prevent XSS
- ✅ Passwords encrypted with bcrypt
- ✅ Database queries use prepared statements

---

## 📊 Key Metrics to Monitor

Track these regularly:
- Total registered users
- Active / available cars
- Pending orders (need approval)
- Revenue from completed orders
- Customer satisfaction

---

## 🎯 Common Workflows

### Daily Routine
```
1. Check Dashboard
2. Review pending orders
3. Update order statuses
4. Monitor car availability
5. Logout when done
```

### Adding New Vehicle
```
1. Go to Cars → Add New Car
2. Fill all details
3. Upload representative image
4. Set competitive pricing
5. Mark as available
6. Publish
```

### Processing Rental Request
```
1. View pending order
2. Verify customer details
3. Check car availability
4. Approve and set delivery date
5. Update status to "Approved"
6. Add delivery notes if needed
```

### Managing Customer Cancellation
```
1. Find order in Orders list
2. View order details
3. Click "Update Status"
4. Change to "Cancelled"
5. Add cancellation reason
6. Confirm
```

---

## 📅 Maintenance Checklist

Weekly:
- [ ] Review pending orders
- [ ] Check car availability status
- [ ] Monitor system performance

Monthly:
- [ ] Review customer feedback
- [ ] Update pricing if needed
- [ ] Backup database
- [ ] Check system logs

Quarterly:
- [ ] Review user statistics
- [ ] Analyze revenue trends
- [ ] Plan vehicle inventory updates
- [ ] Update documentation if needed

---

**Happy managing! 🎉**
