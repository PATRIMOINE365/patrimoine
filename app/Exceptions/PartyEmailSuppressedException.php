<?php

namespace App\Exceptions;

use App\Models\Party;
use RuntimeException;

/**
 * Thrown when Patrimoine declines to email a Party because the
 * organisation has switched party emails off, or because that Party is
 * individually excluded.
 *
 * It extends RuntimeException on purpose: every controller that already
 * sends or resends a document catches RuntimeException and answers 422
 * with the exception message, so a suppressed send tells the operator why
 * nothing left the building without any new plumbing.
 *
 * Nothing about the underlying operation fails. The invoice is still
 * issued, the payment still recorded, the voucher still downloadable —
 * only the message is withheld.
 */
class PartyEmailSuppressedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?Party $party = null,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($message);
    }
}
