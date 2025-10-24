<?php
/**
 * Sample Data for Job XML Feed Plugin
 * 
 * This file contains example code for creating job posts with proper meta fields
 * that are compatible with the Intuitive Job XML Feed Generator plugin.
 * 
 * Use this as a reference when setting up your job data structure.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sample function to create a job post with all required meta fields
 */
function create_sample_job_post()
{
    // Create the job post
    $job_data = array(
        'post_title' => 'Senior Software Developer',
        'post_content' => '<p>We are looking for an experienced software developer to join our team.</p>
                          <p><strong>Requirements:</strong></p>
                          <ul>
                              <li>5+ years of experience in web development</li>
                              <li>Proficiency in PHP, JavaScript, and MySQL</li>
                              <li>Experience with WordPress development</li>
                              <li>Strong problem-solving skills</li>
                          </ul>
                          <p><strong>Benefits:</strong></p>
                          <ul>
                              <li>Competitive salary</li>
                              <li>Health insurance</li>
                              <li>Flexible work hours</li>
                          </ul>',
        'post_status' => 'publish',
        'post_type' => 'job',
        'post_author' => 1
    );

    $job_id = wp_insert_post($job_data);

    if ($job_id && !is_wp_error($job_id)) {
        // Add required meta fields
        update_post_meta($job_id, '_job_country', 'US');
        update_post_meta($job_id, '_job_city', 'San Francisco');
        update_post_meta($job_id, '_job_state', 'CA');
        update_post_meta($job_id, '_job_company', 'Tech Solutions Inc.');
        update_post_meta($job_id, '_job_postal_code', '94105');

        // Add optional meta fields
        update_post_meta($job_id, '_job_type', 'Full-time');
        update_post_meta($job_id, '_job_category', 'Technology');
        update_post_meta($job_id, '_job_is_remote', false);
        update_post_meta($job_id, '_job_expire_date', '2024-03-15');

        return $job_id;
    }

    return false;
}

/**
 * Sample function to create multiple job posts
 */
function create_sample_job_posts()
{
    $sample_jobs = array(
        array(
            'title' => 'Frontend Developer',
            'content' => '<p>Join our frontend team and work on cutting-edge web applications.</p>',
            'country' => 'US',
            'city' => 'New York',
            'state' => 'NY',
            'company' => 'Digital Agency LLC',
            'postal_code' => '10001',
            'job_type' => 'Full-time',
            'category' => 'Technology',
            'is_remote' => true
        ),
        array(
            'title' => 'Marketing Manager',
            'content' => '<p>Lead our marketing efforts and drive growth for our company.</p>',
            'country' => 'US',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'company' => 'Growth Marketing Co.',
            'postal_code' => '90210',
            'job_type' => 'Full-time',
            'category' => 'Marketing',
            'is_remote' => false
        ),
        array(
            'title' => 'Data Analyst',
            'content' => '<p>Analyze data and provide insights to help drive business decisions.</p>',
            'country' => 'US',
            'city' => 'Chicago',
            'state' => 'IL',
            'company' => 'Analytics Pro',
            'postal_code' => '60601',
            'job_type' => 'Contract',
            'category' => 'Data & Analytics',
            'is_remote' => true
        )
    );

    $created_jobs = array();

    foreach ($sample_jobs as $job_data) {
        $post_data = array(
            'post_title' => $job_data['title'],
            'post_content' => $job_data['content'],
            'post_status' => 'publish',
            'post_type' => 'job',
            'post_author' => 1
        );

        $job_id = wp_insert_post($post_data);

        if ($job_id && !is_wp_error($job_id)) {
            // Add required meta fields
            update_post_meta($job_id, '_job_country', $job_data['country']);
            update_post_meta($job_id, '_job_city', $job_data['city']);
            update_post_meta($job_id, '_job_state', $job_data['state']);
            update_post_meta($job_id, '_job_company', $job_data['company']);
            update_post_meta($job_id, '_job_postal_code', $job_data['postal_code']);

            // Add optional meta fields
            update_post_meta($job_id, '_job_type', $job_data['job_type']);
            update_post_meta($job_id, '_job_category', $job_data['category']);
            update_post_meta($job_id, '_job_is_remote', $job_data['is_remote']);
            update_post_meta($job_id, '_job_expire_date', date('Y-m-d', strtotime('+30 days')));

            $created_jobs[] = $job_id;
        }
    }

    return $created_jobs;
}

/**
 * Sample function to update existing job posts with missing meta fields
 */
function update_existing_jobs_with_meta()
{
    $args = array(
        'post_type' => 'job',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );

    $jobs = get_posts($args);
    $updated_count = 0;

    foreach ($jobs as $job) {
        $needs_update = false;

        // Check and add missing required fields
        if (empty(get_post_meta($job->ID, '_job_country', true))) {
            update_post_meta($job->ID, '_job_country', 'US');
            $needs_update = true;
        }

        if (empty(get_post_meta($job->ID, '_job_city', true))) {
            update_post_meta($job->ID, '_job_city', 'New York');
            $needs_update = true;
        }

        if (empty(get_post_meta($job->ID, '_job_state', true))) {
            update_post_meta($job->ID, '_job_state', 'NY');
            $needs_update = true;
        }

        if (empty(get_post_meta($job->ID, '_job_company', true))) {
            update_post_meta($job->ID, '_job_company', 'Your Company Name');
            $needs_update = true;
        }

        if (empty(get_post_meta($job->ID, '_job_postal_code', true))) {
            update_post_meta($job->ID, '_job_postal_code', '10001');
            $needs_update = true;
        }

        if ($needs_update) {
            $updated_count++;
        }
    }

    return $updated_count;
}

/**
 * Sample function to validate job data
 */
function validate_job_meta_fields($job_id)
{
    $required_fields = array(
        '_job_country' => 'Country',
        '_job_city' => 'City',
        '_job_state' => 'State',
        '_job_company' => 'Company',
        '_job_postal_code' => 'Postal Code'
    );

    $missing_fields = array();

    foreach ($required_fields as $field => $label) {
        $value = get_post_meta($job_id, $field, true);
        if (empty($value)) {
            $missing_fields[] = $label;
        }
    }

    return $missing_fields;
}

/**
 * Sample function to get job feed statistics
 */
function get_job_feed_stats()
{
    $args = array(
        'post_type' => 'job',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );

    $all_jobs = get_posts($args);
    $valid_jobs = 0;
    $invalid_jobs = 0;

    foreach ($all_jobs as $job_id) {
        $missing_fields = validate_job_meta_fields($job_id);
        if (empty($missing_fields)) {
            $valid_jobs++;
        } else {
            $invalid_jobs++;
        }
    }

    return array(
        'total_jobs' => count($all_jobs),
        'valid_jobs' => $valid_jobs,
        'invalid_jobs' => $invalid_jobs,
        'feed_url' => home_url('/jobs-feed.xml')
    );
}

/**
 * Admin notice to show job feed statistics
 */
function show_job_feed_admin_notice()
{
    if (current_user_can('manage_options')) {
        $stats = get_job_feed_stats();
        echo '<div class="notice notice-info">';
        echo '<p><strong>Job XML Feed Status:</strong></p>';
        echo '<ul>';
        echo '<li>Total Jobs: ' . $stats['total_jobs'] . '</li>';
        echo '<li>Valid Jobs (in feed): ' . $stats['valid_jobs'] . '</li>';
        echo '<li>Invalid Jobs (missing fields): ' . $stats['invalid_jobs'] . '</li>';
        echo '<li>Feed URL: <a href="' . $stats['feed_url'] . '" target="_blank">' . $stats['feed_url'] . '</a></li>';
        echo '</ul>';
        echo '</div>';
    }
}

// Add admin notice
add_action('admin_notices', 'show_job_feed_admin_notice');

/**
 * Sample meta field structure for reference
 */
/*
Required Meta Fields:
- _job_country: Two-letter ISO country code (e.g., "US", "CA", "GB")
- _job_city: City name (must be USPS valid)
- _job_state: Two-letter state abbreviation (e.g., "NY", "CA", "TX")
- _job_company: Company name
- _job_postal_code: 5-digit USPS postal code (or _job_zip as fallback)

Optional Meta Fields:
- _job_expire_date: Job expiration date (YYYY-MM-DD format)
- _job_type: Job type (Full-time, Part-time, Contract, etc.)
- _job_category: Job category matching ZipRecruiter categories
- _job_is_remote: Boolean value for remote work (true/false)

Common Job Types:
- Full-time
- Part-time
- Contract
- Temporary
- Internship
- Freelance

Common Categories:
- Technology
- Healthcare
- Finance
- Marketing
- Sales
- Customer Service
- Education
- Manufacturing
- Retail
- Hospitality
*/
