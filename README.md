# 🚀 VELORA — SaaS POS Enterprise Platform

> Platform SaaS POS (Point of Sale) multi-tenant untuk UMKM Indonesia dengan AI-powered business insights.

---

## 📋 Overview

VELORA adalah platform POS berbasis SaaS yang dirancang untuk membantu UMKM Indonesia mengelola bisnis mereka dengan mudah. Dilengkapi dengan fitur AI untuk memberikan insight bisnis yang cerdas.

## 🏗️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 13 · PHP 8.3+ |
| **Frontend** | Next.js 15 (React) · TypeScript · Tailwind CSS |
| **Database** | MySQL 8.0+ |
| **Cache/Queue** | Redis 7+ |
| **Search** | Laravel Scout + Meilisearch |
| **Payment** | Midtrans Snap API |
| **AI Engine** | OpenAI GPT-4o (Platform-level key) |
| **Storage** | S3-compatible (MinIO for dev) |
| **Realtime** | Laravel Reverb (WebSocket) |

## 🧩 Modules (15 Modules)

### Core Modules
- **Tenant** — Business registration, branch management, tenant isolation
- **Auth** — Login, registration, token management, password reset
- **Subscription** — Plans, billing, Midtrans integration, feature gating

### Business Modules
- **POS** — Products, variants, categories, barcodes, unit conversion
- **Sales** — Transactions, shifts, payment methods, refunds
- **Inventory** — Warehouses, stock movements, opname, batch/expiry
- **Purchasing** — Suppliers, purchase orders, receiving
- **Finance** — Expenses, journals, cash flows, profit/loss
- **CRM** — Customers, loyalty points, vouchers

### Support Modules
- **Employee** — Staff management, attendance, roles per branch
- **Notification** — Email, push, in-app notifications
- **Report** — Sales, stock, financial reports, export
- **Audit** — Activity logs, audit trails, data change history
- **Setting** — Business settings, POS config, API keys

### Intelligence Module
- **AI** — Smart insights, sales forecasting, restock suggestions, anomaly detection

## 📁 Documentation Structure

```
velora-project/
├── implementation_plan.md          # Master architecture plan
├── ai_architecture.md              # AI module design
├── infrastructure_architecture.md  # Multi-tenant, subscription, inventory, roles
├── infrastructure_architecture_part2.md  # Financial, events, queue, cache, security
├── database_architecture_part1.md  # Core DB tables
├── database_architecture_part2.md  # Business DB tables
├── database_architecture_part3.md  # Support DB tables
├── database_architecture_addendum.md  # Multi-currency, Midtrans, AI tables
├── module_auth.md                  # Auth module blueprint
├── module_tenant_part1/2.md        # Tenant module blueprint
├── module_subscription_part1/2.md  # Subscription module blueprint
├── module_pos_part1/2/3.md         # POS module blueprint
├── module_inventory_part1/2/3.md   # Inventory module blueprint
├── module_sales_part1/2/3.md       # Sales module blueprint
├── module_crm_part1/2.md           # CRM module blueprint
├── module_finance_part1/2.md       # Finance module blueprint
├── module_settings_reports.md      # Settings & Reports blueprint
├── setup_guide_part1/2.md          # Laravel setup guide
└── decisions.md                    # Architecture decisions log
```

## 🎯 Key Decisions

| Question | Decision |
|----------|----------|
| **Framework** | Laravel 13 (PHP 8.3+) |
| **Frontend** | Next.js 15 + TypeScript + Tailwind CSS |
| **Payment** | Midtrans Snap API |
| **Currency** | Multi-currency (default IDR) |
| **AI Key** | Platform-level (Velora manages) |
| **Receipt** | PDF/Browser print (MVP) → ESC/POS (Phase 2) |
| **Deployment** | Shared Hosting → VPS (MVP) → Cloud (Scale) |
| **Billing** | Dual: Auto-Charge (Midtrans) + Manual Transfer |

## 📊 Project Status

- [x] Architecture Planning
- [x] Database Design (~71 tables)
- [x] Module Blueprints (15 modules)
- [x] Infrastructure Design
- [x] AI Architecture
- [ ] Backend Implementation
- [ ] Frontend Implementation
- [ ] Testing
- [ ] Deployment

## 📄 License

Private — All rights reserved.

---

*Built with ❤️ for Indonesian SMEs (UMKM)*
