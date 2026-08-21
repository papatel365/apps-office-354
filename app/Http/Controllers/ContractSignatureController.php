<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractSignaturePosition;
use App\Models\SignatureAuditLog;
use App\Models\SignatureTemplate;
use App\Models\ContractSignedDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ContractSignatureController extends Controller
{
    /**
     * Display the contract signature page with document preview.
     */
    public function show(string $token): View|RedirectResponse
    {
        $contract = Contract::where('client_signature_token', $token)
            ->with(['signaturePositions' => function ($q) {
                $q->where('signer_type', ContractSignaturePosition::SIGNER_CLIENT);
            }])
            ->first();

        if (!$contract) {
            abort(404, 'Link tidak valid atau sudah tidak digunakan.');
        }

        if ($contract->client_signature_expires_at && $contract->client_signature_expires_at->isPast()) {
            // Log expired action
            SignatureAuditLog::log($contract, SignatureAuditLog::ACTION_EXPIRED);

            return redirect()->route('contract.sign.expired')->with('error', 'Link signature sudah expired. Silakan minta link baru dari pihak terkait.');
        }

        // Check if all client signature positions are signed
        $unsignedClientPositions = $contract->signaturePositions()
            ->where('signer_type', ContractSignaturePosition::SIGNER_CLIENT)
            ->where('is_signed', false)
            ->first();

        if (!$unsignedClientPositions) {
            // All client positions are already signed
            return redirect()->route('contract.sign.already-signed', $contract->id);
        }

        // Log viewed action
        SignatureAuditLog::log($contract, SignatureAuditLog::ACTION_VIEWED, 'client');

        // Get document URL if exists
        $documentUrl = null;
        if ($contract->document_path) {
            $documentUrl = Storage::disk('public')->url($contract->document_path);
        }

        // Get company signature template for display
        $companyTemplate = SignatureTemplate::where('company_id', $contract->company_id)
            ->where('is_active', true)
            ->first();

        return view('contracts.signature', [
            'contract' => $contract,
            'documentUrl' => $documentUrl,
            'companyTemplate' => $companyTemplate,
        ]);
    }

    /**
     * Process the signature.
     */
    public function sign(Request $request, string $token): RedirectResponse|JsonResponse
    {
        $contract = Contract::where('client_signature_token', $token)
            ->with(['signaturePositions' => function ($q) {
                $q->where('signer_type', ContractSignaturePosition::SIGNER_CLIENT)
                  ->where('is_signed', false);
            }])
            ->first();

        if (!$contract) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Link tidak valid.'], 404);
            }
            abort(404, 'Link tidak valid.');
        }

        if ($contract->client_signature_expires_at && $contract->client_signature_expires_at->isPast()) {
            SignatureAuditLog::log($contract, SignatureAuditLog::ACTION_EXPIRED);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Link sudah expired.'], 403);
            }
            return redirect()->route('contract.sign.expired')->with('error', 'Link signature sudah expired.');
        }

        // Validate
        $validator = Validator::make($request->all(), [
            'signature_name' => 'required|string|max:255',
            'signature_data' => 'required|string',
            'position_id' => 'nullable|exists:contract_signature_positions,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get unsigned client position
        $signaturePosition = $contract->signaturePositions()
            ->where('signer_type', ContractSignaturePosition::SIGNER_CLIENT)
            ->where('is_signed', false)
            ->first();

        if (!$signaturePosition) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada posisi signature yang perlu ditandatangani.'], 400);
            }
            return redirect()->route('contract.sign.already-signed', $contract->id);
        }

        // Sign the position
        $signaturePosition->sign(
            $request->signature_data,
            $request->signature_name
        );

        // Also update the contract's client_signature field for backward compatibility
        $contract->update([
            'client_signature' => $request->signature_data,
            'client_signed_at' => now(),
            'client_signed_name' => $request->signature_name,
            'signature_ip_address' => $request->ip(),
        ]);

        // Log signature action
        SignatureAuditLog::log(
            $contract,
            SignatureAuditLog::ACTION_SIGNED,
            'client',
            $request->signature_name,
            $signaturePosition->id,
            [
                'browser' => request()->userAgent(),
                'position' => $signaturePosition->position_array,
            ]
        );

        // Update contract status
        $contract->updateSignatureStatus();

        // Check if we should generate the final document
        if ($contract->signature_progress['all_signed']) {
            // Both parties have signed - generate final document
            $this->generateFinalDocument($contract);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tanda tangan berhasil disimpan!',
                'all_signed' => $contract->signature_progress['all_signed'],
            ]);
        }

        return redirect()->route('contract.sign.success', $contract->id)
            ->with('success', 'Kontrak berhasil ditandatangani!');
    }

    /**
     * Generate the final signed document.
     */
    protected function generateFinalDocument(Contract $contract): ?ContractSignedDocument
    {
        try {
            // Get all signature positions
            $signaturePositions = $contract->signaturePositions()->get();

            // For now, create a record - actual PDF generation would require PDF library integration
            // This is a placeholder for the PDF generation logic
            $signedDocument = ContractSignedDocument::create([
                'uuid' => \Str::uuid(),
                'tenant_id' => $contract->tenant_id,
                'company_id' => $contract->company_id,
                'contract_id' => $contract->id,
                'original_document_path' => $contract->document_path,
                'document_path' => $contract->document_path, // In real implementation, this would be the final PDF path
                'generated_by' => 'system',
                'created_by' => auth()->id(),
            ]);

            // Log document generation
            SignatureAuditLog::log($contract, SignatureAuditLog::ACTION_DOCUMENT_GENERATED);

            return $signedDocument;
        } catch (\Exception $e) {
            \Log::error('Failed to generate final document: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Show success page.
     */
    public function showSuccess(Contract $contract): View
    {
        $contract->load(['signaturePositions', 'signedDocuments']);

        return view('contracts.signature-success', compact('contract'));
    }

    /**
     * Show expired page.
     */
    public function expired(): View
    {
        return view('contracts.signature-expired');
    }

    /**
     * Show already signed page.
     */
    public function alreadySigned(Contract $contract): View
    {
        return view('contracts.signature-already-signed', compact('contract'));
    }

    /**
     * Create signature position for a contract.
     */
    public function createPosition(Request $request, Contract $contract): JsonResponse
    {
        $request->validate([
            'signer_type' => 'required|in:company,client',
            'page_number' => 'required|integer|min:1',
            'x_position' => 'required|numeric|min:0',
            'y_position' => 'required|numeric|min:0',
            'width' => 'nullable|numeric|min:50',
            'height' => 'nullable|numeric|min:30',
        ]);

        $position = ContractSignaturePosition::create([
            'uuid' => \Str::uuid(),
            'tenant_id' => $this->tenantId(),
            'company_id' => $this->companyId(),
            'contract_id' => $contract->id,
            'signer_type' => $request->signer_type,
            'page_number' => $request->page_number,
            'x_position' => $request->x_position,
            'y_position' => $request->y_position,
            'width' => $request->width ?? 200,
            'height' => $request->height ?? 80,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posisi signature berhasil ditambahkan',
            'data' => $position,
        ]);
    }

    /**
     * Update signature position.
     */
    public function updatePosition(Request $request, Contract $contract, ContractSignaturePosition $position): JsonResponse
    {
        $request->validate([
            'page_number' => 'nullable|integer|min:1',
            'x_position' => 'nullable|numeric|min:0',
            'y_position' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:50',
            'height' => 'nullable|numeric|min:30',
        ]);

        $position->update($request->only([
            'page_number', 'x_position', 'y_position', 'width', 'height'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Posisi signature berhasil diperbarui',
            'data' => $position,
        ]);
    }

    /**
     * Delete signature position.
     */
    public function deletePosition(Contract $contract, ContractSignaturePosition $position): JsonResponse
    {
        if ($position->is_signed) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus posisi yang sudah ditandatangani',
            ], 400);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Posisi signature berhasil dihapus',
        ]);
    }

    /**
     * Sign as company (admin function).
     */
    public function signAsCompany(Request $request, Contract $contract): JsonResponse
    {
        $request->validate([
            'signature_name' => 'required|string|max:255',
            'signature_data' => 'required|string',
            'position_id' => 'nullable|exists:contract_signature_positions,id',
        ]);

        // Get or create company signature position
        $position = $contract->signaturePositions()
            ->where('signer_type', ContractSignaturePosition::SIGNER_COMPANY)
            ->where('is_signed', false)
            ->first();

        if (!$position) {
            // Create a default position if none exists
            $position = ContractSignaturePosition::create([
                'uuid' => \Str::uuid(),
                'tenant_id' => $contract->tenant_id,
                'company_id' => $contract->company_id,
                'contract_id' => $contract->id,
                'signer_type' => ContractSignaturePosition::SIGNER_COMPANY,
                'page_number' => 1,
                'x_position' => 50,
                'y_position' => 700,
                'width' => 200,
                'height' => 80,
            ]);
        }

        // Sign the position
        $position->sign($request->signature_data, $request->signature_name);

        // Also update contract for backward compatibility
        $contract->update([
            'company_signature' => $request->signature_data,
            'company_signed_at' => now(),
            'company_signed_name' => $request->signature_name,
        ]);

        // Log signature action
        SignatureAuditLog::log(
            $contract,
            SignatureAuditLog::ACTION_SIGNED,
            'company',
            $request->signature_name,
            $position->id
        );

        // Update contract status
        $contract->updateSignatureStatus();

        // Check if we should generate the final document
        if ($contract->signature_progress['all_signed']) {
            $this->generateFinalDocument($contract);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan perusahaan berhasil disimpan',
            'data' => $position,
        ]);
    }
}
