# Setup and Build

This document provides detailed instructions on how to set up and build the LOTR API project.

## Quick Start

To set up the entire project, initialize the database, populate it with sample data, and run all tests with a single command:

```bash
make setup-and-test
```

This is the fastest way to get the project up and running. If you need more control over the setup process, follow the detailed steps below.

## Prerequisites

- Docker
- Docker Compose
- Make

## Detailed Setup Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/lotr-api.git
   cd lotr-api
   ```

2. Build and start the Docker containers:
   ```bash
   make up
   ```

3. Initialize the database schema:
   ```bash
   make init-db
   ```

4. Populate the database with sample data:
   ```bash
   make populate-db
   ```

5. Run all tests:
   ```bash
   make run-tests
   ```

## Other Useful Commands

- `make down`: Stop the Docker containers
- `make build`: Rebuild the Docker containers
- `make logs`: View Docker container logs

## Accessing the API

Once the setup is complete, the API will be available at:

```
http://localhost:8080
```

For API documentation and testing, visit:

```
http://localhost:8080/swagger
```
