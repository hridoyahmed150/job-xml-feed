<?php
/*
Plugin Name: Intuitive Job XML Feed Generator
Description: Generates XML feed for job listings compatible with Intuitive requirements. Reusable across multiple WordPress sites.
Version: 2.0
Author: Hridoy Ahmed
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('JOB_XML_FEED_VERSION', '2.0');
define('JOB_XML_FEED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JOB_XML_FEED_PLUGIN_PATH', plugin_dir_path(__FILE__));

class JobXMLFeedGenerator
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('template_redirect', array($this, 'handle_feed_request'));
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        // Add rewrite rule for XML feed
        add_rewrite_rule('^jobs-feed\.xml$', 'index.php?job_xml_feed=1', 'top');

        // Flush rewrite rules if needed
        if (get_option('job_xml_feed_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('job_xml_feed_flush_rewrite_rules');
        }
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'job_xml_feed';
        return $vars;
    }

    public function handle_feed_request()
    {
        if (get_query_var('job_xml_feed')) {
            $this->generate_xml_feed();
            exit;
        }
    }

    public function generate_xml_feed()
    {
        // Set proper headers
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        try {
            $xml = $this->build_xml_feed();
            echo $xml;
        } catch (Exception $e) {
            error_log('Job XML Feed Error: ' . $e->getMessage());
            $this->output_error_xml();
        }
    }

    private function build_xml_feed()
    {
        // Get plugin settings
        $settings = get_option('job_xml_feed_settings', array());
        $post_type = isset($settings['post_type']) ? $settings['post_type'] : 'job';
        $max_jobs = isset($settings['max_jobs']) ? intval($settings['max_jobs']) : 1000;

        // Get jobs
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $max_jobs,
            'meta_query' => array(
                array(
                    'key' => '_job_country',
                    'compare' => 'EXISTS'
                )
            )
        );

        $jobs = get_posts($args);

        // Create XML structure
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><jobs></jobs>');

        foreach ($jobs as $job) {
            if ($this->validate_job_data($job)) {
                $this->add_job_to_xml($xml, $job);
            }
        }

        return $xml->asXML();
    }

    private function validate_job_data($job)
    {
        $required_fields = array(
            '_job_country',
            '_job_city',
            '_job_state',
            '_job_company'
        );

        foreach ($required_fields as $field) {
            if (empty(get_post_meta($job->ID, $field, true))) {
                return false;
            }
        }

        return true;
    }

    private function add_job_to_xml($xml, $job)
    {
        $job_node = $xml->addChild('job');

        // Required fields
        $job_node->addChild('referenceID', $this->sanitize_text($job->ID));
        $job_node->addChild('title', $this->sanitize_text($job->post_title));

        // Description with CDATA
        $description = $job_node->addChild('description');
        $content = apply_filters('the_content', $job->post_content);
        $content = $this->clean_description($content);
        $description->addCData($content);

        // Location fields
        $job_node->addChild('country', $this->sanitize_text(get_post_meta($job->ID, '_job_country', true)));
        $job_node->addChild('city', $this->sanitize_text(get_post_meta($job->ID, '_job_city', true)));
        $job_node->addChild('state', $this->sanitize_text(get_post_meta($job->ID, '_job_state', true)));

        // Postal code (with fallback)
        $postal_code = get_post_meta($job->ID, '_job_postal_code', true);
        if (empty($postal_code)) {
            $postal_code = get_post_meta($job->ID, '_job_zip', true);
        }
        $job_node->addChild('postalCode', $this->sanitize_text($postal_code));

        // Dates
        $job_node->addChild('datePosted', get_the_date('Y-m-d', $job->ID));
        $valid_through = get_post_meta($job->ID, '_job_expire_date', true);
        if (empty($valid_through)) {
            $valid_through = date('Y-m-d', strtotime('+30 days'));
        }
        $job_node->addChild('validThrough', $valid_through);

        // Company and URL
        $job_node->addChild('hiringOrganization', $this->sanitize_text(get_post_meta($job->ID, '_job_company', true)));
        $job_node->addChild('url', get_permalink($job->ID));

        // Optional but recommended fields
        $job_type = get_post_meta($job->ID, '_job_type', true);
        if (!empty($job_type)) {
            $job_node->addChild('jobType', $this->sanitize_text($job_type));
        }

        $category = get_post_meta($job->ID, '_job_category', true);
        if (!empty($category)) {
            $job_node->addChild('category', $this->sanitize_text($category));
        }

        $is_remote = get_post_meta($job->ID, '_job_is_remote', true);
        if (!empty($is_remote)) {
            $job_node->addChild('isRemote', $is_remote ? 'true' : 'false');
        }
    }

    private function sanitize_text($text)
    {
        return htmlspecialchars(strip_tags($text), ENT_XML1, 'UTF-8');
    }

    private function clean_description($content)
    {
        // Remove phone numbers and external URLs as per requirements
        $content = preg_replace('/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', '', $content);
        $content = preg_replace('/https?:\/\/[^\s<>"]+/', '', $content);

        // Clean up extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        return $content;
    }

    private function output_error_xml()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><jobs></jobs>');
        $error = $xml->addChild('error');
        $error->addChild('message', 'Unable to generate job feed');
        $error->addChild('timestamp', date('Y-m-d H:i:s'));
        echo $xml->asXML();
    }

    // Admin functions
    public function add_admin_menu()
    {
        add_options_page(
            'Job XML Feed Settings',
            'Job XML Feed',
            'manage_options',
            'job-xml-feed',
            array($this, 'admin_page')
        );
    }

    public function admin_init()
    {
        register_setting('job_xml_feed_settings', 'job_xml_feed_settings');

        add_settings_section(
            'job_xml_feed_main',
            'Main Settings',
            null,
            'job-xml-feed'
        );

        add_settings_field(
            'post_type',
            'Job Post Type',
            array($this, 'post_type_callback'),
            'job-xml-feed',
            'job_xml_feed_main'
        );

        add_settings_field(
            'max_jobs',
            'Maximum Jobs in Feed',
            array($this, 'max_jobs_callback'),
            'job-xml-feed',
            'job_xml_feed_main'
        );
    }

    public function admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Job XML Feed Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('job_xml_feed_settings');
                do_settings_sections('job-xml-feed');
                submit_button();
                ?>
            </form>

            <h2>Feed Information</h2>
            <p><strong>Feed URL:</strong> <code><?php echo home_url('/jobs-feed.xml'); ?></code></p>
            <p><strong>Total Jobs:</strong> <?php echo $this->get_total_jobs(); ?></p>

            <h2>Required Meta Fields</h2>
            <p>Make sure your job posts have these meta fields:</p>
            <ul>
                <li><code>_job_country</code> - Two-letter ISO country code</li>
                <li><code>_job_city</code> - City name</li>
                <li><code>_job_state</code> - Two-letter state abbreviation</li>
                <li><code>_job_company</code> - Company name</li>
                <li><code>_job_postal_code</code> or <code>_job_zip</code> - Postal code</li>
                <li><code>_job_expire_date</code> - Job expiration date (optional)</li>
            </ul>

            <h2>Optional Meta Fields</h2>
            <ul>
                <li><code>_job_type</code> - Job type (Full-time, Part-time, etc.)</li>
                <li><code>_job_category</code> - Job category</li>
                <li><code>_job_is_remote</code> - Remote work (true/false)</li>
            </ul>
        </div>
        <?php
    }

    public function post_type_callback()
    {
        $settings = get_option('job_xml_feed_settings', array());
        $post_type = isset($settings['post_type']) ? $settings['post_type'] : 'job';
        echo '<input type="text" name="job_xml_feed_settings[post_type]" value="' . esc_attr($post_type) . '" />';
    }

    public function max_jobs_callback()
    {
        $settings = get_option('job_xml_feed_settings', array());
        $max_jobs = isset($settings['max_jobs']) ? $settings['max_jobs'] : 1000;
        echo '<input type="number" name="job_xml_feed_settings[max_jobs]" value="' . esc_attr($max_jobs) . '" min="1" max="10000" />';
    }

    private function get_total_jobs()
    {
        $settings = get_option('job_xml_feed_settings', array());
        $post_type = isset($settings['post_type']) ? $settings['post_type'] : 'job';

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );

        $jobs = get_posts($args);
        return count($jobs);
    }

    public function activate()
    {
        add_option('job_xml_feed_flush_rewrite_rules', true);
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new JobXMLFeedGenerator();
