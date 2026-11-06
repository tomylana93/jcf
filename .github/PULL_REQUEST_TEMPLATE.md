<!-- Write the title and description entirely in English. Replace all prompts before submitting. -->
<!-- Use an English Conventional Commit title; mark breaking changes with !. -->

## Summary

<!-- Show the smallest useful diff sketch, tree, flow, or example. -->
<!-- Explain the concrete problem and resulting behavior briefly. -->

## Evidence

<!-- Record actual before/after evidence, commands and results. -->
<!-- Include screenshots when useful; state checks not run and why. -->

- [ ] Relevant checks pass on the latest head; checks not run or not applicable are explained above.
- [ ] `tests / CI` passes on the latest PR head; review feedback is resolved.

## Merge Danger

**Door:** <!-- one-way or two-way -->
**Blast Radius:** <!-- affected area -->

<!-- Describe rollout, migrations, compatibility, and rollback when relevant. -->

## Merge completion

- [ ] Squash merge into `main`, verify GitHub reports `MERGED`, and record the merge commit.
- [ ] Update local `main` with `git pull --ff-only`; remove the merged remote and local branch after preserving any additional work.
