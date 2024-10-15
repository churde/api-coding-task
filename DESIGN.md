# Design and Assumptions

This document outlines the design principles and assumptions made during the development of the LOTR API.

## Design Principles

1. **SOLID Principles**: The project adheres to SOLID principles to ensure maintainability and extensibility.
   - Single Responsibility Principle: Each class has a single, well-defined responsibility.
   - Open/Closed Principle: The codebase is open for extension but closed for modification.
   - Liskov Substitution Principle: Subtypes are substitutable for their base types.
   - Interface Segregation Principle: Interfaces are specific to client needs.
   - Dependency Inversion Principle: High-level modules don't depend on low-level modules. Both depend on abstractions.

2. **RESTful Design**: The API follows RESTful principles for resource naming and HTTP method usage.

3. **Separation of Concerns**: The project structure separates different concerns (e.g., controllers, models, services) for better organization and maintainability.

4. **Dependency Injection**: Used to manage dependencies and facilitate testing.

5. **Repository Pattern**: Implemented to abstract data access logic.

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

These design choices and assumptions aim to create a robust, scalable, and maintainable API that meets the requirements of a high-demand production environment while allowing for future growth and modifications.
