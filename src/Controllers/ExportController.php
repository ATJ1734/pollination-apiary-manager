<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ApiaryRepository;
use App\Repositories\ContractRepository;
use App\Repositories\GrowerRepository;
use App\Repositories\HiveRepository;
use App\Repositories\InspectionRepository;
use App\Repositories\TreatmentRepository;

/** CSV export for any of the record lists, respecting the same filters as the on-screen list. */
final class ExportController extends Controller
{
    private const MAX_ROWS = 100000; // comfortably above this app's row counts; reuses paginate() with no real paging

    public function csv(array $params): void
    {
        $entity = preg_replace('/\.csv$/', '', (string) $params['entity']);
        [$rows, $columns] = match ($entity) {
            'apiaries' => [$this->apiaryRows(), ['name', 'location_description', 'landowner_name', 'landowner_phone', 'active']],
            'hives' => [$this->hiveRows(), ['hive_code', 'apiary_name', 'status', 'hive_type', 'established_on']],
            'inspections' => [$this->inspectionRows(), ['inspected_on', 'hive_code', 'apiary_name', 'inspector_name', 'frames_of_bees', 'brood_frames', 'queen_seen', 'eggs_seen', 'mites_per_300', 'temperament', 'honey_stores']],
            'treatments' => [$this->treatmentRows(), ['hive_code', 'product', 'applied_on', 'dose', 'remove_by', 'removed_on']],
            'growers' => [$this->growerRows(), ['farm_name', 'contact_name', 'phone', 'email', 'address']],
            'contracts' => [$this->contractRows(), ['farm_name', 'crop', 'season_year', 'start_date', 'end_date', 'hives_required', 'hives_placed', 'min_frames_of_bees', 'rate_per_hive', 'status']],
            default => [null, null],
        };

        if ($rows === null) {
            (new ErrorController())->notFound();
            return;
        }

        if (is_field_tech()) {
            $columns = array_values(array_diff($columns, ['rate_per_hive']));
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$entity}.csv\"");

        $out = fopen('php://output', 'w');
        fputcsv($out, $columns, ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn ($c) => $row[$c] ?? '', $columns), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    private function apiaryRows(): array
    {
        return (new ApiaryRepository())->paginate(['q' => $this->input('q', '')], 1, self::MAX_ROWS)['rows'];
    }

    private function hiveRows(): array
    {
        return (new HiveRepository())->paginateWithApiary([
            'q' => $this->input('q', ''), 'status' => $this->input('status', ''), 'apiary_id' => $this->input('apiary_id', ''),
        ], 1, self::MAX_ROWS)['rows'];
    }

    private function inspectionRows(): array
    {
        return (new InspectionRepository())->paginateWithHive(['q' => $this->input('q', '')], 1, self::MAX_ROWS)['rows'];
    }

    private function treatmentRows(): array
    {
        return (new TreatmentRepository())->paginateWithHive(['q' => $this->input('q', '')], 1, self::MAX_ROWS)['rows'];
    }

    private function growerRows(): array
    {
        return (new GrowerRepository())->paginate(['q' => $this->input('q', '')], 1, self::MAX_ROWS)['rows'];
    }

    private function contractRows(): array
    {
        $rows = (new ContractRepository())->paginateWithGrower([
            'q' => $this->input('q', ''), 'status' => $this->input('status', ''),
        ], 1, self::MAX_ROWS)['rows'];

        foreach ($rows as &$row) {
            $row['hives_placed'] = $row['hives_placed'] ?? 0;
        }

        return $rows;
    }
}
