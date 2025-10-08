# Tenant Restrictions Plugin - Solution Summary

## Overview
This plugin restricts **Vendor Admin** (`vendor` role) and **Partner Manager** (`partner_manager` role) users to only see and manage courses within their assigned tenant categories.

## Problem Statement
In a Moodle 5.0 multi-tenant environment using the Multi Tenancy Tool (`admin/tool/mutenancy`):

1. **Course Management Page** (`/course/management.php`): Shows ALL categories from the entire Moodle site
2. **Course Creation Dropdown**: Shows ALL categories when creating a new course

**Required Behavior**: 
- Vendor Admin and Partner Manager should only see **their tenant's categories** (main category + subcategories)
- No access to other tenants' categories

## Solution Architecture

### 1. Tenant Detection
Uses the Multi Tenant Tool API:
```php
\tool_mutenancy\local\tenancy::get_user_tenantid($userid)
```

### 2. Category Filtering Strategy
- **Server-side**: Uses `tenant_helper` class to get allowed categories
- **Client-side**: JavaScript filtering to hide unauthorized categories from UI

### 3. Key Components

#### A. `tenant_helper.php`
- `get_user_tenant()`: Gets tenant information using Multi Tenant Tool API
- `get_allowed_categories()`: Returns tenant category + all subcategories
- `has_restricted_access()`: Checks if user is vendor or partner_manager
- `get_tenant_category()`: Gets the main tenant category ID

#### B. `page_restrictions.php` (NEW)
Two main methods:

**`filter_course_management_page()`**:
- Applies to `/course/management.php`
- Filters category lists, dropdowns, trees, and tables
- Uses JavaScript with MutationObserver for dynamic content
- Removes non-tenant categories from the UI

**`filter_course_edit_page()`**:
- Applies to `/course/edit.php`
- Filters category dropdown in course creation/editing form
- Auto-selects tenant category
- Removes non-tenant categories from dropdown

#### C. `course_form_observer.php`
- Observes `\core\event\course_edit_form_displayed` event
- Provides additional filtering for course edit forms
- Backup method for category dropdown filtering

### 4. Integration Points

#### Hook: `before_footer_html_generation`
Located in `classes/hook/before_footer_hook.php`:
```php
public static function handle(before_footer_html_generation $hook) {
    \local_tenant_restrictions\page_restrictions::filter_course_management_page();
    \local_tenant_restrictions\page_restrictions::filter_course_edit_page();
}
```

This hook:
- Runs before the page footer is generated
- Injects JavaScript for client-side filtering
- Applies to both course management and course edit pages

## How It Works

### For Course Management Page (`/course/management.php`)

1. **User Access**: Vendor Admin or Partner Manager navigates to `/course/management.php`
2. **Detection**: `tenant_helper::has_restricted_access()` returns `true`
3. **Category Retrieval**: Gets allowed categories (tenant + subcategories)
4. **JavaScript Injection**: Injects filtering code via `$PAGE->requires->js_amd_inline()`
5. **Client-side Filtering**: JavaScript:
   - Filters category lists and trees
   - Filters category dropdowns
   - Filters table rows
   - Uses MutationObserver to handle AJAX updates
   - Removes non-tenant categories from DOM

**Result**: User only sees their tenant's categories and courses

### For Course Creation/Edit Page (`/course/edit.php`)

1. **User Access**: Vendor Admin or Partner Manager creates/edits a course
2. **Detection**: `tenant_helper::has_restricted_access()` returns `true`
3. **Category Retrieval**: Gets allowed categories (tenant + subcategories)
4. **JavaScript Injection**: Injects filtering code for category dropdown
5. **Client-side Filtering**: JavaScript:
   - Removes non-tenant category options from `<select>`
   - Auto-selects tenant category if none selected
   - Re-applies filtering on dropdown focus

**Result**: Category dropdown shows only tenant categories + subcategories

## Technical Details

### Allowed Categories Logic
```php
public static function get_allowed_categories($userid = null) {
    $tenant = self::get_user_tenant($userid);
    $tenant_category_id = $tenant->categoryid;
    
    // SQL: Get main category + all subcategories
    $sql = "SELECT id FROM {course_categories} 
            WHERE path LIKE :path_pattern 
            OR id = :category_id
            ORDER BY path";
    
    // Returns: [108, 109, 110, ...] (tenant category + subcategories)
}
```

### Role Detection
```php
public static function has_restricted_access($userid = null) {
    $role = self::get_user_tenant_role($userid);
    
    // Returns true for 'vendor' and 'partner_manager' roles
    return in_array($role, ['vendor', 'partner_manager']);
}
```

## Benefits of This Approach

✅ **No Custom Pages**: Uses Moodle's core `/course/management.php` and `/course/edit.php`  
✅ **No Form Overrides**: No custom form classes that can break  
✅ **Clean Integration**: JavaScript-based filtering is non-intrusive  
✅ **Subcategory Support**: Tenant admins can create and manage subcategories  
✅ **Future-proof**: Works with Moodle's standard interfaces  
✅ **No Redirects**: No complex redirect logic that can cause errors  

## Testing

### Test Case 1: Course Management Page
1. Login as Vendor Admin (assigned to Tenant A with category ID 108)
2. Navigate to `/course/management.php`
3. **Expected**: Only see category 108 and its subcategories
4. **Not visible**: Other tenants' categories or site-level categories

### Test Case 2: Course Creation
1. Login as Partner Manager (assigned to Tenant B with category ID 200)
2. Click "Add new course"
3. Navigate to category dropdown
4. **Expected**: Only see category 200 and its subcategories
5. **Default selection**: Category 200 auto-selected

### Test Case 3: Course Editing
1. Login as Vendor Admin
2. Edit an existing course in their tenant
3. Navigate to category dropdown
4. **Expected**: Only see tenant categories
5. **Can change**: To subcategories within tenant

## Files Modified/Created

### New Files
- `classes/page_restrictions.php` - Main filtering logic

### Modified Files
- `classes/hook/before_footer_hook.php` - Hook integration
- `version.php` - Version 2025010724

### Existing Files (Unchanged)
- `classes/tenant_helper.php` - Tenant detection and category logic
- `classes/observers/course_form_observer.php` - Form observer (backup filtering)
- `db/events.php` - Event observers registration
- `db/hooks.php` - Hook callbacks registration

## Troubleshooting

### Issue: Categories still visible
**Solution**: Check browser console for JavaScript errors. Ensure jQuery is loaded.

### Issue: JavaScript not executing
**Solution**: Clear Moodle cache: `php admin/cli/purge_caches.php`

### Issue: Wrong tenant detected
**Solution**: Verify user is assigned to tenant in Multi Tenancy Tool settings.

### Debugging
Enable debugging to see console logs:
- `console.log("Tenant restrictions active - filtering categories")`
- `console.log("Allowed categories:", allowedCategories)`

## Compatibility
- **Moodle Version**: 5.0+
- **Dependencies**: Multi Tenancy Tool (`tool_mutenancy`)
- **PHP Version**: 7.4+
- **Browsers**: Modern browsers with JavaScript enabled

## Future Enhancements
- Server-side category filtering (override Moodle core functions)
- Capability-based restrictions
- Admin UI for configuring restricted roles
- Bulk tenant assignment tools

