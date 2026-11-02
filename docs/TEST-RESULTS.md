# Test results

Date: 2 November 2026. API checked using PHP 8.3.33 and a separate SQLite test database.

## API integration tests: 16 passed
1. Missing CSRF token rejected.
2. Product created.
3. Duplicate SKU rejected after uppercase normalisation.
4. Receipt of 10 accepted.
5. Issue of 4 leaves 6.
6. Issue of 7 rejected.
7. Rejection leaves balance and history unchanged.
8. Zero quantity rejected.
9. Negative quantity rejected.
10. Fractional quantity rejected.
11. Missing product rejected.
12. Invalid movement type rejected.
13. Withdrawal equal to balance accepted.
14. Withdrawal from zero rejected.
15. Sample data cannot overwrite a non-empty inventory.
16. Final balance agrees with the expected transaction history.

## Scope limits
These checks do not constitute a security audit, load test, or production certification. Concurrency is addressed by transaction design but has not been stress-tested. Human owner acceptance is pending.

## Browser checks: passed
- Dashboard loads with fictional sample data.
- Overdraw error is visible in the transaction dialog.
- Report view renders and CSV downloads.
- Mobile viewport at 390px has no document-level horizontal overflow; tables/navigation scroll within their containers.
- No uncaught JavaScript errors during the tested flow.
- Desktop, mobile, report, and validation screenshots were visually reviewed.

Screenshots: dashboard.png, mobile.png, report.png, validation.png. The rejected browser transaction deliberately leaves demo stock unchanged.
