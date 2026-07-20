<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AzureGroupRoleMapping;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class AzureController extends Controller
{
    public function redirectToAzure(): RedirectResponse
    {
        return Socialite::driver('microsoft')
            ->scopes(['User.Read', 'GroupMember.Read.All'])
            ->redirect();
    }

    public function handleAzureCallback(): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('microsoft')->user();
        } catch (Exception $e) {
            return redirect('/login')->with('error', 'Azure authentication failed.');
        }

        // Check for specific tenant if AZURE_TENANT_ID is set and not 'common'
        $tenantId = config('services.microsoft.tenant');
        if ($tenantId && $tenantId !== 'common' && $tenantId !== 'organizations' && $tenantId !== 'consumers') {
            // Socialite azure provider might not directly expose tenant id in the user object easily without extra calls or parsing token
            // but the provider itself handles it if configured.
        }

        $user = User::where('azure_id', $azureUser->id)
            ->orWhere('email', $azureUser->email)
            ->first();

        if (! $user) {
            $user = User::create([
                'azure_id' => $azureUser->id,
                'firstname' => $azureUser->offsetGet('givenName') ?? $azureUser->name,
                'lastname' => $azureUser->offsetGet('surname') ?? '',
                'scoutname' => explode(' / ', $azureUser->name)[1] ?? '',
                'email' => $azureUser->email,
                'password' => null,
            ]);
        } else {
            $user->update([
                'azure_id' => $azureUser->id,
                'firstname' => $azureUser->offsetGet('givenName') ?? $azureUser->name,
                'lastname' => $azureUser->offsetGet('surname') ?? '',
                'scoutname' => explode(' / ', $azureUser->name)[1] ?? '',
                'email' => $azureUser->email,
            ]);
        }

        $this->syncRoles($user, $azureUser->token);

        Auth::login($user);

        return redirect()->intended(route('overwatch'));
    }

    protected function syncRoles(User $user, string $token)
    {
        // Get user groups from Microsoft Graph
        $response = Http::withToken($token)
            ->get('https://graph.microsoft.com/v1.0/me/memberOf?$select=id');

        if ($response->successful()) {
            $groups = collect($response->json('value'))
                ->pluck('id')
                ->toArray();

            $rolesToAssign = AzureGroupRoleMapping::whereIn('azure_group_id', $groups)
                ->pluck('role_name')
                ->toArray();

            if (! empty($rolesToAssign)) {
                $user->syncRoles($rolesToAssign);
            }
        }
    }
}
