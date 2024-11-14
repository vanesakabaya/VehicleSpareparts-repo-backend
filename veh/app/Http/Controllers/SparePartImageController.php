<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use App\Models\SparePartImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class SparePartImageController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sparepart_id' => 'required|max:255',
                'sparepart_name' => 'required|max:255',
                'new_images.*' => 'nullable|image|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $slug = Str::slug($request->input('sparepart_name'), '-');
            $unique_slug = $this->getUniqueSlug($slug, SparePart::class, 'slug');

            if ($request->hasFile('new_images')) {
                $images = $request->file('new_images');
                foreach ($images as $index => $image) {
                    $filename = 'vsl-' . $unique_slug . '-' . $index . '.' . $image->getClientOriginalExtension();
                    $path = $image->move(\public_path('images/spareparts'), $filename);
                    $url = asset("images/spareparts/" . $filename);
                    $sparePartImage = new SparePartImage([
                        'spare_part_id' => $request->input('sparepart_id'),
                        'image_url' => $url,
                        'created_by' => $request->user()->id
                    ]);
                    $sparePartImage->save();
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Images uploaded successfully!',
                'result' => $sparePartImage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload product image',
                'errors' => $e->getMessage()
            ]);
        }
    }

    public function destroy(Request $request, SparePartImage $sparePartImage)
    {
        try {
            $sparePartImage->load(['spare_part.shop']);
            if ($request->user()->role != 'admin' && $request->user()->id != $sparePartImage->product->shop->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admins or product owner can delete product image.'
                ]);
            }
            // Extracting filename from the URL
            $filename = basename($sparePartImage->image_url);

            // Get the absolute path from the URL
            $absolutePath = public_path('images/spareparts/' . $filename);

            // Check if the file exists before deletion
            if (File::exists($absolutePath)) {
                // Delete the file
                $sparePartImage->delete();
                File::delete($absolutePath);
            } else {
                // $sparePartImage->delete();
                return response()->json([
                    'status' => false,
                    'message' => 'File not found at the specified path',
                    'path' => $absolutePath
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Product Image deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete product image',
                'errors' => $e->getMessage()
            ]);
        }
    }

    function getUniqueSlug($slug, $model, $column = 'slug')
    {
        $originalSlug = $slug;
        $count = 2;

        while ($model::where($column, '=', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
