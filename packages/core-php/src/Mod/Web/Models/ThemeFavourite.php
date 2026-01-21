<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThemeFavourite Model
 *
 * Represents a user's favourite theme.
 */
class ThemeFavourite extends Model
{
    use HasFactory;

    protected $table = 'theme_favourites';

    protected $fillable = [
        'user_id',
        'theme_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who favourited the theme.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the theme that was favourited.
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    /**
     * Toggle favourite for a user and theme.
     *
     * Returns true if favourited, false if unfavourited.
     */
    public static function toggle(User $user, int $themeId): bool
    {
        $favourite = static::where('user_id', $user->id)
            ->where('theme_id', $themeId)
            ->first();

        if ($favourite) {
            $favourite->delete();

            return false;
        }

        static::create([
            'user_id' => $user->id,
            'theme_id' => $themeId,
        ]);

        return true;
    }

    /**
     * Check if a user has favourited a theme.
     */
    public static function isFavourited(User $user, int $themeId): bool
    {
        return static::where('user_id', $user->id)
            ->where('theme_id', $themeId)
            ->exists();
    }
}
