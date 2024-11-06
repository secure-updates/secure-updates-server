# Secure Updates Server API Documentation

The Secure Updates Server plugin provides several REST API endpoints for managing plugin updates securely. All endpoints are prefixed with `/wp-json/secure-updates-server/v1`.

## Authentication

Most endpoints are public except for the Plugin List endpoint which requires authentication using a Bearer token. To authenticate:

1. Add your API key in the WordPress admin under Secure Updates Server > API Keys
2. Include the API key in requests using the Authorization header:
   ```
   Authorization: Bearer your-api-key-here
   ```

## Endpoints

### Download Plugin
Get the latest version of a plugin package.

```
GET /download/{slug}
```

**Parameters:**
- `slug` (path parameter): The plugin slug (e.g., "my-plugin")

**Response:**
- Success: Returns the plugin ZIP file for download
- Error (404): Plugin not found or file doesn't exist
  ```json
  {
    "code": "plugin_not_found",
    "message": "Plugin not found or file does not exist.",
    "status": 404
  }
  ```

### Get Plugin Information
Retrieve metadata about a specific plugin.

```
GET /info/{slug}
```

**Parameters:**
- `slug` (path parameter): The plugin slug

**Response:**
- Success:
  ```json
  {
    "name": "Plugin Name",
    "slug": "plugin-slug",
    "version": "1.0.0",
    "author": "Author Name",
    "homepage": "https://example.com",
    "download_link": "https://example.com/wp-json/secure-updates-server/v1/download/plugin-slug",
    "sections": {
      "description": "Plugin description here.",
      "installation": "Installation instructions here.",
      "changelog": "Changelog here."
    }
  }
  ```
- Error (404): Plugin not found
  ```json
  {
    "code": "plugin_not_found",
    "message": "Plugin not found",
    "status": 404
  }
  ```

### Check Connection
Test if the update server is accessible and functioning.

```
GET /connected
```

**Response:**
```json
{
  "status": "connected"
}
```

### Verify Plugin File
Check if a plugin file exists and get its verification information.

```
GET /verify_file/{slug}
```

**Parameters:**
- `slug` (path parameter): The plugin slug

**Response:**
- Success:
  ```json
  {
    "status": "success",
    "message": "Plugin file is correctly hosted and accessible.",
    "file_exists": true,
    "file_path": "/path/to/plugin.zip",
    "checksum": "sha256-hash-of-file"
  }
  ```
- Error (404): Plugin or file not found
  ```json
  {
    "code": "plugin_not_found",
    "message": "Plugin not found.",
    "status": 404
  }
  ```
  or
  ```json
  {
    "code": "file_not_found",
    "message": "Plugin file does not exist on the server.",
    "status": 404
  }
  ```

### Manage Plugin List
Add or update multiple plugins on the server.

```
POST /plugins
```

**Authentication Required**: Bearer token in Authorization header

**Request Body:**
```json
{
  "plugins": ["plugin-slug-1", "plugin-slug-2"]
}
```

**Response:**
- Success:
  ```json
  {
    "status": "success",
    "plugins": {
      "plugin-slug-1": "already mirrored",
      "plugin-slug-2": "mirrored successfully"
    }
  }
  ```
- Error (400): Invalid request
  ```json
  {
    "code": "invalid_request",
    "message": "Invalid plugin list.",
    "status": 400
  }
  ```
- Error (401): Unauthorized
  ```json
  {
    "code": "rest_forbidden",
    "message": "Sorry, you are not allowed to do that."
  }
  ```

## Error Handling

All endpoints return appropriate HTTP status codes:
- 200: Successful request
- 400: Bad request (invalid parameters)
- 401: Unauthorized (invalid or missing API key)
- 404: Resource not found
- 500: Server error

Error responses follow the WordPress REST API format:
```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "status": 400
}
```