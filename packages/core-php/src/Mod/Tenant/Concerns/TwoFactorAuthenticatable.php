<?php

declare(strict_types=1);

namespace Core\Mod\Tenant\Concerns;

use Core\Mod\Tenant\Models\UserTwoFactorAuth;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait for two-factor authentication support.
 *
 * This is a native implementation replacing Mixpost's 2FA.
 * Currently stubbed - full implementation to follow.
 */
trait TwoFactorAuthenticatable
{
    /**
     * Get the user's two-factor authentication record.
     */
    public function twoFactorAuth(): HasOne
    {
        return $this->hasOne(UserTwoFactorAuth::class, 'user_id');
    }

    /**
     * Check if two-factor authentication is enabled.
     */
    public function hasTwoFactorAuthEnabled(): bool
    {
        if ($this->twoFactorAuth) {
            return ! is_null($this->twoFactorAuth->secret_key)
                && ! is_null($this->twoFactorAuth->confirmed_at);
        }

        return false;
    }

    /**
     * Get the two-factor authentication secret key.
     */
    public function twoFactorAuthSecretKey(): ?string
    {
        return $this->twoFactorAuth?->secret_key;
    }

    /**
     * Get the two-factor recovery codes.
     */
    public function twoFactorRecoveryCodes(): array
    {
        return $this->twoFactorAuth?->recovery_codes?->toArray() ?? [];
    }

    /**
     * Replace a used recovery code with a new one.
     */
    public function twoFactorReplaceRecoveryCode(string $code): void
    {
        if (! $this->twoFactorAuth) {
            return;
        }

        $codes = $this->twoFactorRecoveryCodes();
        $index = array_search($code, $codes);

        if ($index !== false) {
            $codes[$index] = $this->generateRecoveryCode();
            $this->twoFactorAuth->update(['recovery_codes' => $codes]);
        }
    }

    /**
     * Generate a QR code SVG for two-factor setup.
     */
    public function twoFactorQrCodeSvg(): string
    {
        // Stub - will implement with bacon/bacon-qr-code
        return '';
    }

    /**
     * Generate the TOTP URL for QR code.
     */
    public function twoFactorQrCodeUrl(): string
    {
        $appName = rawurlencode(config('app.name'));
        $email = rawurlencode($this->email);
        $secret = $this->twoFactorAuthSecretKey();

        return "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";
    }

    /**
     * Generate a random recovery code.
     */
    protected function generateRecoveryCode(): string
    {
        return strtoupper(bin2hex(random_bytes(5))).'-'.strtoupper(bin2hex(random_bytes(5)));
    }
}
