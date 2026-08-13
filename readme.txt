=== ListiScore - Listing Health Score for GeoDirectory ===
Contributors: tartamata
Tags: geodirectory, business directory, listings, seo, gamification
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scores every GeoDirectory listing 0-100 based on completeness and quality, with actionable recommendations for listing owners.

== Description ==

ListiScore adds a gamified completeness score to every listing in your [GeoDirectory](https://wpgeodirectory.com/) directory. Admins get an at-a-glance view of which listings need attention; listing owners get a clear, prioritized checklist of what to fix next for the biggest score gain.

Source code and development happen in the open on [GitHub](https://github.com/RachidBray/listiscore).

= Features =

* **0-100 health score** for every listing, calculated from a weighted set of criteria: featured image, logo, description length, opening hours, phone, email, website, social links, photo gallery, review count, and freshness (how recently the listing was updated).
* **Admin column**: a sortable, color-coded Health column on every GeoDirectory post type's admin list table, so admins can spot and sort by low-scoring listings instantly.
* **Admin filter + bulk recalculate**: filter any listing type's admin list table by health band (Good / Needs improvement / Poor), and recalculate the score for any selection via the "Recalculate Health Score" bulk action.
* **Owner-facing widget / shortcode / block**: `[listiscore_health]` (also available as a widget and a Gutenberg block) shows a "complete your listing" checklist: percent-complete progress bar and a recommendations list sorted by potential score percentage gain, so owners know exactly what to fix first. Visible only to the listing owner and site admins.
* **Settings tab inside GeoDirectory's own settings UI**: enable or disable individual criteria, reweight them, and tune scaling targets (description length, photo/review/social counts, freshness decay window) and the score-band thresholds, all without touching code.
* **Automatic recalculation**: scores update when a listing is saved, when reviews are added or change status, and via a daily batched cron job so freshness decay stays accurate even without new activity. Changing settings automatically invalidates and lazily recalculates affected scores.
* **Developer-friendly**: every scaling target, threshold, and the criteria list itself are filterable; a `listiscore_score_updated` action fires whenever a score changes.
* **Free, no upsells**: the full feature set is free. No premium tier, no license key, no nags.

= Requirements =

* [GeoDirectory](https://wordpress.org/plugins/geodirectory/) v2 or later must be installed and active. ListiScore does nothing without it.

== Installation ==

1. Make sure [GeoDirectory](https://wordpress.org/plugins/geodirectory/) is installed and activated.
2. In your WordPress dashboard, go to 'Plugins' > 'Add Plugin', search for ListiScore, and install it from the WordPress.org plugin directory. Alternatively, you can download the plugin from WordPress.org and install it manually.
3. Activate ListiScore through the Plugins menu in WordPress.
4. Go to 'GeoDirectory' > 'Settings' > 'Health Score' to review and adjust the scoring criteria, weights, and targets. The plugin works with the default settings out of the box.
5. Add the ListiScore widget, Gutenberg block, or [listiscore_health] shortcode to your single listing template or listing page to display the health score and recommendations to listing owners.

== Frequently Asked Questions ==

= Does this slow down my site? =

No. Scores are calculated once and stored as post meta, then only recalculated when something relevant changes (the listing is saved, a review comes in, settings change) or once a day in a small batch for freshness decay. Nothing is recalculated on every page load.

= Who can see a listing's health score? =

Only the listing owner and site admins can see the score via the widget/shortcode/block, never the general public. The admin Health column is always admin-only, like the rest of wp-admin.

= Can I change which criteria count, or how much each is worth? =

Yes. Go to GeoDirectory > Settings > Health Score to enable/disable individual criteria and adjust what percentage of the score each is worth, plus the targets used for criteria that scale (description length, photo count, etc.) and the score band thresholds. Enabled criteria must add up to exactly 100%: the page shows a running total as you edit, and won't save until it does.

= Does a low score hide a listing or hurt its ranking? =

No. The health score is informational only, shown to the listing owner and site admins. It never affects a listing's visibility, its position in directory search results, or any frontend sorting; the only place a listing's score changes what an admin sees is the wp-admin Health column and its filter/sort.

= Does this work without GeoDirectory? =

No. ListiScore is a GeoDirectory addon and requires GeoDirectory to be installed and active; it will show an admin notice if it can't detect GeoDirectory.

== Screenshots ==

1. Owner facing widget showing a listing's score both in progress and fully complete.
2. The Health Score settings tab

== Changelog ==

= 1.0.0 =
* First stable release - CHANGED

= 0.8.9 =
* Removed the "claimed" criterion and its dependency on the Claim Listing addon entirely: one less thing to configure, and the plugin no longer changes behavior based on whether that addon is installed. Default weights for the remaining 11 criteria were proportionally rescaled so they still sum to 100 - REMOVED

= 0.8.8 =
* Owner-facing widget: removed the "Show score publicly" option: the widget now renders only for the listing owner and site admins, with no visitor-facing mode. Any existing widget instance previously set to show publicly will silently stop showing to visitors - REMOVED

= 0.8.7 =
* Owner-facing widget: dropped the striped texture from the progress bar entirely: now a plain flat fill at every score, matching the 100%-complete state's look - CHANGED

= 0.8.6 =
* Owner-facing widget: the progress bar no longer animates (moving stripes) while incomplete: keeps the static diagonal stripe texture, drops the motion - CHANGED

= 0.8.5 =
* Owner-facing widget: the "Show recommendations" option's admin-facing description still said "potential point gain": updated to "potential score percentage gain" to match how the list has actually been sorted since v0.6.2 - FIXED

= 0.8.4 =
* Recommendations: a criterion that's fractionally incomplete but rounds to 0% potential gain (e.g. freshness decay just under 1.0) is no longer shown as a tip: it read as a confusing "+0%" with nothing actionable behind it - FIXED
* Owner-facing widget: the percent-remaining label dropped the "Only" prefix: now "8% left" instead of "Only 8% left" - CHANGED

= 0.8.3 =
* Owner-facing widget: removed the special highlight styling (border/tint) on the first recommendation card: all recommendations now use the same plain default styling - CHANGED

= 0.8.2 =
* Owner-facing widget: removed extra trailing whitespace under the header block when the recommendations list doesn't render (100% score, or any state with nothing to recommend) - FIXED

= 0.8.1 =
* Owner-facing widget: a 100% score now always shows the "fully optimized" state: a plain (non-striped, non-animated) bar and no recommendations checklist, instead of relying on the recommendations list being empty, which could still contain a zero-value entry due to rounding - FIXED

= 0.8.0 =
* Owner-facing widget: redesigned as a "complete your profile" style checklist: a striped progress bar with a percent-complete/percent-remaining row, and recommendation cards with the single biggest-win item visually highlighted - CHANGED
* Owner-facing widget: recommendation percentages show a "+" prefix again (e.g. "+8%"): reverses the 0.6.3 change now that they're whole numbers, so it no longer reads as noisy alongside a decimal - CHANGED

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
