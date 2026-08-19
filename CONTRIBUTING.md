# Contribution Guidelines

Thank you for contributing to the Enterprise Service Desk & Operations Management System.

## Development Workflow

1. **Branching Strategy**: Create feature branches from `main` (`feature/your-feature-name` or `bugfix/issue-name`).
2. **Environment Configuration**: Copy `.env.example` to `.env` in both backend and mobile projects. Never commit real API keys or passwords.
3. **Local Testing Requirements**:
   - Backend PHPUnit tests: `cd backend && php bin/phpunit`
   - Mobile Flutter Analysis: `cd mobile && flutter analyze`
   - Mobile Flutter Tests: `cd mobile && flutter test`
4. **Pull Request Validation**: All PRs must pass automated GitHub Actions CI before merging to `main`.
