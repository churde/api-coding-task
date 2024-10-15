# Design and Assumptions

[Back to README](../README.md) | [Setup](SETUP.md) | [Testing](TESTING.md) | [Features](FEATURES.md)

## Design Principles

1. **SOLID Principles**: The project adheres to SOLID principles to ensure maintainability and extensibility.

2. **RESTful Design**: The API follows RESTful principles for resource naming and HTTP method usage.

3. **Separation of Concerns**: The project structure separates different concerns (e.g., controllers, models, services) for better organization and maintainability.

4. **Dependency Injection**: Used to manage dependencies and facilitate testing.

5. **Repository Pattern**: Implemented to abstract data access logic.

6. **API Versioning**: The project is designed with multiple versions in mind. The current implementation is version 1 (v1), allowing for future versions to be added without breaking existing client integrations.

## Assumptions

1. **High Demand**: The API is designed assuming it will face high demand, similar to production environments in the target job.

2. **Scalability**: While the current implementation uses a single database, the design allows for easy scaling (e.g., read replicas, sharding) in the future.

3. **Performance**: Caching mechanisms are implemented assuming frequent reads and less frequent writes.

4. **Security**: Basic security measures are implemented, assuming the API will be publicly accessible.

5. **Extensibility**: The codebase is designed to be easily extendable for future features or additional LOTR-related resources.

6. **Consistency**: Data consistency is prioritized, assuming this is critical for the application's use case.

7. **Internationalization**: The current implementation is in English, but the design allows for easy addition of multiple languages in the future.

## Trade-offs

1. **Simplicity vs. Feature Completeness**: Given the project's scope, some advanced features (e.g., full-text search) are not implemented but are considered in the design for future addition.

2. **Performance vs. Flexibility**: Some design choices prioritize performance (e.g., denormalization) while maintaining a balance with data flexibility.

3. **Strict Typing vs. Development Speed**: PHP 8.1's strict typing is used to catch errors early, even though it might slightly slow down initial development.

## Project Structure

- `app/`: Contains the main application code
  - `config/`: Configuration files
  - `logs/`: Application logs
  - `opt/`: Optional scripts and database files
  - `src/`: Source code
    - `Controllers/`: API endpoint controllers
    - `Models/`: Data models
    - `Repositories/`: Data access layer
    - `Services/`: Business logic
    - `Middleware/`: Request/response middleware
    - `Formatters/`: Response formatters
  - `tests/`: Unit and integration tests
  - `vendor/`: Composer dependencies
- `database/`: Database-related files
  - `migrations/`: Database migration files
  - `seeds/`: Database seeder files
- `documentation/`: Project documentation files
- `docker/`: Docker-related files
- `public/`: Publicly accessible files
  - `index.php`: Entry point for the application
  - `swagger/`: Swagger UI files for API documentation

This structure organizes the code for easy navigation and maintenance, separating concerns and following best practices for PHP application development.

## API Versioning

The API is designed to support multiple versions, ensuring backward compatibility and allowing for future enhancements. Key aspects of our versioning approach include:

1. **URL-based Versioning**: The current version (v1) is explicitly stated in the URL path (e.g., `/api/v1/characters`).


### API Versioning Design Ideas

To ensure our API remains flexible and scalable, we're considering the following design ideas for versioning:

1. **Version-specific Controller Architecture**: Design a system where each API version has its own set of controllers, allowing for version-specific functionality and easier maintenance.

2. **Shared Core Logic**: Architect a shared core of business logic and data access layers that can be utilized across different API versions, minimizing code duplication and improving maintainability.

3. **Comprehensive Version Documentation**: Implement a documentation system that clearly outlines the differences between API versions, facilitating easier understanding and integration for developers.

4. **Deprecation Policy Framework**: Develop a formal deprecation policy framework that defines the lifecycle of each API version, including support periods and migration timelines for clients.

5. **Version Compatibility Layer**: Consider implementing a compatibility layer capable of translating between different API versions, smoothing the transition process for clients during upgrades.

These design ideas aim to enhance the API's robustness, scalability, and user-friendliness, ensuring that the LOTR API can evolve while maintaining backwards compatibility and ease of use for our clients.
