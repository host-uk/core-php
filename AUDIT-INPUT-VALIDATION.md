# Input Validation Security Audit

This document outlines the findings of a security audit focused on input validation and sanitization within the application.

## 1. Input Entry Points Inventory

This section inventories the locations where untrusted input enters the system.

- **URL Parameters (Route Parameters):**
  - **`api.php`:**
    - `GET /stats/{provider}`: The `provider` parameter is passed to `TreeStatsController@provider`.
    - `GET /stats/{provider}/{model}`: The `provider` and `model` parameters are passed to `TreeStatsController@model`.
  - **`web.php`:**
    - `GET /ref/{provider}/{model?}`: The `provider` and optional `model` parameters are passed to `ReferralController@track`.

- **Query Parameters (`$_GET`):**
  - Sanitized by `Core\Input\Input::capture()`, which applies the `Core\Input\Sanitiser` to the `$_GET` superglobal.

- **Form Submissions (`$_POST`):**
  - Sanitized by `Core\Input\Input::capture()`, which applies the `Core\Input\Sanitiser` to the `$_POST` superglobal.

- **Cookies (`$_COOKIE`):**
  - Not currently sanitized by the `Core\Input\Input` class.

- **Server Variables (`$_SERVER`):**
  - Not currently sanitized. Headers and other server variables are used by the framework but not explicitly sanitized by the application's `Input` class.

- **Request Body (JSON/XML):**
  - The `Input` class does not currently handle `php://input` for `PUT`, `PATCH`, `DELETE` requests.

## 2. Validation Gaps Found

- **Route Parameters Bypass Sanitization:** The primary validation gap is that URL parameters from the routing system are not passed through the `Core\Input\Sanitiser`. These values are passed directly to controller methods.

- **Incomplete Superglobal Coverage:** The current sanitization only covers `$_GET` and `$_POST`. Other sources of input like `$_COOKIE`, `$_SERVER`, and the request body for non-POST requests are not sanitized.

- **Allowlist vs. Denylist:** The `Sanitiser` class primarily uses a denylist approach (stripping control characters) with some allowlist presets. A consistent allowlist approach is preferred for stronger security.

## 3. Injection Vectors Discovered

- **API Endpoint:** `GET /api/trees/stats/{provider}/{model}`
- **Controller:** `Core\Mod\Trees\Controllers\Api\TreeStatsController`
- **Method:** `model(string $provider, string $model)`

### Vulnerability Details

The `provider` and `model` parameters from the URL are passed directly into the `model` method of the `TreeStatsController`.

- **`$provider`:** This parameter undergoes a basic validation check using `TreePlanting::isValidProvider($provider)`. This is a good use of an allowlist. However, the raw, unsanitized string is still used in the database query via the `byProvider($provider)` scope.

- **`$model`:** This parameter has **no validation or sanitization**. The raw value from the URL is passed directly to the `byModel($model)` scope in the `TreePlanting` model.

While Laravel's Eloquent ORM uses parameter binding, which mitigates traditional SQL injection, passing unsanitized user input directly into database queries is a significant security risk. This could lead to:

- **Information Disclosure:** An attacker could potentially craft inputs to manipulate the query's behavior and infer information from the database based on the response (e.g., timing attacks, error messages).
- **Future Vulnerabilities:** If the underlying query logic were to change to use raw SQL expressions, this would immediately become a critical SQL injection vulnerability.

The `ReferralController@track` method also accepts unsanitized `provider` and `model` parameters, but since the controller's code is missing, the exact risk cannot be determined. However, the pattern of accepting unsanitized URL parameters is consistent.

## 4. Remediation Recommendations

### Recommendation 1: Use Middleware for Route Parameter Sanitization

For global sanitization of route parameters, a middleware is the most effective approach. This ensures that any parameter from the URL is cleaned before it reaches the controller.

**Example:** Create a `SanitizeRouteParameters` middleware.

```php
// app/Http/Middleware/SanitizeRouteParameters.php (new file)
namespace App\Http\Middleware;

use Closure;
use Core\Input\Sanitiser;

class SanitizeRouteParameters
{
    public function handle($request, Closure $next)
    {
        $sanitiser = new Sanitiser;
        $routeParameters = $request->route()->parameters();

        $sanitizedParameters = $sanitiser->filter($routeParameters);

        foreach ($sanitizedParameters as $key => $value) {
            $request->route()->setParameter($key, $value);
        }

        return $next($request);
    }
}

// Then, register this middleware in app/Http/Kernel.php in the 'api' or 'web' group.
```

### Recommendation 2: Implement Strict Validation in Controllers

Even with global sanitization, each controller method should validate its inputs. For the `TreeStatsController`, this means enforcing a strict format for the `model` parameter.

**Example:** Add validation for the `$model` parameter in `TreeStatsController`.

```php
// In Core\Mod\Trees\Controllers\Api\TreeStatsController.php

public function model(string $provider, string $model): JsonResponse
{
    // Validate provider (already exists)
    if (! TreePlanting::isValidProvider($provider)) {
        return response()->json([
            'success' => false,
            'error' => "Unknown provider: {$provider}",
            'valid_providers' => TreePlanting::VALID_PROVIDERS,
        ], 404);
    }

    // **NEW:** Add validation for the model parameter
    // Allow only alphanumeric characters, dashes, and dots.
    if (! preg_match('/^[a-zA-Z0-9\-\.]+$/', $model)) {
        return response()->json([
            'success' => false,
            'error' => "Invalid model format: {$model}",
        ], 400);
    }

    // ... rest of the method
}
```

### Recommendation 3: Use Form Requests for Validation

For more complex scenarios, use Laravel's Form Request validation to centralize validation logic and keep controllers clean.

**Example:** Create a `TreeStatsRequest` and use it in the controller.

```php
// app/Http/Requests/TreeStatsRequest.php (new file)
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Core\Mod\Trees\Models\TreePlanting;

class TreeStatsRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Public API
    }

    public function rules()
    {
        return [
            'provider' => ['required', 'in:' . implode(',', TreePlanting::VALID_PROVIDERS)],
            'model' => ['sometimes', 'required', 'regex:/^[a-zA-Z0-9\-\.]+$/'],
        ];
    }
}

// In Core\Mod\Trees\Controllers\Api\TreeStatsController.php
public function model(TreeStatsRequest $request, string $provider, string $model): JsonResponse
{
    // Validation is now handled automatically by TreeStatsRequest
    // ... rest of the method
}
```
