# Verification Notes

Manual verification focused on the behaviours locked in `docs/SATORI-FORMS-SPEC.md`:

1. **Post type registration** – confirmed the `form` and `form_submission` CPT definitions are hooked on `init`, include the required supports, and are hidden from the public front end.
2. **Meta boxes & persistence** – walked through the form editor meta boxes to ensure fields, behaviour, notifications, and webhook data post to the expected `_satori_forms_*` meta keys with sanitisation. Assumed JSON configuration entered in the "Form fields" textarea follows the schema from the spec.
3. **Shortcode & rendering** – reviewed `Form_Renderer` + templates to ensure `[satori_form id="123"]` renders all configured fields, adds nonce/honeypot/timestamp inputs, shows error summaries, and outputs inline field errors.
4. **Submission flow** – stepped through `Form_Handler::process()` to ensure nonce validation, honeypot/timestamp checks, rate limiting via transients, payload/meta storage, notification emails, and webhook dispatches are wired to the documented hooks.
5. **Admin UI** – validated the custom admin menu routes to the CPT screens and the settings page persists global defaults via `satori_forms_options`.

## Assumptions & Limitations

- **Field builder input**: For v1 the UI accepts JSON in the fields meta box. A richer UI can be layered later, but the current implementation expects valid JSON; invalid payloads are silently discarded.
- **File uploads**: Files use `wp_handle_upload()`; no additional validation beyond what WordPress provides was added.
- **Emails/Webhooks**: Notification sending and webhook requests are wired but not executed in this environment.
- **Rate limiting**: Uses `set_transient()` per form/IP pair; assumes object cache/transients are available.

## Outstanding Checks

- **PHPCS** could not be run because Composer was unable to reach Packagist from the execution environment (`curl error 56 … CONNECT tunnel failed`). See the test log in the PR/testing summary.
