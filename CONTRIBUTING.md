# Contributing to APBD Analysis 2026

Thank you for your interest in contributing to APBD Analysis 2026! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)
- [Development Workflow](#development-workflow)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Git
- Basic knowledge of Laravel, PHP, and Docker
- Understanding of APBD (Anggaran Pendapatan dan Belanja Daerah) concepts

### Development Setup

1. **Fork the repository**
   ```bash
   git clone https://github.com/your-username/apbdanalysis2026.git
   cd apbdanalysis2026
   ```

2. **Setup development environment**
   ```bash
   make setup
   make dev
   ```

3. **Access the application**
   - Application: http://localhost:8000
   - phpMyAdmin: http://localhost:8081

## Contributing Guidelines

### Types of Contributions

We welcome several types of contributions:

- **Bug Reports**: Report bugs and issues
- **Feature Requests**: Suggest new features
- **Code Contributions**: Submit code improvements
- **Documentation**: Improve documentation
- **Testing**: Add or improve tests
- **Performance**: Optimize performance

### Code Standards

- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: Use ES6+ features
- **CSS**: Use consistent naming conventions
- **Docker**: Follow Docker best practices
- **Documentation**: Write clear, concise documentation

### Commit Message Format

Use the following format for commit messages:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(upload): add support for large Excel files
fix(database): resolve migration order issue
docs(readme): update installation instructions
```

## Pull Request Process

### Before Submitting

1. **Test your changes**
   ```bash
   make test
   make logs
   ```

2. **Check code quality**
   ```bash
   # Run PHP CS Fixer
   ./vendor/bin/php-cs-fixer fix
   
   # Run PHPStan
   ./vendor/bin/phpstan analyse
   ```

3. **Update documentation** if needed

4. **Add tests** for new features

### Pull Request Guidelines

1. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**
   - Write clean, readable code
   - Add appropriate tests
   - Update documentation

3. **Submit pull request**
   - Provide a clear description
   - Reference related issues
   - Include screenshots if applicable

### Review Process

1. **Automated checks** must pass
2. **Code review** by maintainers
3. **Testing** in development environment
4. **Approval** from at least one maintainer

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

- **Environment**: OS, Docker version, PHP version
- **Steps to reproduce**: Clear, numbered steps
- **Expected behavior**: What should happen
- **Actual behavior**: What actually happens
- **Screenshots**: If applicable
- **Logs**: Relevant error logs

### Feature Requests

When requesting features, please include:

- **Use case**: Why is this feature needed?
- **Proposed solution**: How should it work?
- **Alternatives**: Other solutions considered
- **Additional context**: Any other relevant information

## Development Workflow

### Branch Strategy

- `main`: Production-ready code
- `develop`: Development branch
- `feature/*`: Feature branches
- `hotfix/*`: Critical bug fixes
- `release/*`: Release preparation

### Development Process

1. **Create feature branch** from `develop`
2. **Develop and test** your changes
3. **Submit pull request** to `develop`
4. **Code review** and testing
5. **Merge** to `develop`
6. **Release** to `main` when ready

### Testing

```bash
# Run all tests
make test

# Run specific test suite
docker-compose -f docker-compose.dev.yml exec app php artisan test --testsuite=Feature

# Run with coverage
docker-compose -f docker-compose.dev.yml exec app php artisan test --coverage
```

### Database Changes

1. **Create migration**
   ```bash
   docker-compose -f docker-compose.dev.yml exec app php artisan make:migration your_migration_name
   ```

2. **Test migration**
   ```bash
   make migrate
   ```

3. **Create rollback** if needed

### Docker Changes

1. **Test locally** with development environment
2. **Update documentation** for new configurations
3. **Test production** deployment
4. **Update scripts** if needed

## Performance Guidelines

### Code Performance

- Use efficient database queries
- Implement proper caching
- Optimize file uploads
- Monitor memory usage

### Docker Performance

- Use multi-stage builds
- Optimize image sizes
- Use appropriate base images
- Implement health checks

## Security Guidelines

### Code Security

- Validate all inputs
- Use prepared statements
- Implement proper authentication
- Follow OWASP guidelines

### Docker Security

- Use non-root users
- Keep images updated
- Use secrets management
- Implement network security

## Documentation

### Code Documentation

- Write clear comments
- Document complex logic
- Use PHPDoc for functions
- Keep README updated

### API Documentation

- Document all endpoints
- Provide examples
- Include error responses
- Keep documentation current

## Release Process

### Version Numbering

We use semantic versioning (MAJOR.MINOR.PATCH):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

- [ ] All tests pass
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] Version bumped
- [ ] Docker images built
- [ ] Production deployment tested

## Getting Help

### Resources

- **Documentation**: Check README.md and other docs
- **Issues**: Search existing issues
- **Discussions**: Use GitHub Discussions
- **Community**: Join our community channels

### Contact

- **Maintainers**: @maintainers
- **Email**: support@apbdanalysis.com
- **Website**: https://apbdanalysis.com

## Recognition

Contributors will be recognized in:

- **CONTRIBUTORS.md**: List of all contributors
- **Release notes**: Mentioned in releases
- **Documentation**: Credited in relevant sections

Thank you for contributing to APBD Analysis 2026! 🎉
