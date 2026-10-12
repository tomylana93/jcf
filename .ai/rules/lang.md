---
paths:
  - 'lang/**'
---

# Lang

## Neutral language and Indonesian technical terms
Use neutral, impersonal wording in language messages and comments; omit personal forms of address and personal pronouns (e.g. you/your/we/our, Anda/kamu/kami/kita). For Indonesian translations in lang/id/**, use Indonesian sentences while retaining English technical terms such as password, login, email, token, field, file, array, string, and URL. Keep translation keys, nested structure, and interpolation placeholders consistent with the corresponding English files.

## Order translation scopes and keys consistently
Order domain translation files by title, card, label, placeholder, helper, button, status, and error; place feature-specific groups together in a clear logical order. Within each scope, follow the related UI/form order, keeping associated keys adjacent and ordering title before description. Use the same scope and key order in en/id, separate scopes with a blank line, and retain the established order when adding keys.
