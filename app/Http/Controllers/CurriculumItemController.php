<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurriculumItemRequest;
use App\Http\Requests\UpdateCurriculumItemRequest;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CurriculumItemController extends Controller implements HasMiddleware
{
    /**
     * Controller Middleware
     *
     * Curriculum Items follow the same permission tier as Curriculums
     * themselves — only Admin/Registrar may attach items into a
     * curriculum's prospectus.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {

                abort_unless(
                    auth()->user()->hasAnyRole(['Admin', 'Registrar']),
                    403,
                    'Unauthorized.'
                );

                return $next($request);

            }),
        ];
    }

    /**
     * Display a global listing of every curriculum item — Subject and
     * OJT alike — across all curriculums.
     */
    public function index()
    {
        return Inertia::render('CurriculumItems/Index', [

            'curriculumItems' => CurriculumItem::with([
                    'curriculum.program',
                    'curriculum.specialization',
                    'subject',
                ])
                ->orderBy('curriculum_id')
                ->orderBy('year_level')
                ->orderBy('semester')
                ->orderBy('sort_order')
                ->get(),

        ]);
    }

    /**
     * Show the form for creating a new curriculum item. Supports an
     * optional ?curriculum_id= query parameter so the "Add Item" action
     * on a curriculum's Manage page can pre-select the curriculum.
     */
    public function create(Request $request)
    {
        $selectedCurriculumId = $request->integer('curriculum_id') ?: null;

        return Inertia::render('CurriculumItems/Create', [

            'curricula' => Curriculum::with(['program', 'specialization'])
                ->orderBy('code')
                ->get(),

            'subjects' => Subject::where('active', true)
                ->orderBy('subject_code')
                ->get(),

            'selectedCurriculumId' => $selectedCurriculumId,

            // Lets the Subject dropdown gray out subjects already sitting
            // in this curriculum, instead of letting the user pick one
            // and only finding out after submitting.
            'assignedSubjectIds' => $selectedCurriculumId
                ? CurriculumItem::subjects()
                    ->where('curriculum_id', $selectedCurriculumId)
                    ->pluck('subject_id')
                : [],

        ]);
    }

    /**
     * Store one or more newly created curriculum items.
     *
     * Subject items are bulk-creatable — the Create form lets the user
     * check off several subjects at once and places all of them into
     * the same year_level/semester in one submit. OJT items are always
     * singular (each has its own title), so that branch just creates
     * one row.
     *
     * The Form Request's conditional rules do the type-specific shape
     * validation; the CurriculumItem model's saving() hook nulls out
     * whichever fields don't belong to the chosen item_type as a final
     * safety net.
     */
    public function store(StoreCurriculumItemRequest $request)
    {
        $validated = $request->validated();

        return $validated['item_type'] === CurriculumItem::TYPE_SUBJECT
            ? $this->storeSubjects($validated)
            : $this->storeOjt($validated);
    }

    /**
     * Bulk-attach every selected subject that isn't already assigned to
     * this curriculum. Subjects already present are silently skipped
     * (rather than failing the whole batch) since the Create form's
     * checklist already grays these out — this is just a safety net
     * for stale page state.
     */
    private function storeSubjects(array $validated)
    {
        $alreadyAssigned = CurriculumItem::subjects()
            ->where('curriculum_id', $validated['curriculum_id'])
            ->pluck('subject_id')
            ->all();

        $toAssign = array_values(array_diff($validated['subject_ids'], $alreadyAssigned));

        if (empty($toAssign)) {
            return back()
                ->withErrors(['subject_ids' => 'All selected subjects are already assigned to this curriculum.'])
                ->withInput();
        }

        // Space sort_order out in steps of 10 so an item can later be
        // manually re-ordered between two existing ones (via Edit)
        // without having to renumber the whole semester.
        $nextSortOrder = (int) CurriculumItem::where('curriculum_id', $validated['curriculum_id'])
            ->where('year_level', $validated['year_level'])
            ->where('semester', $validated['semester'])
            ->max('sort_order');

        foreach ($toAssign as $subjectId) {
            $nextSortOrder += 10;

            CurriculumItem::create([
                'curriculum_id' => $validated['curriculum_id'],
                'item_type' => CurriculumItem::TYPE_SUBJECT,
                'subject_id' => $subjectId,
                'year_level' => $validated['year_level'],
                'semester' => $validated['semester'],
                'sort_order' => $nextSortOrder,
                'active' => $validated['active'],
            ]);
        }

        $assignedCount = count($toAssign);
        $skippedCount = count($validated['subject_ids']) - $assignedCount;

        $message = $assignedCount === 1
            ? '1 subject added successfully.'
            : "{$assignedCount} subjects added successfully.";

        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} already assigned and skipped.)";
        }

        return redirect()
            ->route('curriculums.items.manage', $validated['curriculum_id'])
            ->with('success', $message);
    }

    /**
     * Create a single OJT item.
     */
    private function storeOjt(array $validated)
    {
        $nextSortOrder = 10 + (int) CurriculumItem::where('curriculum_id', $validated['curriculum_id'])
            ->where('year_level', $validated['year_level'])
            ->where('semester', $validated['semester'])
            ->max('sort_order');

        CurriculumItem::create([
            'curriculum_id' => $validated['curriculum_id'],
            'item_type' => CurriculumItem::TYPE_OJT,
            'title' => $validated['title'],
            'ojt_hours' => $validated['ojt_hours'],
            'year_level' => $validated['year_level'],
            'semester' => $validated['semester'],
            'sort_order' => $nextSortOrder,
            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('curriculums.items.manage', $validated['curriculum_id'])
            ->with('success', 'OJT item added to curriculum successfully.');
    }

    /**
     * Show the form for editing a curriculum item's placement.
     */
    public function edit(CurriculumItem $curriculumItem)
    {
        $curriculumItem->load(['curriculum', 'subject']);

        return Inertia::render('CurriculumItems/Edit', [

            'curriculumItem' => $curriculumItem,

            'curricula' => Curriculum::with(['program', 'specialization'])
                ->orderBy('code')
                ->get(),

            'subjects' => Subject::where('active', true)
                ->orderBy('subject_code')
                ->get(),

            // Subjects already used elsewhere in this curriculum (excluding
            // this item itself), so the dropdown can gray them out.
            'assignedSubjectIds' => CurriculumItem::subjects()
                ->where('curriculum_id', $curriculumItem->curriculum_id)
                ->where('id', '!=', $curriculumItem->id)
                ->pluck('subject_id'),

        ]);
    }

    /**
     * Update a curriculum item's placement.
     */
    public function update(UpdateCurriculumItemRequest $request, CurriculumItem $curriculumItem)
    {
        $validated = $request->validated();

        $validated['sort_order'] = $validated['sort_order'] ?? $curriculumItem->sort_order;

        $curriculumItem->update($validated);

        return redirect()
            ->route('curriculums.items.manage', $validated['curriculum_id'])
            ->with('success', 'Curriculum item updated successfully.');
    }

    /**
     * Remove an item from a curriculum.
     *
     * Redirects back to wherever the request originated (the global
     * CurriculumItems index or a curriculum's Manage page) rather than
     * a fixed route.
     */
    public function destroy(CurriculumItem $curriculumItem)
    {
        $curriculumItem->delete();

        return back()->with('success', 'Item removed from curriculum successfully.');
    }

    /**
     * Manage Items — curriculum-scoped prospectus view.
     *
     * Shows every item (Subject and OJT) assigned to a single curriculum,
     * grouped by year level and semester. This is the primary workspace
     * for assigning, re-placing, and removing items from a curriculum.
     */
    public function manage(Curriculum $curriculum)
    {
        $curriculum->load(['program.department', 'specialization']);

        return Inertia::render('CurriculumItems/Manage', [

            'curriculum' => $curriculum,

            'curriculumItems' => CurriculumItem::with('subject')
                ->where('curriculum_id', $curriculum->id)
                ->orderBy('year_level')
                ->orderBy('semester')
                ->orderBy('sort_order')
                ->get(),

        ]);
    }
}