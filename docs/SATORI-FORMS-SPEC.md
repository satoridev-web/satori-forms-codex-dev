# SATORI Forms — Plugin Specification (R3P Master Spec, v1.0.0)

## 1. Overview

**Plugin Name:** SATORI Forms  
**Slug:** satori-forms  
**Namespace Root:** `Satori\Forms`  
**Text Domain:** `satori-forms`  
**Minimum WordPress:** 6.4  
**Minimum PHP:** 7.4+ (prefer 8.1+)  

### 1.1 Purpose

SATORI Forms is a flexible form builder plugin designed to power contact forms and general input forms across SATORI-managed sites.  

Version 1.0.0 focuses on a **configuration-first, meta-box-driven builder** (no drag-and-drop yet) with:

- Structured form definitions stored as JSON in meta.
- Automatic frontend rendering via a shortcode.
- Validated submissions stored in the database as a dedicated CPT.
- Email notifications, optional webhooks, and basic anti-spam.

Future versions will layer on a **JS drag-and-drop builder UI** on top of this core.

### 1.2 Target Users

- **Site admins / editors** who need to create and manage forms without code.
- **Developers** who want a consistent, extensible form architecture aligned with SATORI standards.
- **SATORI internal team** for client projects where a lightweight, tightly controlled forms plugin is preferred over large third-party builders.

---

## 2. Scope — v1.0.0 (Locked)

### 2.1 Goals

- Provide a **Form CPT** (`form`) to define forms with JSON-based configuration stored in meta.
- Provide a **Form Submission CPT** (`form_submission`) to persist submissions.
- Provide a **configuration-first builder** via meta boxes (no drag-and-drop) to define:
  - Fields (type, label, name, validation, options, etc.)
  - Email notifications & autoresponders
  - Success messages and redirect rules
  - Basic spam protection & rate limiting
- Implement a frontend renderer via `[satori_form id="123"]` shortcode:
  - Automatic field rendering
  - Client-side & server-side validation
  - Per-field error messages
  - Honeypot & timestamp/token
  - Optional simple rate limiting
- Implement optional **webhook support** for advanced integrations.
- Implement a **global settings page** for defaults.
- Implement a **submissions viewer** & **CSV export**.

### 2.2 Non-Goals & Future Work

The following are explicitly out of scope for v1.0.0 but should be considered in the architecture:

- Full **drag-and-drop JS form builder UI** (planned v1.5+).
- Multi-step forms and conditional logic UI.
- Complex integrations (CRM, payment gateways).
- Advanced analytics & A/B testing.
- Frontend form templates specifically tailored for Satori Events (just expose hooks now).

---

## 3. Feature Breakdown

### 3.1 Form Definitions (Form CPT)

**Description:**  
Forms are stored as a custom post type `form`, with configuration stored in structured meta (JSON arrays).

**Key behaviours:**

- Each form has:
  - Title (used as form name/label).
  - Optional excerpt/description.
  - Meta for configuration (fields, actions, settings).
- Form definitions can be duplicated via standard “Clone” behaviour in future versions (optional v1.1+).

### 3.2 Field Configuration

**Field types supported in v1:**

- `text`
- `email`
- `textarea`
- `select`
- `radio`
- `checkbox`
- `date`
- `file`
- `hidden`
- `honeypot`
- `submit` (implicitly handled; one submit button per form)

**Typical field schema (stored as array of arrays/objects):**

Each field is represented as:

- `id` (string; unique within form, e.g. `field_abc123`)
- `type` (string; one of the above)
- `name` (string; HTML name attribute / meta key)
- `label` (string; human-friendly label)
- `placeholder` (string; optional)
- `required` (bool)
- `options` (array; for select/radio/checkbox)
- `default` (mixed)
- `help_text` (string; optional)
- `validation` (string/array; e.g. `email`, `max_length:255`)
- `css_class` (string; optional)
- `wrapper_class` (string; optional; applied to container)
- `attributes` (key/value pairs for additional HTML attributes)

The meta storage format is described in section 4.

### 3.3 Form Rendering (Frontend)

Provided via `[satori_form id="123"]`:

- Renders the configured fields in DOM order.
- Automatically generates:

  - `<form>` wrapper with nonce and hidden fields.
  - Labeled `<input>`/`<textarea>`/`<select>` elements.
  - A submit button (can be controlled via configuration).

- Handles:

  - Client-side validation (HTML5 + minimal JS).
  - Server-side validation for all required fields and field-specific rules.
  - Error feedback: per-field + global error message area.

- On success:

  - Stores submission in DB (CPT `form_submission`).
  - Sends configured email(s) and webhook(s).
  - Either:
    - Shows inline success message (same page), or
    - Redirects to a configured URL.

### 3.4 Submission Handling & Storage

Submissions are stored using a dedicated CPT `form_submission`:

- Each submission:
  - is `post_type = form_submission`
  - has `post_parent = <form_ID>`
  - stores payload as JSON meta
  - stores metadata like IP, user agent, timestamp, referer, etc.

Admin features:

- Submissions are viewable in a list table.
- Submissions can be exported as CSV.
- Optional filtering by form, date range, and status (just basic filtering in v1).

### 3.5 Notifications & Actions

On successful validation:

- **Email Admin:**
  - To: one or more configured recipient addresses (global default + form-specific).
  - Subject/template configurable.
- **Email Submitter:**
  - Optional autoresponder.
  - Sent to the value of a chosen email field.
- **Webhook:**
  - Optional HTTP POST to a configured URL, with JSON payload.
- **Redirect:**
  - Optional redirect URL after success (takes precedence over inline success message if set).

### 3.6 Anti-Spam & Rate Limiting

- Honeypot field:
  - Hidden via CSS; bots fill it, humans don’t.
- Timestamp/token:
  - Hidden field storing form load time.
  - Reject submissions that are too fast (e.g. < N seconds) or too slow (optionally).
- Simple IP-based rate limiting:
  - Configurable limit per IP over a time window (e.g. X submissions per hour).
- Optional integration with future SATORI Core security/logging hooks (only hooks in v1).

---

## 4. Data Model

### 4.1 Custom Post Types

#### 4.1.1 `form` (Form Definitions)

- **Post Type:** `form`
- **Labels:** “Forms”, “Form”
- **Supports:** `title`, `excerpt`
- **Public:** `false`
- **Show UI:** `true`
- **Show in menu:** `true` (under SATORI menu if available, else top-level)
- **Capability Type:** `post` (filterable)
- **Has archive:** `false`
- **Rewrite:** `false` (no front-end archives needed)

#### 4.1.2 `form_submission` (Form Submissions)

- **Post Type:** `form_submission`
- **Labels:** “Submissions”, “Submission”
- **Supports:** `title` (auto-generated), `author` (optional)
- **Public:** `false`
- **Show UI:** `true`
- **Show in menu:** optionally as a submenu under “Forms”
- **Capability Type:** `post` (filterable)
- **Has archive:** `false`
- **Rewrite:** `false`

### 4.2 Meta Fields — Form CPT

Prefix: `_satori_forms_`

- `_satori_forms_fields` (array)
  - Array of field definitions as described in **3.2**.
- `_satori_forms_settings` (array)
  - `success_message` (string; can contain limited HTML)
  - `redirect_url` (string; optional; validated URL)
  - `use_ajax` (bool; reserved for future)
  - `spam_protection` (array)
    - `honeypot_enabled` (bool)
    - `timestamp_enabled` (bool)
    - `min_fill_seconds` (int; minimum seconds before submission)
    - `rate_limit_enabled` (bool)
    - `rate_limit_max_per_hour` (int)
- `_satori_forms_notifications` (array)
  - `admin_email_enabled` (bool)
  - `admin_email_to` (string/array)
  - `admin_email_subject` (string)
  - `admin_email_template` (string)
  - `user_email_enabled` (bool)
  - `user_email_field` (string; name of field used as email)
  - `user_email_subject` (string)
  - `user_email_template` (string)
- `_satori_forms_webhook` (array)
  - `enabled` (bool)
  - `url` (string)
  - `method` (string; e.g. `POST`)
  - `headers` (array of key/value)
  - `payload_format` (string; e.g. `json`)

> Exact storage structure can be an associative array encoded to JSON, or PHP-serialized arrays, but Codex should implement consistent use of `maybe_unserialize()` / `wp_json_encode()` and `json_decode()`.

### 4.3 Meta Fields — Form Submission CPT

Prefix: `_satori_forms_submission_`

- `_satori_forms_submission_form_id` (int; parent form ID, redundant but convenient)
- `_satori_forms_submission_payload` (array/json)
  - Raw field values keyed by field `name`.
- `_satori_forms_submission_meta` (array/json)
  - `ip_address`
  - `user_agent`
  - `referer`
  - `submitted_at` (timestamp or ISO string)
- `_satori_forms_submission_status` (string; e.g. `new`, `read`, `archived`)

### 4.4 Options (Global Settings)

Use `satori_forms_options` option record (array) to store:

- `default_admin_email_to` (string)
- `default_admin_email_subject` (string)
- `default_user_email_subject` (string)
- `default_success_message` (string)
- `default_spam_settings` (array)
  - as per `_satori_forms_settings['spam_protection']`
- `recaptcha_enabled` (bool; for future)
- `recaptcha_site_key` (string; future)
- `recaptcha_secret_key` (string; future)

---

## 5. Architecture & Folder Structure

### 5.1 Main Plugin File

**File:** `satori-forms.php` (in repo root)

Responsibilities:

- Plugin header with name, description, version, author, text domain.
- Define constants:
  - `SATORI_FORMS_VERSION`
  - `SATORI_FORMS_PATH`
  - `SATORI_FORMS_URL`
  - `SATORI_FORMS_BASENAME`
- Load autoloader: `includes/autoloader.php`.
- Initialize main plugin class: `Satori\Forms\Plugin::init()`.

### 5.2 Namespaces & Core Classes

Namespace root: `Satori\Forms`

Core classes (under `includes/`):

- `includes/autoloader.php`
  - Simple PSR-4 style autoloader for `Satori\Forms\*`.
- `includes/class-plugin.php`
  - Main orchestrator.
  - Registers CPTs, meta boxes, templates, shortcodes, assets, hooks.
- `includes/post-types/class-form-post-type.php`
  - Registers `form` CPT.
- `includes/post-types/class-form-submission-post-type.php`
  - Registers `form_submission` CPT.
- `includes/meta/class-form-meta.php`
  - Handles meta boxes for form definition.
  - Save logic, validation, sanitization.
- `includes/meta/class-submission-meta.php`
  - Optional: manage additional submission meta (status, admin notes).
- `includes/frontend/class-form-renderer.php`
  - Renders forms in frontend.
  - Renders fields based on definitions.
  - Handles client-side support hooks.
- `includes/frontend/class-form-handler.php`
  - Handles POST submissions, validation, spam checks, storage, actions.
- `includes/admin/class-admin-menu.php`
  - Adds Forms menu and submenus.
- `includes/admin/class-form-list-table.php` (or augmentation via hooks)
  - Custom columns for forms.
- `includes/admin/class-submissions-list-table.php` (or similar)
  - Custom columns, filters, for `form_submission`.
- `includes/admin/class-settings-page.php`
  - Global settings.
- `includes/shortcodes/class-form-shortcode.php`
  - Implements `[satori_form]`.

Template loader (optional, or integrated into `Form_Renderer`):

- `includes/templates/class-template-loader.php`
  - Supports theme overrides in `satori-forms/` directory.

### 5.3 Folders

- `includes/`
- `includes/post-types/`
- `includes/meta/`
- `includes/frontend/`
- `includes/admin/`
- `includes/shortcodes/`
- `includes/templates/`
- `templates/`
  - `form.php` (wrapper for full form)
  - `fields/field-text.php`
  - `fields/field-email.php`
  - `fields/field-textarea.php`
  - `fields/field-select.php`
  - `fields/field-radio.php`
  - `fields/field-checkbox.php`
  - `fields/field-date.php`
  - `fields/field-file.php`
  - `fields/field-hidden.php`
  - `fields/field-honeypot.php`
  - `parts/errors.php`
  - `parts/success-message.php`
- `assets/css/`
  - `satori-forms.css`
- `assets/js/`
  - `satori-forms.js` (for minimal validation, optional)
- `languages/`
- `docs/`
  - `SATORI-FORMS-SPEC.md` (this file)
  - `PLUGIN-DETECTION-REPORT.md`
  - `VERIFICATION-NOTES.md`
  - `CODEX-WORKFLOW-AUDIT.md`
  - `CHANGELOG.md` (if kept under docs/)
- `build/` (release zips, created later)

---

## 6. Frontend UX

### 6.1 Form Display

- Forms are rendered via `[satori_form id="123"]` shortcode.
- Default markup:

  - `<form class="satori-forms-form satori-forms-form--{form_id}">`
  - Each field wrapped in container with classes:
    - `satori-forms-field`
    - `satori-forms-field--{type}`
    - `satori-forms-field--{name}`

- Each field includes:
  - `<label>` with `for` attribute.
  - `<input>`/`<textarea>`/`<select>` with `id` and `name`.
  - Optional help text.
  - Error display container (if errors present).

### 6.2 Validation UX

- **Client-side:**
  - HTML5 attributes (e.g. `required`, `type="email"`, `min`, `max`).
  - Optional small JS enhancements in `satori-forms.js` to:
    - Scroll to first error.
    - Highlight invalid fields.
- **Server-side:**
  - Validate all required fields.
  - Validate email format for email fields.
  - Validate file types/size for file fields.
  - Validate date format for date fields.

### 6.3 Success & Error Messages

- Top-level **error summary** shown above the form when validation fails.
- Per-field errors shown under each field.
- On success:
  - Show success message (from form settings or global default) in place of form, unless redirect is configured.
  - If redirect URL is set and valid, redirect there instead of showing inline message.

### 6.4 Responsiveness & Accessibility

- Fields stack vertically on small screens.
- Labels associated with inputs via `for` and `id`.
- ARIA attributes used where sensible for error messaging.
- Avoid inline styles; use CSS classes only.

---

## 7. Admin UX

### 7.1 Forms List Table

- Columns:
  - Title (form name)
  - Shortcode (e.g. `[satori_form id="123"]`)
  - Number of submissions
  - Last submitted date
- Row actions:
  - Edit
  - View submissions (links to filtered `form_submission` list)
  - Trash

### 7.2 Form Edit Screen

Meta boxes:

1. **Form Fields**
   - Interface to manage repeatable fields.
   - Simple UI, using repeatable sets:
     - Type (select)
     - Label
     - Name
     - Required (checkbox)
     - Options (for select/radio/checkbox; maybe a textarea or structured input)
     - Validation (e.g. “email” for email fields)
     - Default value
     - Placeholder
   - Saved to `_satori_forms_fields`.

2. **Form Behaviour & Messages**
   - Success message (textarea).
   - Redirect URL (optional).
   - Spam protection toggles (honeypot, timestamp, min seconds).
   - Rate limit settings (on/off, max per hour per IP).
   - Saved to `_satori_forms_settings`.

3. **Notifications**
   - Admin email toggle, recipients, subject, template.
   - User email toggle, email-field binding, subject, template.
   - Saved to `_satori_forms_notifications`.

4. **Webhook**
   - Webhook toggle, URL, method, headers, payload format.
   - Saved to `_satori_forms_webhook`.

### 7.3 Submissions List

- List table for `form_submission` posts.
- Columns:
  - Date
  - Form (parent)
  - IP
  - Status
  - Key field values preview (e.g. name/email)
- Filters:
  - By form (dropdown)
  - By date (month/year)
- Bulk actions:
  - Mark as read/archived (future optional).
- Single submission detail screen:
  - Show all fields + metadata.

### 7.4 Settings Page (Global)

Under “Forms → Settings”:

- Default admin emails/subjects.
- Default user email subjects.
- Default success message.
- Global spam defaults (honeypot, timestamp, min fill seconds, rate limiting).
- Recaptcha keys (for future use; can be present but not enforced in v1.0.0).

---

## 8. Hooks & Extension Points

### 8.1 Actions

- `do_action( 'satori_forms_before_render', $form_id, $context );`
  - Before rendering a form.
- `do_action( 'satori_forms_after_render', $form_id, $context );`
  - After rendering a form.
- `do_action( 'satori_forms_before_validate', $form_id, $submission_data, $request );`
  - Before server-side validation.
- `do_action( 'satori_forms_after_validate', $form_id, $is_valid, $errors, $submission_data );`
  - After validation, before persisting.
- `do_action( 'satori_forms_before_submit', $form_id, $submission_data );`
  - Before storing submission and sending notifications.
- `do_action( 'satori_forms_after_submit', $form_id, $submission_id, $submission_data );`
  - After storing submission and sending notifications.
- `do_action( 'satori_forms_after_email_admin', $form_id, $submission_id );`
- `do_action( 'satori_forms_after_email_user', $form_id, $submission_id );`
- `do_action( 'satori_forms_after_webhook', $form_id, $submission_id, $response );`

### 8.2 Filters

- `apply_filters( 'satori_forms_form_settings', $settings, $form_id );`
- `apply_filters( 'satori_forms_fields', $fields, $form_id );`
- `apply_filters( 'satori_forms_validate_field', $result, $field_def, $value, $form_id );`
  - `$result` contains `valid` bool and `message` string.
- `apply_filters( 'satori_forms_submission_payload', $payload, $form_id );`
- `apply_filters( 'satori_forms_notification_admin_email', $email_data, $form_id, $submission_id );`
- `apply_filters( 'satori_forms_notification_user_email', $email_data, $form_id, $submission_id );`
- `apply_filters( 'satori_forms_webhook_request_args', $request_args, $form_id, $submission_id );`
- `apply_filters( 'satori_forms_rate_limit_key', $key, $form_id, $ip );`

These hooks should be implemented in such a way that other SATORI plugins (e.g. SATORI Events, SATORI Core) can later hook into form submission pipeline.

---

## 9. Coding Standards & Requirements

- Namespace root: `Satori\Forms`.
- Autoloader: PSR-4-like mapping in `includes/autoloader.php`.
- File format:
  - UTF-8
  - Unix line endings (LF)
  - No BOM.
- PHPCS:
  - Use WordPress-Extra, WordPress-Docs, and any SATORI custom rules.
- Security:
  - Validate and sanitize all request data.
  - Escape all output in templates (`esc_html`, `esc_attr`, `esc_url`, etc.).
  - Use nonces for all form submissions.
  - Enforce capability checks in admin (e.g. `manage_options` or custom caps).
- Compatibility:
  - Fail gracefully on unsupported PHP/WP with admin notice and no fatal errors.

---

## 10. Acceptance Criteria (v1.0.0)

1. Activating SATORI Forms registers the `form` and `form_submission` CPTs.
2. Admins can create new forms via the Form edit screen using meta boxes for fields, settings, notifications, and webhook.
3. The `[satori_form id="123"]` shortcode renders a form on any post/page.
4. All configured fields render correctly and respect required flags and labels.
5. Client-side validation prevents submission for obviously invalid fields (e.g. empty required, invalid email).
6. Server-side validation blocks invalid submissions and shows:
   - A global error summary.
   - Per-field error messages.
7. On success:
   - A `form_submission` entry is created with payload and meta.
   - Admin receives an email if configured.
   - The user receives an email if configured and if the email field is present.
   - Webhook is called if enabled.
8. If a redirect URL is configured, user is redirected after success; otherwise, a success message is displayed inline.
9. Honeypot, timestamp, and rate-limiting features prevent basic spam and rapid re-submissions as configured.
10. Forms list table shows the shortcode, submission counts, and last submitted date.
11. Submissions list table shows at least date, form, IP, status, and a short summary.
12. Submissions can be exported to CSV.
13. Theme overrides in `theme/satori-forms/` are respected for templates.
14. Running PHPCS on the plugin yields no errors (warnings acceptable only if unavoidable and commented).
15. Edge case: existing forms with no fields should fail gracefully with a helpful message (no fatal errors).

---

## 11. Documentation & Build Outputs

Codex should ensure the following documentation files exist and are reasonably populated:

- `docs/SATORI-FORMS-SPEC.md` (this master spec).
- `docs/PLUGIN-DETECTION-REPORT.md`
  - Confirm plugin header and detection by WordPress.
- `docs/VERIFICATION-NOTES.md`
  - Summarize key tests performed (manual or automated).
- `docs/CODEX-WORKFLOW-AUDIT.md`
  - Briefly describe how Codex built/modified the plugin.

For release tasks (later):

- `docs/CHANGELOG.md` or root `CHANGELOG.md` with:
  - Entry for v1.0.0 detailing major features.
- `/build/satori-forms-1.0.0.zip`
  - Generated by Codex for distribution.

---

## 12. Notes for Codex Build Plan

When using Codex to implement this spec:

- Start from a clean repo with only:
  - `README.md`
  - Basic `.gitignore` (optional)
  - `docs/SATORI-FORMS-SPEC.md`
- Implement the architecture and features as described.
- Use a dedicated branch:
  - `codex-satori-forms-v1-from-scratch`
- Open a PR against `main` summarising:
  - CPTs
  - Meta structure
  - Shortcode
  - Submission flow
  - Anti-spam
  - Admin UI
- Ensure main plugin file is **not** nested; it must live at the repo root in the plugin folder.

This spec defines the **source of truth** for SATORI Forms v1.0.0.
