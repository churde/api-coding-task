# Features

This document provides an in-depth look at the key features of the LOTR API project.

## Project Structure

The project follows a clean and modular structure:

```
lotr-api/
├── app/
│   ├── src/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Middleware/
│   │   └── Formatters/
│   ├── tests/
│   └── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── public/
└── docker/
```

This structure separates concerns and makes the codebase easy to navigate and maintain.

## Input Validation

Input validation is implemented using a combination of:

1. Type hinting in method signatures
2. Custom validation classes for complex validations
3. Database constraints for data integrity

Example:
```php
public function getCharacter(int $id): CharacterResponse
{
    $this->validateId($id);
    // ...
}
```

## Response Formatters

Response formatting is handled by dedicated formatter classes, ensuring consistent API responses:

1. Each resource (Character, Faction, Equipment) has its own formatter
2. Formatters handle both single-item and collection responses
3. Error responses are also consistently formatted

Example:
```php
class CharacterFormatter implements FormatterInterface
{
    public function format(Character $character): array
    {
        return [
            'id' => $character->getId(),
            'name' => $character->getName(),
            // ...
        ];
    }
}
```

## Search Functionality

The API supports basic search functionality:

1. Search is implemented for Character, Faction, and Equipment endpoints
2. Searches are case-insensitive and match partial strings
3. Search logic is encapsulated in repository classes for easy extension

Example usage:
```
GET /characters?q=frodo
```

## Pagination

All list endpoints support pagination:

1. Clients can specify `page` and `per_page` parameters
2. Default and maximum values for `per_page` are enforced
3. Pagination metadata is included in the response

Example response:
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "per_page": 10,
    "total": 50,
    "total_pages": 5
  }
}
```

## Rate Limiting

Rate limiting is implemented to protect the API from abuse:

1. Limits are enforced on a per-client basis using IP addresses
2. Limit information is included in response headers
3. Configurable limits (e.g., 100 requests per minute)

Rate limit headers:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1620000000
```

## Unit Testing

Comprehensive unit tests are implemented:

1. Tests cover Controllers, Services, and Repositories
2. Mock objects are used to isolate units of code
3. Both happy path and edge cases are tested
4. PHPUnit is used as the testing framework

Example test:
```php
public function testGetCharacterReturnsCorrectData()
{
    $character = $this->createMock(Character::class);
    $character->method('getId')->willReturn(1);
    $character->method('getName')->willReturn('Frodo');

    $this->characterRepository->expects($this->once())
        ->method('find')
        ->with(1)
        ->willReturn($character);

    $response = $this->characterController->getCharacter(1);

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('Frodo', json_decode($response->getContent())->name);
}
```

These features work together to create a robust, scalable, and maintainable API that meets the requirements of a high-demand production environment.
