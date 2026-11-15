# Nexus Stock

## Project overview

Nexus Stock is a Laravel 12 application using Spatie Permission. Superadmins
are authorized through `Gate::before`. The Nexus design system is implemented
in `public/assets/css/nexus.css` and `public/assets/js/nexus.js`.

## Modules built

- Users: create, edit, soft delete, restrict, restore, and allocation
  management.
- Roles and permissions.
- Branches.
- Warehouses, including `branch_id`.
- Locations: country > state > city > upazila, postcode, and address.
- Departments and designations.
- Categories, brands, and products.
- Activity log.
- Dashboard.

## Access model

Permissions are named `<module>.<action>`. `PermissionSeeder` currently defines:

- `dashboard.view`
- `roles.view`, `roles.create`, `roles.edit`, `roles.delete`, `roles.assign`
- `permissions.view`, `permissions.edit`
- `users.view`, `users.create`, `users.edit`, `users.delete`, `users.status`
- `departments.view`, `departments.create`, `departments.edit`,
  `departments.delete`
- `designations.view`, `designations.create`, `designations.edit`,
  `designations.delete`
- `locations.view`, `locations.create`, `locations.status`
- `warehouses.view`, `warehouses.create`, `warehouses.edit`,
  `warehouses.delete`
- `branches.view`, `branches.create`, `branches.edit`, `branches.delete`
- `categories.view`, `categories.create`, `categories.edit`,
  `categories.delete`
- `brands.view`, `brands.create`, `brands.edit`, `brands.delete`
- `products.view`, `products.create`, `products.edit`, `products.delete`

User allocations use the `branch_user` and `warehouse_user` pivots. Users also
have an `access_all` flag. `accessibleBranchIds()` returns all branch IDs for
superadmins and `access_all` users, otherwise only assigned branch IDs.
`accessibleWarehouseIds()` returns all warehouse IDs for those users, otherwise
the union of directly assigned warehouses and warehouses whose `branch_id`
belongs to an assigned branch.

`LandingService` sends a user after login to the dashboard when the user has
`dashboard.view`, then to the first permitted module in its configured order.
Users with no permitted destination receive the no-access page. Unauthorized
requests use the Nexus 403 page.

The current route list contains 83 routes. Destructive resource routes use
`DELETE`; the user status route is `PATCH`. All admin routes have a permission
middleware. The activity-log route is protected by `roles.view`; there is no
separate activity-log permission in `PermissionSeeder`.

## Activity log

The `RecordsActivity` concern uses Spatie Activitylog for User, Role, Branch,
Warehouse, Category, Brand, and Product model events. Login and logout are
also recorded by centralized listeners. The global activity-log page supports
causer/user, module/subject, and date-range filters and pagination; the role
audit-log page is also paginated and eager-loads causers.

Sensitive password, remember-token, API-token, and token fields are excluded
by the shared concern. The activity-log page is read-only and is protected by
`roles.view`.

## Commit history

Commits from the start of the design-system work, newest first:

| Hash | Subject |
|---|---|
| `70ab7b8` | feat(auth): role-based post-login landing and scoped dashboard |
| `9f321c6` | feat(auth): permission-aware sidebar and routes |
| `e8314e0` | fix(activity-log): centralize model audit events and filters |
| `425740c` | fix(roles): harden permission and role management |
| `a6aa851` | fix(users): show branch and warehouse location in allocations |
| `ad6c36a` | test(users): add status and delete guard tests |
| `e7bbe43` | fix(users): make store and update atomic |
| `58a4051` | perf(catalog): remove N+1 product counts |
| `a3637bd` | test: cover users.edit, branch-based warehouse access and csrf |
| `a4d24f7` | fix(seed): make city codes and slugs district-based |
| `4f6a2b0` | feat(warehouse): add branch assignment to warehouses |
| `fcc52aa` | test(crud): migrate @test doc-comments to PHPUnit attributes |
| `c251345` | fix(tests): repair the feature suite (CSRF, seeding, stale assertions) |
| `c13c7bc` | fix(users): qualify id when plucking branch and warehouse ids in edit form |
| `9928348` | refactor(location): remove dead union and thana leftovers |
| `f1c8886` | feat(location): finalize upazila rename and view updates |
| `9261a6e` | chore: ignore uploads and tmp |
| `7da7e24` | refactor(location): rename thanas to upazilas and drop the union layer |
| `c7c27f1` | fix(access): name the warehouse_user pivot table in the relations |
| `c58af11` | fix(inventory): add brand and product placeholder images |
| `ff9af38` | docs: update report.md with inventory module documentation |
| `b657faa` | feat(inventory): add sidebar group, permissions and routes |
| `985e649` | feat(products): add product management |
| `cdb6c6d` | feat(brands): add brand management |
| `b297828` | feat(categories): add category management |
| `5defebc` | feat(users): add access assignment to user forms |
| `3f5d036` | feat(access): add branch and warehouse user scope |
| `ac4c0ca` | feat(users): add restrict and restore with login and session enforcement |
| `421e413` | feat(users): add soft delete with guards |
| `59bcd72` | fix(users): sync role_id and clean up old photo in update |
| `6f3352b` | docs: record the welcome, user edit and chart-token work in the report |
| `f53e646` | style(layout): take the dashboard chart fill from a token |
| `906e7b5` | feat(users): add the edit and update actions the routes already pointed at |
| `97ecd51` | style(welcome): drop the last `<style>` block and inline mix-blend-mode |
| `3b1a1b7` | style(views): tokenise the last inline colours so no view holds a colour literal |
| `7789a18` | docs: record the login token migration and location cleanup in the report |
| `d52624d` | style(auth): move the login page onto the Nexus token system |
| `581670d` | docs: correct the blade file and compiled template counts in the report |
| `1fc4b71` | docs: add the design system refinement report |
| `ee78ee5` | style(location): remove dead edit and delete controls from the location list |
| `dbbcd9c` | style(design-system): fix empty-state cell padding and bootstrap colour leaks |
| `3be2d61` | style(role): redesign role list, create, edit, permission assign and audit log |
| `fe9d73f` | style(permission): redesign permission list and edit |
| `93d71de` | style(warehouse): redesign warehouse list, create and edit |
| `d9b5963` | style(location): redesign location list and create |
| `33df1d4` | style(designation): redesign designation list, create and edit |
| `9312385` | style(department): redesign department list, create and edit |
| `e6bfdfa` | style(branch): redesign branch list, create and edit |
| `fbd1332` | style(layout): refine topbar and sidebar alignment and active states |
| `72574c6` | style(design-system): refine tables, typography, topbar/sidebar and interaction states |
| `ecac506` | style(users): redesign users list and create form |
| `78dcb1d` | feat(users): add list search, filters and page-size control |
| `9daf929` | style(dashboard): redesign dashboard |
| `d7916eb` | style(auth): redesign login page |
| `ef03c6c` | style(layout): redesign master, navbar and sidebar |
| `03c2778` | style(design-system): add Nexus tokens, nexus.css and nexus.js |

The three older location-related commits `f1c8886`, `9261a6e`, and `7da7e24`
contain literal `\n` sequences in their stored commit messages.

## Testing

Run the suite with:

```bash
php artisan test
```

`phpunit.xml` configures SQLite `:memory:` and `BCRYPT_ROUNDS=4`. The fresh
run for this report passed **88 tests and 345 assertions** in **10.43s**.

Feature coverage includes:

- `AccessScopeTest.php`: branch and warehouse allocation visibility.
- `ActivityLogTest.php`: product activity, sensitive-property exclusion,
  pagination, and module filtering.
- `AuthenticationTest.php`: login, authentication, route protection, and
  CSRF rejection when the middleware is explicitly enabled.
- `CrudValidationTest.php`: CRUD validation, user edit/location rendering,
  and atomic user rollback.
- `DatabaseAndSecurityTest.php`: schema/security regressions and catalog
  query bounds.
- `HttpRoutesNoDbTest.php` and `StartupAndRoutesTest.php`: route and startup
  behavior without database-dependent setup.
- `PermissionVisibilityTest.php`: sidebar visibility, landing, superadmin
  access, and scoped dashboard counts.
- `RoleManagementTest.php`: permission sync/cache reset, uniqueness,
  deletion protection, and superadmin protection.
- `UserGuardTest.php`: inactive users, status authorization, self/last-
  superadmin guards, and session cleanup.
- `ExampleTest.php` and `NonDbStaticTests.php`: baseline/unit and static
  checks.

## Incident note

Earlier tests ran against a stale `bootstrap/cache/config.php`, which booted
the application with `APP_ENV=local` and the MySQL `inventory` database
instead of SQLite. This was fixed with:

```bash
php artisan config:clear
```

The test base class still deliberately disables CSRF middleware globally for
feature tests; `AuthenticationTest` explicitly enables it for the 419
regression test. Never run `php artisan config:cache` in the development
checkout. Always run `php artisan config:clear` before tests.

## Deploy/setup for a real database

After configuring the real database connection:

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
```

On the Roles page, assign the required permissions to each non-superadmin
role. In particular, assign `dashboard.view`, the relevant
`categories.*`, `brands.*`, and `products.*` permissions, and `roles.view`
for the activity log. `PermissionSeeder` creates permissions and synchronizes
the superadmin role; it does not auto-assign permissions to other roles.

## Known issues / decisions pending

- Soft-deleted users retain the database-unique email value. Soft-deleted
  products retain unique SKU values; product slug validation also continues
  to treat the slug as taken. Reuse policy for deleted records is undecided.
- The three older commits listed above contain literal `\n` sequences in
  their stored messages.
- Feature tests globally disable CSRF and cover enabled CSRF separately in one
  focused test.
- There is no dedicated `activity-log.view` permission; activity-log access
  currently follows `roles.view`.

## NEEDS MANUAL BROWSER CHECK

- Sidebar visibility for each role and allocation.
- 403 page and no-access page appearance and navigation.
- Scoped dashboard widgets and counts.
- User form branch/warehouse location labels.
- Activity-log filters, pagination, and display.
- Role permission grouping and assignment controls.

## Not built yet

Suppliers, stock levels, stock movements, purchases, sales, transfers,
adjustments, reports, and settings.

## Phase 9: opening stock approval workflow

Implemented in three logical commits:

- `feat(stock): add opening request schema and approval service`
- `feat(stock): add opening request UI and scoped routes`
- `feat(stock): add opening request notifications and audit events`

Follow-up fix: `f9cf36d fix(stock): accept serial-count opening request lines`.

New migration: `2026_11_16_000001_create_opening_stock_requests_tables.php`.
It creates `opening_stock_requests` and `opening_stock_request_lines`, including
the requested status/location index and request-number uniqueness.

Routes:

- `GET|POST /admin/stock/opening` (`admin.stock.opening`,
  `admin.stock.opening.store`)
- `GET /admin/stock/requests` (`admin.stock.requests.index`)
- `GET /admin/stock/requests/{openingStockRequest}`
  (`admin.stock.requests.show`)
- `POST /admin/stock/requests/{openingStockRequest}/approve`
  (`admin.stock.requests.approve`)
- `POST /admin/stock/requests/{openingStockRequest}/reject`
  (`admin.stock.requests.reject`)
- `POST /admin/stock/requests/{openingStockRequest}/cancel`
  (`admin.stock.requests.cancel`)
- `POST /admin/stock/requests/{openingStockRequest}/resubmit`
  (`admin.stock.requests.resubmit`)

The opening form now submits multiple escaped product lines for approval.
Approval remains the only path that calls `StockService::recordOpening` or
`recordSerialOpening`; ledger references point to the request and record the
approver. The notification mail channel is disabled by default via
`STOCK_MAIL_NOTIFICATIONS`, and self-approval is configurable via
`STOCK_ALLOW_SELF_APPROVAL`.

Changed files include the request models/service/event/listener/notification,
stock controller/service/config/seeder, stock routes and views, sidebar and
movement reference link, `.env.example`, and the focused feature tests
`tests/Feature/OpeningStockRequestTest.php` and the deliberately updated
opening-flow cases in `tests/Feature/StockUiTest.php`:

- `test_opening_stock_submission_is_pending_and_second_pending_opening_is_rejected`
- `test_opening_stock_rejects_invalid_quantity_and_unwritable_location`

Validation: `php artisan test` passed **196 tests and 762 assertions** on
SQLite. No real-DB migrate/seed was run. Browser rendering and MySQL
concurrency behavior were not verified in this environment.

## Phase 9 follow-up: opening form Blade regression

Commit: `c53253b fix(stock): compile opening request form safely`.

Fixed the opening form's nested `@json` expression by preparing product
options in `StockLevelController`. The form now safely preserves old line
input, dynamically restores serial/quantity fields, and displays escaped
field errors beside the relevant controls. Added regression coverage for all
stock page GETs, full Blade cache compilation with cleanup, and visible
opening-form validation errors.

Changed files:

- `app/Http/Controllers/Admin/StockLevelController.php`
- `resources/views/admin/stock/opening.blade.php`
- `tests/Feature/StockUiTest.php`
- `report.md`

Validation: `php artisan view:cache` followed by `php artisan view:clear`
passed, and `php artisan test` passed **199 tests and 775 assertions** on
SQLite. Browser rendering and MySQL concurrency behavior remain unverified.

---

# Supply-chain build

Work happens on branch `supply-chain-v1` (not merged into `main`). Migrations
were only authored, never executed against the real MySQL database; the full
suite runs on SQLite `:memory:`.

## Phase A - Transfers (warehouse -> supplied branch)

Status: **done**.

Commits (this phase): see `git log --oneline supply-chain-v1` for the commit
titled `feat(transfers): add warehouse-to-branch transfers with serial units`.

Migrations added:

- `2026_11_17_000001_create_document_sequences_table.php` — per-prefix/year
  counter used by `App\Support\DocumentNumber::next()`; the row is locked so
  two requests cannot receive the same number on MySQL.
- `2026_11_17_000002_create_transfers_tables.php` — `transfers`,
  `transfer_items`, `transfer_item_units` with short explicit index names.

Permissions added to `PermissionSeeder` (not seeded):

- `transfers.view`, `transfers.create`, `transfers.send`,
  `transfers.receive`, `transfers.cancel`
- plus forward-looking permissions for later phases: `suppliers.*`,
  `purchases.*`, `receiving.*`, `adjustments.*`, `counts.*`, `reports.view`,
  `activitylog.view`, `stock.discrepancies.resolve`, `stock.kpi.view`.

Routes (named, all permission-protected):

- `GET /admin/transfers` (`admin.transfers.index`)
- `GET /admin/transfers/create` (`admin.transfers.create`)
- `POST /admin/transfers/store` (`admin.transfers.store`)
- `GET /admin/transfers/backorders` (`admin.transfers.backorders`)
- `GET /admin/transfers/{transfer}` (`admin.transfers.show`)
- `GET /admin/transfers/{transfer}/delivery-note`
  (`admin.transfers.delivery-note`)
- `POST /admin/transfers/{transfer}/send` (`admin.transfers.send`)
- `POST /admin/transfers/{transfer}/receive` (`admin.transfers.receive`)
- `POST /admin/transfers/{transfer}/cancel` (`admin.transfers.cancel`)

Behaviour:

- Destination branch must belong to the source warehouse; validated in the
  service (and the form only lists supplied branches). Returns (branch ->
  supplying warehouse, reason required) share the same state machine and were
  already wired in this phase.
- `send()` locks the transfer row, rejects any status other than `draft`, and
  posts all lines through `StockService` in one transaction. Quantity lines
  post `transfer_out` / `transfer_in`; serial lines move units `in_stock ->
  in_transit -> in_stock` through the new
  `StockService::dispatchSerialUnits()` / `receiveSerialUnits()` methods.
  Insufficient stock rejects the whole send and changes nothing.
- `receive()` accepts per-line quantities and unit selections (the service
  refuses quantities above `sent_qty`); a full receipt is the default.
- Alerts page now has a real "Send stock" link that prefills warehouse,
  branch, product and `stock_alert_id`, and shows a "Stock on the way" badge
  while a sent transfer exists for that product + branch.
- Printable delivery note with a Code128 barcode of `transfer_no`.
- Notification on send to destination users holding `transfers.receive`
  (superadmin/`access_all` users resolved explicitly by
  `App\Support\StockRecipients`), never the actor.

Notable fix: `AppServiceProvider` registered
`SendOpeningStockRequestNotification` manually while the framework also
auto-discovers listeners in `app/Listeners`, which delivered duplicate
notifications. Both manual registrations were removed.

Tests added: `tests/Feature/TransferTest.php` (10 tests) covering full
quantity flow with ledger/levels and alert resolution, serial flow, insufficient
stock atomicity, double send/receive rejection, supplier/branch validation,
location scoping, 403s, unwritable store, page renders, notification
recipients and `stock:rebuild` cleanliness.

Last full run: **209 tests, 838 assertions** (`php artisan test`, SQLite).

Known limitations / unverified:

- Partial receipt already creates no `transfer_discrepancies` rows; Phase B
  adds the discrepancy table and lifecycle. Until then a partial receipt would
  leave the shortage unaccounted for (the service accepts the shorter quantity
  and the difference stays out of branch stock, but it is not tracked).
- Serial quantity on a line is derived from the selected units.
- MySQL row-lock behaviour, browser rendering and printing were not verified
  here.

## Phase B - Receiving exceptions, returns, backorders

Status: **done**. Phase A commit: `e9f5e48`.

Migration added: `2026_11_17_000003_create_transfer_discrepancies_table.php`
(`transfer_discrepancies`: item, type short|damaged|extra, qty, reason,
`stock_unit_ids` json for serial lines, status open|resolved, resolution
lost|found|written_off|returned, note, resolver, resolved_at).

Routes added:

- `POST /admin/transfers/discrepancies/{transferDiscrepancy}/resolve`
  (`admin.transfers.discrepancies.resolve`, `stock.discrepancies.resolve`)
- `POST /admin/transfers/{transfer}/send` now accepts per-line quantities
  (partial send) and serial unit selections.

Behaviour:

- `receiveWithQuantities()` accepts `received_qty` and `damaged_qty` per line
  (serial: arrived unit ids and damaged unit ids). Only good quantity enters
  the destination; shortage = sent - received - damaged creates a `short`
  discrepancy, damaged quantity creates a `damaged` discrepancy. Unticked
  serial units stay `in_transit` and are recorded on the discrepancy.
- `resolveDiscrepancy()` is locked, permission- and scope-checked and audited:
  `found` moves the quantity/units into the destination via an `adjustment`
  movement, `returned` moves it back to the source, `written_off` records a
  zero-delta adjustment for quantity products and marks serial units
  `written_off`, `lost` records no stock movement.
- Partial send (`send($user, $transfer, $lines)`) leaves `sent_qty <
  requested_qty`, which surfaces as a backorder badge on the detail page and
  on `admin.transfers.backorders`.
- `StockService` now emits `StockArrivedAtLocation` for positive
  purchase/transfer_in/opening/return_in/adjustment movements; the listener
  notifies warehouse users holding `transfers.create` that a backorder can be
  filled (never auto-sends).
- Discrepancy opened -> notification to source-location users with
  `transfers.create` (warehouse managers). Return sent -> destination
  (warehouse) users with `transfers.receive` via the existing send
  notification.
- Transfer detail shows the discrepancy table with an inline resolve form and
  a partial-send modal.

Tests added: `tests/Feature/TransferExceptionsTest.php` (8 tests) for partial
receive, damaged handling and write-off ledger effects, return flow, return
reason validation, partial send backorder + warehouse notification, serial
partial receive and found resolution, discrepancy permission/scope, page
render and rebuild cleanliness.

Last full run: **217 tests, 889 assertions** (`php artisan test`, SQLite).

Known limitations / unverified: MySQL concurrency, browser rendering of the
new modals, and printing are unverified. "Lost" discrepancies deliberately
record no stock movement (documented above).

## Phase C - Suppliers, purchase orders, goods receipts

Status: **done**. Phase B commit: `c1c5540`.

Migration added: `2026_11_17_000004_create_purchasing_tables.php` —
`suppliers` (soft deletes), `purchase_orders`, `purchase_order_lines`,
`goods_receipts`, `goods_receipt_lines` (accepted/damaged/short/returned,
serial numbers json), with short explicit index names.

Routes added:

- `admin.suppliers.index/create/store/edit/update/delete`
- `admin.purchases.index/create/store/show/edit/update/approve/cancel/print`
- `admin.receiving.index/create/store/show/post/supplier-return/print`

Behaviour:

- Supplier CRUD with unique code; deletion is refused while open purchase
  orders exist; activity log via `RecordsActivity`.
- Purchase orders: `PO-YYYY-NNNNNN` via `DocumentNumber`, draft/ordered based
  on `stock.purchases.require_approval`; approve (draft -> ordered), edit
  (draft only), cancel (draft/ordered/partial), printable PO.
- Goods receipts: created as a **draft** from a PO with the outstanding
  quantity prefilled, then posted explicitly. Posting locks the GRN and PO,
  is rejected twice, validates over-receipt against the outstanding quantity
  (config `STOCK_ALLOW_OVER_RECEIPT`), posts `purchase` movements through
  `StockService` (or `recordSerialPurchase()` for serial products), updates
  PO line `received_qty` and PO status (`partial`/`received`).
- Damaged/short quantities are stored on the GRN line and never enter stock.
  Supplier return posts `return_out` at the warehouse, capped by
  `damaged_qty - returned_qty`; serial damaged units are refused because they
  never entered stock.
- Optional product cost update on posting: `STOCK_PO_COST_UPDATE=off|last|
  weighted_average` (default off).
- Warehouse users only see POs/GRNs of their warehouses; all services also
  enforce permissions and `StockAccess::canWrite`.

Tests added: `tests/Feature/PurchaseTest.php` (11 tests) — supplier CRUD and
delete guard, PO lifecycle with partial then full receipt, damaged/short
handling, serial purchase units, over-receipt rejection and config override,
double post rejection, supplier return cap, cost update, scoping, 403s, page
renders and HTTP receipt flow. Ledger rebuild stays clean.

Last full run: **228 tests, 988 assertions** (`php artisan test`, SQLite).

Known limitations / unverified: no PO line editing after ordering, no partial
supplier-return UI for serial units, MySQL concurrency and browser printing
unverified.
