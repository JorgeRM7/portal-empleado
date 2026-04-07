<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceSat extends Model
{
    
    use HasFactory;

    protected $table = 'invoice_sats';

    protected $fillable = ['uuid', 'invoice_id', 'emisor_name', 'emisor_rfc', 'trandate', 'total', 'status', 'subsidiary', 'efecto_comprobante', 'branch_office_id', 'deleted_at', 'rfc_pac', 'trandate_cer', 'trandate_cancel', 'rfc_cuenta_terceros', 'nombre_cuenta_terceros', 'file_path', 'meta', 'meta_xml', 'provider_id', 'invoice_category_id', 'invoice_location_id', 'invoice_department_id', 'invoice_company_id', 'xml_path', 'pdf_path', 'order_id', 'processing_at', 'cancelled_at', 'service_processing_at', 'service_ends_at', 'external_id', 'invoice_term_id', 'invoice_accounting_id', 'invoice_exclusion_category_id', 'invoice_article_id', 'notes', 'send_status', 'invoice_provider_type', 'invoice_operation_type_id', 'invoice_class_id', 'exchange_rate'];

    protected $casts = [
        'meta_xml' => 'array',
    ];



}
