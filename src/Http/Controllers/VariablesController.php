<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Http\Resources\VariableResource;
use Inovector\Mixpost\Models\Variable;
use Inovector\Mixpost\Support\VariableProcessor;

class VariablesController extends Controller
{
    public function index(): Response
    {
        $customVariables = Variable::latest()->get();
        $systemVariables = Variable::getSystemVariables();

        return Inertia::render('Variables', [
            'custom_variables' => VariableResource::collection($customVariables)->resolve(),
            'system_variables' => $systemVariables,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|alpha_dash|unique:mixpost_variables,key',
            'value' => 'required|string',
        ]);

        Variable::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Variable $variable): HttpResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255|alpha_dash|unique:mixpost_variables,key,' . $variable->id,
            'value' => 'required|string',
        ]);

        $variable->update($validated);

        return response()->noContent();
    }

    public function destroy(Variable $variable): RedirectResponse
    {
        $variable->delete();

        return redirect()->back();
    }

    /**
     * Get all variables (system + custom) for post editor
     */
    public function all(): HttpResponse
    {
        return response()->json([
            'variables' => Variable::getAllVariables(),
        ]);
    }

    /**
     * Preview content with variables replaced
     */
    public function preview(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $preview = VariableProcessor::preview($validated['content']);
        $validation = VariableProcessor::validateVariables($validated['content']);

        return response()->json([
            'preview' => $preview,
            'valid' => $validation['valid'],
            'missing_variables' => $validation['missing'],
        ]);
    }
}
