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
}
