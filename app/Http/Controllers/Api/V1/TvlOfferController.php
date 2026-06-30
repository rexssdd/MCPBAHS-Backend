<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TvlOffers\StoreTvlOfferRequest;
use App\Http\Requests\TvlOffers\UpdateTvlOfferRequest;
use App\Http\Resources\TvlOfferResource;
use App\Models\TvlOffer;
use App\Support\StorageUploader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin management of TVL (Technical-Vocational-Livelihood) track offers,
 * including their image. Restricted to admin via the 'role:admin'
 * middleware in routes/api.php — the public, read-only listing lives in
 * PublicController::tvlOffers().
 *
 * Image uploads go through StorageUploader, which tries the app's
 * configured default disk first and automatically falls back to the
 * local 'public' disk if that disk isn't actually reachable (e.g. S3
 * configured via FILESYSTEM_DISK but missing real credentials) — this
 * is what was previously causing POST/PUT here to 500 instead of saving
 * the offer.
 */
class TvlOfferController extends Controller
{
    public function index()
    {
        $offers = TvlOffer::query()
            ->orderBy('display_order')
            ->get();

        return TvlOfferResource::collection($offers);
    }

    public function store(StoreTvlOfferRequest $request)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $stored = $this->storeImage($request->file('image'));
            $data['image_path'] = $stored['path'];
            $data['image_disk'] = $stored['disk'];
        }

        $offer = TvlOffer::create($data);

        return response()->json([
            'message' => 'TVL offer created successfully.',
            'data' => new TvlOfferResource($offer),
        ], 201);
    }

    public function show(TvlOffer $tvl_offer)
    {
        return new TvlOfferResource($tvl_offer);
    }

    public function update(UpdateTvlOfferRequest $request, TvlOffer $tvl_offer)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->deleteImage($tvl_offer);

            $stored = $this->storeImage($request->file('image'));
            $data['image_path'] = $stored['path'];
            $data['image_disk'] = $stored['disk'];
        }

        $tvl_offer->update($data);

        return response()->json([
            'message' => 'TVL offer updated successfully.',
            'data' => new TvlOfferResource($tvl_offer->refresh()),
        ]);
    }

    public function destroy(TvlOffer $tvl_offer)
    {
        $this->deleteImage($tvl_offer);

        $tvl_offer->delete();

        return response()->noContent();
    }

    /**
     * @return array{path: string, disk: string, url: string}
     */
    private function storeImage(\Illuminate\Http\UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        return StorageUploader::store($file, 'tvl-offers', Str::uuid() . '.' . $extension);
    }

    private function deleteImage(TvlOffer $tvl_offer): void
    {
        if (! $tvl_offer->image_path) {
            return;
        }

        $disk = Storage::disk($tvl_offer->image_disk ?: config('filesystems.default'));

        if ($disk->exists($tvl_offer->image_path)) {
            $disk->delete($tvl_offer->image_path);
        }
    }
}