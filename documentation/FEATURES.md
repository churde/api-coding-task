# Features

[Back to README](../README.md) | [Setup](SETUP.md) | [Testing](TESTING.md) | [Design](DESIGN.md)

# Table of Contents

1. [Authentication / Authorization](#authentication--authorization)
2. [Input Validation](#input-validation)
   - [Controller Layer](#controller-layer)
   - [Service Layer](#service-layer)
   - [Key Aspects of Input Validation](#key-aspects-of-input-validation)
     - [Dedicated Validator Classes](#dedicated-validator-classes)
     - [Type Casting](#type-casting)
     - [Default Values](#default-values)



# Authentication / Authorization

The LOTR API project implements a robust authentication system using JSON Web Tokens (JWT). Here's a brief overview of the key concepts:

1. **Token Generation**: When a user logs in, a JWT token is generated. This token contains the user's ID and role ID, allowing for role-based access control.

2. **Token Validation**: Every request to the API is validated using middleware. This middleware checks for the presence of a valid JWT token in the Authorization header.

3. **Permission Checking**: The system uses a dedicated service to check if a user has the required permissions for a specific action. This involves both token validation and authorization checks.

4. **Role-Based Access Control**: The project implements three roles: Admin, Editor, and Viewer. Each role has different permissions set up in the database, allowing for granular control over user actions.

5. **Caching Permissions**: To improve performance, user permissions are cached. This reduces the need for frequent database queries when checking permissions.

6. **Testing**: The project includes comprehensive unit tests for authentication, ensuring that each role has the correct access rights and that invalid tokens are properly rejected.

This authentication system provides a secure and efficient way to manage user access to the LOTR API, allowing for fine-grained control over who can perform various actions within the application. It balances security with performance, using caching strategies to minimize database load while maintaining strict access controls.

# Input Validation

Input validation in this project is implemented in two layers:

Controller Layer

- Performs basic validation of request parameters
- Checks for required fields and ensures all fields are valid
- Validates field types and rejects unexpected fields
- Handles type casting and default values for optional parameters


Service Layer

- Performs more detailed business logic validations
- Ensures data integrity and consistency

### Key Aspects of Input Validation

- Dedicated validator classes for each model ensure consistent and specific validation rules
- Type casting of request parameters in controllers
- Default values for optional parameters
- Business-specific rules applied in the service layer
- Comprehensive error handling with model-specific error messages

# Caching

The LOTR API project implements a caching system to improve performance and reduce database load. Here's a brief overview of the key concepts:

**Cache Service**: The project uses a dedicated Cache service to handle all caching operations. This service provides methods for getting, setting, and deleting cached data.

**Configurable Caching**: The caching behavior is configurable through the application's configuration file. This allows for fine-tuning which routes or operations should use caching.

**TTL (Time-To-Live)**: Cached data has a configurable TTL, after which it expires and needs to be refreshed from the database.

**Cache Keys**: Unique cache keys are generated based on the request parameters, ensuring that different queries don't interfere with each other's cached results.

**Cache Invalidation**: When data is updated or deleted, related cache entries are invalidated to ensure data consistency.

**Performance Optimization**: Caching is particularly useful for read-heavy operations, significantly reducing response times for frequently accessed data.

**Flexible Storage**: The caching system is designed to be flexible, potentially allowing for different cache storage backends (e.g., Redis, Memcached) in the future.

This caching system provides a balance between performance and data freshness, allowing the API to handle high loads efficiently while maintaining data integrity. It's particularly beneficial for read-heavy operations and helps in reducing the overall load on the database.

For implementation details, you can refer to the following code sections:

```
    'cache' => [
        'ttl' => 3600, // Cache TTL in seconds (1 hour)
        'enable_cache' => [
            'get_all_characters' => false,
            'get_character_by_id' => false,
            'get_all_equipment' => true,
            'get_equipment_by_id' => true,
            'get_all_factions' => false,
            'get_faction_by_id' => false,
            // Add more route-specific cache flags as needed
        ],
    ],
```


# Rate Limiting

The LOTR API project implements a rate limiting system to prevent abuse and ensure fair usage of the API. Here's a brief overview of the key concepts:

**Middleware Approach**: Rate limiting is implemented as middleware, which intercepts and processes every request before it reaches the main application logic.

**Token-Based Limiting**: The system uses the user's authentication token as the basis for rate limiting, ensuring that limits are applied on a per-user basis.

**Configurable Limits**: The rate limit parameters (number of requests and time window) are configurable through the application's configuration file, allowing for easy adjustment without code changes.

**Cache-Based Tracking**: The system uses a cache (Redis) to track the number of requests made by each user within the specified time window.

**Time Window Approach**: The rate limit is implemented using a sliding time window, which provides a more accurate and fair limiting mechanism compared to fixed time windows.

**Graceful Rejection**: When a user exceeds their rate limit, the system responds with a 429 (Too Many Requests) status code, along with a clear error message.

**Authorization Integration**: The rate limiting system is integrated with the authentication system, ensuring that only authenticated requests are rate-limited and preventing unauthorized access.

**Performance Considerations**: By using a fast, in-memory cache for tracking request counts, the rate limiting system adds minimal overhead to request processing.

**Scalability**: The design allows for easy scaling, as the rate limiting data is stored in a centralized cache that can be shared across multiple API instances.

This rate limiting system provides a robust mechanism to protect the API from abuse while ensuring fair access for all users. It's designed to be efficient, scalable, and easily configurable to meet changing requirements.


```
'rate_limit' => [
        'requests' => 60,
        'per_minutes' => 1,
    ],
```

# Pagination

- **Query Parameters**: Pagination is implemented using `page` and `per_page` query parameters.
- **Default Values**: Default values are configurable in the app settings.
- **Repository Layer**: Pagination logic is implemented in the repository layer.
- **Metadata**: Pagination metadata (current page, total pages, etc.) is included in the API response.
- **Consistency**: All list endpoints support pagination for consistent API behavior.

For implementation details, see:
```
    'pagination' => [
        'per_page' => 20,
    ],
```
# Search Functionality

- **Query Parameter**: Search is implemented using a `search` query parameter.
- **Flexible Search**: Allows searching across multiple fields (e.g., name, kingdom).
- **Repository Layer**: Search logic is implemented in the repository layer.
- **Performance**: Optimized database queries are used for efficient searching.
- **Integration**: Search is integrated with pagination for seamless use.
