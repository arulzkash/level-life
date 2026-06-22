<?php

namespace App\Http\Controllers;



use App\Models\JournalTemplate;
use Illuminate\Http\Request;

class JournalTemplateController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'sections' => ['nullable', 'array', 'max:100'],
            'sections.*.title' => ['nullable', 'string', 'max:120'],
        ]);

        // normalize: buang title kosong
        $sections = collect($data['sections'] ?? [])
            ->map(fn($s) => ['title' => trim((string)($s['title'] ?? ''))])
            ->filter(fn($s) => $s['title'] !== '')
            ->values()
            ->all();

        JournalTemplate::updateOrCreate(
            ['user_id' => $user->id, 'name' => $data['name']],
            ['sections' => $sections]
        );

        return back()->with('success', 'Template saved.');
    }

    public function destroy(Request $request, JournalTemplate $template)
    {
        abort_unless($request->user()->can('delete', $template), 403);
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }
}
