---
paths:
  - 'lang/**'
---

# Lang

## Neutral language and Indonesian technical terms
Use neutral, impersonal wording in language messages and comments; omit personal forms of address and personal pronouns (e.g. you/your/we/our, Anda/kamu/kami/kita). For Indonesian translations in lang/id/**, use Indonesian sentences while retaining English technical terms such as password, login, email, token, field, file, array, string, and URL. Keep translation keys, nested structure, and interpolation placeholders consistent with the corresponding English files.

## Order translation scopes and keys consistently
Order domain translation files by title, card, label, placeholder, helper, button, status, and error; place feature-specific groups together in a clear logical order. Within each scope, follow the related UI/form order, keeping associated keys adjacent and ordering title before description. Use the same scope and key order in en/id, separate scopes with a blank line, and retain the established order when adding keys.

## Wording adds information for its audience
Descriptions explain content or impact instead of repeating the title or using "Manage <title>."; helpers add consequences, limits or formats instead of restating the title or description; text in shared lists stays valid when optional modules are disabled. Screens for all users use only the technical terms listed above, while admin and operator pages may also use operator terms (SMTP, port, STARTTLS, queue worker, scheduler, cache, storage, maintenance mode, application log). Describe impact, not mechanism: avoid driver names, probe, idempotency, synchronous, temporary, queued, identifier, .env and local tooling (Mailpit, lerd, localhost, dev ports). Local-only hints render behind an environment check passed from the controller.

## One key per position and no raw values
Keep each key in its own position: list card.description is not reused in sheets, dialogs or detail pages, button.* keys are not titles, and navigation.* keys are not page titles. Do not render permission codes, group prefixes, field names, enum values or JSON.stringify output; send a label from the backend or map it through a translation key, keeping the raw value only as a documented fallback. Render a status only when it is informative: hide default or idle states and show a repeated per-item status only on the relevant item.

## Sentence case, no filler, fixed Indonesian terms
Write titles, labels and buttons in sentence case in both locales, keeping proper nouns and acronyms such as API, SMTP, 2FA and Mailpit. Omit filler such as Please, Silakan, successfully and filler berhasil (keep berhasil only when it carries meaning); success messages follow "<Object> <passive verb>.", for example "Profile updated." / "Profil diperbarui.". In Indonesian use pengguna, perbarui, upload/download (di-upload, di-download; never unggah/unduh), code, password, login, email, token, file and link. tests/Acceptance/LangWordingTest.php enforces the forbidden patterns in test:lint; allowlist a key there only with a reason, as for settings.mail.hint.mailpit (local-only hint) and enums.audit_event.UserInvitationQueued (operator audit record).
