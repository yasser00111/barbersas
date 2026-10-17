<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class QrCodeController extends Controller
{
    public function __construct(protected QrCodeService $qr) {}

    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Admin/QrCode/Index', [
            'publicUrl' => $tenant->publicUrl(),
            'queueUrl' => url('/b/'.$tenant->slug.'/queue'),
            'svg' => $this->qr->svg($tenant->publicUrl()),
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
        ]);
    }

    public function download(Request $request): HttpResponse
    {
        $tenant = $request->user()->tenant;

        return response($this->qr->svg($tenant->publicUrl(), 480, 2), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.$tenant->slug.'.svg"',
        ]);
    }
}
