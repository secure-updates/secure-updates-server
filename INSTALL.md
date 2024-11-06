# Installation Guide

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- ZIP extension enabled
- Write permissions for wp-content/uploads

## Installation Steps

1. **Download the Plugin:**
    - Clone the repository or download the ZIP file from the [GitHub Repository](https://github.com/secure-updates/secure-updates-server)

2. **Install via WordPress Admin:**
    - Navigate to `Plugins` > `Add New` > `Upload Plugin`
    - Upload the `secure-updates-server.zip` file
    - Click `Install Now` and then `Activate`

3. **Configure the Plugin:**

   ### API Keys Setup
    - Go to `Secure Updates Server` > `API Keys`
    - Add a new API key that clients will use to authenticate
    - Ensure keys are securely stored and distributed

   ### Mirroring Plugins
    - Navigate to `Secure Updates Server` > `Secure Updates Server`
    - Enter plugin slug to mirror from WordPress.org
    - Click `Mirror Plugin`

   ### Uploading Plugins
    - Use the `Upload Your Plugin to the Server` section
    - Support for direct ZIP file uploads
    - Version management included

   ### Managing Plugins
    - View all plugins in the `Managed Plugins` section
    - Perform deletions or rollbacks as needed

## Usage Guide

### Managing API Keys

1. **Add a New API Key:**
    - Navigate to API Keys section
    - Enter secure key (16-64 characters)
    - Use only letters, numbers, underscores, hyphens

2. **Delete an API Key:**
    - Locate key in the list
    - Click Delete and confirm

### Plugin Management

1. **Mirroring Plugins:**
    - Enter plugin slug (e.g., `akismet`)
    - Click Mirror Plugin
    - Monitor progress in Managed Plugins table

2. **Direct Uploads:**
    - Use Upload section
    - Select ZIP file
    - Provide version information

3. **Version Management:**
    - View version history
    - Perform rollbacks
    - Delete outdated versions

## Client Configuration

1. Install Secure Updates Client plugin
2. Navigate to Settings > Secure Updates Client
3. Enter:
    - Custom Host URL (your server)
    - API key from server
4. Test connection
5. Enable updates

## Troubleshooting

### Common Issues

1. **Upload Failures:**
    - Check file permissions
    - Verify ZIP format
    - Confirm WordPress upload limits

2. **API Key Issues:**
    - Verify key format
    - Check client configuration
    - Confirm server settings

3. **Mirroring Problems:**
    - Check WordPress.org connectivity
    - Verify plugin slug
    - Monitor error logs

### Getting Help

- Check the FAQ section
- Review error logs
- Open GitHub issue for support