# Auth Module Documentation

## Overview

The `auth.php` file contains all centralized authentication, authorization, and security functions for the BookerPOS system. It should be included on every page that requires security checks.

## Quick Start

### Include in Your Page:

```php
<?php
session_start();
require_once 'auth.php';

// Check session timeout
checkSessionTimeout();

// Require login
requireLogin();

// Require specific role
requireRole('Admin');

// Your page code here
?>
```

---

## Available Functions

### Session Management

#### `isLoggedIn()`

Checks if user is currently logged in.

```php
if (isLoggedIn()) {
    echo "User is logged in";
} else {
    echo "User needs to login";
}
```

#### `checkSessionTimeout()`

Automatically checks and enforces 30-minute session timeout. Redirects to login if expired.

```php
checkSessionTimeout(); // Call at top of protected pages
```

---

### Authorization Functions

#### `requireLogin()`

Redirects to login page if user is not logged in.

```php
requireLogin(); // Protects page, allows any logged-in user
```

#### `requireRole($role)`

Requires specific role. Redirects to login if user doesn't have required role.

```php
requireRole('Admin');              // Requires Admin role
requireRole(['Admin', 'Cashier']); // Allows Admin OR Cashier
```

#### `hasRole($role)`

Checks if user has a specific role (returns boolean).

```php
if (hasRole('Admin')) {
    // Show admin controls
}

if (hasRole(['Admin', 'Manager'])) {
    // Show to both Admin and Manager
}
```

---

### User Information

#### `getCurrentUser()`

Gets currently logged-in username (HTML-escaped for safety).

```php
$username = getCurrentUser();
echo "Welcome, " . $username;
```

#### `getCurrentUserRole()`

Gets current user's role.

```php
$role = getCurrentUserRole();
if ($role === 'Admin') {
    // Show admin panel
}
```

---

### CSRF Protection

#### `generateCSRFToken()`

Creates and returns a CSRF token. Call once per page and store for form verification.

```php
$token = generateCSRFToken();
```

#### `verifyCSRFToken($token)`

Verifies CSRF token from POST request.

```php
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die("Security token invalid");
}
```

### Usage in HTML Form:

```html
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?php echo $token; ?>" />
  <!-- Other form fields -->
  <button type="submit">Submit</button>
</form>
```

---

### Activity Logging

#### `logActivity($action, $details = '', $userid = null)`

Logs user action for audit trail.

```php
logActivity('USER_CREATED', 'Username: admin, Role: Admin');
logActivity('PAYMENT_PROCESSED', 'Invoice: INV-001, Amount: 5000');
logActivity('PAGE_VIEW', 'Accessed Dashboard');
```

**Recommended Actions:**

- LOGIN_SUCCESS, LOGIN_FAILED
- LOGOUT
- USER_CREATED, USER_UPDATED, USER_DELETED
- PRODUCT_CREATED, PRODUCT_DELETED
- PAYMENT_PROCESSED
- PAGE_VIEW (for tracking access)

---

### Input Validation

#### `sanitizeInput($input)`

Sanitizes user input to prevent XSS attacks.

```php
$username = sanitizeInput($_POST['username']);
echo $username; // Safe to output
```

#### `isValidEmail($email)`

Validates email format.

```php
if (isValidEmail($email)) {
    // Email is valid
}
```

#### `isStrongPassword($password)`

Checks password strength (min 8 chars, uppercase, lowercase, numbers).

```php
if (!isStrongPassword($password)) {
    echo "Password too weak";
}
```

---

### File Upload Validation

#### `validateFileUpload($file, $allowedTypes = [], $maxSize = 5000000)`

Validates uploaded file.

```php
$validation = validateFileUpload($_FILES['image']);
if (!$validation['success']) {
    echo $validation['message']; // Error message
} else {
    // File is valid, process it
}

// With custom options:
$validation = validateFileUpload(
    $_FILES['document'],
    ['pdf', 'doc', 'docx'],
    10485760  // 10MB
);
```

---

### Rate Limiting

#### `checkRateLimit($identifier, $limit = 5, $window = 300)`

Implements rate limiting for repeated actions.

```php
if (!checkRateLimit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
    die("Too many attempts. Try again in 5 minutes");
}
// Process login attempt
```

**Parameters:**

- `$identifier`: Unique key for rate limiting (e.g., IP address, user ID)
- `$limit`: Max attempts allowed (default: 5)
- `$window`: Time window in seconds (default: 300)

---

## Complete Example

### Protected Admin Page:

```php
<?php
session_start();
require_once 'dbConnection.php';
require_once 'auth.php';

// Security checks
checkSessionTimeout();
requireRole('Admin');

$username = getCurrentUser();
$csrf_token = generateCSRFToken();

// Log page access
logActivity('PAGE_VIEW', 'Accessed Reports', $username);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid";
    } else {
        $report_type = sanitizeInput($_POST['report_type'] ?? '');

        if (empty($report_type)) {
            $error = "Report type required";
        } else {
            // Process report
            logActivity('REPORT_GENERATED', "Report: $report_type", $username);
            $success = "Report generated successfully";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>
</head>
<body>
    <h1>Welcome, <?php echo $username; ?></h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <select name="report_type" required>
            <option value="">Select Report</option>
            <option value="sales">Sales Report</option>
            <option value="users">Users Report</option>
        </select>
        <button type="submit">Generate</button>
    </form>

    <a href="logout.php">Logout</a>
</body>
</html>
```

---

## Error Handling

All functions throw exceptions on database errors. Wrap in try-catch:

```php
try {
    requireRole('Admin');
    // Continue with page logic
} catch (Exception $e) {
    error_log($e->getMessage());
    die("An error occurred. Please try again.");
}
```

---

## Security Best Practices

1. **Always include auth.php on protected pages**

   ```php
   require_once 'auth.php';
   ```

2. **Always verify CSRF tokens on POST requests**

   ```php
   if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
       die("Invalid request");
   }
   ```

3. **Sanitize all user input**

   ```php
   $input = sanitizeInput($_POST['field']);
   ```

4. **Log important actions**

   ```php
   logActivity('ACTION_NAME', 'Details', $username);
   ```

5. **Use prepared statements (already done in dbConnection.php)**

   ```php
   $stmt = prepareStatement("SELECT * FROM users WHERE id = ?");
   $stmt->bind_param('i', $id);
   ```

6. **Always check session timeout**
   ```php
   checkSessionTimeout();
   ```

---

## Common Patterns

### Admin-Only Page:

```php
<?php
session_start();
require_once 'auth.php';
checkSessionTimeout();
requireRole('Admin');
// Rest of page
```

### User-Accessible (Any Logged-In User):

```php
<?php
session_start();
require_once 'auth.php';
checkSessionTimeout();
requireLogin();
// Rest of page
```

### Specific Roles:

```php
<?php
session_start();
require_once 'auth.php';
checkSessionTimeout();
requireRole(['Admin', 'Manager']); // Allows either role
// Rest of page
```

---

## Troubleshooting

### "Security token invalid" error

- Make sure CSRF token is in form: `<input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">`
- Check token is passed in POST: `verifyCSRFToken($_POST['csrf_token'] ?? '')`

### User keeps getting logged out

- Session timeout is 30 minutes by default
- Modify in auth.php: `$timeout = 1800;` (in seconds)

### "Unauthorized" redirect

- Check user role: `getCurrentUserRole()`
- Verify role matches requirement: `requireRole('Admin')`

### Password validation failing

- Password must be: 8+ chars, uppercase, lowercase, number
- Example valid: `MyPassword123`

---

**Last Updated**: 2026-07-01
**Version**: 1.0
