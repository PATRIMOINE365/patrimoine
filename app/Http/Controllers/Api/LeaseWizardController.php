<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaseWizardRequest;
use App\Services\LeaseWizardService;
use Illuminate\Http\JsonResponse;

/**
 * V1.0.29 guided lease creation.
 *
 * One endpoint for the whole wizard. It exists because the wizard writes
 * nothing until its last page: the property, the owners, the tenant, the
 * agent and the lease arrive together and are created together, or not at
 * all. Everything it does is done by LeaseWizardService inside a single
 * transaction.
 */
class LeaseWizardController extends Controller
{
    /**
     * Create the property, the parties and the lease in one go.
     */
    public function store(
        StoreLeaseWizardRequest $request,
        LeaseWizardService $wizard
    ): JsonResponse {
        $lease = $wizard->create(
            $request->validated(),
            $request
        );

        return response()->json(
            data: $lease->load([
                'unit.building.ownerships.party',
                'tenant',
                'agent',
                'invoices',
                'tenantFundAccounts',
            ]),
            status: 201
        );
    }
}
