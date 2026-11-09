<?php

namespace App\Models\Talents\Concerns;

use App\Models\Talents\JournalActivite;

/**
 * Enregistre automatiquement création / modification / suppression dans le journal d'activité.
 */
trait Journalise
{
    public static function bootJournalise(): void
    {
        static::created(fn ($model) => $model->journaliser('création'));
        static::updated(fn ($model) => $model->journaliser('modification', $model->getChanges()));
        static::deleted(fn ($model) => $model->journaliser('suppression'));
    }

    public function journaliser(string $action, ?array $modifications = null): void
    {
        unset($modifications['updated_at']);

        JournalActivite::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => class_basename($this),
            'objet_id' => $this->getKey(),
            'libelle' => $this->libelleJournal(),
            'modifications' => $modifications ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    public function libelleJournal(): string
    {
        return method_exists($this, 'talent') && $this->talent
            ? $this->talent->nom_complet
            : (string) ($this->nom ?? $this->getKey());
    }
}
