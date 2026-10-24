<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\P2hTemplate;
use App\Models\P2hTemplateItem;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class P2hTemplateController extends Controller
{
    /**
     * Tampilkan daftar Master Template Checksheet P2H Heavy Equipment sesuai kategori aset.
     */
    public function index(Request $request): View
    {
        $query = P2hTemplate::withCount(['items', 'vehicles', 'records']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        $templates = $query->orderBy('name')->get();

        return view('qma.p2h_templates', compact('templates'));
    }

    /**
     * Tampilkan detail butir pertanyaan dalam template tertentu.
     */
    public function show(int $id): View
    {
        $template = P2hTemplate::findOrFail($id);
        
        // Pastikan seluruh nomor urut pertanyaan berurutan otomatis tanpa celah (1, 2, 3... N)
        $this->resequenceTemplate($template);
        
        $template->load(['items' => fn($q) => $q->orderBy('sequence')]);

        $groupedItems = [];
        foreach ($template->items as $item) {
            $groupedItems[$item->section][] = $item;
        }

        // Ambil daftar unik nama section yang sudah ada untuk saran/opsi dropdown
        $availableSections = P2hTemplateItem::where('template_id', $template->id)
            ->distinct()
            ->pluck('section')
            ->values()
            ->all();

        return view('qma.p2h_template_detail', compact('template', 'groupedItems', 'availableSections'));
    }

    /**
     * Perbarui butir pertanyaan checksheet P2H yang sudah ada.
     */
    public function updateItem(Request $request, int $templateId, int $itemId): RedirectResponse
    {
        $template = P2hTemplate::findOrFail($templateId);
        $item = P2hTemplateItem::where('template_id', $template->id)->findOrFail($itemId);

        $allowedTypes = [
            'Multiple Lines Text Box',
            'Single Line Text Box',
            'Numerical Value',
            'Date',
            'Datetime',
            'Multiple choice: only one answer',
            'Multiple choice: multiple answers allowed',
            'Matrix',
            'choice',
            'condition',
            'number',
            'text',
            'datetime',
            'date',
            'textarea',
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'section' => 'required|string|max:100',
            'question_type' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in($allowedTypes)],
            'sequence' => 'nullable|integer|min:1',
        ]);

        $oldSection = $item->section;
        $newSection = trim($validated['section']);

        $item->update([
            'title' => trim($validated['title']),
            'section' => $newSection,
            'question_type' => $validated['question_type'],
        ]);

        if ($oldSection !== $newSection) {
            $targetMaxSeq = P2hTemplateItem::where('template_id', $template->id)
                ->where('section', $newSection)
                ->where('id', '!=', $item->id)
                ->max('sequence');

            if ($targetMaxSeq) {
                P2hTemplateItem::where('template_id', $template->id)
                    ->where('id', '!=', $item->id)
                    ->where('sequence', '>', $targetMaxSeq)
                    ->increment('sequence', 10);
                $item->update(['sequence' => $targetMaxSeq + 1]);
            }
        }

        $this->resequenceTemplate($template);

        return redirect()
            ->route('qma.p2h.templates.show', $template->id)
            ->with('success', "Butir pertanyaan '{$item->title}' berhasil diperbarui.");
    }

    /**
     * Tambah butir pertanyaan baru ke dalam template checksheet.
     */
    public function storeItem(Request $request, int $templateId): RedirectResponse
    {
        $template = P2hTemplate::findOrFail($templateId);

        $allowedTypes = [
            'Multiple Lines Text Box',
            'Single Line Text Box',
            'Numerical Value',
            'Date',
            'Datetime',
            'Multiple choice: only one answer',
            'Multiple choice: multiple answers allowed',
            'Matrix',
            'choice',
            'condition',
            'number',
            'text',
            'datetime',
            'date',
            'textarea',
        ];

        // 1. Jika form mengirimkan daftar pertanyaan sekaligus (Tambah Section dengan List Pertanyaan)
        if ($request->has('questions') && is_array($request->input('questions'))) {
            $validated = $request->validate([
                'section' => 'required|string|max:100',
                'position_type' => 'nullable|string|in:end,start,after,before',
                'target_section' => 'nullable|string|max:100',
                'questions' => 'required|array|min:1',
                'questions.*.title' => 'required|string|max:255',
                'questions.*.question_type' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in($allowedTypes)],
                'questions.*.sequence' => 'nullable|integer|min:1',
            ]);

            $section = trim($validated['section']);
            $positionType = $validated['position_type'] ?? 'end';
            $targetSection = !empty($validated['target_section']) ? trim($validated['target_section']) : null;

            $validQuestions = array_values(array_filter($validated['questions'], fn($q) => !empty(trim($q['title'] ?? ''))));
            $n = count($validQuestions);

            if ($n === 0) {
                return redirect()->back()->withErrors(['questions' => 'Minimal harus ada 1 pertanyaan yang terisi.']);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($template, $section, $positionType, $targetSection, $validQuestions, $n) {
                // Tentukan base sequence awal berdasarkan posisi penempatan
                if ($positionType === 'start') {
                    // Geser semua item yang sudah ada
                    P2hTemplateItem::where('template_id', $template->id)->increment('sequence', $n + 100);
                    $startSeq = 1;
                } elseif ($positionType === 'after' && $targetSection) {
                    $targetMaxSeq = P2hTemplateItem::where('template_id', $template->id)
                        ->where('section', $targetSection)
                        ->max('sequence');

                    if ($targetMaxSeq) {
                        P2hTemplateItem::where('template_id', $template->id)
                            ->where('sequence', '>', $targetMaxSeq)
                            ->increment('sequence', $n + 100);
                        $startSeq = $targetMaxSeq + 1;
                    } else {
                        $startSeq = (P2hTemplateItem::where('template_id', $template->id)->max('sequence') ?? 0) + 1;
                    }
                } elseif ($positionType === 'before' && $targetSection) {
                    $targetMinSeq = P2hTemplateItem::where('template_id', $template->id)
                        ->where('section', $targetSection)
                        ->min('sequence');

                    if ($targetMinSeq) {
                        P2hTemplateItem::where('template_id', $template->id)
                            ->where('sequence', '>=', $targetMinSeq)
                            ->increment('sequence', $n + 100);
                        $startSeq = $targetMinSeq;
                    } else {
                        $startSeq = (P2hTemplateItem::where('template_id', $template->id)->max('sequence') ?? 0) + 1;
                    }
                } else {
                    // Default 'end'
                    $startSeq = (P2hTemplateItem::where('template_id', $template->id)->max('sequence') ?? 0) + 1;
                }

                foreach ($validQuestions as $idx => $qData) {
                    P2hTemplateItem::create([
                        'template_id' => $template->id,
                        'section' => $section,
                        'title' => trim($qData['title']),
                        'question_type' => $qData['question_type'],
                        'sequence' => $startSeq + $idx,
                    ]);
                }

                // Normalisasi ulang sequence otomatis berurutan dari section pertama hingga akhir
                $this->resequenceTemplate($template);
            });

            return redirect()
                ->route('qma.p2h.templates.show', $template->id)
                ->with('success', "Bagian '{$section}' dengan {$n} butir pertanyaan berhasil ditambahkan.");
        }

        // 2. Format single question (form butir tunggal via modal tambah pertanyaan)
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'section' => 'required|string|max:100',
            'question_type' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in($allowedTypes)],
            'sequence' => 'nullable|integer|min:1',
        ]);

        $section = trim($validated['section']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($template, $section, $validated) {
            $targetSectionMaxSeq = P2hTemplateItem::where('template_id', $template->id)
                ->where('section', $section)
                ->max('sequence');

            if ($targetSectionMaxSeq) {
                // Geser butir di section setelahnya agar pertanyaan ini diletakkan di akhir section terkait
                P2hTemplateItem::where('template_id', $template->id)
                    ->where('sequence', '>', $targetSectionMaxSeq)
                    ->increment('sequence', 10);
                $seq = $targetSectionMaxSeq + 1;
            } else {
                $seq = (P2hTemplateItem::where('template_id', $template->id)->max('sequence') ?? 0) + 1;
            }

            P2hTemplateItem::create([
                'template_id' => $template->id,
                'section' => $section,
                'title' => trim($validated['title']),
                'question_type' => $validated['question_type'],
                'sequence' => $seq,
            ]);

            $this->resequenceTemplate($template);
        });

        return redirect()
            ->route('qma.p2h.templates.show', $template->id)
            ->with('success', "Butir pertanyaan berhasil ditambahkan ke bagian '{$section}'.");
    }

    /**
     * Atur ulang urutan seluruh section dalam template checksheet.
     */
    public function reorderSections(Request $request, int $templateId): RedirectResponse
    {
        $template = P2hTemplate::findOrFail($templateId);

        $validated = $request->validate([
            'sections' => 'required|array|min:1',
            'sections.*' => 'required|string|max:100',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($template, $validated) {
            $currentSequence = 1;

            foreach ($validated['sections'] as $sectionName) {
                $items = P2hTemplateItem::where('template_id', $template->id)
                    ->where('section', $sectionName)
                    ->orderBy('sequence')
                    ->orderBy('id')
                    ->get();

                foreach ($items as $item) {
                    $item->update(['sequence' => $currentSequence]);
                    $currentSequence++;
                }
            }
        });

        return redirect()
            ->route('qma.p2h.templates.show', $template->id)
            ->with('success', 'Urutan section berhasil diperbarui.');
    }

    /**
     * Hapus butir pertanyaan dari template checksheet.
     */
    public function destroyItem(int $templateId, int $itemId): RedirectResponse
    {
        $template = P2hTemplate::findOrFail($templateId);
        $item = P2hTemplateItem::where('template_id', $template->id)->findOrFail($itemId);

        $itemTitle = $item->title;
        $item->delete();

        // Normalisasi ulang sequence otomatis agar tidak ada celah/lompatan nomor
        $this->resequenceTemplate($template);

        return redirect()
            ->route('qma.p2h.templates.show', $template->id)
            ->with('success', "Butir pertanyaan '{$itemTitle}' berhasil dihapus.");
    }

    /**
     * Urutkan ulang sequence seluruh butir pertanyaan dalam template
     * agar berurutan secara konsisten dari section pertama hingga akhir (1, 2, 3... N).
     */
    private function resequenceTemplate(P2hTemplate $template): void
    {
        $sections = P2hTemplateItem::where('template_id', $template->id)
            ->select('section', \Illuminate\Support\Facades\DB::raw('MIN(sequence) as min_seq'))
            ->groupBy('section')
            ->orderBy('min_seq')
            ->pluck('section');

        $currentSeq = 1;
        foreach ($sections as $sectionName) {
            $items = P2hTemplateItem::where('template_id', $template->id)
                ->where('section', $sectionName)
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();

            foreach ($items as $item) {
                if ($item->sequence !== $currentSeq) {
                    $item->update(['sequence' => $currentSeq]);
                }
                $currentSeq++;
            }
        }
    }
}

