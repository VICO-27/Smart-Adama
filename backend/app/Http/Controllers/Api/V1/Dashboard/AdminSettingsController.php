<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = AdminSetting::all()->pluck('value', 'key');

        return response()->json([
            'settings' => $settings
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string'
        ]);

        foreach ($validated['settings'] as $key => $value) {
            AdminSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value]
            );
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => AdminSetting::all()->pluck('value', 'key')
        ]);
    }
}
