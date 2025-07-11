# Configuration Guide - What You Need to Change

Before deploying to Heroku, you need to update the following values:

## 1. Update `app.json`

**Change this line:**
```json
"repository": "https://github.com/YOUR_USERNAME/YOUR_REPO_NAME"
```

**To your actual GitHub repository URL:**
```json
"repository": "https://github.com/your-actual-username/your-actual-repo-name"
```

## 2. Update `config.php`

**Change this line:**
```php
$CFG->wwwroot   = getenv('MOODLE_SITE_URL') ?: 'https://YOUR_APP_NAME.herokuapp.com';
```

**To your actual Heroku app URL:**
```php
$CFG->wwwroot   = getenv('MOODLE_SITE_URL') ?: 'https://your-actual-app-name.herokuapp.com';
```

## 3. Values You'll Provide During Deployment

When you run `./deploy-s3.sh`, you'll be prompted for:

- **Heroku app name**: Your Heroku app name (e.g., `my-moodle-app`)
- **AWS Access Key ID**: From your AWS IAM user
- **AWS Secret Access Key**: From your AWS IAM user  
- **AWS region**: Your S3 bucket region (e.g., `us-east-1`, `eu-west-1`)
- **S3 bucket name**: Your S3 bucket name (e.g., `my-moodle-files`)
- **Moodle site URL**: Your Heroku app URL (e.g., `https://my-moodle-app.herokuapp.com`)

## 4. Optional: Update Admin Email

In `app.json`, you can change the admin email:

```json
"postdeploy": "php admin/cli/install_database.php --agree-license --adminpass=admin123 --adminemail=YOUR_EMAIL@example.com --fullname=Admin --shortname=admin"
```

## 5. Optional: Change Admin Password

In `app.json`, you can change the admin password:

```json
"postdeploy": "php admin/cli/install_database.php --agree-license --adminpass=YOUR_PASSWORD --adminemail=admin@example.com --fullname=Admin --shortname=admin"
```

## Quick Setup Checklist

- [ ] Update `app.json` repository URL
- [ ] Update `config.php` default site URL
- [ ] Create S3 bucket in AWS
- [ ] Create IAM user with S3 permissions
- [ ] Get AWS credentials ready
- [ ] Run `./deploy-s3.sh`

## Example Values

Here's an example of what your values might look like:

**app.json:**
```json
"repository": "https://github.com/johndoe/moodle-s3-deployment"
```

**config.php:**
```php
$CFG->wwwroot   = getenv('MOODLE_SITE_URL') ?: 'https://my-moodle-lms.herokuapp.com';
```

**Deployment prompts:**
- Heroku app name: `my-moodle-lms`
- AWS Access Key ID: `AKIAIOSFODNN7EXAMPLE`
- AWS Secret Access Key: `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY`
- AWS region: `us-east-1`
- S3 bucket name: `my-moodle-files`
- Moodle site URL: `https://my-moodle-lms.herokuapp.com` 