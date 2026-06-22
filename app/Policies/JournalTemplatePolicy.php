<?php

namespace App\Policies;

use App\Models\JournalTemplate;
use App\Models\User;

class JournalTemplatePolicy
{
    public function delete(User $user, JournalTemplate $journalTemplate): bool
    {
        return $journalTemplate->user_id === $user->id;
    }
}
