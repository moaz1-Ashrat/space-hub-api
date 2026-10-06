<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpaceImageRequest;
use App\Http\Resources\SpaceImageResource;
use App\Models\Space;
use App\Models\SpaceImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SpaceImageController extends Controller
{
    /**
     * POST /api/v1/spaces/{space}/images
     */
    public function store(StoreSpaceImageRequest $request, Space $space)
    {
        // Authorization: owner or admin
        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $space->user_id) {
            abort(403, 'Forbidden');
        }

        return DB::transaction(function () use ($request, $space) {
            // Upload to storage/app/public/spaces/{space_id}/
            $path = $request->file('image')->store("spaces/{$space->id}", 'public');

            // Determine order (append to end)
            $nextOrder = ($space->images()->max('order') ?? -1) + 1;

            // Is this the first image? → make it primary
            $isFirst = $space->images()->count() === 0;
            $isPrimary = $request->boolean('is_primary', $isFirst);

            // If new image is primary, unset others
            if ($isPrimary) {
                $space->images()->update(['is_primary' => false]);
            }

            $image = SpaceImage::create([
                'space_id' => $space->id,
                'image_path' => $path,
                'order' => $nextOrder,
                'is_primary' => $isPrimary,
            ]);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'data' => new SpaceImageResource($image),
            ], 201);
        });
    }

    /**
     * DELETE /api/v1/spaces/images/{image}
     */
    public function destroy(SpaceImage $image)
    {
        $user = auth('sanctum')->user();
        $space = $image->space;

        if (! $user || ($user->role !== 'admin' && $user->id !== $space->user_id)) {
            abort(403, 'Forbidden');
        }

        return DB::transaction(function () use ($image, $space) {
            $wasPrimary = $image->is_primary;

            // Delete file from storage
            Storage::disk('public')->delete($image->image_path);

            // Delete record
            $image->delete();

            // If it was primary, promote the first remaining image
            if ($wasPrimary) {
                $nextImage = $space->images()->first();
                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }

            return response()->json([
                'message' => 'Image deleted successfully',
            ]);
        });
    }

    /**
     * PUT /api/v1/spaces/images/{image}/primary
     */
    public function setPrimary(SpaceImage $image)
    {
        $user = auth('sanctum')->user();
        $space = $image->space;

        if (! $user || ($user->role !== 'admin' && $user->id !== $space->user_id)) {
            abort(403, 'Forbidden');
        }

        return DB::transaction(function () use ($image, $space) {
            // Unset all
            $space->images()->update(['is_primary' => false]);

            // Set new primary
            $image->update(['is_primary' => true]);

            return response()->json([
                'message' => 'Primary image updated',
                'data' => new SpaceImageResource($image->fresh()),
            ]);
        });
    }
}
