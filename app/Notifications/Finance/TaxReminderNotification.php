<?php

namespace App\Notifications\Finance;

use App\Models\Finance\FinanceTaxTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaxReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected FinanceTaxTransaction $transaction;
    protected string $channel;

    public function __construct(FinanceTaxTransaction $transaction, string $channel = 'email')
    {
        $this->transaction = $transaction;
        $this->channel = $channel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return match($this->channel) {
            'whatsapp' => ['vonage'],
            default => ['mail', 'database'],
        };
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tax = $this->transaction->tax;
        $daysUntilDue = now()->diffInDays($this->transaction->due_date);

        return (new MailMessage)
            ->subject('Pengingat Pajak: ' . $tax->name)
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Ini adalah pengingat bahwa Anda memiliki tagihan pajak yang perlu dibayar.')
            ->line('**Detail Pajak:**')
            ->line('- Nama Pajak: ' . $tax->name . ' (' . $tax->type_label . ')')
            ->line('- Deskripsi: ' . $this->transaction->description)
            ->line('- Jumlah: Rp ' . number_format($this->transaction->tax_amount, 0, ',', '.'))
            ->line('- Tanggal Jatuh Tempo: ' . $this->transaction->due_date->format('d F Y'))
            ->line('- Sisa Hari: ' . $daysUntilDue . ' hari')
            ->action('Lihat Detail di CRM', url('/finance/taxes'))
            ->line('Harap segera proses pembayaran pajak ini sebelum jatuh tempo.');
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     */
    public function toVonage(object $notifiable): array
    {
        $tax = $this->transaction->tax;
        $daysUntilDue = now()->diffInDays($this->transaction->due_date);

        return [
            'content' => "Pengingat Pajak: {$tax->name}\n" .
                "Jumlah: Rp " . number_format($this->transaction->tax_amount, 0, ',', '.') . "\n" .
                "Jatuh Tempo: " . $this->transaction->due_date->format('d M Y') . "\n" .
                "Sisa Hari: {$daysUntilDue} hari\n" .
                "Segera proses di CRM: " . url('/finance/taxes'),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'tax_id' => $this->transaction->tax_id,
            'tax_name' => $this->transaction->tax->name,
            'description' => $this->transaction->description,
            'tax_amount' => $this->transaction->tax_amount,
            'due_date' => $this->transaction->due_date->format('Y-m-d'),
            'channel' => $this->channel,
            'message' => "Pengingat: Pajak {$this->transaction->tax->name} sebesar Rp " .
                number_format($this->transaction->tax_amount, 0, ',', '.') .
                " jatuh tempo pada " . $this->transaction->due_date->format('d M Y'),
        ];
    }
}
