<?php

declare(strict_types=1);

namespace kintai\Bundles\Installed\DailyReport;

use kintai\Contract\Auth\AuthService;
use kintai\Contract\Bundle;
use kintai\Contract\Container;
use kintai\Contract\Data\StoreRepositoryInterface;
use kintai\Contract\Data\StoreUserRepositoryInterface;
use kintai\Contract\Extension\NavItem;
use kintai\Contract\Extension\StoreFeature;
use kintai\Core\Services\DailyReportPermissionService;

/**
 * Le repository et les services métier (permission, PDF, mail, auto-validate)
 * sont liés par Kintai Core (RepositoryServiceProvider/AppServiceProvider), pas
 * par ce bundle : plusieurs composants Core (DailyReportNavMiddleware,
 * AutoValidateJob/CronController) en dépendent directement et doivent
 * continuer de fonctionner même si ce bundle est désactivé ou désinstallé —
 * même exception que Timeoff/ShiftSwap/Timeclock (voir docs/architecture.md
 * "Modular Bundles" côté Kintai).
 */
final class DailyReportBundle extends Bundle
{
    public function getName(): string
    {
        return 'daily-report';
    }

    public function getVersion(): string
    {
        return '1.2.0';
    }

    public function getLabel(): string
    {
        return __('bundle_daily_report');
    }

    public function getDescription(): string
    {
        return __('bundle_daily_report_desc');
    }

    public function register(): void
    {
        $this->loadViewsFrom($this->getPath() . '/Views', 'daily-report');
        $this->loadRoutesFrom($this->getPath() . '/routes.php');
        $this->loadAssetsFrom('public');
    }

    /**
     * Navigation (contrat 1.1) : entrée(s) de la barre du haut et de la barre mobile.
     */
    public function navigation(): array
    {
        // Avec la permission globale : un lien vers tous les rapports ; sinon, un lien par magasin où l'utilisateur
        // rédige les rapports ($daily_report_staff_stores, voir viewVariables()).
        return [
            new NavItem('daily_reports', NavItem::REPORTS, 'daily_reports', 'admin.daily_reports.all', 'admin.daily_reports.all',
                icon: 'chart', storeFeature: 'daily_reports', order: 30, mobile: true,
                links: static function (array $view, bool $isManager): array {
                    $path = (string) ($view['path'] ?? '');
                    if (($view['route_visible'])('admin.daily_reports.all')) {
                        return [['href' => ($view['route_url'])('admin.daily_reports.all'), 'label' => __('daily_reports'), 'active' => str_contains($path, '/daily-reports')]];
                    }
                    $stores = (array) ($view['daily_report_staff_stores'] ?? []);
                    $links = [];
                    foreach ($stores as $store) {
                        $id = (int) ($store['id'] ?? 0);
                        $links[] = [
                            'href'   => ($view['route_url'])('admin.stores') . '/' . $id . '/daily-reports',
                            'label'  => __('daily_reports') . (count($stores) > 1 ? ' — ' . (string) ($store['name'] ?? '') : ''),
                            'active' => str_contains($path, '/stores/' . $id . '/daily-reports'),
                        ];
                    }
                    return $links;
                }),
        ];
    }

    /**
     * Catégories de permissions (contrat 1.2), proposées sur la page des rôles.
     */
    public function permissions(): array
    {
        return ['daily_reports' => ['view', 'create', 'update', 'submit', 'approve', 'delete']];
    }

    /**
     * Fonctionnalité activable par magasin (contrat 1.3), sur la fiche magasin.
     */
    public function storeFeatures(): array
    {
        return [new StoreFeature('daily_reports', 'feature_daily_reports', 'feature_daily_reports_desc', '📊', 70)];
    }

    /**
     * Variables partagées avec les vues des pages authentifiées (contrat 1.5).
     */
    public function viewVariables(): array
    {
        return [
            // Magasins où l'employé rédige les rapports journaliers (liens de la navigation) ; vide pour l'Owner et les
            // gérants, qui ont leur propre lien.
            'daily_report_staff_stores' => static function (array $user): array {
                $container = Container::getInstance();
                if (!empty($user['is_admin']) || $container->make(AuthService::class)->isManager()) {
                    return [];
                }
                $stores = $container->make(StoreRepositoryInterface::class);
                $permissions = $container->make(DailyReportPermissionService::class);
                $accessible = [];
                foreach ($container->make(StoreUserRepositoryInterface::class)->findByUser((int) $user['id']) as $membership) {
                    $store = $stores->findById((int) $membership['store_id']);
                    if ($store !== null && $permissions->canCreate($user, $store, $membership)) {
                        $accessible[] = $store;
                    }
                }
                return $accessible;
            },
        ];
    }
}
