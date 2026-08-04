=== Listing Health Score for GeoDirectory ===
Contributors: addictedtoweb
Tags: geodirectory, business directory, listings, seo, gamification
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scores every GeoDirectory listing 0-100 based on completeness and quality, with actionable recommendations for listing owners.

== Description ==

Listing Health Score adds a gamified completeness score to every listing in your [GeoDirectory](https://wpgeodirectory.com/) directory. Admins get an at-a-glance view of which listings need attention; listing owners get a clear, prioritized checklist of what to fix next for the biggest score gain.

= Features =

* **0-100 health score** for every listing, calculated from a weighted set of criteria: featured image, logo, description length, opening hours, phone, email, website, social links, photo gallery, review count, claimed status, and freshness (how recently the listing was updated).
* **Admin column** — a sortable, color-coded Health column on every GeoDirectory post type's admin list table, so admins can spot and sort by low-scoring listings instantly.
* **Admin filter + bulk recalculate** — filter any listing type's admin list table by health band (Good / Needs improvement / Poor), and recalculate the score for any selection via the "Recalculate Health Score" bulk action.
* **Owner-facing widget / shortcode / block** — `[lhs_health]` (also available as a widget and a Gutenberg block) shows a "complete your listing" checklist: percent-complete progress bar and a recommendations list sorted by potential score percentage gain, with the single biggest opportunity highlighted, so owners know exactly what to fix first. Visible to the listing owner and site admins by default; can be set to show publicly.
* **Settings tab inside GeoDirectory's own settings UI** — enable or disable individual criteria, reweight them, and tune scaling targets (description length, photo/review/social counts, freshness decay window) and the score-band thresholds, all without touching code.
* **Automatic recalculation** — scores update when a listing is saved, when reviews are added or change status, when a listing is claimed (if the Claim Listing addon is active), and via a daily batched cron job so freshness decay stays accurate even without new activity. Changing settings automatically invalidates and lazily recalculates affected scores.
* **Developer-friendly** — every scaling target, threshold, and the criteria list itself are filterable; a `lhs_score_updated` action fires whenever a score changes. See `AGENTS.md` in the plugin's GitHub repository for the full list of hooks.
* **Free, no upsells** — the full feature set is free. No premium tier, no license key, no nags.

= Requirements =

* [GeoDirectory](https://wordpress.org/plugins/geodirectory/) v2 or later must be installed and active. Listing Health Score does nothing without it.
* The optional Claim Listing addon is supported but not required — the "claimed" criterion simply doesn't penalize listings when that addon isn't active.

== Installation ==

1. Make sure [GeoDirectory](https://wordpress.org/plugins/geodirectory/) is installed and active first.
2. Upload the `listing-health-score` folder to `/wp-content/plugins/`, or install it through the WordPress admin's Plugins > Add New screen.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Visit GeoDirectory > Settings > Health Score to adjust criteria, weights, and targets if you don't want the defaults.
5. Add the `[lhs_health]` shortcode, the "Listing Health Score" widget, or its Gutenberg block to your single listing template so owners can see their score and recommendations.

== Frequently Asked Questions ==

= Does this slow down my site? =

No. Scores are calculated once and stored as post meta, then only recalculated when something relevant changes (the listing is saved, a review comes in, settings change) or once a day in a small batch for freshness decay. Nothing is recalculated on every page load.

= Who can see a listing's health score? =

By default, only the listing owner and site admins can see the score via the widget/shortcode/block. Each widget instance has a "Show score publicly" option if you want visitors to see it too. The admin Health column is always admin-only, like the rest of wp-admin.

= What happens if the Claim Listing addon isn't installed? =

The "claimed" criterion simply isn't counted against you — it returns full credit so your maximum achievable score isn't affected by an addon you don't have installed.

= Can I change which criteria count, or how much each is worth? =

Yes. Go to GeoDirectory > Settings > Health Score to enable/disable individual criteria and adjust what percentage of the score each is worth, plus the targets used for criteria that scale (description length, photo count, etc.) and the score band thresholds. Enabled criteria must add up to exactly 100% — the page shows a running total as you edit, and won't save until it does.

= Can I add my own criteria? =

Yes, via the `lhs_criteria` filter. See `AGENTS.md` in the plugin's source repository for the full hook reference.

= Does this work without GeoDirectory? =

No. Listing Health Score is a GeoDirectory addon and requires GeoDirectory to be installed and active — it will show an admin notice if it can't detect GeoDirectory.

== Screenshots ==

1. The sortable, color-coded Health column on a GeoDirectory listing type's admin list table.
2. The Health Score settings tab inside GeoDirectory's settings UI — criteria, weights, targets, and score bands.
3. The owner-facing widget showing a listing's score, band, and prioritized recommendations.

== Changelog ==

= 0.8.0 =
* Owner-facing widget: redesigned as a "complete your profile" style checklist — a striped progress bar with a percent-complete/percent-remaining row, and recommendation cards with the single biggest-win item visually highlighted - CHANGED
* Owner-facing widget: recommendation percentages show a "+" prefix again (e.g. "+8%") — reverses the 0.6.3 change now that they're whole numbers, so it no longer reads as noisy alongside a decimal - CHANGED

= 0.7.1 =
* Settings tab: a rejected save (criteria not summing to 100) now correctly replaces GeoDirectory's own "settings saved" message with an error, instead of both appearing separately where the error could be missed - FIXED

= 0.7.0 =
* Settings tab: per-criterion weight fields are now labeled and validated as a percentage of the score, with a live running total that turns red/green as you edit - ADDED
* Settings tab: saving is now rejected (with an explanatory notice, nothing is persisted) if enabled criteria don't add up to exactly 100 - ADDED

= 0.6.3 =
* Owner-facing widget: recommendation percentages now round to a whole number and dropped the "+" prefix (e.g. "8%" instead of "+7.5%") - CHANGED

= 0.6.2 =
* Owner-facing widget: the score meter now shows the percentage directly inside the bar, always legible regardless of fill size - ADDED
* Owner-facing widget: recommendations now show potential score gain as a percentage instead of raw points, computed against the listing's actual total criteria weight (not just relabeled) - CHANGED

= 0.6.1 =
* Owner-facing widget: score meter's unfilled track is now a lighter tint of its own band color instead of neutral gray, so the band reads across the whole bar - CHANGED
* Owner-facing widget: switched the legacy (non-AyeCode UI) band colors to a validated, accessible status palette, with per-band text color chosen from measured contrast rather than a fixed white - CHANGED
* Owner-facing widget: recommendations list now shows the point-gain as a trailing pill instead of inline parenthetical text, for clearer visual hierarchy - CHANGED

= 0.6.0 =
* Admin health-band filter dropdown on listing type list tables - ADDED
* "Recalculate Health Score" bulk action on listing type list tables - ADDED

= 0.5.0 =
* wp.org readiness: readme.txt, uninstall.php, .pot file - ADDED

= 0.4.0 =
* Owner recommendations panel as a Super Duper widget / `[lhs_health]` shortcode / Gutenberg block - ADDED

= 0.3.0 =
* Settings tab inside GeoDirectory's settings UI (criteria weights, targets, score bands) - ADDED
* Changing settings now invalidates and lazily recalculates affected scores - ADDED

= 0.2.0 =
* Refactored the plugin shell to boot as an AyeCode-native singleton on `geodirectory_loaded` - CHANGED

= 0.1.0 =
* Initial release: scoring engine, recalculation hooks, admin Health column - ADDED
