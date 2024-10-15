# LOTR API Project

Welcome to the Lord of the Rings API project! This API provides access to information about characters, factions, and equipment from the Lord of the Rings universe.

## Quick Start

To set up the project, initialize the database, populate it with sample data, and run all tests, use this single command:

```bash
make setup-and-run-tests
```

## Note on Docker Platform

The Docker setup includes a parameter `DOCKER_DEFAULT_PLATFORM` for specifying the platform (it defaults to linux/amd64):
```platform: ${DOCKER_DEFAULT_PLATFORM:-linux/amd64}```



## Table of Contents

1. [Setup and Build](documentation/SETUP.md)
2. [Testing](documentation/TESTING.md)
3. [Design and Assumptions](documentation/DESIGN.md)
4. [Features](documentation/FEATURES.md)
   - Authentication / Authorization
   - Input Validation
   - Caching
   - Rate Limiting
   - Pagination
   - Search Functionality

For more detailed instructions on setup and individual steps, please refer to the [Setup and Build](documentation/SETUP.md) guide.

## Technologies Used

- PHP 8.1
- MySQL 85.7
- Docker and Docker Compose
- PHPUnit for testing
- Redis for Cache
- Swagger for API documentation

## Key Features

- **Authentication**: JWT-based authentication with role-based access control
- **Input Validation**: Robust validation at both controller and service layers
- **Caching**: Configurable caching system to improve performance
- **Rate Limiting**: Token-based rate limiting to prevent API abuse
- **Pagination**: Supported on all list endpoints for efficient data retrieval
- **Search**: Flexible search functionality across multiple fields

For a comprehensive overview of these features, please see the [Features](documentation/FEATURES.md) document.

## Project Structure

The project follows a well-organized structure to separate concerns and improve maintainability. For details on the project structure and design principles, refer to the [Design and Assumptions](documentation/DESIGN.md) document.

## Testing

This project includes both automated unit tests and manual testing capabilities via Swagger UI. For detailed information on how to run tests and use Swagger UI for API exploration, please see the [Testing](documentation/TESTING.md) guide.

## Contact

If you have any questions or need further clarification, please don't hesitate to contact me at churde@gmail.com

Thank you for reviewing my project!
