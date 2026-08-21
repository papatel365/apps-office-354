<?php

namespace App\Contracts;

use App\Services\Payment\DTOs\PaymentData;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\TransactionStatus;
use App\Services\Payment\DTOs\CallbackResult;
use App\Services\Payment\DTOs\RefundResult;
use App\Models\PaymentGateway;

interface PaymentGatewayInterface
{
    /**
     * Create a new payment transaction
     */
    public function createTransaction(PaymentData $data): PaymentResult;

    /**
     * Check the status of a transaction
     */
    public function checkStatus(string $referenceNo): TransactionStatus;

    /**
     * Cancel a pending transaction
     */
    public function cancelTransaction(string $referenceNo): bool;

    /**
     * Refund a transaction (full or partial)
     */
    public function refundTransaction(string $referenceNo, ?float $amount = null): RefundResult;

    /**
     * Handle callback/notification from payment gateway
     */
    public function handleCallback(array $payload, array $headers = []): CallbackResult;

    /**
     * Get the payment URL for customer redirect
     */
    public function getPaymentUrl(string $transactionId): string;

    /**
     * Check if the gateway is properly configured
     */
    public function isConfigured(): bool;

    /**
     * Get the gateway code (midtrans, duitku, tripay)
     */
    public function getGatewayCode(): string;

    /**
     * Get available payment channels
     */
    public function getAvailableChannels(): array;

    /**
     * Get the payment gateway model
     */
    public function getGateway(): PaymentGateway;

    /**
     * Set the payment gateway model
     */
    public function setGateway(PaymentGateway $gateway): self;

    /**
     * Test the connection to the payment gateway
     */
    public function testConnection(): array;
}
