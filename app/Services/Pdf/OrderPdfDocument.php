<?php

namespace App\Services\Pdf;

use App\Models\Order;
use App\Services\PdfService;
use Illuminate\Http\Response;

abstract class OrderPdfDocument
{
    public function __construct(protected PdfService $pdf) {}

    /**
     * Render the document for the given order and return it as a PDF response.
     */
    public function download(Order $order): Response
    {
        $order->load($this->relations());

        $this->render($order);

        return response($this->pdf->output($order->id.'_'.$this->fileSuffix().'.pdf'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * Write the document body using the PDF service.
     */
    abstract protected function render(Order $order): void;

    /**
     * The suffix of the generated file name, e.g. "auftragsliste".
     */
    abstract protected function fileSuffix(): string;

    /**
     * The order relations the document needs.
     *
     * @return array<int, string>
     */
    abstract protected function relations(): array;

    /**
     * Start the document with the shared title page header and order information.
     */
    protected function begin(string $title, string $heading, Order $order): void
    {
        $this->pdf->init($title.' - '.$order->event);
        $this->pdf->renderHeader($heading, $order->event);
        $this->pdf->renderOrderInfo($order);
    }
}
