# Project-Namer

## What this codebase does

AI-powered business-name + logo generator built on Laravel 12 / TALL stack
(Livewire 3 + Volt, FluxUI Pro, Filament 4 admin). Authenticated users
create `Project`s, run AI name-generation sessions through multiple model
providers (OpenAI, Anthropic, X.AI, Google) via the `prism` package,
upload images to mood boards, generate logos via DALL-E, and create
public or password-protected `Share` links. SQLite is the database
across all environments. Auth uses Laravel Fortify with email
verification, optional Google2FA TOTP, and a per-user `is_admin`
boolean column.

## Auth shape

- `auth` + `verified` route middleware gate every dashboard, project,
  logo, and settings route in `routes/web.php`. Unverified users cannot
  reach `Project` or generation flows.
- `admin` middleware alias → `App\Http\Middleware\EnsureUserIsAdmin`
  checks `$user->is_admin` (DB column) and `abort(403)` otherwise. It
  guards the `/admin/*` Livewire pages (AI config / cost monitor).
- `App\Models\User::isAdmin()` is a **separate** method that returns
  true when the email contains the substring `admin` or matches a
  hard-coded list. It is *not* what the middleware uses, but any code
  path that calls `$user->isAdmin()` instead of the column is a
  privilege-escalation vector if user-controlled emails reach it.
- Resource policies live in `app/Policies/` (`SharePolicy`,
  `ExportPolicy`, `LogoGenerationPolicy`, `ProjectPolicy`) and are
  registered in `AppServiceProvider`. They check `user_id` ownership
  only — no role hierarchy.
- Public access to shares goes through `PublicShareController` with a
  session flag `share_authenticated_{uuid}` set after password POST.
  Password is bcrypt-hashed via `Share::password` attribute setter.

## Threat model

Highest impact: an attacker pivoting from a low-privilege account into
admin-only AI configuration / cost-monitor pages, or scraping other
users' private projects, name suggestions, or uploaded images by
guessing/forging IDs (UUIDs are used in routes but `Project` and
`ProjectImage` lookups sometimes use numeric `id`). Secondary: abusing
AI generation, image upload, or logo endpoints to incur cost (rate
limits exist but are per-user, not global). Public share URLs leak
business-idea text if a UUID is enumerable or the password check is
bypassed.

## Project-specific patterns to flag

- **Mixed admin checks.** Any new code path that uses
  `$user->isAdmin()` (the email-substring method) for authorization
  instead of the `admin` middleware / `is_admin` column. The two
  disagree and the method-based check is bypassable by registering
  `attacker+admin@example.com`.
- **Tenant isolation on Project-scoped routes.** `routes/api.php`
  binds `{project}` then nested `{image}`, `{uuid}` segments. Several
  controllers (`ImageUploadController::destroy`,
  `PhotoGalleryController`, `MoodBoardController`) must verify the
  child belongs to the bound project AND that
  `$project->user_id === auth()->id()` (or via policy). Missing
  ownership re-check is a pattern worth flagging.
- **Debug routes in `web.php`.** Treat any new unauthenticated
  `Route::get('test-…')` / `/debug-…` route that reflects config or
  invokes AI/mail services as a finding — the previous
  `/test-generation` and `/test-email` routes were removed.
- **Share password bypass.** `PublicShareController::show` trusts the
  session flag `share_authenticated_{uuid}`; any code that sets this
  flag without going through `ShareService::validateShareAccess` is a
  bypass.
- **AI prompt construction.** Services under `app/Services/AI/` and
  `OpenAINameService`, `OpenAILogoService`, `FallbackNameService` build
  prompts from user-supplied `business_description` (up to 5000 chars
  in contact, 2000+ elsewhere). Flag prompt-injection sinks that
  forward this text to tool-use, file fetching, or shell-like agents,
  and any place the AI response is rendered as raw HTML.

## Known false-positives

- `ContactController::store` hard-codes a fallback recipient
  `03matei@gmail.com` via `config('mail.contact_recipient', …)` — this
  is the project owner's address, not a leak.
- `database/database.sqlite` and `storage/app/public/projects/*` paths
  are intentional dev fixtures; SQL-injection matchers firing on
  seeders / factories under `database/` are FPs.
- `_ide_helper.php`, `blade_syntax_check*.php`, `test-blade.blade.php`
  at the repo root are dev-only scratch files, not shipped code paths.
- `\Log::info('Admin middleware check', …)` in `EnsureUserIsAdmin`
  logs `is_admin` boolean but no PII / secrets — informational.
