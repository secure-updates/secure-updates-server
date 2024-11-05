<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('API Keys Management', 'secure-updates-server'); ?></h1>

    <?php
    // Show success message if key was added or deleted
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_key' && !empty($_POST['new_api_key'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('API key added successfully.', 'secure-updates-server'); ?></p>
            </div>
            <?php
        } elseif ($_POST['action'] === 'delete_key') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('API key deleted successfully.', 'secure-updates-server'); ?></p>
            </div>
            <?php
        }
    }
    ?>

    <!-- Add New API Key -->
    <div class="card">
        <h2><?php esc_html_e('Add New API Key', 'secure-updates-server'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('manage_api_keys'); ?>
            <input type="hidden" name="action" value="add_key">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="new_api_key"><?php esc_html_e('New API Key', 'secure-updates-server'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="new_api_key"
                               id="new_api_key"
                               class="regular-text"
                               required
                               pattern="[a-zA-Z0-9_-]{16,64}"
                               title="<?php esc_attr_e('API key should be between 16 and 64 characters and contain only letters, numbers, underscores, and hyphens', 'secure-updates-server'); ?>"
                        >
                        <p class="description">
                            <?php esc_html_e('Enter a secure API key between 16 and 64 characters. Use only letters, numbers, underscores, and hyphens.', 'secure-updates-server'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Add API Key', 'secure-updates-server')); ?>
        </form>
    </div>

    <!-- Existing API Keys -->
    <div class="card" style="margin-top: 20px;">
        <h2><?php esc_html_e('Existing API Keys', 'secure-updates-server'); ?></h2>
        <table class="widefat striped">
            <thead>
            <tr>
                <th><?php esc_html_e('API Key', 'secure-updates-server'); ?></th>
                <th><?php esc_html_e('Actions', 'secure-updates-server'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($valid_api_keys)): ?>
                <?php foreach ($valid_api_keys as $api_key): ?>
                    <tr>
                        <td>
                            <code><?php echo esc_html($api_key); ?></code>
                        </td>
                        <td>
                            <form method="post" action="" style="display: inline;">
                                <?php wp_nonce_field('manage_api_keys'); ?>
                                <input type="hidden" name="action" value="delete_key">
                                <input type="hidden" name="api_key" value="<?php echo esc_attr($api_key); ?>">
                                <button type="submit"
                                        class="button button-secondary"
                                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this API key? Any clients using this key will lose access.', 'secure-updates-server'); ?>')">
                                    <?php esc_html_e('Delete', 'secure-updates-server'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">
                        <?php esc_html_e('No API keys have been created yet.', 'secure-updates-server'); ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Usage Instructions -->
    <div class="card" style="margin-top: 20px;">
        <h2><?php esc_html_e('API Key Usage Instructions', 'secure-updates-server'); ?></h2>
        <div style="padding: 10px;">
            <p>
                <?php esc_html_e('To use an API key in your requests:', 'secure-updates-server'); ?>
            </p>
            <ol>
                <li>
                    <?php esc_html_e('Include the API key in the Authorization header of your HTTP requests:', 'secure-updates-server'); ?>
                    <pre style="background: #f1f1f1; padding: 10px; margin: 10px 0;">Authorization: Bearer YOUR_API_KEY</pre>
                </li>
                <li>
                    <?php esc_html_e('Example cURL request:', 'secure-updates-server'); ?>
                    <pre style="background: #f1f1f1; padding: 10px; margin: 10px 0;">curl -X POST \
  <?php echo esc_url(rest_url('secure-updates-server/v1/plugins')); ?> \
  -H 'Authorization: Bearer YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"plugins": ["plugin-slug-1", "plugin-slug-2"]}'</pre>
                </li>
            </ol>
            <p>
                <strong><?php esc_html_e('Security Note:', 'secure-updates-server'); ?></strong><br>
                <?php esc_html_e('Keep your API keys secure and never share them publicly. If a key is compromised, delete it immediately and create a new one.', 'secure-updates-server'); ?>
            </p>
        </div>
    </div>
</div>

<style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    .card h2 {
        margin-top: 0;
        padding-bottom: 12px;
        border-bottom: 1px solid #eee;
    }
    pre {
        overflow-x: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>