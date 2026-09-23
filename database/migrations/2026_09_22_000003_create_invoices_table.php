<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->uuid('invoice_recipient_id');
            $table->foreign('invoice_recipient_id')
                ->references('id')
                ->on('invoice_recipients')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('reference', 255);
            $table->string('status', 20)->default('draft');
            $table->string('currency', 3);
            $table->date('date');
            $table->date('due_at')->nullable();
            $table->date('paid_date')->nullable();
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            // Seller snapshot, copied from invoice_settings at creation time so past invoices stay correct
            // even if the organization later edits its seller details.
            $table->string('seller_name', 255);
            $table->string('seller_vatin', 255)->nullable();
            $table->string('seller_address_line_1', 255)->nullable();
            $table->string('seller_address_line_2', 255)->nullable();
            $table->string('seller_address_line_3', 255)->nullable();
            $table->string('seller_address_post_code', 255)->nullable();
            $table->string('seller_address_city', 255)->nullable();
            $table->string('seller_address_country', 255)->nullable();
            $table->string('seller_phone', 255)->nullable();
            $table->string('seller_email', 255)->nullable();
            $table->string('payment_iban', 255)->nullable();
            $table->text('payment_terms')->nullable();
            $table->unsignedInteger('tax_rate')->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->unsignedBigInteger('discount_amount')->nullable();
            $table->boolean('is_eu_reverse_charge')->default(false);
            $table->text('footer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'reference']);
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
