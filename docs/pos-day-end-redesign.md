# POS Invoicing + Day-End Reconciliation — Design

**Status:** Approved design (2026-06-17). Build in signed-off modules.
**Branch:** `feature/pos-day-end` (off `production-current`).
**Currency:** ZMW.

## Goal

Replace the current "one batch report per day" flow with:

1. **POS capture during the day** — record individual invoices/sales as they happen,
   each tagged with how it was paid. Removes the same-day `sale_date` UNIQUE error.
2. **Day-end reconciliation wizard** — aggregate the day's invoices, show the
   settlement breakdown (Cash / Bank / Mobile Money / Outstanding Debt), capture cash
   expenses, confirm, and approve. Approval locks that day's invoices.

## Decisions (signed off)

- Payment method captured **per invoice** (POS-style) → day-end auto-totals, no manual entry.
- **One whole-shop day-end per day** (all reps' invoices). Admin approves.
- Invoices are **internal transaction records** (no required customer details).
  `customer_name` / `note` are **optional**, used mainly to identify a credit debtor.
- Settlement types tracked: **Cash, Cash @ Bank, Mobile Money, Outstanding Debt**.

## Data model (additive — nothing existing is dropped)

### New table `sales` (one row per invoice)
| Column | Type | Notes |
|---|---|---|
| id | bigint | |
| reference | string, unique | e.g. `INV-20260617-0007` |
| user_id | FK users | who recorded it |
| business_date | date | trading day it belongs to |
| payment_method | string | `cash` / `bank` / `mobile_money` / `credit` (App\Enums\PaymentMethod) |
| total_amount | decimal(12,2) | invoice total |
| amount_due | decimal(12,2) | outstanding (>0 only for credit) |
| customer_name | string, nullable | optional; required when credit |
| note | text, nullable | |
| status | string | `completed` / `void` |
| day_end_report_id | FK daily_sales_reports, nullable | set on approval → locks invoice |
| timestamps | | |

### New table `sale_items`
`sale_id` FK, `product_id` FK nullable, `product_name`, `quantity`, `unit_price`, `total_price`.

### Extend `daily_sales_reports` (the day-end header)
Add: `total_cash`, `total_bank`, `total_mobile_money`, `total_outstanding` (decimal 12,2, default 0),
`approved_by` (FK users nullable), `approved_at` (timestamp nullable).
The existing `status` enum is **left untouched**; a report is *approved* iff `approved_at` is set.
The existing `sale_date` UNIQUE constraint now correctly enforces one day-end per day.

## Money math (replaces manual deductions)

```
gross_sales  = Σ sales.total_amount        (status = completed, for the day)
total_cash   = Σ where payment_method = cash
total_bank   = Σ where payment_method = bank
total_mobile = Σ where payment_method = mobile_money
total_outstanding = Σ credit amount_due

Cash at Hand = total_cash − cash_expenses(deductions)
Check:        total_cash + total_bank + total_mobile + total_outstanding = gross_sales
```

Fixes the current bug where `cash_at_hand = total_sales − deductions` treats *all* sales as cash.

> Assumption: single tender per invoice (one payment method). Split payments = future enhancement.

## Day-end wizard (admin)

1. **Review** — day's invoices + auto-computed Cash/Bank/Mobile/Debt breakdown.
2. **Expenses** — add cash expenses paid from the drawer (optional).
3. **Count** — enter physically counted cash → variance vs expected (optional).
4. **Confirm** — summary + Cash at Hand → **Approve** (creates the `daily_sales_reports`
   row with `approved_at`/`approved_by`, links the day's `sales`, locks them).

## Modules

| Module | Scope | Status |
|---|---|---|
| 0 — Schema | migrations (additive) + `Sale`/`SaleItem`/`PaymentMethod` + `DailySalesReport` extensions + tests | in progress |
| 1 — POS capture | POS screen (reuses product search + quick-create), per-invoice payment, today's-invoices list + void, stock deducted per sale via `StockService` | |
| 2 — Day-end wizard | 4-step wizard, approval, locking | |
| 3 — Reporting | dashboard / monthly / PDF read new sales + settlement breakdown; legacy reports stay viewable | |

## Permissions
- **sales_rep** — record invoices, view own.
- **admin** — run/approve day-end, view all, reports.

## Data safety
- All migrations additive + reversible; existing tables untouched.
- Stock is deducted per invoice via `StockService` (consolidates the previously duplicated
  stock-deduction logic).
- Old "Record Daily Sales" batch screen kept as fallback until POS is proven, then retired.
