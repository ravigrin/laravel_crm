<?php

namespace App\Services\Lead;

use App\Models\Blocklist;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeadValidationService
{
    /**
     * Validate that user exists if userId is provided.
     * 
     * @throws ValidationException
     */
    public function validateUserId(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        if (!User::query()->where('id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'userId' => 'User does not exist',
            ]);
        }
    }

    /**
     * Validate that quiz exists and is not blocked if quizId is provided.
     * 
     * Note: Since we don't have a Quiz model in this codebase, 
     * we check if quiz is blocked via blocklist.
     * 
     * @throws ValidationException
     */
    public function validateQuizId(?int $quizId): void
    {
        if ($quizId === null) {
            return;
        }

        // Check if quiz is blocked in blocklist
        $isBlocked = Blocklist::query()
            ->where('quiz_id', $quizId)
            ->where('type', 'blacklist')
            ->exists();

        if ($isBlocked) {
            throw ValidationException::withMessages([
                'quizId' => 'Quiz is blocked',
            ]);
        }

        // Note: If you have a Quiz model/table, add existence check here:
        // if (!Quiz::query()->where('id', $quizId)->exists()) {
        //     throw ValidationException::withMessages([
        //         'quizId' => 'Quiz does not exist',
        //     ]);
        // }
    }

    /**
     * Validate all required fields for lead creation.
     * 
     * @throws ValidationException
     */
    public function validateLead(Lead $lead): void
    {
        $this->validateUserId($lead->user_id);
        $this->validateQuizId($lead->quiz_id);
    }
}



