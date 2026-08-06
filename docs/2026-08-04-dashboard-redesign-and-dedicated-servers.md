# 2026-08-04 — Dashboard redesign + Dedicated Server ordering

Two pieces of work landed today, in two commits on `main`:

- `243fee5` — Redesign dashboard: collapsible sidebar, services grid, login activity
- `b02cbec` — Add Dedicated Server ordering, provisioning, and admin pricing

## 1. Dashboard redesign

Goal: bring the client portal's dashboard closer to parity with InterServer's own
dashboard (screenshots compared during the session) without breaking the existing
design.

### Sidebar

- The hamburger button now collapses/expands the sidebar **on desktop**, not just
  mobile. Previously it was `lg:hidden` and did nothing above the `lg` breakpoint.
- The collapse state persists across page loads via `localStorage.sidebarCollapsed`.
- The "Client Portal" caption next to the logo was removed.
- Implementation note: the sidebar's open/closed state now drives the main content
  wrapper's left margin via an **inline `:style` binding** (`resources/views/layouts/app.blade.php`),
  not a dynamic Tailwind class. A dynamic class (`:class="... ? 'lg:ml-64' : 'lg:ml-0'"`)
  intermittently failed to take effect in real browser sessions (a stale
  Tailwind/Vite dev-cache issue — see the existing comment in `resources/css/app.css`
  about the same class of problem with Alpine ternaries). The inline-style approach
  sidesteps class generation entirely and is deterministic.

Files: `resources/views/layouts/app.blade.php`, `resources/js/app.js`

### Services grid

The dashboard's main column now has a "Services" section: a card per service line
(VPS, Quick Servers, SSL, Domains, Dedicated Servers, legacy "My Services", Business
Email Hosting, Backup & Security), each showing a live active-service count and an
"Order New" button linking to that service's real catalog route. Counts are computed
in `HomeController` from each service's local `*_orders` table (`status = 'provisioned'`).

Files: `app/Http/Controllers/Dashboard/HomeController.php`, `resources/views/dashboard/home.blade.php`

### Recent Activity / login tracking

- New `login_activities` table + `LoginActivity` model — one row per successful login
  (client id, IP, resolved location, user agent, timestamp).
- Recorded on both the password login path (`LoginController`) and the social login
  path (`SocialLoginController::logIn()`), so Google/Facebook sign-ins show up too.
- Surfaced as a "Recent Activity" widget in the dashboard's right column (last 5), with
  a "View all" link to a new "Login Activity" section on the Profile page (last 20).

Files: `database/migrations/2026_08_04_000001_create_login_activities_table.php`,
`app/Models/LoginActivity.php`, `app/Http/Controllers/Auth/LoginController.php`,
`app/Http/Controllers/Auth/SocialLoginController.php`,
`app/Http/Controllers/Dashboard/ProfileController.php`,
`resources/views/dashboard/profile/index.blade.php`

### Floating dock

A small rounded pill fixed at the bottom-center of the viewport (all breakpoints),
with quick links to Dashboard / My VPS / Order a Server (highlighted) / Billing /
Support, with active-state highlighting. New `.dock-link` component class added
alongside the existing `.nav-link` in `resources/css/app.css`.

Files: `resources/views/layouts/app.blade.php`, `resources/css/app.css`

## 2. Dedicated Server ordering

Previously "Dedicated Server" in the sidebar just linked to the generic
`coming-soon` placeholder. This wires it up to a real order flow.

### What InterServer's API offers for dedicated servers

Per `https://my.interserver.net/spec/openapi.yaml` (fetched via the published API
docs at `/api-docs/elements.html`), InterServer exposes dedicated servers two ways:

1. **Custom build** — `GET/POST /servers/order` (`getNewServer` / `addServer`): pick
   CPU, RAM, disk, RAID, bandwidth, IPs, OS, control panel, and region individually
   from a large option matrix.
2. **Rapid Deploy / Buy-It-Now marketplace** — `GET /buy_now_servers_list`
   (`getMPServers`, public, no auth) lists pre-built physical servers currently in
   stock with live pricing; `GET/POST /servers/order/buy_now_server?a=<asset_id>`
   (`buyItNowServerOrder` / `placeBuyNowServer`) fetches configurable
   OS/bandwidth/IP/control-panel/RAID options for one listing and places the order.

This implementation uses **option 2** (the marketplace), because:
- It returns a stable, browsable list of concrete listings — the same shape the site
  already uses for "Quick Servers" (`/qs/order`), so it reuses that established
  pattern (catalog page → pick one → configure → order → invoice → auto-provision on
  payment via a scheduled command).
- The custom-build flow's option matrix has no natural mapping to a persistent,
  admin-priceable "product catalog" the way a list of concrete listings does.

At the time of writing, `getMPServers` returned **26 live listings** — a mix of AMD
EPYC, Intel Core i9, and Xeon builds, roughly $250–1500+/mo before markup.

### New backend pieces

- `InterServerService`: `listServers`, `getServer`, `cancelServer`,
  `getServerInvoices`, `getMarketplaceServers`, `getBuyNowOptions`,
  `placeBuyNowOrder`.
- `dedicated_server_orders` table + `DedicatedServerOrder` model — same shape as
  `vps_orders`/`qs_orders` (client id, invoice id, InterServer server id, status,
  price, config JSON snapshot, failure reason).
- `DedicatedServerController` (`app/Http/Controllers/Dashboard/`): `index` (owned
  servers), `catalog` (browse marketplace), `quote` (AJAX — fetch a listing's
  configurable options + our price), `store` (create local invoice), `show`
  (detail), `action` (cancel only — see "Deliberately out of scope" below).
- `config/dedicated_pricing.php` + two new `PricingConfig` methods
  (`dedicatedMarkupPercent`, `dedicatedServerOverrides`) — same "live cost × markup
  %, with optional per-listing override" model already used for Quick Servers and
  SSL. Default markup: 20%.
- `dedicated:provision-paid` console command, scheduled every 5 minutes
  (`routes/console.php`) — mirrors `qs:provision-paid`: once a dedicated-server
  order's invoice is paid, it calls `placeBuyNowOrder` for real and records the
  resulting InterServer `service_id`. Also registered in
  `CheckStuckProvisioning::ORDER_MODELS` so a crashed provisioning run gets flagged
  the same way VPS/QS/SSL/Domain orders do.
- Routes: `GET /dedicated`, `GET/POST /dedicated/order`, `POST /dedicated/quote`,
  `GET /dedicated/{order}`, `POST /dedicated/{order}/action`.

### New views

`resources/views/dashboard/dedicated/{index,catalog,show}.blade.php` — same visual
language as the existing VPS/QS pages. `catalog.blade.php` is an Alpine component:
pick a listing → AJAX-fetch its OS/bandwidth/IP/control-panel/RAID options and price
→ fill in hostname/root password → submit.

### Admin price editing

`Admin → Products` gained a "Dedicated Servers" tab (`AdminProductsController::TYPES`
now includes `dedicated`). Each of the 26 live listings appears by name (e.g. "AMD
EPYC 9254"); an admin can pin an explicit price per currency/billing-cycle the same
way they already can for VPS/QS/SSL/Domains, via the existing generic
`products` / `product_prices` tables and `ProductCatalog::price()` resolver — no new
admin UI code was needed beyond adding the tab and the `dedicated` case to the
existing type-driven controller methods.

### A real bug found and fixed along the way

While building the catalog page, an Alpine.js crash surfaced
(`TypeError: Cannot set properties of null (setting '_x_dataStack')`, thrown inside
Alpine's `x-if` handler). Root cause: `<template x-if="...">—</template>` — bare text
with no wrapping element. Alpine's `x-if` clones `el.content.cloneNode(true).firstElementChild`,
which is `null` for a text-only template, and crashes as soon as that branch is
actually the one that evaluates true.

This exact pattern already existed in the **QS and SSL catalog pages** — just masked
there because both pre-select a default item on load, so the "no price yet" branch
never actually rendered in practice. Fixed in all three:
`resources/views/dashboard/{dedicated,qs,ssl}/catalog.blade.php`.

Also fixed along the way: a related bug in `InterServerService::request()` — passing
an explicit empty array as the second argument to Laravel's `Http::get()` sets
Guzzle's `query` option to `[]`, which *replaces* (rather than leaves alone) any
query string already embedded in the URL. This silently stripped `?a=<asset_id>`
from `getBuyNowOptions()`'s request. Fixed by only passing the `$data` argument
when it's non-empty.

### Deliberately out of scope

- **IPMI power/console management.** VPS/QS expose simple `start`/`stop`/`restart`
  REST calls; bare metal instead requires InterServer's IPMI endpoints
  (`/servers/{id}/ipmi_power`, `/servers/{id}/ipmi_live`) — a materially different
  interaction model (remote chassis power, KVM session). The dedicated-server
  "show" page only exposes **cancel**.
- **Managed Dedicated Server** (the sidebar's other "Server" sub-item) is still a
  `coming-soon` placeholder — it's a Kloud101 managed-support upsell concept with no
  direct InterServer API equivalent, not something this API research covers.
- **Custom-build ordering** (pick CPU/RAM/disk piece by piece via `/servers/order`)
  was not implemented — the marketplace flow above covers the "list all products and
  let admin price them" ask more directly.

## Deploying

Both commits are pushed to `origin/main`, which should trigger the existing GitHub
Actions auto-deploy workflow. **Two migrations still need to run on production**
after deploy:

```bash
php artisan migrate --force
```

This applies:
- `2026_08_04_000001_create_login_activities_table.php`
- `2026_08_04_000002_create_dedicated_server_orders_table.php`

Without this, the dashboard's Recent Activity widget, the Profile page's Login
Activity section, and all `/dedicated/*` routes will error.
