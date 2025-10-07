# Tenant Restrictions Plugin

A Moodle local plugin that enforces tenant-level restrictions on user roles for multi-tenant Moodle installations using the "Multi Tenant Tool" plugin.

## Features

- **Role-based Access Control**: Enforces restrictions based on tenant user roles (Vendor Admin, Partner Manager, Partner, Partner Team Member)
- **Category Filtering**: Restricts course creation and management to tenant categories only
- **Navigation Management**: Dynamically adds "Manage Tenant" menu for Vendor Admin users
- **Breadcrumb Filtering**: Removes access to "Manage Categories" links outside tenant scope
- **Event-based Restrictions**: Uses Moodle events to enforce restrictions in real-time

## User Roles

### 1. Vendor Admin (slug: vendor)
- Can only access their assigned tenant and tenant category
- Cannot access other course categories
- Can only create courses under their tenant category
- Gets a dynamically generated "Manage Tenant" navigation item
- Cannot manage categories themselves

### 2. Partner Manager (slug: partner_manager)
- Can create courses and manage existing courses under the tenant category
- Cannot manage categories
- Restricted to tenant category only when creating or managing courses

### 3. Partner (slug: partner)
- Behaves like a student in courses

### 4. Partner Team Member (slug: partner_team_member)
- Behaves like a student

## Installation

1. **Prerequisites**: Ensure you have the "Multi Tenant Tool" plugin installed and configured
2. **Upload Plugin**: Copy the plugin files to `local/tenant_restrictions/`
3. **Install**: Go to Site Administration > Notifications and install the plugin
4. **Configure**: Go to Site Administration > Local plugins > Tenant Restrictions
5. **Set Permissions**: Assign the appropriate capabilities to your tenant roles

## Configuration

### Settings

- **Enable Tenant Restrictions**: Master switch for the plugin
- **Restrict Course Creation**: Limits course creation to tenant categories
- **Restrict Category Access**: Prevents access to categories outside tenant
- **Add Manage Tenant Menu**: Adds navigation menu for Vendor Admin users

### Capabilities

The plugin defines the following capabilities:

- `local/tenant_restrictions:managetenant` - Manage tenant (Vendor Admin)
- `local/tenant_restrictions:createcourse` - Create courses in tenant (Partner Manager)
- `local/tenant_restrictions:managecourse` - Manage courses in tenant (Partner Manager)
- `local/tenant_restrictions:accesstenantcategory` - Access tenant category only

## Usage

### For Administrators

1. **Install the Plugin**: Follow the installation steps above
2. **Configure Settings**: Adjust plugin settings as needed
3. **Assign Capabilities**: Ensure tenant roles have appropriate capabilities
4. **Test Restrictions**: Verify that tenant users can only access their assigned categories

### For Vendor Admin Users

1. **Access Manage Tenant**: Use the "Manage Tenant" menu item in navigation
2. **View Tenant Info**: See tenant information and category details
3. **Manage Courses**: Access course management within tenant category
4. **Edit Category**: Modify tenant category settings

### For Partner Manager Users

1. **Create Courses**: Can create courses only in tenant category
2. **Manage Courses**: Can edit and manage existing courses in tenant category
3. **Restricted Access**: Cannot access categories outside tenant

## Technical Details

### File Structure

```
local/tenant_restrictions/
├── version.php                    # Plugin version information
├── lib.php                        # Main library functions and hooks
├── settings.php                   # Plugin settings
├── manage_tenant.php              # Manage tenant page
├── db/
│   ├── access.php                 # Capabilities definition
│   └── events.php                 # Event observers
├── classes/
│   ├── tenant_helper.php          # Tenant helper functions
│   ├── navigation_extension.php   # Navigation extensions
│   └── event_observers.php        # Event observers
└── lang/en/
    └── local_tenant_restrictions.php  # Language strings
```

### Key Functions

- `tenant_helper::get_user_tenant()` - Get user's tenant information
- `tenant_helper::get_user_tenant_role()` - Get user's role in tenant
- `tenant_helper::has_restricted_access()` - Check if user has restrictions
- `tenant_helper::get_allowed_categories()` - Get allowed category IDs
- `tenant_helper::can_access_category()` - Check category access

### Event Observers

- `course_created` - Validates course creation in allowed categories
- `course_category_viewed` - Redirects unauthorized category access
- `course_viewed` - Redirects unauthorized course access

## Troubleshooting

### Common Issues

1. **Plugin Not Working**: Ensure Multi Tenant Tool is installed and active
2. **Users Can Access Other Categories**: Check capability assignments
3. **Navigation Menu Missing**: Verify Vendor Admin role and capabilities
4. **Redirects Not Working**: Check event observers are registered

### Debug Mode

Enable debug mode to see detailed logging of restriction enforcement.

## Support

For issues and support, please contact your system administrator or refer to the Moodle documentation for local plugins.

## License

This plugin is licensed under the GNU GPL v3 or later.
