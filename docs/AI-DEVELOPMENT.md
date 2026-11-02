# AI-assisted development record

Date: 2 November 2026

## Human brief
Afrijal requested an Inventory Mini portfolio project covering products, incoming/outgoing stock, transaction history, reports, negative-stock prevention, screenshots, and test results. The project is intended to support an application for Junior AI Developer.

## Actual roles in this session
- **Afrijal:** chose the project scope and requested its implementation.
- **Codex:** proposed the implementation, wrote the source and automated tests, ran the checks, and prepared documentation and screenshots.
- Human review and acceptance are still pending. Do not describe the automated checks as tests Afrijal personally performed.

## Implementation choices
- PHP and SQLite keep the local demo easy to run without a database service or frontend build.
- A server-side balance check is essential; a browser-only check could be bypassed.
- Each balance update and ledger insertion share one immediate SQLite transaction to avoid partial updates and competing stock writes.
- Product balances begin at zero; sample balances have corresponding receipt entries.
- Reports explicitly distinguish period movements from current balance.
- No private employer code or real business data was imported.

## Problems actually encountered
1. Initial automated network tests were restricted by the execution environment. They were rerun with access to the local demo server and a separate test database.
2. Playwright's default browser binary was unavailable. UI testing was configured to use the installed Chrome executable in headless mode.

These are tooling issues, not invented application bugs. Overdraw is an intentionally tested business rule, not evidence that a previously broken implementation was fixed.

## Review checklist for Afrijal
- Create a product and receive 10 units.
- Issue 4 units and explain why the balance is 6.
- Try issuing 7 and confirm the error, unchanged stock, and unchanged ledger.
- Explain minimum-stock alerts and current-balance report semantics.
- Read `api.php` and explain the transaction around the stock/ledger writes.
- Identify changes you would make for authentication, multiple users, and production hosting.
- Add your own review notes and verified decisions after personally running the app.
