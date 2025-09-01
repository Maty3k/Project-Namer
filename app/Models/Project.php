<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'user_id',
        'selected_name_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Project $project) {
            if (empty($project->uuid)) {
                $project->uuid = (string) Str::uuid();
            }

            if (empty($project->name)) {
                $project->name = 'Project '.now()->format('Y-m-d H:i');
            }
        });
    }

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all name suggestions for the project.
     */
    public function nameSuggestions(): HasMany
    {
        return $this->hasMany(NameSuggestion::class);
    }

    /**
     * Get the selected name for the project.
     */
    public function selectedName(): BelongsTo
    {
        return $this->belongsTo(NameSuggestion::class, 'selected_name_id');
    }

    /**
     * Get visible name suggestions for the project.
     */
    public function visibleNameSuggestions(): HasMany
    {
        return $this->hasMany(NameSuggestion::class)->where('is_hidden', false);
    }
}
