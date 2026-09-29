# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

### Changed

- Compatibilité avec la Content-Security-Policy stricte de Kintai (`script-src 'self' 'nonce-…'`, sans `'unsafe-inline'`) : les 23 attributs d'événements inline des vues (`onclick=`/`onchange=`/`onsubmit=`/`oninput=`) sont remplacés par des attributs `data-*` (`data-on-click`, `data-submit-on-change`, `data-confirm`… gérés par `csp-actions.js` du Core) et les 2 `<script>` inline portent désormais le nonce de la requête (`csp_nonce()`, gardé par `function_exists` pour ne pas planter sur un Core plus ancien). Sans ce changement, les boutons, sélecteurs et confirmations de ces vues ne font plus rien sous la nouvelle politique, sans aucune erreur visible. **Nécessite Kintai Core 0.3.0 ou plus** (`kintai_core.min`), version qui introduit `csp-actions.js` et la CSP à nonce. `tests.yml` échoue désormais si un handler inline, un lien `javascript:` ou un `<script>` sans nonce réapparaît dans `Views/` ou `src/`.

### Changed

- Le CSS de la modale de détail (`.dr-v2-*`, `.dr-show-grid`/`.dr-kpi-table`/`.dr-meta-table`), le JS de formatage numérique (`numeric-input.js`) et le CSS du PDF (`pdf-daily-report.css`) vivaient dans Kintai Core — le premier éclaté entre le composant partagé `modals.css` et `responsive.css`, sans fichier dédié. Tout vit maintenant dans `public/css/daily-report.css`/`public/js/numeric-input.js`/`public/css/pdf-daily-report.css`, fournis par ce bundle via `Bundle::loadAssetsFrom()`/`bundle_asset()`/`bundle_asset_path()`. Corrige au passage un bug latent : `daily-report-pdf.php` résolvait le chemin de son propre CSS PDF via `dirname(__DIR__, 4)`, correct pour l'ancien emplacement monorepo mais pas pour un bundle installé dynamiquement (`storage/bundles/{slug}/{version}/`, un niveau de plus) — le PDF perdait donc son style spécifique en production. **Nécessite** `kintai_core.min: "0.2.0"`.

## [1.1.0] - 2026-09-29

### Fixed

- `submit()` passait une phrase française codée en dur (`'Un rapport journalier attend votre validation.'`) comme clé de traduction du corps de la notification `daily_report_submitted`, au lieu d'une vraie clé — `notif_daily_report_submitted_body` n'existait nulle part, donc cette notification s'affichait toujours en français, quelle que soit la langue du destinataire. Utilise désormais cette clé, ajoutée côté Kintai Core. Au passage, `submit()`/`validate()` enrichissent aussi le corps (date du rapport, magasin) et renvoient au clic vers la page du rapport concerné (`/admin/stores/{id}/daily-reports/{rid}`) — nécessite la version de Kintai Core introduisant le paramètre `$link` sur `notify()`/`notifyMany()`.
- `DailyReportController::indexAll()` — un utilisateur non-Owner dont le rôle accorde `daily_reports.view` en portée globale (case "Toutes les boutiques", `managed_store_ids === null`) voyait le sélecteur de magasin vide et se faisait refuser tout filtre `?store_id=` précis, alors que la liste de rapports elle-même restait visible par coïncidence (convention "tableau vide = pas de filtre" de `findAllActive()`). La méthode traitait `managed_store_ids === null` comme équivalent à `[]` (`?? []`) au lieu de le traiter comme Owner/is_admin — c'est-à-dire "aucune restriction". Traité maintenant de façon uniforme avec `is_admin`.

## [1.0.0] - 2026-09-19

### Added

- Extraction initiale depuis Kintai (`src/Bundles/DailyReport`).
