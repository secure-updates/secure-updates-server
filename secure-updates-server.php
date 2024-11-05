<?php
/*
Plugin Name: Secure Updates Server
Description: Provides secure plugin updates by allowing direct uploads and mirroring from repositories with Media Library integration.
Version: 4.0
Author: Secure Updates Foundation
Text Domain: secure-updates-server
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Secure_Updates_Server')) {
    class Secure_Updates_Server
    {
        /**
         * Constructor to initialize the plugin
         */
        public function __construct()
        {
            // Load text domain for translations
            add_action('plugins_loaded', [$this, 'load_textdomain']);

            // Setup hooks
            $this->setup_hooks();
        }

        /**
         * Load plugin textdomain for translations
         */
        public function load_textdomain()
        {
            load_plugin_textdomain('secure-updates-server', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Setup WordPress hooks
         */
        private function setup_hooks()
        {
            // Admin Menu
            add_action('admin_menu', [$this, 'setup_admin_menu']);

            // Handle Direct Plugin Upload
            add_action('admin_post_upload_plugin_to_server', [$this, 'upload_plugin_to_server']);

            // Handle Mirror Plugin via standard form submission
            add_action('admin_post_mirror_plugin', [$this, 'mirror_plugin']);

            // Handle Delete Plugin via standard form submission
            add_action('admin_post_delete_plugin', [$this, 'delete_plugin']);

            // Scheduled Updates
            add_action('secure_updates_server_check_updates', [$this, 'check_for_updates']);

            // REST API Endpoints
            add_action('rest_api_init', [$this, 'register_rest_routes']);

            // Activation and Deactivation Hooks
            register_activation_hook(__FILE__, [$this, 'activate_plugin']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate_plugin']);
        }

        /**
         * Activate the plugin and schedule update checks
         */
        public function activate_plugin()
        {
            if (!wp_next_scheduled('secure_updates_server_check_updates')) {
                wp_schedule_event(time(), 'hourly', 'secure_updates_server_check_updates');
            }
        }

        /**
         * Deactivate the plugin and clear scheduled hooks
         */
        public function deactivate_plugin()
        {
            wp_clear_scheduled_hook('secure_updates_server_check_updates');
        }

        /**
         * Setup the admin menu in WordPress dashboard
         */
        public function setup_admin_menu()
        {
            add_menu_page(
                __('Secure Updates Server', 'secure-updates-server'),
                __('Secure Updates Server', 'secure-updates-server'),
                'manage_options',
                'secure-updates-server',
                [$this, 'admin_page'],
                'dashicons-update',
                6
            );

            add_submenu_page(
                'secure-updates-server',
                __('API Keys', 'secure-updates-server'),
                __('API Keys', 'secure-updates-server'),
                'manage_options',
                'secure-updates-server-api-keys',
                [$this, 'api_keys_page']
            );
        }

        /**
         * Display the admin page for managing plugins
         */
        public function admin_page()
        {
            // Include the admin page content
            include plugin_dir_path(__FILE__) . 'admin/admin-page.php';
        }

        /**
         * Register REST API routes
         */
        public function register_rest_routes()
        {
            // Existing Download Endpoint
            register_rest_route('secure-updates-server/v1', '/download/(?P<slug>[a-zA-Z0-9-]+)', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_download_request'],
                'permission_callback' => '__return_true',
            ]);

            // Existing Info Endpoint
            register_rest_route('secure-updates-server/v1', '/info/(?P<slug>[a-zA-Z0-9-]+)', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_info_request'],
                'permission_callback' => '__return_true',
            ]);

            // Existing Connected Endpoint
            register_rest_route('secure-updates-server/v1', '/connected', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_connected_request'],
                'permission_callback' => '__return_true',
            ]);

            // Existing Verify File Endpoint
            register_rest_route('secure-updates-server/v1', '/verify_file/(?P<slug>[a-zA-Z0-9-]+)', [
                'methods' => 'GET',
                'callback' => [$this, 'handle_verify_file_request'],
                'permission_callback' => '__return_true',
            ]);

            // New Plugin List Endpoint
            register_rest_route('secure-updates-server/v1', '/plugins', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_plugin_list_request'],
                'permission_callback' => [$this, 'verify_client_request'],
            ]);
        }

        /**
         * Verify client API key
         */
        public function verify_client_request($request)
        {
            $headers = $request->get_headers();
            if (isset($headers['authorization'])) {
                $auth = $headers['authorization'][0];
                if (strpos($auth, 'Bearer ') === 0) {
                    $api_key = substr($auth, 7);
                    $valid_api_keys = get_option('secure_updates_valid_api_keys', []);
                    if (in_array($api_key, $valid_api_keys, true)) {
                        return true;
                    }
                }
            }
            return false;
        }

        /**
         * Handle REST API request for plugin download
         */
        public function handle_download_request($request)
        {
            $plugin_slug = sanitize_text_field($request['slug']);

            // Get the latest version of the plugin
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            if (isset($secure_updates_plugins[$plugin_slug])) {
                $latest_version = $secure_updates_plugins[$plugin_slug]['latest_version'];
                $attachment_id = $secure_updates_plugins[$plugin_slug]['versions'][$latest_version]['attachment_id'];
                $file_path = get_attached_file($attachment_id);

                if ($file_path && file_exists($file_path)) {
                    // Serve the file for download
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                    header('Content-Length: ' . filesize($file_path));
                    header('Pragma: public');
                    flush();
                    readfile($file_path);
                    exit;
                }
            }

            return new WP_Error('plugin_not_found', __('Plugin not found or file does not exist.', 'secure-updates-server'), ['status' => 404]);
        }

        /**
         * Handle REST API request for plugin information
         */
        public function handle_info_request($request)
        {
            $plugin_slug = sanitize_text_field($request['slug']);

            // Get plugin information
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            if (isset($secure_updates_plugins[$plugin_slug])) {
                // Retrieve plugin data
                $plugin_info = [
                    'name' => ucfirst($plugin_slug),
                    'slug' => $plugin_slug,
                    'version' => $secure_updates_plugins[$plugin_slug]['latest_version'],
                    'author' => 'Your Name',
                    'homepage' => home_url(),
                    'download_link' => rest_url('secure-updates-server/v1/download/' . $plugin_slug),
                    'sections' => [
                        'description' => __('Plugin description here.', 'secure-updates-server'),
                        'installation' => __('Installation instructions here.', 'secure-updates-server'),
                        'changelog' => __('Changelog here.', 'secure-updates-server'),
                    ],
                ];

                return rest_ensure_response($plugin_info);
            }

            return new WP_Error('plugin_not_found', __('Plugin not found', 'secure-updates-server'), ['status' => 404]);
        }

        /**
         * Handle REST API request to test connection
         */
        public function handle_connected_request($request)
        {
            return rest_ensure_response(['status' => 'connected']);
        }

        /**
         * Handle REST API request to verify plugin file
         */
        public function handle_verify_file_request($request)
        {
            $plugin_slug = sanitize_text_field($request['slug']);

            // Get the plugin information
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            if (isset($secure_updates_plugins[$plugin_slug])) {
                $attachment_id = $secure_updates_plugins[$plugin_slug]['attachment_id'];
                $file_path = get_attached_file($attachment_id);

                if ($file_path && file_exists($file_path)) {
                    // Calculate checksum
                    $checksum = isset($secure_updates_plugins[$plugin_slug]['versions'][$secure_updates_plugins[$plugin_slug]['latest_version']]['checksum'])
                        ? $secure_updates_plugins[$plugin_slug]['versions'][$secure_updates_plugins[$plugin_slug]['latest_version']]['checksum']
                        : '';

                    return rest_ensure_response([
                        'status' => 'success',
                        'message' => __('Plugin file is correctly hosted and accessible.', 'secure-updates-server'),
                        'file_exists' => true,
                        'file_path' => $file_path,
                        'checksum' => $checksum,
                    ]);
                } else {
                    return new WP_Error('file_not_found', __('Plugin file does not exist on the server.', 'secure-updates-server'), ['status' => 404]);
                }
            }

            return new WP_Error('plugin_not_found', __('Plugin not found.', 'secure-updates-server'), ['status' => 404]);
        }

        /**
         * Handle plugin list request
         */
        public function handle_plugin_list_request($request)
        {
            $params = $request->get_json_params();
            if (empty($params['plugins']) || !is_array($params['plugins'])) {
                return new WP_Error('invalid_request', __('Invalid plugin list.', 'secure-updates-server'), ['status' => 400]);
            }

            $plugin_slugs = array_map('sanitize_text_field', $params['plugins']);
            $responses = [];

            foreach ($plugin_slugs as $slug) {
                if ($this->is_plugin_mirrored($slug)) {
                    $responses[$slug] = 'already mirrored';
                } else {
                    $result = $this->mirror_plugin_by_slug($slug);
                    if ($result) {
                        $responses[$slug] = 'mirrored successfully';
                    } else {
                        $responses[$slug] = 'failed to mirror';
                    }
                }
            }

            return rest_ensure_response(['status' => 'success', 'plugins' => $responses]);
        }

        /**
         * Mirror a plugin (Handles standard form submission)
         */
        public function mirror_plugin()
        {
            // Verify nonce
            if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'mirror_plugin_nonce')) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=nonce_failed'));
                exit;
            }

            // Capability check
            if (!current_user_can('manage_options')) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=insufficient_permissions'));
                exit;
            }

            $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field($_POST['plugin_slug']) : '';

            if (empty($plugin_slug)) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=empty_slug'));
                exit;
            }

            $this->log_message("Mirroring plugin: $plugin_slug");

            if ($this->mirror_plugin_by_slug($plugin_slug)) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=success'));
            } else {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=mirror_failed'));
            }
            exit;
        }

        /**
         * Delete a mirrored or uploaded plugin (Handles standard form submission)
         */
        public function delete_plugin()
        {
            // Verify nonce
            if (!isset($_POST['delete_plugin_nonce_field']) || !wp_verify_nonce($_POST['delete_plugin_nonce_field'], 'delete_plugin_nonce')) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=nonce_failed'));
                exit;
            }

            // Capability check
            if (!current_user_can('manage_options')) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=insufficient_permissions'));
                exit;
            }

            $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field($_POST['plugin_slug']) : '';

            if (empty($plugin_slug)) {
                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=empty_slug'));
                exit;
            }

            // Delete plugin data from the database
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            if (isset($secure_updates_plugins[$plugin_slug])) {
                // Delete all attachments related to the plugin
                foreach ($secure_updates_plugins[$plugin_slug]['versions'] as $version_info) {
                    wp_delete_attachment($version_info['attachment_id'], true);
                }

                unset($secure_updates_plugins[$plugin_slug]);
                update_option('secure_updates_plugins', $secure_updates_plugins);

                $this->log_message("Deleted plugin: $plugin_slug");

                do_action('secure_updates_plugin_deleted', $plugin_slug);

                wp_redirect(admin_url('admin.php?page=secure-updates-server&status=deleted'));
                exit;
            }

            $this->log_message("Plugin not found: $plugin_slug");
            wp_redirect(admin_url('admin.php?page=secure-updates-server&status=error&message=plugin_not_found'));
            exit;
        }

        /**
         * Handle Direct Plugin Upload
         */
        public function upload_plugin_to_server()
        {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'upload_plugin_nonce')) {
                wp_die(__('Nonce verification failed', 'secure-updates-server'));
            }

            // Capability check
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions.', 'secure-updates-server'));
            }

            // Check for file upload
            if (empty($_FILES['plugin_zip_file']) || $_FILES['plugin_zip_file']['error'] !== UPLOAD_ERR_OK) {
                wp_die(__('Please select a valid ZIP file to upload.', 'secure-updates-server'));
            }

            // Handle file upload
            $file = $_FILES['plugin_zip_file'];
            $upload = wp_handle_upload($file, ['test_form' => false]);

            if (isset($upload['error']) && !empty($upload['error'])) {
                wp_die(__('Error uploading file: ', 'secure-updates-server') . esc_html($upload['error']));
            }

            // Insert the uploaded file into the WordPress Media Library
            $filetype = wp_check_filetype(basename($upload['file']), null);
            $attachment = [
                'guid' => $upload['url'],
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attachment_id = wp_insert_attachment($attachment, $upload['file']);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            // Extract plugin slug from file name
            $plugin_slug = sanitize_title(preg_replace('/\.[^.]+$/', '', basename($upload['file'])));

            // Extract version from plugin data if possible
            $plugin_data = $this->get_plugin_data_from_zip($upload['file']);
            $version = isset($plugin_data['Version']) ? sanitize_text_field($plugin_data['Version']) : '1.0.0';

            // Calculate checksum
            $checksum = $this->calculate_checksum($upload['file']);

            // Add plugin slug to served plugins list
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            if (!isset($secure_updates_plugins[$plugin_slug])) {
                $secure_updates_plugins[$plugin_slug] = [
                    'attachment_id' => $attachment_id,
                    'type' => 'direct_upload',
                    'versions' => [
                        $version => [
                            'version' => $version,
                            'date' => current_time('mysql'),
                            'attachment_id' => $attachment_id,
                            'checksum' => $checksum,
                        ]
                    ],
                    'latest_version' => $version,
                ];
            } else {
                // Handle versioning for existing plugin
                $secure_updates_plugins[$plugin_slug]['versions'][$version] = [
                    'version' => $version,
                    'date' => current_time('mysql'),
                    'attachment_id' => $attachment_id,
                    'checksum' => $checksum,
                ];
                $secure_updates_plugins[$plugin_slug]['latest_version'] = $version;
                $secure_updates_plugins[$plugin_slug]['attachment_id'] = $attachment_id;
            }

            update_option('secure_updates_plugins', $secure_updates_plugins);

            $this->log_message("Uploaded plugin: $plugin_slug version: $version");

            do_action('secure_updates_plugin_uploaded', $plugin_slug, $version);

            // Redirect back with success message
            wp_redirect(admin_url('admin.php?page=secure-updates-server&status=upload_success'));
            exit;
        }

        /**
         * Check for updates (both mirrored and uploaded plugins)
         */
        public function check_for_updates()
        {
            $secure_updates_plugins = get_option('secure_updates_plugins', []);

            foreach ($secure_updates_plugins as $plugin_slug => $plugin_info) {
                if ($plugin_info['type'] === 'mirror') {
                    $this->check_mirrored_plugin_update($plugin_slug, $plugin_info);
                }
                // For direct uploads, you might implement different update mechanisms if needed
            }

            $this->log_message('Checked for plugin updates.');
        }

        /**
         * Check and update a mirrored plugin
         */
        private function check_mirrored_plugin_update($plugin_slug, $plugin_info)
        {
            $plugin_data = $this->fetch_plugin_data($plugin_slug);

            if ($plugin_data && version_compare($plugin_data['version'], $plugin_info['latest_version'], '>')) {
                $version = sanitize_text_field($plugin_data['version']);
                $attachment_id = $this->upload_plugin_zip_to_media_library($plugin_slug, $plugin_data['download_link'], $version);

                if ($attachment_id) {
                    // Calculate checksum
                    $file_path = get_attached_file($attachment_id);
                    $checksum = $this->calculate_checksum($file_path);

                    // Update plugin information
                    $this->update_plugin_info($plugin_slug, $version, $attachment_id, 'mirror', $checksum);

                    $this->log_message("Updated mirrored plugin: $plugin_slug to version: $version");

                    do_action('secure_updates_plugin_updated', $plugin_slug, $version);
                }
            }
        }

        /**
         * Check if a plugin is already mirrored
         */
        private function is_plugin_mirrored($slug)
        {
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            return isset($secure_updates_plugins[$slug]);
        }

        /**
         * Mirror a plugin by slug
         */
        private function mirror_plugin_by_slug($slug)
        {
            $plugin_data = $this->fetch_plugin_data($slug);

            if ($plugin_data && isset($plugin_data['download_link'])) {
                $version = sanitize_text_field($plugin_data['version']);
                $attachment_id = $this->upload_plugin_zip_to_media_library($slug, $plugin_data['download_link'], $version);

                if ($attachment_id) {
                    $file_path = get_attached_file($attachment_id);
                    $checksum = $this->calculate_checksum($file_path);

                    $this->update_plugin_info($slug, $version, $attachment_id, 'mirror', $checksum);

                    $this->log_message("Successfully mirrored plugin: $slug version: $version");

                    do_action('secure_updates_plugin_mirrored', $slug, $version);

                    return true;
                }
            }

            $this->log_message("Failed to mirror plugin: $slug");
            return false;
        }

        /**
         * Display API keys management page
         */
        public function api_keys_page()
        {
            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'manage_api_keys')) {
                    wp_die(__('Security check failed', 'secure-updates-server'));
                }

                $valid_api_keys = get_option('secure_updates_valid_api_keys', []);

                if ($_POST['action'] === 'add_key' && isset($_POST['new_api_key'])) {
                    $new_key = sanitize_text_field($_POST['new_api_key']);
                    if (!empty($new_key)) {
                        $valid_api_keys[] = $new_key;
                        update_option('secure_updates_valid_api_keys', array_unique($valid_api_keys));
                    }
                } elseif ($_POST['action'] === 'delete_key' && isset($_POST['api_key'])) {
                    $key_to_delete = sanitize_text_field($_POST['api_key']);
                    $valid_api_keys = array_diff($valid_api_keys, [$key_to_delete]);
                    update_option('secure_updates_valid_api_keys', $valid_api_keys);
                }
            }

            // Display the page
            $valid_api_keys = get_option('secure_updates_valid_api_keys', []);
            include plugin_dir_path(__FILE__) . 'admin/api-keys-page.php';
        }

        /**
         * Fetch plugin data from WordPress.org
         */
        private function fetch_plugin_data($slug)
        {
            $transient_key = 'plugin_data_' . $slug;
            $plugin_data = get_transient($transient_key);

            if ($plugin_data === false) {
                $response = wp_remote_get("https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=$slug");

                if (is_wp_error($response)) {
                    $this->log_message("Error fetching data for plugin $slug: " . $response->get_error_message());
                    return false;
                }

                $plugin_data = json_decode(wp_remote_retrieve_body($response), true);
                set_transient($transient_key, $plugin_data, HOUR_IN_SECONDS);
            }

            return $plugin_data;
        }

        /**
         * Upload plugin ZIP to Media Library
         */
        private function upload_plugin_zip_to_media_library($slug, $url, $version)
        {
            $tmp = download_url($url);

            if (is_wp_error($tmp)) {
                $this->log_message("Error downloading plugin $slug: " . $tmp->get_error_message());
                return false;
            }

            $file_array = [
                'name' => $slug . '-' . $version . '.zip',
                'tmp_name' => $tmp,
            ];

            // Handle the upload using WordPress's media library functions
            $attachment_id = media_handle_sideload($file_array, 0);

            // Check for errors during upload
            if (is_wp_error($attachment_id)) {
                $this->log_message("Error uploading plugin $slug to media library: " . $attachment_id->get_error_message());
                @unlink($tmp); // Delete the temporary file
                return false;
            }

            // Delete the temporary file
            @unlink($tmp);

            return $attachment_id;
        }

        /**
         * Handle logging of messages
         */
        private function log_message($message)
        {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Secure Updates Server] ' . $message);
            }
        }

        /**
         * Calculate SHA256 checksum of a file
         */
        private function calculate_checksum($file_path)
        {
            return hash_file('sha256', $file_path);
        }

        /**
         * Update plugin information in the database
         */
        private function update_plugin_info($slug, $version, $attachment_id, $type, $checksum = '')
        {
            $secure_updates_plugins = get_option('secure_updates_plugins', []);
            if (!isset($secure_updates_plugins[$slug])) {
                $secure_updates_plugins[$slug] = [
                    'slug' => $slug,
                    'type' => $type,
                    'versions' => [],
                ];
            }

            $secure_updates_plugins[$slug]['versions'][$version] = [
                'version' => $version,
                'date' => current_time('mysql'),
                'attachment_id' => $attachment_id,
                'checksum' => $checksum,
            ];

            $secure_updates_plugins[$slug]['latest_version'] = $version;
            $secure_updates_plugins[$slug]['attachment_id'] = $attachment_id;

            update_option('secure_updates_plugins', $secure_updates_plugins);
        }

        /**
         * Extract plugin data from ZIP file
         */
        private function get_plugin_data_from_zip($file_path)
        {
            $plugin_data = [];
            $zip = new ZipArchive();
            if ($zip->open($file_path) === true) {
                // Look for main plugin file (assuming it has the same name as the folder)
                $plugin_slug = basename($file_path, '.zip');
                $main_plugin_file = "$plugin_slug.php";

                if ($zip->locateName($main_plugin_file)) {
                    $plugin_content = $zip->getFromName($main_plugin_file);
                    if ($plugin_content !== false) {
                        $plugin_data = $this->parse_plugin_header($plugin_content);
                    }
                }
                $zip->close();
            }
            return $plugin_data;
        }

        /**
         * Parse plugin header to get version and other data
         */
        private function parse_plugin_header($plugin_content)
        {
            $plugin_data = [];
            $headers = [
                'Version' => 'Version',
                'Plugin Name' => 'Name',
                // Add more headers if needed
            ];

            foreach ($headers as $field => $regex_field) {
                if (preg_match('/' . $field . ':\s*(.+)/i', $plugin_content, $matches)) {
                    $plugin_data[$field] = trim($matches[1]);
                }
            }

            return $plugin_data;
        }
    }

    // Initialize the plugin
    new Secure_Updates_Server();
}