# Listing Health Score for GeoDirectory

![CI](https://github.com/RachidBray/listing-health-score/actions/workflows/ci.yml/badge.svg)

A free WordPress addon for [GeoDirectory](https://wpgeodirectory.com/) that scores every listing 0-100 based on completeness and quality — a featured image, a real description, contact details, photos, reviews, and more — then shows owners exactly what to fix next for the biggest score gain.

- **Admin column** — a sortable, color-coded Health column on every GeoDirectory listing type's admin list table.
- **Settings tab** — a "Health Score" tab inside GeoDirectory's own settings UI to enable/disable criteria, reweight them, and tune targets and score-band thresholds.
- **Owner widget** — a widget / `[lhs_health]` shortcode / Gutenberg block showing the score, band, and a recommendations list sorted by potential score percentage gain, visible to the listing owner and admins (optionally public).

<!-- screenshot: admin Health column and the owner-facing recommendations widget -->

## For contributors

This project follows a documented house-style contract so the code reads like a native AyeCode/GeoDirectory addon. Start with [AGENTS.md](AGENTS.md) — it has the full architecture, the style contract (with source references back into GeoDirectory core), the roadmap, and testing notes.

```bash
composer install
composer lint   # PHPCS / WPCS
composer test   # PHPUnit
```
