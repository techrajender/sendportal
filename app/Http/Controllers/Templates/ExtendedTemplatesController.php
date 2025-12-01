<?php

namespace App\Http\Controllers\Templates;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Models\Template;

class ExtendedTemplatesController extends Controller
{
    /**
     * Clone a template
     *
     * @param int $id
     * @return RedirectResponse
     * @throws Exception
     */
    public function clone(int $id): RedirectResponse
    {
        try {
            $workspaceId = Sendportal::currentWorkspaceId();
            
            // Find the original template
            $originalTemplate = Template::where('workspace_id', $workspaceId)
                ->where('id', $id)
                ->first();
            
            if (!$originalTemplate) {
                return redirect()
                    ->route('sendportal.templates.index')
                    ->withErrors(['error' => __('Template not found.')]);
            }

            // Create cloned template
            $clonedTemplate = Template::create([
                'workspace_id' => $workspaceId,
                'name' => $originalTemplate->name . '-clone',
                'content' => $originalTemplate->content,
            ]);

            return redirect()
                ->route('sendportal.templates.edit', $clonedTemplate->id)
                ->with('success', __('Template cloned successfully.'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error cloning template', [
                'template_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->route('sendportal.templates.index')
                ->withErrors(['error' => __('Failed to clone template: ') . $e->getMessage()]);
        }
    }
}

