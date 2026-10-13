<?php

namespace App\Models;

use App\Enums\AiProvider;
use Database\Factories\AiSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One saved configuration per AI provider; at most one is active at a time.
 *
 * @property int $id
 * @property AiProvider $provider
 * @property string|null $model
 * @property string|null $api_key
 * @property string|null $base_url
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'model', 'api_key', 'base_url', 'is_active'])]
#[Hidden(['api_key'])]
class AiSetting extends Model
{
    /** @use HasFactory<AiSettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AiProvider::class,
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The provider configuration currently used for AI features, if any.
     */
    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }

    /**
     * Make this provider the active one and deactivate all others.
     */
    public function activate(): void
    {
        static::query()->whereKeyNot($this->getKey())->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    /**
     * The base URL requests should go to, falling back to the provider default.
     */
    public function resolvedBaseUrl(): string
    {
        return rtrim($this->base_url ?: $this->provider->defaultBaseUrl(), '/');
    }

    /**
     * The model requests should use, falling back to the provider default.
     */
    public function resolvedModel(): string
    {
        return $this->model ?: $this->provider->defaultModel();
    }
}
