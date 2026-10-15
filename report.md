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
