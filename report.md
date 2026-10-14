# Nexus Stock — design system refinement report

Scope: a polish pass on the shared Nexus Stock design system, then application of that
system to every remaining admin view, committed module by module.

Hard constraints respected throughout: views + CSS/JS only. No routes, controllers,
models, migrations, permissions, middleware, form field names, form actions or HTTP
methods were changed. No `git push`, no history rewrite, no `git add -A` / `git add .`
(every commit stages explicit paths).

---

## 1. Commits

```
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
| 8 | user edit | **not created** | see below |
| 9 | auth (login) | `auth/login.blade.php` + `nexus.css` | `d52624d` — moved onto the token system; `<style>` block, gradients, blurred orbs, glass card, `backdrop-filter`, heavy shadow and decorative animation all removed |

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

### User edit — not created

`app/Http/Controllers/Admin/UserController.php` contains only `list()`, `create()` and
`store()`; it has no `edit()`/`update()` method and nothing references
`view('admin.user.edit')`. `routes/web.php` does declare `admin.users.edit` and
`admin.users.update` (both mapped to those non-existent methods), and the users list
links an Edit action at them, so **those two routes 500 today**. Per instruction
("if the controller does not reference it, tell me and do not create it") the view was
not created.

---

## 4. Step 3 — cleanup findings

Fixed inside files touched by this work: every `<style>` block, all duplicated CDN
`<script>`/`<link>` tags (Bootstrap bundle ×6, jQuery ×2, FontAwesome + iconpicker CSS/JS
in `role/create`), all `bg-gradient-primary` card headers, all
`data-bs-toggle="tooltip"` + `text-bg-*` + `onclick="return confirm"` usages in those files.

No Step 3 commit was needed for those; they shipped with each module's commit. The repairs
below got their own commits — the first two in `dbbcd9c`, the location one in `ee78ee5`,
the login page in `d52624d`:

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

**Reported, not fixed** (files outside this task's edits):

| File | Issue |
| --- | --- |
| `resources/views/welcome.blade.php` | Laravel's default welcome page, `<style>` block + inline `mix-blend-mode` (not part of the admin panel) |
| `permission/create.blade.php` | correctly absent; not recreated |

---

## 5. Verification — what was actually run

| Check | Command | Result |
| --- | --- | --- |
| Blade compiles | `php artisan view:cache` | "Blade templates cached successfully" (all 30 blade files → 67 compiled templates, observed during the run then cleared) |
| Compiled template syntax | `php -l` over all 67 compiled templates in `storage/framework/views` | 0 syntax errors |
| View cache clear | `php artisan view:clear` | OK |
| JS syntax | `node --check public/assets/js/nexus.js` | syntax OK |
| Contrast | `python3 /tmp/nx_contrast.py` (parses `nexus.css` tokens) | 48/48 pairs OK, 0 failing |
| Colour literals in component rules | token-block-stripping script | 0 |
| Duplicate selectors | selector scan | only the intentional media-query/keyframe/split-property cases |
| No `<style>` / inline colours / CDN scripts in edited files | `grep` | clean — only `welcome.blade.php` (Laravel's default page, not in the panel) still has a `<style>` block |
| No gradient / blur / glass left on the login page | `grep -n 'gradient\|blur\|backdrop\|orb\|style="\|<style>' resources/views/auth/login.blade.php` | clean (the only "blur" match is the JS `blur` event listener) |
| Login form contract preserved | `grep` for `route('login.submit')`, `@csrf`, `name="email|password|remember"`, `old('email')`, `old('remember')`, `@error`, `session('status')` | all present |
| No `data-bs-toggle="tooltip"` / `text-bg-*` / `onclick="return confirm"` in edited files | `grep` | clean |
| No dead controls left | `grep -rn 'href=""\|action=""' resources/views` | none |
| Location table columns line up | `<th>` count vs empty-state `colspan` | 7 = 7 |
| Custom classes used in views exist in CSS | script comparing `nx-`/`ns-`/`app-` classes | 0 missing (incl. new `.nx-stat-*`, `.nx-col-num`, `.nx-form-actions`) |
| No inline style or colour literal in panel views | `grep -rn 'style="'` / `grep -rn '#[0-9A-Fa-f]\{6\}'` over `resources/views` | none outside `welcome.blade.php` |
| Icon-only controls have `aria-label` | script (PHP `->` aware) | 0 missing |
| Routes unchanged | `php artisan route:list` | 63 total / 57 admin — identical to before |
| Test suite | `php artisan test` | 46 failed / 9 passed — **byte-identical to baseline `639c3bf`**, re-run in a throwaway `git worktree` |
| Tree state | `git status --porcelain` | clean |

The 46 test failures are **pre-existing and environmental**, not caused by this work:
`pdo_sqlite` is not installed in this environment (all 46 errors are
`could not find driver`), plus two stale route expectations (`/` always redirects, while
`ExampleTest` expects 200 and `HttpRoutesNoDbTest` expects 404). Verified identical on the
baseline commit.

---

## 6. Not verified — please check in a browser

No browser is available here (`Chrome: not found`) and there is no usable database
(`pdo_sqlite` missing, `.env` points at MySQL), so **nothing below could be observed**:

* the visual result in light **and** dark mode — especially the table header vs row
  separation, badge/alert tints, and the darker resting input border;
* layout at 1366 / 1024 / 768 / 390px and the "no horizontal overflow at 390px" requirement;
* keyboard focus rings and tooltips (including the tooltip on keyboard focus);
* dropdown open animation, toast appearance, skeleton shimmer/`.is-loading`;
* the new submit-button spinner, and that a back-navigation releases it (`pageshow`);
* the login page in light and dark mode, at 390px and on a large screen (including the
  password reveal button alignment against the 44px input, the Caps Lock hint and the
  submit spinner);
* the permission-assign grouped cards with real data;
* the locations AJAX status toggle after a click (class swap + label text);
* that the icon picker on `role/create` and `role/edit` now initialises at all — see §7.4.

---

## 7. Assumptions and judgement calls to sign off

1. **`@can` wrappers added** around list/form actions in the modules
   (`branches.*`, `departments.*`, `designations.*`, `warehouses.*`, `locations.create`,
   `roles.*`, `permissions.edit`). Those views had no gates, but every route is already
   protected by `can:*` middleware in `routes/web.php`, so this only hides buttons that
   would 403. No access-control change.
2. **`@error` / `.is-invalid` / alert blocks and `old()` added** to module forms that
   previously showed nothing on validation failure. Presentation only, using the existing
   error bag; no controller or validation change.
3. **`guard_name` in permission edit** is now a read-only display plus a hidden input
   carrying the original value, so the submitted payload is unchanged (the controller
   reads only `status`).
4. **Inline scripts moved into `@section('scripts')`.** In the old layout, scripts inside
   `@section('content')` execute *before* `master.blade.php` loads jQuery, so
   `role/edit`'s `$(document).ready(…)` icon-picker init and `location/list`'s `$` handler
   could never have run. This is a behavioural fix, not just a move.
5. **Font-family/size/weight** were left as they were apart from the table header, per
   "do not chase a numeric rating" and the documented scale.
6. **`--border-input` was darkened** in both themes to satisfy the requested 3:1 non-text
   requirement, which is a visible change to the intended "subtle border" look.
7. **`--ok on --ok-bg` (light)** passes at 4.84:1 only — flagged, not changed.
8. **Locations list dead controls were resolved by removal** (`ee78ee5`), not by adding
   routes/controller methods/permissions: `routes/web.php` has no `locations.edit`/`delete`,
   `LocationController` has no such methods and `PermissionSeeder` grants only
   `view/create/status`, so locations are CSV-imported and status-toggled by design. If you
   do want location CRUD, that is a separate feature (routes + controller + validation +
   permission entries) and was deliberately not invented here.
