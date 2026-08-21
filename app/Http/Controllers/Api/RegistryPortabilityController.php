<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\DataPortability\RegistryExportService;
use App\Services\DataPortability\RegistryImportService;
use App\Services\DataPortability\SpreadsheetReader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * V1.0.7 Administrator-only Registry backup and restore endpoints.
 *
 * Export produces a machine-restorable backup of the Registry (parties,
 * buildings + ownership, units, leases). Import restores such a backup
 * idempotently after an accidental deletion.
 *
 * Financial history (payments, invoices, journal, funds) is intentionally
 * excluded — the Financial Journal is immutable and is never restored
 * from a spreadsheet.
 *
 * Both directions are meaningful human actions and each records exactly
 * one Activity Log event.
 */
class RegistryPortabilityController extends Controller
{
    /**
     * How many rows the PDF rendering shows before truncating.
     *
     * The PDF exists for eyeballing a backup, not for restoring it —
     * CSV/XLSX are the restore formats. Very large registries therefore
     * cap at this many rows with a "+N more rows" line instead of
     * producing an unusable multi-hundred-page document.
     */
    private const PDF_ROW_CAP = 500;

    /**
     * Export one Registry entity set as CSV or XLSX.
     *
     * GET /api/registry/export?entity=parties|buildings|units|leases&format=csv|xlsx
     */
    public function export(
        Request $request,
        RegistryExportService $export,
        ActivityLogService $activityLog
    ): Response {
        $validated =
            $request->validate([
                'entity' => [
                    'required',
                    Rule::in(
                        RegistryExportService::ENTITIES
                    ),
                ],

                'format' => [
                    'required',
                    Rule::in([
                        'csv',
                        'xlsx',
                    ]),
                ],
            ]);

        $entity =
            $validated['entity'];

        $format =
            $validated['format'];

        $contents =
            $format === 'csv'
                ? $export->csv($entity)
                : $export->xlsx($entity);

        $activityLog->record(
            action: 'registry.exported',
            request: $request,
            entityType: 'registry',
            entityLabel: 'Registry',
            metadata: [
                'entity' => $entity,
                'format' => $format,
                'record_count' => count(
                    $export->rows($entity)
                ),
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    $format === 'csv'
                        ? 'text/csv; charset=UTF-8'
                        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' =>
                    'attachment; filename="registry-'
                    .$entity
                    .'.'
                    .$format
                    .'"',
            ]
        );
    }

    /**
     * Export the complete Registry backup: one XLSX workbook with one
     * sheet per entity set.
     *
     * GET /api/registry/export/full
     */
    public function exportFull(
        Request $request,
        RegistryExportService $export,
        ActivityLogService $activityLog
    ): Response {
        $contents =
            $export->xlsxAll();

        $counts = [];

        foreach (
            RegistryExportService::ENTITIES
            as $entity
        ) {
            $counts[$entity] =
                count(
                    $export->rows($entity)
                );
        }

        $activityLog->record(
            action: 'registry.exported',
            request: $request,
            entityType: 'registry',
            entityLabel: 'Registry',
            metadata: [
                'entity' => 'all',
                'format' => 'xlsx',
                'record_counts' => $counts,
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' =>
                    'attachment; filename="registry-full.xlsx"',
            ]
        );
    }

    /**
     * Render one Registry entity set as a simple tabular PDF.
     *
     * The PDF is for human review only. It may truncate at PDF_ROW_CAP
     * rows; the CSV/XLSX exports remain the authoritative restore
     * formats and are never truncated.
     *
     * GET /api/registry/export/pdf?entity=...
     */
    public function exportPdf(
        Request $request,
        RegistryExportService $export,
        ActivityLogService $activityLog
    ): Response {
        $validated =
            $request->validate([
                'entity' => [
                    'required',
                    Rule::in(
                        RegistryExportService::ENTITIES
                    ),
                ],
            ]);

        $entity =
            $validated['entity'];

        $rows =
            $export->rows(
                $entity
            );

        $totalRowCount =
            count($rows);

        $contents =
            Pdf::loadView(
                'registry.export',
                [
                    'entity' =>
                        $entity,

                    'columns' =>
                        $export->columns(
                            $entity
                        ),

                    'rows' =>
                        array_slice(
                            $rows,
                            0,
                            self::PDF_ROW_CAP
                        ),

                    'totalRowCount' =>
                        $totalRowCount,

                    'generatedAt' =>
                        now()->format(
                            'd-m-Y H:i:s'
                        ),
                ]
            )
                ->setPaper(
                    'a4',
                    'landscape'
                )
                ->output();

        $activityLog->record(
            action: 'registry.exported',
            request: $request,
            entityType: 'registry',
            entityLabel: 'Registry',
            metadata: [
                'entity' => $entity,
                'format' => 'pdf',
                'record_count' => $totalRowCount,
            ],
        );

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'application/pdf',

                'Content-Disposition' =>
                    'attachment; filename="registry-'
                    .$entity
                    .'.pdf"',
            ]
        );
    }

    /**
     * Restore one Registry entity set from an uploaded backup file.
     *
     * POST /api/registry/import
     * multipart: file + entity + dry_run (boolean)
     *
     * Import is idempotent: re-importing the same file reports every row
     * as unchanged. A dry run executes the full import inside a rolled
     * back transaction and returns the counts a real run would produce.
     */
    public function import(
        Request $request,
        SpreadsheetReader $reader,
        RegistryImportService $import,
        ActivityLogService $activityLog
    ): JsonResponse {
        $validated =
            $request->validate([
                'file' => [
                    'required',
                    'file',
                ],

                'entity' => [
                    'required',
                    Rule::in(
                        RegistryExportService::ENTITIES
                    ),
                ],

                'dry_run' => [
                    'sometimes',
                    'boolean',
                ],
            ]);

        $entity =
            $validated['entity'];

        $dryRun =
            $request->boolean(
                'dry_run'
            );

        try {
            $rows =
                $reader->rows(
                    $request->file('file'),
                    $entity
                );

            $result =
                $import->import(
                    $entity,
                    $rows,
                    $dryRun
                );
        } catch (RuntimeException $exception) {
            /*
             * Structural problems (unreadable file, missing required
             * columns) surface as normal 422 validation feedback.
             */
            throw ValidationException::withMessages([
                'file' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        /*
         * Recorded AFTER the import transaction so the event survives a
         * dry run's rollback — a dry run is still a meaningful human
         * action worth auditing.
         */
        $activityLog->record(
            action: 'registry.imported',
            request: $request,
            entityType: 'registry',
            entityLabel: 'Registry',
            metadata: [
                'entity' => $entity,

                'counts' => [
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'unchanged' => $result['unchanged'],
                    'skipped' => count($result['skipped']),
                ],

                'dry_run' => $dryRun,
            ],
        );

        return response()->json([
            'entity' => $entity,
            'dry_run' => $dryRun,
            'created' => $result['created'],
            'updated' => $result['updated'],
            'unchanged' => $result['unchanged'],
            'skipped' => $result['skipped'],
        ]);
    }

    /**
     * V1.0.7 full restore: import a complete multi-sheet backup workbook
     * in ONE request, running parties → buildings → units → leases on a
     * single import-service instance so recreated records' old→new id
     * remapping carries across the entities (the map is per instance —
     * per-entity requests cannot restore cross-entity references).
     *
     * POST /api/registry/import/full  (multipart: file, dry_run?)
     */
    public function importFull(
        Request $request,
        SpreadsheetReader $reader,
        RegistryImportService $import,
        ActivityLogService $activityLog
    ): JsonResponse {
        $request->validate([
            'file' => [
                'required',
                'file',
            ],

            'dry_run' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $dryRun =
            $request->boolean(
                'dry_run'
            );

        $results = [];

        try {
            /*
             * Restore order matters: referenced entities first, so the
             * id map is populated before dependants are imported.
             */
            foreach (
                RegistryExportService::ENTITIES as $entity
            ) {
                $rows =
                    $reader->rows(
                        $request->file('file'),
                        $entity
                    );

                $results[$entity] =
                    $import->import(
                        $entity,
                        $rows,
                        $dryRun
                    );
            }
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'file' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        $activityLog->record(
            action: 'registry.imported',
            request: $request,
            entityType: 'registry',
            entityLabel: 'Registry',
            metadata: [
                'entity' => 'full',

                'counts' => collect($results)->map(
                    static fn (array $result): array => [
                        'created' => $result['created'],
                        'updated' => $result['updated'],
                        'unchanged' => $result['unchanged'],
                        'skipped' => count($result['skipped']),
                    ]
                )->all(),

                'dry_run' => $dryRun,
            ],
        );

        return response()->json([
            'dry_run' => $dryRun,
            'results' => $results,
        ]);
    }
}
