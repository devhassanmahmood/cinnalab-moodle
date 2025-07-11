#!/bin/bash

# Deploy and configure Moodle with S3 on Heroku

echo "🚀 Deploying Moodle with S3 to Heroku..."

# Check if Heroku CLI is installed
if ! command -v heroku &> /dev/null; then
    echo "❌ Heroku CLI is not installed. Please install it first:"
    echo "   https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

# Check if we're logged into Heroku
if ! heroku auth:whoami &> /dev/null; then
    echo "❌ Not logged into Heroku. Please run: heroku login"
    exit 1
fi

# Get app name from user
read -p "Enter your Heroku app name: " APP_NAME

# Create app if it doesn't exist
if ! heroku apps:info --app $APP_NAME &> /dev/null; then
    echo "📱 Creating new Heroku app: $APP_NAME"
    heroku create $APP_NAME
else
    echo "✅ App $APP_NAME already exists"
fi

# Set environment variables
echo "🔧 Setting up environment variables..."

# Get AWS credentials
read -p "Enter your AWS Access Key ID: " AWS_ACCESS_KEY
read -p "Enter your AWS Secret Access Key: " AWS_SECRET_KEY
read -p "Enter your AWS region (e.g., us-east-1): " AWS_REGION
read -p "Enter your S3 bucket name: " S3_BUCKET
read -p "Enter your Moodle site URL (e.g., https://$APP_NAME.herokuapp.com): " SITE_URL

# Set environment variables on Heroku
heroku config:set \
    AWS_ACCESS_KEY_ID="$AWS_ACCESS_KEY" \
    AWS_SECRET_ACCESS_KEY="$AWS_SECRET_KEY" \
    AWS_DEFAULT_REGION="$AWS_REGION" \
    AWS_S3_BUCKET="$S3_BUCKET" \
    MOODLE_SITE_URL="$SITE_URL" \
    MOODLE_DEBUG="false" \
    MOODLE_DEBUG_DISPLAY="false" \
    MOODLE_DEBUG_PAGEINFO="false" \
    --app $APP_NAME

# Add PostgreSQL addon if not already added
echo "🗄️ Setting up PostgreSQL database..."
heroku addons:create heroku-postgresql:mini --app $APP_NAME

# Deploy to Heroku
echo "📦 Deploying to Heroku..."
git add .
git commit -m "Configure S3 for Moodle"
git push heroku main

# Wait for deployment to complete
echo "⏳ Waiting for deployment to complete..."
sleep 30

# Configure S3 settings in Moodle
echo "⚙️ Configuring S3 settings in Moodle..."

# Run the S3 configuration commands
heroku run --app $APP_NAME bash -c "
php admin/cli/cfg.php --component=tool_objectfs --name=enabletasks --set=1
php admin/cli/cfg.php --component=tool_objectfs --name=deletelocal --set=1
php admin/cli/cfg.php --component=tool_objectfs --name=consistencydelay --set=0
php admin/cli/cfg.php --component=tool_objectfs --name=sizethreshold --set=0
php admin/cli/cfg.php --component=tool_objectfs --name=minimumage --set=0
php admin/cli/cfg.php --component=tool_objectfs --name=filesystem --set='\tool_objectfs\s3_file_system'
php admin/cli/cfg.php --component=tool_objectfs --name=s3_key --set='$AWS_ACCESS_KEY'
php admin/cli/cfg.php --component=tool_objectfs --name=s3_secret --set='$AWS_SECRET_KEY'
php admin/cli/cfg.php --component=tool_objectfs --name=s3_bucket --set='$S3_BUCKET'
php admin/cli/cfg.php --component=tool_objectfs --name=s3_region --set='$AWS_REGION'
php admin/cli/cfg.php --component=tool_objectfs --name=s3_bucket_acl --set=private
php admin/cli/cfg.php --component=tool_objectfs --name=enablepresignedurls --set=1
php admin/cli/cfg.php --component=tool_objectfs --name=expirationtime --set=7200
php admin/cli/cfg.php --component=tool_objectfs --name=presignedminfilesize --set=0
php admin/cli/cfg.php --component=tool_objectfs --name=signingmethod --set=s3
"

echo "✅ S3 configuration complete!"
echo "🌐 Your Moodle site is available at: $SITE_URL"
echo "🔧 Admin login: admin / admin123"
echo ""
echo "📋 Next steps:"
echo "1. Visit your Moodle site and complete the installation"
echo "2. Go to Site administration > Plugins > Object file system"
echo "3. Verify S3 settings are configured correctly"
echo "4. Test file uploads to ensure S3 is working" 