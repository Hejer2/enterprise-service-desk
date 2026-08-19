# Enterprise Service Desk & Operations Management System

An Enterprise-grade Service Desk & Operations Management System featuring a **Symfony 6.4 (PHP 8.3)** backend with **Doctrine ORM** & **MySQL 8.0**, responsive Web Portal (Twig, Dark/Light theme, Messenger-style threads), **Cross-Platform Mobile App (Flutter / Dart / Riverpod)**, automated multi-tier SLA Escalation engine, Knowledge Base, automated business workflows & approval chains, Executive Analytics, and an **AI Assistant Suite**.

---

## 1. System Architecture

```
+-----------------------------------------------------------------------------------+
|                                Client Applications                                |
|  +-------------------------------------+   +------------------------------------+  |
|  |        Symfony Web Portal           |   |      Flutter Mobile Application    |  |
|  | (Twig, Turbo, SSE Realtime Events)  |   | (Android, iOS, Web - Riverpod/Dio) |  |
|  +------------------+------------------+   +-----------------+------------------+  |
+---------------------|----------------------------------------|--------------------+
                      |                                        |
                      v                                        v
+-----------------------------------------------------------------------------------+
|                        Nginx Reverse Proxy & Load Balancer                        |
|                     (HTTP 80 / HTTPS 443, SSL Termination, Gzip)                  |
+-------------------------------------+---------------------------------------------+
                                      |
                                      v
+-----------------------------------------------------------------------------------+
|                          Symfony PHP 8.3 FPM Application                          |
|  - RESTful API & Web Controllers       - Real-time Event Broadcaster (SSE)        |
|  - Multi-Tier SLA & Escalation Engine   - Multi-Level Approval Engine              |
|  - Automation Engine (CRON-driven)     - AI Provider Integration (Mock/OpenAI)     |
|  - Lexik JWT Token Authenticator       - Audit & Activity Logging                  |
+---------------------+-----------------------------------+-------------------------+
                      |                                   |
                      v                                   v
+------------------------------------+   +------------------------------------------+
|          MySQL 8.0 Database        |   |       External AI Provider (LLM API)     |
| (Doctrine ORM, Normalized Schema)  |   |     (OpenAI GPT-4o / Compatible API)     |
+------------------------------------+   +------------------------------------------+
```

---

## 2. Technology Stack

| Layer | Technologies |
|---|---|
| **Backend** | PHP 8.3, Symfony 6.4 LTS, Doctrine ORM, Lexik JWT, Monolog, Symfony Mailer, Symfony Messenger |
| **Web Frontend** | Twig, Vanilla JS / ES6, CSS3 (Light/Dark themes, Responsive layout), Server-Sent Events |
| **Mobile App** | Flutter 3.x, Dart, Flutter Riverpod (State Management), Dio (HTTP/JWT), Material 3 Design |
| **Database** | MySQL 8.0 (InnoDB, Foreign Key Constraints, Normalized Indexes) |
| **Infrastructure** | Docker, Docker Compose, Nginx Alpine, Multi-stage Docker build, OPCache |
| **CI / CD** | GitHub Actions (CI Tests, GHCR Docker Build, SSH Cloud Deployment) |
| **Testing** | PHPUnit 12 (Backend 36/36 tests), Flutter Test (Mobile 43/43 tests) |

---

## 3. Core Features

### A. Web Application
- **Role-Based Portals**: Tailored interfaces for `ROLE_ADMIN`, `ROLE_IT_TECH`, `ROLE_MAINTENANCE_TECH`, `ROLE_HR`, and `ROLE_EMPLOYEE`.
- **Interactive Ticket Workspace**: Full conversation thread, internal technician notes, canned response insertion, file attachments, and CSAT ratings.
- **SLA & Escalation Engine**: Dynamic response/resolution timers, AT_RISK alerts, pause on customer wait, and automated breach escalations.
- **Workflow Automation & Approvals**: Configurable trigger-action rules and multi-level approval hierarchies.
- **Executive Analytics**: KPI metric cards, SLA breach charts, team workload distribution, and CSV/PDF export.
- **Dark/Light Theme & Localization**: Full theme toggle and multi-language support (English, French, Arabic RTL).

### B. Flutter Mobile Application
- **Universal Cross-Platform**: Native Android and iOS support with adaptive layouts.
- **State Management & Offline Resilience**: Powered by `flutter_riverpod` with defensive JSON model parsing.
- **Deep Ticket Navigation**: Instant push navigation to ticket conversations directly from top-level unread notifications.
- **Interactive Modals**: In-app SLA countdown cards, CSAT rating modal, and image lightbox.

### C. AI Assistant Suite
1. 🏷️ **AI Ticket Classification (`classify`)**: Analyzes ticket title/description to predict category, priority, and team routing.
2. 📝 **AI Ticket Summary (`summarize`)**: Synthesizes issue details, actions taken, and next troubleshooting steps.
3. 💬 **AI Reply Draft (`reply`)**: Generates empathetic, context-aware reply drafts directly into the reply composer.
4. 🔍 **Similar Tickets Discovery (`similar`)**: Queries historically resolved tickets with matching signatures.
5. 📚 **Knowledge Base AI Solution (`knowledge`)**: Synthesizes verified resolutions from published knowledge base articles.
6. 🛠️ **Resolution Steps Recommendation (`resolution`)**: Recommends multi-step diagnostic and remediation protocols.

---

## 4. Local Development Setup

### Prerequisites
- PHP 8.3+ with `pdo_mysql`, `intl`, `zip` extensions
- Composer 2.x
- Flutter SDK 3.x
- Docker & Docker Compose

### Quick Start
```bash
# 1. Clone the repository
git clone https://github.com/your-org/service-desk.git
cd service-desk

# 2. Configure Environment Variables
cp .env.example .env
cp backend/.env.example backend/.env

# 3. Start Local Docker Services
docker compose up -d

# 4. Run Database Migrations & Seeds
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console app:kb:seed-defaults
docker compose exec php php bin/console app:automation:seed-defaults
docker compose exec php php bin/console app:sla:seed-defaults

# 5. Launch Flutter Mobile App
cd mobile
flutter pub get
flutter run
```

---

## 5. Environment Variables & Security

Copy `.env.example` to `.env` and configure appropriate values for your environment.

| Variable | Description | Default / Example |
|---|---|---|
| `APP_ENV` | Application environment (`dev`, `prod`, `test`) | `dev` |
| `APP_SECRET` | Secret key for CSRF and session signing | `CHANGE_ME_IN_PRODUCTION` |
| `DATABASE_URL` | MySQL database connection string | `mysql://servicedesk:password@db:3306/servicedesk?serverVersion=8.0.32` |
| `JWT_PASSPHRASE` | Passphrase used to unlock JWT private key | `CHANGE_ME_JWT_PASSPHRASE` |
| `AI_PROVIDER` | AI backend engine (`mock`, `openai`) | `mock` |
| `AI_API_KEY` | API Key for LLM provider (when not in mock mode) | `""` |
| `AI_MODEL` | AI model identifier | `gpt-4o-mini` |

> [!IMPORTANT]
> Never commit `.env`, `.env.local`, or `*.pem` private keys to version control. They are strictly ignored via `.gitignore`.

---

## 6. Automated Testing

### Backend PHPUnit Tests
```bash
cd backend
php bin/phpunit
# Result: 36/36 tests passing (100%)
```

### Mobile Flutter Tests
```bash
cd mobile
flutter analyze
flutter test
# Result: 43/43 tests passing (100%)
```

---

## 7. Continuous Integration & Deployment (CI/CD)

The repository includes pre-configured GitHub Actions workflows in `.github/workflows/`:

1. **`ci.yml`**: Runs on every pull request and push to `main`. Executes backend migrations, PHPUnit test suites, Flutter static analysis (`flutter analyze`), and Flutter unit/widget tests.
2. **`docker.yml`**: Builds production-ready container images upon successful CI and pushes them to GitHub Container Registry (`ghcr.io`).
3. **`deploy.yml`**: Deploys images to production server via SSH, executes non-destructive database migrations, and verifies endpoint health (`/health`).

### Required GitHub Actions Secrets for Deployment
- `PROD_SERVER_HOST`: Production server IP or hostname.
- `PROD_SERVER_USER`: SSH deployment user.
- `PROD_SERVER_SSH_KEY`: Private SSH key for server authentication.

---

## 8. Current Deployment Status

> [!NOTE]
> **Deployment Status**: **Ready for Production Deployment**.
> The codebase is fully containerized and verified locally with passing test suites. It is prepared for initial rollout to your chosen Cloud VPS (AWS, Azure, GCP, DigitalOcean, Hetzner, or on-premise infrastructure).

### Cloud VPS Deployment Steps
```bash
# 1. SSH into your Cloud VPS and install Docker & Docker Compose
sudo apt update && sudo apt install -y docker.io docker-compose-v2

# 2. Clone repository and create production .env
git clone https://github.com/your-org/service-desk.git /opt/service-desk
cd /opt/service-desk
cp .env.example .env
# Edit .env with your secure production passwords and secrets

# 3. Launch stack with production configuration
docker compose -f docker-compose.prod.yml up -d

# 4. Run database migrations
docker compose -f docker-compose.prod.yml exec php php bin/console doctrine:migrations:migrate --no-interaction

# 5. Verify system health
curl -f http://localhost/health
```

---

## 9. License

This project is licensed under the proprietary enterprise license. All rights reserved.
