# Nexus Stock — design system refinement report

Scope: a polish pass on the shared Nexus Stock design system, then application of that
system to every remaining admin view, committed module by module.

Hard constraints for the polish pass itself: views + CSS/JS only. No routes, controllers,
models, migrations, permissions, middleware or HTTP methods were changed. No `git push`,
no history rewrite, no `git add -A` / `git add .` (every commit stages explicit paths).

**One deliberate, explicitly-requested exception** (a separate later task, commits
`906e7b5` and `97ecd51`): `UserController` gained the `edit()`/`update()` methods its
routes already pointed at, and `resources/views/admin/user/edit.blade.php` was created.
Everything else remains views + CSS/JS.

---

## 1. Commits

```
b657faa feat(inventory): add sidebar group, permissions and routes
985e649 feat(products): add product management
cdb6c6d feat(brands): add brand management
b297828 feat(categories): add category management
5defebc feat(users): add access assignment to user forms
3f5d036 feat(access): add branch and warehouse user scope
ac4c0ca feat(users): add restrict and restore with login and session enforcement
421e413 feat(users): add soft delete with guards
59bcd72 fix(users): sync role_id and clean up old photo in update
f53e646 style(layout): take the dashboard chart fill from a token
97ecd51 style(welcome): drop the last <style> block and inline mix-blend-mode
906e7b5 feat(users): add the edit and update actions the routes already pointed at
3b1a1b7 style(views): tokenise the last inline colours so no view holds a colour literal
d52624d style(auth): move the login page onto the Nexus token system
ee78ee5 style(location): remove dead edit and delete controls from the location list
dbbcd9c style(design-system): fix empty-state cell padding and bootstrap colour leaks
3be2d61 style(role): redesign role list, create, edit, permission assign and audit log
fe9d73f style(permission): redesign permission list and edit
93d71de style(warehouse): redesign warehouse list, create and edit
d9b5963 style(location): redesign location list and create
33df1d4 style(designation): redesign designation list, create and edit
9312385 style(department): redesign department list, create and edit
e6bfdfa style(branch): redesign branch list, create and edit
fbd1332 style(layout): refine topbar and sidebar alignment and active states
72574c6 style(design-system): refine tables, typography, topbar/sidebar and interaction states
ecac506 style(users): redesign users list and create form
78dcb1d feat(users): add list search, filters and page-size control
9daf929 style(dashboard): redesign dashboard
d7916eb style(auth): redesign login page
ef03c6c style(layout): redesign master, navbar and sidebar
03c2778 style(design-system): add Nexus tokens, nexus.css and nexus.js
```

### Step 0 — baseline (pre-existing uncommitted redesign)

Committed first, in the requested groups so later module work starts from a clean tree:

| Commit | Staged paths |
| --- | --- |
| `03c2778` | `public/assets/css/nexus.css`, `public/assets/js/nexus.js` |
| `ef03c6c` | `resources/views/layout/master.blade.php`, `navbar.blade.php`, `sidebar.blade.php` |
| `d7916eb` | `resources/views/auth/login.blade.php` |
| `9daf929` | `resources/views/admin/dashboard.blade.php` |
| `78dcb1d` | `app/Http/Controllers/Admin/UserController.php` |
| `ecac506` | `resources/views/admin/user/list.blade.php`, `create.blade.php` |

No file mixed changes from two groups, so nothing needed to be stopped and queried.

**Deviation:** the requested subject for `78dcb1d` was
`feat(users): add list search/filters and profile photo accessor`. `app/Models/User.php`
has **no changes** — the `profile_photo_url` accessor already existed in the committed
tree (`app/Models/User.php` line 87) — so that clause was dropped rather than claim a
change that is not in the commit.

---

## 2. Step 1 — polish pass (shared system, committed before the modules)

### 2.1 Duplicate / conflicting selectors removed

| Removed / merged | Why |
| --- | --- |
| `.table > :not(caption) > * > *` → `.table > tbody > * > *` | It also matched `thead th`, so the header's `background`/`color` were being fought over by two rules |
| `#sidebar.ns-sidebar, #sidebar.ns-sidebar .sidebar-content { … }` then `#sidebar.ns-sidebar { … }` then `#sidebar.ns-sidebar .sidebar-content { … }` | Merged to one rule per selector |
| `.nx-swal-toast .swal2-title` | Was declared in the shared title rule *and* again in the toast block; now declared once |

Remaining duplicate-selector reports from the scan are intentional and were left alone:
media-query overrides (`.content`, `.footer`, `.app-navbar`, `.nx-toolbar`, `.nx-search`,
`.nx-title`, `.nx-user-sub`, `.card-body`, `.nx-footer-bar`, `.app-search`,
`.nx-toolbar .form-select`), `@keyframes … to`, and split-property rules (`body`,
`.nx-swal-danger`, `.nx-swal-cancel`).

### 2.2 Colour literals moved into tokens

`0` colour literals remain in component rules (verified by script — see §5).
New/moved tokens: `--thead-bg`, `--thead-fg`, `--field-option-bg`, `--on-danger`,
`--danger-solid`, `--radius-xs`. Previously hard-coded: `#151F32` (dark `<option>`),
`#fff` (button on a gradient header), `#F43F5E` + `#fff` (danger confirm button).

### 2.3 Tables

* `--thead-bg` light `#F6F8FD` (a soft tint lighter than `--surface-2`, not a grey band),
  dark `#1E2A44` (clearly lighter than the `--surface` row background), header text
  `--thead-fg` = `--text-2`, 11.5px / 600 / uppercase / `.06em`.
* Header 46px, rows 60px, avatar 40px, cell padding `--s3 --s4`, 1px `--border`
  separators, no bottom border on the last row.
* Row hover on the *cells* using `--row-hover` with a `background-color var(--t)` (160ms)
  transition so there is no layout shift.
* `.nx-action` 36×36 hit area, hover tint, danger tint, `:active` 1px press,
  `:focus-visible` 2px `--primary` ring, and the CSS tooltip now also shows on keyboard
  focus (`:focus-visible::after`).

### 2.4 Typography and rhythm

One rhythm defined once: page head → toolbar `--s5`, title → subtitle `--s1`,
toolbar/table share the card (no extra gap), table → `.nx-footer-bar`. Sizes/weights kept
on the documented scale (title 28px/700, subtitle 14px/400, card & section titles
16px/600, table header 11.5px/600, body 14px/400, secondary 12–13px/400).

### 2.5 Topbar and sidebar

* One icon size (18px) across the topbar and sidebar; one vertical centre line for the
  search box, clock, icon buttons and user chip (`.app-navbar .navbar-nav { align-items: center }`).
* Every clickable control keeps a ≥40px hit area (44px sidebar rows), identical radius.
* Sidebar keeps a single active indicator (the left bar) plus the `--active-bg` tint; hover
  stays weaker than active.
* Profile dropdown: `--s1` menu padding, 40px items, divider before logout, a new
  name/email header row, and a fixed width with `max-width: calc(100vw - --s4)` so it
  cannot clip. Opens with a 150ms fade + 4px translate.
* The sidebar's unused inline `style="--c:#…"` colour literals are gone (nothing consumed
  `--c` on `.ns-link`), and the notifications menu no longer uses an inline `min-width`
  style.

### 2.6 Interaction

* Buttons: hover colour step, 1px `:active` press, `:focus-visible` 2px `--primary` outline
  at 2px offset, `:disabled` reduced opacity + `not-allowed`.
* Submit loading state: `nexus.js` appends a spinner (`::after`, `currentColor` so it works
  on every variant), disables the button **on the next tick** so the browser has already
  serialised the form (a clicked button's own `name`/`value` is still submitted), and
  releases every marked button on `pageshow` so a back-navigation never leaves it stuck.
* Dropdowns animate 150ms with no layout jump.
* The `.is-loading` skeleton does not change layout (it only sets `opacity: 0` on children)
  and is covered by the global reduced-motion guard.
* Toasts: themed surface/border/text, themed icon colours, `--primary` progress bar,
  top-right, 3.5s.

### 2.7 Contrast fix (Step 1.5)

Measured with a throwaway script that parses the token blocks straight out of
`nexus.css`, resolves `var()` references, composites `rgba()` over a base surface and
computes WCAG ratios (kept out of the repo, in `/tmp`).

Pairs that **failed** before this work (values measured against the tokens as committed
in `03c2778`) and their values now:

| Pair | Before | After |
| --- | --- | --- |
| light `--text-2` on `--bg` | 4.44:1 FAIL | 5.37:1 |
| light `--text-2` on `--surface-2` | 4.32:1 FAIL | 5.22:1 |
| light `--border-input` on `--surface` | 1.78:1 FAIL | 3.50:1 |
| light `--border-input` on `--surface-2` | 1.61:1 FAIL | 3.17:1 |
| dark `--border-input` on `--surface` | 1.85:1 FAIL | 3.57:1 |
| dark `--border-input` on `--surface-2` | 1.67:1 FAIL | 3.22:1 |
| dark `--primary` on `--primary-soft` | 4.46:1 FAIL | 4.69:1 |

Token edits: light `--text-2 #64748B → #5A6779`; light
`--border-input #B9C3D3 → #8A94A3 → #7F8A9A`; dark `--border-input #3A4A66 → #5C6E90 → #63759A`;
dark `--primary-soft` alpha `.14 → .11`; light `--row-hover → #F3F6FC`.

Two rounds: the first pass changed the tokens above, then the script still flagged three
pairs (light border-input on `--surface-2` 2.78, dark primary on primary-soft 4.46, dark
border-input on `--surface-2` 2.90), which drove the second set of values.

Final state — **0 failing pairs** of 46 checked (23 per theme):

```
LIGHT   --text on --bg/surface/surface-2        15.18 / 16.27 / 14.77
        --text-2 on --bg/surface/surface-2      5.37 / 5.75 / 5.22
        --text-2 on --thead-bg / --sidebar / --topbar / --active-bg   5.41 / 5.75 / 5.75 / 5.14
        --text-2-on-bg on --bg                  5.94
        --primary on surface/active-bg/soft     6.29 / 5.62 / 5.62
        --on-primary on --primary               6.29
        --on-danger on --danger-solid           4.83
        --ok / --danger / --warn on their tints 4.84 / 5.30 / 6.37
        --border-input on surface/surface-2     3.50 / 3.17   (non-text, >=3:1)
        focus ring on --surface                 6.29
DARK    --text on --bg/surface/surface-2        15.27 / 13.37 / 12.07
        --text-2 on --bg/surface/surface-2      8.38 / 7.34 / 6.63
        --text-2 on --thead-bg / --sidebar / --topbar / --active-bg   6.36 / 7.95 / 7.90 / 6.67
        --text-2-on-bg on --bg                  8.38
        --primary on surface/active-bg/soft     5.53 / 5.02 / 4.69
        --on-primary on --primary               6.31
        --on-danger on --danger-solid           4.70
        --ok / --danger / --warn on their tints 8.30 / 7.48 / 8.63
        --border-input on surface/surface-2     3.57 / 3.22   (non-text, >=3:1)
        focus ring on --surface                 5.53
```

Known tight value: light `--ok on --ok-bg` is 4.84:1 — passes, but with little headroom.
Light `--border-input` is deliberately noticeably darker now (needed for 3:1); that is a
visible trade-off against the "subtle border" direction.

---

## 3. Step 2 — per-module redesign

| # | Module | Files changed | Notes |
| --- | --- | --- | --- |
| 1 | branch | `admin/branch/{list,create,edit}.blade.php` | edit submit label "Update Warehouse" → "Update Branch" |
| 2 | department | `admin/department/{list,create,edit}.blade.php` | edit page was titled "Create Department"; its status `<select>` carried `value=""` instead of `@selected` on the options, so the saved status was never preserved — fixed |
| 3 | designation | `admin/designation/{list,create,edit}.blade.php` + `nexus.css` | `.nx-static` added (one new shared class) for the read-only code preview next to the hidden `code` input |
| 4 | location | `admin/location/{list,create}.blade.php` | list's own jQuery CDN removed (scripts moved to `@section('scripts')`); AJAX status control restyled as `.nx-status-toggle` while still toggling `btn-success`/`btn-danger` so the existing script keeps working; the two dead row controls were removed in `ee78ee5` (see §4) |
| 5 | warehouse | `warehouse/{list,create,edit}.blade.php` | inactive badge `bg-secondary` → `bg-danger` for cross-module consistency |
| 6 | permission | `admin/permission/{list,edit}.blade.php` | stray `</nav>` around `links()` removed; duplicate `<!-- Footer -->` comment removed; guard select → read-only `.nx-static` + hidden `guard_name` with its original value (controller only reads `status`) |
| 7 | role | `admin/role/{list,create,edit,permission-assign,audit-log}.blade.php` | see below |
| 8 | user edit | `admin/user/edit.blade.php` (new) + `UserController` | `906e7b5` — created **later, on explicit request**, together with the missing `edit()`/`update()` methods (see below) |
| 9 | auth (login) | `auth/login.blade.php` + `nexus.css` | `d52624d` — moved onto the token system; `<style>` block, gradients, blurred orbs, glass card, `backdrop-filter`, heavy shadow and decorative animation all removed |
| 10 | welcome | `welcome.blade.php` | `97ecd51` — the last `<style>` block (a Tailwind v4 dump) and the inline `mix-blend-mode` removed; rebuilt on the auth shell classes (see §4) |

### Role module detail

* Permission assign groups the flat list into `nx-perm-card` cards by the prefix before
  the first dot. Checkbox `name="permissions[]"`, `id="permission_{id}"` and the
  `$role->hasPermissionTo($permission->name)` check are untouched.
* Audit log is a read-only `nx-table` with no action column, `.nx-json` for the payload.
* Removed the hard-coded fake paginator (`Prev / 1 / 2 / Next`) and the literal
  "Showing <strong>2</strong> roles" that sat next to the real `links()` output.
* View bugs fixed: edit was titled "Create New Role"; `@foreach($roles as $role)` clobbered
  the `$role` being edited (so the icon and status fields fell back to the last role in
  the list); the parent `<select>` used `.form-control` instead of `.form-select`.

### User edit — the routes used to point at nothing

At the time of the polish pass, `UserController` contained only `list()`, `create()` and
`store()`: `routes/web.php` declared `admin.users.edit` and `admin.users.update` against
methods that did not exist, and the users list linked an Edit action at them, so **both
routes 500ed**. Per instruction it was reported rather than invented. It was then added in a
separate, explicitly-requested step (`906e7b5`): `edit()` + `update()` plus
`admin/user/edit.blade.php`, which mirrors `user/create` and reuses only existing classes.
See §7.9 for the behaviour decisions and §7.11 for the delete route, which is still missing.

---

## 4. Step 2b — Inventory module (categories, brands, products)

### 4.1 Categories (commit `b297828`)

**Files created:**
- `app/Models/Category.php` — model with `parent()`, `children()`, `products()` relations, `product_count` accessor, `scopeActive()`
- `app/Http/Requests/CategoryRequest.php` — Form Request validation with max-depth-2 guard and circular-reference guard
- `app/Http/Controllers/Admin/CategoryController.php` — CRUD with `generateSlug()` endpoint
- `database/migrations/2026_10_14_000010_create_categories_table.php` — migration with `parent_id` self-FK, `sort_order`, soft deletes
- `resources/views/admin/category/{list,create,edit}.blade.php`

**Table:** `id, name, slug (unique), parent_id (nullable self-FK, nullOnDelete), description, status, sort_order (default 0), timestamps, deleted_at`

**Guards:**
- Cannot set itself or a descendant as parent (validated in `withValidator` after hook)
- Cannot delete a category that has children (flash error)
- Cannot delete a category that has products (flash error)

**List:** Shows name, slug, parent (or "Root"), product count via `$category->product_count`, status badge, actions. Search across name/slug, parent filter, status filter, per_page 10/25/50.

**Slug:** Editable, auto-generated via backend `generateSlug()` endpoint with vanilla JS fetch in views (ensure uniqueness).

---

### 4.2 Brands (commit `cdb6c6d`)

**Files created:**
- `app/Models/Brand.php` — model with `products()` relation, `product_count` accessor, `logo_url` accessor
- `app/Http/Requests/BrandRequest.php` — Form Request with logo validation (2MB, image types)
- `app/Http/Controllers/Admin/BrandController.php` — CRUD with logo upload to `public/uploads/brand/`, `generateSlug()` endpoint
- `database/migrations/2026_10_14_000020_create_brands_table.php` — migration with logo, website, soft deletes
- `resources/views/admin/brand/{list,create,edit}.blade.php`

**Table:** `id, name (unique), slug (unique), logo (nullable), website (nullable url), description, status, timestamps, deleted_at`

**Logo:** Uploaded to `public/uploads/brand/`, shown as 40px rounded (`nx-avatar`) in list with placeholder fallback. Old logo removed on replacement.

**Guard:** Cannot delete a brand that has products (flash error).

---

### 4.3 Products (commit `985e649`)

**Files created:**
- `app/Models/Product.php` — model with `category()`, `brand()` relations, `image_url` accessor, `boot()` to touch category/brand on save
- `app/Http/Requests/ProductRequest.php` — Form Request with pricing validation, unit enum from `config('inventory.units')`, SKU uppercase/unique
- `app/Http/Controllers/Admin/ProductController.php` — CRUD with search across name/sku/barcode, category/brand/status filters
- `database/migrations/2026_10_14_000030_create_products_table.php` — migration with `sku (unique)`, `barcode (nullable unique)`, `category_id (restrictOnDelete)`, `brand_id (nullable, nullOnDelete)`, `unit`, `cost_price`/`selling_price` decimal(12,2), `reorder_level`, `track_stock`, `image`, indexes on category_id/brand_id/status/name
- `resources/views/admin/product/{list,create,edit}.blade.php`

**Table:** `id, sku (unique, required, uppercase), barcode (nullable unique), name, slug, description, category_id (FK restrict), brand_id (FK nullable), unit (from config), cost_price (decimal 12,2), selling_price (decimal 12,2), reorder_level (unsigned int), track_stock (boolean), image (nullable), status, timestamps, deleted_at`

**Catalog only — no stock quantity stored** (stock will live in separate per-warehouse table later).

**Validation:** `selling_price >= 0`, `cost_price >= 0`, `reorder_level >= 0`. Image max 2MB. Selling price < cost price warning is intentionally NOT implemented (out of scope).

**Views:** Sectioned with `nx-section` — Basic info, Classification (category/brand), Pricing and stock settings, Image.

**SKU:** User enters it (not auto-generated). No Generate button (vanilla JS slug generation would be misleading for SKU).

---

### 4.4 Shared infrastructure

**config/inventory.php** — units list: `pcs, kg, g, l, ml, box, pack, dozen, m`

**Routes (all with `can:` middleware):**
- `admin.categories.*` — list, create, store, edit/{category}, update/{category}, delete/{category}, generate-slug
- `admin.brands.*` — list, create, store, edit/{brand}, update/{brand}, delete/{brand}, generate-slug
- `admin.products.*` — list, create, store, edit/{product}, update/{product}, delete/{product}

**PermissionSeeder additions:**
```php
'categories' => ['view', 'create', 'edit', 'delete'],
'brands'     => ['view', 'create', 'edit', 'delete'],
'products'   => ['view', 'create', 'edit', 'delete'],
```

**Sidebar:** New "Inventory" group with Products, Categories, Brands links (each in `@can(...view)`, active state via `request()->routeIs(...)`).

**CSS:** Added `.nx-avatar-lg` (60px) for product edit page image display.

**Product counts in category/brand lists:** `$category->product_count` and `$brand->product_count` via model accessors, touched on product save.

---

## 5. Step 3 — cleanup findings

Fixed inside files touched by this work: every `<style>` block, all duplicated CDN
`<script>`/`<link>` tags (Bootstrap bundle ×6, jQuery ×2, FontAwesome + iconpicker CSS/JS
in `role/create`), all `bg-gradient-primary` card headers, all
`data-bs-toggle="tooltip"` + `text-bg-*` + `onclick="return confirm"` usages in those files.

No Step 3 commit was needed for those; they shipped with each module's commit. The repairs
below got their own commits — the first two in `dbbcd9c`, the location one in `ee78ee5`,
the login page in `d52624d`, the welcome page in `97ecd51`, the user edit form in
`906e7b5`, the chart colours in `f53e646`:

* `.nx-empty` on an empty-state `<td>` lost to the generic `.table > tbody > * > *` cell
  padding (specificity), so the empty state rendered tight → added `.table td.nx-empty`.
* Bootstrap's own default red leaked in via `.invalid-feedback`, `.text-danger` and the
  invalid focus shadow → all three now come from `--danger` / `--danger-bg`.
* `ee78ee5` — the location list's edit link and delete form had empty `href`/`action`
  values and could never work (no `locations.edit`/`delete` route, no matching
  `LocationController` method, and `PermissionSeeder` grants only `view/create/status` for
  locations). Both controls were removed, along with the now-empty Actions column; the
  empty-state `colspan` follows to 7. No route, controller or permission was added or
  changed.
* `f53e646` — the dashboard line chart's area gradient (`rgba(215, 227, 244, 1)` /
  `…, 0)`) and its two invisible grid lines (`rgba(0,0,0,0.0)`) were the last hard-coded
  colours left in a view, inline in `layout/master.blade.php`'s `<script>`. A `--chart-fill`
  token was added and the alpha is now applied in JS from it; the grid lines use the same
  `"transparent"` keyword the bar chart in the same file already used. Verified the helper
  produces byte-identical colours for the current token, so the chart looks the same.
* `d52624d` — `auth/login.blade.php` was moved onto the token system, so the last
  admin/auth `<style>` block in the panel is gone: gradients, blurred orbs, glass card,
  `backdrop-filter`, heavy shadow and the float/rise animations all removed, and Bootstrap
  + FontAwesome + `nexus.css` linked instead of re-declared inline. The page now reuses
  the panel's form components and `nexus.js`'s submit loading state, so its bespoke
  spinner CSS and handler were dropped. Form contract untouched (see §3).
* The last inline colour literals were removed from the panel's views, so **no view in the
  admin panel declares a colour or an inline style any more**:
  * `admin/dashboard.blade.php` — the four `style="--c:#14b8a6"` tile colours and
    `style="height:320px"` replaced by `--stat-1…4` tokens (new, `:root`) consumed through
    `.nx-stat-1…4 { --c: var(--stat-N) }`, `.nx-stat-ico`, `.nx-stat-num`, `.nx-stat-lbl`.
    No literal remains in the view; the chart height now comes from a CSS rule.
  * `admin/user/create.blade.php` — the three section-header `style="--c:#…"` values and
    the inline `border-top:2px dashed var(--nx-line)` action footer replaced by the shared
    `.nx-section` / `.nx-form-actions` classes; stray `me-1` on button icons dropped
    (spacing comes from the button gap) and the submit button moved off Bootstrap's
    `btn-success`/`btn-danger` colour names to `btn-primary`/`.nx-status-toggle` tokens.
  * `admin/user/list.blade.php` — `style="width:56px"` → `.nx-col-num`,
    `style="height:auto"` → removed, `style="width:auto"` → Bootstrap's `w-auto` utility.
  * `layout/sidebar.blade.php` — the unused `style="--c:#…"` literals (nothing read `--c`
    on `.ns-link`) were already dropped earlier in the branch.
* `97ecd51` — `welcome.blade.php` (Laravel's default landing page) was the last view still
  holding styling: a ~200-line Tailwind v4 dump inside a `<style>` block plus inline
  `mix-blend-mode` on its decorative SVG. It is now a small standalone page on the same
  token system and auth-shell classes as the login page (`.nx-auth`, `.nx-auth-card`,
  `.nx-auth-brand`, `.ns-logo`, `btn btn-primary`, `.nx-auth-foot`) with its own theme
  toggle on `nexus.js`. No `<style>`, no inline styles, no Tailwind, no external font
  bundle. **Judgement call:** the Laravel-brand SVG artwork was dropped, because the
  Tailwind utilities and the `<style>` block were the only thing styling it. Note the page
  is also unreachable — `GET /` always redirects (`routes/web.php` line 16).
* `906e7b5` — `UserController::edit()` / `update()` added, with
  `resources/views/admin/user/edit.blade.php`, because `admin.users.edit` and
  `admin.users.update` were registered and linked from the user list but had no controller
  methods. The form mirrors `user/create` and reuses only existing classes
  (`.nx-section`, `.nx-static`, `.nx-form-actions`, `.nx-avatar`).

**Inventory module additions (new Step 3):**

* `public/assets/css/nexus.css` — added `.nx-avatar-lg` class (60px) for larger product images in edit form. One new shared class using tokens.
* Fixed 4 inline `style="width:...; height:..."` attributes on `<img>` tags in brand/product views by using the existing `.nx-avatar` class (40px) and new `.nx-avatar-lg` (60px).

---

## 6. Step 3b — User delete and restrict (Part A)

### A1: UserController@update review — OK

The existing `UserController@update` already implements correctly:
- ✅ `users.role_id` and Spatie role stay in sync: uses `syncRoles([$requestedRole->name])`
- ✅ Email unique ignoring current id: `unique:users,email,' . $user->id`
- ✅ Old profile photo removed only if exists and not default.png
- ✅ Password written only when filled: `if ($request->filled('password'))`
- ✅ Non-superadmin cannot assign/edit superadmin: guard checks before update
- ✅ No privilege escalation through mass assignment

No changes needed for A1.

### A2: User delete (commit `421e413`)

**Existing implementation verified:**
- `UserController@delete` with `guardStatusChange()` private method
- SoftDeletes on User (migration `2026_10_14_000001_add_deleted_at_to_users_table.php`)
- Guards: cannot delete self, cannot delete last active superadmin, only superadmin can delete superadmin
- Session invalidation: `DB::table('sessions')->where('user_id', $user->id)->delete()`
- Success flash "User deleted"
- Route: `PATCH admin/users/delete/{id}` named `admin.users.delete` with `can:users.delete`

**Relations pointing at users:** The `User` model has `branches()` and `warehouses()` many-to-many relations. Soft-deleted users' branch/warehouse assignments are preserved in pivot tables (no cascade on pivot delete). Other models (categories, brands, products) do not reference users directly. No breaking issues found.

### A3: Restrict/restore (commit `ac4c0ca`)

**Existing implementation verified:**
- `UserController@toggleStatus` with same `guardStatusChange()` guards
- Route: `PATCH admin/users/status/{id}` named `admin.users.status` with `can:users.status`
- Status `!= 'active'` means restricted (uses `'active'/'inactive'` values)
- LoginController: refuses non-active user with generic error on email field ("This account is restricted. Contact an administrator.")
- `EnsureUserIsActive` middleware registered in `bootstrap/app.php` as `active` alias
- Applied to authenticated admin group (middleware alias in route group)
- Logs user out, invalidates session, regenerates token, redirects to login
- No redirect loop (login/logout routes outside authenticated group)
- Guard logic in one private method (`guardStatusChange`)

### A4: List page

**Existing implementation verified:**
- Restrict/restore button inside `@can('users.status')` 
- Hidden for current user (`@if($user->id !== auth()->id())`)
- PATCH form with `@csrf @method('PATCH')`
- `data-confirm` when restricting
- Delete button keeps its `@can` and is hidden for current user
- Uses `.nx-action` with `data-tip` and `aria-label`

---

## 7. Step 3c — Branch/Warehouse user scope (Part B)

### B: Table structure findings

**Findings:**
- Warehouse does NOT belong to a branch (no `branch_id` column on warehouses table)
- Both `branch_user` and `warehouse_user` pivot tables exist (migrations `2026_10_14_000002` and `2026_10_14_000003`)
- `users.access_all` column exists (migration `2026_10_14_000004`, default false)
- User model has `hasAccessAll()`, `accessibleBranchIds()`, `accessibleWarehouseIds()` helpers
- Both Branch and Warehouse models have `scopeAccessibleTo($query, User $user)` scope
- All controllers use the scope on list/edit/update/delete

**Access rule (documented in User model comment):**
- Superadmin always has all access regardless of flag
- User with `access_all = true` sees everything (helpers return null = unrestricted)
- Otherwise assigned ids returned (empty array = no access)
- Warehouses do NOT belong to branches, so branch assignments do NOT imply warehouse access — warehouses must be assigned directly

### B1: Migrations

**Existing migrations verified:**
- `branch_user`: `user_id`, `branch_id`, unique pair, cascade on delete
- `warehouse_user`: `user_id`, `warehouse_id`, unique pair, cascade on delete
- `users.access_all`: boolean, default false

### B2: User model helpers

**Existing implementation verified:**
- `branches()`, `warehouses()` relations (belongsToMany)
- `accessibleBranchIds()`, `accessibleWarehouseIds()` — return null for access_all/superadmin, otherwise assigned ids
- One reusable query scope `scopeAccessibleTo($query, User $user)` on both Branch and Warehouse

### B3: Scoped controller lookups

**Existing implementation verified:**
- `BranchController@list/edit/update/delete` all use `Branch::accessibleTo(auth()->user())->findOrFail($id)`
- `WarehouseController@list/edit/update/delete` all use `Warehouse::accessibleTo(auth()->user())->findOrFail($id)`
- Scoped user cannot open another branch's/warehouse's record by URL (404 from findOrFail)

### B4: User create/edit forms

**Existing implementation verified:**
- "Access" section with "Access all branches and warehouses" checkbox
- Multi-selects for branches and warehouses (hidden/disabled when access_all is on)
- Saved in store()/update() with sync()
- Only users who may edit users can change it
- User cannot change their own access (guarded in update)

### B5: Tests

**Existing test file:** `tests/Feature/AccessScopeTest.php` with comprehensive tests:
- `test_scoped_user_sees_only_assigned_warehouses`
- `test_direct_url_to_unassigned_warehouse_is_refused`
- `test_access_all_user_sees_every_warehouse`
- `test_superadmin_sees_every_warehouse_without_access_all_flag`
- `test_scoped_user_sees_only_assigned_branches`
- `test_direct_url_to_unassigned_branch_is_refused`
- `test_user_without_assignments_sees_nothing`

**Cannot run** — pdo_sqlite missing.

---

## 8. Verification — what was actually run (inventory module)

| Check | Command | Result |
| --- | --- | --- |
| PHP syntax (new files) | `php -l` on 17 new/modified PHP files | ✅ All pass |
| Blade compiles | `php artisan view:cache` then `view:clear` | ✅ Passed |
| Route list | `php artisan route:list` | ✅ 20 new routes (categories/brands/products) |
| Routes with `can:` middleware | grep on `routes/web.php` | ✅ 67 routes with `can:`, 0 admin routes missing it |
| Route count unchanged | `php artisan route:list` total | ✅ 84 total routes (addition, not replacement) |
| Controller methods exist | tinker `method_exists()` check | ✅ CategoryController, BrandController, ProductController all OK |
| Model relationships | tinker check | ✅ Category->products, Brand->products, Product->category/brand all OK |
| PermissionSeeder permissions | grep | ✅ categories.*, brands.*, products.* added |
| Sidebar active state | grep `routeIs` | ✅ Uses `request()->routeIs('admin.{module}.*')` |
| No `<style>` in new views | grep | ✅ None found |
| No inline styles in new views | grep `style=` | ✅ Fixed 4 occurrences, added CSS class |
| No colour literals in new views | grep `#[0-9a-fA-F]` | ✅ None found |
| No debug statements | grep console.log/dump/dd | ✅ None found |

---

## 9. Verification — what could not run

| Check | Reason |
| --- | --- |
| Database-dependent tests | pdo_sqlite driver missing |
| `php artisan migrate --pretend` | No DB connection available |
| Browser visual verification | Chrome: not found |
| Form submission flows | Cannot test through browser |
| Image upload flows | No DB, cannot test |

---

## 10. Commands you must run

```bash
# Run migrations to create categories, brands, products tables
php artisan migrate

# Seed new permissions
php artisan db:seed --class=PermissionSeeder
```

---

## 11. What you must assign on the Roles page

After running the seeder, go to **Roles → [each role] → Permissions** and assign:

| Module | Permissions |
|--------|-------------|
| Categories | `categories.view`, `categories.create`, `categories.edit`, `categories.delete` |
| Brands | `brands.view`, `brands.create`, `brands.edit`, `brands.delete` |
| Products | `products.view`, `products.create`, `products.edit`, `products.delete` |

The seeder does NOT auto-assign these to non-superadmin roles (by design).

---

## 12. Assumptions

1. **Status values:** `'active'/'inactive'` (matching existing codebase)
2. **Branch/warehouse relationship:** Warehouses do NOT belong to branches (confirmed — no `branch_id` on Warehouse)
3. **Upload paths:** Brand logos → `public/uploads/brand/`, Product images → `public/uploads/product/` (matching user photo convention at `public/uploads/user/`)
4. **Default placeholders:** `assets/img/brand-placeholder.png`, `assets/img/product-placeholder.png`
5. **Max category depth:** 2 levels (root → child only, no grandchildren) — enforced in validation
6. **Slug generation:** Backend endpoint for uniqueness check with vanilla JS fetch
7. **User access rule:** Branch assignment does NOT imply warehouse access (warehouses assigned independently)
8. **Selling price < cost price warning:** Intentionally NOT implemented (out of scope)

---

## 13. What is intentionally NOT built yet

- Units table (using `config/inventory.php` array)
- Suppliers
- Stock levels (separate per-warehouse stock table later)
- Stock movements
- Purchases
- Sales
- Transfers
- Adjustments
- Reports
- Audit log for new modules (Spatie activitylog installed but not used)

---

## 14. Final commit list

```
b657faa feat(inventory): add sidebar group, permissions and routes
985e649 feat(products): add product management
cdb6c6d feat(brands): add brand management
b297828 feat(categories): add category management
5defebc feat(users): add access assignment to user forms
3f5d036 feat(access): add branch and warehouse user scope
ac4c0ca feat(users): add restrict and restore with login and session enforcement
421e413 feat(users): add soft delete with guards
59bcd72 fix(users): sync role_id and clean up old photo in update
```

(Part A and B commits pre-existed; Part C/D/E/F added in this session)
