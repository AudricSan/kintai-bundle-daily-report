<?php

declare(strict_types=1);

namespace kintai\Tests\Bundles\DailyReport\Views;

use PHPUnit\Framework\TestCase;

/**
 * Chaque réglage enregistré par DailyReportController::saveSettings() doit avoir son champ dans le formulaire.
 *
 * Régression d'origine : le formulaire n'avait aucun champ pour l'heure de validation automatique ni pour les heures de
 * rappel. Comme le contrôleur lit ces valeurs dans la requête, chaque enregistrement effaçait l'auto-validation et
 * remettait les rappels à 15:00 et 18:00, sans que personne puisse les régler.
 */
final class DailyReportSettingsFormTest extends TestCase
{
    private string $view;
    private string $controller;

    protected function setUp(): void
    {
        $this->view       = (string) file_get_contents(dirname(__DIR__, 2) . '/Views/daily-report-settings.php');
        $this->controller = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Controllers/Web/DailyReportController.php');
    }

    /** @return list<string> noms des champs lus par saveSettings() */
    private function savedFields(): array
    {
        $start = strpos($this->controller, 'public function saveSettings');
        $this->assertNotFalse($start, 'saveSettings() introuvable');
        $body = substr($this->controller, $start, 3500);

        preg_match_all('/\$data\[\'([a-z_]+)\'\]/', $body, $m);

        return array_values(array_unique($m[1]));
    }

    public function testEverySavedSettingHasAFormField(): void
    {
        $fields = $this->savedFields();
        $this->assertNotEmpty($fields);

        foreach ($fields as $name) {
            $this->assertMatchesRegularExpression(
                '/name="' . preg_quote($name, '/') . '(\[\])?"/',
                $this->view,
                "Le réglage « $name » est enregistré par le contrôleur mais n'a aucun champ dans le formulaire."
            );
        }
    }

    public function testTheAutoValidationAndReminderTimesAreEditable(): void
    {
        foreach (['auto_validate_time', 'reminder_time', 'no_report_time'] as $name) {
            $this->assertStringContainsString('name="' . $name . '"', $this->view);
            $this->assertMatchesRegularExpression('/type="time"[^>]*name="' . $name . '"/s', $this->view);
        }
    }
}
