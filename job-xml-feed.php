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
        // Add rewrite rule for ZipRecruiter XML feed
        add_rewrite_rule('^ziprecruiter', 'index.php?job_xml_feed=ziprecruiter', 'top');

        // Add rewrite rule for Indeed XML feed
        add_rewrite_rule('^indeed', 'index.php?job_xml_feed=indeed', 'top');

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
        $feed_type = get_query_var('job_xml_feed');
        if ($feed_type) {
            if ($feed_type === 'indeed') {
                $this->generate_indeed_xml_feed();
            } else {
                // Default to ZipRecruiter format (backward compatible)
                $this->generate_xml_feed();
            }
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
            $xml_content = $this->build_xml_feed();
            echo $xml_content;
            exit;
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

        // Get jobs - only PUBLIC posting status
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $max_jobs,
            'meta_query' => array(
                array(
                    'key' => '_job_posting_status',
                    'value' => 'PUBLIC',
                    'compare' => '='
                )
            )
        );

        $jobs = get_posts($args);

        header('Content-Type: application/rss+xml; charset=UTF-8');

        // Start building XML manually for better control
        $xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml_content .= '<rss version="2.0">' . "\n";
        $xml_content .= '  <channel>' . "\n";
        $xml_content .= '    <title>' . esc_html(get_bloginfo('name')) . ' - Job Feed</title>' . "\n";
        $xml_content .= '    <link>' . esc_url(home_url()) . '</link>' . "\n";
        $xml_content .= '    <description>' . esc_html(get_bloginfo('description')) . '</description>' . "\n";
        $xml_content .= '    <language>' . esc_html(get_bloginfo('language')) . '</language>' . "\n";
        $xml_content .= '    <jobs>' . "\n";

        if (empty($jobs)) {
            $xml_content .= '  <debug>' . "\n";
            $xml_content .= '    <message>No jobs found</message>' . "\n";
            $xml_content .= '    <post_type>' . esc_html($post_type) . '</post_type>' . "\n";
            $xml_content .= '    <total_posts>' . wp_count_posts($post_type)->publish . '</total_posts>' . "\n";
            $xml_content .= '  </debug>' . "\n";
        } else {
            $valid_jobs = 0;
            foreach ($jobs as $job) {
                if ($this->validate_job_data($job)) {
                    $xml_content .= $this->build_job_xml($job);
                    $valid_jobs++;
                }
            }

            if ($valid_jobs == 0) {
                $xml_content .= '  <debug>' . "\n";
                $xml_content .= '    <message>No valid jobs found - missing required meta fields</message>' . "\n";
                $xml_content .= '    <total_jobs>' . count($jobs) . '</total_jobs>' . "\n";
                $xml_content .= '    <required_fields>country, city, state, company</required_fields>' . "\n";
                $xml_content .= '  </debug>' . "\n";
            }
        }

        $xml_content .= '    </jobs>' . "\n";
        $xml_content .= '  </channel>' . "\n";
        $xml_content .= '</rss>';
        return $xml_content;
    }

    private function validate_job_data($job)
    {
        // Check for your existing meta field structure first
        $country = get_post_meta($job->ID, '_job_country_code', true);
        $city = get_post_meta($job->ID, '_job_city', true);
        $region = get_post_meta($job->ID, '_job_region_code', true);

        // If your existing fields exist, use them
        if (!empty($country) && !empty($city) && !empty($region)) {
            return true;
        }

        // Fallback to standard fields
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

    private function build_job_xml($job)
    {
        // Required fields - use job reference number if available
        $reference_id = get_post_meta($job->ID, '_job_ref_number', true);
        if (empty($reference_id)) {
            $reference_id = $job->ID; // Fallback to post ID
        }

        // Description - use job description from your data
        $job_description = get_post_meta($job->ID, '_job_ad_job_description_text', true);
        if (empty($job_description)) {
            $job_description = apply_filters('the_content', $job->post_content);
        }
        $content = $this->clean_description($job_description);

        // Location fields - use your existing meta fields
        $country = get_post_meta($job->ID, '_job_country_code', true);
        if (empty($country)) {
            $country = get_post_meta($job->ID, '_job_country', true);
        }

        $city = get_post_meta($job->ID, '_job_city', true);

        $state = get_post_meta($job->ID, '_job_region_code', true);
        if (empty($state)) {
            $state = get_post_meta($job->ID, '_job_state', true);
        }

        // Postal code from location_full
        $location_full = get_post_meta($job->ID, '_job_location_full', true);
        $postal_code = '';

        if (!empty($location_full)) {
            // Handle both string and array formats
            if (is_string($location_full)) {
                $location_data = json_decode($location_full, true);
            } else {
                $location_data = $location_full;
            }

            if (is_array($location_data) && isset($location_data['postalCode'])) {
                $postal_code = $location_data['postalCode'];
            }
        }

        // Fallback to other postal code fields

        $postal_code = get_post_meta($job->ID, '_job_postal_code', true);


        // Dates
        $created_on = get_post_meta($job->ID, '_job_created_on', true);
        if (!empty($created_on)) {
            $date_posted = date('Y-m-d', strtotime($created_on));
        }

        $expiration_date = get_post_meta($job->ID, '_job_expiration_date', true);
        if (!empty($expiration_date)) {
            $valid_through = date('Y-m-d', strtotime($expiration_date));
        }
        if (empty($valid_through)) {
            $valid_through = get_post_meta($job->ID, '_job_expire_date', true);
        }
        if (empty($valid_through)) {
            $valid_through = get_post_meta($job->ID, '_job_property_intakelink_label', true);
        }

        // Company - use from your data or fallback
        $company = get_post_meta($job->ID, '_job_property_brands_label', true);
        if (empty($company)) {
            $company = 'Intuitive Health'; // Default company name
        }

        $job_web_url = get_post_meta($job->ID, '_job_apply_on_web', true);
        if (empty($job_web_url)) {
            $job_web_url = get_permalink($job->ID);
        }

        $job_type = get_post_meta($job->ID, '_job_type_of_employment', true);



        // Remote work - check your existing field
        $is_remote = get_post_meta($job->ID, '_job_remote', true);
        $remote_value = '';
        if ($is_remote === 'ONSITE') {
            $remote_value = 'false';
        } elseif ($is_remote === 'REMOTE') {
            $remote_value = 'true';
        } else {
            // Check location_full for remote info
            if (!empty($location_full)) {
                if (is_string($location_full)) {
                    $location_data = json_decode($location_full, true);
                } else {
                    $location_data = $location_full;
                }
                if (isset($location_data['remote'])) {
                    $remote_value = $location_data['remote'] ? 'true' : 'false';
                }
            }
        }

        // Build XML manually
        $xml = '      <job>' . "\n";
        if (!empty($reference_id)) {
            $xml .= '       <referenceID>' . esc_html($reference_id) . '</referenceID>' . "\n";
        }
        if (!empty($job->post_title)) {
            $xml .= '       <title>' . esc_html($job->post_title) . '</title>' . "\n";
        }

        if (!empty($content)) {
            $xml .= '       <description><![CDATA[' . $content . ']]></description>' . "\n";
        }
        if (!empty($country)) {
            $xml .= '       <country>' . esc_html($country) . '</country>' . "\n";
        }
        if (!empty($city)) {
            $xml .= '       <city>' . esc_html($city) . '</city>' . "\n";
        }
        if (!empty($state)) {
            $xml .= '       <state>' . esc_html($state) . '</state>' . "\n";
        }
        if (!empty($postal_code)) {
            $xml .= '       <postalCode>' . esc_html($postal_code) . '</postalCode>' . "\n";
        }
        if (!empty($date_posted)) {
            $xml .= '       <datePosted>' . esc_html($date_posted) . '</datePosted>' . "\n";
        }
        if (!empty($valid_through)) {
            $xml .= '       <validThrough>' . esc_html($valid_through) . '</validThrough>' . "\n";
        }
        if (!empty($company)) {
            $xml .= '       <hiringOrganization>' . esc_html($company) . '</hiringOrganization>' . "\n";
        }
        if (!empty($job_web_url)) {
            $xml .= '       <url>' . esc_html($job_web_url) . '</url>' . "\n";
        }
        if (!empty($job_type)) {
            $xml .= '       <jobType>' . esc_html($job_type) . '</jobType>' . "\n";
        }

        if (!empty($remote_value)) {
            $xml .= '       <isRemote>' . $remote_value . '</isRemote>' . "\n";
        }

        $xml .= '     </job>' . "\n";

        return $xml;
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

    private function clean_description_for_indeed($content)
    {
        // Remove phone numbers and external URLs but keep full description
        $content = preg_replace('/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', '', $content);
        $content = preg_replace('/https?:\/\/[^\s<>"]+/', '', $content);

        // Clean up excessive whitespace but preserve structure
        $content = preg_replace('/[ \t]+/', ' ', $content);
        $content = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $content);

        // Don't trim - keep full description
        return $content;
    }

    public function generate_indeed_xml_feed()
    {
        // Set proper headers
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        try {
            $xml_content = $this->build_indeed_xml_feed();
            echo $xml_content;
            exit;
        } catch (Exception $e) {
            error_log('Indeed Job XML Feed Error: ' . $e->getMessage());
            $this->output_error_xml();
        }
    }

    private function build_indeed_xml_feed()
    {
        // Get plugin settings
        $settings = get_option('job_xml_feed_settings', array());
        $post_type = isset($settings['post_type']) ? $settings['post_type'] : 'job';
        $max_jobs = isset($settings['max_jobs']) ? intval($settings['max_jobs']) : 1000;

        // Get jobs - only PUBLIC posting status (same data source as ZipRecruiter)
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $max_jobs,
            'meta_query' => array(
                array(
                    'key' => '_job_posting_status',
                    'value' => 'PUBLIC',
                    'compare' => '='
                )
            )
        );

        $jobs = get_posts($args);

        // Start building XML in Indeed format
        $xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml_content .= '<source>' . "\n";

        if (empty($jobs)) {
            $xml_content .= '  <message>No jobs found</message>' . "\n";
        } else {
            $valid_jobs = 0;
            foreach ($jobs as $job) {
                if ($this->validate_job_data($job)) {
                    $xml_content .= $this->build_indeed_job_xml($job);
                    $valid_jobs++;
                }
            }

            if ($valid_jobs == 0) {
                $xml_content .= '  <message>No valid jobs found - missing required meta fields</message>' . "\n";
            }
        }

        $xml_content .= '</source>';
        return $xml_content;
    }

    private function build_indeed_job_xml($job)
    {
        // Get job data (same source as ZipRecruiter feed)
        $reference_id = get_post_meta($job->ID, '_job_ref_number', true);
        if (empty($reference_id)) {
            $reference_id = $job->ID; // Fallback to post ID
        }

        // Job title - clean, only position name (remove "Final Test", salary, location)
        $job_title = $job->post_title;
        // Remove "Final Test" and similar test indicators
        $job_title = preg_replace('/\b(final\s+test|test\s+job|test\s+posting)\b/i', '', $job_title);
        // Remove common salary patterns
        $job_title = preg_replace('/\$[\d,]+(\s*-\s*\$[\d,]+)?(\s*\/\s*(year|hour|month))?/i', '', $job_title);
        // Remove location patterns like "in City, State", "at Location", "near Location"
        $job_title = preg_replace('/\s*(in|at|near)\s+[A-Z][a-z]+(\s*,\s*[A-Z]{2})?/i', '', $job_title);
        // Remove parentheses with location/salary info
        $job_title = preg_replace('/\s*\([^)]*(?:salary|location|city|state|country)[^)]*\)/i', '', $job_title);
        // Clean up extra whitespace
        $job_title = preg_replace('/\s+/', ' ', $job_title);
        $job_title = trim($job_title);
        // Remove trailing dash
        $job_title = rtrim($job_title, ' -');

        // Description - use full, untrimmed description (minimum 600 characters)
        $job_description = get_post_meta($job->ID, '_job_ad_job_description_text', true);
        if (empty($job_description)) {
            $job_description = apply_filters('the_content', $job->post_content);
        }
        // Clean phone numbers and URLs but keep full description
        $content = $this->clean_description_for_indeed($job_description);
        // Ensure minimum 600 characters
        if (strlen($content) < 600 && !empty($job->post_content)) {
            // If cleaned description is too short, try using post content
            $fallback_content = apply_filters('the_content', $job->post_content);
            $fallback_content = $this->clean_description_for_indeed($fallback_content);
            if (strlen($fallback_content) >= 600) {
                $content = $fallback_content;
            }
        }

        // Company name
        $company = get_post_meta($job->ID, '_job_property_brands_label', true);
        if (empty($company)) {
            $company = 'Intuitive Health'; // Default company name
        }

        // Location fields - separate tags for Indeed
        $country = get_post_meta($job->ID, '_job_country_code', true);
        if (empty($country)) {
            $country = get_post_meta($job->ID, '_job_country', true);
        }
        // Convert country to uppercase ISO format (US instead of us)
        if (!empty($country)) {
            $country = strtoupper($country);
        }

        $city = get_post_meta($job->ID, '_job_city', true);

        $state = get_post_meta($job->ID, '_job_region_code', true);
        if (empty($state)) {
            $state = get_post_meta($job->ID, '_job_state', true);
        }

        // Dates
        $created_on = get_post_meta($job->ID, '_job_created_on', true);
        $date_posted = '';
        if (!empty($created_on)) {
            $date_posted = date('Y-m-d', strtotime($created_on));
        } else {
            // Fallback to post date
            $date_posted = date('Y-m-d', strtotime($job->post_date));
        }

        $expiration_date = get_post_meta($job->ID, '_job_expiration_date', true);
        $exp_date = '';
        if (!empty($expiration_date)) {
            $exp_date = date('Y-m-d', strtotime($expiration_date));
        } else {
            $exp_date = get_post_meta($job->ID, '_job_expire_date', true);
        }

        // Build XML in Indeed format
        $xml = '  <job>' . "\n";

        // Title (clean, only position name)
        if (!empty($job_title)) {
            $xml .= '    <title><![CDATA[' . $job_title . ']]></title>' . "\n";
        }

        // Description (full, untrimmed, minimum 600 characters)
        if (!empty($content)) {
            $xml .= '    <description><![CDATA[' . $content . ']]></description>' . "\n";
        }

        // Reference number / unique job ID
        if (!empty($reference_id)) {
            $xml .= '    <referencenumber><![CDATA[' . esc_html($reference_id) . ']]></referencenumber>' . "\n";
        }

        // Company name
        if (!empty($company)) {
            $xml .= '    <company><![CDATA[' . esc_html($company) . ']]></company>' . "\n";
        }

        // Location - structured tags
        if (!empty($city)) {
            $xml .= '    <city><![CDATA[' . esc_html($city) . ']]></city>' . "\n";
        }
        if (!empty($state)) {
            $xml .= '    <state><![CDATA[' . esc_html($state) . ']]></state>' . "\n";
        }
        if (!empty($country)) {
            $xml .= '    <country><![CDATA[' . esc_html($country) . ']]></country>' . "\n";
        }

        // Posting date
        if (!empty($date_posted)) {
            $xml .= '    <date><![CDATA[' . esc_html($date_posted) . ']]></date>' . "\n";
        }

        // Expiration date (if available)
        if (!empty($exp_date)) {
            $xml .= '    <expiration_date><![CDATA[' . esc_html($exp_date) . ']]></expiration_date>' . "\n";
        }

        $xml .= '  </job>' . "\n";

        return $xml;
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
            <p><strong>ZipRecruiter Feed URL:</strong> <code><?php echo home_url('/ziprecruiter'); ?></code></p>
            <p><strong>Indeed Feed URL:</strong> <code><?php echo home_url('/indeed'); ?></code></p>
            <p><strong>Total Jobs:</strong> <?php echo $this->get_total_jobs(); ?></p>

            <h2>Your Existing Meta Fields</h2>
            <p>Plugin automatically detects and uses these fields from your database:</p>
            <ul>
                <li><code>_job_country_code</code> - Country code (e.g., "us")</li>
                <li><code>_job_city</code> - City name</li>
                <li><code>_job_region_code</code> - State code (e.g., "TX")</li>
                <li><code>_job_location_full</code> - JSON location data with postal code</li>
                <li><code>_job_ad_job_description_text</code> - Job description</li>
                <li><code>_job_remote</code> - Remote work status (ONSITE/REMOTE)</li>
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
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_job_posting_status',
                    'value' => 'PUBLIC',
                    'compare' => '='
                )
            )
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
$job_xml_feed = new JobXMLFeedGenerator();

