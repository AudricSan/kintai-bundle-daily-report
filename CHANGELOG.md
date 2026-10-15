# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

### Changed

- Test `DailyReportMassAssignmentTest` repris du Core (affectation de masse à la création par l'API : identifiant ignoré, auteur et brouillon imposés, validation refusée), retiré du Core avec l'extraction. Aucun changement fonctionnel.
- Extraction du Core (étape 4 du plan du contrat) : le bundle possède désormais tout son code et ses données. Le modèle `DailyReport`, le dépôt `DailyReportRepositoryInterface` / `DatabaseDailyReportRepository` et les services (`DailyReportPermissionService`, `DailyReportPdfService`, `DailyReportMailService`, `DailyReportAutoValidateService`, `DailyReportDataNormalizer`) quittent le Core et sont liés par le bundle ; la table `daily_reports` garde son nom et ses données, et une migration du bundle la crée si elle manque (installation neuve), au schéma actuel. Nouvelles déclarations : tâche planifiée `auto-validate` (`cronJobs()`, lancée par `/cron/run/auto-validate` ou `php scripts/cron.php auto-validate`), types de notification `daily_report_submitted` / `daily_report_validated` (`notificationTypes()`) et ventes journalières (`salesProviders()`, `Providers/DailyReportSales`), lues par la rentabilité du Core et par salary-report. `"kintai_contract": "^1.16"` (envoi d'e-mails et police japonaise des PDF par le contrat). Les libellés de la catégorie de permissions, de la fonctionnalité, des notifications, du journal d'activité, du courriel et des erreurs passent dans `lang/`. `ContractUsageTest` n'admet plus aucune classe du Core hors contrat ; `ContractDeclarationsTest` vérifie chaque déclaration valeur par valeur. Tests repris du Core (dépôt, services, tâche auto-validate) ; nouveaux tests `DailyReportSalesTest` et `DailyReportsMigrationTest`.
- Contrat de bundle versionné : le bundle n'utilise plus que les noms du contrat `kintai\Contract\…` du Core (requête, réponse, routes, middlewares, exceptions, droits, données du Core, services, vues et composants) et déclare `"kintai_contract": "^1.0"` dans `bundle.json` (`kintai_core.min` relevé à `0.3.6`, première version du Core qui fournit le contrat). Nouveau test `ContractUsageTest` : toute utilisation du Core hors contrat échoue, hormis 4 classe(s) propre(s) au bundle qui vivent encore dans le Core (extraction prévue).

### Added

- Libellé de la tâche planifiée `auto-validate` (`cron_job_auto_validate`, fr/en/ja), affiché par `php scripts/cron.php` depuis le contrat 1.20 (convention de traduction ; sans la clé, le nom de la tâche s'affiche, aucune contrainte de version à relever).
- Contrat de bundle 1.11 : le bundle déclare lui-même ce qu'il apporte au Core (`navigation()`, `permissions()`, `storeFeatures()`, `viewVariables()`) au lieu que le Core le connaisse à l'avance ; déclarations identiques à ce que le Core affichait pour ce bundle, rien ne change à l'écran. `bundle.json` demande désormais `"kintai_contract": "^1.11"` (Kintai Core avec les points d'extension et fournisseurs du contrat). Nouveau test `ContractDeclarationsTest` : chaque déclaration est comparée au repli historique du Core.
- Socle de tests PHPUnit (composer.json, phpunit.xml) et CI qui l'exécute contre le vrai Core : les tests que le Core faisait tourner sur une copie figée du bundle (fixtures 1.0.0) vivent désormais ici et testent le code réel.

### Fixed

- Sécurité — `POST`/`PUT /api/v1/daily-reports` fusionnaient le JSON brut du client dans `save()` (upsert dès qu'un `id` est présent) : un `id` écrasait le rapport de quelqu'un d'autre en contournant `canEdit()`, `author_id` était pris du client (un rapport pouvait être attribué à un tiers) et `PUT` laissait modifier `store_id`, `status`, la validation ou le PDF. Seuls `report_date`, `sales_total`, `customer_count`, `labor_cost`, `waste_total`, `notes` et `data` sont lus ; l'auteur est l'appelant, le statut initial est `draft`, et les transitions passent par `submit`/`validate`.

### Changed

- Liste des rapports journaliers (`/admin/daily-reports`) : la vue par défaut est désormais **par jour** au lieu de par magasin. Le choix fait avec le bouton de bascule reste mémorisé dans le navigateur. La lecture de ce choix est protégée : en navigation privée ou avec un stockage bloqué, `localStorage` levait une erreur et les deux vues restaient affichées l'une sous l'autre.

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
