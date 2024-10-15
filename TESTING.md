# Testing with Swagger

This document explains how to test the LOTR API using Swagger UI.

## Accessing Swagger UI

1. Ensure that the Docker containers are running (`make up` if they're not).
2. Open your web browser and navigate to:
   ```
   http://localhost:8080/swagger.php
   ```

## Using Swagger UI

1. You'll see a list of all available endpoints grouped by resource (Characters, Factions, Equipment).
2. Click on an endpoint to expand it and see details about the request and response.
3. To test an endpoint:
   - Click the "Try it out" button
   - Fill in any required parameters
   - Click "Execute"
   - Swagger will send the request and display the response

## Available Endpoints

### Characters
- GET /characters
- GET /characters/{id}
- POST /characters
- PUT /characters/{id}
- DELETE /characters/{id}

### Factions
- GET /factions
- GET /factions/{id}
- POST /factions
- PUT /factions/{id}
- DELETE /factions/{id}

### Equipment
- GET /equipment
- GET /equipment/{id}
- POST /equipment
- PUT /equipment/{id}
- DELETE /equipment/{id}

## Testing Pagination

When testing endpoints that return lists (e.g., GET /characters), you can use the following query parameters:
- `page`: The page number (default: 1)
- `per_page`: Number of items per page (default: 10, max: 100)

Example:
```
GET /characters?page=2&per_page=20
```

## Testing Search

For endpoints that support search, use the `q` query parameter:

Example:
```
GET /characters?q=Frodo
```

## Testing Rate Limiting

The API implements rate limiting. You can test this by making multiple requests in quick succession. Once you exceed the limit, you'll receive a 429 Too Many Requests response.

## Running Automated Tests

To run the full suite of unit tests:

```bash
make run-tests
```

This will execute all PHPUnit tests and provide a detailed report of the results.
