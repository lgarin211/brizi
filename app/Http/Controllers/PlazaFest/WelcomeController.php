<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class WelcomeController extends Controller
{
    public function index()
    {
        $components = [
            'component0' => $this->getSliders(),
            'component1' => $this->getLandingData(),
            'component2' => $this->getEvents(),
            'component3' => $this->getSportFacilities(),
            'component4' => $this->getTenants(),
            'component5' => $this->getSocialMedia(),
            'component6' => $this->getFloorMaps(),
            'component7' => $this->getBookingImages(),
        ];

        return response()->json([
            'message' => 'Data berhasil diambil',
            'data' => $components
        ], 200);
    }

    /**
     * Get sliders data with processed banner URLs
     */
    private function getSliders()
    {
        $sliders = DB::table('sliders')->get();

        return $sliders->map(function ($slider) {
            $slider->banner = $this->generateStorageUrl($slider->banner);
            return $slider;
        });
    }

    /**
     * Get landing page data including banners
     */
    private function getLandingData()
    {
        $sliders = $this->getSliders();
        $banners = $sliders->pluck('banner')->toArray();

        return [
            'title' => setting('landing.welcome_text'),
            'big_banner' => $banners,
            'big_welcome' => setting('landing.big_welcome')
        ];
    }

    /**
     * Get events with processed banner URLs
     */
    private function getEvents()
    {
        return $this->getDataWithImageProcessing(
            'event',
            ['id', 'banner', 'title', 'description', 'created_at', 'updated_at'],
            'banner'
        );
    }

    /**
     * Get sport facilities with processed image URLs
     */
    private function getSportFacilities()
    {
        return $this->getDataWithImageProcessing(
            'sport_facility',
            ['id', 'image', 'description', 'created_at', 'updated_at'],
            'image'
        );
    }

    /**
     * Get tenants with processed image URLs
     */
    private function getTenants()
    {
        return $this->getDataWithImageProcessing(
            'tenants',
            ['id', 'img', 'location', 'created_at', 'updated_at'],
            'img'
        );
    }

    /**
     * Get social media settings
     */
    private function getSocialMedia()
    {
        return [
            'fb' => setting('landing.fb'),
            'ig' => setting('landing.ig')
        ];
    }

    /**
     * Get floor maps with processed URLs
     */
    private function getFloorMaps()
    {
        $maps = [
            setting('lantai.ug_map'),
            setting('lantai.gf_map')
        ];

        return array_map(function ($map) {
            return $map ? $this->generateStorageUrl($map) : null;
        }, array_filter($maps));
    }

    /**
     * Get booking images that are currently active
     */
    private function getBookingImages()
    {
        $images = DB::table('img_dbooking')
            ->where('start_show', '<=', now())
            ->where('end_show', '>=', now())
            ->get();

        return $images->map(function ($image) {
            $image->image = $this->generateStorageUrl($image->image);
            return $image;
        });
    }

    /**
     * Generic method to get data with image processing
     *
     * @param string $table
     * @param array $columns
     * @param string $imageColumn
     * @return \Illuminate\Support\Collection
     */
    private function getDataWithImageProcessing($table, $columns, $imageColumn)
    {
        $data = DB::table($table)->select($columns)->get();

        return $data->map(function ($item) use ($imageColumn) {
            $item->$imageColumn = $this->generateStorageUrl($item->$imageColumn);
            return $item;
        });
    }

    /**
     * Generate storage URL for given path
     *
     * @param string $path
     * @return string
     */
    private function generateStorageUrl($path)
    {
        return url('/storage/' . $path);
    }
}
