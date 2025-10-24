# Intuitive Job XML Feed Generator

A WordPress plugin that generates XML feeds for job listings compatible with Intuitive requirements. This plugin is designed to be reusable across multiple WordPress sites.

## Features

-   ✅ **Intuitive Compatible**: Meets all Intuitive XML feed requirements
-   ✅ **Reusable**: Can be used across multiple WordPress sites
-   ✅ **Admin Interface**: Easy configuration through WordPress admin
-   ✅ **Data Validation**: Validates required fields before including jobs
-   ✅ **Security**: Proper sanitization and error handling
-   ✅ **Flexible**: Configurable post type and job limits
-   ✅ **Clean Output**: Removes phone numbers and external URLs from descriptions

## Installation

1. Upload the `job-xml-feed.php` file to your WordPress plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Job XML Feed to configure the plugin

## Configuration

### Admin Settings

Navigate to **Settings > Job XML Feed** in your WordPress admin to configure:

-   **Job Post Type**: The custom post type containing your job listings (default: 'job')
-   **Maximum Jobs in Feed**: Limit the number of jobs in the XML feed (default: 1000)

### Required Meta Fields

Your job posts must have these meta fields for the XML feed to work properly:

#### Required Fields:

-   `_job_country` - Two-letter ISO country code (e.g., "US", "CA")
-   `_job_city` - City name (must be USPS valid)
-   `_job_state` - Two-letter state abbreviation (e.g., "NY", "CA")
-   `_job_company` - Company name
-   `_job_postal_code` or `_job_zip` - 5-digit USPS postal code

#### Optional Fields:

-   `_job_expire_date` - Job expiration date (YYYY-MM-DD format)
-   `_job_type` - Job type (e.g., "Full-time", "Part-time", "Contract")
-   `_job_category` - Job category matching ZipRecruiter categories
-   `_job_is_remote` - Boolean value for remote work (true/false)

## XML Feed Structure

The plugin generates XML feeds at: `https://yoursite.com/jobs-feed.xml`

### Sample XML Output:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<jobs>
    <job>
        <referenceID>123</referenceID>
        <title>Software Developer</title>
        <description><![CDATA[We are looking for a skilled software developer...]]></description>
        <country>US</country>
        <city>New York</city>
        <state>NY</state>
        <postalCode>10001</postalCode>
        <datePosted>2024-01-15</datePosted>
        <validThrough>2024-02-15</validThrough>
        <hiringOrganization>Tech Company Inc.</hiringOrganization>
        <url>https://yoursite.com/job/software-developer/</url>
        <jobType>Full-time</jobType>
        <category>Technology</category>
        <isRemote>false</isRemote>
    </job>
</jobs>
```

## Usage Examples

### Adding Job Meta Fields

```php
// When creating/updating a job post
update_post_meta($job_id, '_job_country', 'US');
update_post_meta($job_id, '_job_city', 'San Francisco');
update_post_meta($job_id, '_job_state', 'CA');
update_post_meta($job_id, '_job_company', 'Tech Corp');
update_post_meta($job_id, '_job_postal_code', '94105');
update_post_meta($job_id, '_job_type', 'Full-time');
update_post_meta($job_id, '_job_category', 'Technology');
update_post_meta($job_id, '_job_is_remote', true);
```

### Custom Post Type Integration

If you're using a different post type for jobs:

1. Go to Settings > Job XML Feed
2. Change the "Job Post Type" field to your custom post type
3. Save settings

### Programmatic Access

```php
// Get the feed URL
$feed_url = home_url('/jobs-feed.xml');

// Check if feed is working
$response = wp_remote_get($feed_url);
if (!is_wp_error($response)) {
    $xml_content = wp_remote_retrieve_body($response);
    // Process XML content
}
```

## Data Validation

The plugin automatically validates that jobs have all required fields before including them in the XML feed. Jobs missing required fields are excluded from the feed.

### Validation Rules:

-   Country must be a valid 2-letter ISO code
-   City must be provided and not empty
-   State must be a valid 2-letter abbreviation
-   Company name must be provided
-   Postal code should be 5 digits (USPS format)

## Security Features

-   **Input Sanitization**: All text fields are properly sanitized
-   **XML Encoding**: Special characters are properly encoded
-   **Error Handling**: Graceful error handling with proper XML error responses
-   **Access Control**: Admin settings are protected with proper capabilities

## Troubleshooting

### Feed Not Working

1. Check that permalinks are enabled in WordPress
2. Go to Settings > Permalinks and save to flush rewrite rules
3. Verify the feed URL: `https://yoursite.com/jobs-feed.xml`

### Jobs Not Appearing

1. Ensure jobs have all required meta fields
2. Check that jobs are published (not draft)
3. Verify the post type setting matches your job post type

### XML Validation Errors

1. Check for special characters in job titles/descriptions
2. Ensure all required fields are properly filled
3. Check WordPress error logs for specific error messages

## Customization

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Modify job query arguments
add_filter('job_xml_feed_query_args', function($args) {
    // Add custom meta query
    $args['meta_query'][] = array(
        'key' => '_custom_field',
        'value' => 'custom_value'
    );
    return $args;
});

// Modify job data before XML generation
add_filter('job_xml_feed_job_data', function($job_data, $job) {
    // Add custom processing
    return $job_data;
}, 10, 2);
```

### Custom Meta Field Mapping

To use different meta field names, you can extend the plugin:

```php
// Add this to your theme's functions.php or a custom plugin
add_filter('job_xml_feed_meta_mapping', function($mapping) {
    return array(
        'country' => '_custom_country_field',
        'city' => '_custom_city_field',
        // ... other mappings
    );
});
```

## Requirements

-   WordPress 5.0 or higher
-   PHP 7.4 or higher
-   Custom post type for jobs (or compatible post type)

## Support

For support and customization requests, please contact the plugin author.

## Changelog

### Version 2.0

-   Complete rewrite with object-oriented structure
-   Added admin interface
-   Improved data validation
-   Enhanced security features
-   Added optional field support
-   Better error handling

### Version 1.0

-   Initial release
-   Basic XML feed generation
-   Simple rewrite rules

## License

This plugin is released under the GPL v2 or later license.
