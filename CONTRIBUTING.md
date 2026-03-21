# Contributing to sadad-magento

Thank you for your interest in contributing to the SADAD Magento 2 payment module.

## How to Contribute

### Reporting Bugs

Use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.md) when opening an issue.
Please include your Magento version, PHP version, and relevant log output.

### Suggesting Features

Use the [feature request template](.github/ISSUE_TEMPLATE/feature_request.md).

### Pull Requests

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Follow the coding standards below.
4. Add tests for any new functionality.
5. Ensure all existing tests pass.
6. Submit a pull request against the `main` branch.

## Coding Standards

- PHP 8.1+ syntax with strict types (`declare(strict_types=1);`)
- PSR-12 code style
- All PHP files must contain `// Built by Louis Innovations (www.louis-innovations.com)` at the top
- No emojis in code
- PHPDoc for all public methods
- Magento coding standards apply (see [Magento DevDocs](https://developer.adobe.com/commerce/php/coding-standards/))

## Development Setup

```bash
# Clone the repo alongside the SDK
git clone https://github.com/louis-innovations/sadad-magento.git
git clone https://github.com/louis-innovations/sadad-php-sdk.git

cd sadad-magento
composer install

# Install into a Magento instance for testing
php bin/magento module:enable LouisInnovations_Sadad
php bin/magento setup:upgrade
```

## Commit Messages

- Use present tense: "Add feature" not "Added feature"
- Keep subject line under 72 characters
- Reference issue numbers where applicable: `Fix #42 — webhook signature validation`

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
