<!-- Write the title and description entirely in English. Replace all prompts before submitting. -->
<!-- Use an English Conventional Commit title; mark breaking changes with !. -->

## Summary

<!-- Explain the concrete problem and the resulting behavior in one or two sentences. -->

## Changes

<!-- List the meaningful changes and why they matter. Keep detail proportional to the scope. -->

## Validation

<!-- List actual commands and results, manual checks, and checks not run with reasons. -->
<!-- Include screenshots for visual changes when available. -->

- [ ] Relevant checks and `composer test` pass.
- [ ] `tests / CI` passes on the latest PR head; review feedback is resolved.

## Risks and rollout

<!-- Describe breaking changes, migrations, compatibility concerns, and rollback steps when applicable. Otherwise write None. -->

## Merge completion

- [ ] Squash merge into `main`, verify GitHub reports `MERGED`, and record the merge commit.
- [ ] Update local `main` with `git pull --ff-only`; remove the merged remote and local branch after preserving any additional work.
