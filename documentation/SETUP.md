# Setup and Build

This document provides detailed instructions on how to set up and build the LOTR API project.

## Quick Start

To set up the entire project, initialize the database, populate it with sample data, run all tests, and generate authentication tokens:

```bash
make setup-and-test
```

This command will:

1. Build the Docker containers
2. Start the services
3. Initialize the database schema
4. Set up authentication tables
5. Populate the database with fake data
6. Run all tests
7. Generate JWT tokens for each role (admin, editor, viewer)
8. Display the Swagger UI link and token usage instructions

## Accessing the API

Once the setup is complete, the API will be available at:

```
http://localhost:8080
```

## Testing the API

For API documentation and testing, visit:
```
http://localhost:8080/swagger.php
```

For more information on how to test the API using Swagger UI, including details on available endpoints, pagination, search functionality, and rate limiting, please refer to the [Testing Guide](TESTING.md).



## Detailed Setup Steps

1. Clone the repository:

   ```bash
   git clone https://github.com/churde/api-coding-task.git
   cd lotr-api
   ```

2. Build the Docker containers and install dependencies:

   ```bash
   make build
   ```

   This command includes both `build-container` and `composer-install`.

3. If you need to update or add dependencies:

   ```bash
   make composer-update
   make composer-require
   make composer-require-dev
   ```

4. Start the Docker containers:

   ```bash
   make up
   ```

5. Initialize the database schema:

   ```bash
   make init-db
   ```

6. Set up authentication tables:

   ```bash
   make setup-auth-tables
   ```

7. Populate the database with sample data:

   ```bash
   make populate-db
   ```

8. Run all tests:

   ```bash
   make run-tests
   ```

## Available Commands

To see all available commands, run:

```bash
make help
```

## Troubleshooting

If you encounter any issues during the setup process, try the following:

1. Ensure that Docker and Docker Compose are installed and running correctly.
2. If the database initialization fails, you can retry by running `make init-db` again.
3. If you need to rebuild the containers, use `make build` followed by `make up`.
4. Check the Docker logs for any error messages: `docker-compose logs`

If problems persist, please refer to the project's issue tracker or contact the development team for support.
