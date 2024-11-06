<?php
// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Fetch mirrored and uploaded plugins
$secure_updates_plugins = get_option('secure_updates_plugins', []);
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>
<div class="wrap">
    <h1><?php esc_html_e('Secure Updates Server', 'secure-updates-server'); ?></h1>

    <!-- Status Messages -->
    <?php if ($status === 'success'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Plugin mirrored successfully.', 'secure-updates-server'); ?></p>
        </div>
    <?php elseif ($status === 'error'): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                if ($message === 'empty_slug') {
                    esc_html_e('Plugin slug cannot be empty.', 'secure-updates-server');
                } elseif ($message === 'mirror_failed') {
                    esc_html_e('Failed to mirror the plugin.', 'secure-updates-server');
                } elseif ($message === 'nonce_failed') {
                    esc_html_e('Security check failed. Please try again.', 'secure-updates-server');
                } elseif ($message === 'insufficient_permissions') {
                    esc_html_e('You do not have sufficient permissions to perform this action.', 'secure-updates-server');
                } elseif ($message === 'plugin_not_found') {
                    esc_html_e('Plugin not found.', 'secure-updates-server');
                } else {
                    esc_html_e('An error occurred while processing the plugin.', 'secure-updates-server');
                }
                ?>
            </p>
        </div>
    <?php elseif ($status === 'deleted'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Plugin deleted successfully.', 'secure-updates-server'); ?></p>
        </div>
    <?php elseif ($status === 'upload_success'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Plugin uploaded successfully.', 'secure-updates-server'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Mirroring a Plugin -->
    <h2><?php esc_html_e('Mirror a Plugin from Repository', 'secure-updates-server'); ?></h2>
    <form id="mirror-plugin-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php
        // Nonce field for security
        wp_nonce_field('mirror_plugin_nonce', 'security');
        ?>
        <input type="hidden" name="action" value="mirror_plugin">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="plugin_slug"><?php esc_html_e('Plugin Slug', 'secure-updates-server'); ?></label>
                </th>
                <td>
                    <input type="text" id="plugin_slug" name="plugin_slug" placeholder="<?php esc_attr_e('Enter Plugin Slug', 'secure-updates-server'); ?>" required>
                    <p class="description"><?php esc_html_e('Enter the slug of the plugin you wish to mirror from WordPress.org.', 'secure-updates-server'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Mirror Plugin', 'secure-updates-server'), 'primary'); ?>
    </form>
    <!-- Removed AJAX feedback div -->

    <hr>

    <!-- Uploading a Plugin -->
    <h2><?php esc_html_e('Upload Your Plugin to the Server', 'secure-updates-server'); ?></h2>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php
        // Nonce field for security
        wp_nonce_field('upload_plugin_nonce', '_wpnonce');
        ?>
        <input type="hidden" name="action" value="upload_plugin_to_server">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="plugin_zip_file"><?php esc_html_e('Plugin ZIP File', 'secure-updates-server'); ?></label></th>
                <td>
                    <input type="file" id="plugin_zip_file" name="plugin_zip_file" accept=".zip" required>
                    <p class="description"><?php esc_html_e('Upload the ZIP file of your plugin.', 'secure-updates-server'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Upload Plugin', 'secure-updates-server'), 'primary', 'upload_plugin_button'); ?>
    </form>

    <hr>

    <!-- Listing Mirrored and Uploaded Plugins -->
    <h2><?php esc_html_e('Managed Plugins', 'secure-updates-server'); ?></h2>
    <table class="widefat fixed striped">
        <thead>
        <tr>
            <th><?php esc_html_e('Plugin Slug', 'secure-updates-server'); ?></th>
            <th><?php esc_html_e('Latest Version', 'secure-updates-server'); ?></th>
            <th><?php esc_html_e('Versions', 'secure-updates-server'); ?></th>
            <th><?php esc_html_e('Hosted Plugin', 'secure-updates-server'); ?></th>
            <th><?php esc_html_e('Actions', 'secure-updates-server'); ?></th>
        </tr>
        </thead>
        <tbody id="managed-plugins-table-body">
        <?php if (!empty($secure_updates_plugins)): ?>
            <?php foreach ($secure_updates_plugins as $plugin_slug => $plugin): ?>
                <tr>
                    <td><?php echo esc_html($plugin_slug); ?></td>
                    <td><?php echo esc_html($plugin['latest_version']); ?></td>
                    <td>
                        <?php
                        foreach ($plugin['versions'] as $version_info) {
                            echo esc_html($version_info['version']) . ' (' . esc_html($version_info['date']) . ')<br>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        // Generate the REST API verify_file URL for the plugin
                        $verify_api_url = rest_url('secure-updates-server/v1/verify_file/' . $plugin_slug);
                        ?>
                        <a href="<?php echo esc_url($verify_api_url); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Hosted Plugin', 'secure-updates-server'); ?>
                        </a>
                    </td>
                    <td>
                        <!-- Delete Plugin Form -->
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                            <?php
                            // Nonce field for security
                            wp_nonce_field('delete_plugin_nonce', 'delete_plugin_nonce_field');
                            ?>
                            <input type="hidden" name="action" value="delete_plugin">
                            <input type="hidden" name="plugin_slug" value="<?php echo esc_attr($plugin_slug); ?>">
                            <?php submit_button(__('Delete', 'secure-updates-server'), 'secondary', 'delete_plugin', false, ['onclick' => "return confirm('" . esc_attr__('Are you sure you want to delete this plugin?', 'secure-updates-server') . "');"]); ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">
                    <?php esc_html_e('No plugins managed yet.', 'secure-updates-server'); ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Removed JavaScript for handling AJAX submissions -->
