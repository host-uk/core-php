# Database Query Optimization Audit

This document outlines the findings of a database query optimization audit.

## 1. Missing Indexes

### `activities` Table

**Finding:** The `activities` table, used by the `spatie/laravel-activitylog` package, was missing indexes on the polymorphic `subject` and `causer` columns. This can lead to slow queries when filtering activities by these relationships.

**Recommendation:** Add composite indexes to the `(subject_type, subject_id)` and `(causer_type, causer_id)` columns.

**Action Taken:** A migration has been created to add these indexes.

## 2. Environment and Dependencies

**Finding:** The project has conflicting dependencies, specifically with `vimeo/psalm`, which prevents the installation of development tools like Laravel Debugbar and blocks the execution of the test suite.

**Recommendation:** Resolve the dependency conflicts in `composer.json` to ensure a stable and consistent development environment. This is crucial for proper testing and debugging.

## 3. Further Analysis

The following files have been identified for further manual review to identify potential N+1 query problems, inefficient Eloquent patterns, and other query-related issues:

*   `src/Mod/Trees/Models/TreeDonation.php`
*   `src/Mod/Trees/Models/TreePlanting.php`
*   `src/Mod/Trees/Models/TreePlantingStats.php`
*   `src/Mod/Trees/Models/TreeReserve.php`
*   `src/Mod/Trees/Controllers/Api/TreeStatsController.php`
