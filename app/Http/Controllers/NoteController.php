<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\JournalTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoteController extends Controller
{
    private function makeHeadline(Note $note): string
    {
        $text = trim((string)($note->body ?? ''));

        if ($text === '') {
            foreach (($note->sections ?? []) as $s) {
                $c = trim((string)($s['content'] ?? ''));
                if ($c !== '') {
                    $text = $c;
                    break;
                }
            }
        }

        $text = preg_replace("/\s+/", " ", $text);
        return mb_strimwidth($text ?: '...', 0, 120, '...');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $q = trim((string) ($validated['q'] ?? ''));

        $query = Note::where('user_id', $user->id);

        if ($q !== '') {
            $needle = '%' . strtolower($q) . '%';
            $query->where(function ($sub) use ($needle) {
                $sub->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(body) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(sections) LIKE ?', [$needle]);
            });
        }

        $notes = $query->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($e) => [
                'id' => $e->id,
                'title' => trim((string)($e->title ?? '')) !== '' ? $e->title : 'Untitled Note',
                'is_pinned' => (bool)($e->is_pinned ?? false),
                'color' => $e->color ?? 'slate',
                'headline' => $this->makeHeadline($e),
                'updated_at' => $e->updated_at->toISOString(),
                'updated_at_human' => $e->updated_at->diffForHumans(),
            ]);

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'query' => $q,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        // Load templates so user can import them in the note
        $templates = JournalTemplate::where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'sections'])
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'sections' => $t->sections ?? [],
            ]);

        return Inertia::render('Notes/Editor', [
            'note' => null,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:50000'],
            'sections' => ['nullable', 'array', 'max:100'],
            'sections.*.id' => ['required_with:sections', 'string', 'max:100'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.content' => ['nullable', 'string', 'max:10000'],
            'is_pinned' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'in:slate,indigo,emerald,amber,rose,sky'],
        ]);

        $note = new Note();
        $note->user_id = $request->user()->id;
        $note->title = $data['title'] ?? null;
        $note->body = $data['body'] ?? null;
        $note->sections = $data['sections'] ?? null;
        $note->is_pinned = (bool)($data['is_pinned'] ?? false);
        $note->color = $data['color'] ?? 'slate';
        $note->save();

        return redirect()->route('notes.show', $note->id)->with('success', 'Note created successfully.');
    }

    public function show(Request $request, Note $note)
    {
        if (! $request->user()->can('view', $note)) {
            abort(403);
        }

        $templates = JournalTemplate::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'sections'])
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'sections' => $t->sections ?? [],
            ]);

        return Inertia::render('Notes/Editor', [
            'note' => [
                'id' => $note->id,
                'title' => $note->title ?? '',
                'body' => $note->body ?? '',
                'sections' => $note->sections ?? [],
                'is_pinned' => (bool)$note->is_pinned,
                'color' => $note->color ?? 'slate',
                'updated_at' => $note->updated_at->toISOString(),
            ],
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, Note $note)
    {
        if (! $request->user()->can('update', $note)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:50000'],
            'sections' => ['nullable', 'array', 'max:100'],
            'sections.*.id' => ['required_with:sections', 'string', 'max:100'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.content' => ['nullable', 'string', 'max:10000'],
            'is_pinned' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'in:slate,indigo,emerald,amber,rose,sky'],
        ]);

        $note->update([
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'sections' => $data['sections'] ?? null,
            'is_pinned' => (bool)($data['is_pinned'] ?? false),
            'color' => $data['color'] ?? 'slate',
        ]);

        return redirect()->route('notes.show', $note->id)->with('success', 'Note updated successfully.');
    }

    public function destroy(Request $request, Note $note)
    {
        if (! $request->user()->can('delete', $note)) {
            abort(403);
        }

        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Note deleted successfully.');
    }
}
