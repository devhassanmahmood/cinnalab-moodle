# Update User Information API Endpoint

## Overview
This document describes the new `local_customapi_update_user_info` endpoint that allows you to update user information in Moodle.

---

## 🚀 Quick API Reference

### Update User Information
**Endpoint:** `/api/user/update`

**Parameters:**
- `user_id` (User ID) - **Required**
- `firstname` (First Name) - Optional
- `lastname` (Last Name) - Optional
- `email` (Email Address) - Optional
- `idnumber` (ID Number) - Optional
- `phone1` (Phone Number 1) - Optional
- `phone2` (Phone Number 2) - Optional
- `institution` (Institution) - Optional
- `department` (Department) - Optional
- `address` (Address) - Optional
- `city` (City) - Optional
- `country` (Country Code - 2 letters) - Optional
- `description` (User Description) - Optional

### Moodle API END Point:

**API URL:** `https://cinnalab-moodle-d1962611ca61.herokuapp.com/webservice/rest/server.php`

**Method:** `POST`

### Request (POST form data):

**Example 1: Update Email and Name**
```
wstoken=f14f5c28b529c65551bd07f17c5bc552
wsfunction=local_customapi_update_user_info
moodlewsrestformat=json
user_id=123
email=john.doe@example.com
firstname=John
lastname=Doe
```

**Example 2: Update Phone and Department**
```
wstoken=f14f5c28b529c65551bd07f17c5bc552
wsfunction=local_customapi_update_user_info
moodlewsrestformat=json
user_id=123
phone1=+1-555-0100
department=Engineering
```

**Example 3: Update All Fields**
```
wstoken=f14f5c28b529c65551bd07f17c5bc552
wsfunction=local_customapi_update_user_info
moodlewsrestformat=json
user_id=123
firstname=Jane
lastname=Smith
email=jane.smith@example.com
idnumber=EMP12345
phone1=+1-555-0100
phone2=+1-555-0200
institution=Tech Corp
department=IT Department
address=123 Main Street
city=San Francisco
country=US
description=Senior Developer
```

### Response:

**Success:**
```json
{
  "success": true,
  "user_id": 123,
  "updated_fields": [
    "firstname",
    "lastname",
    "email"
  ],
  "message": "User information updated successfully"
}
```

**Error - User Not Found:**
```json
{
  "exception": "moodle_exception",
  "errorcode": "usernotfound",
  "message": "The specified user was not found."
}
```

**Error - Email Already Exists:**
```json
{
  "exception": "moodle_exception",
  "errorcode": "emailexists",
  "message": "Email address john.doe@example.com is already in use by another user."
}
```

**Error - ID Number Already Exists:**
```json
{
  "exception": "moodle_exception",
  "errorcode": "idnumberexists",
  "message": "ID number EMP12345 is already in use by another user."
}
```

**Error - No Fields Provided:**
```json
{
  "exception": "moodle_exception",
  "errorcode": "noupdatefields",
  "message": "No fields were provided to update."
}
```

### cURL Example:
```bash
curl -X POST "https://cinnalab-moodle-d1962611ca61.herokuapp.com/webservice/rest/server.php" \
  -d "wstoken=f14f5c28b529c65551bd07f17c5bc552" \
  -d "wsfunction=local_customapi_update_user_info" \
  -d "moodlewsrestformat=json" \
  -d "user_id=123" \
  -d "email=newemail@example.com" \
  -d "firstname=John" \
  -d "lastname=Doe"
```

### Important Notes:
- **Only send fields you want to update** - all fields except `user_id` are optional
- Email must be unique across the site
- ID number must be unique if provided
- Country must be a 2-letter ISO code (e.g., US, GB, CA)
- Requires `moodle/user:update` capability

---

## Endpoint Details

**Function Name:** `local_customapi_update_user_info`  
**Type:** Write  
**Capability Required:** `moodle/user:update`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | int | Yes | The Moodle user ID to update |
| `firstname` | string | No | First name of the user |
| `lastname` | string | No | Last name of the user |
| `email` | string | No | Email address (must be unique) |
| `idnumber` | string | No | ID number (must be unique if provided) |
| `phone1` | string | No | Primary phone number |
| `phone2` | string | No | Secondary phone number |
| `institution` | string | No | Institution name |
| `department` | string | No | Department name |
| `address` | string | No | Street address |
| `city` | string | No | City |
| `country` | string | No | Country code (e.g., "US", "GB") |
| `description` | string | No | User description/bio |

## Response

```json
{
    "success": true,
    "user_id": 123,
    "updated_fields": [
        "firstname",
        "email",
        "phone1"
    ],
    "message": "User information updated successfully"
}
```

## Features

### ✅ Partial Updates
- You only need to pass the fields you want to update
- All fields except `user_id` are optional
- Only the provided fields will be updated

### ✅ Validation
- **User Existence:** Validates that the user exists and is not deleted
- **Email Uniqueness:** Checks if email is already in use by another user
- **ID Number Uniqueness:** Checks if idnumber is already in use by another user
- **Empty Fields Check:** Returns error if no fields are provided to update

### ✅ Event Triggering
- Triggers Moodle's `user_updated` event after successful update
- Allows other plugins to respond to user changes

### ✅ Security
- Requires `moodle/user:update` capability
- Uses Moodle's standard parameter validation
- Uses context validation for security

## Usage Examples

### Example 1: Update Email and Name

**REST API Call:**
```bash
curl -X POST "https://your-moodle-site.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_customapi_update_user_info" \
  -d "moodlewsrestformat=json" \
  -d "user_id=123" \
  -d "email=newemail@example.com" \
  -d "firstname=John" \
  -d "lastname=Doe"
```

### Example 2: Update Only Phone Number

**REST API Call:**
```bash
curl -X POST "https://your-moodle-site.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_customapi_update_user_info" \
  -d "moodlewsrestformat=json" \
  -d "user_id=123" \
  -d "phone1=+1-555-0100"
```

### Example 3: Update Multiple Fields

**JavaScript/React Example:**
```javascript
const updateUserInfo = async (userId, updates) => {
  const formData = new FormData();
  formData.append('wstoken', 'YOUR_TOKEN');
  formData.append('wsfunction', 'local_customapi_update_user_info');
  formData.append('moodlewsrestformat', 'json');
  formData.append('user_id', userId);
  
  // Add only the fields you want to update
  Object.keys(updates).forEach(key => {
    formData.append(key, updates[key]);
  });
  
  const response = await fetch(
    'https://your-moodle-site.com/webservice/rest/server.php',
    {
      method: 'POST',
      body: formData
    }
  );
  
  return response.json();
};

// Usage
updateUserInfo(123, {
  firstname: 'Jane',
  lastname: 'Smith',
  email: 'jane.smith@example.com',
  phone1: '+1-555-0200',
  department: 'Engineering',
  city: 'New York'
}).then(result => {
  console.log('Updated fields:', result.updated_fields);
}).catch(error => {
  console.error('Error:', error);
});
```

### Example 4: PHP Example (Direct Call)
```php
<?php
require_once($CFG->dirroot . '/local/customapi/externallib.php');

// Update user information
$result = local_customapi_external::update_user_info(
    123,                           // user_id
    'John',                        // firstname
    'Doe',                         // lastname
    'john.doe@example.com',        // email
    'EMP12345',                    // idnumber
    '+1-555-0100',                 // phone1
    null,                          // phone2
    'Tech Corp',                   // institution
    'IT Department',               // department
    '123 Main St',                 // address
    'San Francisco',               // city
    'US',                          // country
    'Senior Developer'             // description
);

print_r($result);
?>
```

## Error Handling

### Common Errors

| Error Code | Message | Cause |
|------------|---------|-------|
| `usernotfound` | The specified user was not found. | User ID doesn't exist or user is deleted |
| `emailexists` | Email address {email} is already in use by another user. | Email is already registered to another user |
| `idnumberexists` | ID number {idnumber} is already in use by another user. | ID number is already assigned to another user |
| `noupdatefields` | No fields were provided to update. | No optional parameters were provided |
| `nopermission` | You do not have permission to update users | User doesn't have `moodle/user:update` capability |

### Error Response Example
```json
{
    "exception": "moodle_exception",
    "errorcode": "emailexists",
    "message": "Email address john@example.com is already in use by another user."
}
```

## Installation & Setup

### 1. Purge Caches
After adding this endpoint, you need to purge Moodle caches:

**Via Admin Interface:**
- Go to: Site administration → Development → Purge caches
- Click "Purge all caches"

**Via CLI:**
```bash
php admin/cli/purge_caches.php
```

### 2. Verify Web Service Registration
- Go to: Site administration → Server → Web services → External services
- Look for "Custom API for React Integration"
- Click "Functions" and verify `local_customapi_update_user_info` is listed

### 3. Grant Capability
Ensure your API user has the `moodle/user:update` capability:
- Go to: Site administration → Users → Permissions → Define roles
- Edit the role your API user has
- Search for "moodle/user:update" and enable it

## Testing

### Quick Test Script
Create a test file: `local/customapi/test_update_user.php`

```php
<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/customapi/externallib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$userid = 2; // Change to a valid user ID

try {
    $result = local_customapi_external::update_user_info(
        $userid,
        'Updated First',    // firstname
        'Updated Last',     // lastname
        null,               // email (not updating)
        null,               // idnumber (not updating)
        '+1-555-9999'      // phone1
    );
    
    echo '<pre>';
    print_r($result);
    echo '</pre>';
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
```

Access: `https://your-moodle-site.com/local/customapi/test_update_user.php`

## Best Practices

1. **Always validate data on the client side** before sending to the API
2. **Only send fields you want to update** - don't send null values unnecessarily
3. **Handle errors gracefully** - check for error responses and display user-friendly messages
4. **Log API calls** - especially for auditing user information changes
5. **Use HTTPS** - always use secure connections when transmitting user data
6. **Rate limiting** - implement rate limiting on your client to prevent abuse

## Security Considerations

- The endpoint requires authentication via Moodle's web service token
- Only users with `moodle/user:update` capability can use this endpoint
- Email and ID number uniqueness is enforced
- All inputs are validated using Moodle's parameter validation
- User update events are triggered for audit logging
- Deleted users cannot be updated

## Related Endpoints

- `local_customapi_create_user_in_tenant` - Create new users
- `local_customapi_add_user_to_tenant` - Add users to tenants
- `local_customapi_add_user_by_role` - Assign roles to users

## Changelog

### Version 1.0 (2025-11-11)
- Initial implementation
- Support for updating 12 user fields
- Email and ID number uniqueness validation
- Partial update support (only update provided fields)
- Event triggering for user updates

## Support

For issues or questions, please contact your Moodle administrator or development team.

