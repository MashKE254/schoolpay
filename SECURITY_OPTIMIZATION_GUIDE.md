# Security & Optimization Guide

## What Was Fixed

### ✅ Security Improvements

#### 1. **Environment Variables (.env)**
- **Problem**: API keys and database credentials were hardcoded in `config.php` and committed to version control
- **Fix**: Created `.env` file for sensitive credentials
- **Action Required**:
  ```bash
  # Copy .env.example to .env (already done)
  # Update .env with your production credentials
  # NEVER commit .env to git (it's now in .gitignore)
  ```

#### 2. **CSRF Protection**
- **Problem**: Forms were vulnerable to Cross-Site Request Forgery attacks
- **Fix**: Added CSRF token generation and validation in `security.php`
- **Usage**:
  ```php
  // In your forms, add:
  <?php echo csrf_field(); ?>

  // In form handlers, add:
  csrf_verify(); // Dies on failure
  // OR
  if (!csrf_verify(false)) {
      // Handle error
  }
  ```

#### 3. **Rate Limiting**
- **Problem**: No protection against brute force attacks on login or SMS spam
- **Fix**: Implemented session-based rate limiting
- **Current Limits**:
  - Login: 5 attempts per 5 minutes
  - SMS: Can be configured per endpoint
- **Usage**:
  ```php
  if (is_rate_limited('login', 5, 300)) {
      // Show error
  }
  ```

#### 4. **Secure Session Configuration**
- **Problem**: Default session settings were not secure
- **Fix**: Added `init_secure_session()` with:
  - HttpOnly cookies (XSS protection)
  - Secure cookies (HTTPS only in production)
  - SameSite=Strict (CSRF protection)
  - Session regeneration every 30 minutes
  - 2-hour session lifetime

#### 5. **Input Sanitization**
- **Problem**: Inconsistent input validation across the app
- **Fix**: Added helper functions in `security.php`:
  - `sanitize_input()` - HTML escaping
  - `validate_email()` - Email validation
  - `validate_phone()` - Phone number validation

#### 6. **Error Logging**
- **Problem**: No centralized error logging
- **Fix**: Added logging functions:
  - `log_error()` - General errors → `logs/error.log`
  - `log_security_event()` - Security events → `logs/security.log`

### ⚡ Performance Optimizations

#### 1. **Database Indexes**
- **Problem**: No indexes on frequently queried columns (slow queries with many records)
- **Fix**: Created `database_optimization.sql` with indexes on:
  - `students` (school_id, status, class_id)
  - `invoices` (school_id, student_id, status, due_date)
  - `payments` (school_id, payment_date, invoice_id)
  - And 15+ other tables

- **Apply Indexes**:
  ```bash
  mysql -u root -p school_finance < database_optimization.sql
  ```

- **Expected Results**:
  - Student queries: 10-50x faster
  - Invoice lookups: 20-100x faster
  - Payment history: 15-30x faster

#### 2. **PDO Improvements in config.php**
- Added `charset=utf8mb4` for proper emoji/unicode support
- `PDO::ATTR_EMULATE_PREPARES => false` for true prepared statements
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` for consistency

## Files Created

```
/schoolpay
├── .env                              # Environment variables (DO NOT COMMIT)
├── .env.example                      # Template for .env
├── .gitignore                        # Prevents committing sensitive files
├── env_loader.php                    # Loads .env into environment
├── security.php                      # Security helper functions
├── database_optimization.sql         # Database indexes
├── SECURITY_OPTIMIZATION_GUIDE.md   # This file
└── logs/                            # Log files directory
    ├── .gitkeep
    ├── error.log                    # General errors
    └── security.log                 # Security events
```

## Files Modified

1. **config.php** - Now uses environment variables
2. **login.php** - Added CSRF protection, rate limiting, and logging

## Next Steps

### Critical (Do Now)

1. **Apply Database Indexes**:
   ```bash
   mysql -u root -p school_finance < database_optimization.sql
   ```

2. **Update .env for Production**:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Set `SESSION_SECURE=true` (requires HTTPS)

3. **Add CSRF to All Forms**:
   - Every `<form>` needs: `<?php echo csrf_field(); ?>`
   - Every form handler needs: `csrf_verify();`
   - Priority files:
     - `customer_center.php` (39+ forms!)
     - `create_invoice.php`
     - `payroll.php`
     - `expense_management.php`
     - `banking.php`

### Important (Do Soon)

4. **Regenerate API Keys**:
   - Your current API keys are exposed in git history
   - Generate new keys from:
     - Africa's Talking dashboard
     - Safaricom Daraja portal
   - Update `.env` file

5. **Add HTTPS**:
   - Get SSL certificate (Let's Encrypt is free)
   - Enable `SESSION_SECURE=true` in `.env`

6. **Monitor Logs**:
   ```bash
   # Watch security events
   tail -f logs/security.log

   # Watch errors
   tail -f logs/error.log
   ```

### Nice to Have

7. **Add Rate Limiting to SMS Endpoints**:
   ```php
   // In customer_center.php (messaging section)
   if (is_rate_limited('sms', 20, 3600)) {
       $errors[] = "SMS limit reached. Max 20 messages per hour.";
   }
   ```

8. **Add Validation to All Inputs**:
   ```php
   $name = sanitize_input($_POST['name']);
   $email = sanitize_input($_POST['email']);

   if (!validate_email($email)) {
       $errors[] = 'Invalid email';
   }
   ```

9. **Implement Database Backups**:
   ```bash
   # Add to cron (daily backups)
   0 2 * * * mysqldump -u root school_finance | gzip > /backups/school_finance_$(date +\%Y\%m\%d).sql.gz
   ```

## Testing Checklist

### Security Testing

- [ ] Try logging in with wrong password 6 times → Should show rate limit error
- [ ] Try submitting form without CSRF token → Should fail
- [ ] Check `logs/security.log` → Should show failed login attempts
- [ ] Check `logs/error.log` → Should show any PHP errors

### Performance Testing

- [ ] Run database queries before indexes:
  ```sql
  EXPLAIN SELECT * FROM students WHERE school_id = 1 AND status = 'active';
  ```
- [ ] Apply indexes: `mysql -u root -p school_finance < database_optimization.sql`
- [ ] Run same query after indexes → Should show "Using index" in EXPLAIN
- [ ] Check query time improvement

### Functionality Testing

- [ ] Login works correctly
- [ ] Sessions persist correctly
- [ ] Forms submit successfully with CSRF tokens
- [ ] Rate limiting resets after time window

## Common Issues & Solutions

### Issue: "CSRF token validation failed"
**Solution**: Make sure every `<form method="post">` has `<?php echo csrf_field(); ?>`

### Issue: "Call to undefined function env()"
**Solution**: Make sure `env_loader.php` is required in `config.php` (it is)

### Issue: ".env file not found"
**Solution**: Copy `.env.example` to `.env`: `cp .env.example .env`

### Issue: Rate limit not resetting
**Solution**: Clear sessions: `rm -rf /tmp/sess_*` or restart PHP-FPM

### Issue: Logs directory permission denied
**Solution**: `chmod 755 logs/ && chmod 644 logs/*.log`

## Monitoring

### Check Security Logs
```bash
# View recent security events
tail -n 50 logs/security.log | jq .

# Count failed logins today
grep "Failed login" logs/security.log | grep "$(date +%Y-%m-%d)" | wc -l

# Find IPs with failed logins
grep "Failed login" logs/security.log | jq -r '.ip' | sort | uniq -c | sort -rn
```

### Check Performance
```sql
-- Show slow queries (enable slow query log first)
SHOW VARIABLES LIKE 'slow_query%';

-- Check index usage
SHOW INDEX FROM students;

-- Table sizes
SELECT
    table_name,
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)',
    table_rows
FROM information_schema.TABLES
WHERE table_schema = 'school_finance'
ORDER BY (data_length + index_length) DESC;
```

## Production Deployment Checklist

- [ ] .env file configured with production values
- [ ] APP_ENV=production, APP_DEBUG=false
- [ ] Database indexes applied
- [ ] HTTPS enabled with SSL certificate
- [ ] SESSION_SECURE=true in .env
- [ ] New API keys generated and updated
- [ ] Logs directory writable (chmod 755)
- [ ] .gitignore in place (don't commit .env!)
- [ ] Backups configured
- [ ] Security logs monitored

## Security Best Practices Going Forward

1. **Always use CSRF tokens** for any form that modifies data
2. **Always sanitize input** using `sanitize_input()`
3. **Always validate** email, phone, and other inputs
4. **Always use prepared statements** (you already do this ✓)
5. **Always log security events** for compliance
6. **Never commit** .env or config files with credentials
7. **Always use HTTPS** in production
8. **Regularly review** security logs for suspicious activity
9. **Keep PHP and dependencies** updated

## Contact & Support

If you encounter issues:
1. Check logs: `logs/error.log` and `logs/security.log`
2. Enable debug mode temporarily: `APP_DEBUG=true` in `.env`
3. Check PHP error logs: `tail -f /var/log/php-fpm/error.log`

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [MySQL Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
