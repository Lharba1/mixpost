<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Http\Resources\PostTemplateResource;
use Inovector\Mixpost\Models\PostTemplate;

class PostTemplatesController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PostTemplate::latest();

        if ($request->has('category') && $request->category) {
            $query->category($request->category);
        }

        $templates = $query->get();
        $categories = PostTemplate::getCategories();

        return Inertia::render('PostTemplates', [
            'templates' => PostTemplateResource::collection($templates)->resolve(),
            'categories' => $categories,
            'current_category' => $request->category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'content' => 'required|array',
            'category' => 'nullable|string|max:100',
        ]);

        PostTemplate::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, PostTemplate $postTemplate): HttpResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'content' => 'required|array',
            'category' => 'nullable|string|max:100',
        ]);

        $postTemplate->update($validated);

        return response()->noContent();
    }

    public function destroy(PostTemplate $postTemplate): RedirectResponse
    {
        $postTemplate->delete();

        return redirect()->back();
    }

    /**
     * Get all templates for the post editor
     */
    public function all(): HttpResponse
    {
        $templates = PostTemplate::latest()->get();

        return response()->json([
            'templates' => PostTemplateResource::collection($templates)->resolve(),
        ]);
    }

    /**
     * Save current post content as a template
     */
    public function saveFromPost(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|array',
            'category' => 'nullable|string|max:100',
        ]);

        $template = PostTemplate::create($validated);

        return response()->json([
            'template' => new PostTemplateResource($template),
        ], 201);
    }
}
