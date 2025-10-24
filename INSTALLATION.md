# Installation Guide - Intuitive Job XML Feed Generator

## Quick Start

### Step 1: Upload Plugin

1. Upload `job-xml-feed.php` to your WordPress `/wp-content/plugins/` directory
2. Or create a folder `job-xml-feed` and place the file inside it

### Step 2: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "Intuitive Job XML Feed Generator"
3. Click "Activate"

### Step 3: Configure Settings

1. Go to Settings → Job XML Feed
2. Set your job post type (default: 'job')
3. Set maximum jobs in feed (default: 1000)
4. Save settings

### Step 4: Test Feed

1. Visit: `https://yoursite.com/jobs-feed.xml`
2. Verify XML output is valid

## Detailed Installation

### Method 1: Manual Upload

1. **Download the plugin file**

    - Get `job-xml-feed.php` from this repository

2. **Upload to WordPress**

    ```
    /wp-content/plugins/job-xml-feed/
    └── job-xml-feed.php
    ```

3. **Activate the plugin**
    - Go to WordPress Admin → Plugins
    - Find "Intuitive Job XML Feed Generator"
    - Click "Activate"

### Method 2: FTP Upload

1. **Connect to your server via FTP**
2. **Navigate to WordPress directory**
3. **Upload to plugins folder**
    ```
    /public_html/wp-content/plugins/job-xml-feed/
    └── job-xml-feed.php
    ```
4. **Activate through WordPress admin**

### Method 3: WordPress Admin Upload

1. **Go to Plugins → Add New**
2. **Click "Upload Plugin"**
3. **Choose the plugin file**
4. **Click "Install Now"**
5. **Activate the plugin**

## Post-Installation Setup

### 1. Configure Plugin Settings

Navigate to **Settings → Job XML Feed** and configure:

-   **Job Post Type**: The custom post type containing your jobs
-   **Maximum Jobs**: Limit the number of jobs in the XML feed

### 2. Set Up Job Post Type

If you don't have a job post type, create one:

```php
// Add this to your theme's functions.php or create a custom plugin
function create_job_post_type() {
    register_post_type('job', array(
        'labels' => array(
            'name' => 'Jobs',
            'singular_name' => 'Job',
            'add_new' => 'Add New Job',
            'add_new_item' => 'Add New Job',
            'edit_item' => 'Edit Job',
            'new_item' => 'New Job',
            'view_item' => 'View Job',
            'search_items' => 'Search Jobs',
            'not_found' => 'No jobs found',
            'not_found_in_trash' => 'No jobs found in trash'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
        'menu_icon' => 'dashicons-businessman',
        'show_in_rest' => true
    ));
}
add_action('init', 'create_job_post_type');
```

### 3. Add Required Meta Fields

Your job posts need these meta fields. You can add them manually or programmatically:

#### Manual Method (WordPress Admin):

1. Go to your job post
2. Add custom fields:
    - `_job_country` = "US"
    - `_job_city` = "New York"
    - `_job_state` = "NY"
    - `_job_company` = "Your Company"
    - `_job_postal_code` = "10001"

#### Programmatic Method:

```php
// When creating/updating a job
update_post_meta($job_id, '_job_country', 'US');
update_post_meta($job_id, '_job_city', 'San Francisco');
update_post_meta($job_id, '_job_state', 'CA');
update_post_meta($job_id, '_job_company', 'Tech Corp');
update_post_meta($job_id, '_job_postal_code', '94105');
```

### 4. Test the Feed

1. **Check feed URL**: `https://yoursite.com/jobs-feed.xml`
2. **Verify XML structure**:
    ```xml
    <?xml version="1.0" encoding="UTF-8"?>
    <jobs>
        <job>
            <referenceID>123</referenceID>
            <title>Job Title</title>
            <description><![CDATA[Job description...]]></description>
            <country>US</country>
            <city>New York</city>
            <state>NY</state>
            <postalCode>10001</postalCode>
            <datePosted>2024-01-15</datePosted>
            <validThrough>2024-02-15</validThrough>
            <hiringOrganization>Company Name</hiringOrganization>
            <url>https://yoursite.com/job/123/</url>
        </job>
    </jobs>
    ```

## Troubleshooting

### Feed Not Working

1. **Check permalinks**:

    - Go to Settings → Permalinks
    - Save changes to flush rewrite rules

2. **Verify plugin activation**:

    - Check that plugin is active in Plugins page

3. **Check feed URL**:
    - Visit: `https://yoursite.com/jobs-feed.xml`
    - Should show XML content, not 404 error

### Jobs Not Appearing in Feed

1. **Check required meta fields**:

    - Ensure all required fields are filled
    - Use the sample-data.php file as reference

2. **Verify post type**:

    - Check that your job post type matches plugin settings
    - Default is 'job'

3. **Check post status**:
    - Ensure jobs are published, not draft

### XML Validation Errors

1. **Check for special characters**:

    - Remove or encode special characters in job titles/descriptions

2. **Verify meta field values**:
    - Country should be 2-letter code (US, CA, etc.)
    - State should be 2-letter abbreviation (NY, CA, etc.)
    - Postal code should be 5 digits

## Advanced Configuration

### Custom Meta Field Names

If you use different meta field names, you can map them:

```php
// Add to functions.php
add_filter('job_xml_feed_meta_mapping', function($mapping) {
    return array(
        'country' => '_custom_country_field',
        'city' => '_custom_city_field',
        'state' => '_custom_state_field',
        'company' => '_custom_company_field',
        'postal_code' => '_custom_zip_field'
    );
});
```

### Custom Post Type Integration

For different post types:

1. Go to Settings → Job XML Feed
2. Change "Job Post Type" to your custom post type
3. Save settings

### Bulk Update Existing Jobs

Use the sample-data.php file to bulk update existing jobs:

```php
// Run this once to update existing jobs
$updated_count = update_existing_jobs_with_meta();
echo "Updated {$updated_count} jobs with required meta fields";
```

## Security Considerations

1. **File permissions**: Ensure plugin files have proper permissions (644 for files, 755 for directories)

2. **Access control**: The feed is publicly accessible, but admin settings are protected

3. **Data sanitization**: All output is properly sanitized and encoded

## Performance Optimization

1. **Limit jobs**: Set a reasonable maximum in plugin settings
2. **Caching**: Consider adding caching for large job feeds
3. **Database optimization**: Ensure proper indexing on meta fields

## Support

If you encounter issues:

1. Check WordPress error logs
2. Verify all required meta fields are present
3. Test with a simple job post first
4. Contact plugin author for support

## Uninstallation

To remove the plugin:

1. **Deactivate** the plugin in WordPress admin
2. **Delete** the plugin files from `/wp-content/plugins/`
3. **Optional**: Remove plugin settings from database:
    ```sql
    DELETE FROM wp_options WHERE option_name = 'job_xml_feed_settings';
    ```
