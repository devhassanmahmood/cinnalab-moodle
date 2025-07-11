# Moodle S3 Setup on Heroku

This guide will help you set up Amazon S3 file storage for your Moodle installation on Heroku.

## Prerequisites

1. **AWS Account**: You need an AWS account with S3 access
2. **Heroku Account**: You need a Heroku account
3. **Heroku CLI**: Install the Heroku CLI
4. **Git**: Make sure you have Git installed

## Step 1: Create S3 Bucket

1. Log into your AWS Console
2. Navigate to S3 service
3. Click "Create bucket"
4. Choose a unique bucket name (e.g., `your-moodle-files`)
5. Select your preferred region
6. **Important**: Keep all default settings, especially "Block all public access" should be enabled
7. Click "Create bucket"

## Step 2: Create AWS IAM User

1. Go to AWS IAM service
2. Click "Users" → "Add user"
3. Enter a username (e.g., `moodle-s3-user`)
4. Select "Programmatic access"
5. Click "Next: Permissions"
6. Click "Attach existing policies directly"
7. Search for and select "AmazonS3FullAccess" (or create a custom policy with minimal permissions)
8. Complete the user creation
9. **Save the Access Key ID and Secret Access Key**

## Step 3: Configure S3 Bucket Policy

Create a bucket policy for your S3 bucket. Replace `your-bucket-name` with your actual bucket name:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["s3:ListBucket"],
      "Resource": ["arn:aws:s3:::your-bucket-name"]
    },
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject"
      ],
      "Resource": ["arn:aws:s3:::your-bucket-name/*"]
    }
  ]
}
```

## Step 4: Deploy to Heroku

### Option A: Using the deployment script (Recommended)

1. Make sure you're logged into Heroku:
   ```bash
   heroku login
   ```

2. Run the deployment script:
   ```bash
   ./deploy-s3.sh
   ```

3. Follow the prompts to enter your:
   - Heroku app name
   - AWS Access Key ID
   - AWS Secret Access Key
   - AWS region
   - S3 bucket name
   - Moodle site URL

### Option B: Manual deployment

1. Create a new Heroku app:
   ```bash
   heroku create your-app-name
   ```

2. Add PostgreSQL addon:
   ```bash
   heroku addons:create heroku-postgresql:mini
   ```

3. Set environment variables:
   ```bash
   heroku config:set AWS_ACCESS_KEY_ID="your-access-key"
   heroku config:set AWS_SECRET_ACCESS_KEY="your-secret-key"
   heroku config:set AWS_DEFAULT_REGION="your-region"
   heroku config:set AWS_S3_BUCKET="your-bucket-name"
   heroku config:set MOODLE_SITE_URL="https://your-app-name.herokuapp.com"
   ```

4. Deploy to Heroku:
   ```bash
   git add .
   git commit -m "Configure S3 for Moodle"
   git push heroku main
   ```

5. Configure S3 settings in Moodle:
   ```bash
   heroku run --app your-app-name bash -c "
   php admin/cli/cfg.php --component=tool_objectfs --name=enabletasks --set=1
   php admin/cli/cfg.php --component=tool_objectfs --name=deletelocal --set=1
   php admin/cli/cfg.php --component=tool_objectfs --name=consistencydelay --set=0
   php admin/cli/cfg.php --component=tool_objectfs --name=sizethreshold --set=0
   php admin/cli/cfg.php --component=tool_objectfs --name=minimumage --set=0
   php admin/cli/cfg.php --component=tool_objectfs --name=filesystem --set='\tool_objectfs\s3_file_system'
   php admin/cli/cfg.php --component=tool_objectfs --name=s3_key --set='your-access-key'
   php admin/cli/cfg.php --component=tool_objectfs --name=s3_secret --set='your-secret-key'
   php admin/cli/cfg.php --component=tool_objectfs --name=s3_bucket --set='your-bucket-name'
   php admin/cli/cfg.php --component=tool_objectfs --name=s3_region --set='your-region'
   php admin/cli/cfg.php --component=tool_objectfs --name=s3_bucket_acl --set=private
   php admin/cli/cfg.php --component=tool_objectfs --name=enablepresignedurls --set=1
   php admin/cli/cfg.php --component=tool_objectfs --name=expirationtime --set=7200
   php admin/cli/cfg.php --component=tool_objectfs --name=presignedminfilesize --set=0
   php admin/cli/cfg.php --component=tool_objectfs --name=signingmethod --set=s3
   "
   ```

## Step 5: Complete Moodle Installation

1. Visit your Heroku app URL
2. Complete the Moodle installation process
3. Log in as admin (default: admin/admin123)

## Step 6: Verify S3 Configuration

1. Go to **Site administration** → **Plugins** → **Object file system**
2. Verify that all S3 settings are configured correctly
3. Test file uploads to ensure S3 is working

## Step 7: Test File Uploads

1. Create a course
2. Add a file to the course
3. Check that the file is stored in your S3 bucket
4. Verify that files can be downloaded

## Troubleshooting

### Common Issues

1. **Files not uploading to S3**:
   - Check AWS credentials are correct
   - Verify bucket permissions
   - Check Moodle logs for errors

2. **Permission denied errors**:
   - Ensure your IAM user has the correct S3 permissions
   - Check bucket policy is applied correctly

3. **Configuration not saving**:
   - Make sure the objectfs plugin is installed
   - Check that `$CFG->alternative_file_system_class` is set in config.php

### Useful Commands

Check Heroku logs:
```bash
heroku logs --tail --app your-app-name
```

Check environment variables:
```bash
heroku config --app your-app-name
```

Run Moodle CLI commands:
```bash
heroku run --app your-app-name php admin/cli/maintenance.php --enable
```

## Security Considerations

1. **Never commit AWS credentials to Git**
2. **Use environment variables for sensitive data**
3. **Regularly rotate AWS access keys**
4. **Monitor S3 bucket access logs**
5. **Use IAM roles with minimal required permissions**

## Cost Optimization

1. **Use S3 Intelligent Tiering** for cost savings
2. **Set up lifecycle policies** to move old files to cheaper storage
3. **Monitor usage** with AWS CloudWatch
4. **Consider using CloudFront** for better performance

## Support

If you encounter issues:

1. Check the [Moodle documentation](https://docs.moodle.org/)
2. Review [objectfs plugin documentation](https://github.com/catalyst/moodle-tool_objectfs)
3. Check [Heroku logs](https://devcenter.heroku.com/articles/logging)
4. Verify [AWS S3 documentation](https://docs.aws.amazon.com/s3/) 