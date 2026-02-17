<?php

namespace App\Services\Lead;

class LeadPaymentService
{
    public function shouldMarkPaid(array $payload = []): bool
    {
        if (array_key_exists('paid', $payload)) {
            return filter_var($payload['paid'], FILTER_VALIDATE_BOOLEAN);
        }

        $subscriptionActive = filter_var(data_get($payload, 'subscription.active'), FILTER_VALIDATE_BOOLEAN);
        if ($subscriptionActive) {
            return true;
        }

        $paymentStatus = strtolower((string) data_get($payload, 'payment.status'));
        if (in_array($paymentStatus, ['paid', 'success', 'completed'], true)) {
            return true;
        }

        $amount = (float) data_get($payload, 'payment.amount', 0);
        return $amount > 0 && in_array($paymentStatus, ['captured', 'charged'], true);
    }
}

