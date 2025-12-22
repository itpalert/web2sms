## [2.2.1] - 2024-12-21

### Fixed
- Fixed Laravel dependency injection error when injecting `Client` class directly
- Updated `Web2smsServiceProvider` to properly bind `Client::class` to the container
- Fixed facade accessor to return correct class for dependency injection

### Changed
- Reorganized tests into `Unit` and `Integration` directories
- Improved code quality with proper PHPDoc annotations
- Standardized HTTP request calls in `Client` class
- Updated `phpunit.xml` to reflect new test structure

### Added
- Account type validation in `Basic` credentials class
- Throws `InvalidArgumentException` for invalid account types

### Improved
- Better type safety across the codebase
- Cleaner code with null coalescing assignment operator
- Enhanced documentation and comments