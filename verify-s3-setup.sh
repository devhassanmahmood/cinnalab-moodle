#!/bin/bash

# Verify S3 setup for Moodle on Heroku

echo "🔍 Verifying S3 setup for Moodle..."

# Check if we're in the right directory
if [ ! -f "config.php" ]; then
    echo "❌ Error: config.php not found. Please run this script from the Moodle root directory."
    exit 1
fi

# Check if objectfs plugin is installed
if [ ! -d "admin/tool/objectfs" ]; then
    echo "❌ Error: objectfs plugin not found. Please install the objectfs plugin first."
    exit 1
fi

# Check config.php for S3 configuration
echo "📋 Checking config.php configuration..."
if grep -q "alternative_file_system_class.*tool_objectfs" config.php; then
    echo "✅ S3 file system class is configured in config.php"
else
    echo "❌ S3 file system class is not configured in config.php"
    echo "   Add: \$CFG->alternative_file_system_class = '\tool_objectfs\s3_file_system';"
fi

# Check if Heroku CLI is installed
if command -v heroku &> /dev/null; then
    echo "✅ Heroku CLI is installed"
else
    echo "❌ Heroku CLI is not installed"
    echo "   Install from: https://devcenter.heroku.com/articles/heroku-cli"
fi

# Check if logged into Heroku
if heroku auth:whoami &> /dev/null; then
    echo "✅ Logged into Heroku"
else
    echo "❌ Not logged into Heroku"
    echo "   Run: heroku login"
fi

# Check if app.json exists
if [ -f "app.json" ]; then
    echo "✅ app.json exists"
else
    echo "❌ app.json not found"
fi

# Check if deployment script exists
if [ -f "deploy-s3.sh" ]; then
    echo "✅ Deployment script exists"
else
    echo "❌ Deployment script not found"
fi

# Check if S3 setup documentation exists
if [ -f "S3-SETUP.md" ]; then
    echo "✅ S3 setup documentation exists"
else
    echo "❌ S3 setup documentation not found"
fi

echo ""
echo "📋 Next steps:"
echo "1. Create an S3 bucket in AWS"
echo "2. Create an IAM user with S3 permissions"
echo "3. Run: ./deploy-s3.sh"
echo "4. Follow the prompts to deploy to Heroku"
echo ""
echo "📖 For detailed instructions, see: S3-SETUP.md" 