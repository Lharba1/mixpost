<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Http\Resources\HashtagGroupResource;
use Inovector\Mixpost\Models\HashtagGroup;

class HashtagGroupsController extends Controller
{
    public function index(): Response
    {
        $groups = HashtagGroup::latest()->get();

        return Inertia::render('HashtagGroups', [
            'hashtag_groups' => HashtagGroupResource::collection($groups)->resolve(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hashtags' => 'required|string',
            'color' => 'nullable|string|max:7',
        ]);

        $group = new HashtagGroup();
        $group->name = $validated['name'];
        $group->setHashtagsFromString($validated['hashtags']);
        $group->color = $validated['color'] ?? '#6366f1';
        $group->save();

        return redirect()->back();
    }

    public function update(Request $request, HashtagGroup $hashtagGroup): HttpResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hashtags' => 'required|string',
            'color' => 'nullable|string|max:7',
        ]);

        $hashtagGroup->name = $validated['name'];
        $hashtagGroup->setHashtagsFromString($validated['hashtags']);
        if (isset($validated['color'])) {
            $hashtagGroup->color = $validated['color'];
        }
        $hashtagGroup->save();

        return response()->noContent();
    }

    public function destroy(HashtagGroup $hashtagGroup): RedirectResponse
    {
        $hashtagGroup->delete();

        return redirect()->back();
    }

    /**
     * Get all hashtag groups for the post editor
     */
    public function all(): HttpResponse
    {
        $groups = HashtagGroup::latest()->get();

        return response()->json([
            'hashtag_groups' => HashtagGroupResource::collection($groups)->resolve(),
        ]);
    }
}
