<?php

namespace App\Http\Controllers\Tags;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Tags\TagsController as BaseTagsController;
use Sendportal\Base\Http\Requests\TagStoreRequest;
use Sendportal\Base\Http\Requests\TagUpdateRequest;
use Sendportal\Base\Repositories\TagTenantRepository;
use Sendportal\Base\Repositories\Subscribers\SubscriberTenantRepositoryInterface;

class ExtendedTagsController extends BaseTagsController
{
    /** @var TagTenantRepository */
    protected $tagRepository;
    
    /** @var SubscriberTenantRepositoryInterface */
    private $subscriberRepository;

    public function __construct(TagTenantRepository $tagRepository, SubscriberTenantRepositoryInterface $subscriberRepository)
    {
        parent::__construct($tagRepository);
        $this->tagRepository = $tagRepository;
        $this->subscriberRepository = $subscriberRepository;
    }

    /**
     * @throws Exception
     */
    public function create(): View
    {
        $workspaceId = Sendportal::currentWorkspaceId();
        $subscribers = $this->subscriberRepository->all($workspaceId, 'email');
        
        return view('sendportal::tags.create', compact('subscribers'));
    }

    /**
     * @throws Exception
     */
    public function store(TagStoreRequest $request): RedirectResponse
    {
        try {
            $workspaceId = Sendportal::currentWorkspaceId();
            $tag = $this->tagRepository->store($workspaceId, $request->only(['name']));
            
            // Sync subscribers - always sync, even if empty array
            $subscriberIds = $request->input('subscribers', []);
            if (!is_array($subscriberIds)) {
                $subscriberIds = [];
            }
            
            // Filter out any empty values and convert to integers
            $subscriberIds = array_filter(array_map('intval', $subscriberIds), function($id) {
                return $id > 0;
            });
            
            // Sync subscribers (empty array will remove all subscribers)
            $this->tagRepository->syncSubscribers($tag, array_values($subscriberIds));
            
            return redirect()->route('sendportal.tags.index')->with('success', __('Tag created successfully.'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error storing tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('Failed to create tag: ') . $e->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    public function edit(int $id): View
    {
        $workspaceId = Sendportal::currentWorkspaceId();
        $tag = $this->tagRepository->find($workspaceId, $id, ['subscribers']);
        $subscribers = $this->subscriberRepository->all($workspaceId, 'email');
        $selectedSubscriberIds = $tag->subscribers->pluck('id')->toArray();
        
        return view('sendportal::tags.edit', compact('tag', 'subscribers', 'selectedSubscriberIds'));
    }

    /**
     * @throws Exception
     */
    public function update(int $id, TagUpdateRequest $request): RedirectResponse
    {
        try {
            $workspaceId = Sendportal::currentWorkspaceId();
            $tag = $this->tagRepository->update($workspaceId, $id, $request->only(['name']));
            
            // Sync subscribers - always sync, even if empty array (to remove all subscribers)
            $subscriberIds = $request->input('subscribers', []);
            if (!is_array($subscriberIds)) {
                $subscriberIds = [];
            }
            
            // Filter out any empty values and convert to integers
            $subscriberIds = array_filter(array_map('intval', $subscriberIds), function($id) {
                return $id > 0;
            });
            
            // Sync subscribers (empty array will remove all subscribers)
            $this->tagRepository->syncSubscribers($tag, array_values($subscriberIds));
            
            return redirect()->route('sendportal.tags.index')->with('success', __('Tag updated successfully.'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating tag', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('Failed to update tag: ') . $e->getMessage()]);
        }
    }
}

