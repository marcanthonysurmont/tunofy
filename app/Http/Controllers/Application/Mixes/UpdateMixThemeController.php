<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMixThemeRequest;
use App\Models\Mix;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;

class UpdateMixThemeController extends Controller
{
    public function __invoke(Mix $mix, UpdateMixThemeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $mix->update(['theme_setting_definition_id' => $validated['theme_setting_definition_id']]);

            if($validated['theme_setting_definition_id'] === 1) {
                return redirect()->back()
                    ->with('success', 'Theme updated successfully');    
            }

            Theme::updateOrCreate(
                [
                    'mix_id' => $mix->id,
                    'theme_setting_definition_id' => $validated['theme_setting_definition_id'],
                ],
                [
                    'settings' => $validated['settings'],
                ]
            );

            return redirect()->back()
                ->with('success', 'Theme updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update theme');
        }
    }
}
