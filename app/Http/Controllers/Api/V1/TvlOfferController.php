<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TvlOffers\StoreTvlOfferRequest;
use App\Http\Requests\TvlOffers\UpdateTvlOfferRequest;
use App\Http\Resources\TvlOfferResource;
use App\Models\TvlOffer;
use Illuminate\Support\Facades\Storage;

/**
 * Admin management of TVL (Technical-Vocational-Livelihood) track offers,
 * including their image. Restricted to admin via the 'role:admin'
 * middleware in routes/api.php — the public, read-only listing lives in
 * PublicController::tvlOffers().
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
            $data['image_path'] = $request->file('image')->store('tvl-offers', config('filesystems.default'));
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
            $disk = Storage::disk(config('filesystems.default'));

            if ($tvl_offer->image_path && $disk->exists($tvl_offer->image_path)) {
                $disk->delete($tvl_offer->image_path);
            }

            $data['image_path'] = $request->file('image')->store('tvl-offers', config('filesystems.default'));
        }

        $tvl_offer->update($data);

        return response()->json([
            'message' => 'TVL offer updated successfully.',
            'data' => new TvlOfferResource($tvl_offer->refresh()),
        ]);
    }

    public function destroy(TvlOffer $tvl_offer)
    {
        $disk = Storage::disk(config('filesystems.default'));

        if ($tvl_offer->image_path && $disk->exists($tvl_offer->image_path)) {
            $disk->delete($tvl_offer->image_path);
        }

        $tvl_offer->delete();

        return response()->noContent();
    }
}
