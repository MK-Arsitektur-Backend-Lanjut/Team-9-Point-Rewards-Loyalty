<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Request\StatementFilterRequest;
use App\Services\PointStatementService;

class StatementController extends Controller
{
    protected $statementService;

    public function __construct(PointStatementService $statementService)
    {
        $this->statementService = $statementService;
    }

    /**
     * Get E-Statement (riwayat poin)
     * 
     * @queryParam start_date Filter dari tanggal (Y-m-d)
     * @queryParam end_date Filter sampai tanggal (Y-m-d)
     * @queryParam activity_code Filter berdasarkan activity code
     * @queryParam point_status Filter status (active/expired/redeemed)
     * @queryParam per_page Jumlah per halaman (default 15)
     */
    public function index(StatementFilterRequest $request)
    {
        $statement = $this->statementService->getStatement(
            auth()->id(),
            $request->validated()
        );
        
        return response()->json([
            'success' => true,
            'data' => $statement
        ]);
    }

    /**
     * Get points balance
     */
    public function balance()
    {
        $balance = $this->statementService->getPointsBalance(auth()->id());
        
        return response()->json([
            'success' => true,
            'data' => $balance
        ]);
    }

    /**
     * Export E-Statement to PDF
     */
    public function exportPdf(StatementFilterRequest $request)
    {
        $statement = $this->statementService->getStatement(
            auth()->id(),
            $request->validated()
        );
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.e-statement', [
            'statement' => $statement,
            'user' => auth()->user(),
            'generated_at' => now()
        ]);
        
        return $pdf->download('e-statement-' . auth()->id() . '-' . date('Y-m-d') . '.pdf');
    }
}