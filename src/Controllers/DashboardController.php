<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DashboardRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $repo = new DashboardRepository();
        $threshold = (float) config('app.mite_threshold');
        $overdueDays = (int) config('app.inspection_overdue_days');

        $miteByApiary = $repo->miteLoadByApiaryByMonth();
        $apiaries = array_values(array_unique(array_column($miteByApiary, 'apiary_name')));
        $months = array_values(array_unique(array_column($miteByApiary, 'month')));
        sort($months);
        $byApiaryMonth = [];
        foreach ($miteByApiary as $row) {
            $byApiaryMonth[$row['apiary_name']][$row['month']] = round((float) $row['avg_mite_load'], 2);
        }

        $colonySpread = $repo->colonyStrengthSpread();
        $contractsFilled = $repo->contractsFilledVsRequired();

        $this->view('dashboard/index', [
            'tiles' => [
                'activeHives' => $repo->activeHiveCount(),
                'overThreshold' => $repo->hivesOverMiteThreshold($threshold),
                'overdueInspections' => $repo->hivesNotInspectedWithin($overdueDays),
                'activeContracts' => $repo->activeContractCount(),
                'shortfall60' => $repo->hiveShortfallNextDays(60),
            ],
            'miteAlerts' => $repo->miteAlerts($threshold),
            'overdueInspectionList' => $repo->overdueInspections($overdueDays),
            'overdueTreatments' => $repo->overdueTreatments(),
            'mitesChartLabels' => $months,
            'mitesChartDatasets' => array_values(array_map(static function ($apiaryName, $i) use ($byApiaryMonth, $months) {
                // Cycle through the theme's own color tokens, per the brief's
                // "use only these tokens ... in chart settings" rule.
                $palette = ['#B8860B', '#7A5C00', '#5C4500', '#A3261B', '#2F6B2F', '#5F5747'];
                $color = $palette[$i % count($palette)];

                return [
                    'label' => $apiaryName,
                    'data' => array_map(static fn ($m) => $byApiaryMonth[$apiaryName][$m] ?? null, $months),
                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'spanGaps' => true,
                    'tension' => 0.25,
                ];
            }, $apiaries, array_keys($apiaries))),
            'colonyLabels' => ['0-4', '5-8', '9-12', '13-16', '17+'],
            'colonyValues' => $colonySpread,
            'contractLabels' => array_column($contractsFilled, 'label'),
            'contractRequired' => array_column($contractsFilled, 'required'),
            'contractPlaced' => array_column($contractsFilled, 'placed'),
            'extraScripts' => ['/assets/vendor/chart.umd.min.js'],
        ]);
    }
}
