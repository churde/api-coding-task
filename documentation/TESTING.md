# Testing the LOTR API

[Back to README](../README.md) | [Setup](SETUP.md) | [Design](DESIGN.md) | [Features](FEATURES.md)

This document provides comprehensive guidance on testing the LOTR API, including manual testing with Swagger UI and automated unit testing.

## Environment Setup

Before testing, ensure your environment is properly set up:

1. Follow the instructions in SETUP.md to install all dependencies.
2. Make sure your .env file is configured correctly.

## Running Automated Tests

To run the full suite of unit tests:

```bash
make run-tests
```

This will execute all PHPUnit tests and provide a detailed report of the results.

## Manual Testing with Swagger UI

### Accessing Swagger UI

1. Ensure that the Docker containers are running (`make up` if they're not).
2. Open your web browser and navigate to:
   ```
   http://localhost:8080/swagger.php
   ```

### Authentication

The API uses JWT tokens for authentication. To authenticate:

1. Obtain a token by running:
   ```
   make generate-tokens
   ```
   This will generate tokens for Admin, Editor, and Viewer roles.

2. In Swagger UI, click the "Authorize" button at the top of the page.
3. In the "Value" field, enter: `Bearer <your_token>` (replace `<your_token>` with the actual token)
4. Click "Authorize" to apply the token to all requests.

Each role has different permissions:

- Admin: Can perform all operations (create, read, update, delete) on all resources.
- Editor: Can perform all operations except delete. They can create, read, and update all resources.
- Viewer: Can only read (GET) resources. They cannot create, update, or delete any resource.

Make sure to use the appropriate token for the operation you want to test.

## Available Endpoints

### Characters
- GET /api/v1/characters - List all characters (supports pagination and search)
- GET /api/v1/characters/{id} - Get a specific character
- POST /api/v1/characters - Create a new character (Admin only)
- PUT /api/v1/characters/{id} - Update a character (Admin and Editor)
- DELETE /api/v1/characters/{id} - Delete a character (Admin only)

### Factions
- GET /api/v1/factions - List all factions (supports pagination and search)
- GET /api/v1/factions/{id} - Get a specific faction
- POST /api/v1/factions - Create a new faction (Admin only)
- PUT /api/v1/factions/{id} - Update a faction (Admin and Editor)
- DELETE /api/v1/factions/{id} - Delete a faction (Admin only)

### Equipment
- GET /api/v1/equipment - List all equipment (supports pagination and search)
- GET /api/v1/equipment/{id} - Get specific equipment
- POST /api/v1/equipment - Create new equipment (Admin only)
- PUT /api/v1/equipment/{id} - Update equipment (Admin and Editor)
- DELETE /api/v1/equipment/{id} - Delete equipment (Admin only)

## Using Swagger UI

1. Click on an endpoint to expand it and see details about the request and response.
2. Click the "Try it out" button.
3. Fill in any required parameters or request body.
4. Click "Execute" to send the request.
5. Swagger will display the response, including status code and body.

## Testing Features

### Pagination

For list endpoints, use these query parameters:
- `page`: The page number (default: 1)
- `per_page`: Number of items per page (default: 20, configurable)

Example: `GET /api/v1/characters?page=2&per_page=15`

### Search

For endpoints that support search, use the `search` parameter:

Example: `GET /api/v1/characters?search=Frodo`

### Rate Limiting

The API implements rate limiting (default: 60 requests per minute). Exceed this, and you'll receive a 429 Too Many Requests response.

### Caching

Some endpoints may have caching enabled. The cache behavior is configurable in the app configuration.

### Error Handling

Test various error scenarios to ensure the API returns appropriate error responses:

- Invalid input data
- Unauthorized access attempts
- Requests for non-existent resources
- Exceeding rate limits

## Configuration

The `app/config/app.php` file contains important configuration options:

- Cache settings (TTL, enabled routes)
- Rate limiting settings
- Pagination default values
- Character constraints (name length, level range)
- Database timeouts
- API version and format
- Logging settings

You can adjust these settings to fine-tune the API's behavior.

## Unit Testing

The LOTR API project includes comprehensive unit tests to ensure the reliability and correctness of the API endpoints. These tests cover various aspects of the API functionality for different user roles (Admin, Editor, and Viewer).

### Test Environment

It's important to note that in this project, for simplicity and ease of setup, the unit tests are conducted on the same database used by the application. However, in a production environment or more complex projects, it's generally recommended to use a separate, identical database for testing purposes.

Using a separate test database offers several advantages:
1. It prevents test data from interfering with production data.
2. It allows for more aggressive testing without risking damage to live data.
3. It enables parallel testing without data conflicts.

In our case, the tests are designed to clean up after themselves, but users should be aware that running tests may temporarily affect the state of the database.

### Running the Tests

To run the unit tests, use the following command:



### Test Structure

The unit tests are organized into the following categories:

1. Character API Tests
   - CharacterApiAdminTest
   - CharacterApiEditorTest
   - CharacterApiViewerTest

2. Equipment API Tests
   - EquipmentApiAdminTest
   - EquipmentApiEditorTest
   - EquipmentApiViewerTest

3. Faction API Tests
   - FactionApiAdminTest
   - FactionApiEditorTest
   - FactionApiViewerTest

### What We Test

Our unit tests cover the following aspects:

1. CRUD Operations
   - Creating new entities (characters, equipment, factions)
   - Reading entity details and lists
   - Updating existing entities
   - Deleting entities

2. Authorization and Permissions
   - Ensuring each role (Admin, Editor, Viewer) has the correct access rights
   - Testing forbidden actions for each role

3. Search Functionality
   - Testing the search feature for each entity type

4. Pagination
   - Verifying that list endpoints support pagination

5. Error Handling
   - Testing responses for non-existent entities
   - Checking error messages for unauthorized actions

6. Token Authentication
   - Verifying that endpoints require valid authentication tokens
   - Testing responses with invalid tokens

### Key Test Cases

- GET requests for listing and retrieving individual entities
- POST requests for creating new entities (Admin only)
- PUT requests for updating entities (Admin and Editor)
- DELETE requests for removing entities (Admin only)
- Search functionality with unique identifiers
- Attempts to perform unauthorized actions based on role

## Troubleshooting

If you encounter issues:
1. Ensure you're using the correct authentication token for the desired role.
2. Check that you're not exceeding rate limits.
3. Verify that your request body matches the expected format for POST and PUT requests.
4. For 404 errors, ensure the requested resource exists.

For persistent issues, check the application logs or contact the development team.
