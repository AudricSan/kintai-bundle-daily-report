# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

### Fixed

- `DailyReportController::indexAll()` — un utilisateur non-Owner dont le rôle accorde `daily_reports.view` en portée globale (case "Toutes les boutiques", `managed_store_ids === null`) voyait le sélecteur de magasin vide et se faisait refuser tout filtre `?store_id=` précis, alors que la liste de rapports elle-même restait visible par coïncidence (convention "tableau vide = pas de filtre" de `findAllActive()`). La méthode traitait `managed_store_ids === null` comme équivalent à `[]` (`?? []`) au lieu de le traiter comme Owner/is_admin — c'est-à-dire "aucune restriction". Traité maintenant de façon uniforme avec `is_admin`.

## [1.0.0] - 2026-09-19

### Added

- Extraction initiale depuis Kintai (`src/Bundles/DailyReport`).
