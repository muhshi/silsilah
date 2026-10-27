<?php

namespace App\Http\Controllers;

use App\Services\TreeExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TreeExportController extends Controller
{
    public function __construct(
        protected TreeExportService $exportService
    ) {}

    /**
     * Render route for signed preview/debugging
     */
    public function renderPreview(Request $request, string|int $tree): View
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $viewType = $request->query('view', 'horizontal');
        $treeModel = $this->exportService->findTree($tree);
        $rootMembers = $this->exportService->getRootMembers($treeModel);
        $allMembers = $treeModel->members;

        return view('tree.export-render', [
            'tree' => $treeModel,
            'rootMembers' => $rootMembers,
            'allMembers' => $allMembers,
            'viewType' => $viewType,
        ]);
    }

    /**
     * Export trigger for PNG, PDF, JSON, or Prompt AI
     */
    public function export(Request $request, string|int $tree, string $format): Response|JsonResponse
    {
        set_time_limit(120);
        abort_unless(in_array($format, ['png', 'pdf', 'json', 'prompt']), 400);

        $treeModel = $this->exportService->findTree($tree);
        $viewType = $request->query('view', 'horizontal');

        return match ($format) {
            'prompt' => $this->exportService->exportPrompt($treeModel),
            'json' => $this->exportService->exportJson($treeModel),
            'pdf' => $this->exportService->exportPdf($treeModel, $viewType),
            'png' => $this->exportService->exportPng($treeModel, $viewType),
        };
    }
}
