# Immediate S3 Upload Plugin

## Overview

This plugin automatically uploads files to S3 immediately when they are created in Moodle, preventing file loss on Heroku's ephemeral filesystem.

## Features

- ✅ Uploads files to S3 immediately upon creation
- ✅ Respects ObjectFS configuration (size threshold, minimum age)
- ✅ Works seamlessly with ObjectFS
- ✅ Prevents file loss on Heroku dyno restarts

## Installation

1. **The plugin files are already in place** at `local/immediate_s3_upload/`

2. **Upgrade Moodle** to install the plugin:
   - Go to: **Site administration → Notifications**
   - Click **Upgrade Moodle database now**
   - The plugin will be automatically installed

3. **Verify installation**:
   - Go to: **Site administration → Plugins → Local plugins**
   - You should see "Immediate S3 Upload" listed

## Configuration

The plugin automatically works with your existing ObjectFS configuration:

- **Minimum age = 0**: Files upload immediately ✅
- **Size threshold = 0**: All files are uploaded ✅
- **Enable tasks = 1**: Background tasks enabled ✅

No additional configuration needed!

## How It Works

1. When a file is uploaded to Moodle, the `file_created` event is triggered
2. This plugin intercepts the event
3. Immediately uploads the file to S3 via ObjectFS
4. Updates the file location to "DUPLICATED" (exists in both local and S3)

## Testing

1. Upload a new file to Moodle (e.g., a PDF or image)
2. Check ObjectFS status:
   ```sql
   SELECT 
       f.filename,
       CASE oo.location
           WHEN 0 THEN 'LOCAL'
           WHEN 1 THEN 'DUPLICATED ✅'
           WHEN 2 THEN 'EXTERNAL (S3) ✅'
       END as location
   FROM mdl_files f
   LEFT JOIN mdl_tool_objectfs_objects oo ON oo.contenthash = f.contenthash
   WHERE f.timemodified > EXTRACT(EPOCH FROM NOW()) - 60
   ORDER BY f.id DESC
   LIMIT 5;
   ```
3. The file should show as "DUPLICATED" or "EXTERNAL (S3)" immediately

## Troubleshooting

### Files still not uploading immediately

- Check ObjectFS settings: `minimumage` should be `0`
- Check ObjectFS settings: `sizethreshold` should be `0`
- Check ObjectFS settings: `enabletasks` should be `1`
- Verify S3 credentials are correct

### Check plugin is active

```sql
SELECT * FROM mdl_config_plugins WHERE plugin = 'local_immediate_s3_upload';
```

### View error logs

Check Heroku logs for upload errors:
```bash
heroku logs --tail --app cinnalab-moodle-production | grep -i "immediate\|s3\|upload"
```

## Requirements

- Moodle 4.4+
- ObjectFS plugin installed and configured
- S3 credentials configured in ObjectFS

## Support

If files are still not uploading:
1. Verify ObjectFS is working (check S3 bucket)
2. Check Heroku logs for errors
3. Ensure ObjectFS settings are optimized (minimumage=0, sizethreshold=0)

