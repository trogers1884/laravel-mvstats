# Contributing Guidelines

## How to Contribute

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a Pull Request

### Guidelines

- Keep changes focused and minimal
- Add tests if applicable
- Follow existing code style
- Provide clear commit messages

### Development Environment Setup

1. Requirements:
    - PHP 8.1+
    - PostgreSQL 12+
    - Laravel 10+

2. Initial Setup:
   ```bash
   # Clone your fork
   git clone [your-fork-url]
   cd laravel-mvstats

   # Install dependencies
   composer install

   # Create test database
   createdb mvstats_test

   # Copy testing environment file
   cp .env.testing.example .env.testing
   ```

### Testing

1. Create a PostgreSQL test database
2. Configure `.env.testing` with your database credentials
3. Run the test suite:
   ```bash
   vendor/bin/phpunit
   ```

### SQL Coding Standards

When contributing PostgreSQL functions or triggers:
- Use consistent capitalization for SQL keywords
- Include proper error handling in PL/pgSQL functions
- Follow the existing naming convention (tr1884_mvstats_*)
- Document complex SQL logic with comments
- Test with multiple PostgreSQL versions when possible

### Pull Request Process

1. Ensure your code builds cleanly
2. Update documentation if needed
3. Wait for review (may take up to 2 weeks)
4. Address any requested changes

### Documentation Updates

When making changes, remember to update:
- README.md if adding features or changing requirements
- CHANGELOG.md with your changes
- PHPDoc blocks for new classes/methods
- SQL comments for complex database objects

### Common Tasks

#### Adding New Statistics Columns
1. Create a migration for the column addition
2. Update the statistics view
3. Add corresponding tests
4. Update documentation

#### Modifying Event Triggers
1. Test thoroughly with different materialized view operations
2. Consider backward compatibility
3. Update related functions
4. Add regression tests

Note: This is a hobby project maintained in spare time. Response times may vary.

### Questions or Problems?

- Open an issue for bug reports
- Use discussions for feature requests
- Tag issues appropriately

## License

By contributing, you agree that your contributions will be licensed under the MIT License.