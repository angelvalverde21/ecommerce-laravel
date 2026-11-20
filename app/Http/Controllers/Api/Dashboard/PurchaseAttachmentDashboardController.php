<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Dashboard\AttachmentFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseAttachmentDashboardController extends Controller
{
    private function purchase(Store $store, int $id)
    {
        $user = Auth::user();
        abort_unless($user && ($user->stores()->where('stores.id', $store->id)->exists() || $user->hasRole('master')), 403);
        return $store->purchases()->findOrFail($id);
    }

    public function index(Store $store, int $purchase_id)
    {
        return responseOk($this->purchase($store, $purchase_id)->attachments()->latest('id')->get());
    }

    public function store(Store $store, Request $request, int $purchase_id, AttachmentFileService $files)
    {
        return responseOk($files->store($request, $this->purchase($store, $purchase_id)));
    }

    public function download(Store $store, int $purchase_id, int $attachment_id, AttachmentFileService $files)
    {
        return $files->download($this->purchase($store, $purchase_id), $attachment_id);
    }

    public function preview(Store $store, int $purchase_id, int $attachment_id, AttachmentFileService $files)
    {
        return $files->preview($this->purchase($store, $purchase_id), $attachment_id);
    }
}
