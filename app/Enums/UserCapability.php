<?php

namespace App\Enums;

/**
 * Central Patrimoine application capabilities.
 *
 * V1.0.3 deliberately uses a small fixed capability vocabulary mapped to
 * the three fixed application roles. This is not a custom-permission system.
 */
enum UserCapability: string
{
    /**
     * Read normal operational application data.
     */
    case ViewOperations = 'view_operations';

    /**
     * Create or modify normal operational business records.
     */
    case ManageOperations = 'manage_operations';

    /**
     * Perform explicit/manual financial operations.
     */
    case ManageFinance = 'manage_finance';

    /**
     * Delete business records where deletion is supported and safe.
     */
    case DeleteRecords = 'delete_records';

    /**
     * View reports/statements and download applicable exports/documents.
     */
    case ExportReports = 'export_reports';

    /**
     * Access and modify Managing Organisation settings.
     */
    case ManageSettings = 'manage_settings';

    /**
     * Administer Patrimoine application users.
     */
    case ManageUsers = 'manage_users';

    /**
     * View/export the V1.0.3 Activity Log.
     */
    case ViewActivityLog = 'view_activity_log';
}
