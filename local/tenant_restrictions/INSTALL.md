# Installation Guide - Tenant Restrictions Plugin

## Prerequisites

1. **Moodle 4.4+** (tested with Moodle 5.0)
2. **Multi Tenant Tool Plugin** - Must be installed and configured
3. **Administrator Access** - To install and configure the plugin

## Installation Steps

### Step 1: Download and Extract

1. Download the plugin files
2. Extract to your Moodle `local/` directory
3. Ensure the directory structure is: `local/tenant_restrictions/`

### Step 2: Install via Moodle Admin

1. **Login as Administrator**
2. **Go to Site Administration > Notifications**
3. **Click "Upgrade Moodle database now"** if prompted
4. **Verify Installation** - You should see "Tenant Restrictions" in the plugin list

### Step 3: Configure Plugin Settings

1. **Go to Site Administration > Local plugins > Tenant Restrictions**
2. **Configure Settings**:
   - ✅ **Enable Tenant Restrictions**: Check this box
   - ✅ **Restrict Course Creation**: Check this box
   - ✅ **Restrict Category Access**: Check this box
   - ✅ **Add Manage Tenant Menu**: Check this box
3. **Save Changes**

### Step 4: Set Up User Roles

#### Create Tenant Roles (if not already done)

1. **Go to Site Administration > Users > Permissions > Define roles**
2. **Create New Role** for each tenant role:

##### Vendor Admin Role
- **Short name**: `vendor`
- **Display name**: `Vendor Admin`
- **Description**: `Tenant administrator with full access to tenant category`

##### Partner Manager Role
- **Short name**: `partner_manager`
- **Display name**: `Partner Manager`
- **Description**: `Can create and manage courses in tenant category`

##### Partner Role
- **Short name**: `partner`
- **Display name**: `Partner`
- **Description**: `Partner user with student-like access`

##### Partner Team Member Role
- **Short name**: `partner_team_member`
- **Display name**: `Partner Team Member`
- **Description**: `Team member with student-like access`

### Step 5: Assign Capabilities

#### For Vendor Admin Role
1. **Go to Site Administration > Users > Permissions > Define roles**
2. **Edit Vendor Admin role**
3. **Assign Capabilities**:
   - ✅ `local/tenant_restrictions:managetenant`
   - ✅ `local/tenant_restrictions:accesstenantcategory`

#### For Partner Manager Role
1. **Edit Partner Manager role**
2. **Assign Capabilities**:
   - ✅ `local/tenant_restrictions:createcourse`
   - ✅ `local/tenant_restrictions:managecourse`
   - ✅ `local/tenant_restrictions:accesstenantcategory`

### Step 6: Assign Roles to Users

1. **Go to Site Administration > Multi-tenancy > Tenants**
2. **For each tenant**:
   - **Assign Vendor Admin role** to tenant managers
   - **Assign Partner Manager role** to course creators
   - **Assign Partner roles** to regular users

### Step 7: Test the Installation

#### Run Plugin Test Script
1. **Login as Administrator**
2. **Navigate to**: `/local/tenant_restrictions/test_plugin.php`
3. **Review Test Results**: Check all components are working correctly

#### Test Vendor Admin Access
1. **Login as Vendor Admin user**
2. **Verify**:
   - ✅ "Manage Tenant" menu appears in navigation
   - ✅ Can only see tenant category in course creation
   - ✅ Cannot access other categories
   - ✅ Can create courses in tenant category

#### Test Partner Manager Access
1. **Login as Partner Manager user**
2. **Verify**:
   - ✅ Can create courses in tenant category only
   - ✅ Cannot access other categories
   - ✅ Can manage existing courses in tenant category

#### Test Partner Access
1. **Login as Partner user**
2. **Verify**:
   - ✅ Can access courses in tenant category
   - ✅ Cannot access other categories
   - ✅ Behaves like a student in courses

## Troubleshooting

### Common Issues

#### 1. Plugin Not Appearing in Admin
- **Check**: File permissions on `local/tenant_restrictions/`
- **Check**: Moodle version compatibility
- **Solution**: Ensure all files are properly uploaded

#### 2. "Multi Tenant Tool Required" Error
- **Check**: Multi Tenant Tool plugin is installed
- **Solution**: Install Multi Tenant Tool plugin first

#### 3. Users Can Still Access Other Categories
- **Check**: Capability assignments
- **Check**: Role assignments in tenant context
- **Solution**: Verify role assignments and capabilities

#### 4. "Manage Tenant" Menu Not Appearing
- **Check**: User has Vendor Admin role
- **Check**: `add_manage_tenant_menu` setting is enabled
- **Solution**: Verify role and capability assignments

#### 5. Course Creation Still Shows All Categories
- **Check**: `restrict_course_creation` setting is enabled
- **Check**: User has appropriate role
- **Solution**: Verify settings and role assignments

#### 6. Duplicate Admin Page Errors (FIXED)
- **Issue**: "Duplicate admin page name: local_tenant_restrictions"
- **Solution**: Updated settings.php to avoid duplicate admin page creation
- **Action**: Use the latest version of the plugin files

#### 7. Navigation Node Intersection Errors (FIXED)
- **Issue**: "Navigation node intersect: Adding a node that already exists"
- **Solution**: Navigation extension now checks for existing nodes before adding
- **Action**: Use the latest version of the plugin files

### Debug Mode

Enable debug mode to see detailed logging:

1. **Go to Site Administration > Development > Debugging**
2. **Enable Debug Messages**
3. **Check Logs** in `moodledata/temp/` for restriction enforcement

### Performance Considerations

- **Caching**: Plugin uses Moodle caching for tenant information
- **Database Queries**: Minimal additional queries for restriction checks
- **Event Handling**: Efficient event-based restriction enforcement

## Uninstallation

### Remove Plugin
1. **Go to Site Administration > Local plugins > Tenant Restrictions**
2. **Click "Uninstall"**
3. **Confirm Uninstallation**

### Clean Up
1. **Remove Role Assignments** if needed
2. **Update User Permissions** if needed
3. **Clear Caches** after uninstallation

## Support

For technical support:
1. **Check Moodle Logs** for error messages
2. **Verify Multi Tenant Tool** is working correctly
3. **Test with Debug Mode** enabled
4. **Contact System Administrator** for role/permission issues

## Version Compatibility

- **Moodle 4.4+**: Fully supported
- **Moodle 5.0**: Tested and recommended
- **Multi Tenant Tool**: Version 2025080950+ required

## Security Notes

- **Capabilities**: Plugin respects Moodle's capability system
- **Context**: All restrictions are applied at appropriate context levels
- **Audit**: All restriction enforcement is logged for security auditing
