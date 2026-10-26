# Minimal growth attribution

User scope: update Juejin observations, prepare a second distribution draft, and implement the missing source/goal counts locally for review. No production deployment or new platform post in this change.

Use NimbusKit, not a new service. Default-off setting plus configurable `key|label|url` goals. Aggregate counters per site day, kind, finite source bucket and goal key. A short first-party source cookie preserves the first source for 30 minutes; per-goal cookies deduplicate repeat clicks in the same window. These are browser-window counters, not unique visitors or sales. Do not persist IP, user agent, full referrer, page query or identities. Honor DNT/GPC and exclude authenticated Typecho users.

Tasks executed inline:
1. Add source/goal validation and a portable aggregate schema; prove with PHP unit tests.
2. Add same-origin POST endpoint, opt-in configuration, safe upgrade initialization and read-only admin report; prove real HTTP behavior against an isolated Typecho database.
3. Add a navigation-safe frontend collector and configurable public goal data. Test real event dispatch, source minimization, router reinitialization, preferences and failure handling.
4. Verify the complete suites, review the branch, push a PR and supply a local preview plus deployment instructions. Update distribution observations separately; keep public copy separate from internal notes.

Review focus: no personal hardcoding; no double listeners; attribution survives internal navigation; invalid/hidden goals never count; database upgrade and concurrent aggregate increments preserve data; statistics do not block navigation.

Ruling: retain the existing plugin's short-cookie counting model rather than introduce visitor/session identity tables. Concurrent first requests may race as with existing view counts; report approximate, deduplicated browser events, not UV or audited business outcomes.

Verification (2026-10-26): 175 PHP unit checks, 38 JavaScript checks, 19 disposable HTTP checks, lint, stats-counts and Cuteen compatibility pass. Actual anonymous browser clicks produced one Juejin arrival plus one click per configured goal in the local DB; the admin report rendered those synthetic counts. Fresh read-only review found no important issue. HTTP admin files are copied into the disposable site because symlinked admin/common.php would resolve the wrong config. MySQL/PostgreSQL have schema tests only. Production remains unchanged.
