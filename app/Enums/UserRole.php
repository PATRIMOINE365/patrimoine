<?php

namespace App\Enums;

/**
 * Fixed Patrimoine application roles.
 *
 * V1.0.3 supports exactly one role per authenticated application User.
 *
 * These values are internal identifiers and must not be translated.
 * English/French labels are handled separately in the presentation layer.
 */
enum UserRole: string
{
    /**
     * Full administrative and operational access.
     */
    case Administrator = 'administrator';

    /**
     * Normal operational and financial management access.
     */
    case PropertyManager = 'property_manager';

    /**
     * Read-only operational access with permitted reports and exports.
     */
    case Viewer = 'viewer';

    /**
     * Determine whether this fixed role grants a central application
     * capability.
     *
     * Patrimoine V1.0.3 intentionally has no custom roles or individually
     * configurable permissions. This matrix is therefore the authoritative
     * server-side RBAC definition.
     */
    public function allows(UserCapability $capability): bool
    {
        return match ($this) {
            self::Administrator => true,

            self::PropertyManager => in_array(
                $capability,
                [
                    UserCapability::ViewOperations,
                    UserCapability::ManageOperations,
                    UserCapability::ManageFinance,
                    UserCapability::ExportReports,
                ],
                true
            ),

            self::Viewer => in_array(
                $capability,
                [
                    UserCapability::ViewOperations,
                    UserCapability::ExportReports,
                ],
                true
            ),
        };
    }
}
